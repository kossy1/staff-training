<?php
// admin/add-trainer.php - Add New Trainer with Auto-Email & Password Fix
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

$errors = [];
$success = false;
$form_data = [];
$email_sent = false;
$email_error = '';

// ============================================
// EMAIL FUNCTION
// ============================================
function sendTrainerWelcomeEmail($email, $first_name, $last_name, $username, $password, &$email_error = '') {
    $site_url = rtrim(SITE_URL, '/') . '/';
    $login_url = $site_url . 'login.php';
    $full_name = $first_name . ' ' . $last_name;
    
    $subject = "Welcome to " . SITE_NAME . " - Your Trainer Account";
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    </head>
    <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background: #f8f9fc;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            
            <div style='background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); color: white; padding: 40px 30px; text-align: center; border-radius: 15px 15px 0 0;'>
                <div style='font-size: 1.6rem; font-weight: 800; color: #667eea; letter-spacing: 2px;'>
                    THE POLYTECHNIC, IBADAN
                </div>
                <div style='font-size: 1rem; color: rgba(255,255,255,0.8); margin-top: 5px;'>
                    SKILL DEVELOPMENT CENTRE
                </div>
                <div style='border: none; height: 2px; background: linear-gradient(135deg, #667eea, #764ba2); width: 80px; margin: 15px auto; border-radius: 10px;'></div>
                <div style='font-size: 1.3rem; font-weight: 700; margin-top: 15px;'>
                    🎓 Trainer Account Created
                </div>
            </div>
            
            <div style='background: white; padding: 35px 30px;'>
                <h2 style='color: #2d3748; margin-top: 0;'>Welcome aboard, {$first_name}! 🎉</h2>
                
                <p style='color: #4a5568; line-height: 1.7;'>
                    Your trainer account has been successfully created by the administrator at 
                    <strong>" . SITE_NAME . "</strong>. You can now login to access your trainer dashboard 
                    where you can manage your trainings, students, and sessions.
                </p>
                
                <div style='background: #f8f9fc; border-left: 4px solid #667eea; padding: 25px; border-radius: 10px; margin: 25px 0;'>
                    <h3 style='color: #667eea; margin-top: 0; font-size: 1.1rem;'>🔐 Your Login Credentials</h3>
                    
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 10px 0; border-bottom: 1px solid #e2e8f0;'>
                                <strong style='color: #718096; font-size: 0.85rem;'>FULL NAME</strong><br>
                                <span style='color: #2d3748; font-size: 1rem;'>{$full_name}</span>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 10px 0; border-bottom: 1px solid #e2e8f0;'>
                                <strong style='color: #718096; font-size: 0.85rem;'>LOGIN EMAIL</strong><br>
                                <span style='color: #2d3748; font-size: 1rem;'>{$email}</span>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 10px 0; border-bottom: 1px solid #e2e8f0;'>
                                <strong style='color: #718096; font-size: 0.85rem;'>USERNAME</strong><br>
                                <span style='color: #2d3748; font-size: 1rem;'>{$username}</span>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 10px 0;'>
                                <strong style='color: #718096; font-size: 0.85rem;'>PASSWORD</strong><br>
                                <span style='color: #e74a3b; font-size: 1.1rem; font-family: monospace; font-weight: 700;'>{$password}</span>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$login_url}' 
                       style='display: inline-block; padding: 15px 40px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; text-decoration: none; border-radius: 50px; font-weight: 700; font-size: 1rem;'>
                        🔓 Login to Your Dashboard
                    </a>
                </div>
                
                <div style='background: #fffaf0; border-left: 4px solid #f6c23e; padding: 15px 20px; border-radius: 8px; margin: 25px 0;'>
                    <strong style='color: #744210;'>⚠️ Security Notice:</strong>
                    <p style='color: #744210; margin: 8px 0 0; font-size: 0.9rem;'>
                        For your account security, please change your password after your first login.
                    </p>
                </div>
                
                <h3 style='color: #2d3748; font-size: 1.05rem; margin-top: 30px;'>What you can do as a Trainer:</h3>
                <ul style='color: #4a5568; line-height: 1.9; padding-left: 20px;'>
                    <li>📚 View and manage your assigned trainings</li>
                    <li>👥 See all students enrolled in your trainings</li>
                    <li>📅 Schedule and manage training sessions</li>
                    <li>💬 Respond to student requests</li>
                    <li>📊 Track your training performance</li>
                    <li>🎓 Issue completion feedback</li>
                </ul>
                
                <p style='color: #4a5568; line-height: 1.7; margin-top: 25px;'>
                    If you have any questions or need assistance, please contact the administrator 
                    at <a href='mailto:sdc@polyibadan.edu.ng' style='color: #667eea;'>sdc@polyibadan.edu.ng</a>.
                </p>
                
                <p style='color: #4a5568; line-height: 1.7;'>
                    We're excited to have you on board!
                </p>
                
                <p style='color: #4a5568; margin-top: 25px;'>
                    Best regards,<br>
                    <strong>" . SITE_NAME . " Team</strong>
                </p>
            </div>
            
            <div style='background: #f8f9fc; padding: 25px; text-align: center; border-radius: 0 0 15px 15px; border-top: 1px solid #e2e8f0;'>
                <p style='color: #6c757d; font-size: 0.85rem; margin: 5px 0;'>
                    <strong style='color: #667eea;'>" . SITE_NAME . "</strong>
                </p>
                <p style='color: #a0aec0; font-size: 0.75rem; margin: 5px 0;'>
                    &copy; " . date('Y') . " THE POLYTECHNIC, IBADAN - SKILL DEVELOPMENT CENTRE
                </p>
                <p style='color: #a0aec0; font-size: 0.75rem; margin: 5px 0;'>
                    This is an automated message. Please do not reply to this email.
                </p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Try PHPMailer first
    if (file_exists('../vendor/autoload.php')) {
        try {
            require_once '../vendor/autoload.php';
            
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
            $mail->addAddress($email, $full_name);
            
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $message;
            $mail->AltBody = strip_tags($message);
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            $email_error = $e->getMessage();
        }
    }
    
    // Fallback to mail()
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . SITE_NAME . " <noreply@" . $_SERVER['HTTP_HOST'] . ">\r\n";
    $headers .= "Reply-To: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n";
    
    $result = @mail($email, $subject, $message, $headers);
    if (!$result) {
        $email_error = "mail() function failed";
    }
    return $result;
}

