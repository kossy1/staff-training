<?php
// login.php - Mobile Responsive Login Page (Fixed Trainer Login)
session_start();
require_once 'includes/config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    redirectByRole($_SESSION['role']);
}

$error = '';
$success_message = '';
$debug_info = [];

// Handle messages
if (isset($_GET['logout']) && $_GET['logout'] == 'success') {
    $success_message = "You have been logged out successfully.";
}
if (isset($_GET['expired']) && $_GET['expired'] == '1') {
    $error = "Your session has expired. Please login again.";
}
if (isset($_GET['error']) && $_GET['error'] == 'no_trainer_profile') {
    $error = "Your trainer profile is not set up. Contact administrator.";
}
if (isset($_GET['error']) && $_GET['error'] == 'trainer_not_found') {
    $error = "Trainer account not found. Contact administrator.";
}

/**
 * Redirect user based on their role
 */
function redirectByRole($role) {
    switch ($role) {
        case 'admin':
        case 'manager':
            header('Location: admin/dashboard.php');
            exit();
        case 'trainer':
            header('Location: trainer/dashboard.php');
            exit();
        case 'employee':
            header('Location: employee/dashboard.php');
            exit();
        default:
            // Unknown role - destroy session and show login
            session_destroy();
            header('Location: login.php?error=invalid_role');
            exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate inputs
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        // Find user
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        if (!$stmt) {
            $error = 'Database error: ' . $conn->error;
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                // Verify password
                if (password_verify($password, $row['password'])) {
                    // Clear any existing session data
                    $_SESSION = array();
                    
                    // Set session variables
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['role'] = strtolower(trim($row['role'])); // Ensure lowercase
                    $_SESSION['employee_id'] = $row['employee_id'] ?? null;
                    $_SESSION['email'] = $row['email'];
                    $_SESSION['logged_in'] = true;
                    $_SESSION['login_time'] = time();
                    
                    $debug_info[] = "User found: ID={$row['id']}, Role={$row['role']}";
                    
                    // ===== TRAINER SPECIFIC HANDLING =====
                    if ($_SESSION['role'] === 'trainer') {
                        $debug_info[] = "Trainer login detected";
                        
                        // Try to find trainer profile by user_id OR email
                        $t_stmt = $conn->prepare("SELECT id FROM trainers WHERE user_id = ? OR email = ? LIMIT 1");
                        if ($t_stmt) {
                            $t_stmt->bind_param("is", $row['id'], $email);
                            $t_stmt->execute();
                            $trainer = $t_stmt->get_result()->fetch_assoc();
                            
                            if ($trainer) {
                                $_SESSION['trainer_id'] = $trainer['id'];
                                $debug_info[] = "Trainer ID set: {$trainer['id']}";
                            } else {
                                // Trainer user exists but no trainer profile - create a placeholder or redirect with error
                                $debug_info[] = "ERROR: No trainer profile found for user_id={$row['id']}";
                                session_destroy();
                                $error = 'Your trainer profile is missing. Please contact the administrator.';
                                $debug_info[] = "Contact admin to link your account";
                            }
                        }
                    }
                    
                    // If no errors, log and redirect
                    if (empty($error)) {
                        logAction($row['id'], 'login', ['ip' => $_SERVER['REMOTE_ADDR']]);
                        
                        // Redirect based on role
                        redirectByRole($_SESSION['role']);
                    }
                    
                } else {
                    $error = 'Invalid email or password';
                    $debug_info[] = "Password verification failed for: $email";
                }
            } else {
                $error = 'Invalid email or password';
                $debug_info[] = "User not found: $email";
            }
        }
    }
}

