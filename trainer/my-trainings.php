<?php
// trainer/my-trainings.php - Trainer's Assigned Trainings
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'trainer') {
    header('Location: ../login.php');
    exit();
}

$trainer_id = $_SESSION['trainer_id'] ?? 0;

// Fallback: Get trainer_id if missing
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
        header('Location: ../login.php?error=no_trainer_profile');
        exit();
    }
}

// Get trainer
$trainer = $conn->query("SELECT * FROM trainers WHERE id = $trainer_id")->fetch_assoc();
if (!$trainer) {
    header('Location: ../login.php');
    exit();
}

$full_name = $trainer['first_name'] . ' ' . $trainer['last_name'];
$profile_pic = !empty($trainer['profile_picture']) ? '../uploads/trainers/' . $trainer['profile_picture'] : '../assets/images/default-avatar.png';

// ===== FILTERS =====
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$type_filter = isset($_GET['type']) ? sanitizeInput($_GET['type']) : '';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// ===== BUILD QUERY =====
$query = "
    SELECT tp.*,
           (SELECT COUNT(*) FROM employee_trainings WHERE training_id = tp.id) as enrolled_count,
           (SELECT COUNT(*) FROM employee_trainings WHERE training_id = tp.id AND status = 'completed') as completed_count,
           (SELECT COUNT(*) FROM employee_trainings WHERE training_id = tp.id AND status = 'in_progress') as in_progress_count,
           (SELECT COUNT(*) FROM trainer_sessions WHERE training_id = tp.id) as session_count
    FROM training_programs tp
    WHERE tp.trainer_id = ?
";

$params = [$trainer_id];
$types = "i";

if ($status_filter) {
    $query .= " AND tp.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($type_filter) {
    $query .= " AND tp.type = ?";
    $params[] = $type_filter;
    $types .= "s";
}

