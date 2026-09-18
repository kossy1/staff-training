<?php
// employee/enroll-free-training.php - Enroll in Free Training
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isEmployee()) {
    header('Location: ../login.php');
    exit();
}

$employee_id = $_SESSION['employee_id'];
$training_id = isset($_GET['training_id']) ? (int)$_GET['training_id'] : 0;

if ($training_id <= 0) {
    header('Location: my-trainings.php');
    exit();
}

// Check if training is free
$training = $conn->query("SELECT cost, title FROM training_programs WHERE id = $training_id")->fetch_assoc();

if (!$training || $training['cost'] > 0) {
    header('Location: my-trainings.php');
    exit();
}

// Check if already enrolled
$check = $conn->query("SELECT id FROM employee_trainings WHERE employee_id = $employee_id AND training_id = $training_id");
if ($check->num_rows > 0) {
    header('Location: my-trainings.php?msg=already_enrolled');
    exit();
}

// Enroll for free
$stmt = $conn->prepare("
    INSERT INTO employee_trainings (employee_id, training_id, enrollment_date, status, payment_status) 
    VALUES (?, ?, CURDATE(), 'enrolled', 'paid')
");
$stmt->bind_param("ii", $employee_id, $training_id);

if ($stmt->execute()) {
    // Update participant count
    $conn->query("UPDATE training_programs SET current_participants = current_participants + 1 WHERE id = $training_id");
    
    logAction($_SESSION['user_id'], 'free_training_enrolled', ['training_id' => $training_id]);
    
    header('Location: my-trainings.php?msg=enrolled_free');
} else {
    header('Location: my-trainings.php?msg=enrollment_failed');
}
exit();
?>