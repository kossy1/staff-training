<?php
// admin/certifications.php - Manage Certifications
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Get certifications with employee and training info
$certifications = $conn->query("
    SELECT c.*, 
           CONCAT(e.first_name, ' ', e.last_name) as employee_name,
           e.email as employee_email,
           tp.title as training_title
    FROM certifications c
    LEFT JOIN employees e ON c.employee_id = e.id
    LEFT JOIN training_programs tp ON c.training_id = tp.id
    ORDER BY c.created_at DESC
");

$page_title = 'Certifications';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <div>
                <h1><i class="fas fa-certificate text-primary"></i> Certifications</h1>
                <p class="text-muted">Manage employee certifications</p>
            </div>
            <div>
                <a href="add-certification.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Issue Certification
                </a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="certificationsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Employee</th>
                            <th>Certification</th>
                            <th>Training</th>
                            <th>Issue Date</th>
                            <th>Expiry Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($certifications && $certifications->num_rows > 0): ?>
                            <?php while ($cert = $certifications->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $cert['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($cert['employee_name'] ?? 'N/A'); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($cert['employee_email']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($cert['certification_name']); ?></td>
                                    <td><?php echo htmlspecialchars($cert['training_title'] ?? 'N/A'); ?></td>
                                    <td><?php echo formatDate($cert['issue_date']); ?></td>
                                    <td><?php echo $cert['expiry_date'] ? formatDate($cert['expiry_date']) : 'N/A'; ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $cert['status'] == 'active' ? 'success' : ($cert['status'] == 'expired' ? 'danger' : 'warning'); ?>">
                                            <?php echo ucfirst($cert['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="view-certification.php?id=<?php echo $cert['id']; ?>" 
                                               class="btn btn-outline-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="download-certificate.php?id=<?php echo $cert['id']; ?>" 
                                               class="btn btn-outline-success" title="Download">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <a href="javascript:void(0)" 
                                               onclick="deleteCertification(<?php echo $cert['id']; ?>)" 
                                               class="btn btn-outline-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="fas fa-certificate fa-3x mb-3 d-block"></i>
                                    <h5>No certifications found</h5>
                                    <p>Click "Issue Certification" to create a new certification.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function deleteCertification(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'delete-certification.php?id=' + id;
        }
    });
}

$(document).ready(function() {
    $('#certificationsTable').DataTable({
        responsive: true,
        pageLength: 25,
        ordering: true,
        searching: true,
        lengthChange: true,
        info: true,
        paging: true
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>