$page_title = 'Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#667eea">
    <title>Login - <?php echo SITE_NAME; ?></title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        /* ===== RESET & BASE ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }
        
        html, body {
            height: 100%;
            width: 100%;
            overflow-x: hidden;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
            min-height: 100vh;
            position: relative;
        }
        
        /* ===== BACKGROUND ANIMATION ===== */
        .bg-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            pointer-events: none;
            overflow: hidden;
        }
        
        .bg-shapes .shape {
            position: absolute;
            border-radius: 50%;
            animation: float 20s infinite ease-in-out;
        }
        
        .bg-shapes .shape:nth-child(1) {
            width: 400px;
            height: 400px;
            top: -10%;
            right: -15%;
            background: radial-gradient(circle, rgba(102, 126, 234, 0.15) 0%, transparent 70%);
            animation-delay: 0s;
        }
        
        .bg-shapes .shape:nth-child(2) {
            width: 300px;
            height: 300px;
            bottom: -10%;
            left: -10%;
            background: radial-gradient(circle, rgba(118, 75, 162, 0.15) 0%, transparent 70%);
            animation-delay: 5s;
        }
        
        .bg-shapes .shape:nth-child(3) {
            width: 250px;
            height: 250px;
            top: 50%;
            left: 40%;
            background: radial-gradient(circle, rgba(102, 126, 234, 0.1) 0%, transparent 70%);
            animation-delay: 10s;
        }
        
        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(20px, -20px) scale(1.05); }
        }
        
        /* ===== LOGIN WRAPPER ===== */
        .login-wrapper {
            width: 100%;
            max-width: 1000px;
            position: relative;
            z-index: 1;
            margin: 0 auto;
        }
        
        .login-box {
            display: flex;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.35);
            animation: boxFadeIn 0.6s ease;
        }
        
        @keyframes boxFadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* ===== LEFT PANEL (Branding) ===== */
        .brand-panel {
            width: 42%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 50px 35px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .brand-panel::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
            animation: rotate 30s linear infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .brand-content {
            position: relative;
            z-index: 1;
            text-align: center;
        }
        
        .brand-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            animation: iconFloat 3s ease-in-out infinite;
        }
        
        @keyframes iconFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .brand-panel h1 {
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: 1px;
            margin-bottom: 8px;
            line-height: 1.3;
        }
        
        .brand-panel h2 {
            font-size: 0.95rem;
            font-weight: 600;
            opacity: 0.9;
            margin-bottom: 25px;
            letter-spacing: 0.5px;
        }
        
        .brand-panel .tagline {
            font-size: 1rem;
            opacity: 0.85;
            font-style: italic;
            margin-bottom: 30px;
            line-height: 1.5;
        }
        
        .brand-features {
            text-align: left;
            margin-top: 20px;
        }
        
        .brand-features .feature {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
            font-size: 0.9rem;
            opacity: 0.95;
        }
        
        .brand-features .feature i {
            width: 32px;
            height: 32px;
            min-width: 32px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
        }
        
        /* ===== RIGHT PANEL (Form) ===== */
        .form-panel {
            width: 58%;
            padding: 45px 40px;
            background: white;
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .form-header h3 {
            font-size: 1.6rem;
            font-weight: 800;
            color: #1a1a2e;
            margin-bottom: 6px;
        }
        
        .form-header p {
            color: #6c757d;
            font-size: 0.9rem;
            margin: 0;
        }
        
        /* ===== FORM ELEMENTS ===== */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            font-weight: 600;
            color: #2d3748;
            font-size: 0.85rem;
            margin-bottom: 6px;
            display: block;
        }
        
        .input-group {
            border-radius: 10px;
            overflow: hidden;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
            background: white;
        }
        
        .input-group:focus-within {
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        
        .input-group-prepend .input-group-text {
            background: transparent;
            border: none;
            color: #a0aec0;
            padding: 0 15px;
            transition: all 0.3s ease;
        }
        
        .input-group:focus-within .input-group-prepend .input-group-text {
            color: #667eea;
        }
        
        .form-control {
            border: none;
            padding: 14px 12px;
            font-size: 16px;
            height: auto;
            background: transparent;
            color: #2d3748;
            font-weight: 500;
        }
        
        .form-control:focus {
            box-shadow: none;
            border: none;
            background: transparent;
        }
        
        .form-control::placeholder {
            color: #a0aec0;
            font-weight: 400;
        }
        
        .input-group-append .btn {
            border: none;
            background: transparent;
            color: #a0aec0;
            padding: 0 15px;
        }
        
        .input-group-append .btn:hover {
            color: #667eea;
            background: transparent;
        }
        
        /* ===== LOGIN BUTTON ===== */
        .btn-login {
            width: 100%;
            padding: 15px;
            font-size: 16px;
            font-weight: 700;
            color: white;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        /* ===== DEMO ACCOUNTS ===== */
        .divider {
            display: flex;
            align-items: center;
            margin: 25px 0 18px;
            color: #a0aec0;
            font-size: 0.75rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e2e8f0;
        }
        
        .divider span {
            padding: 0 12px;
        }
        
        .demo-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .demo-item {
            display: flex;
            align-items: center;
            padding: 10px 12px;
            background: #f8f9fc;
            border: 2px solid transparent;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.25s ease;
            text-decoration: none;
            color: inherit;
        }
        
        .demo-item:hover,
        .demo-item:active {
            background: white;
            border-color: #667eea;
            transform: translateX(4px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.15);
            text-decoration: none;
            color: inherit;
        }
        
        .demo-role {
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-right: 10px;
            min-width: 72px;
            text-align: center;
            flex-shrink: 0;
        }
        
        .demo-role.admin { background: #667eea; color: white; }
        .demo-role.employee { background: #48bb78; color: white; }
        .demo-role.trainer { background: #f6c23e; color: #2d3748; }
        
        .demo-info {
            flex: 1;
            font-size: 0.8rem;
            min-width: 0;
            overflow: hidden;
        }
        
        .demo-info strong {
            display: block;
            color: #2d3748;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 0.78rem;
        }
        
        .demo-info small {
            color: #718096;
            font-size: 0.7rem;
        }
        
        .demo-item .demo-arrow {
            color: #a0aec0;
            font-size: 0.8rem;
            flex-shrink: 0;
            transition: all 0.25s ease;
        }
        
        .demo-item:hover .demo-arrow {
            color: #667eea;
            transform: translateX(3px);
        }
        
        /* ===== ALERTS ===== */
        .alert {
            border: none;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 0.85rem;
            margin-bottom: 18px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        
        .alert-danger {
            background: #fff5f5;
            color: #c53030;
            border-left: 4px solid #e74a3b;
        }
        
        .alert-success {
            background: #f0fff4;
            color: #276749;
            border-left: 4px solid #48bb78;
        }
        
        .alert i {
            font-size: 1.1rem;
            flex-shrink: 0;
            margin-top: 2px;
        }
        
        /* Debug Info */
        .debug-box {
            background: #1a1a2e;
            color: #48bb78;
            padding: 12px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 0.75rem;
            margin-bottom: 15px;
            max-height: 150px;
            overflow-y: auto;
        }
        
        /* ===== FORGOT PASSWORD ===== */
        .forgot-link {
            color: #667eea;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
        }
        
        .forgot-link:hover {
            color: #764ba2;
            text-decoration: none;
        }
        
        /* ===== REGISTER LINK ===== */
        .register-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .register-link a {
            color: #667eea;
            font-weight: 600;
            text-decoration: none;
        }
        
        /* ===== FOOTER ===== */
        .page-footer {
            text-align: center;
            margin-top: 20px;
            color: rgba(255,255,255,0.4);
            font-size: 0.75rem;
            line-height: 1.5;
        }
        
        /* ============================================ */
        /* ===== TABLET RESPONSIVE (< 992px) ===== */
        /* ============================================ */
        @media (max-width: 991px) {
            .login-wrapper {
                max-width: 700px;
            }
            
            .brand-panel {
                width: 38%;
                padding: 40px 25px;
            }
            
            .form-panel {
                width: 62%;
                padding: 40px 30px;
            }
            
            .brand-panel h1 {
                font-size: 1.3rem;
            }
            
            .brand-features {
                display: none;
            }
        }
        
        /* ============================================ */
        /* ===== MOBILE RESPONSIVE (< 768px) ===== */
        /* ============================================ */
        @media (max-width: 767px) {
            body {
                padding: 0;
                align-items: flex-start;
                background: white;
            }
            
            .bg-shapes {
                display: none;
            }
            
            .login-wrapper {
                max-width: 100%;
                min-height: 100vh;
                display: flex;
                flex-direction: column;
            }
            
            .login-box {
                flex-direction: column;
                border-radius: 0;
                box-shadow: none;
                min-height: 100vh;
                animation: none;
            }
            
            .brand-panel {
                width: 100%;
                padding: 35px 25px 30px;
                border-radius: 0 0 30px 30px;
                min-height: auto;
                flex-shrink: 0;
            }
            
            .brand-panel::before {
                display: none;
            }
            
            .brand-content {
                display: flex;
                align-items: center;
                text-align: left;
                gap: 15px;
            }
            
            .brand-icon {
                font-size: 2.5rem;
                margin-bottom: 0;
                animation: none;
                flex-shrink: 0;
            }
            
            .brand-text {
                flex: 1;
                min-width: 0;
            }
            
            .brand-panel h1 {
                font-size: 1.05rem;
                margin-bottom: 3px;
                letter-spacing: 0.5px;
            }
            
            .brand-panel h2 {
                font-size: 0.75rem;
                margin-bottom: 0;
                opacity: 0.85;
            }
            
            .brand-panel .tagline {
                display: none;
            }
            
            .brand-features {
                display: none;
            }
            
            .form-panel {
                width: 100%;
                padding: 30px 22px;
                flex: 1;
                display: flex;
                flex-direction: column;
                justify-content: flex-start;
            }
            
            .form-header {
                margin-bottom: 25px;
                text-align: left;
            }
            
            .form-header h3 {
                font-size: 1.35rem;
            }
            
            .form-header p {
                font-size: 0.85rem;
            }
            
            .form-group {
                margin-bottom: 18px;
            }
            
            .form-control {
                font-size: 16px;
                padding: 12px 10px;
            }
            
            .btn-login {
                padding: 14px;
                font-size: 16px;
            }
            
            .demo-list {
                gap: 6px;
            }
            
            .demo-item {
                padding: 9px 10px;
            }
            
            .demo-role {
                min-width: 62px;
                font-size: 0.6rem;
                padding: 3px 8px;
            }
            
            .demo-info strong {
                font-size: 0.72rem;
            }
            
            .demo-info small {
                font-size: 0.65rem;
            }
            
            .page-footer {
                color: rgba(255,255,255,0.8);
                background: #1a1a2e;
                margin-top: 0;
                padding: 15px;
                font-size: 0.7rem;
            }
        }
        
        /* ============================================ */
        /* ===== SMALL PHONE (< 480px) ===== */
        /* ============================================ */
        @media (max-width: 479px) {
            .brand-panel {
                padding: 30px 20px 25px;
            }
            
            .brand-icon {
                font-size: 2.2rem;
            }
            
            .brand-panel h1 {
                font-size: 0.95rem;
                letter-spacing: 0.3px;
            }
            
            .brand-panel h2 {
                font-size: 0.7rem;
            }
            
            .form-panel {
                padding: 25px 18px;
            }
            
            .form-header h3 {
                font-size: 1.2rem;
            }
            
            .demo-role {
                min-width: 55px;
                font-size: 0.55rem;
            }
            
            .demo-info strong {
                font-size: 0.68rem;
            }
            
            .demo-info small {
                display: none;
            }
            
            .divider {
                margin: 20px 0 14px;
                font-size: 0.7rem;
            }
        }
        
        /* ============================================ */
        /* ===== LANDSCAPE PHONE ===== */
        /* ============================================ */
        @media (max-width: 767px) and (orientation: landscape) {
            .login-box {
                min-height: auto;
            }
            
            .brand-panel {
                padding: 20px;
            }
            
            .form-panel {
                padding: 20px;
            }
        }
        
        /* ============================================ */
        /* ===== ACCESSIBILITY & UTILITIES ===== */
        /* ============================================ */
        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
            }
        }
        
        @media (max-width: 767px) {
            .form-control,
            .btn-login,
            .demo-item,
            .input-group-append .btn {
                min-height: 44px;
            }
        }
        
        .btn-login,
        .demo-item {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
    </style>
</head>
<body>

<!-- Background Animation -->
<div class="bg-shapes">
    <div class="shape"></div>
    <div class="shape"></div>
    <div class="shape"></div>
</div>

<!-- Login Wrapper -->
<div class="login-wrapper">
    <div class="login-box">
        
        <!-- Left Panel - Branding -->
        <div class="brand-panel">
            <div class="brand-content">
                <div class="brand-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="brand-text">
                    <h1>THE POLYTECHNIC, IBADAN</h1>
                    <h2>SKILL DEVELOPMENT CENTRE</h2>
                    <div class="tagline">Staff Training &amp; Development Tracking System</div>
                </div>
            </div>
            
            <div class="brand-features">
                <div class="feature">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Manage Training Programs</span>
                </div>
                <div class="feature">
                    <i class="fas fa-certificate"></i>
                    <span>Track Certifications</span>
                </div>
                <div class="feature">
                    <i class="fas fa-chart-line"></i>
                    <span>Monitor Progress</span>
                </div>
                <div class="feature">
                    <i class="fas fa-users"></i>
                    <span>Develop Your Team</span>
                </div>
            </div>
        </div>
        
        <!-- Right Panel - Login Form -->
        <div class="form-panel">
            <div class="form-header">
                <h3>Welcome Back</h3>
                <p>Sign in to continue to your dashboard</p>
            </div>
            
            <!-- Alerts -->
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($success_message); ?></span>
                </div>
            <?php endif; ?>
            
            <!-- Debug Info (only show when there's an error) -->
            <?php if ($error && !empty($debug_info)): ?>
                <div class="debug-box">
                    <?php foreach ($debug_info as $debug): ?>
                        → <?php echo htmlspecialchars($debug); ?><br>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form method="POST" action="" id="loginForm" autocomplete="on">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        </div>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               class="form-control" 
                               placeholder="Enter your email" 
                               required 
                               autocomplete="email"
                               inputmode="email"
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        </div>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-control" 
                               placeholder="Enter your password" 
                               required
                               autocomplete="current-password">
                        <div class="input-group-append">
                            <button type="button" class="btn" id="togglePassword" aria-label="Show password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="form-group d-flex justify-content-between align-items-center mb-4">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="remember" name="remember">
                        <label class="custom-control-label" for="remember">Remember me</label>
                    </div>
                    <a href="forgot-password.php" class="forgot-link">Forgot Password?</a>
                </div>
                
                <button type="submit" class="btn-login" id="loginBtn">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    <span>Sign In</span>
                </button>
            </form>
            
            <!-- Demo Accounts -->
            <div class="divider">
                <span>Quick Login</span>
            </div>
            
            <div class="demo-list">
                <div class="demo-item" onclick="fillLogin('admin@example.com', 'admin123')" role="button" tabindex="0">
                    <span class="demo-role admin">Admin</span>
                    <div class="demo-info">
                        <strong>admin@example.com</strong>
                        <small>Password: admin123</small>
                    </div>
                    <i class="fas fa-chevron-right demo-arrow"></i>
                </div>
                
                <div class="demo-item" onclick="fillLogin('employee@example.com', 'employee123')" role="button" tabindex="0">
                    <span class="demo-role employee">Employee</span>
                    <div class="demo-info">
                        <strong>employee@example.com</strong>
                        <small>Password: employee123</small>
                    </div>
                    <i class="fas fa-chevron-right demo-arrow"></i>
                </div>
                
                <div class="demo-item" onclick="fillLogin('trainer@example.com', 'trainer123')" role="button" tabindex="0">
                    <span class="demo-role trainer">Trainer</span>
                    <div class="demo-info">
                        <strong>trainer@example.com</strong>
                        <small>Password: trainer123</small>
                    </div>
                    <i class="fas fa-chevron-right demo-arrow"></i>
                </div>
            </div>
            
            <!-- Register Link -->
            <div class="register-link">
                Don't have an account? <a href="register.php">Register here</a>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <div class="page-footer">
        <p class="mb-0">&copy; <?php echo date('Y'); ?> THE POLYTECHNIC, IBADAN - SKILL DEVELOPMENT CENTRE</p>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
(function() {
    'use strict';
    
    // ===== PASSWORD TOGGLE =====
    var toggleBtn = document.getElementById('togglePassword');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            var password = document.getElementById('password');
            var icon = this.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
                this.setAttribute('aria-label', 'Hide password');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
                this.setAttribute('aria-label', 'Show password');
            }
        });
    }
    
    // ===== FORM SUBMISSION =====
    var loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            var email = document.getElementById('email').value.trim();
            var password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                return false;
            }
            
            var btn = document.getElementById('loginBtn');
            var originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i><span>Signing in...</span>';
            btn.disabled = true;
            
            setTimeout(function() {
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            }, 8000);
        });
    }
    
    // ===== ENTER KEY NAVIGATION =====
    var emailField = document.getElementById('email');
    if (emailField) {
        emailField.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('password').focus();
            }
        });
    }
    
})();

// ===== FILL LOGIN FUNCTION (Global) =====
function fillLogin(email, password) {
    var emailField = document.getElementById('email');
    var passwordField = document.getElementById('password');
    
    emailField.value = email;
    passwordField.value = password;
    
    var formPanel = document.querySelector('.form-panel');
    formPanel.style.transform = 'scale(0.99)';
    setTimeout(function() {
        formPanel.style.transform = 'scale(1)';
    }, 150);
    
    passwordField.focus();
    if (window.innerWidth < 768) {
        document.querySelector('.form-header').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    
    setTimeout(function() {
        document.getElementById('loginForm').submit();
    }, 400);
}
</script>

</body>
</html>