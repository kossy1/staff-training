<?php
// admin/update-trainer-status.php - Update Trainer Status
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

$trainer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';

$allowed_statuses = ['active', 'inactive', 'on_leave'];

if ($trainer_id <= 0 || !in_array($status, $allowed_statuses)) {
    header('Location: trainers.php?error=invalid');
    exit();
}

// Check trainer exists
$check = $conn->prepare("SELECT id FROM trainers WHERE id = ?");
$check->bind_param("i", $trainer_id);
$check->execute();
if ($check->get_result()->num_rows == 0) {
    header('Location: trainers.php?error=notfound');
    exit();
}

// Update status
$stmt = $conn->prepare("UPDATE trainers SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $trainer_id);

if ($stmt->execute()) {
    logAction($_SESSION['user_id'], 'trainer_status_updated', [
        'trainer_id' => $trainer_id,
        'new_status' => $status
    ]);
    header('Location: trainers.php?success=status');
} else {
    header('Location: trainers.php?error=update_failed');
}
exit();
?>