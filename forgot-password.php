<?php
// forgot-password.php - Password Reset Request (Mobile Responsive)
session_start();
require_once 'includes/config.php';

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'manager') {
        header('Location: admin/dashboard.php');
        exit();
    } elseif ($_SESSION['role'] == 'trainer') {
        header('Location: trainer/dashboard.php');
        exit();
    } else {
        header('Location: employee/dashboard.php');
        exit();
    }
}

$error = '';
$success = '';
$email_value = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $email_value = $email;
    
    // Validate email
    if (empty($email)) {
        $error = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check if user exists
        $stmt = $conn->prepare("SELECT id, username, email, role FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if ($user) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Create password_resets table if not exists
            $conn->query("
                CREATE TABLE IF NOT EXISTS password_resets (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    user_id INT NOT NULL,
                    email VARCHAR(100) NOT NULL,
                    token VARCHAR(255) NOT NULL,
                    expires_at DATETIME NOT NULL,
                    used TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_token (token),
                    INDEX idx_email (email)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            
            // Delete any existing tokens for this email
            $del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $del->bind_param("s", $email);
            $del->execute();
            
            // Insert new token
            $ins = $conn->prepare("INSERT INTO password_resets (user_id, email, token, expires_at) VALUES (?, ?, ?, ?)");
            $ins->bind_param("isss", $user['id'], $email, $token, $expires);
            $ins->execute();
            
            // Build reset link
            $reset_link = rtrim(SITE_URL, '/') . '/reset-password.php?token=' . urlencode($token) . '&email=' . urlencode($email);
            
            // Send email
            $email_sent = sendPasswordResetEmail($email, $user['username'], $reset_link);
            
            // Log the request
            logAction($user['id'], 'password_reset_requested', ['email' => $email]);
            
            // Always show success (security best practice)
            $success = "If an account exists with that email, a password reset link has been sent.";
            
            // Show additional info if email failed
            if (!$email_sent) {
                $success .= " <br><small class='text-warning'>(Note: Email delivery may be delayed)</small>";
            }
            
            // For development/testing ONLY - remove in production
            if (defined('SITE_URL') && strpos(SITE_URL, 'localhost') !== false) {
                $success .= "<br><br><small class='text-muted'>Development Mode - Reset Link:</small><br>";
                $success .= "<a href='{$reset_link}' class='btn btn-sm btn-outline-primary mt-2'>Click here to reset</a>";
            }
            
        } else {
            // User doesn't exist - still show success (security best practice)
            $success = "If an account exists with that email, a password reset link has been sent.";
        }
    }
}

/**
 * Send password reset email
 */
function sendPasswordResetEmail($email, $username, $reset_link) {
    $site_url = rtrim(SITE_URL, '/');
    
    $subject = "Password Reset - " . SITE_NAME;
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    </head>
    <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background: #f8f9fc;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            
            <!-- Header -->
            <div style='background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); color: white; padding: 40px 30px; text-align: center; border-radius: 15px 15px 0 0;'>
                <div style='font-size: 3rem; margin-bottom: 15px;'>🔐</div>
                <div style='font-size: 1.6rem; font-weight: 800; color: #667eea; letter-spacing: 2px;'>
                    THE POLYTECHNIC, IBADAN
                </div>
                <div style='font-size: 1rem; color: rgba(255,255,255,0.8); margin-top: 5px;'>
                    SKILL DEVELOPMENT CENTRE
                </div>
                <div style='border: none; height: 2px; background: linear-gradient(135deg, #667eea, #764ba2); width: 80px; margin: 15px auto; border-radius: 10px;'></div>
                <div style='font-size: 1.3rem; font-weight: 700; margin-top: 15px;'>
                    Password Reset Request
                </div>
            </div>
            
            <!-- Content -->
            <div style='background: white; padding: 35px 30px;'>
                <h2 style='color: #2d3748; margin-top: 0;'>Hello " . htmlspecialchars($username) . ",</h2>
                
                <p style='color: #4a5568; line-height: 1.7;'>
                    We received a request to reset the password for your account on 
                    <strong>" . SITE_NAME . "</strong>.
                </p>
                
                <p style='color: #4a5568; line-height: 1.7;'>
                    Click the button below to create a new password:
                </p>
                
                <!-- Reset Button -->
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$reset_link}' 
                       style='display: inline-block; padding: 15px 40px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; text-decoration: none; border-radius: 50px; font-weight: 700; font-size: 1rem;'>
                        🔓 Reset My Password
                    </a>
                </div>
                
                <p style='color: #4a5568; line-height: 1.7; font-size: 0.9rem;'>
                    Or copy and paste this link into your browser:
                </p>
                
                <div style='background: #f8f9fc; border-left: 4px solid #667eea; padding: 15px; border-radius: 8px; margin: 15px 0; word-break: break-all;'>
                    <a href='{$reset_link}' style='color: #667eea; font-size: 0.85rem; text-decoration: none;'>{$reset_link}</a>
                </div>
                
                <!-- Warning -->
                <div style='background: #fffaf0; border-left: 4px solid #f6c23e; padding: 15px 20px; border-radius: 8px; margin: 25px 0;'>
                    <strong style='color: #744210;'>⏰ Important:</strong>
                    <ul style='color: #744210; margin: 8px 0 0; padding-left: 20px; font-size: 0.9rem;'>
                        <li>This link will expire in <strong>1 hour</strong></li>
                        <li>It can only be used <strong>once</strong></li>
                        <li>If you didn't request this, please ignore this email</li>
                    </ul>
                </div>
                
                <p style='color: #4a5568; line-height: 1.7;'>
                    If you didn't request a password reset, no action is needed. Your account is still secure.
                </p>
                
                <p style='color: #4a5568; margin-top: 30px;'>
                    Best regards,<br>
                    <strong>" . SITE_NAME . " Team</strong>
                </p>
            </div>
            
            <!-- Footer -->
            <div style='background: #f8f9fc; padding: 25px; text-align: center; border-radius: 0 0 15px 15px; border-top: 1px solid #e2e8f0;'>
                <p style='color: #6c757d; font-size: 0.85rem; margin: 5px 0;'>
                    <strong style='color: #667eea;'>" . SITE_NAME . "</strong>
                </p>
                <p style='color: #a0aec0; font-size: 0.75rem; margin: 5px 0;'>
                    &copy; " . date('Y') . " THE POLYTECHNIC, IBADAN - SKILL DEVELOPMENT CENTRE
                </p>
                <p style='color: #a0aec0; font-size: 0.75rem; margin: 5px 0;'>
                    This is an automated message. Please do not reply.
                </p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Try PHPMailer first
    if (file_exists('vendor/autoload.php')) {
        try {
            require_once 'vendor/autoload.php';
            
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = defined('MAIL_HOST') ? MAIL_HOST : 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = defined('MAIL_USERNAME') ? MAIL_USERNAME : '';
            $mail->Password   = defined('MAIL_PASSWORD') ? MAIL_PASSWORD : '';
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = defined('MAIL_PORT') ? MAIL_PORT : 587;
            
            $from_email = defined('MAIL_FROM') ? MAIL_FROM : 'noreply@' . $_SERVER['HTTP_HOST'];
            $mail->setFrom($from_email, SITE_NAME);
            $mail->addAddress($email, $username);
            
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $message;
            $mail->AltBody = strip_tags($message);
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Password reset email failed: " . $e->getMessage());
        }
    }
    
    // Fallback to mail()
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . SITE_NAME . " <noreply@" . $_SERVER['HTTP_HOST'] . ">\r\n";
    
    return @mail($email, $subject, $message, $headers);
}

