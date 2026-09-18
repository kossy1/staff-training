<?php
// includes/config.php - Fix Session Configuration

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'staff_training_system');

// Site configuration
define('SITE_URL', 'http://localhost/staff-training/');
define('SITE_NAME', 'THE POLYTECHNIC, IBADAN - SKILL DEVELOPMENT CENTRE');
define('SITE_TAGLINE', 'Staff Training & Development Tracking System');
define('SITE_SHORT_NAME', 'PolyIbadan SDC');

// Upload paths
define('UPLOAD_PATH', dirname(__DIR__) . '/uploads/');

// ===== FIX: Proper Session Handling =====
// Only start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session parameters
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// Timezone
date_default_timezone_set('Africa/Lagos');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/authentication.php';
require_once __DIR__ . '/currency.php';

// Email Configuration
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'jkossy1@gmail.com');
define('MAIL_PASSWORD', 'bxrx znmd ugra dkjn');
define('MAIL_FROM', 'jkossy1@gmail.com');
?>