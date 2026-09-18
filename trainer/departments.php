<?php
// trainer/departments.php - View Departments of Students
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'trainer') {
    header('Location: ../login.php');
    exit();
}

$trainer_id = $_SESSION['trainer_id'];

// Get departments with student counts from trainer's trainings
$departments = $conn->query("
    SELECT e.department, 
           COUNT(DISTINCT e.id) as student_count,
           COUNT(DISTINCT tp.id) as training_count
    FROM employee_trainings et
    JOIN employees e ON et.employee_id = e.id
    JOIN training_programs tp ON et.training_id = tp.id
    WHERE tp.trainer_id = $trainer_id 
      AND e.department IS NOT NULL 
      AND e.department != ''
    GROUP BY e.department
    ORDER BY student_count DESC
");

$page_title = 'Departments';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h1><i class="fas fa-building text-primary"></i> Departments</h1>
        <p class="text-muted">Departments with students in your trainings</p>
    </div>

    <div class="row">
        <?php if ($departments && $departments->num_rows > 0): ?>
            <?php while ($d = $departments->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="dept-icon mr-3">
                                    <i class="fas fa-building"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0"><?php echo htmlspecialchars($d['department']); ?></h5>
                                    <small class="text-muted">Department</small>
                                </div>
                            </div>
                            <div class="row text-center">
                                <div class="col-6">
                                    <h3 class="text-primary mb-0"><?php echo $d['student_count']; ?></h3>
                                    <small class="text-muted">Students</small>
                                </div>
                                <div class="col-6">
                                    <h3 class="text-success mb-0"><?php echo $d['training_count']; ?></h3>
                                    <small class="text-muted">Trainings</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-building fa-4x text-muted mb-3 d-block" style="opacity: 0.3;"></i>
                        <h4>No Departments</h4>
                        <p class="text-muted">No departments found with students in your trainings.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.dept-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
}
</style>

<?php require_once 'includes/footer.php'; ?>