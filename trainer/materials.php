<?php
// trainer/materials.php - Training Materials Management
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'trainer') {
    header('Location: ../login.php');
    exit();
}

$trainer_id = $_SESSION['trainer_id'] ?? 0;

// Fallback for trainer_id
if ($trainer_id == 0) {
    $user_id = $_SESSION['user_id'];
    $user_email = $_SESSION['email'] ?? '';
    $stmt = $conn->prepare("SELECT id FROM trainers WHERE user_id = ? OR email = ? LIMIT 1");
    $stmt->bind_param("is", $user_id, $user_email);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        $trainer_id = $row['id'];
        $_SESSION['trainer_id'] = $trainer_id;
    } else {
        session_destroy();
        header('Location: ../login.php');
        exit();
    }
}

$trainer = $conn->query("SELECT * FROM trainers WHERE id = $trainer_id")->fetch_assoc();
$full_name = $trainer['first_name'] . ' ' . $trainer['last_name'];
$profile_pic = !empty($trainer['profile_picture']) ? '../uploads/trainers/' . $trainer['profile_picture'] : '../assets/images/default-avatar.png';

$message = '';
$message_type = '';

// ===== HANDLE ACTIONS =====

// Upload Material
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_material'])) {
    $training_id = (int)$_POST['training_id'];
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $resource_url = trim($_POST['resource_url'] ?? '');
    
    // Verify training belongs to this trainer
    $check = $conn->prepare("SELECT id FROM training_programs WHERE id = ? AND trainer_id = ?");
    $check->bind_param("ii", $training_id, $trainer_id);
    $check->execute();
    $valid = $check->get_result()->num_rows > 0;
    
    if (!$valid) {
        $message = "You don't have access to this training.";
        $message_type = 'danger';
    } elseif (empty($title)) {
        $message = "Title is required.";
        $message_type = 'danger';
    } else {
        $file_path = null;
        
        // Handle file upload
        if (isset($_FILES['material_file']) && $_FILES['material_file']['error'] == 0) {
            $allowed = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'zip', 'jpg', 'jpeg', 'png', 'gif'];
            $ext = strtolower(pathinfo($_FILES['material_file']['name'], PATHINFO_EXTENSION));
            $max_size = 20 * 1024 * 1024; // 20MB
            
            if (!in_array($ext, $allowed)) {
                $message = "Invalid file type. Allowed: " . implode(', ', $allowed);
                $message_type = 'danger';
            } elseif ($_FILES['material_file']['size'] > $max_size) {
                $message = "File is too large. Maximum 20MB.";
                $message_type = 'danger';
            } else {
                $upload_dir = '../uploads/materials/';
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $new_filename = 'material_' . $training_id . '_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                
                if (move_uploaded_file($_FILES['material_file']['tmp_name'], $upload_dir . $new_filename)) {
                    $file_path = $new_filename;
                } else {
                    $message = "Failed to upload file.";
                    $message_type = 'danger';
                }
            }
        }
        
        if (empty($message)) {
            $stmt = $conn->prepare("
                INSERT INTO training_materials (training_id, title, description, file_path, resource_url, uploaded_by)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("issssi", $training_id, $title, $description, $file_path, $resource_url, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $message = "Material uploaded successfully!";
                $message_type = 'success';
                logAction($_SESSION['user_id'], 'material_uploaded', ['training_id' => $training_id]);
            } else {
                $message = "Failed to save material: " . $conn->error;
                $message_type = 'danger';
            }
        }
    }
}

// Delete Material
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $mat_id = (int)$_GET['delete'];
    
    // Verify ownership
    $check = $conn->prepare("
        SELECT tm.*, tp.trainer_id 
        FROM training_materials tm
        JOIN training_programs tp ON tm.training_id = tp.id
        WHERE tm.id = ? AND tp.trainer_id = ?
    ");
    $check->bind_param("ii", $mat_id, $trainer_id);
    $check->execute();
    $material = $check->get_result()->fetch_assoc();
    
    if ($material) {
        // Delete file
        if (!empty($material['file_path'])) {
            $file = '../uploads/materials/' . $material['file_path'];
            if (file_exists($file)) unlink($file);
        }
        
        // Delete DB record
        $del = $conn->prepare("DELETE FROM training_materials WHERE id = ?");
        $del->bind_param("i", $mat_id);
        $del->execute();
        
        logAction($_SESSION['user_id'], 'material_deleted', ['material_id' => $mat_id]);
        
        header('Location: materials.php?deleted=1');
        exit();
    }
}

