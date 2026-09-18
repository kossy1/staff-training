<?php
// admin/payments.php - Manage Payments
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Get payments
$payments = $conn->query("
    SELECT p.*, 
           CONCAT(e.first_name, ' ', e.last_name) as employee_name,
           e.email as employee_email,
           tp.title as training_title
    FROM payments p
    LEFT JOIN employees e ON p.user_id = e.id
    LEFT JOIN training_programs tp ON p.training_id = tp.id
    ORDER BY p.created_at DESC
");

// Get statistics
$stats = [
    'total' => $conn->query("SELECT COUNT(*) as count FROM payments")->fetch_assoc()['count'],
    'success' => $conn->query("SELECT COUNT(*) as count FROM payments WHERE status = 'success'")->fetch_assoc()['count'],
    'pending' => $conn->query("SELECT COUNT(*) as count FROM payments WHERE status = 'pending'")->fetch_assoc()['count'],
    'failed' => $conn->query("SELECT COUNT(*) as count FROM payments WHERE status = 'failed'")->fetch_assoc()['count'],
    'total_amount' => $conn->query("SELECT SUM(amount) as total FROM payments WHERE status = 'success'")->fetch_assoc()['total']
];

$page_title = 'Payment Management';
$page_scripts = '
<script>
$(document).ready(function() {
    if ($.fn.DataTable) {
        $("#paymentsTable").DataTable({
            responsive: true,
            pageLength: 25,
            order: [[0, "desc"]]
        });
    }
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
            <h1><i class="fas fa-credit-card text-primary"></i> Payment Management</h1>
            <p class="text-muted">View and manage all payments</p>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Payments</h6>
                    <h2 class="mb-0"><?php echo $stats['total']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Successful</h6>
                    <h2 class="mb-0"><?php echo $stats['success']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="card-title">Pending</h6>
                    <h2 class="mb-0"><?php echo $stats['pending']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Revenue</h6>
                    <h2 class="mb-0"><?php echo formatNaira($stats['total_amount'] ?? 0); ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="paymentsTable">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Employee</th>
                            <th>Training</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($payments && $payments->num_rows > 0): ?>
                            <?php while ($payment = $payments->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <small class="font-weight-bold"><?php echo htmlspecialchars($payment['reference']); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($payment['employee_name']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($payment['employee_email']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($payment['training_title']); ?></td>
                                    <td>
                                        <span class="font-weight-bold"><?php echo formatNaira($payment['amount']); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $payment['status'] == 'success' ? 'success' : 
                                                ($payment['status'] == 'pending' ? 'warning' : 
                                                ($payment['status'] == 'failed' ? 'danger' : 'secondary')); 
                                        ?>">
                                            <?php echo ucfirst($payment['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?php echo formatDateTime($payment['created_at']); ?></small>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="fas fa-credit-card fa-3x mb-3 d-block"></i>
                                    <h5>No payments found</h5>
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