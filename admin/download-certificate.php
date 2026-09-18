<?php
// admin/download-certificate.php - Download Certificate
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

// If certificate has a file, download it
if (!empty($certificate['file_path']) && file_exists('../uploads/certificates/' . $certificate['file_path'])) {
    $file_path = '../uploads/certificates/' . $certificate['file_path'];
    $file_name = 'certificate_' . $certificate['certification_number'] . '.' . pathinfo($file_path, PATHINFO_EXTENSION);
    
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $file_name . '"');
    header('Content-Length: ' . filesize($file_path));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    readfile($file_path);
    exit();
}

// If no file exists, generate a simple certificate HTML and convert to PDF
// For simplicity, we'll create a downloadable HTML certificate
// In production, you would use a PDF library like dompdf or tcpdf

// Log the download
logAction($_SESSION['user_id'], 'certificate_downloaded', ['certificate_id' => $certificate_id]);

// Generate HTML certificate
$html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Certificate - ' . htmlspecialchars($certificate['certification_number']) . '</title>
    <style>
        body {
            font-family: "Times New Roman", Georgia, serif;
            margin: 0;
            padding: 0;
            background: white;
        }
        .certificate-container {
            width: 800px;
            margin: 50px auto;
            padding: 40px;
            border: 3px double #667eea;
            border-radius: 10px;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        .certificate-icon {
            font-size: 3rem;
            color: #f6c23e;
            margin-bottom: 10px;
        }
        .certificate-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2d3748;
            margin: 10px 0;
            text-transform: uppercase;
            letter-spacing: 3px;
        }
        .certificate-subtitle {
            font-size: 1.1rem;
            color: #6c757d;
            margin-bottom: 20px;
        }
        .certificate-number {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 30px;
        }
        .awarded-text {
            font-size: 1.2rem;
            color: #4a5568;
            margin: 10px 0;
        }
        .employee-name {
            font-size: 2.8rem;
            font-weight: 800;
            color: #2d3748;
            margin: 15px 0;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .completion-text {
            font-size: 1.2rem;
            color: #4a5568;
            margin: 10px 0;
        }
        .training-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #667eea;
            margin: 15px 0;
        }
        .training-details {
            font-size: 1rem;
            color: #6c757d;
            margin: 5px 0;
        }
        .certificate-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
            display: flex;
            justify-content: space-around;
        }
        .footer-item {
            text-align: center;
        }
        .footer-item .label {
            font-size: 0.8rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .footer-item .value {
            font-weight: 600;
            color: #2d3748;
            margin-top: 5px;
        }
        .signature-line {
            width: 150px;
            border-bottom: 2px solid #2d3748;
            margin: 0 auto 5px;
        }
        .company-name {
            color: #667eea;
            font-weight: 700;
            font-size: 1.1rem;
        }
        @media print {
            .certificate-container {
                margin: 0;
                border: none;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="certificate-container">
        <div class="certificate-icon">✦</div>
        <div class="certificate-title">Certificate of Completion</div>
        <div class="certificate-subtitle">This is to certify that</div>
        <div class="employee-name">' . htmlspecialchars($certificate['employee_name']) . '</div>
        <div class="awarded-text">has successfully completed</div>
        <div class="training-title">' . htmlspecialchars($certificate['training_title']) . '</div>
        <div class="training-details">
            <i class="fas fa-tag"></i> ' . ucfirst(str_replace('_', ' ', $certificate['training_type'])) . '
            <span style="margin: 0 10px;">•</span>
            <i class="fas fa-clock"></i> ' . $certificate['training_duration'] . ' hours
            <span style="margin: 0 10px;">•</span>
            <i class="fas fa-calendar"></i> ' . formatDate($certificate['training_start']) . ' - ' . formatDate($certificate['training_end']) . '
        </div>
        ' . ($certificate['trainer_name'] ? '<div class="training-details">Trainer: ' . htmlspecialchars($certificate['trainer_name']) . '</div>' : '') . '
        <div class="certificate-footer">
            <div class="footer-item">
                <div class="label">Issue Date</div>
                <div class="value">' . formatDate($certificate['issue_date']) . '</div>
            </div>
            <div class="footer-item">
                <div class="label">Certificate #</div>
                <div class="value">' . htmlspecialchars($certificate['certification_number']) . '</div>
            </div>
            ' . ($certificate['expiry_date'] ? '
            <div class="footer-item">
                <div class="label">Expiry Date</div>
                <div class="value">' . formatDate($certificate['expiry_date']) . '</div>
            </div>' : '') . '
            <div class="footer-item">
                <div class="label">Issued By</div>
                <div class="value company-name">' . SITE_NAME . '</div>
            </div>
        </div>
        <div style="margin-top: 20px; font-size: 0.8rem; color: #6c757d;">
            <i>This certificate is issued to acknowledge the successful completion of the training program.</i>
        </div>
    </div>
</body>
</html>';

// Set headers for download
header('Content-Type: text/html');
header('Content-Disposition: attachment; filename="certificate_' . $certificate['certification_number'] . '.html"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

echo $html;
exit();
?>