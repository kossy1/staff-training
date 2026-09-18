<?php
// includes/authentication.php - Fixed Authentication

// ============================================
// SESSION VALIDATION FUNCTIONS
// ============================================

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && 
           isset($_SESSION['logged_in']) && 
           $_SESSION['logged_in'] === true;
}

/**
 * Check if session is valid
 * @return bool
 */
function isSessionValid() {
    if (!isLoggedIn()) {
        return false;
    }
    
    // Check if user still exists in database
    global $conn;
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        logout();
        return false;
    }
    
    // Check session timeout (30 minutes)
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 1800)) {
        logout();
        return false;
    }
    
    return true;
}

/**
 * Check if user is admin
 * @return bool
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Check if user is manager
 * @return bool
 */
function isManager() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'manager';
}

/**
 * Check if user is employee
 * @return bool
 */
function isEmployee() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'employee';
}

/**
 * Require login - Fixed to prevent loops
 */
function requireLogin() {
    if (!isLoggedIn() || !isSessionValid()) {
        header('Location: ../login.php');
        exit();
    }
}

/**
 * Require admin - Fixed
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ../index.php');
        exit();
    }
}

/**
 * Require employee - Fixed
 */
function requireEmployee() {
    requireLogin();
    if (!isEmployee()) {
        header('Location: ../index.php');
        exit();
    }
}
?>