<?php
// trainer/requests.php - View and Manage Requests
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'trainer') {
    header('Location: ../login.php');
    exit();
}

$trainer_id = $_SESSION['trainer_id'];

// Handle response
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['respond'])) {
    $request_id = (int)$_POST['request_id'];
    $response = trim($_POST['response'] ?? '');
    $status = sanitizeInput($_POST['status'] ?? 'resolved');
    
    if ($response) {
        $stmt = $conn->prepare("
            UPDATE trainer_requests 
            SET admin_response = ?, status = ?, responded_at = NOW() 
            WHERE id = ? AND trainer_id = ?
        ");
        $stmt->bind_param("ssii", $response, $status, $request_id, $trainer_id);
        if ($stmt->execute()) {
            $message = "Response submitted successfully!";
        }
    }
}

// Get requests
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$query = "
    SELECT tr.*, 
           CONCAT(e.first_name, ' ', e.last_name) as employee_name,
           e.email as employee_email,
           tp.title as training_title
    FROM trainer_requests tr
    LEFT JOIN employees e ON tr.employee_id = e.id
    LEFT JOIN training_programs tp ON tr.training_id = tp.id
    WHERE tr.trainer_id = $trainer_id
";
if ($status_filter) {
    $query .= " AND tr.status = '" . $conn->real_escape_string($status_filter) . "'";
}
$query .= " ORDER BY 
    CASE tr.priority 
        WHEN 'urgent' THEN 1 
        WHEN 'high' THEN 2 
        WHEN 'medium' THEN 3 
        ELSE 4 
    END, 
    tr.created_at DESC";

$requests = $conn->query($query);

$page_title = 'Requests';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <div>
                <h1><i class="fas fa-envelope-open-text text-primary"></i> Requests</h1>
                <p class="text-muted">Training-related requests from students</p>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Filter Tabs -->
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link <?php echo !$status_filter ? 'active' : ''; ?>" href="requests.php">
                All <span class="badge badge-secondary"><?php echo $conn->query("SELECT COUNT(*) as c FROM trainer_requests WHERE trainer_id = $trainer_id")->fetch_assoc()['c']; ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $status_filter == 'pending' ? 'active' : ''; ?>" href="?status=pending">
                Pending <span class="badge badge-warning"><?php echo $conn->query("SELECT COUNT(*) as c FROM trainer_requests WHERE trainer_id = $trainer_id AND status = 'pending'")->fetch_assoc()['c']; ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $status_filter == 'resolved' ? 'active' : ''; ?>" href="?status=resolved">
                Resolved
            </a>
        </li>
    </ul>

    <!-- Requests -->
    <div class="card">
        <div class="card-body p-0">
            <?php if ($requests && $requests->num_rows > 0): ?>
                <div class="list-group list-group-flush">
                    <?php while ($r = $requests->fetch_assoc()): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="badge badge-<?php 
                                            echo $r['priority'] == 'urgent' ? 'danger' : 
                                                ($r['priority'] == 'high' ? 'warning' : 
                                                ($r['priority'] == 'medium' ? 'info' : 'secondary')); 
                                        ?> mr-2">
                                            <?php echo ucfirst($r['priority']); ?>
                                        </span>
                                        <span class="badge badge-<?php 
                                            echo $r['status'] == 'pending' ? 'warning' : 
                                                ($r['status'] == 'approved' ? 'success' : 
                                                ($r['status'] == 'rejected' ? 'danger' : 'info')); 
                                        ?> mr-2">
                                            <?php echo ucfirst($r['status']); ?>
                                        </span>
                                        <small class="text-muted">
                                            <i class="far fa-clock"></i> <?php echo timeAgo($r['created_at']); ?>
                                        </small>
                                    </div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($r['subject']); ?></h6>
                                    <p class="mb-1 text-muted small"><?php echo htmlspecialchars($r['message']); ?></p>
                                    <small class="text-muted">
                                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($r['employee_name']); ?>
                                        <?php if ($r['training_title']): ?>
                                            <span class="mx-2">•</span>
                                            <i class="fas fa-book"></i> <?php echo htmlspecialchars($r['training_title']); ?>
                                        <?php endif; ?>
                                    </small>
                                    
                                    <?php if ($r['admin_response']): ?>
                                        <div class="alert alert-info mt-2 mb-0">
                                            <strong>Your Response:</strong>
                                            <p class="mb-0 small"><?php echo nl2br(htmlspecialchars($r['admin_response'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="ml-3">
                                    <?php if ($r['status'] == 'pending'): ?>
                                        <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#respondModal<?php echo $r['id']; ?>">
                                            <i class="fas fa-reply"></i> Respond
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Response Modal -->
                        <div class="modal fade" id="respondModal<?php echo $r['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Respond to Request</h5>
                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="request_id" value="<?php echo $r['id']; ?>">
                                            <p><strong><?php echo htmlspecialchars($r['subject']); ?></strong></p>
                                            <p class="small text-muted"><?php echo htmlspecialchars($r['message']); ?></p>
                                            <hr>
                                            <div class="form-group">
                                                <label>Your Response</label>
                                                <textarea name="response" class="form-control" rows="4" required></textarea>
                                            </div>
                                            <div class="form-group">
                                                <label>Status</label>
                                                <select name="status" class="form-control">
                                                    <option value="approved">Approve</option>
                                                    <option value="rejected">Reject</option>
                                                    <option value="resolved">Resolved</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                            <button type="submit" name="respond" class="btn btn-primary">Submit Response</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center text-muted py-5">
                    <i class="fas fa-inbox fa-4x mb-3 d-block" style="opacity: 0.3;"></i>
                    <h4>No Requests</h4>
                    <p>No requests found for this filter.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>