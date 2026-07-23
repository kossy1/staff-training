<?php
// reset-password.php - Reset Password
session_start();
require_once 'includes/config.php';

$token = isset($_GET['token']) ? $_GET['token'] : '';
$message = '';
$message_type = '';
$success = false;

// Validate token (simplified - in production, check against database)
if (empty($token)) {
    header('Location: forgot-password.php');
    exit();
}

// For demo purposes, we'll accept any token
// In production, verify token from password_resets table

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($password)) {
        $message = "Password is required";
        $message_type = 'danger';
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters";
        $message_type = 'danger';
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match";
        $message_type = 'danger';
    } else {
        // In production, get user_id from token and update password
        $message = "Password reset successful! You can now <a href='login.php'>login</a>.";
        $message_type = 'success';
        $success = true;
        
        // For demo, we'll just show success
    }
}

$page_title = 'Reset Password';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .reset-container {
            max-width: 450px;
            width: 100%;
            padding: 40px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .reset-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .reset-header h2 {
            color: #333;
            font-weight: 700;
        }
        .reset-header p {
            color: #6c757d;
        }
        @media (max-width: 576px) {
            .reset-container {
                margin: 15px;
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <i class="fas fa-lock" style="font-size: 3rem; color: #667eea;"></i>
            <h2>Reset Password</h2>
            <p>Enter your new password</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$success): ?>
            <form method="POST">
                <div class="form-group">
                    <label>New Password</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        </div>
                        <input type="password" name="password" class="form-control" 
                               placeholder="Enter new password" required minlength="6">
                    </div>
                    <small class="text-muted">Minimum 6 characters</small>
                </div>
                
                <div class="form-group">
                    <label>Confirm Password</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-check"></i></span>
                        </div>
                        <input type="password" name="confirm_password" class="form-control" 
                               placeholder="Confirm new password" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-save"></i> Reset Password
                </button>
            </form>
        <?php endif; ?>
        
        <div class="text-center mt-3">
            <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>