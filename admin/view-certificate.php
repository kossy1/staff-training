<?php
// admin/view-certificate.php - View Certificate Details with New Branding
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

// Get certificate details with employee and training info
$certificate = $conn->query("
    SELECT c.*, 
           CONCAT(e.first_name, ' ', e.last_name) as employee_name,
           e.email as employee_email,
           e.phone as employee_phone,
           e.department as employee_department,
           e.position as employee_position,
           tp.title as training_title,
           tp.type as training_type,
           tp.category as training_category,
           tp.duration_hours as training_duration,
           tp.trainer_name as trainer_name,
           tp.start_date as training_start,
           tp.end_date as training_end
    FROM certifications c
    LEFT JOIN employees e ON c.employee_id = e.id
    LEFT JOIN training_programs tp ON c.training_id = tp.id
    WHERE c.id = $certificate_id
")->fetch_assoc();

if (!$certificate) {
    header('Location: certifications.php');
    exit();
}

// Get certificate statistics
$stats = [
    'total_issued' => $conn->query("SELECT COUNT(*) as count FROM certifications")->fetch_assoc()['count'],
    'active_certs' => $conn->query("SELECT COUNT(*) as count FROM certifications WHERE status = 'active'")->fetch_assoc()['count'],
    'employee_certs' => $conn->query("SELECT COUNT(*) as count FROM certifications WHERE employee_id = {$certificate['employee_id']}")->fetch_assoc()['count']
];

$page_title = 'View Certificate';
$page_scripts = '
<script>
$(document).ready(function() {
    // Print certificate
    $("#printCertificate").on("click", function() {
        window.print();
    });
    
    // Download certificate
    $("#downloadCertificate").on("click", function() {
        window.location.href = "download-certificate.php?id=' . $certificate_id . '";
    });
    
    // Send certificate email
    $("#sendEmail").on("click", function() {
        Swal.fire({
            title: "Send Certificate via Email?",
            text: "This will send the certificate to ' . $certificate['employee_email'] . '",
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#28a745",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "Yes, send it!",
            cancelButtonText: "Cancel"
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "send-certificate-email.php?id=' . $certificate_id . '";
            }
        });
    });
    
    // Delete certificate
    $("#deleteCertificate").on("click", function() {
        Swal.fire({
            title: "Delete Certificate?",
            text: "This action cannot be undone!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "Yes, delete it!",
            cancelButtonText: "Cancel"
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "delete-certificate.php?id=' . $certificate_id . '";
            }
        });
    });
});
</script>
';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<style>
/* Certificate View Styles */
.certificate-wrapper {
    background: #f8f9fc;
    padding: 30px;
    border-radius: 10px;
}

.certificate-card {
    background: white;
    border-radius: 15px;
    padding: 40px;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    position: relative;
    border: 1px solid #e2e8f0;
}

.certificate-card .certificate-border {
    border: 3px double #667eea;
    padding: 30px;
    border-radius: 10px;
    position: relative;
}

.certificate-card .certificate-border::before {
    content: "✦";
    position: absolute;
    top: -15px;
    left: 50%;
    transform: translateX(-50%);
    background: white;
    padding: 0 15px;
    color: #667eea;
    font-size: 1.5rem;
}

.certificate-card .certificate-border::after {
    content: "✦";
    position: absolute;
    bottom: -15px;
    left: 50%;
    transform: translateX(-50%);
    background: white;
    padding: 0 15px;
    color: #667eea;
    font-size: 1.5rem;
}

.certificate-header {
    text-align: center;
    border-bottom: 2px solid #f1f3f5;
    padding-bottom: 20px;
    margin-bottom: 20px;
}

.certificate-header .cert-icon {
    font-size: 3rem;
    color: #f6c23e;
    margin-bottom: 10px;
}

.certificate-header .institution-name {
    font-size: 1.8rem;
    font-weight: 800;
    color: #2d3748;
    margin: 0;
    letter-spacing: 2px;
}

.certificate-header .institution-dept {
    font-size: 1.1rem;
    color: #667eea;
    font-weight: 600;
    margin: 2px 0;
}

.certificate-header .cert-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #2d3748;
    margin-top: 10px;
}

.certificate-header .cert-number {
    color: #6c757d;
    font-size: 0.9rem;
}

.certificate-divider {
    border: none;
    height: 2px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    width: 80px;
    margin: 10px auto;
    border-radius: 10px;
}

.certificate-body {
    text-align: center;
    padding: 20px 0;
}

.certificate-body .awarded-to {
    font-size: 1.1rem;
    color: #6c757d;
    margin-bottom: 5px;
}

.certificate-body .employee-name {
    font-size: 2.5rem;
    font-weight: 800;
    color: #2d3748;
    margin: 10px 0;
}

