<?php
// employee/remove-profile-picture.php - Remove Profile Picture
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isEmployee()) {
    header('Location: ../login.php');
    exit();
}

$employee_id = $_SESSION['employee_id'];

// Get current profile picture
$stmt = $conn->prepare("SELECT profile_picture FROM employees WHERE id = ?");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();

if ($employee && !empty($employee['profile_picture'])) {
    // Delete file
    $file_path = '../uploads/profile-pictures/' . $employee['profile_picture'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    
    // Update database
    $stmt = $conn->prepare("UPDATE employees SET profile_picture = NULL WHERE id = ?");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    
    // Clear session
    unset($_SESSION['profile_picture']);
    
    logAction($_SESSION['user_id'], 'profile_picture_removed');
}

header('Location: profile.php?success=removed');
exit();
?>