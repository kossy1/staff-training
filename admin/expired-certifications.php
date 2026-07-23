<?php
// admin/expired-certifications.php - Expired Certifications
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Get expired certifications
$expired = $conn->query("
    SELECT c.*, 
           CONCAT(e.first_name, ' ', e.last_name) as employee_name,
           e.email as employee_email,
           tp.title as training_title,
           DATEDIFF(CURDATE(), c.expiry_date) as days_expired
    FROM certifications c
    LEFT JOIN employees e ON c.employee_id = e.id
    LEFT JOIN training_programs tp ON c.training_id = tp.id
    WHERE c.status = 'expired' OR (c.expiry_date IS NOT NULL AND c.expiry_date < CURDATE())
    ORDER BY c.expiry_date ASC
");

// Get certifications expiring soon (within 30 days)
$expiring_soon = $conn->query("
    SELECT c.*, 
           CONCAT(e.first_name, ' ', e.last_name) as employee_name,
           e.email as employee_email,
           tp.title as training_title,
           DATEDIFF(c.expiry_date, CURDATE()) as days_until_expiry
    FROM certifications c
    LEFT JOIN employees e ON c.employee_id = e.id
    LEFT JOIN training_programs tp ON c.training_id = tp.id
    WHERE c.status = 'active' 
      AND c.expiry_date IS NOT NULL 
      AND c.expiry_date >= CURDATE()
      AND c.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ORDER BY c.expiry_date ASC
");

$page_title = 'Expired Certifications';
$page_scripts = '
<script>
$(document).ready(function() {
    $("#expiredTable").DataTable({
        responsive: true,
        pageLength: 25,
        order: [[4, "desc"]]
    });
    
    $("#expiringTable").DataTable({
        responsive: true,
        pageLength: 25,
        order: [[4, "asc"]]
    });
});

function renewCertification(id) {
    Swal.fire({
        title: "Renew Certification",
        text: "Enter new expiry date",
        input: "date",
        inputLabel: "New Expiry Date",
        showCancelButton: true,
        confirmButtonColor: "#28a745",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Renew",
        inputValidator: (value) => {
            if (!value) {
                return "Please select a date";
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "renew-certification.php?id=" + id + "&expiry=" + result.value;
        }
    });
}
</script>
';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <div>
                <h1><i class="fas fa-exclamation-triangle text-warning"></i> Expired Certifications</h1>
                <p class="text-muted">View and manage expired or expiring certifications</p>
            </div>
            <div>
                <a href="certifications.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Certifications
                </a>
            </div>
        </div>
    </div>

    <!-- Expiring Soon -->
    <?php if ($expiring_soon && $expiring_soon->num_rows > 0): ?>
        <div class="card mb-4 border-warning">
            <div class="card-header bg-warning text-white">
                <h5 class="mb-0"><i class="fas fa-clock"></i> Expiring Soon (Within 30 Days)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="expiringTable">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Certification</th>
                                <th>Training</th>
                                <th>Expiry Date</th>
                                <th>Days Until Expiry</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($cert = $expiring_soon->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($cert['employee_name'] ?? 'N/A'); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($cert['employee_email']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($cert['certification_name']); ?></td>
                                    <td><?php echo htmlspecialchars($cert['training_title'] ?? 'N/A'); ?></td>
                                    <td><?php echo formatDate($cert['expiry_date']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $cert['days_until_expiry'] <= 7 ? 'danger' : 'warning'; ?>">
                                            <?php echo $cert['days_until_expiry']; ?> days
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="view-certification.php?id=<?php echo $cert['id']; ?>" 
                                               class="btn btn-outline-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button onclick="renewCertification(<?php echo $cert['id']; ?>)" 
                                                    class="btn btn-outline-success">
                                                <i class="fas fa-sync-alt"></i> Renew
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Expired Certifications -->
    <div class="card">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0"><i class="fas fa-exclamation-circle"></i> Expired Certifications</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="expiredTable">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Certification</th>
                            <th>Training</th>
                            <th>Expiry Date</th>
                            <th>Days Expired</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($expired && $expired->num_rows > 0): ?>
                            <?php while ($cert = $expired->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($cert['employee_name'] ?? 'N/A'); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($cert['employee_email']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($cert['certification_name']); ?></td>
                                    <td><?php echo htmlspecialchars($cert['training_title'] ?? 'N/A'); ?></td>
                                    <td><?php echo formatDate($cert['expiry_date']); ?></td>
                                    <td>
                                        <span class="badge badge-danger">
                                            <?php echo abs($cert['days_expired']); ?> days
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="view-certification.php?id=<?php echo $cert['id']; ?>" 
                                               class="btn btn-outline-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button onclick="renewCertification(<?php echo $cert['id']; ?>)" 
                                                    class="btn btn-outline-success">
                                                <i class="fas fa-sync-alt"></i> Renew
                                            </button>
                                            <a href="javascript:void(0)" 
                                               onclick="deleteCertification(<?php echo $cert['id']; ?>)" 
                                               class="btn btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-success py-4">
                                    <i class="fas fa-check-circle fa-3x mb-3 d-block"></i>
                                    <h5>No expired certifications found</h5>
                                    <p>All certifications are up to date!</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>