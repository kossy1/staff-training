<?php
// trainer/dashboard.php - Trainer Dashboard
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'trainer') {
    header('Location: ../login.php');
    exit();
}

$trainer_id = $_SESSION['trainer_id'];
$trainer = $conn->query("SELECT * FROM trainers WHERE id = $trainer_id")->fetch_assoc();

if (!$trainer) {
    header('Location: ../login.php');
    exit();
}

// ===== STATS =====
$stats = [
    'total_trainings' => $conn->query("SELECT COUNT(*) as c FROM training_programs WHERE trainer_id = $trainer_id")->fetch_assoc()['c'] ?? 0,
    'active_trainings' => $conn->query("SELECT COUNT(*) as c FROM training_programs WHERE trainer_id = $trainer_id AND status = 'ongoing'")->fetch_assoc()['c'] ?? 0,
    'total_students' => $conn->query("
        SELECT COUNT(DISTINCT et.employee_id) as c 
        FROM employee_trainings et
        JOIN training_programs tp ON et.training_id = tp.id
        WHERE tp.trainer_id = $trainer_id
    ")->fetch_assoc()['c'] ?? 0,
    'completed_trainings' => $conn->query("SELECT COUNT(*) as c FROM training_programs WHERE trainer_id = $trainer_id AND status = 'completed'")->fetch_assoc()['c'] ?? 0,
    'pending_requests' => $conn->query("SELECT COUNT(*) as c FROM trainer_requests WHERE trainer_id = $trainer_id AND status = 'pending'")->fetch_assoc()['c'] ?? 0,
    'upcoming_sessions' => $conn->query("SELECT COUNT(*) as c FROM trainer_sessions WHERE trainer_id = $trainer_id AND status = 'scheduled' AND session_date >= NOW()")->fetch_assoc()['c'] ?? 0
];

// ===== MY TRAININGS =====
$my_trainings = $conn->query("
    SELECT tp.*,
           (SELECT COUNT(*) FROM employee_trainings WHERE training_id = tp.id) as enrolled_count
    FROM training_programs tp
    WHERE tp.trainer_id = $trainer_id
    ORDER BY tp.start_date DESC
    LIMIT 5
");

// ===== UPCOMING SESSIONS =====
$upcoming_sessions = $conn->query("
    SELECT ts.*, tp.title as training_title
    FROM trainer_sessions ts
    LEFT JOIN training_programs tp ON ts.training_id = tp.id
    WHERE ts.trainer_id = $trainer_id AND ts.session_date >= NOW()
    ORDER BY ts.session_date ASC
    LIMIT 5
");

// ===== RECENT REQUESTS =====
$recent_requests = $conn->query("
    SELECT tr.*, 
           CONCAT(e.first_name, ' ', e.last_name) as employee_name,
           tp.title as training_title
    FROM trainer_requests tr
    LEFT JOIN employees e ON tr.employee_id = e.id
    LEFT JOIN training_programs tp ON tr.training_id = tp.id
    WHERE tr.trainer_id = $trainer_id
    ORDER BY tr.created_at DESC
    LIMIT 5
");

// ===== RECENT STUDENTS =====
$recent_students = $conn->query("
    SELECT DISTINCT e.id, e.first_name, e.last_name, e.email, e.department,
           et.enrollment_date, et.status as enrollment_status
    FROM employee_trainings et
    JOIN employees e ON et.employee_id = e.id
    JOIN training_programs tp ON et.training_id = tp.id
    WHERE tp.trainer_id = $trainer_id
    ORDER BY et.enrollment_date DESC
    LIMIT 5
");

$page_title = 'Trainer Dashboard';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">
    <!-- Welcome Header -->
    <div class="card mb-4" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); color: white; border: none;">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div class="d-flex align-items-center">
                    <img src="<?php echo $profile_pic; ?>" 
                         class="rounded-circle mr-3" 
                         width="70" height="70"
                         style="border: 3px solid rgba(255,255,255,0.2); object-fit: cover;"
                         onerror="this.src='../assets/images/default-avatar.png'">
                    <div>
                        <h3 class="mb-1" style="color: white;">Welcome back, <?php echo htmlspecialchars($full_name); ?>!</h3>
                        <p class="mb-0" style="color: rgba(255,255,255,0.7);">
                            <i class="fas fa-star text-warning"></i> 
                            <?php echo htmlspecialchars($trainer['specialization']); ?> 
                            <span class="mx-2">•</span>
                            <i class="fas fa-award text-info"></i> 
                            <?php echo $trainer['experience_years']; ?> years experience
                        </p>
                    </div>
                </div>
                <div class="mt-3 mt-md-0">
                    <a href="my-trainings.php" class="btn btn-light">
                        <i class="fas fa-chalkboard-teacher"></i> View My Trainings
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-2">
            <div class="stat-card">
                <div class="stat-icon primary"><i class="fas fa-chalkboard-teacher"></i></div>
                <h3 class="stat-number"><?php echo $stats['total_trainings']; ?></h3>
                <p class="stat-label">Total Trainings</p>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="stat-card">
                <div class="stat-icon success"><i class="fas fa-play-circle"></i></div>
                <h3 class="stat-number"><?php echo $stats['active_trainings']; ?></h3>
                <p class="stat-label">Active</p>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="stat-card">
                <div class="stat-icon info"><i class="fas fa-users"></i></div>
                <h3 class="stat-number"><?php echo $stats['total_students']; ?></h3>
                <p class="stat-label">Students</p>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="stat-card">
                <div class="stat-icon warning"><i class="fas fa-clock"></i></div>
                <h3 class="stat-number"><?php echo $stats['upcoming_sessions']; ?></h3>
                <p class="stat-label">Upcoming Sessions</p>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="stat-card">
                <div class="stat-icon danger"><i class="fas fa-envelope"></i></div>
                <h3 class="stat-number"><?php echo $stats['pending_requests']; ?></h3>
                <p class="stat-label">Pending Requests</p>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="stat-card">
                <div class="stat-icon primary"><i class="fas fa-check-circle"></i></div>
                <h3 class="stat-number"><?php echo $stats['completed_trainings']; ?></h3>
                <p class="stat-label">Completed</p>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- My Trainings -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-chalkboard-teacher text-primary"></i> My Trainings</h5>
                    <a href="my-trainings.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if ($my_trainings && $my_trainings->num_rows > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php while ($t = $my_trainings->fetch_assoc()): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($t['title']); ?></h6>
                                            <small class="text-muted">
                                                <i class="far fa-calendar-alt"></i> <?php echo formatDate($t['start_date']); ?>
                                                <span class="mx-1">•</span>
                                                <i class="fas fa-users"></i> <?php echo $t['enrolled_count']; ?> enrolled
                                            </small>
                                        </div>
                                        <span class="badge badge-<?php 
                                            echo $t['status'] == 'ongoing' ? 'success' : 
                                                ($t['status'] == 'upcoming' ? 'warning' : 
                                                ($t['status'] == 'completed' ? 'info' : 'danger')); 
                                        ?>">
                                            <?php echo ucfirst($t['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-chalkboard-teacher fa-2x mb-2 d-block"></i>
                            No trainings assigned yet
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Upcoming Sessions -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-calendar-check text-success"></i> Upcoming Sessions</h5>
                    <a href="sessions.php" class="btn btn-sm btn-outline-success">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if ($upcoming_sessions && $upcoming_sessions->num_rows > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php while ($s = $upcoming_sessions->fetch_assoc()): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($s['topic'] ?? 'Session'); ?></h6>
                                            <small class="text-muted">
                                                <i class="fas fa-book"></i> <?php echo htmlspecialchars($s['training_title']); ?>
                                                <br>
                                                <i class="far fa-clock"></i> <?php echo formatDateTime($s['session_date']); ?>
                                            </small>
                                        </div>
                                        <span class="badge badge-success">Scheduled</span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-calendar fa-2x mb-2 d-block"></i>
                            No upcoming sessions
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Requests -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-envelope-open-text text-warning"></i> Recent Requests</h5>
                    <a href="requests.php" class="btn btn-sm btn-outline-warning">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if ($recent_requests && $recent_requests->num_rows > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php while ($r = $recent_requests->fetch_assoc()): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($r['subject']); ?></h6>
                                            <small class="text-muted">
                                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($r['employee_name']); ?>
                                                <br>
                                                <i class="far fa-clock"></i> <?php echo timeAgo($r['created_at']); ?>
                                            </small>
                                        </div>
                                        <span class="badge badge-<?php 
                                            echo $r['status'] == 'pending' ? 'warning' : 
                                                ($r['status'] == 'approved' ? 'success' : 
                                                ($r['status'] == 'rejected' ? 'danger' : 'info')); 
                                        ?>">
                                            <?php echo ucfirst($r['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                            No requests
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Students -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-users text-info"></i> Recent Students</h5>
                    <a href="my-students.php" class="btn btn-sm btn-outline-info">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if ($recent_students && $recent_students->num_rows > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php while ($s = $recent_students->fetch_assoc()): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></h6>
                                            <small class="text-muted">
                                                <i class="fas fa-building"></i> <?php echo htmlspecialchars($s['department'] ?? 'N/A'); ?>
                                                <br>
                                                <i class="far fa-calendar-alt"></i> <?php echo formatDate($s['enrollment_date']); ?>
                                            </small>
                                        </div>
                                        <span class="badge badge-info">Enrolled</span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-users fa-2x mb-2 d-block"></i>
                            No students yet
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.stat-card {
    padding: 20px;
    border-radius: 10px;
    background: white;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    height: 100%;
    text-align: center;
}
.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}
.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    margin: 0 auto 12px;
}
.stat-icon.primary { background: rgba(102, 126, 234, 0.1); color: #667eea; }
.stat-icon.success { background: rgba(72, 187, 120, 0.1); color: #48bb78; }
.stat-icon.warning { background: rgba(246, 194, 62, 0.1); color: #f6c23e; }
.stat-icon.danger { background: rgba(231, 74, 59, 0.1); color: #e74a3b; }
.stat-icon.info { background: rgba(54, 185, 204, 0.1); color: #36b9cc; }
.stat-number { font-size: 1.5rem; font-weight: 800; margin: 0; line-height: 1.2; }
.stat-label { color: #6c757d; font-size: 0.75rem; font-weight: 500; margin: 0; }
</style>

<?php require_once 'includes/footer.php'; ?>