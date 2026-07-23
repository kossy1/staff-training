<?php
// api/get-trainings.php - Get Trainings (AJAX)
header('Content-Type: application/json');
session_start();

require_once '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$type = isset($_GET['type']) ? sanitizeInput($_GET['type']) : '';

$query = "SELECT * FROM training_programs WHERE 1=1";
$params = [];
$types = "";

if ($status) {
    $query .= " AND status = ?";
    $params[] = $status;
    $types .= "s";
}

if ($type) {
    $query .= " AND type = ?";
    $params[] = $type;
    $types .= "s";
}

$query .= " ORDER BY start_date DESC LIMIT 100";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$trainings = [];
while ($row = $result->fetch_assoc()) {
    $trainings[] = $row;
}

echo json_encode($trainings);
?>