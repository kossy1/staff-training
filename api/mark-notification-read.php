<?php
// api/mark-notification-read.php - Mark Notification as Read
header('Content-Type: application/json');
session_start();

require_once '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$notification_id = $data['id'] ?? 0;

if ($notification_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid notification ID']);
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $notification_id, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to update']);
}
?>