// ============================================
// HANDLE FORM SUBMISSION
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $form_data = [
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'specialization' => trim($_POST['specialization'] ?? ''),
        'qualification' => trim($_POST['qualification'] ?? ''),
        'experience_years' => (int)($_POST['experience_years'] ?? 0),
        'bio' => trim($_POST['bio'] ?? ''),
        'linkedin_url' => trim($_POST['linkedin_url'] ?? ''),
        'twitter_url' => trim($_POST['twitter_url'] ?? ''),
        'website_url' => trim($_POST['website_url'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'city' => trim($_POST['city'] ?? ''),
        'state' => trim($_POST['state'] ?? ''),
        'country' => trim($_POST['country'] ?? 'Nigeria'),
        'status' => trim($_POST['status'] ?? 'active'),
        'auto_generate' => isset($_POST['auto_generate']) && $_POST['auto_generate'] === 'on',
        'send_email' => isset($_POST['send_email']) && $_POST['send_email'] === 'on'
    ];
    
    // ===== STEP 1: DETERMINE THE FINAL PASSWORD (Single Source of Truth) =====
    $final_password = '';
    
    if ($form_data['auto_generate']) {
        // Auto-generate a strong password - IGNORE any submitted value
        $final_password = 'Trainer@' . rand(10000, 99999) . '!';
    } else {
        // Use the submitted password
        $submitted_password = $_POST['password'] ?? '';
        $submitted_confirm = $_POST['confirm_password'] ?? '';
        
        if (empty($submitted_password)) {
            $errors[] = "Password is required";
        } elseif (strlen($submitted_password) < 6) {
            $errors[] = "Password must be at least 6 characters";
        } elseif ($submitted_password !== $submitted_confirm) {
            $errors[] = "Passwords do not match";
        } else {
            $final_password = $submitted_password;
        }
    }
    
    // ===== STEP 2: OTHER VALIDATIONS =====
    if (empty($form_data['first_name'])) $errors[] = "First name is required";
    if (empty($form_data['last_name'])) $errors[] = "Last name is required";
    if (empty($form_data['email'])) $errors[] = "Email is required";
    if (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (empty($form_data['phone'])) $errors[] = "Phone number is required";
    if (empty($form_data['specialization'])) $errors[] = "Specialization is required";
    
    // Check email uniqueness
    if (empty($errors)) {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $form_data['email']);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $errors[] = "Email already exists in the system!";
        }
    }
    
    if (empty($errors)) {
        $check = $conn->prepare("SELECT id FROM trainers WHERE email = ?");
        $check->bind_param("s", $form_data['email']);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $errors[] = "A trainer with this email already exists!";
        }
    }
    
    // Handle profile picture
    $profile_picture = '';
    if (empty($errors) && isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $upload_dir = '../uploads/trainers/';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $new_filename = 'trainer_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_dir . $new_filename)) {
                $profile_picture = $new_filename;
            }
        } else {
            $errors[] = "Invalid image type. Allowed: JPG, PNG, GIF, WEBP";
        }
    }
    
    // ===== STEP 3: CREATE TRAINER =====
    if (empty($errors)) {
        $conn->begin_transaction();
        
        try {
            // Generate unique username
            $username_base = strtolower(preg_replace('/[^a-z0-9]/', '', explode('@', $form_data['email'])[0]));
            if (empty($username_base)) $username_base = 'trainer';
            $username = $username_base;
            $counter = 1;
            
            while (true) {
                $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
                $check->bind_param("s", $username);
                $check->execute();
                if ($check->get_result()->num_rows == 0) break;
                $username = $username_base . $counter++;
            }
            
            // ⭐ Hash THE FINAL PASSWORD - the exact one that will be emailed
            $hashed_password = password_hash($final_password, PASSWORD_DEFAULT);
            
            // 1. Create user account
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'trainer')");
            $stmt->bind_param("sss", $username, $form_data['email'], $hashed_password);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to create user account: " . $conn->error);
            }
            
            $user_id = $conn->insert_id;
            
            // ⭐ VERIFY the password was stored correctly
            $verify_stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $verify_stmt->bind_param("i", $user_id);
            $verify_stmt->execute();
            $stored_hash = $verify_stmt->get_result()->fetch_assoc()['password'];
            
            if (!password_verify($final_password, $stored_hash)) {
                throw new Exception("Password storage verification failed!");
            }
            
            // 2. Create trainer profile
            $stmt = $conn->prepare("
                INSERT INTO trainers (
                    user_id, first_name, last_name, email, phone, specialization, qualification,
                    experience_years, bio, profile_picture, linkedin_url, twitter_url, 
                    website_url, address, city, state, country, status, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->bind_param(
                "issssssissssssssssi",
                $user_id,
                $form_data['first_name'],
                $form_data['last_name'],
                $form_data['email'],
                $form_data['phone'],
                $form_data['specialization'],
                $form_data['qualification'],
                $form_data['experience_years'],
                $form_data['bio'],
                $profile_picture,
                $form_data['linkedin_url'],
                $form_data['twitter_url'],
                $form_data['website_url'],
                $form_data['address'],
                $form_data['city'],
                $form_data['state'],
                $form_data['country'],
                $form_data['status'],
                $_SESSION['user_id']
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to create trainer profile: " . $conn->error);
            }
            
            $trainer_id = $conn->insert_id;
            
            $conn->commit();
            
            // ===== STEP 4: SEND EMAIL (with the SAME verified password) =====
            $email_sent = false;
            $email_error = '';
            
            if ($form_data['send_email']) {
                $email_sent = sendTrainerWelcomeEmail(
                    $form_data['email'],
                    $form_data['first_name'],
                    $form_data['last_name'],
                    $username,
                    $final_password, // ⭐ SAME password that was hashed
                    $email_error
                );
            }
            
            logAction($_SESSION['user_id'], 'trainer_created', [
                'trainer_id' => $trainer_id,
                'user_id' => $user_id,
                'username' => $username,
                'email_sent' => $email_sent,
                'auto_generated' => $form_data['auto_generate']
            ]);
            
            // ===== STEP 5: STORE FOR SUCCESS MESSAGE =====
            $_SESSION['new_trainer_credentials'] = [
                'name' => $form_data['first_name'] . ' ' . $form_data['last_name'],
                'email' => $form_data['email'],
                'username' => $username,
                'password' => $final_password, // ⭐ SAME password
                'email_sent' => $email_sent,
                'email_error' => $email_error,
                'trainer_id' => $trainer_id,
                'user_id' => $user_id,
                'auto_generated' => $form_data['auto_generate'],
                'verified' => true
            ];
            
            header('Location: add-trainer.php?success=1');
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Failed to create trainer: " . $e->getMessage();
            error_log("Trainer creation failed: " . $e->getMessage());
        }
    }
}