$page_title = 'Forgot Password';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#667eea">
    <title>Forgot Password - <?php echo SITE_NAME; ?></title>
    
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
        
        /* ===== WRAPPER ===== */
        .forgot-wrapper {
            width: 100%;
            max-width: 1000px;
            position: relative;
            z-index: 1;
            margin: 0 auto;
        }
        
        .forgot-box {
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
        
        .form-header .header-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 1.8rem;
            color: white;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        .form-header h3 {
            font-size: 1.5rem;
            font-weight: 800;
            color: #1a1a2e;
            margin-bottom: 8px;
        }
        
        .form-header p {
            color: #6c757d;
            font-size: 0.9rem;
            margin: 0;
            line-height: 1.5;
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
        
        /* ===== SUBMIT BUTTON ===== */
        .btn-submit {
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
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .btn-submit:active {
            transform: translateY(0);
        }
        
        .btn-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        /* ===== ALERTS ===== */
        .alert {
            border: none;
            border-radius: 10px;
            padding: 14px 18px;
            font-size: 0.9rem;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            line-height: 1.5;
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
            font-size: 1.2rem;
            flex-shrink: 0;
            margin-top: 2px;
        }
        
        /* ===== BACK LINK ===== */
        .back-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        
        .back-link a {
            color: #667eea;
            font-weight: 600;
            text-decoration: none;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .back-link a:hover {
            color: #764ba2;
            text-decoration: none;
            transform: translateX(-3px);
        }
        
        /* ===== INFO BOX ===== */
        .info-box {
            background: #f8f9fc;
            border-radius: 10px;
            padding: 15px 18px;
            margin-top: 20px;
            font-size: 0.85rem;
            color: #4a5568;
            line-height: 1.6;
        }
        
        .info-box i {
            color: #667eea;
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
        /* ===== TABLET (< 992px) ===== */
        /* ============================================ */
        @media (max-width: 991px) {
            .forgot-wrapper {
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
        /* ===== MOBILE (< 768px) ===== */
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
            
            .forgot-wrapper {
                max-width: 100%;
                min-height: 100vh;
                display: flex;
                flex-direction: column;
            }
            
            .forgot-box {
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
                text-align: left;
                margin-bottom: 25px;
            }
            
            .form-header .header-icon {
                width: 55px;
                height: 55px;
                font-size: 1.5rem;
                margin: 0 0 15px 0;
            }
            
            .form-header h3 {
                font-size: 1.3rem;
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
            
            .btn-submit {
                padding: 14px;
                font-size: 16px;
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
            
            .alert {
                padding: 12px 14px;
                font-size: 0.8rem;
            }
        }
        
        /* ============================================ */
        /* ===== LANDSCAPE PHONE ===== */
        /* ============================================ */
        @media (max-width: 767px) and (orientation: landscape) {
            .forgot-box {
                min-height: auto;
            }
            
            .brand-panel {
                padding: 20px;
            }
            
            .form-panel {
                padding: 20px;
            }
        }
        
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
            .btn-submit {
                min-height: 44px;
            }
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

<!-- Forgot Password Wrapper -->
<div class="forgot-wrapper">
    <div class="forgot-box">
        
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
                    <i class="fas fa-shield-alt"></i>
                    <span>Secure Account Recovery</span>
                </div>
                <div class="feature">
                    <i class="fas fa-envelope"></i>
                    <span>Email Verification</span>
                </div>
                <div class="feature">
                    <i class="fas fa-clock"></i>
                    <span>1-Hour Reset Window</span>
                </div>
                <div class="feature">
                    <i class="fas fa-lock"></i>
                    <span>Encrypted Tokens</span>
                </div>
            </div>
        </div>
        
        <!-- Right Panel - Forgot Password Form -->
        <div class="form-panel">
            <div class="form-header">
                <div class="header-icon">
                    <i class="fas fa-key"></i>
                </div>
                <h3>Forgot Password?</h3>
                <p>Enter your email address and we'll send you a link to reset your password.</p>
            </div>
            
            <!-- Error Message -->
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <!-- Success Message -->
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo $success; ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (!$success): ?>
                <!-- Form -->
                <form method="POST" action="" id="forgotForm" autocomplete="on">
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
                                   placeholder="Enter your registered email" 
                                   required 
                                   autocomplete="email"
                                   inputmode="email"
                                   value="<?php echo htmlspecialchars($email_value); ?>">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-submit" id="submitBtn">
                        <i class="fas fa-paper-plane"></i>
                        <span>Send Reset Link</span>
                    </button>
                </form>
            <?php else: ?>
                <!-- Success State -->
                <div class="info-box">
                    <i class="fas fa-info-circle"></i>
                    <strong>What happens next?</strong>
                    <ul style="margin: 10px 0 0; padding-left: 20px;">
                        <li>Check your email inbox</li>
                        <li>Click the reset link in the email</li>
                        <li>Create a new password</li>
                        <li>Login with your new password</li>
                    </ul>
                </div>
                
                <div class="text-center mt-3">
                    <a href="login.php" class="btn-submit" style="text-decoration: none;">
                        <i class="fas fa-arrow-left"></i>
                        <span>Back to Login</span>
                    </a>
                </div>
                
                <div class="text-center mt-2">
                    <small class="text-muted">Didn't receive the email?</small><br>
                    <a href="forgot-password.php" class="font-weight-bold" style="color: #667eea; text-decoration: none;">Try again</a>
                </div>
            <?php endif; ?>
            
            <!-- Back to Login (if not success) -->
            <?php if (!$success): ?>
                <div class="back-link">
                    <a href="login.php">
                        <i class="fas fa-arrow-left"></i>
                        <span>Back to Login</span>
                    </a>
                </div>
            <?php endif; ?>
            
            <!-- Help Info -->
            <?php if (!$success): ?>
                <div class="info-box">
                    <i class="fas fa-lightbulb"></i>
                    <strong>Need help?</strong>
                    <p class="mb-0 mt-2">
                        If you don't have access to your registered email or need further assistance, 
                        please contact the administrator at 
                        <a href="mailto:sdc@polyibadan.edu.ng" style="color: #667eea; font-weight: 600;">sdc@polyibadan.edu.ng</a>
                    </p>
                </div>
            <?php endif; ?>
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
    
    var form = document.getElementById('forgotForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            var email = document.getElementById('email').value.trim();
            
            if (!email) {
                e.preventDefault();
                return false;
            }
            
            // Email validation
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address');
                return false;
            }
            
            // Show loading state
            var btn = document.getElementById('submitBtn');
            var originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Sending...</span>';
            btn.disabled = true;
            
            // Re-enable after 10 seconds
            setTimeout(function() {
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            }, 10000);
        });
        
        // Auto-focus email field
        document.getElementById('email').focus();
    }
    
})();
</script>

</body>
</html>