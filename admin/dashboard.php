<?php
// admin/dashboard.php - Admin Dashboard (Complete)
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/authentication.php';

// Authentication check
if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

if (!isAdmin()) {
    header('Location: ../index.php');
    exit();
}

// Null-safe helper
if (!function_exists('safeHtml')) {
    function safeHtml($value, $flags = ENT_QUOTES, $encoding = 'UTF-8') {
        return htmlspecialchars((string)($value ?? ''), $flags, $encoding);
    }
}

$user_id = $_SESSION['user_id'];
$employee_id = $_SESSION['employee_id'];

// Get admin info
$admin = $conn->query("
    SELECT u.*, e.first_name, e.last_name, e.profile_picture, e.employee_code, e.department, e.position
    FROM users u
    LEFT JOIN employees e ON u.employee_id = e.id
    WHERE u.id = $user_id
")->fetch_assoc();

$admin_name = trim(($admin['first_name'] ?? '') . ' ' . ($admin['last_name'] ?? ''));
if (empty($admin_name)) $admin_name = $admin['username'] ?? 'Admin';

$admin_pic = !empty($admin['profile_picture']) 
    ? '../uploads/profile-pictures/' . $admin['profile_picture'] 
    : '../assets/images/default-avatar.png';

// ============================================
// DASHBOARD STATISTICS
// ============================================
$stats = [
    'total_employees' => 0,
    'active_employees' => 0,
    'total_trainers' => 0,
    'active_trainers' => 0,
    'total_trainings' => 0,
    'ongoing_trainings' => 0,
    'upcoming_trainings' => 0,
    'completed_trainings' => 0,
    'total_enrollments' => 0,
    'active_enrollments' => 0,
    'completed_enrollments' => 0,
    'total_certifications' => 0,
    'active_certifications' => 0,
    'expiring_certifications' => 0,
    'total_revenue' => 0,
    'pending_payments' => 0,
    'successful_payments' => 0,
    'total_payments' => 0
];

// Employees
$r = $conn->query("SELECT COUNT(*) as c FROM employees");
if ($r) $stats['total_employees'] = $r->fetch_assoc()['c'] ?? 0;

$r = $conn->query("SELECT COUNT(*) as c FROM employees WHERE status = 'active'");
if ($r) $stats['active_employees'] = $r->fetch_assoc()['c'] ?? 0;

// Trainers
$r = $conn->query("SELECT COUNT(*) as c FROM trainers");
if ($r) $stats['total_trainers'] = $r->fetch_assoc()['c'] ?? 0;

$r = $conn->query("SELECT COUNT(*) as c FROM trainers WHERE status = 'active'");
if ($r) $stats['active_trainers'] = $r->fetch_assoc()['c'] ?? 0;

// Trainings
$r = $conn->query("SELECT COUNT(*) as c FROM training_programs");
if ($r) $stats['total_trainings'] = $r->fetch_assoc()['c'] ?? 0;

$r = $conn->query("SELECT COUNT(*) as c FROM training_programs WHERE status = 'ongoing'");
if ($r) $stats['ongoing_trainings'] = $r->fetch_assoc()['c'] ?? 0;

$r = $conn->query("SELECT COUNT(*) as c FROM training_programs WHERE status = 'upcoming'");
if ($r) $stats['upcoming_trainings'] = $r->fetch_assoc()['c'] ?? 0;

$r = $conn->query("SELECT COUNT(*) as c FROM training_programs WHERE status = 'completed'");
if ($r) $stats['completed_trainings'] = $r->fetch_assoc()['c'] ?? 0;

// Enrollments
$r = $conn->query("SELECT COUNT(*) as c FROM employee_trainings");
if ($r) $stats['total_enrollments'] = $r->fetch_assoc()['c'] ?? 0;

$r = $conn->query("SELECT COUNT(*) as c FROM employee_trainings WHERE status IN ('enrolled', 'in_progress')");
if ($r) $stats['active_enrollments'] = $r->fetch_assoc()['c'] ?? 0;

$r = $conn->query("SELECT COUNT(*) as c FROM employee_trainings WHERE status = 'completed'");
if ($r) $stats['completed_enrollments'] = $r->fetch_assoc()['c'] ?? 0;

// Certifications
$r = $conn->query("SELECT COUNT(*) as c FROM certifications");
if ($r) $stats['total_certifications'] = $r->fetch_assoc()['c'] ?? 0;

$r = $conn->query("SELECT COUNT(*) as c FROM certifications WHERE status = 'active'");
if ($r) $stats['active_certifications'] = $r->fetch_assoc()['c'] ?? 0;

$r = $conn->query("
    SELECT COUNT(*) as c FROM certifications 
    WHERE status = 'active' 
    AND expiry_date IS NOT NULL 
    AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
");
if ($r) $stats['expiring_certifications'] = $r->fetch_assoc()['c'] ?? 0;

// Payments (check if table exists)
$table_check = $conn->query("SHOW TABLES LIKE 'payments'");
if ($table_check && $table_check->num_rows > 0) {
    $r = $conn->query("SELECT SUM(amount) as total FROM payments WHERE status = 'success'");
    if ($r) $stats['total_revenue'] = $r->fetch_assoc()['total'] ?? 0;
    
    $r = $conn->query("SELECT COUNT(*) as c FROM payments WHERE status = 'pending'");
    if ($r) $stats['pending_payments'] = $r->fetch_assoc()['c'] ?? 0;
    
    $r = $conn->query("SELECT COUNT(*) as c FROM payments WHERE status = 'success'");
    if ($r) $stats['successful_payments'] = $r->fetch_assoc()['c'] ?? 0;
    
    $r = $conn->query("SELECT COUNT(*) as c FROM payments");
    if ($r) $stats['total_payments'] = $r->fetch_assoc()['c'] ?? 0;
}

// Completion rate
$completion_rate = $stats['total_enrollments'] > 0 
    ? round(($stats['completed_enrollments'] / $stats['total_enrollments']) * 100, 1) 
    : 0;

// ============================================
// RECENT DATA
// ============================================

// Recent enrollments
$recent_enrollments = $conn->query("
    SELECT et.*, 
           CONCAT(e.first_name, ' ', e.last_name) as employee_name,
           e.profile_picture as employee_pic,
           tp.title as training_title,
           tp.type as training_type
    FROM employee_trainings et
    JOIN employees e ON et.employee_id = e.id
    JOIN training_programs tp ON et.training_id = tp.id
    ORDER BY et.enrollment_date DESC, et.id DESC
    LIMIT 6
");

// Recent payments
$recent_payments = null;
if ($table_check && $table_check->num_rows > 0) {
    $recent_payments = $conn->query("
        SELECT p.*,
               CONCAT(e.first_name, ' ', e.last_name) as employee_name,
               e.profile_picture as employee_pic,
               tp.title as training_title
        FROM payments p
        LEFT JOIN employees e ON p.user_id = e.id OR p.user_id = e.user_id
        LEFT JOIN training_programs tp ON p.training_id = tp.id
        ORDER BY p.created_at DESC
        LIMIT 5
    ");
}

// Upcoming trainings
$upcoming_trainings = $conn->query("
    SELECT tp.*,
           CONCAT(t.first_name, ' ', t.last_name) as trainer_name,
           t.profile_picture as trainer_pic,
           (SELECT COUNT(*) FROM employee_trainings WHERE training_id = tp.id) as enrolled_count
    FROM training_programs tp
    LEFT JOIN trainers t ON tp.trainer_id = t.id
    WHERE tp.status IN ('upcoming', 'ongoing')
    AND tp.start_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ORDER BY tp.start_date ASC
    LIMIT 5
");

// Recent activities
$recent_activities = $conn->query("
    SELECT l.*, u.username
    FROM system_logs l
    LEFT JOIN users u ON l.user_id = u.id
    ORDER BY l.created_at DESC
    LIMIT 8
");

// Top trainers by training count
$top_trainers = $conn->query("
    SELECT t.*,
           (SELECT COUNT(*) FROM training_programs WHERE trainer_id = t.id) as training_count
    FROM trainers t
    WHERE t.status = 'active'
    ORDER BY training_count DESC, t.experience_years DESC
    LIMIT 4
");

// Department distribution
$dept_stats = $conn->query("
    SELECT department, COUNT(*) as count 
    FROM employees 
    WHERE status = 'active' AND department IS NOT NULL AND department != ''
    GROUP BY department 
    ORDER BY count DESC
    LIMIT 6
");

$page_title = 'Dashboard';

// Chart data (for JS)
$chart_data = [
    'monthly_enrollments' => [],
    'training_status' => [
        'upcoming' => $stats['upcoming_trainings'],
        'ongoing' => $stats['ongoing_trainings'],
        'completed' => $stats['completed_trainings']
    ]
];

// Get enrollments by month for last 6 months
$enroll_result = $conn->query("
    SELECT DATE_FORMAT(enrollment_date, '%Y-%m') as month, COUNT(*) as count
    FROM employee_trainings
    WHERE enrollment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(enrollment_date, '%Y-%m')
    ORDER BY month ASC
");
if ($enroll_result) {
    while ($row = $enroll_result->fetch_assoc()) {
        $chart_data['monthly_enrollments'][] = $row;
    }
}

$page_scripts = '
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // ===== MONTHLY ENROLLMENTS CHART =====
    var enrollCtx = document.getElementById("enrollmentsChart");
    if (enrollCtx) {
        var enrollData = ' . json_encode($chart_data['monthly_enrollments']) . ';
        new Chart(enrollCtx, {
            type: "line",
            data: {
                labels: enrollData.length > 0 ? enrollData.map(function(d) { return d.month; }) : ["No data"],
                datasets: [{
                    label: "Enrollments",
                    data: enrollData.length > 0 ? enrollData.map(function(d) { return d.count; }) : [0],
                    borderColor: "#667eea",
                    backgroundColor: "rgba(102, 126, 234, 0.1)",
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: "#667eea",
                    pointBorderColor: "#fff",
                    pointBorderWidth: 2,
                    pointRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: "rgba(0,0,0,0.8)",
                        padding: 12,
                        cornerRadius: 8
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: "rgba(0,0,0,0.05)" }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    }
    
    // ===== TRAINING STATUS CHART =====
    var statusCtx = document.getElementById("statusChart");
    if (statusCtx) {
        new Chart(statusCtx, {
            type: "doughnut",
            data: {
                labels: ["Upcoming", "Ongoing", "Completed"],
                datasets: [{
                    data: [
                        ' . $stats['upcoming_trainings'] . ',
                        ' . $stats['ongoing_trainings'] . ',
                        ' . $stats['completed_trainings'] . '
                    ],
                    backgroundColor: ["#f6c23e", "#48bb78", "#36b9cc"],
                    borderWidth: 3,
                    borderColor: "#fff"
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: "70%",
                plugins: {
                    legend: {
                        position: "bottom",
                        labels: { padding: 15, usePointStyle: true }
                    }
                }
            }
        });
    }
});
</script>
';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<style>
/* Dashboard Styles */
.welcome-banner {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    border-radius: 20px;
    padding: 30px;
    color: white;
    margin-bottom: 25px;
    position: relative;
    overflow: hidden;
}
.welcome-banner::before {
    content: '';
    position: absolute;
    top: -50%; right: -10%;
    width: 400px; height: 400px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(102, 126, 234, 0.2) 0%, transparent 70%);
}
.welcome-banner h2 {
    font-weight: 800;
    margin: 0 0 5px;
    position: relative;
    z-index: 1;
}
.welcome-banner p {
    color: rgba(255,255,255,0.7);
    margin: 0;
    position: relative;
    z-index: 1;
}
.welcome-avatar {
    width: 70px; height: 70px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid rgba(255,255,255,0.2);
    position: relative;
    z-index: 1;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}

.stat-box {
    background: white;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    border-left: 4px solid #667eea;
}
.stat-box:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.1);
}
.stat-box.success { border-left-color: #48bb78; }
.stat-box.warning { border-left-color: #f6c23e; }
.stat-box.danger { border-left-color: #e74a3b; }
.stat-box.info { border-left-color: #36b9cc; }
.stat-box.purple { border-left-color: #764ba2; }

.stat-box .stat-icon {
    width: 45px; height: 45px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    background: rgba(102, 126, 234, 0.1);
    color: #667eea;
    margin-bottom: 12px;
}
.stat-box.success .stat-icon { background: rgba(72, 187, 120, 0.1); color: #48bb78; }
.stat-box.warning .stat-icon { background: rgba(246, 194, 62, 0.1); color: #f6c23e; }
.stat-box.danger .stat-icon { background: rgba(231, 74, 59, 0.1); color: #e74a3b; }
.stat-box.info .stat-icon { background: rgba(54, 185, 204, 0.1); color: #36b9cc; }
.stat-box.purple .stat-icon { background: rgba(118, 75, 162, 0.1); color: #764ba2; }

.stat-box .stat-value {
    font-size: 1.8rem;
    font-weight: 800;
    color: #2d3748;
    line-height: 1;
    margin-bottom: 5px;
}
.stat-box .stat-label {
    font-size: 0.8rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}
.stat-box .stat-meta {
    font-size: 0.75rem;
    color: #a0aec0;
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px solid #f1f3f5;
}
.stat-box .stat-meta strong {
    color: #667eea;
}

/* Cards */
.card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    margin-bottom: 20px;
}
.card-header {
    background: transparent;
    border-bottom: 1px solid #f1f3f5;
    padding: 15px 20px;
}
.card-header h5 {
    font-weight: 700;
    font-size: 1rem;
    margin: 0;
}

/* Activity List */
.activity-list {
    list-style: none;
    padding: 0;
    margin: 0;
}
.activity-item {
    display: flex;
    align-items: flex-start;
    padding: 12px 20px;
    border-bottom: 1px solid #f1f3f5;
    transition: background 0.2s ease;
}
.activity-item:last-child { border-bottom: none; }
.activity-item:hover { background: #f8f9fc; }

.activity-icon {
    width: 36px; height: 36px;
    min-width: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    margin-right: 12px;
    background: #f0f4ff;
    color: #667eea;
}

.activity-content {
    flex: 1;
    min-width: 0;
}
.activity-content .title {
    font-size: 0.85rem;
    font-weight: 600;
    color: #2d3748;
    margin: 0 0 3px;
}
.activity-content .time {
    font-size: 0.72rem;
    color: #a0aec0;
}

/* Training Card */
.training-card {
    display: flex;
    gap: 15px;
    padding: 15px 20px;
    border-bottom: 1px solid #f1f3f5;
    transition: background 0.2s ease;
}
.training-card:last-child { border-bottom: none; }
.training-card:hover { background: #f8f9fc; }

.training-date {
    width: 55px; min-width: 55px;
    text-align: center;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-radius: 10px;
    padding: 8px 5px;
}
.training-date .day {
    font-size: 1.3rem;
    font-weight: 800;
    line-height: 1;
}
.training-date .month {
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 2px;
}

.training-info {
    flex: 1;
    min-width: 0;
}
.training-info h6 {
    font-weight: 700;
    font-size: 0.9rem;
    margin: 0 0 5px;
    color: #2d3748;
}
.training-info .meta {
    font-size: 0.75rem;
    color: #6c757d;
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}
.training-info .meta i {
    color: #667eea;
    margin-right: 3px;
}

/* Progress bar */
.progress-thin {
    height: 6px;
    border-radius: 50px;
    background: #f1f3f5;
}

/* Quick Actions */
.quick-actions {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}
.quick-action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 15px 10px;
    background: #f8f9fc;
    border-radius: 12px;
    text-decoration: none;
    color: #2d3748;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}
.quick-action-btn:hover {
    background: white;
    border-color: #667eea;
    transform: translateY(-3px);
    color: #667eea;
    text-decoration: none;
    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.15);
}
.quick-action-btn i {
    font-size: 1.5rem;
    color: #667eea;
    margin-bottom: 8px;
}
.quick-action-btn span {
    font-size: 0.75rem;
    font-weight: 600;
    text-align: center;
}

/* Trainer List */
.trainer-item {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    border-bottom: 1px solid #f1f3f5;
    transition: background 0.2s ease;
}
.trainer-item:last-child { border-bottom: none; }
.trainer-item:hover { background: #f8f9fc; }

.trainer-avatar {
    width: 42px; height: 42px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 12px;
    border: 2px solid #e2e8f0;
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #a0aec0;
}
.empty-state i {
    font-size: 2.5rem;
    margin-bottom: 12px;
    opacity: 0.4;
}
.empty-state p {
    margin: 0;
    font-size: 0.85rem;
}

/* Chart containers */
.chart-container {
    position: relative;
    height: 250px;
}

/* Responsive */
@media (max-width: 768px) {
    .welcome-banner {
        padding: 20px;
        text-align: center;
    }
    .welcome-avatar {
        width: 55px;
        height: 55px;
        margin-bottom: 10px;
    }
    .welcome-banner h2 {
        font-size: 1.2rem;
    }
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .stat-box .stat-value {
        font-size: 1.4rem;
    }
    .stat-box .stat-icon {
        width: 38px;
        height: 38px;
        font-size: 1.1rem;
    }
    .quick-actions {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<div class="main-content">

    <!-- ============================================
         WELCOME BANNER
    ============================================ -->
    <div class="welcome-banner">
        <div class="d-flex align-items-center flex-wrap">
            <img src="<?php echo safeHtml($admin_pic); ?>" 
                 alt="Admin" 
                 class="welcome-avatar mr-3"
                 onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMDAiIGhlaWdodD0iMjAwIiB2aWV3Qm94PSIwIDAgMjAwIDIwMCI+PGRlZnM+PGxpbmVhckdyYWRpZW50IGlkPSJnIiB4MT0iMCUiIHkxPSIwJSIgeDI9IjEwMCUiIHkyPSIxMDAlIj48c3RvcCBvZmZzZXQ9IjAlIiBzdHlsZT0ic3RvcC1jb2xvcjojNjY3ZWVhIi8+PHN0b3Agb2Zmc2V0PSIxMDAlIiBzdHlsZT0ic3RvcC1jb2xvcjojNzY0YmEyIi8+PC9saW5lYXJHcmFkaWVudD48L2RlZnM+PGNpcmNsZSBjeD0iMTAwIiBjeT0iMTAwIiByPSIxMDAiIGZpbGw9InVybCgjZykiLz48Y2lyY2xlIGN4PSIxMDAiIGN5PSI4MCIgcj0iMzUiIGZpbGw9IndoaXRlIi8+PGVsbGlwc2UgY3g9IjEwMCIgY3k9IjE3MCIgcng9IjY1IiByeT0iNDUiIGZpbGw9IndoaXRlIi8+PC9zdmc+';">
            <div class="flex-grow-1">
                <h2>Welcome back, <?php echo safeHtml($admin_name); ?>! 👋</h2>
                <p>
                    <?php echo date('l, F j, Y'); ?>
                    <span class="mx-2">•</span>
                    <?php 
                    $hour = (int)date('H');
                    if ($hour < 12) echo "Good morning";
                    elseif ($hour < 17) echo "Good afternoon";
                    else echo "Good evening";
                    ?>
                    <span class="mx-2">•</span>
                    Staff Training & Development Tracking System
                </p>
            </div>
            <div class="mt-3 mt-md-0" style="position: relative; z-index: 1;">
                <a href="add-training.php" class="btn btn-light">
                    <i class="fas fa-plus-circle text-primary"></i> New Training
                </a>
            </div>
        </div>
    </div>

    <!-- ============================================
         STATS GRID
    ============================================ -->
    <div class="stats-grid">
        
        <div class="stat-box">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-value"><?php echo number_format($stats['total_employees']); ?></div>
            <div class="stat-label">Total Employees</div>
            <div class="stat-meta">
                <strong><?php echo $stats['active_employees']; ?></strong> active
            </div>
        </div>
        
        <div class="stat-box success">
            <div class="stat-icon"><i class="fas fa-user-tie"></i></div>
            <div class="stat-value"><?php echo number_format($stats['total_trainers']); ?></div>
            <div class="stat-label">Total Trainers</div>
            <div class="stat-meta">
                <strong><?php echo $stats['active_trainers']; ?></strong> active
            </div>
        </div>
        
        <div class="stat-box info">
            <div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
            <div class="stat-value"><?php echo number_format($stats['total_trainings']); ?></div>
            <div class="stat-label">Total Trainings</div>
            <div class="stat-meta">
                <strong><?php echo $stats['ongoing_trainings']; ?></strong> ongoing
            </div>
        </div>
        
        <div class="stat-box warning">
            <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
            <div class="stat-value"><?php echo number_format($stats['total_enrollments']); ?></div>
            <div class="stat-label">Total Enrollments</div>
            <div class="stat-meta">
                <strong><?php echo $stats['active_enrollments']; ?></strong> active
            </div>
        </div>
        
        <div class="stat-box purple">
            <div class="stat-icon"><i class="fas fa-certificate"></i></div>
            <div class="stat-value"><?php echo number_format($stats['total_certifications']); ?></div>
            <div class="stat-label">Certifications</div>
            <div class="stat-meta">
                <strong><?php echo $stats['active_certifications']; ?></strong> active
            </div>
        </div>
        
        <div class="stat-box danger">
            <div class="stat-icon"><i class="fas fa-credit-card"></i></div>
            <div class="stat-value"><?php echo formatNairaShort($stats['total_revenue']); ?></div>
            <div class="stat-label">Total Revenue</div>
            <div class="stat-meta">
                <?php echo $stats['successful_payments']; ?> payments
            </div>
        </div>
        
    </div>

    <!-- ============================================
         ALERTS
    ============================================ -->
    <?php if ($stats['expiring_certifications'] > 0): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <strong><?php echo $stats['expiring_certifications']; ?> certification(s)</strong> 
            are expiring within 30 days. 
            <a href="expired-certifications.php" class="font-weight-bold">Review now →</a>
        </div>
    <?php endif; ?>

    <?php if ($stats['pending_payments'] > 0): ?>
        <div class="alert alert-info">
            <i class="fas fa-clock"></i>
            <strong><?php echo $stats['pending_payments']; ?> payment(s)</strong> 
            are pending confirmation.
            <a href="payments.php?status=pending" class="font-weight-bold">View →</a>
        </div>
    <?php endif; ?>

    <!-- ============================================
         CHARTS
    ============================================ -->
    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="fas fa-chart-line text-primary"></i> Enrollment Trends (Last 6 Months)</h5>
                    <span class="badge badge-success"><?php echo $completion_rate; ?>% completion</span>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="enrollmentsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5><i class="fas fa-chart-pie text-primary"></i> Training Status</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================
         QUICK ACTIONS
    ============================================ -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-bolt text-warning"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="quick-actions">
                        <a href="add-employee.php" class="quick-action-btn">
                            <i class="fas fa-user-plus"></i>
                            <span>Add Employee</span>
                        </a>
                        <a href="add-trainer.php" class="quick-action-btn">
                            <i class="fas fa-user-tie"></i>
                            <span>Add Trainer</span>
                        </a>
                        <a href="add-training.php" class="quick-action-btn">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <span>New Training</span>
                        </a>
                        <a href="add-certification.php" class="quick-action-btn">
                            <i class="fas fa-certificate"></i>
                            <span>Issue Certificate</span>
                        </a>
                        <a href="reports.php" class="quick-action-btn">
                            <i class="fas fa-file-alt"></i>
                            <span>Reports</span>
                        </a>
                        <a href="backup.php" class="quick-action-btn">
                            <i class="fas fa-database"></i>
                            <span>Backup</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================
         RECENT DATA ROW
    ============================================ -->
    <div class="row">
        
        <!-- Recent Enrollments -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="fas fa-user-plus text-primary"></i> Recent Enrollments</h5>
                    <a href="enrollments.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if ($recent_enrollments && $recent_enrollments->num_rows > 0): ?>
                        <?php while ($e = $recent_enrollments->fetch_assoc()): ?>
                            <div class="activity-item">
                                <img src="<?php echo !empty($e['employee_pic']) ? '../uploads/profile-pictures/' . $e['employee_pic'] : '../assets/images/default-avatar.png'; ?>" 
                                     class="activity-icon" 
                                     style="object-fit: cover;"
                                     onerror="this.onerror=null; this.src='../assets/images/default-avatar.png'">
                                <div class="activity-content">
                                    <p class="title"><?php echo safeHtml($e['employee_name']); ?></p>
                                    <div class="time">
                                        <i class="fas fa-book text-primary"></i> 
                                        <?php echo safeHtml($e['training_title']); ?>
                                    </div>
                                </div>
                                <span class="badge badge-<?php echo getStatusBadgeClass($e['status']); ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $e['status'])); ?>
                                </span>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox d-block"></i>
                            <p>No recent enrollments</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Upcoming Trainings -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="fas fa-calendar-alt text-primary"></i> Upcoming Trainings</h5>
                    <a href="trainings.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if ($upcoming_trainings && $upcoming_trainings->num_rows > 0): ?>
                        <?php while ($t = $upcoming_trainings->fetch_assoc()): ?>
                            <?php 
                            $start = strtotime($t['start_date']);
                            $now = time();
                            $days = floor(($start - $now) / 86400);
                            ?>
                            <div class="training-card">
                                <div class="training-date">
                                    <div class="day"><?php echo date('d', $start); ?></div>
                                    <div class="month"><?php echo date('M', $start); ?></div>
                                </div>
                                <div class="training-info">
                                    <h6><?php echo safeHtml($t['title']); ?></h6>
                                    <div class="meta">
                                        <span><i class="fas fa-user-tie"></i> <?php echo safeHtml($t['trainer_name'] ?? 'TBD'); ?></span>
                                        <span><i class="fas fa-users"></i> <?php echo $t['enrolled_count']; ?>/<?php echo $t['max_participants']; ?></span>
                                        <span><i class="fas fa-clock"></i> <?php echo $t['duration_hours']; ?>h</span>
                                    </div>
                                    <div class="progress progress-thin mt-2">
                                        <div class="progress-bar bg-<?php 
                                            echo $t['status'] == 'ongoing' ? 'success' : 'warning'; 
                                        ?>" style="width: <?php 
                                            echo $t['max_participants'] > 0 ? ($t['enrolled_count'] / $t['max_participants']) * 100 : 0; 
                                        ?>%"></div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="badge badge-<?php echo $t['status'] == 'ongoing' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($t['status']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times d-block"></i>
                            <p>No upcoming trainings</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
    </div>

    <!-- ============================================
         TRAINERS & PAYMENTS
    ============================================ -->
    <div class="row">
        
        <!-- Top Trainers -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="fas fa-trophy text-warning"></i> Top Trainers</h5>
                    <a href="trainers.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if ($top_trainers && $top_trainers->num_rows > 0): ?>
                        <?php while ($t = $top_trainers->fetch_assoc()): ?>
                            <div class="trainer-item">
                                <img src="<?php echo !empty($t['profile_picture']) ? '../uploads/trainers/' . $t['profile_picture'] : '../assets/images/default-avatar.png'; ?>" 
                                     class="trainer-avatar"
                                     onerror="this.onerror=null; this.src='../assets/images/default-avatar.png'">
                                <div class="flex-grow-1">
                                    <a href="view-trainer.php?id=<?php echo $t['id']; ?>" 
                                       class="text-dark font-weight-bold" style="font-size: 0.9rem; text-decoration: none;">
                                        <?php echo safeHtml($t['first_name'] . ' ' . $t['last_name']); ?>
                                    </a>
                                    <div class="text-muted" style="font-size: 0.75rem;">
                                        <?php echo safeHtml($t['specialization'] ?? 'General'); ?>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="badge badge-primary"><?php echo $t['training_count']; ?> trainings</div>
                                    <div class="text-muted" style="font-size: 0.7rem;">
                                        <?php echo $t['experience_years']; ?> yrs exp
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-user-tie d-block"></i>
                            <p>No trainers yet</p>
                            <a href="add-trainer.php" class="btn btn-sm btn-primary mt-2">
                                <i class="fas fa-plus"></i> Add First Trainer
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Recent Payments -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="fas fa-credit-card text-success"></i> Recent Payments</h5>
                    <a href="payments.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if ($recent_payments && $recent_payments->num_rows > 0): ?>
                        <?php while ($p = $recent_payments->fetch_assoc()): ?>
                            <div class="activity-item">
                                <div class="activity-icon" style="background: rgba(72, 187, 120, 0.1); color: #48bb78;">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="activity-content">
                                    <p class="title"><?php echo safeHtml($p['employee_name'] ?? 'Unknown'); ?></p>
                                    <div class="time">
                                        <i class="fas fa-book"></i> <?php echo safeHtml($p['training_title'] ?? 'N/A'); ?>
                                        <br>
                                        <i class="fas fa-hashtag"></i> <?php echo safeHtml($p['reference'] ?? ''); ?>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-weight-bold" style="color: #48bb78; font-size: 0.9rem;">
                                        <?php echo formatNaira($p['amount'] ?? 0); ?>
                                    </div>
                                    <span class="badge badge-<?php 
                                        echo ($p['status'] ?? '') == 'success' ? 'success' : 
                                            (($p['status'] ?? '') == 'pending' ? 'warning' : 'danger'); 
                                    ?>">
                                        <?php echo ucfirst($p['status'] ?? 'unknown'); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-credit-card d-block"></i>
                            <p>No recent payments</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
    </div>

    <!-- ============================================
         RECENT ACTIVITIES
    ============================================ -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="fas fa-history text-primary"></i> Recent Activities</h5>
                    <a href="logs.php" class="btn btn-sm btn-outline-primary">View All Logs</a>
                </div>
                <div class="card-body p-0">
                    <?php if ($recent_activities && $recent_activities->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 20%;">User</th>
                                        <th style="width: 25%;">Action</th>
                                        <th style="width: 25%;">Details</th>
                                        <th style="width: 15%;">IP Address</th>
                                        <th style="width: 15%;">Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($a = $recent_activities->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo safeHtml($a['username'] ?? 'System'); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    $action = $a['action'] ?? '';
                                                    if (strpos($action, 'login') !== false) echo 'success';
                                                    elseif (strpos($action, 'delete') !== false) echo 'danger';
                                                    elseif (strpos($action, 'create') !== false || strpos($action, 'add') !== false) echo 'info';
                                                    elseif (strpos($action, 'update') !== false || strpos($action, 'edit') !== false) echo 'warning';
                                                    else echo 'secondary';
                                                ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $a['action'] ?? '')); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                $details = $a['details'] ?? '';
                                                if (!empty($details)) {
                                                    $decoded = json_decode($details, true);
                                                    if (is_array($decoded)) {
                                                        $parts = [];
                                                        foreach ($decoded as $k => $v) {
                                                            if (is_scalar($v)) {
                                                                $parts[] = "<strong>{$k}:</strong> " . safeHtml($v);
                                                            }
                                                        }
                                                        echo implode(' • ', array_slice($parts, 0, 2));
                                                    }
                                                } else {
                                                    echo '<span class="text-muted">—</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <code style="font-size: 0.75rem;"><?php echo safeHtml($a['ip_address'] ?? 'N/A'); ?></code>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <i class="far fa-clock"></i>
                                                    <?php echo timeAgo($a['created_at'] ?? ''); ?>
                                                </small>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox d-block"></i>
                            <p>No recent activities</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<?php require_once 'includes/footer.php'; ?>