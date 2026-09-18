<?php
// admin/send-certificate-email.php - Send Certificate via Email with New Branding
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

$certificate_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($certificate_id <= 0) {
    header('Location: certifications.php');
    exit();
}

// Get certificate details
$certificate = $conn->query("
    SELECT c.*, 
           CONCAT(e.first_name, ' ', e.last_name) as employee_name,
           e.email as employee_email,
           e.department as employee_department,
           e.position as employee_position,
           tp.title as training_title,
           tp.type as training_type,
           tp.duration_hours as training_duration,
           tp.start_date as training_start,
           tp.end_date as training_end,
           tp.trainer_name as trainer_name
    FROM certifications c
    LEFT JOIN employees e ON c.employee_id = e.id
    LEFT JOIN training_programs tp ON c.training_id = tp.id
    WHERE c.id = $certificate_id
")->fetch_assoc();

if (!$certificate) {
    header('Location: certifications.php');
    exit();
}

$message = '';
$message_type = '';
$email_sent = false;
$debug_info = array();

// ============================================
// EMAIL FUNCTIONS WITH NEW BRANDING
// ============================================

/**
 * Send email using PHPMailer with Gmail SMTP
 */
function sendEmailWithPHPMailer($to, $subject, $body, $attachment = null, &$debug = array()) {
    // Check if PHPMailer is available
    if (!file_exists('../vendor/autoload.php')) {
        $debug[] = "PHPMailer autoload not found. Please run: composer require phpmailer/phpmailer";
        return false;
    }
    
    try {
        require_once '../vendor/autoload.php';
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = defined('MAIL_USERNAME') ? MAIL_USERNAME : 'your-email@gmail.com';
        $mail->Password   = defined('MAIL_PASSWORD') ? MAIL_PASSWORD : 'your-app-password';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        $debug[] = "SMTP configured with Gmail";
        
        // Recipients
        $from_email = defined('MAIL_FROM') ? MAIL_FROM : 'noreply@' . $_SERVER['HTTP_HOST'];
        $mail->setFrom($from_email, SITE_NAME);
        $mail->addAddress($to);
        $debug[] = "Recipient: " . $to;
        
        // Attachments
        if ($attachment && file_exists($attachment)) {
            $mail->addAttachment($attachment);
            $debug[] = "Attachment added: " . $attachment;
        } else {
            $debug[] = "No attachment found";
        }
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);
        
        $debug[] = "Attempting to send email...";
        
        $mail->send();
        $debug[] = "Email sent successfully!";
        return true;
        
    } catch (Exception $e) {
        $debug[] = "PHPMailer Error: " . $e->getMessage();
        $debug[] = "Error details: " . ($mail->ErrorInfo ?? 'No additional info');
        return false;
    }
}

/**
 * Generate HTML email body with new branding
 */