.certificate-body .cert-text {
    font-size: 1.1rem;
    color: #4a5568;
    margin: 15px 0;
}

.certificate-body .training-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #667eea;
    margin: 10px 0;
}

.certificate-body .training-details {
    color: #6c757d;
    font-size: 0.95rem;
    margin: 5px 0;
}

.certificate-footer {
    display: flex;
    justify-content: space-between;
    border-top: 2px solid #f1f3f5;
    padding-top: 20px;
    margin-top: 20px;
}

.certificate-footer .footer-item {
    text-align: center;
}

.certificate-footer .footer-item label {
    display: block;
    font-size: 0.8rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.certificate-footer .footer-item .value {
    font-weight: 600;
    color: #2d3748;
}

.certificate-footer .footer-item .value .institution-name {
    font-size: 0.9rem;
    font-weight: 700;
    color: #2d3748;
}

.certificate-footer .footer-item .value .dept-name {
    font-size: 0.8rem;
    color: #667eea;
}

.certificate-footer .signature {
    text-align: center;
}

.certificate-footer .signature .sig-line {
    width: 150px;
    border-bottom: 2px solid #2d3748;
    margin: 0 auto 5px;
}

/* Status Badge */
.status-badge {
    padding: 8px 20px;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.9rem;
    display: inline-block;
}
.status-badge.active { background: #d4edda; color: #155724; }
.status-badge.expired { background: #f8d7da; color: #721c24; }
.status-badge.revoked { background: #fff3cd; color: #856404; }

/* Info Grid */
.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin: 15px 0;
}
.info-grid .info-item {
    background: #f8f9fc;
    padding: 12px 15px;
    border-radius: 8px;
}
.info-grid .info-item label {
    display: block;
    font-size: 0.75rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.info-grid .info-item .value {
    font-weight: 600;
    color: #2d3748;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}
.action-buttons .btn {
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
}

@media print {
    .no-print {
        display: none !important;
    }
    .certificate-wrapper {
        background: white !important;
        padding: 0 !important;
    }
    .certificate-card {
        box-shadow: none !important;
        border: none !important;
    }
    .certificate-card .certificate-border {
        border-color: #333 !important;
    }
    .main-content {
        padding: 0 !important;
    }
    .certificate-header .institution-name {
        font-size: 1.5rem;
    }
}

@media (max-width: 768px) {
    .certificate-card {
        padding: 20px;
    }
    .certificate-card .certificate-border {
        padding: 15px;
    }
    .certificate-body .employee-name {
        font-size: 1.8rem;
    }
    .certificate-body .training-title {
        font-size: 1.2rem;
    }
    .certificate-footer {
        flex-direction: column;
        gap: 10px;
    }
    .info-grid {
        grid-template-columns: 1fr;
    }
    .action-buttons .btn {
        flex: 1;
        min-width: 120px;
    }
    .certificate-header .institution-name {
        font-size: 1.3rem;
    }
    .certificate-header .institution-dept {
        font-size: 0.9rem;
    }
}
</style>

<div class="main-content">
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <div>
                <h1><i class="fas fa-certificate text-primary"></i> Certificate Details</h1>
                <p class="text-muted">View and manage certificate #<?php echo $certificate['certification_number']; ?></p>
            </div>
            <div class="action-buttons no-print">
                <a href="certifications.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row g-3 mb-4 no-print">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Certificates</h6>
                    <h3 class="mb-0"><?php echo $stats['total_issued']; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Active Certificates</h6>
                    <h3 class="mb-0"><?php echo $stats['active_certs']; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title">Employee Certificates</h6>
                    <h3 class="mb-0"><?php echo $stats['employee_certs']; ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Certificate Display -->
            <div class="certificate-wrapper">
                <div class="certificate-card">
                    <div class="certificate-border">
                        <!-- Certificate Header -->
                        <div class="certificate-header">
                            <div class="cert-icon">
                                <i class="fas fa-certificate"></i>
                            </div>
                            <div class="institution-name">THE POLYTECHNIC, IBADAN</div>
                            <div class="institution-dept">SKILL DEVELOPMENT CENTRE</div>
                            <hr class="certificate-divider">
                            <div class="cert-title">CERTIFICATE OF COMPLETION</div>
                            <div class="cert-number">
                                Certificate #<?php echo htmlspecialchars($certificate['certification_number']); ?>
                            </div>
                        </div>

                        <!-- Certificate Body -->
                        <div class="certificate-body">
                            <div class="awarded-to">This is to certify that</div>
                            <div class="employee-name">
                                <?php echo htmlspecialchars($certificate['employee_name']); ?>
                            </div>
                            
                            <div class="cert-text">has successfully completed the training program:</div>
                            
                            <div class="training-title">
                                <?php echo htmlspecialchars($certificate['training_title']); ?>
                            </div>
                            
                            <div class="training-details">
                                <i class="fas fa-tag"></i> <?php echo ucfirst(str_replace('_', ' ', $certificate['training_type'])); ?>
                                <?php if ($certificate['training_category']): ?>
                                    <span class="mx-2">•</span>
                                    <i class="fas fa-folder"></i> <?php echo htmlspecialchars($certificate['training_category']); ?>
                                <?php endif; ?>
                                <span class="mx-2">•</span>
                                <i class="fas fa-clock"></i> <?php echo $certificate['training_duration']; ?> hours
                            </div>

                            <div class="training-details">
                                <i class="far fa-calendar-alt"></i> 
                                <?php echo formatDate($certificate['training_start']); ?> - <?php echo formatDate($certificate['training_end']); ?>
                            </div>

                            <?php if ($certificate['trainer_name']): ?>
                                <div class="training-details">
                                    <i class="fas fa-user-tie"></i> Trainer: <?php echo htmlspecialchars($certificate['trainer_name']); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Certificate Footer -->
                        <div class="certificate-footer">
                            <div class="footer-item">
                                <label>Issue Date</label>
                                <div class="value"><?php echo formatDate($certificate['issue_date']); ?></div>
                            </div>
                            <div class="footer-item">
                                <label>Status</label>
                                <div>
                                    <span class="status-badge <?php echo $certificate['status']; ?>">
                                        <?php echo ucfirst($certificate['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <?php if ($certificate['expiry_date']): ?>
                                <div class="footer-item">
                                    <label>Expiry Date</label>
                                    <div class="value"><?php echo formatDate($certificate['expiry_date']); ?></div>
                                </div>
                            <?php else: ?>
                                <div class="footer-item">
                                    <label>Expiry Date</label>
                                    <div class="value text-muted">Never</div>
                                </div>
                            <?php endif; ?>
                            <div class="footer-item">
                                <label>Issued By</label>
                                <div class="value">
                                    <div class="institution-name">THE POLYTECHNIC, IBADAN</div>
                                    <div class="dept-name">SKILL DEVELOPMENT CENTRE</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Certificate Info -->
            <div class="card no-print">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Certificate Information</h5>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Certificate #</label>
                            <div class="value"><?php echo htmlspecialchars($certificate['certification_number']); ?></div>
                        </div>
                        <div class="info-item">
                            <label>Employee</label>
                            <div class="value"><?php echo htmlspecialchars($certificate['employee_name']); ?></div>
                        </div>
                        <div class="info-item">
                            <label>Email</label>
                            <div class="value"><?php echo htmlspecialchars($certificate['employee_email']); ?></div>
                        </div>
                        <div class="info-item">
                            <label>Department</label>
                            <div class="value"><?php echo htmlspecialchars($certificate['employee_department'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-item">
                            <label>Training</label>
                            <div class="value"><?php echo htmlspecialchars($certificate['training_title']); ?></div>
                        </div>
                        <div class="info-item">
                            <label>Status</label>
                            <div class="value">
                                <span class="status-badge <?php echo $certificate['status']; ?>" style="font-size: 0.75rem; padding: 4px 12px;">
                                    <?php echo ucfirst($certificate['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <?php if ($certificate['file_path']): ?>
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-file-pdf"></i> 
                            Certificate file attached:
                            <a href="../uploads/certificates/<?php echo $certificate['file_path']; ?>" target="_blank" class="font-weight-bold">
                                View File
                            </a>
                        </div>
                    <?php endif; ?>

                    <hr>

                    <div class="action-buttons">
                        <button id="printCertificate" class="btn btn-info">
                            <i class="fas fa-print"></i> Print
                        </button>
                        <button id="downloadCertificate" class="btn btn-success">
                            <i class="fas fa-download"></i> Download
                        </button>
                        <button id="sendEmail" class="btn btn-primary">
                            <i class="fas fa-envelope"></i> Send Email
                        </button>
                        <button id="deleteCertificate" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </div>

            <!-- Employee Info -->
            <div class="card mt-3 no-print">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user"></i> Employee Details</h5>
                </div>
                <div class="card-body">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($certificate['employee_name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($certificate['employee_email']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($certificate['employee_phone'] ?? 'N/A'); ?></p>
                    <p><strong>Department:</strong> <?php echo htmlspecialchars($certificate['employee_department'] ?? 'N/A'); ?></p>
                    <p><strong>Position:</strong> <?php echo htmlspecialchars($certificate['employee_position'] ?? 'N/A'); ?></p>
                    <a href="../admin/view-employee.php?id=<?php echo $certificate['employee_id']; ?>" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-user"></i> View Employee
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>