if ($search) {
    $query .= " AND (tp.title LIKE ? OR tp.description LIKE ? OR tp.category LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

$query .= " ORDER BY 
    CASE tp.status 
        WHEN 'ongoing' THEN 1 
        WHEN 'upcoming' THEN 2 
        WHEN 'completed' THEN 3 
        ELSE 4 
    END,
    tp.start_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$trainings = $stmt->get_result();

// ===== STATS =====
$stats = [
    'total' => $conn->query("SELECT COUNT(*) as c FROM training_programs WHERE trainer_id = $trainer_id")->fetch_assoc()['c'] ?? 0,
    'ongoing' => $conn->query("SELECT COUNT(*) as c FROM training_programs WHERE trainer_id = $trainer_id AND status = 'ongoing'")->fetch_assoc()['c'] ?? 0,
    'upcoming' => $conn->query("SELECT COUNT(*) as c FROM training_programs WHERE trainer_id = $trainer_id AND status = 'upcoming'")->fetch_assoc()['c'] ?? 0,
    'completed' => $conn->query("SELECT COUNT(*) as c FROM training_programs WHERE trainer_id = $trainer_id AND status = 'completed'")->fetch_assoc()['c'] ?? 0,
    'total_students' => $conn->query("
        SELECT COUNT(DISTINCT et.employee_id) as c
        FROM employee_trainings et
        JOIN training_programs tp ON et.training_id = tp.id
        WHERE tp.trainer_id = $trainer_id
    ")->fetch_assoc()['c'] ?? 0
];

$page_title = 'My Trainings';
$page_scripts = '
<script>
$(document).ready(function() {
    if ($.fn.DataTable) {
        $("#trainingsTable").DataTable({
            responsive: true,
            pageLength: 12,
            ordering: false,
            searching: false,
            lengthChange: false,
            info: true,
            paging: true,
            language: {
                info: "Showing _START_ to _END_ of _TOTAL_ trainings",
                infoEmpty: "No trainings found",
                emptyTable: "No trainings assigned"
            }
        });
    }
});

function viewTraining(id) {
    window.location.href = "view-training.php?id=" + id;
}

function viewStudents(trainingId) {
    window.location.href = "my-students.php?training_id=" + trainingId;
}

function viewSessions(trainingId) {
    window.location.href = "sessions.php?training_id=" + trainingId;
}

function viewMaterials(trainingId) {
    window.location.href = "materials.php?training_id=" + trainingId;
}

function copyJoinLink(id, title) {
    const link = window.location.origin + "/staff-training/employee/apply-training.php?training_id=" + id;
    navigator.clipboard.writeText(link).then(function() {
        Swal.fire({
            icon: "success",
            title: "Link Copied!",
            html: "<p><strong>" + title + "</strong></p><p>Share this link with employees to enroll:</p><code style=\'font-size:11px;\'>" + link + "</code>",
            confirmButtonText: "OK"
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
.my-trainings-wrapper {
    padding: 0;
}
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}
.stat-mini {
    background: white;
    border-radius: 12px;
    padding: 18px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    border-left: 4px solid #667eea;
}
.stat-mini:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}
.stat-mini.ongoing { border-left-color: #48bb78; }
.stat-mini.upcoming { border-left-color: #f6c23e; }
.stat-mini.completed { border-left-color: #36b9cc; }
.stat-mini.students { border-left-color: #e74a3b; }

.stat-mini .stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px;
    font-size: 1.1rem;
    background: rgba(102, 126, 234, 0.1);
    color: #667eea;
}
.stat-mini.ongoing .stat-icon { background: rgba(72, 187, 120, 0.1); color: #48bb78; }
.stat-mini.upcoming .stat-icon { background: rgba(246, 194, 62, 0.1); color: #f6c23e; }
.stat-mini.completed .stat-icon { background: rgba(54, 185, 204, 0.1); color: #36b9cc; }
.stat-mini.students .stat-icon { background: rgba(231, 74, 59, 0.1); color: #e74a3b; }

.stat-mini .stat-number {
    font-size: 1.5rem;
    font-weight: 800;
    color: #2d3748;
    line-height: 1;
}
.stat-mini .stat-label {
    font-size: 0.75rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 5px;
    font-weight: 600;
}

.training-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
    border-top: 4px solid #667eea;
}
.training-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.12);
}
.training-card.status-ongoing { border-top-color: #48bb78; }
.training-card.status-upcoming { border-top-color: #f6c23e; }
.training-card.status-completed { border-top-color: #36b9cc; }
.training-card.status-cancelled { border-top-color: #e74a3b; }

.training-card-header {
    padding: 15px 20px;
    border-bottom: 1px solid #f1f3f5;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
}

.training-card-body {
    padding: 20px;
    flex: 1;
}

.training-card-body h5 {
    font-size: 1.05rem;
    font-weight: 700;
    color: #2d3748;
    margin-bottom: 10px;
    line-height: 1.4;
}

.training-card-body .description {
    font-size: 0.85rem;
    color: #6c757d;
    margin-bottom: 15px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.5;
}

.training-meta {
    display: flex;
    flex-direction: column;
    gap: 8px;
    font-size: 0.82rem;
    color: #718096;
    margin-bottom: 15px;
}
.training-meta div {
    display: flex;
    align-items: center;
    gap: 8px;
}
.training-meta i {
    color: #667eea;
    width: 16px;
    text-align: center;
}

.training-progress-bar {
    margin: 15px 0;
}
.training-progress-bar .d-flex {
    display: flex;
    justify-content: space-between;
    font-size: 0.8rem;
    color: #6c757d;
    margin-bottom: 5px;
}

.training-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #f1f3f5;
}
.training-stats .stat-item {
    text-align: center;
}
.training-stats .stat-item .num {
    font-size: 1.2rem;
    font-weight: 800;
    color: #2d3748;
    line-height: 1;
}
.training-stats .stat-item .lbl {
    font-size: 0.65rem;
    color: #a0aec0;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    margin-top: 3px;
}

.training-card-footer {
    padding: 15px 20px;
    background: #f8f9fc;
    border-top: 1px solid #f1f3f5;
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.training-card-footer .btn {
    flex: 1;
    min-width: 60px;
    padding: 6px 10px;
    font-size: 0.75rem;
    font-weight: 600;
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

.status-badge {
    padding: 5px 12px;
    border-radius: 50px;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.status-badge.ongoing { background: #d4edda; color: #155724; }
.status-badge.upcoming { background: #fff3cd; color: #856404; }
.status-badge.completed { background: #d1ecf1; color: #0c5460; }
.status-badge.cancelled { background: #f8d7da; color: #721c24; }

.type-badge {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
}
.type-badge.technical { background: rgba(102, 126, 234, 0.15); color: #667eea; }
.type-badge.soft_skill { background: rgba(72, 187, 120, 0.15); color: #48bb78; }
.type-badge.management { background: rgba(54, 185, 204, 0.15); color: #36b9cc; }
.type-badge.compliance { background: rgba(246, 194, 62, 0.15); color: #f6c23e; }
.type-badge.other { background: rgba(108, 117, 125, 0.15); color: #6c757d; }

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
    .stat-mini .stat-number { font-size: 1.2rem; }
    .stat-mini .stat-icon { width: 35px; height: 35px; font-size: 1rem; }
    .training-card-body { padding: 15px; }
    .training-card-header { padding: 12px 15px; }
    .training-card-footer { padding: 12px 15px; }
    .training-card-footer .btn { font-size: 0.7rem; padding: 5px 8px; }
}
</style>

<div class="main-content my-trainings-wrapper">

    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <div>
                <h1><i class="fas fa-chalkboard-teacher text-primary"></i> My Trainings</h1>
                <p class="text-muted">All trainings assigned to you</p>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-mini">
            <div class="stat-icon"><i class="fas fa-list"></i></div>
            <div class="stat-number"><?php echo $stats['total']; ?></div>
            <div class="stat-label">Total</div>
        </div>
        <div class="stat-mini ongoing">
            <div class="stat-icon"><i class="fas fa-play-circle"></i></div>
            <div class="stat-number"><?php echo $stats['ongoing']; ?></div>
            <div class="stat-label">Ongoing</div>
        </div>
        <div class="stat-mini upcoming">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-number"><?php echo $stats['upcoming']; ?></div>
            <div class="stat-label">Upcoming</div>
        </div>
        <div class="stat-mini completed">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-number"><?php echo $stats['completed']; ?></div>
            <div class="stat-label">Completed</div>
        </div>
        <div class="stat-mini students">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-number"><?php echo $stats['total_students']; ?></div>
            <div class="stat-label">Students</div>
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
                           placeholder="Search trainings..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-2 mb-2">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="">All Status</option>
                    <option value="ongoing" <?php echo $status_filter == 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                    <option value="upcoming" <?php echo $status_filter == 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                    <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <label>Type</label>
                <select name="type" class="form-control">
                    <option value="">All Types</option>
                    <option value="technical" <?php echo $type_filter == 'technical' ? 'selected' : ''; ?>>Technical</option>
                    <option value="soft_skill" <?php echo $type_filter == 'soft_skill' ? 'selected' : ''; ?>>Soft Skill</option>
                    <option value="management" <?php echo $type_filter == 'management' ? 'selected' : ''; ?>>Management</option>
                    <option value="compliance" <?php echo $type_filter == 'compliance' ? 'selected' : ''; ?>>Compliance</option>
                    <option value="other" <?php echo $type_filter == 'other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </div>
            <div class="col-md-2 mb-2">
                <a href="my-trainings.php" class="btn btn-secondary btn-block">
                    <i class="fas fa-undo"></i> Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Trainings Grid -->
    <?php if ($trainings && $trainings->num_rows > 0): ?>
        <div class="row">
            <?php while ($t = $trainings->fetch_assoc()): ?>
                <?php 
                $progress = $t['enrolled_count'] > 0 ? round(($t['completed_count'] / $t['enrolled_count']) * 100) : 0;
                $training_id = $t['id'];
                $status = $t['status'];
                ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="training-card status-<?php echo $status; ?>">
                        
                        <!-- Card Header -->
                        <div class="training-card-header">
                            <span class="type-badge <?php echo $t['type']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $t['type'])); ?>
                            </span>
                            <span class="status-badge <?php echo $status; ?>">
                                <?php echo ucfirst($status); ?>
                            </span>
                        </div>
                        
                        <!-- Card Body -->
                        <div class="training-card-body">
                            <h5><?php echo htmlspecialchars($t['title']); ?></h5>
                            
                            <p class="description">
                                <?php echo htmlspecialchars($t['description'] ?? 'No description available.'); ?>
                            </p>
                            
                            <div class="training-meta">
                                <div>
                                    <i class="far fa-calendar-alt"></i>
                                    <span><?php echo formatDate($t['start_date']); ?> - <?php echo formatDate($t['end_date']); ?></span>
                                </div>
                                <div>
                                    <i class="fas fa-clock"></i>
                                    <span><?php echo $t['duration_hours']; ?> hours</span>
                                </div>
                                <?php if (!empty($t['location'])): ?>
                                <div>
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($t['location']); ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($t['category'])): ?>
                                <div>
                                    <i class="fas fa-folder"></i>
                                    <span><?php echo htmlspecialchars($t['category']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Progress -->
                            <div class="training-progress-bar">
                                <div class="d-flex">
                                    <span>Completion Rate</span>
                                    <strong><?php echo $progress; ?>%</strong>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-success" style="width: <?php echo $progress; ?>%;"></div>
                                </div>
                            </div>
                            
                            <!-- Stats -->
                            <div class="training-stats">
                                <div class="stat-item">
                                    <div class="num text-primary"><?php echo $t['enrolled_count']; ?></div>
                                    <div class="lbl">Enrolled</div>
                                </div>
                                <div class="stat-item">
                                    <div class="num text-warning"><?php echo $t['in_progress_count']; ?></div>
                                    <div class="lbl">Active</div>
                                </div>
                                <div class="stat-item">
                                    <div class="num text-success"><?php echo $t['completed_count']; ?></div>
                                    <div class="lbl">Done</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Card Footer -->
                        <div class="training-card-footer">
                            <button onclick="viewTraining(<?php echo $training_id; ?>)" 
                                    class="btn btn-primary" title="View Details">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <button onclick="viewStudents(<?php echo $training_id; ?>)" 
                                    class="btn btn-info" title="View Students">
                                <i class="fas fa-users"></i> Students
                            </button>
                            <button onclick="viewSessions(<?php echo $training_id; ?>)" 
                                    class="btn btn-success" title="Sessions">
                                <i class="fas fa-calendar-check"></i> Sessions
                            </button>
                            <button onclick="viewMaterials(<?php echo $training_id; ?>)" 
                                    class="btn btn-warning" title="Materials">
                                <i class="fas fa-book"></i> Materials
                            </button>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <!-- Empty State -->
        <div class="empty-state">
            <i class="fas fa-chalkboard-teacher"></i>
            <h3>No Trainings Found</h3>
            <p class="text-muted">
                <?php if ($search || $status_filter || $type_filter): ?>
                    No trainings match your filter criteria. Try resetting the filters.
                <?php else: ?>
                    You don't have any trainings assigned yet.
                <?php endif; ?>
            </p>
            <?php if ($search || $status_filter || $type_filter): ?>
                <a href="my-trainings.php" class="btn btn-primary">
                    <i class="fas fa-undo"></i> Reset Filters
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

<?php require_once 'includes/footer.php'; ?>