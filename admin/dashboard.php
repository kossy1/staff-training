<?php
// admin/dashboard.php - Admin Dashboard
require_once '../includes/config.php';
require_once '../includes/session.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Get statistics
$stats = getDashboardStats();

// Get recent activities
$recentActivities = $conn->query("
    SELECT l.*, u.username 
    FROM system_logs l 
    LEFT JOIN users u ON l.user_id = u.id 
    ORDER BY l.created_at DESC 
    LIMIT 10
");

// Get upcoming trainings
$upcomingTrainings = $conn->query("
    SELECT * FROM training_programs 
    WHERE status = 'upcoming' 
    ORDER BY start_date ASC 
    LIMIT 5
");

// Get recent enrollments
$recentEnrollments = $conn->query("
    SELECT e.first_name, e.last_name, tp.title, et.enrollment_date, et.status 
    FROM employee_trainings et 
    JOIN employees e ON et.employee_id = e.id 
    JOIN training_programs tp ON et.training_id = tp.id 
    ORDER BY et.enrollment_date DESC 
    LIMIT 5
");

// Get training completion stats
$completionStats = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
    FROM employee_trainings
");

$completionData = $completionStats->fetch_assoc();
$completionRate = $completionData['total'] > 0 ? 
    round(($completionData['completed'] / $completionData['total']) * 100, 1) : 0;

// Get department wise employee count
$deptStats = $conn->query("
    SELECT department, COUNT(*) as count 
    FROM employees 
    WHERE status = 'active' 
    GROUP BY department 
    ORDER BY count DESC
");

$page_title = 'Dashboard';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <div>
                <h1><i class="fas fa-tachometer-alt text-primary"></i> Dashboard</h1>
                <p class="text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
            </div>
            <div class="btn-toolbar">
                <button class="btn btn-primary" onclick="window.location.reload()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-users"></i>
                </div>
                <h3 class="stat-number"><?php echo number_format($stats['total_employees'] ?? 0); ?></h3>
                <p class="stat-label">Total Employees</p>
                <div class="stat-change up">
                    <i class="fas fa-arrow-up"></i> 12% from last month
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <h3 class="stat-number"><?php echo number_format($stats['total_trainings'] ?? 0); ?></h3>
                <p class="stat-label">Total Trainings</p>
                <div class="stat-change up">
                    <i class="fas fa-arrow-up"></i> 8% from last month
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-clock"></i>
                </div>
                <h3 class="stat-number"><?php echo number_format($stats['ongoing_trainings'] ?? 0); ?></h3>
                <p class="stat-label">Ongoing Trainings</p>
                <div class="stat-change up">
                    <i class="fas fa-arrow-up"></i> 5% from last month
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon danger">
                    <i class="fas fa-certificate"></i>
                </div>
                <h3 class="stat-number"><?php echo number_format($stats['certifications'] ?? 0); ?></h3>
                <p class="stat-label">Certifications Issued</p>
                <div class="stat-change up">
                    <i class="fas fa-arrow-up"></i> 15% from last month
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-line text-primary"></i> Training Overview</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="statsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-pie text-primary"></i> Training Distribution</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="trainingDistributionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Activities -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-history text-primary"></i> Recent Activities</h5>
                    <a href="logs.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php if ($recentActivities && $recentActivities->num_rows > 0): ?>
                            <?php while ($activity = $recentActivities->fetch_assoc()): ?>
                                <div class="list-group-item d-flex align-items-center">
                                    <div class="activity-icon">
                                        <i class="fas fa-<?php echo $activity['action'] == 'login' ? 'sign-in-alt text-success' : 'circle text-info'; ?>"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <p class="mb-0 small">
                                            <strong><?php echo htmlspecialchars($activity['username'] ?? 'System'); ?></strong>
                                            <?php echo htmlspecialchars($activity['action']); ?>
                                        </p>
                                        <small class="text-muted">
                                            <i class="far fa-clock"></i> 
                                            <?php echo timeAgo($activity['created_at']); ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                No recent activities
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upcoming Trainings -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-calendar-alt text-primary"></i> Upcoming Trainings</h5>
                    <a href="trainings.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php if ($upcomingTrainings && $upcomingTrainings->num_rows > 0): ?>
                            <?php while ($training = $upcomingTrainings->fetch_assoc()): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($training['title']); ?></h6>
                                            <small class="text-muted">
                                                <i class="far fa-calendar-alt"></i> 
                                                <?php echo formatDate($training['start_date']); ?>
                                                <span class="mx-1">•</span>
                                                <i class="fas fa-map-marker-alt"></i> 
                                                <?php echo htmlspecialchars($training['location'] ?? 'TBD'); ?>
                                            </small>
                                        </div>
                                        <span class="badge badge-primary">Upcoming</span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-calendar-times fa-2x mb-2 d-block"></i>
                                No upcoming trainings
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Enrollments -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-user-plus text-primary"></i> Recent Enrollments</h5>
                    <a href="enrollments.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Training</th>
                                    <th>Enrollment Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recentEnrollments && $recentEnrollments->num_rows > 0): ?>
                                    <?php while ($enrollment = $recentEnrollments->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($enrollment['first_name'] . ' ' . $enrollment['last_name']); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($enrollment['title']); ?></td>
                                            <td><?php echo formatDate($enrollment['enrollment_date']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo getStatusBadgeClass($enrollment['status']); ?>">
                                                    <?php echo ucfirst($enrollment['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                            No recent enrollments
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts
    if (typeof initCharts === 'function') {
        initCharts();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>