<?php
// trainer/my-students.php - View Students Enrolled in Trainer's Trainings
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'trainer') {
    header('Location: ../login.php');
    exit();
}

$trainer_id = $_SESSION['trainer_id'];

// Get students
$students = $conn->query("
    SELECT DISTINCT e.id, e.first_name, e.last_name, e.email, e.phone, 
           e.department, e.position, e.profile_picture,
           COUNT(et.id) as training_count,
           SUM(CASE WHEN et.status = 'completed' THEN 1 ELSE 0 END) as completed_count
    FROM employee_trainings et
    JOIN employees e ON et.employee_id = e.id
    JOIN training_programs tp ON et.training_id = tp.id
    WHERE tp.trainer_id = $trainer_id
    GROUP BY e.id
    ORDER BY e.first_name ASC
");

$total_students = $students->num_rows;
$total_enrollments = $conn->query("
    SELECT COUNT(*) as c FROM employee_trainings et
    JOIN training_programs tp ON et.training_id = tp.id
    WHERE tp.trainer_id = $trainer_id
")->fetch_assoc()['c'] ?? 0;

$page_title = 'My Students';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h1><i class="fas fa-users text-primary"></i> My Students</h1>
        <p class="text-muted">Employees enrolled in your trainings</p>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6>Total Students</h6>
                    <h2 class="mb-0"><?php echo $total_students; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6>Total Enrollments</h6>
                    <h2 class="mb-0"><?php echo $total_enrollments; ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Students Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="studentsTable">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Contact</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Trainings</th>
                            <th>Completed</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($students && $students->num_rows > 0): ?>
                            <?php while ($s = $students->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo !empty($s['profile_picture']) ? '../uploads/profile-pictures/' . $s['profile_picture'] : '../assets/images/default-avatar.png'; ?>" 
                                                 class="rounded-circle mr-2" width="40" height="40" style="object-fit: cover;"
                                                 onerror="this.src='../assets/images/default-avatar.png'">
                                            <div>
                                                <strong><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <small>
                                            <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($s['email']); ?><br>
                                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($s['phone'] ?? 'N/A'); ?>
                                        </small>
                                    </td>
                                    <td><?php echo htmlspecialchars($s['department'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($s['position'] ?? 'N/A'); ?></td>
                                    <td><span class="badge badge-primary"><?php echo $s['training_count']; ?></span></td>
                                    <td><span class="badge badge-success"><?php echo $s['completed_count']; ?></span></td>
                                    <td>
                                        <a href="view-student.php?id=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="mailto:<?php echo htmlspecialchars($s['email']); ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-envelope"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="fas fa-users fa-3x mb-3 d-block" style="opacity: 0.3;"></i>
                                    <h5>No students yet</h5>
                                    <p>No employees are enrolled in your trainings.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>