<?php
// Common functions used throughout the system

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isManager() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'manager';
}

function isEmployee() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'employee';
}

function getEmployeeName($employeeId) {
    global $conn;
    $stmt = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) as name FROM employees WHERE id = ?");
    $stmt->bind_param("i", $employeeId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['name'];
    }
    return 'Unknown';
}

function getTrainingStatus($trainingId) {
    global $conn;
    $stmt = $conn->prepare("SELECT status FROM training_programs WHERE id = ?");
    $stmt->bind_param("i", $trainingId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['status'];
    }
    return null;
}

function formatDate($date) {
    if (!$date) return 'N/A';
    return date('M d, Y', strtotime($date));
}

function formatDateTime($datetime) {
    if (!$datetime) return 'N/A';
    return date('M d, Y H:i', strtotime($datetime));
}

function getPercentage($part, $total) {
    if ($total == 0) return 0;
    return round(($part / $total) * 100, 2);
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generateUniqueCode() {
    return 'EMP' . date('Ymd') . rand(1000, 9999);
}

function calculateTrainingProgress($employeeId, $trainingId) {
    global $conn;
    $stmt = $conn->prepare("SELECT progress FROM employee_trainings WHERE employee_id = ? AND training_id = ?");
    $stmt->bind_param("ii", $employeeId, $trainingId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['progress'];
    }
    return 0;
}

function getNotifications($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result();
}

function sendEmail($to, $subject, $message) {
    // Use PHPMailer or mail() function
    // For now, use mail() function
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: ' . SITE_NAME . ' <noreply@' . $_SERVER['HTTP_HOST'] . '>' . "\r\n";
    return mail($to, $subject, $message, $headers);
}

function logAction($userId, $action, $details = null) {
    global $conn;
    $detailsJson = $details ? json_encode($details) : null;
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $conn->prepare("INSERT INTO system_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $userId, $action, $detailsJson, $ip);
    return $stmt->execute();
}

function getDashboardStats() {
    global $conn;
    $stats = [];
    
    // Total Employees
    $result = $conn->query("SELECT COUNT(*) as total FROM employees WHERE status = 'active'");
    $stats['total_employees'] = $result->fetch_assoc()['total'] ?? 0;
    
    // Total Trainings
    $result = $conn->query("SELECT COUNT(*) as total FROM training_programs");
    $stats['total_trainings'] = $result->fetch_assoc()['total'] ?? 0;
    
    // Ongoing Trainings
    $result = $conn->query("SELECT COUNT(*) as total FROM training_programs WHERE status = 'ongoing'");
    $stats['ongoing_trainings'] = $result->fetch_assoc()['total'] ?? 0;
    
    // Completed Trainings
    $result = $conn->query("SELECT COUNT(*) as total FROM training_programs WHERE status = 'completed'");
    $stats['completed_trainings'] = $result->fetch_assoc()['total'] ?? 0;
    
    // Certifications Issued
    $result = $conn->query("SELECT COUNT(*) as total FROM certifications");
    $stats['certifications'] = $result->fetch_assoc()['total'] ?? 0;
    
    return $stats;
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function getCurrentUser() {
    if (isset($_SESSION['user_id'])) {
        global $conn;
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    return null;
}

function getEmployeeById($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getTrainingById($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM training_programs WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getStatusBadge($status) {
    $colors = [
        'active' => 'success',
        'inactive' => 'danger',
        'on_leave' => 'warning',
        'upcoming' => 'info',
        'ongoing' => 'success',
        'completed' => 'primary',
        'cancelled' => 'danger',
        'enrolled' => 'info',
        'in_progress' => 'warning',
        'dropped' => 'danger'
    ];
    $color = $colors[$status] ?? 'secondary';
    return '<span class="badge badge-' . $color . '">' . ucfirst($status) . '</span>';
}

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return $diff . ' seconds ago';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' minutes ago';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' hours ago';
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . ' days ago';
    } else {
        return date('M d, Y', $time);
    }
}


?>