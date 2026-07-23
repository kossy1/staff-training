<?php
// employee/feedback.php - Give Training Feedback
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isEmployee()) {
    header('Location: ../login.php');
    exit();
}

$employee_id = $_SESSION['employee_id'];
$message = '';
$message_type = '';
$success = false;

// Get completed trainings for feedback
$completed = $conn->query("
    SELECT et.id as enrollment_id, et.training_id, tp.title, tp.start_date, tp.end_date,
           et.feedback, et.rating
    FROM employee_trainings et
    JOIN training_programs tp ON et.training_id = tp.id
    WHERE et.employee_id = $employee_id 
    AND et.status = 'completed'
    AND (et.feedback IS NULL OR et.feedback = '')
    ORDER BY et.completion_date DESC
");

// Get trainings with existing feedback
$has_feedback = $conn->query("
    SELECT et.id as enrollment_id, et.training_id, tp.title, et.feedback, et.rating
    FROM employee_trainings et
    JOIN training_programs tp ON et.training_id = tp.id
    WHERE et.employee_id = $employee_id 
    AND et.feedback IS NOT NULL 
    AND et.feedback != ''
    ORDER BY et.completion_date DESC
    LIMIT 5
");

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['enrollment_id'])) {
    $enrollment_id = (int)$_POST['enrollment_id'];
    $rating = (int)$_POST['rating'];
    $feedback = trim($_POST['feedback']);
    
    if ($rating < 1 || $rating > 5) {
        $message = "Please select a rating between 1 and 5.";
        $message_type = 'danger';
    } elseif (empty($feedback)) {
        $message = "Please provide feedback.";
        $message_type = 'danger';
    } else {
        $stmt = $conn->prepare("UPDATE employee_trainings SET rating = ?, feedback = ? WHERE id = ? AND employee_id = ?");
        $stmt->bind_param("isii", $rating, $feedback, $enrollment_id, $employee_id);
        
        if ($stmt->execute()) {
            $message = "Thank you for your feedback!";
            $message_type = 'success';
            $success = true;
            
            logAction($_SESSION['user_id'], 'feedback_submitted', ['enrollment_id' => $enrollment_id]);
            
            // Refresh the lists
            $completed = $conn->query("
                SELECT et.id as enrollment_id, et.training_id, tp.title, tp.start_date, tp.end_date,
                       et.feedback, et.rating
                FROM employee_trainings et
                JOIN training_programs tp ON et.training_id = tp.id
                WHERE et.employee_id = $employee_id 
                AND et.status = 'completed'
                AND (et.feedback IS NULL OR et.feedback = '')
                ORDER BY et.completion_date DESC
            ");
            
            $has_feedback = $conn->query("
                SELECT et.id as enrollment_id, et.training_id, tp.title, et.feedback, et.rating
                FROM employee_trainings et
                JOIN training_programs tp ON et.training_id = tp.id
                WHERE et.employee_id = $employee_id 
                AND et.feedback IS NOT NULL 
                AND et.feedback != ''
                ORDER BY et.completion_date DESC
                LIMIT 5
            ");
        } else {
            $message = "Failed to submit feedback. Please try again.";
            $message_type = 'danger';
        }
    }
}

$page_title = 'Give Feedback';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-comment-dots text-primary"></i> Training Feedback</h1>
            <p class="text-muted">Share your thoughts about completed trainings</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
            <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo $message; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Trainings needing feedback -->
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-pencil-alt"></i> Need Your Feedback</h5>
                </div>
                <div class="card-body">
                    <?php if ($completed && $completed->num_rows > 0): ?>
                        <div class="list-group">
                            <?php while ($training = $completed->fetch_assoc()): ?>
                                <div class="list-group-item">
                                    <form method="POST" class="feedback-form">
                                        <input type="hidden" name="enrollment_id" value="<?php echo $training['enrollment_id']; ?>">
                                        
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($training['title']); ?></h6>
                                                <small class="text-muted">
                                                    <i class="far fa-calendar-alt"></i> <?php echo formatDate($training['start_date']); ?>
                                                    <span class="mx-1">•</span>
                                                    <i class="far fa-calendar-check"></i> <?php echo formatDate($training['end_date']); ?>
                                                </small>
                                            </div>
                                            <span class="badge badge-success">Completed</span>
                                        </div>
                                        
                                        <div class="form-group mt-3">
                                            <label>Rating</label>
                                            <div class="rating-stars">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <label class="star-label">
                                                        <input type="radio" name="rating" value="<?php echo $i; ?>" required>
                                                        <i class="far fa-star"></i>
                                                    </label>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Your Feedback</label>
                                            <textarea name="feedback" class="form-control" rows="3" 
                                                      placeholder="Share your experience, what you learned, and suggestions..." required></textarea>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-paper-plane"></i> Submit Feedback
                                        </button>
                                    </form>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-check-circle fa-3x text-success mb-3 d-block"></i>
                            <h5>No Trainings Need Feedback</h5>
                            <p>You've provided feedback for all your completed trainings.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Past Feedback -->
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history"></i> Your Past Feedback</h5>
                </div>
                <div class="card-body p-0">
                    <?php if ($has_feedback && $has_feedback->num_rows > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php while ($feedback = $has_feedback->fetch_assoc()): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($feedback['title']); ?></h6>
                                        <div class="text-warning">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?php echo $i <= $feedback['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <p class="mb-0 small text-muted"><?php echo htmlspecialchars(substr($feedback['feedback'], 0, 100)) . (strlen($feedback['feedback']) > 100 ? '...' : ''); ?></p>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-comment-slash fa-2x mb-2 d-block"></i>
                            <p>No feedback submitted yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.rating-stars {
    display: flex;
    gap: 5px;
    flex-direction: row-reverse;
    justify-content: flex-end;
}
.rating-stars .star-label {
    cursor: pointer;
    font-size: 1.5rem;
    transition: all 0.2s ease;
}
.rating-stars .star-label input {
    display: none;
}
.rating-stars .star-label i {
    color: #ddd;
    transition: all 0.2s ease;
}
.rating-stars .star-label:hover i,
.rating-stars .star-label:hover ~ .star-label i,
.rating-stars .star-label input:checked ~ i {
    color: #f6c23e;
}
.rating-stars .star-label:hover {
    transform: scale(1.2);
}
</style>

<?php require_once 'includes/footer.php'; ?>