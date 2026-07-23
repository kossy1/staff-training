<?php
// admin/logs.php - System Activity Logs
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Get filter parameters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$action = isset($_GET['action']) ? sanitizeInput($_GET['action']) : '';
$date_from = isset($_GET['date_from']) ? sanitizeInput($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitizeInput($_GET['date_to']) : '';

// Build query
$query = "SELECT l.*, u.username, u.email 
          FROM system_logs l 
          LEFT JOIN users u ON l.user_id = u.id 
          WHERE 1=1";
$params = [];
$types = "";

if ($search) {
    $query .= " AND (u.username LIKE ? OR u.email LIKE ? OR l.action LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    $types .= "sss";
}

if ($action) {
    $query .= " AND l.action = ?";
    $params[] = $action;
    $types .= "s";
}

if ($date_from) {
    $query .= " AND DATE(l.created_at) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if ($date_to) {
    $query .= " AND DATE(l.created_at) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$query .= " ORDER BY l.created_at DESC";

// Prepare and execute
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$logs = $stmt->get_result();

// Get unique actions for filter
$actions = $conn->query("SELECT DISTINCT action FROM system_logs ORDER BY action");

$page_title = 'Activity Logs';
$page_scripts = '
<script>
$(document).ready(function() {
    $("#logsTable").DataTable({
        responsive: true,
        pageLength: 50,
        order: [[0, "desc"]],
        columnDefs: [
            { orderable: false, targets: [4] }
        ]
    });
});

function clearLogs() {
    Swal.fire({
        title: "Are you sure?",
        text: "This will permanently delete all activity logs!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Yes, clear all logs!"
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "clear-logs.php";
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
                <h1><i class="fas fa-history text-primary"></i> Activity Logs</h1>
                <p class="text-muted">View all system activities and user actions</p>
            </div>
            <div>
                <button onclick="clearLogs()" class="btn btn-danger">
                    <i class="fas fa-trash-alt"></i> Clear Logs
                </button>
                <a href="export-logs.php" class="btn btn-success">
                    <i class="fas fa-file-export"></i> Export
                </a>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row align-items-end">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Search</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search logs..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Action</label>
                        <select name="action" class="form-control">
                            <option value="">All Actions</option>
                            <?php while ($act = $actions->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($act['action']); ?>" 
                                    <?php echo $action == $act['action'] ? 'selected' : ''; ?>>
                                    <?php echo ucfirst(str_replace('_', ' ', $act['action'])); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Date From</label>
                        <input type="date" name="date_from" class="form-control" 
                               value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Date To</label>
                        <input type="date" name="date_to" class="form-control" 
                               value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="logs.php" class="btn btn-secondary">
                        <i class="fas fa-undo"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="logsTable">
                    <thead>
                        <tr>
                            <th>Date &amp; Time</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>IP Address</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($logs && $logs->num_rows > 0): ?>
                            <?php while ($log = $logs->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo formatDateTime($log['created_at']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo timeAgo($log['created_at']); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($log['username']): ?>
                                            <strong><?php echo htmlspecialchars($log['username']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($log['email']); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">System</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo strpos($log['action'], 'login') !== false ? 'success' : 
                                                (strpos($log['action'], 'delete') !== false ? 'danger' : 
                                                (strpos($log['action'], 'update') !== false ? 'warning' : 
                                                (strpos($log['action'], 'create') !== false ? 'info' : 'secondary')));
                                        ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $log['action'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <code><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></code>
                                    </td>
                                    <td>
                                        <?php if ($log['details']): ?>
                                            <button class="btn btn-sm btn-outline-info" 
                                                    onclick="showDetails('<?php echo htmlspecialchars($log['details']); ?>')">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                    <h5>No logs found</h5>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Log Statistics -->
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Logs</h6>
                    <h2><?php echo number_format($logs->num_rows); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Today's Logs</h6>
                    <h2><?php 
                        $today = $conn->query("SELECT COUNT(*) as count FROM system_logs WHERE DATE(created_at) = CURDATE()");
                        echo number_format($today->fetch_assoc()['count']);
                    ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="card-title">This Week</h6>
                    <h2><?php 
                        $week = $conn->query("SELECT COUNT(*) as count FROM system_logs WHERE YEARWEEK(created_at) = YEARWEEK(CURDATE())");
                        echo number_format($week->fetch_assoc()['count']);
                    ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title">This Month</h6>
                    <h2><?php 
                        $month = $conn->query("SELECT COUNT(*) as count FROM system_logs WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
                        echo number_format($month->fetch_assoc()['count']);
                    ?></h2>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showDetails(details) {
    try {
        const data = JSON.parse(details);
        let html = '<div class="table-responsive"><table class="table table-sm">';
        for (const [key, value] of Object.entries(data)) {
            html += `<tr><th>${key}</th><td>${value}</td></tr>`;
        }
        html += '</table></div>';
        
        Swal.fire({
            title: 'Log Details',
            html: html,
            icon: 'info',
            confirmButtonText: 'Close'
        });
    } catch(e) {
        Swal.fire({
            title: 'Log Details',
            text: details,
            icon: 'info',
            confirmButtonText: 'Close'
        });
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>