function generateEmailBody($certificate, $custom_message) {
    $site_url = SITE_URL ?? 'http://localhost/staff-training/';
    
    $body = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Certificate of Completion</title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                line-height: 1.6; 
                color: #333; 
                margin: 0;
                padding: 0;
            }
            .container { 
                max-width: 600px; 
                margin: 0 auto; 
                padding: 20px; 
            }
            .header { 
                background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); 
                color: white; 
                padding: 40px 30px; 
                text-align: center; 
                border-radius: 10px 10px 0 0; 
            }
            .header .institution { 
                font-size: 1.6rem; 
                font-weight: 800; 
                color: #667eea; 
                letter-spacing: 2px; 
                text-transform: uppercase;
            }
            .header .dept { 
                font-size: 1rem; 
                color: rgba(255,255,255,0.8); 
                margin-top: 5px; 
                font-weight: 600;
            }
            .header .divider { 
                border: none; 
                height: 2px; 
                background: linear-gradient(135deg, #667eea, #764ba2); 
                width: 80px; 
                margin: 15px auto; 
                border-radius: 10px;
            }
            .header .cert-title { 
                font-size: 1.4rem; 
                font-weight: 700; 
                margin-top: 10px;
                letter-spacing: 3px;
            }
            .header .cert-number { 
                font-size: 0.9rem; 
                color: rgba(255,255,255,0.5);
                margin-top: 5px;
            }
            .content { 
                padding: 30px; 
                background: #f8f9fc; 
            }
            .cert-details { 
                background: white; 
                padding: 25px; 
                border-radius: 10px; 
                margin: 20px 0; 
                box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
                border-left: 4px solid #667eea;
            }
            .cert-details h3 { 
                color: #667eea; 
                font-size: 1.3rem;
                margin-top: 0;
            }
            .cert-details .detail-row {
                display: flex;
                justify-content: space-between;
                padding: 8px 0;
                border-bottom: 1px solid #f1f3f5;
            }
            .cert-details .detail-row:last-child {
                border-bottom: none;
            }
            .cert-details .detail-label {
                color: #6c757d;
                font-weight: 600;
            }
            .cert-details .detail-value {
                color: #2d3748;
                font-weight: 500;
            }
            .footer { 
                text-align: center; 
                padding: 25px; 
                color: #6c757d; 
                font-size: 0.85rem; 
                background: #f8f9fc; 
                border-radius: 0 0 10px 10px;
                border-top: 1px solid #e2e8f0;
            }
            .btn { 
                display: inline-block; 
                padding: 12px 35px; 
                background: linear-gradient(135deg, #667eea, #764ba2);
                color: white; 
                text-decoration: none; 
                border-radius: 50px; 
                font-weight: 600;
                transition: all 0.3s ease;
            }
            .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
            }
            .institution-footer { 
                color: #667eea; 
                font-weight: 700; 
                font-size: 0.9rem; 
            }
            .footer-divider {
                border: none;
                height: 1px;
                background: linear-gradient(135deg, #667eea, #764ba2);
                width: 50px;
                margin: 15px auto;
                border-radius: 10px;
            }
            .greeting {
                font-size: 1.1rem;
                color: #2d3748;
            }
            .highlight {
                color: #667eea;
                font-weight: 600;
            }
            @media (max-width: 480px) {
                .header .institution { font-size: 1.2rem; }
                .header .cert-title { font-size: 1.1rem; }
                .cert-details .detail-row { flex-direction: column; }
                .cert-details .detail-row .detail-value { margin-top: 3px; }
                .btn { padding: 10px 25px; font-size: 0.9rem; }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div class="institution">THE POLYTECHNIC, IBADAN</div>
                <div class="dept">SKILL DEVELOPMENT CENTRE</div>
                <hr class="divider">
                <div class="cert-title">Certificate of Completion</div>
                <div class="cert-number">#' . htmlspecialchars($certificate['certification_number']) . '</div>
            </div>
            <div class="content">
                <p class="greeting">Dear <strong>' . htmlspecialchars($certificate['employee_name']) . '</strong>,</p>
                
                ' . (!empty($custom_message) ? '<p>' . nl2br(htmlspecialchars($custom_message)) . '</p>' : '') . '
                
                <p>We are pleased to inform you that your certificate has been issued for successfully completing the training program:</p>
                
                <div class="cert-details">
                    <h3>' . htmlspecialchars($certificate['training_title']) . '</h3>
                    <div class="detail-row">
                        <span class="detail-label">Certificate #</span>
                        <span class="detail-value">' . htmlspecialchars($certificate['certification_number']) . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Type</span>
                        <span class="detail-value">' . ucfirst(str_replace('_', ' ', $certificate['training_type'])) . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Duration</span>
                        <span class="detail-value">' . $certificate['training_duration'] . ' hours</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Issue Date</span>
                        <span class="detail-value">' . formatDate($certificate['issue_date']) . '</span>
                    </div>
                    ' . ($certificate['expiry_date'] ? '
                    <div class="detail-row">
                        <span class="detail-label">Expiry Date</span>
                        <span class="detail-value">' . formatDate($certificate['expiry_date']) . '</span>
                    </div>' : '') . '
                    <div class="detail-row">
                        <span class="detail-label">Issued By</span>
                        <span class="detail-value institution-footer">THE POLYTECHNIC, IBADAN - SKILL DEVELOPMENT CENTRE</span>
                    </div>
                    ' . ($certificate['trainer_name'] ? '
                    <div class="detail-row">
                        <span class="detail-label">Trainer</span>
                        <span class="detail-value">' . htmlspecialchars($certificate['trainer_name']) . '</span>
                    </div>' : '') . '
                </div>
                
                <p>You can view and download your certificate using the link below:</p>
                <p style="text-align: center; margin: 30px 0;">
                    <a href="' . $site_url . 'admin/view-certificate.php?id=' . $certificate['id'] . '" class="btn">View Your Certificate</a>
                </p>
                
                <p style="margin-top: 30px;">Best regards,<br><strong>THE POLYTECHNIC, IBADAN - SKILL DEVELOPMENT CENTRE</strong><br>Staff Training &amp; Development Team</p>
            </div>
            <div class="footer">
                <hr class="footer-divider">
                <p style="margin-bottom: 5px;">This is an automated message. Please do not reply to this email.</p>
                <p style="margin: 0;">&copy; ' . date('Y') . ' THE POLYTECHNIC, IBADAN - SKILL DEVELOPMENT CENTRE</p>
                <p style="margin: 0; font-size: 0.8rem;">Staff Training &amp; Development Tracking System</p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    return $body;
}

// Send email
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_email'])) {
    $to = trim($_POST['email'] ?? $certificate['employee_email']);
    $subject = trim($_POST['subject'] ?? "Certificate of Completion - " . $certificate['certification_name']);
    $custom_message = trim($_POST['message'] ?? '');
    $send_copy = isset($_POST['send_copy']) ? true : false;
    
    // Validate email
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email address!";
        $message_type = 'danger';
    } else {
        // Prepare email content
        $email_body = generateEmailBody($certificate, $custom_message);
        
        // Attach certificate file if exists
        $attachment = null;
        if (!empty($certificate['file_path']) && file_exists('../uploads/certificates/' . $certificate['file_path'])) {
            $attachment = '../uploads/certificates/' . $certificate['file_path'];
        }
        
        // Send email
        $email_sent = sendEmailWithPHPMailer($to, $subject, $email_body, $attachment, $debug_info);
        
        if ($email_sent) {
            // Log the action
            logAction($_SESSION['user_id'], 'certificate_email_sent', [
                'certificate_id' => $certificate_id,
                'recipient' => $to
            ]);
            
            $message = "Certificate email sent successfully to " . htmlspecialchars($to) . "!";
            $message_type = 'success';
            
            // Send copy to admin if checked
            if ($send_copy && isset($_SESSION['email']) && !empty($_SESSION['email'])) {
                sendEmailWithPHPMailer($_SESSION['email'], "Copy: " . $subject, $email_body, $attachment, $debug_info);
            }
        } else {
            $message = "Failed to send email. Please check your email configuration.";
            $message_type = 'danger';
        }
    }
}

