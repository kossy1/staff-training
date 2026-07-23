<?php
// employee/apply-training.php - Apply for Training
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isEmployee()) {
    header('Location: ../login.php');
    exit();
}

$employee_id = $_SESSION['employee_id'];
$message = '';
$message_type = '';

// Get available trainings
$available = $conn->query("
    SELECT tp.*,
           (SELECT COUNT(*) FROM employee_trainings WHERE training_id = tp.id) as enrolled_count
    FROM training_programs tp
    WHERE tp.status IN ('upcoming', 'ongoing')
    AND tp.id NOT IN (
        SELECT training_id FROM employee_trainings WHERE employee_id = $employee_id
    )
    ORDER BY tp.start_date ASC
");

// Get enrolled trainings count
$enrolled_count = $conn->query("SELECT COUNT(*) as count FROM employee_trainings WHERE employee_id = $employee_id AND status IN ('enrolled', 'in_progress')")->fetch_assoc()['count'];

// Handle application
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['training_id'])) {
    $training_id = (int)$_POST['training_id'];
    
    // Check if already enrolled
    $check = $conn->query("SELECT id FROM employee_trainings WHERE employee_id = $employee_id AND training_id = $training_id");
    if ($check->num_rows > 0) {
        $message = "You are already enrolled in this training!";
        $message_type = 'warning';
    } else {
        // Check if training is full
        $training = $conn->query("SELECT max_participants, current_participants FROM training_programs WHERE id = $training_id")->fetch_assoc();
        if ($training && $training['current_participants'] >= $training['max_participants']) {
            $message = "This training is already full!";
            $message_type = 'danger';
        } else {
            // Enroll
            $stmt = $conn->prepare("
                INSERT INTO employee_trainings (employee_id, training_id, enrollment_date, status) 
                VALUES (?, ?, CURDATE(), 'enrolled')
            ");
            $stmt->bind_param("ii", $employee_id, $training_id);
            
            if ($stmt->execute()) {
                // Update participant count
                $conn->query("UPDATE training_programs SET current_participants = current_participants + 1 WHERE id = $training_id");
                
                // Log action
                logAction($_SESSION['user_id'], 'training_applied', ['training_id' => $training_id]);
                
                $message = "Successfully applied for training!";
                $message_type = 'success';
                
                // Refresh available trainings
                $available = $conn->query("
                    SELECT tp.*,
                           (SELECT COUNT(*) FROM employee_trainings WHERE training_id = tp.id) as enrolled_count
                    FROM training_programs tp
                    WHERE tp.status IN ('upcoming', 'ongoing')
                    AND tp.id NOT IN (
                        SELECT training_id FROM employee_trainings WHERE employee_id = $employee_id
                    )
                    ORDER BY tp.start_date ASC
                ");
            } else {
                $message = "Failed to apply. Please try again.";
                $message_type = 'danger';
            }
        }
    }
}

$page_title = 'Apply for Training';
$page_scripts = '
<script>
$(document).ready(function() {
    $("#availableTable").DataTable({
        responsive: true,
        pageLength: 25,
        order: [[2, "asc"]]
    });
});
</script>
';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-plus-circle text-primary"></i> Apply for Training</h1>
            <p class="text-muted">Browse and apply for available training programs</p>
        </div>
        <div>
            <a href="my-trainings.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> My Trainings
            </a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
            <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : ($message_type == 'warning' ? 'exclamation-triangle' : 'times-circle'); ?>"></i>
            <?php echo $message; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <?php if ($enrolled_count > 0): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> 
            You are currently enrolled in <strong><?php echo $enrolled_count; ?></strong> training(s).
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="availableTable">
                    <thead>
                        <tr>
                            <th>Training</th>
                            <th>Type</th>
                            <th>Start Date</th>
                            <th>Duration</th>
                            <th>Trainer</th>
                            <th>Capacity</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($available && $available->num_rows > 0): ?>
                            <?php while ($training = $available->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($training['title']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($training['category'] ?? 'Uncategorized'); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $training['type'] == 'technical' ? 'primary' : 
                                                ($training['type'] == 'soft_skill' ? 'success' : 
                                                ($training['type'] == 'management' ? 'info' : 'secondary')); 
                                        ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $training['type'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small>
                                            <i class="far fa-calendar-alt"></i> <?php echo formatDate($training['start_date']); ?>
                                            <br>
                                            <i class="far fa-calendar-check"></i> <?php echo formatDate($training['end_date']); ?>
                                        </small>
                                    </td>
                                    <td><?php echo $training['duration_hours']; ?> hours</td>
                                    <td><?php echo htmlspecialchars($training['trainer_name'] ?? 'TBD'); ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span><?php echo $training['current_participants']; ?>/<?php echo $training['max_participants']; ?></span>
                                            <div class="progress ml-2" style="height: 6px; width: 60px;">
                                                <div class="progress-bar" style="width: <?php echo ($training['current_participants'] / $training['max_participants']) * 100; ?>%;"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($training['current_participants'] < $training['max_participants']): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="training_id" value="<?php echo $training['id']; ?>">
                                                <button type="submit" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-check"></i> Apply
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Full</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-check-circle fa-3x text-success mb-3 d-block"></i>
                                    <h5>No Available Trainings</h5>
                                    <p>You are either enrolled in all trainings or no trainings are currently available.</p>
                                    <a href="my-trainings.php" class="btn btn-primary">
                                        <i class="fas fa-chalkboard-teacher"></i> View My Trainings
                                    </a>
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