// Check for success
$show_credentials = false;
$credentials = null;
if (isset($_GET['success']) && isset($_SESSION['new_trainer_credentials'])) {
    $show_credentials = true;
    $credentials = $_SESSION['new_trainer_credentials'];
    unset($_SESSION['new_trainer_credentials']);
}

$page_title = 'Add New Trainer';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<style>
.password-strength {
    height: 5px;
    border-radius: 3px;
    margin-top: 5px;
    transition: all 0.3s ease;
}
.strength-weak { background: #e74a3b; width: 25%; }
.strength-fair { background: #f6c23e; width: 50%; }
.strength-good { background: #4e73df; width: 75%; }
.strength-strong { background: #48bb78; width: 100%; }

/* ===== SUCCESS MODAL ===== */
.success-modal-content {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    color: white;
    border-radius: 20px;
    overflow: hidden;
    border: none;
    margin: 0;
    max-height: 95vh;
    display: flex;
    flex-direction: column;
}

.success-header {
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
    padding: 30px 20px;
    text-align: center;
    position: relative;
    flex-shrink: 0;
}

.success-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 250px;
    height: 250px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
    pointer-events: none;
}

.success-header .success-icon {
    width: 70px;
    height: 70px;
    background: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    font-size: 2.2rem;
    color: #48bb78;
    animation: popIn 0.5s ease;
    position: relative;
    z-index: 1;
}

@keyframes popIn {
    0% { transform: scale(0); }
    70% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.success-header h3 {
    color: white;
    font-weight: 800;
    font-size: 1.25rem;
    margin-bottom: 5px;
    position: relative;
    z-index: 1;
    line-height: 1.3;
}

.success-header p {
    color: rgba(255,255,255,0.9);
    margin: 0;
    font-size: 0.85rem;
    position: relative;
    z-index: 1;
}

.credentials-body {
    padding: 20px;
    overflow-y: auto;
    flex: 1;
    -webkit-overflow-scrolling: touch;
}

.email-status {
    padding: 12px 15px;
    border-radius: 10px;
    margin-bottom: 15px;
    display: flex;
    align-items: flex-start;
    gap: 10px;
    font-size: 0.85rem;
    line-height: 1.4;
}

.email-status.sent {
    background: rgba(72, 187, 120, 0.15);
    border-left: 4px solid #48bb78;
    color: #68d391;
}

.email-status.failed {
    background: rgba(246, 194, 62, 0.15);
    border-left: 4px solid #f6c23e;
    color: #f6c23e;
}

.email-status i {
    font-size: 1.3rem;
    flex-shrink: 0;
    margin-top: 2px;
}

.email-status .status-text {
    min-width: 0;
    word-wrap: break-word;
}

.email-status strong {
    display: block;
    margin-bottom: 3px;
}

.warning-box {
    background: rgba(246, 194, 62, 0.1);
    border-left: 4px solid #f6c23e;
    padding: 12px 15px;
    border-radius: 10px;
    margin-bottom: 15px;
    color: #f6c23e;
    font-size: 0.8rem;
    display: flex;
    align-items: flex-start;
    gap: 10px;
    line-height: 1.4;
}

.warning-box i {
    font-size: 1.1rem;
    flex-shrink: 0;
    margin-top: 2px;
}

.credentials-info {
    background: rgba(255,255,255,0.03);
    border-radius: 12px;
    margin-bottom: 15px;
    overflow: hidden;
}

.credential-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    gap: 10px;
}

.credential-row:last-child {
    border-bottom: none;
}

.credential-row > div:first-child {
    min-width: 0;
    flex: 1;
}

.cred-label {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: rgba(255,255,255,0.5);
    margin-bottom: 3px;
    display: block;
}

.cred-value {
    font-family: 'Courier New', monospace;
    font-size: 0.95rem;
    font-weight: 700;
    color: white;
    word-break: break-all;
    line-height: 1.3;
}

.credential-row.highlight {
    background: rgba(102, 126, 234, 0.12);
}

.credential-row.highlight .cred-value {
    color: #f6c23e;
    font-size: 1.05rem;
}

.copy-btn {
    background: rgba(102, 126, 234, 0.3);
    border: none;
    color: white;
    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.75rem;
    white-space: nowrap;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    gap: 5px;
}

.copy-btn:hover,
.copy-btn:active {
    background: #667eea;
}

.copy-btn.copied {
    background: #48bb78;
}

.action-buttons {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-bottom: 10px;
}

.action-buttons .btn {
    padding: 10px 12px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.82rem;
    transition: all 0.3s ease;
    white-space: nowrap;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}

.action-buttons .btn:hover {
    transform: translateY(-2px);
}

.add-another-btn {
    width: 100%;
    padding: 10px;
    border-radius: 8px;
    font-size: 0.82rem;
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    color: white;
    transition: all 0.3s ease;
    text-align: center;
    display: block;
}

.add-another-btn:hover {
    background: rgba(255,255,255,0.2);
    color: white;
    text-decoration: none;
}

.modal-dialog {
    margin: 10px auto;
    max-width: 550px;
    width: calc(100% - 20px);
}

@media (min-width: 576px) {
    .modal-dialog {
        margin: 30px auto;
        width: auto;
    }
}

@media (max-width: 575px) {
    .success-header {
        padding: 25px 15px;
    }
    
    .success-header .success-icon {
        width: 60px;
        height: 60px;
        font-size: 1.8rem;
        margin-bottom: 12px;
    }
    
    .success-header h3 {
        font-size: 1.1rem;
    }
    
    .success-header p {
        font-size: 0.78rem;
    }
    
    .credentials-body {
        padding: 15px;
    }
    
    .credential-row {
        flex-direction: column;
        align-items: stretch;
        padding: 12px;
    }
    
    .credential-row > div:first-child {
        width: 100%;
        margin-bottom: 8px;
    }
    
    .copy-btn {
        width: 100%;
        justify-content: center;
        padding: 8px;
    }
    
    .cred-value {
        font-size: 0.88rem;
    }
    
    .credential-row.highlight .cred-value {
        font-size: 0.95rem;
    }
    
    .action-buttons {
        grid-template-columns: 1fr;
    }
    
    .email-status,
    .warning-box {
        padding: 10px 12px;
        font-size: 0.78rem;
    }
    
    .email-status i,
    .warning-box i {
        font-size: 1rem;
    }
}

@media (max-width: 379px) {
    .success-header h3 {
        font-size: 1rem;
    }
    
    .cred-value {
        font-size: 0.82rem;
    }
    
    .credentials-body {
        padding: 12px;
    }
}

@media (max-height: 500px) {
    .modal-dialog {
        margin: 5px auto;
    }
    
    .success-header {
        padding: 20px 15px;
    }
    
    .success-header .success-icon {
        width: 50px;
        height: 50px;
        font-size: 1.5rem;
        margin-bottom: 10px;
    }
    
    .success-header h3 {
        font-size: 1rem;
    }
    
    .credentials-body {
        max-height: 60vh;
    }
}

.credentials-body::-webkit-scrollbar {
    width: 5px;
}

.credentials-body::-webkit-scrollbar-track {
    background: rgba(255,255,255,0.05);
}

.credentials-body::-webkit-scrollbar-thumb {
    background: rgba(102, 126, 234, 0.5);
    border-radius: 10px;
}

body.modal-open {
    overflow: hidden;
}
</style>

<div class="main-content">
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <div>
                <h1><i class="fas fa-user-plus text-primary"></i> Add New Trainer</h1>
                <p class="text-muted">Register a new training instructor with login access</p>
            </div>
            <div>
                <a href="trainers.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Trainers
                </a>
            </div>
        </div>
    </div>

    <!-- ===== SUCCESS MODAL ===== -->
    <?php if ($show_credentials && $credentials): ?>
        <div class="modal fade show" id="successModal" tabindex="-1" 
             style="display: block; background: rgba(0,0,0,0.75); padding-right: 0 !important;" 
             data-backdrop="static" 
             data-keyboard="false">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content success-modal-content">
                    
                    <div class="success-header">
                        <div class="success-icon">
                            <i class="fas fa-check"></i>
                        </div>
                        <h3>Trainer Created Successfully!</h3>
                        <p>Login credentials have been generated</p>
                    </div>
                    
                    <div class="credentials-body">
                        
                        <!-- Email Status -->
                        <?php if (!empty($credentials['email_sent'])): ?>
                            <div class="email-status sent">
                                <i class="fas fa-envelope-open-text"></i>
                                <div class="status-text">
                                    <strong>✓ Welcome email sent!</strong>
                                    Login details emailed to <strong><?php echo htmlspecialchars($credentials['email']); ?></strong>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="email-status failed">
                                <i class="fas fa-exclamation-triangle"></i>
                                <div class="status-text">
                                    <strong>Email could not be sent</strong>
                                    <?php echo !empty($credentials['email_error']) ? htmlspecialchars($credentials['email_error']) : 'Please share the credentials manually.'; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Warning -->
                        <div class="warning-box">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div>
                                <strong>Save this information now!</strong><br>
                                The password will not be shown again for security.
                            </div>
                        </div>
                        
                        <!-- Credentials List -->
                        <div class="credentials-info">
                            
                            <div class="credential-row">
                                <div>
                                    <span class="cred-label">Trainer Name</span>
                                    <div class="cred-value"><?php echo htmlspecialchars($credentials['name']); ?></div>
                                </div>
                            </div>
                            
                            <div class="credential-row">
                                <div>
                                    <span class="cred-label">Login Email</span>
                                    <div class="cred-value"><?php echo htmlspecialchars($credentials['email']); ?></div>
                                </div>
                                <button type="button" class="copy-btn" onclick="copyText('<?php echo htmlspecialchars($credentials['email']); ?>', this)">
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                            </div>
                            
                            <div class="credential-row">
                                <div>
                                    <span class="cred-label">Username</span>
                                    <div class="cred-value"><?php echo htmlspecialchars($credentials['username']); ?></div>
                                </div>
                                <button type="button" class="copy-btn" onclick="copyText('<?php echo htmlspecialchars($credentials['username']); ?>', this)">
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                            </div>
                            
                            <div class="credential-row highlight">
                                <div>
                                    <span class="cred-label">Password <?php echo !empty($credentials['auto_generated']) ? '(Auto-Generated)' : ''; ?></span>
                                    <div class="cred-value"><?php echo htmlspecialchars($credentials['password']); ?></div>
                                </div>
                                <button type="button" class="copy-btn" onclick="copyText('<?php echo htmlspecialchars($credentials['password']); ?>', this)">
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                            </div>
                            
                            <div class="credential-row">
                                <div>
                                    <span class="cred-label">Login URL</span>
                                    <div class="cred-value" style="font-size: 0.8rem;">
                                        <?php echo rtrim(SITE_URL, '/'); ?>/login.php
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="action-buttons">
                            <button type="button" onclick="copyAll()" class="btn btn-success">
                                <i class="fas fa-copy"></i> Copy All
                            </button>
                            
                            <?php if (empty($credentials['email_sent'])): ?>
                                <a href="mailto:<?php echo htmlspecialchars($credentials['email']); ?>?subject=Your Trainer Account - <?php echo SITE_NAME; ?>&body=Hello <?php echo urlencode($credentials['name']); ?>,%0D%0A%0D%0AYour trainer account has been created.%0D%0A%0D%0ALogin Credentials:%0D%0A--------------------%0D%0AEmail: <?php echo urlencode($credentials['email']); ?>%0D%0AUsername: <?php echo urlencode($credentials['username']); ?>%0D%0APassword: <?php echo urlencode($credentials['password']); ?>%0D%0A%0D%0ALogin URL: <?php echo rtrim(SITE_URL, '/'); ?>/login.php%0D%0A%0D%0APlease change your password after first login." 
                                   class="btn btn-info">
                                    <i class="fas fa-envelope"></i> Send Manually
                                </a>
                            <?php endif; ?>
                            
                            <a href="view-trainer.php?id=<?php echo $credentials['trainer_id']; ?>" class="btn btn-primary">
                                <i class="fas fa-eye"></i> View Profile
                            </a>
                            
                            <a href="trainers.php" class="btn btn-secondary">
                                <i class="fas fa-list"></i> All Trainers
                            </a>
                        </div>
                        
                        <a href="add-trainer.php" class="add-another-btn">
                            <i class="fas fa-plus"></i> Add Another Trainer
                        </a>
                        
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        document.body.classList.add('modal-open');
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                window.location.href = 'trainers.php';
            }
        });
        </script>
    <?php endif; ?>

    <!-- Error Messages -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i>
            <strong>Please fix the following errors:</strong>
            <ul class="mb-0 mt-2">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <!-- ===== TRAINER FORM ===== -->
    <form method="POST" enctype="multipart/form-data" id="trainerForm">
        <div class="row">
            <div class="col-lg-8">
                <!-- Basic Info -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">First Name</label>
                                    <input type="text" name="first_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($form_data['first_name'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">Last Name</label>
                                    <input type="text" name="last_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($form_data['last_name'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">Email (Login)</label>
                                    <input type="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" required>
                                    <small class="text-muted">Trainer will use this to login</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">Phone</label>
                                    <input type="tel" name="phone" class="form-control" 
                                           value="<?php echo htmlspecialchars($form_data['phone'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">Specialization</label>
                                    <input type="text" name="specialization" class="form-control" 
                                           value="<?php echo htmlspecialchars($form_data['specialization'] ?? ''); ?>" 
                                           placeholder="e.g., Programming" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Qualification</label>
                                    <input type="text" name="qualification" class="form-control" 
                                           value="<?php echo htmlspecialchars($form_data['qualification'] ?? ''); ?>" 
                                           placeholder="e.g., PhD Computer Science">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Years of Experience</label>
                            <input type="number" name="experience_years" class="form-control" 
                                   value="<?php echo htmlspecialchars($form_data['experience_years'] ?? 0); ?>" min="0">
                        </div>
                        
                        <div class="form-group">
                            <label>Bio / About</label>
                            <textarea name="bio" class="form-control" rows="3" 
                                      placeholder="Brief professional background..."><?php echo htmlspecialchars($form_data['bio'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Login Credentials -->
                <div class="card mb-4" style="border: 2px solid #667eea;">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-key"></i> Login Credentials</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Set a password. The trainer will use <strong>email + password</strong> to login.
                        </div>
                        
                        <div class="custom-control custom-checkbox mb-3">
                            <input type="checkbox" class="custom-control-input" id="auto_generate" 
                                   name="auto_generate" onchange="togglePasswordFields()">
                            <label class="custom-control-label" for="auto_generate">
                                <strong>Auto-generate secure password</strong>
                                <br>
                                <small class="text-muted">System will create a strong password automatically</small>
                            </label>
                        </div>
                        
                        <div id="passwordFields">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="required">Password</label>
                                        <div class="input-group">
                                            <input type="password" name="password" id="password" 
                                                   class="form-control" minlength="6" placeholder="Min 6 characters">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password', this)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="password-strength" id="strengthBar"></div>
                                        <small class="text-muted" id="strengthText">Enter a password</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="required">Confirm Password</label>
                                        <div class="input-group">
                                            <input type="password" name="confirm_password" id="confirm_password" 
                                                   class="form-control" minlength="6" placeholder="Re-enter password">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('confirm_password', this)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <small id="matchText" class="text-muted"></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="custom-control custom-checkbox mt-3">
                            <input type="checkbox" class="custom-control-input" id="send_email" 
                                   name="send_email" checked>
                            <label class="custom-control-label" for="send_email">
                                <i class="fas fa-envelope text-primary"></i>
                                <strong>Send welcome email</strong> with login credentials to the trainer
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Address -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-map-marker-alt"></i> Address & Social</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Address</label>
                            <input type="text" name="address" class="form-control" 
                                   value="<?php echo htmlspecialchars($form_data['address'] ?? ''); ?>">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>City</label>
                                    <input type="text" name="city" class="form-control" 
                                           value="<?php echo htmlspecialchars($form_data['city'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>State</label>
                                    <input type="text" name="state" class="form-control" 
                                           value="<?php echo htmlspecialchars($form_data['state'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Country</label>
                                    <input type="text" name="country" class="form-control" 
                                           value="<?php echo htmlspecialchars($form_data['country'] ?? 'Nigeria'); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><i class="fab fa-linkedin text-primary"></i> LinkedIn</label>
                                    <input type="url" name="linkedin_url" class="form-control" 
                                           value="<?php echo htmlspecialchars($form_data['linkedin_url'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><i class="fab fa-twitter text-info"></i> Twitter</label>
                                    <input type="url" name="twitter_url" class="form-control" 
                                           value="<?php echo htmlspecialchars($form_data['twitter_url'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><i class="fas fa-globe text-success"></i> Website</label>
                                    <input type="url" name="website_url" class="form-control" 
                                           value="<?php echo htmlspecialchars($form_data['website_url'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Profile Picture -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-camera"></i> Profile Picture</h5>
                    </div>
                    <div class="card-body text-center">
                        <img id="previewImage" 
                             src="../assets/images/default-avatar.png" 
                             style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid #e2e8f0; margin-bottom: 15px;">
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="profile_picture" 
                                   name="profile_picture" accept="image/*" onchange="previewImage(this)">
                            <label class="custom-file-label" for="profile_picture">Choose image...</label>
                        </div>
                        <small class="text-muted d-block mt-2">Max 5MB</small>
                    </div>
                </div>
                
                <!-- Status -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-toggle-on"></i> Status</h5>
                    </div>
                    <div class="card-body">
                        <select name="status" class="form-control">
                            <option value="active" <?php echo ($form_data['status'] ?? 'active') == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($form_data['status'] ?? '') == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="on_leave" <?php echo ($form_data['status'] ?? '') == 'on_leave' ? 'selected' : ''; ?>>On Leave</option>
                        </select>
                    </div>
                </div>
                
                <!-- Submit -->
                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary btn-block btn-lg">
                            <i class="fas fa-save"></i> Create Trainer
                        </button>
                        <a href="trainers.php" class="btn btn-secondary btn-block mt-2">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// ===== PASSWORD TOGGLE =====
function togglePassword(fieldId, btn) {
    var field = document.getElementById(fieldId);
    var icon = btn.querySelector('i');
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// ===== AUTO-GENERATE TOGGLE (with disabled to prevent submission) =====
function togglePasswordFields() {
    var checkbox = document.getElementById('auto_generate');
    var fields = document.getElementById('passwordFields');
    var password = document.getElementById('password');
    var confirm = document.getElementById('confirm_password');
    
    if (checkbox.checked) {
        fields.style.display = 'none';
        password.removeAttribute('required');
        confirm.removeAttribute('required');
        // ⭐ CRITICAL: Disable so values aren't submitted
        password.disabled = true;
        confirm.disabled = true;
        password.value = '';
        confirm.value = '';
    } else {
        fields.style.display = 'block';
        password.setAttribute('required', 'required');
        confirm.setAttribute('required', 'required');
        password.disabled = false;
        confirm.disabled = false;
    }
}

// ===== PROFILE PREVIEW =====
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewImage').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// ===== PASSWORD STRENGTH =====
document.getElementById('password').addEventListener('input', function() {
    var val = this.value;
    var strength = 0;
    if (val.length >= 6) strength++;
    if (val.match(/[a-z]/)) strength++;
    if (val.match(/[A-Z]/)) strength++;
    if (val.match(/[0-9]/)) strength++;
    if (val.match(/[^a-zA-Z0-9]/)) strength++;
    
    var bar = document.getElementById('strengthBar');
    var text = document.getElementById('strengthText');
    bar.className = 'password-strength';
    
    if (val.length === 0) {
        text.textContent = 'Enter a password';
    } else if (strength <= 2) {
        bar.classList.add('strength-weak');
        text.textContent = 'Weak password';
    } else if (strength === 3) {
        bar.classList.add('strength-fair');
        text.textContent = 'Fair password';
    } else if (strength === 4) {
        bar.classList.add('strength-good');
        text.textContent = 'Good password';
    } else {
        bar.classList.add('strength-strong');
        text.textContent = 'Strong password';
    }
});

// ===== CONFIRM PASSWORD =====
document.getElementById('confirm_password').addEventListener('input', function() {
    var password = document.getElementById('password').value;
    var confirm = this.value;
    var text = document.getElementById('matchText');
    
    if (confirm.length === 0) {
        text.textContent = '';
    } else if (password === confirm) {
        text.textContent = '✓ Passwords match';
        text.style.color = '#48bb78';
    } else {
        text.textContent = '✗ Passwords do not match';
        text.style.color = '#e74a3b';
    }
});

// ===== COPY FUNCTIONS =====
function copyText(text, btn) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function() {
            showCopied(btn);
        });
    } else {
        var temp = document.createElement('textarea');
        temp.value = text;
        document.body.appendChild(temp);
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
        showCopied(btn);
    }
}

