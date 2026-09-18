<?php
// admin/trainers.php - Manage All Trainers (Enhanced)
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Ensure trainers table exists
$table_check = $conn->query("SHOW TABLES LIKE 'trainers'");
if (!$table_check || $table_check->num_rows == 0) {
    // Create table if it doesn't exist
    $conn->query("
        CREATE TABLE IF NOT EXISTS trainers (
            id INT PRIMARY KEY AUTO_INCREMENT,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            phone VARCHAR(20),
            specialization VARCHAR(255),
            qualification VARCHAR(255),
            experience_years INT DEFAULT 0,
            bio TEXT,
            profile_picture VARCHAR(255),
            linkedin_url VARCHAR(255),
            twitter_url VARCHAR(255),
            website_url VARCHAR(255),
            address VARCHAR(255),
            city VARCHAR(100),
            state VARCHAR(100),
            country VARCHAR(100) DEFAULT 'Nigeria',
            status ENUM('active', 'inactive', 'on_leave') DEFAULT 'active',
            rating DECIMAL(3,2) DEFAULT 0.00,
            total_trainings INT DEFAULT 0,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

// Get filter parameters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$specialization = isset($_GET['specialization']) ? sanitizeInput($_GET['specialization']) : '';
$sort = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'newest';

// Build query
$query = "SELECT * FROM trainers WHERE 1=1";
$params = [];
$types = "";

if ($search) {
    $query .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR specialization LIKE ? OR qualification LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $types .= "sssss";
}

if ($status) {
    $query .= " AND status = ?";
    $params[] = $status;
    $types .= "s";
}

if ($specialization) {
    $query .= " AND specialization LIKE ?";
    $params[] = "%$specialization%";
    $types .= "s";
}

// Sorting
switch ($sort) {
    case 'oldest':
        $query .= " ORDER BY created_at ASC";
        break;
    case 'name':
        $query .= " ORDER BY first_name ASC";
        break;
    case 'name_desc':
        $query .= " ORDER BY first_name DESC";
        break;
    case 'experience':
        $query .= " ORDER BY experience_years DESC";
        break;
    case 'trainings':
        $query .= " ORDER BY total_trainings DESC";
        break;
    default:
        $query .= " ORDER BY created_at DESC";
}

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$trainers = $stmt->get_result();

// Get statistics
$stats = [
    'total' => $conn->query("SELECT COUNT(*) as count FROM trainers")->fetch_assoc()['count'] ?? 0,
    'active' => $conn->query("SELECT COUNT(*) as count FROM trainers WHERE status = 'active'")->fetch_assoc()['count'] ?? 0,
    'inactive' => $conn->query("SELECT COUNT(*) as count FROM trainers WHERE status = 'inactive'")->fetch_assoc()['count'] ?? 0,
    'on_leave' => $conn->query("SELECT COUNT(*) as count FROM trainers WHERE status = 'on_leave'")->fetch_assoc()['count'] ?? 0,
    'with_trainings' => $conn->query("SELECT COUNT(*) as count FROM trainers WHERE total_trainings > 0")->fetch_assoc()['count'] ?? 0
];

// Get specializations for filter
$specializations = $conn->query("
    SELECT DISTINCT specialization 
    FROM trainers 
    WHERE specialization IS NOT NULL AND specialization != '' 
    ORDER BY specialization
");

$page_title = 'Manage Trainers';
$page_scripts = '
<script>
$(document).ready(function() {
    if ($.fn.DataTable) {
        $("#trainersTable").DataTable({
            responsive: true,
            pageLength: 25,
            ordering: false,
            searching: false,
            lengthChange: false,
            info: true,
            paging: true,
            language: {
                info: "Showing _START_ to _END_ of _TOTAL_ trainers",
                infoEmpty: "No trainers found"
            }
        });
    }
});

function deleteTrainer(id, name) {
    Swal.fire({
        title: "Delete Trainer?",
        html: `Are you sure you want to delete <strong>${name}</strong>?<br>
               <small class="text-danger">This will also unassign them from all trainings!</small>`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Yes, delete it!",
        cancelButtonText: "Cancel"
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "delete-trainer.php?id=" + id;
        }
    });
}

function changeStatus(id, status, name) {
    const labels = {
        "active": "Active",
        "inactive": "Inactive",
        "on_leave": "On Leave"
    };
    
    Swal.fire({
        title: "Change Status",
        html: `Change <strong>${name}</strong> status to <strong>${labels[status]}</strong>?`,
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#667eea",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Yes, update it!",
        cancelButtonText: "Cancel"
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "update-trainer-status.php?id=" + id + "&status=" + status;
        }
    });
}

function resetFilters() {
    window.location.href = "trainers.php";
}
</script>
';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}
.stat-box {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    border-left: 4px solid #667eea;
    transition: all 0.3s ease;
}
.stat-box:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}
.stat-box.success { border-left-color: #48bb78; }
.stat-box.danger { border-left-color: #e74a3b; }
.stat-box.warning { border-left-color: #f6c23e; }
.stat-box.info { border-left-color: #36b9cc; }
.stat-box .stat-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6c757d;
    font-weight: 600;
    margin-bottom: 5px;
}
.stat-box .stat-value {
    font-size: 1.8rem;
    font-weight: 800;
    color: #2d3748;
    line-height: 1;
}
.stat-box .stat-icon {
    float: right;
    font-size: 2rem;
    opacity: 0.15;
    margin-top: -10px;
}

.trainer-avatar-sm {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e2e8f0;
}
.status-badge {
    padding: 5px 12px;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-block;
}
.status-badge.active { background: #d4edda; color: #155724; }
.status-badge.inactive { background: #f8d7da; color: #721c24; }
.status-badge.on_leave { background: #fff3cd; color: #856404; }

.trainer-name {
    font-weight: 700;
    color: #2d3748;
    text-decoration: none;
}
.trainer-name:hover {
    color: #667eea;
    text-decoration: none;
}

.rating-stars {
    color: #f6c23e;
    font-size: 0.85rem;
}

.filter-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    margin-bottom: 25px;
}

.table th {
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6c757d;
    border-top: none;
}
.table td {
    vertical-align: middle;
    padding: 12px 8px;
}

.action-buttons .btn {
    padding: 4px 8px;
    font-size: 0.75rem;
    margin: 0 1px;
}

@media (max-width: 768px) {
    .stat-box .stat-value { font-size: 1.4rem; }
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>

<div class="main-content">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <div>
                <h1><i class="fas fa-user-tie text-primary"></i> Manage Trainers</h1>
                <p class="text-muted">View and manage all training instructors</p>
            </div>
            <div>
                <a href="add-trainer.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Add New Trainer
                </a>
                <button onclick="window.print()" class="btn btn-outline-secondary">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>
    </div>

    <!-- Success Messages -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> 
            <?php 
                switch($_GET['success']) {
                    case 'created': echo "Trainer created successfully!"; break;
                    case 'updated': echo "Trainer updated successfully!"; break;
                    case 'deleted': echo "Trainer deleted successfully!"; break;
                    case 'status': echo "Status updated successfully!"; break;
                }
            ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-box">
            <div class="stat-icon"><i class="fas fa-user-tie"></i></div>
            <div class="stat-label">Total Trainers</div>
            <div class="stat-value"><?php echo $stats['total']; ?></div>
        </div>
        <div class="stat-box success">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-label">Active</div>
            <div class="stat-value text-success"><?php echo $stats['active']; ?></div>
        </div>
        <div class="stat-box danger">
            <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
            <div class="stat-label">Inactive</div>
            <div class="stat-value text-danger"><?php echo $stats['inactive']; ?></div>
        </div>
        <div class="stat-box warning">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-label">On Leave</div>
            <div class="stat-value text-warning"><?php echo $stats['on_leave']; ?></div>
        </div>
        <div class="stat-box info">
            <div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
            <div class="stat-label">With Trainings</div>
            <div class="stat-value text-info"><?php echo $stats['with_trainings']; ?></div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-card">
        <form method="GET" class="row align-items-end">
            <div class="col-md-3">
                <label>Search</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                    </div>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Name, email, specialization..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-2">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="on_leave" <?php echo $status == 'on_leave' ? 'selected' : ''; ?>>On Leave</option>
                </select>
            </div>
            <div class="col-md-2">
                <label>Specialization</label>
                <select name="specialization" class="form-control">
                    <option value="">All</option>
                    <?php while ($spec = $specializations->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($spec['specialization']); ?>"
                            <?php echo $specialization == $spec['specialization'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($spec['specialization']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label>Sort By</label>
                <select name="sort" class="form-control">
                    <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                    <option value="oldest" <?php echo $sort == 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                    <option value="name" <?php echo $sort == 'name' ? 'selected' : ''; ?>>Name (A-Z)</option>
                    <option value="name_desc" <?php echo $sort == 'name_desc' ? 'selected' : ''; ?>>Name (Z-A)</option>
                    <option value="experience" <?php echo $sort == 'experience' ? 'selected' : ''; ?>>Most Experienced</option>
                    <option value="trainings" <?php echo $sort == 'trainings' ? 'selected' : ''; ?>>Most Trainings</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Apply Filters
                </button>
                <button type="button" onclick="resetFilters()" class="btn btn-secondary">
                    <i class="fas fa-undo"></i> Reset
                </button>
            </div>
        </form>
    </div>

   <!-- Trainers Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-list"></i> All Trainers 
            <span class="badge badge-primary ml-2"><?php echo $trainers->num_rows; ?></span>
        </h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="trainersTable">
                <thead>
                    <tr>
                        <th>Trainer</th>
                        <th>Specialization</th>
                        <th>Contact</th>
                        <th>Experience</th>
                        <th>Trainings</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($trainers && $trainers->num_rows > 0): ?>
                        <?php while ($trainer = $trainers->fetch_assoc()): ?>
                            <tr>
                                <!-- Column 1: Trainer -->
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo !empty($trainer['profile_picture']) ? '../uploads/trainers/' . $trainer['profile_picture'] : '../assets/images/default-avatar.png'; ?>" 
                                             alt="<?php echo htmlspecialchars($trainer['first_name']); ?>" 
                                             class="trainer-avatar-sm mr-3"
                                             onerror="this.src='../assets/images/default-avatar.png'">
                                        <div>
                                            <a href="view-trainer.php?id=<?php echo $trainer['id']; ?>" 
                                               class="trainer-name">
                                                <?php echo htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name']); ?>
                                            </a>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($trainer['qualification'] ?? 'No qualification'); ?>
                                            </small>
                                            <?php if (!empty($trainer['rating']) && $trainer['rating'] > 0): ?>
                                                <div class="rating-stars">
                                                    <?php 
                                                        $rating = round($trainer['rating']);
                                                        for ($i = 1; $i <= 5; $i++) {
                                                            echo $i <= $rating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                                        }
                                                    ?>
                                                    <small class="text-muted ml-1"><?php echo number_format($trainer['rating'], 1); ?></small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- Column 2: Specialization -->
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo htmlspecialchars($trainer['specialization'] ?? 'General'); ?>
                                    </span>
                                </td>
                                
                                <!-- Column 3: Contact -->
                                <td>
                                    <small>
                                        <i class="fas fa-envelope text-muted"></i> 
                                        <a href="mailto:<?php echo htmlspecialchars($trainer['email']); ?>">
                                            <?php echo htmlspecialchars($trainer['email']); ?>
                                        </a>
                                        <br>
                                        <i class="fas fa-phone text-muted"></i> 
                                        <?php echo htmlspecialchars($trainer['phone'] ?? 'N/A'); ?>
                                    </small>
                                </td>
                                
                                <!-- Column 4: Experience -->
                                <td>
                                    <span class="badge badge-primary">
                                        <?php echo (int)$trainer['experience_years']; ?> yrs
                                    </span>
                                </td>
                                
                                <!-- Column 5: Trainings -->
                                <td>
                                    <a href="trainings.php?trainer_id=<?php echo $trainer['id']; ?>" 
                                       class="badge badge-secondary" style="text-decoration: none;">
                                        <i class="fas fa-chalkboard-teacher"></i>
                                        <?php echo (int)$trainer['total_trainings']; ?>
                                    </a>
                                </td>
                                
                                <!-- Column 6: Status -->
                                <td>
                                    <span class="status-badge <?php echo $trainer['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $trainer['status'])); ?>
                                    </span>
                                </td>
                                
                                <!-- Column 7: Actions -->
                                <td>
                                    <div class="btn-group btn-group-sm action-buttons">
                                        <a href="view-trainer.php?id=<?php echo $trainer['id']; ?>" 
                                           class="btn btn-outline-info" title="View Profile">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit-trainer.php?id=<?php echo $trainer['id']; ?>" 
                                           class="btn btn-outline-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-outline-secondary dropdown-toggle" 
                                                    data-toggle="dropdown" title="More">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right">
                                                <a class="dropdown-item" href="view-trainer.php?id=<?php echo $trainer['id']; ?>">
                                                    <i class="fas fa-eye"></i> View Profile
                                                </a>
                                                <a class="dropdown-item" href="edit-trainer.php?id=<?php echo $trainer['id']; ?>">
                                                    <i class="fas fa-edit"></i> Edit Trainer
                                                </a>
                                                <a class="dropdown-item" href="trainings.php?trainer_id=<?php echo $trainer['id']; ?>">
                                                    <i class="fas fa-chalkboard-teacher"></i> View Trainings
                                                </a>
                                                <a class="dropdown-item" href="mailto:<?php echo htmlspecialchars($trainer['email']); ?>">
                                                    <i class="fas fa-envelope"></i> Send Email
                                                </a>
                                                <div class="dropdown-divider"></div>
                                                <?php if ($trainer['status'] != 'active'): ?>
                                                    <a class="dropdown-item text-success" href="javascript:void(0)" 
                                                       onclick="changeStatus(<?php echo $trainer['id']; ?>, 'active', '<?php echo htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name']); ?>')">
                                                        <i class="fas fa-check-circle"></i> Set as Active
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($trainer['status'] != 'inactive'): ?>
                                                    <a class="dropdown-item text-danger" href="javascript:void(0)" 
                                                       onclick="changeStatus(<?php echo $trainer['id']; ?>, 'inactive', '<?php echo htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name']); ?>')">
                                                        <i class="fas fa-times-circle"></i> Set as Inactive
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($trainer['status'] != 'on_leave'): ?>
                                                    <a class="dropdown-item text-warning" href="javascript:void(0)" 
                                                       onclick="changeStatus(<?php echo $trainer['id']; ?>, 'on_leave', '<?php echo htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name']); ?>')">
                                                        <i class="fas fa-clock"></i> Set as On Leave
                                                    </a>
                                                <?php endif; ?>
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item text-danger" href="javascript:void(0)" 
                                                   onclick="deleteTrainer(<?php echo $trainer['id']; ?>, '<?php echo htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name']); ?>')">
                                                    <i class="fas fa-trash-alt"></i> Delete Trainer
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fas fa-user-tie fa-4x mb-3 d-block" style="opacity: 0.3;"></i>
                                <h4>No trainers found</h4>
                                <p>Add your first trainer to start assigning them to trainings.</p>
                                <a href="add-trainer.php" class="btn btn-primary">
                                    <i class="fas fa-plus-circle"></i> Add First Trainer
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($trainers->num_rows > 0): ?>
        <div class="card-footer text-muted small">
            <i class="fas fa-info-circle"></i>
            Showing <?php echo $trainers->num_rows; ?> of <?php echo $stats['total']; ?> trainers
        </div>
    <?php endif; ?>
</div>
</div>

<?php require_once 'includes/footer.php'; ?>