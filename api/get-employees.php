<?php
// api/get-employees.php - Get Employees (AJAX)
header('Content-Type: application/json');
session_start();

require_once '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$department = isset($_GET['department']) ? sanitizeInput($_GET['department']) : '';

$query = "SELECT id, first_name, last_name, email, department, position FROM employees WHERE 1=1";
$params = [];
$types = "";

if ($search) {
    $query .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    $types .= "sss";
}

if ($department) {
    $query .= " AND department = ?";
    $params[] = $department;
    $types .= "s";
}

$query .= " ORDER BY first_name LIMIT 50";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$employees = [];
while ($row = $result->fetch_assoc()) {
    $employees[] = $row;
}

echo json_encode($employees);
?>