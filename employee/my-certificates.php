<?php
// employee/my-certificates.php - View Employee's Certificates
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isEmployee()) {
    header('Location: ../login.php');
    exit();
}

$employee_id = $_SESSION['employee_id'];

// Get certificates
$certificates = $conn->query("
    SELECT c.*, 
           tp.title as training_title,
           tp.type as training_type
    FROM certifications c
    LEFT JOIN training_programs tp ON c.training_id = tp.id
    WHERE c.employee_id = $employee_id
    ORDER BY c.issue_date DESC
");

$active_certs = $conn->query("SELECT COUNT(*) as count FROM certifications WHERE employee_id = $employee_id AND status = 'active'")->fetch_assoc()['count'];
$expired_certs = $conn->query("SELECT COUNT(*) as count FROM certifications WHERE employee_id = $employee_id AND status = 'expired'")->fetch_assoc()['count'];

$page_title = 'My Certificates';
$page_scripts = '
<script>
$(document).ready(function() {
    $("#certificatesTable").DataTable({
        responsive: true,
        pageLength: 25,
        order: [[0, "desc"]]
    });
});
</script>
';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-certificate text-primary"></i> My Certificates</h1>
            <p class="text-muted">View and download your certificates</p>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-certificate"></i>
                </div>
                <h3 class="stat-number"><?php echo $active_certs + $expired_certs; ?></h3>
                <p class="stat-label">Total Certificates</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3 class="stat-number"><?php echo $active_certs; ?></h3>
                <p class="stat-label">Active</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon danger">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <h3 class="stat-number"><?php echo $expired_certs; ?></h3>
                <p class="stat-label">Expired</p>
            </div>
        </div>
    </div>

    <!-- Certificates -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="certificatesTable">
                    <thead>
                        <tr>
                            <th>Certificate</th>
                            <th>Training</th>
                            <th>Issue Date</th>
                            <th>Expiry Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($certificates && $certificates->num_rows > 0): ?>
                            <?php while ($cert = $certificates->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($cert['certification_name']); ?></strong>
                                        <br>
                                        <small class="text-muted">#<?php echo htmlspecialchars($cert['certification_number']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($cert['training_title'] ?? 'N/A'); ?></td>
                                    <td><?php echo formatDate($cert['issue_date']); ?></td>
                                    <td>
                                        <?php if ($cert['expiry_date']): ?>
                                            <?php echo formatDate($cert['expiry_date']); ?>
                                            <?php if (strtotime($cert['expiry_date']) < time()): ?>
                                                <span class="badge badge-danger">Expired</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">Never</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $cert['status'] == 'active' ? 'success' : ($cert['status'] == 'expired' ? 'danger' : 'warning'); ?>">
                                            <?php echo ucfirst($cert['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="view-certificate.php?id=<?php echo $cert['id']; ?>" 
                                               class="btn btn-outline-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($cert['file_path']): ?>
                                                <a href="../uploads/certificates/<?php echo $cert['file_path']; ?>" 
                                                   target="_blank" class="btn btn-outline-success" title="Download">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="fas fa-certificate fa-3x mb-3 d-block"></i>
                                    <h5>No certificates found</h5>
                                    <p>Complete trainings to earn certificates.</p>
                                    <a href="my-trainings.php" class="btn btn-primary">
                                        <i class="fas fa-chalkboard-teacher"></i> View My Trainings
                                    </a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.stat-card {
    padding: 20px;
    border-radius: 10px;
    background: white;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    transition: all 0.3s ease;
    height: 100%;
}
.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.2);
}
.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 15px;
}
.stat-icon.primary { background: rgba(102, 126, 234, 0.1); color: #667eea; }
.stat-icon.success { background: rgba(72, 187, 120, 0.1); color: #48bb78; }
.stat-icon.danger { background: rgba(231, 74, 59, 0.1); color: #e74a3b; }
.stat-number { font-size: 2rem; font-weight: 800; margin: 0; line-height: 1.2; }
.stat-label { color: #6c757d; font-size: 0.9rem; font-weight: 500; margin: 0; }
</style>

<?php require_once 'includes/footer.php'; ?>