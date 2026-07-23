<?php
// Authentication functions

function login($email, $password) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['employee_id'] = $row['employee_id'];
            
            logAction($row['id'], 'login', ['ip' => $_SERVER['REMOTE_ADDR']]);
            return true;
        }
    }
    return false;
}

function logout() {
    if (isset($_SESSION['user_id'])) {
        logAction($_SESSION['user_id'], 'logout');
    }
    session_destroy();
    return true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        redirect('index.php');
        exit();
    }
}

function requireEmployee() {
    requireLogin();
    if (!isEmployee()) {
        redirect('index.php');
        exit();
    }
}

function checkPermission($requiredRole) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $role = $_SESSION['role'];
    $permissions = [
        'admin' => ['admin', 'manager', 'employee'],
        'manager' => ['manager', 'employee'],
        'employee' => ['employee']
    ];
    
    return in_array($role, $permissions[$requiredRole] ?? []);
}

function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

function getCurrentUserRole() {
    return $_SESSION['role'] ?? null;
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentEmployeeId() {
    return $_SESSION['employee_id'] ?? null;
}

function isSessionValid() {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // Check if user still exists in database
    global $conn;
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}
?>