// ===== FILTERS =====
$training_filter = isset($_GET['training_id']) ? (int)$_GET['training_id'] : 0;
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// ===== GET TRAINER'S TRAININGS FOR DROPDOWN =====
$my_trainings = $conn->query("
    SELECT id, title, status 
    FROM training_programs 
    WHERE trainer_id = $trainer_id 
    ORDER BY title ASC
");

// ===== GET MATERIALS =====
$query = "
    SELECT tm.*, tp.title as training_title, tp.status as training_status
    FROM training_materials tm
    JOIN training_programs tp ON tm.training_id = tp.id
    WHERE tp.trainer_id = ?
";
$params = [$trainer_id];
$types = "i";

if ($training_filter > 0) {
    $query .= " AND tm.training_id = ?";
    $params[] = $training_filter;
    $types .= "i";
}

if ($search) {
    $query .= " AND (tm.title LIKE ? OR tm.description LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

$query .= " ORDER BY tm.uploaded_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$materials = $stmt->get_result();

// Stats
$total_materials = $conn->query("
    SELECT COUNT(*) as c FROM training_materials tm
    JOIN training_programs tp ON tm.training_id = tp.id
    WHERE tp.trainer_id = $trainer_id
")->fetch_assoc()['c'] ?? 0;

$with_files = $conn->query("
    SELECT COUNT(*) as c FROM training_materials tm
    JOIN training_programs tp ON tm.training_id = tp.id
    WHERE tp.trainer_id = $trainer_id AND tm.file_path IS NOT NULL
")->fetch_assoc()['c'] ?? 0;

$with_urls = $conn->query("
    SELECT COUNT(*) as c FROM training_materials tm
    JOIN training_programs tp ON tm.training_id = tp.id
    WHERE tp.trainer_id = $trainer_id AND tm.resource_url IS NOT NULL AND tm.resource_url != ''
")->fetch_assoc()['c'] ?? 0;

$page_title = 'Training Materials';
$page_scripts = '
<script>
$(document).ready(function() {
    // File input label
    $(".custom-file-input").on("change", function() {
        var fileName = $(this).val().split("\\\\").pop();
        $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
    });
    
    if ($.fn.DataTable) {
        $("#materialsTable").DataTable({
            responsive: true,
            pageLength: 15,
            order: [[0, "desc"]],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search materials...",
                info: "Showing _START_ to _END_ of _TOTAL_ materials",
                infoEmpty: "No materials found"
            }
        });
    }
});

function deleteMaterial(id, title) {
    Swal.fire({
        title: "Delete Material?",
        html: "Are you sure you want to delete <strong>" + title + "</strong>?<br><small class=\'text-danger\'>This cannot be undone!</small>",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Yes, delete it!",
        cancelButtonText: "Cancel"
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "materials.php?delete=" + id;
        }
    });
}

function copyLink(url) {
    navigator.clipboard.writeText(url).then(function() {
        Swal.fire({
            icon: "success",
            title: "Link Copied!",
            timer: 1500,
            showConfirmButton: false
        });
    });
}
</script>
';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<style>
.materials-wrapper {
    padding: 0;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}

.stat-mini {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    border-left: 4px solid #667eea;
    transition: all 0.3s ease;
}
.stat-mini:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}
.stat-mini.files { border-left-color: #48bb78; }
.stat-mini.urls { border-left-color: #36b9cc; }

.stat-mini .d-flex {
    display: flex;
    align-items: center;
    gap: 15px;
}
.stat-mini .stat-icon {
    width: 45px;
    height: 45px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    background: rgba(102, 126, 234, 0.1);
    color: #667eea;
    flex-shrink: 0;
}
.stat-mini.files .stat-icon { background: rgba(72, 187, 120, 0.1); color: #48bb78; }
.stat-mini.urls .stat-icon { background: rgba(54, 185, 204, 0.1); color: #36b9cc; }

.stat-mini .stat-number {
    font-size: 1.6rem;
    font-weight: 800;
    color: #2d3748;
    line-height: 1;
}
.stat-mini .stat-label {
    font-size: 0.75rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 3px;
    font-weight: 600;
}

.material-item {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    margin-bottom: 15px;
    display: flex;
    align-items: flex-start;
    gap: 20px;
    border-left: 4px solid #667eea;
}
.material-item:hover {
    transform: translateX(5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.material-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    flex-shrink: 0;
    background: #f8f9fc;
}
.material-icon.pdf { background: #fee; color: #e74a3b; }
.material-icon.doc { background: #e7f3ff; color: #2b6cb0; }
.material-icon.xls { background: #e8f5e9; color: #38a169; }
.material-icon.ppt { background: #fff3e0; color: #dd6b20; }
.material-icon.img { background: #f3e5f5; color: #805ad5; }
.material-icon.zip { background: #fffaf0; color: #975a16; }
.material-icon.link { background: #e0f2fe; color: #0284c7; }
.material-icon.default { background: #f8f9fc; color: #667eea; }

.material-content {
    flex: 1;
    min-width: 0;
}

.material-content h6 {
    font-weight: 700;
    color: #2d3748;
    margin-bottom: 8px;
    font-size: 1rem;
    line-height: 1.4;
}

.material-content .description {
    color: #6c757d;
    font-size: 0.85rem;
    margin-bottom: 10px;
    line-height: 1.5;
}

.material-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    font-size: 0.78rem;
    color: #a0aec0;
}
.material-meta span {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.material-meta i {
    color: #667eea;
}

.material-actions {
    display: flex;
    flex-direction: column;
    gap: 5px;
    flex-shrink: 0;
}
.material-actions .btn {
    padding: 6px 12px;
    font-size: 0.75rem;
    border-radius: 6px;
    white-space: nowrap;
}

.filter-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    margin-bottom: 25px;
}

.upload-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    margin-bottom: 25px;
    overflow: hidden;
}
.upload-card .card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px 20px;
    border: none;
}
.upload-card .card-header h5 {
    margin: 0;
    font-weight: 700;
}

.empty-state {
    text-align: center;
    padding: 80px 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.empty-state i {
    font-size: 4rem;
    color: #cbd5e0;
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .material-item {
        flex-direction: column;
        gap: 15px;
    }
    .material-icon {
        width: 50px;
        height: 50px;
        font-size: 1.5rem;
    }
    .material-actions {
        flex-direction: row;
        flex-wrap: wrap;
        width: 100%;
    }
    .material-actions .btn {
        flex: 1;
        min-width: 80px;
    }
    .stats-grid {
        grid-template-columns: 1fr 1fr;
    }
}
</style>

<div class="main-content materials-wrapper">

    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <div>
                <h1><i class="fas fa-book text-primary"></i> Training Materials</h1>
                <p class="text-muted">Upload and manage learning materials for your trainings</p>
            </div>
            <div>
                <a href="my-trainings.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> My Trainings
                </a>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
            <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo $message; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> Material deleted successfully!
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-mini">
            <div class="d-flex">
                <div class="stat-icon"><i class="fas fa-book"></i></div>
                <div>
                    <div class="stat-number"><?php echo $total_materials; ?></div>
                    <div class="stat-label">Total Materials</div>
                </div>
            </div>
        </div>
        <div class="stat-mini files">
            <div class="d-flex">
                <div class="stat-icon"><i class="fas fa-file"></i></div>
                <div>
                    <div class="stat-number"><?php echo $with_files; ?></div>
                    <div class="stat-label">Files Uploaded</div>
                </div>
            </div>
        </div>
        <div class="stat-mini urls">
            <div class="d-flex">
                <div class="stat-icon"><i class="fas fa-link"></i></div>
                <div>
                    <div class="stat-number"><?php echo $with_urls; ?></div>
                    <div class="stat-label">External Links</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Form -->
    <div class="upload-card">
        <div class="card-header">
            <h5><i class="fas fa-cloud-upload-alt"></i> Upload New Material</h5>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="required">Training</label>
                        <select name="training_id" class="form-control" required>
                            <option value="">Select Training</option>
                            <?php 
                            $my_trainings->data_seek(0);
                            while ($t = $my_trainings->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $t['id']; ?>">
                                    <?php echo htmlspecialchars($t['title']); ?>
                                    <?php if ($t['status'] == 'completed'): ?> ✓<?php endif; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="required">Material Title</label>
                        <input type="text" name="title" class="form-control" 
                               placeholder="e.g., Introduction Slides" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>External Link (optional)</label>
                        <input type="url" name="resource_url" class="form-control" 
                               placeholder="https://example.com/resource">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2" 
                                  placeholder="Brief description of this material"></textarea>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Upload File (optional)</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="material_file" 
                                   name="material_file">
                            <label class="custom-file-label" for="material_file">Choose file...</label>
                        </div>
                        <small class="text-muted">
                            Max 20MB • PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, TXT, ZIP, JPG, PNG
                        </small>
                    </div>
                    <div class="col-md-6 mb-3 d-flex align-items-end">
                        <button type="submit" name="upload_material" class="btn btn-primary btn-block btn-lg">
                            <i class="fas fa-upload"></i> Upload Material
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Filter -->
    <div class="filter-card">
        <form method="GET" class="row align-items-end">
            <div class="col-md-4 mb-2">
                <label>Search</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                    </div>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search materials..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-4 mb-2">
                <label>Training</label>
                <select name="training_id" class="form-control">
                    <option value="">All Trainings</option>
                    <?php 
                    $my_trainings->data_seek(0);
                    while ($t = $my_trainings->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $t['id']; ?>" 
                            <?php echo $training_filter == $t['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($t['title']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </div>
            <div class="col-md-2 mb-2">
                <a href="materials.php" class="btn btn-secondary btn-block">
                    <i class="fas fa-undo"></i> Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Materials List -->
    <?php if ($materials && $materials->num_rows > 0): ?>
        <div class="materials-list">
            <?php while ($m = $materials->fetch_assoc()): ?>
                <?php
                // Determine icon class
                $icon_class = 'default';
                $icon_name = 'file';
                
                if (!empty($m['file_path'])) {
                    $ext = strtolower(pathinfo($m['file_path'], PATHINFO_EXTENSION));
                    switch ($ext) {
                        case 'pdf': $icon_class = 'pdf'; $icon_name = 'file-pdf'; break;
                        case 'doc': case 'docx': $icon_class = 'doc'; $icon_name = 'file-word'; break;
                        case 'xls': case 'xlsx': $icon_class = 'xls'; $icon_name = 'file-excel'; break;
                        case 'ppt': case 'pptx': $icon_class = 'ppt'; $icon_name = 'file-powerpoint'; break;
                        case 'jpg': case 'jpeg': case 'png': case 'gif': $icon_class = 'img'; $icon_name = 'file-image'; break;
                        case 'zip': $icon_class = 'zip'; $icon_name = 'file-archive'; break;
                        default: $icon_class = 'default'; $icon_name = 'file';
                    }
                } elseif (!empty($m['resource_url'])) {
                    $icon_class = 'link';
                    $icon_name = 'link';
                }
                ?>
                <div class="material-item">
                    
                    <!-- Icon -->
                    <div class="material-icon <?php echo $icon_class; ?>">
                        <i class="fas fa-<?php echo $icon_name; ?>"></i>
                    </div>
                    
                    <!-- Content -->
                    <div class="material-content">
                        <h6><?php echo htmlspecialchars($m['title']); ?></h6>
                        
                        <?php if (!empty($m['description'])): ?>
                            <p class="description"><?php echo htmlspecialchars($m['description']); ?></p>
                        <?php endif; ?>
                        
                        <div class="material-meta">
                            <span>
                                <i class="fas fa-chalkboard-teacher"></i>
                                <?php echo htmlspecialchars($m['training_title']); ?>
                            </span>
                            <span>
                                <i class="far fa-clock"></i>
                                <?php echo timeAgo($m['uploaded_at']); ?>
                            </span>
                            <?php if (!empty($m['file_path'])): ?>
                                <?php
                                $file_path = '../uploads/materials/' . $m['file_path'];
                                $size = file_exists($file_path) ? formatFileSize(filesize($file_path)) : 'N/A';
                                ?>
                                <span>
                                    <i class="fas fa-database"></i>
                                    <?php echo $size; ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($m['resource_url'])): ?>
                                <span>
                                    <i class="fas fa-external-link-alt"></i>
                                    External Link
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="material-actions">
                        <?php if (!empty($m['file_path'])): ?>
                            <a href="../uploads/materials/<?php echo $m['file_path']; ?>" 
                               target="_blank" 
                               class="btn btn-primary">
                                <i class="fas fa-download"></i> Download
                            </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($m['resource_url'])): ?>
                            <a href="<?php echo htmlspecialchars($m['resource_url']); ?>" 
                               target="_blank" 
                               class="btn btn-info">
                                <i class="fas fa-external-link-alt"></i> Open Link
                            </a>
                            <button onclick="copyLink('<?php echo htmlspecialchars($m['resource_url']); ?>')" 
                                    class="btn btn-outline-info">
                                <i class="fas fa-copy"></i> Copy Link
                            </button>
                        <?php endif; ?>
                        
                        <button onclick="deleteMaterial(<?php echo $m['id']; ?>, '<?php echo addslashes(htmlspecialchars($m['title'])); ?>')" 
                                class="btn btn-outline-danger">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <!-- Empty State -->
        <div class="empty-state">
            <i class="fas fa-book"></i>
            <h3>No Materials Found</h3>
            <p class="text-muted">
                <?php if ($search || $training_filter): ?>
                    No materials match your filter criteria.
                <?php else: ?>
                    You haven't uploaded any training materials yet. Use the form above to get started!
                <?php endif; ?>
            </p>
            <?php if ($search || $training_filter): ?>
                <a href="materials.php" class="btn btn-primary">
                    <i class="fas fa-undo"></i> Reset Filters
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

<?php require_once 'includes/footer.php'; ?>