function showCopied(btn) {
    var original = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
    btn.classList.add('copied');
    setTimeout(function() {
        btn.innerHTML = original;
        btn.classList.remove('copied');
    }, 1500);
}

function copyAll() {
    var credentials = `TRAINER LOGIN CREDENTIALS
=========================
Name: <?php echo $credentials ? addslashes($credentials['name']) : ''; ?>

Email: <?php echo $credentials ? addslashes($credentials['email']) : ''; ?>

Username: <?php echo $credentials ? addslashes($credentials['username']) : ''; ?>

Password: <?php echo $credentials ? addslashes($credentials['password']) : ''; ?>


Login URL: <?php echo rtrim(SITE_URL, '/'); ?>/login.php

Generated on: <?php echo date('Y-m-d H:i:s'); ?>


THE POLYTECHNIC, IBADAN - SKILL DEVELOPMENT CENTRE`;
    
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(credentials).then(function() {
            Swal.fire({
                icon: 'success',
                title: 'Copied!',
                text: 'All credentials copied to clipboard',
                timer: 1500,
                showConfirmButton: false
            });
        });
    } else {
        var temp = document.createElement('textarea');
        temp.value = credentials;
        document.body.appendChild(temp);
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
        Swal.fire({
            icon: 'success',
            title: 'Copied!',
            text: 'All credentials copied to clipboard',
            timer: 1500,
            showConfirmButton: false
        });
    }
}

// ===== FORM SUBMIT LOADING =====
document.getElementById('trainerForm').addEventListener('submit', function() {
    var btn = this.querySelector('button[type="submit"]');
    var original = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
    btn.disabled = true;
    
    setTimeout(function() {
        btn.innerHTML = original;
        btn.disabled = false;
    }, 15000);
});
</script>

<?php require_once 'includes/footer.php'; ?>