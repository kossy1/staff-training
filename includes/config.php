<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'staff_training_system');

// Site configuration
define('SITE_URL', 'http://localhost/staff-training/');
define('SITE_NAME', 'Staff Training & Development Tracking System');
define('UPLOAD_PATH', dirname(__DIR__) . '/uploads/');

// Only start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Set session ini settings before starting session
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Timezone
date_default_timezone_set('UTC');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/authentication.php';



require_once __DIR__ . '/currency.php';

// Currency constants
define('CURRENCY_SYMBOL', '₦');
define('CURRENCY_CODE', 'NGN');
define('CURRENCY_NAME', 'Naira');
define('CURRENCY_LOCALE', 'en_NG');
define('CURRENCY_DECIMAL_PLACES', 2);
define('CURRENCY_DECIMAL_SEPARATOR', '.');
define('CURRENCY_THOUSAND_SEPARATOR', ',');

?>