// Get default email data
$default_subject = "Certificate of Completion - " . $certificate['certification_name'];
$default_message = "Congratulations on completing the training program! Please find your certificate details below.";

$page_title = 'Send Certificate Email';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<style>
.email-preview {
    background: #f8f9fc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 20px;
    margin: 15px 0;
}
.certificate-info {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
}
.certificate-info .info-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #f1f3f5;
}
.certificate-info .info-item:last-child {
    border-bottom: none;
}
.status-badge {
    padding: 4px 12px;
    border-radius: 50px;
    font-size: 0.8rem;
    font-weight: 600;
}
.status-badge.active { background: #d4edda; color: #155724; }
.status-badge.expired { background: #f8d7da; color: #721c24; }
.status-badge.revoked { background: #fff3cd; color: #856404; }
.debug-info {
    background: #1a1a2e;
    color: #00ff00;
    padding: 15px;
    border-radius: 8px;
    font-family: monospace;
    font-size: 0.85rem;
    max-height: 200px;
    overflow-y: auto;
    margin-top: 10px;
}
.debug-info .debug-line {
    padding: 2px 0;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}
.gap-2 { gap: 10px; }
.flex-wrap { flex-wrap: wrap; }
.institution-badge {
    display: inline-block;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 4px 12px;
    border-radius: 50px;
    font-size: 0.7rem;
    font-weight: 600;
}

@media (max-width: 768px) {
    .certificate-info .info-item {
        flex-direction: column;
    }
    .certificate-info .info-item .label {
        font-weight: 600;
    }
}
</style>

<div class="main-content">
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <div>
                <h1><i class="fas fa-envelope text-primary"></i> Send Certificate Email</h1>
                <p class="text-muted">
                    <span class="institution-badge">THE POLYTECHNIC, IBADAN - SDC</span>
                    <span style="margin-left: 10px;">Send certificate #<?php echo htmlspecialchars($certificate['certification_number']); ?> to employee</span>
                </p>
            </div>
            <div>
                <a href="view-certificate.php?id=<?php echo $certificate_id; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Certificate
                </a>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
            <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo $message; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Debug Info -->
    <?php if (!empty($debug_info)): ?>
        <div class="card mb-3">
            <div class="card-header bg-dark text-white">
                <h6 class="mb-0"><i class="fas fa-bug"></i> Debug Information</h6>
            </div>
            <div class="card-body p-0">
                <div class="debug-info">
                    <?php foreach ($debug_info as $line): ?>
                        <div class="debug-line">> <?php echo htmlspecialchars($line); ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <!-- Email Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-edit"></i> Compose Email</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label class="required">Recipient Email</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                </div>
                                <input type="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($certificate['employee_email']); ?>" required>
                            </div>
                            <small class="text-muted">The certificate will be sent to this email address</small>
                        </div>

                        <div class="form-group">
                            <label class="required">Subject</label>
                            <input type="text" name="subject" id="subject" class="form-control" 
                                   value="<?php echo htmlspecialchars($default_subject); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Custom Message</label>
                            <textarea name="message" id="custom_message" class="form-control" rows="5" 
                                      placeholder="Add a personal message to the email..."><?php echo htmlspecialchars($default_message); ?></textarea>
                            <small class="text-muted">This message will appear before the certificate details</small>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="send_copy" name="send_copy" checked>
                                <label class="custom-control-label" for="send_copy">
                                    <i class="fas fa-copy"></i> Send a copy to myself
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Note:</strong> The certificate file will be attached automatically if available.
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" name="send_email" class="btn btn-success">
                                <i class="fas fa-paper-plane"></i> Send Email
                            </button>
                            <a href="view-certificate.php?id=<?php echo $certificate_id; ?>" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Certificate Info -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-certificate"></i> Certificate Details</h5>
                </div>
                <div class="card-body">
                    <div class="certificate-info">
                        <div class="info-item">
                            <span class="label">Certificate #</span>
                            <span class="value"><?php echo htmlspecialchars($certificate['certification_number']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="label">Employee</span>
                            <span class="value"><?php echo htmlspecialchars($certificate['employee_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="label">Training</span>
                            <span class="value"><?php echo htmlspecialchars($certificate['training_title']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="label">Type</span>
                            <span class="value">
                                <span class="badge badge-<?php 
                                    echo $certificate['training_type'] == 'technical' ? 'primary' : 
                                        ($certificate['training_type'] == 'soft_skill' ? 'success' : 
                                        ($certificate['training_type'] == 'management' ? 'info' : 'secondary')); 
                                ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $certificate['training_type'])); ?>
                                </span>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="label">Issue Date</span>
                            <span class="value"><?php echo formatDate($certificate['issue_date']); ?></span>
                        </div>
                        <?php if ($certificate['expiry_date']): ?>
                            <div class="info-item">
                                <span class="label">Expiry Date</span>
                                <span class="value"><?php echo formatDate($certificate['expiry_date']); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="info-item">
                            <span class="label">Status</span>
                            <span class="value">
                                <span class="status-badge <?php echo $certificate['status']; ?>">
                                    <?php echo ucfirst($certificate['status']); ?>
                                </span>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="label">Has File</span>
                            <span class="value">
                                <?php if (!empty($certificate['file_path'])): ?>
                                    <span class="text-success"><i class="fas fa-check-circle"></i> Yes</span>
                                <?php else: ?>
                                    <span class="text-warning"><i class="fas fa-exclamation-triangle"></i> No file attached</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="info-item" style="border-bottom: none; padding-top: 10px;">
                            <span class="label">Issued By</span>
                            <span class="value" style="font-size: 0.8rem; text-align: right;">
                                <strong>THE POLYTECHNIC, IBADAN</strong><br>
                                <span style="color: #667eea;">SKILL DEVELOPMENT CENTRE</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>