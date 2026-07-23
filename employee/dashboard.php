<?php
// employee/dashboard.php - Employee Dashboard
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isEmployee()) {
    header('Location: ../login.php');
    exit();
}

$employee_id = $_SESSION['employee_id'];

// Get employee stats
$total_trainings = $conn->query("SELECT COUNT(*) as count FROM employee_trainings WHERE employee_id = $employee_id")->fetch_assoc()['count'];
$completed_trainings = $conn->query("SELECT COUNT(*) as count FROM employee_trainings WHERE employee_id = $employee_id AND status = 'completed'")->fetch_assoc()['count'];
$ongoing_trainings = $conn->query("SELECT COUNT(*) as count FROM employee_trainings WHERE employee_id = $employee_id AND status = 'in_progress'")->fetch_assoc()['count'];
$certifications = $conn->query("SELECT COUNT(*) as count FROM certifications WHERE employee_id = $employee_id AND status = 'active'")->fetch_assoc()['count'];

// Get upcoming trainings
$upcoming = $conn->query("
    SELECT tp.*, et.status as enrollment_status, et.progress 
    FROM employee_trainings et
    JOIN training_programs tp ON et.training_id = tp.id
    WHERE et.employee_id = $employee_id AND tp.status = 'upcoming'
    ORDER BY tp.start_date ASC
    LIMIT 5
");

// Get recent activities
$activities = $conn->query("
    SELECT * FROM system_logs 
    WHERE user_id = {$_SESSION['user_id']} 
    ORDER BY created_at DESC 
    LIMIT 5
");

// Get development plan
$plan = $conn->query("
    SELECT * FROM development_plans 
    WHERE employee_id = $employee_id 
    ORDER BY created_at DESC 
    LIMIT 1
")->fetch_assoc();

$page_title = 'Dashboard';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-tachometer-alt text-primary"></i> My Dashboard</h1>
            <p class="text-muted">Welcome back, <?php echo htmlspecialchars($full_name); ?>!</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <h3 class="stat-number"><?php echo $total_trainings; ?></h3>
                <p class="stat-label">Total Trainings</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3 class="stat-number"><?php echo $completed_trainings; ?></h3>
                <p class="stat-label">Completed</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-spinner"></i>
                </div>
                <h3 class="stat-number"><?php echo $ongoing_trainings; ?></h3>
                <p class="stat-label">In Progress</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon danger">
                    <i class="fas fa-certificate"></i>
                </div>
                <h3 class="stat-number"><?php echo $certifications; ?></h3>
                <p class="stat-label">Certifications</p>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Upcoming Trainings -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-calendar-alt text-primary"></i> Upcoming Trainings</h5>
                    <a href="my-trainings.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if ($upcoming && $upcoming->num_rows > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php while ($training = $upcoming->fetch_assoc()): ?>
                                <a href="training-details.php?id=<?php echo $training['id']; ?>" 
                                   class="list-group-item list-group-item-action">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($training['title']); ?></h6>
                                            <small class="text-muted">
                                                <i class="far fa-calendar-alt"></i> <?php echo formatDate($training['start_date']); ?>
                                                <span class="mx-1">•</span>
                                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($training['location'] ?? 'TBD'); ?>
                                            </small>
                                        </div>
                                        <span class="badge badge-primary">Upcoming</span>
                                    </div>
                                </a>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-calendar-times fa-2x mb-2 d-block"></i>
                            <p>No upcoming trainings</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Development Plan -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-tasks text-primary"></i> Development Plan</h5>
                    <a href="development-plan.php" class="btn btn-sm btn-outline-primary">View</a>
                </div>
                <div class="card-body">
                    <?php if ($plan): ?>
                        <h6><?php echo htmlspecialchars($plan['title']); ?></h6>
                        <p class="text-muted small"><?php echo htmlspecialchars(substr($plan['description'] ?? '', 0, 100)) . '...'; ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Progress</span>
                            <span class="font-weight-bold"><?php echo $plan['progress']; ?>%</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar" style="width: <?php echo $plan['progress']; ?>%;"></div>
                        </div>
                        <div class="mt-2">
                            <span class="badge badge-<?php echo $plan['status'] == 'completed' ? 'success' : ($plan['status'] == 'in_progress' ? 'warning' : 'secondary'); ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $plan['status'])); ?>
                            </span>
                            <span class="badge badge-<?php echo $plan['priority'] == 'critical' ? 'danger' : ($plan['priority'] == 'high' ? 'warning' : ($plan['priority'] == 'medium' ? 'info' : 'secondary')); ?>">
                                <?php echo ucfirst($plan['priority']); ?>
                            </span>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-tasks fa-2x mb-2 d-block"></i>
                            <p>No development plan assigned yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-history text-primary"></i> Recent Activities</h5>
        </div>
        <div class="card-body p-0">
            <div class="list-group list-group-flush">
                <?php if ($activities && $activities->num_rows > 0): ?>
                    <?php while ($activity = $activities->fetch_assoc()): ?>
                        <div class="list-group-item">
                            <div class="d-flex align-items-center">
                                <div class="activity-icon mr-3">
                                    <i class="fas fa-<?php echo $activity['action'] == 'login' ? 'sign-in-alt text-success' : 'circle text-info'; ?>"></i>
                                </div>
                                <div>
                                    <p class="mb-0"><?php echo ucfirst(str_replace('_', ' ', $activity['action'])); ?></p>
                                    <small class="text-muted">
                                        <i class="far fa-clock"></i> <?php echo timeAgo($activity['created_at']); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                        <p>No recent activities</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.stat-card {
    padding: 20px;
    border-radius: 10px;
    background: white;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    transition: all 0.3s ease;
    height: 100%;
}
.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.2);
}
.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 15px;
}
.stat-icon.primary { background: rgba(102, 126, 234, 0.1); color: #667eea; }
.stat-icon.success { background: rgba(72, 187, 120, 0.1); color: #48bb78; }
.stat-icon.warning { background: rgba(246, 194, 62, 0.1); color: #f6c23e; }
.stat-icon.danger { background: rgba(231, 74, 59, 0.1); color: #e74a3b; }
.stat-number { font-size: 2rem; font-weight: 800; margin: 0; line-height: 1.2; }
.stat-label { color: #6c757d; font-size: 0.9rem; font-weight: 500; margin: 0; }
</style>

<?php require_once 'includes/footer.php'; ?>