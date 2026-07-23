<?php
// admin/backup.php - Backup Management System
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

$message = '';
$message_type = '';
$backup_dir = '../backups/';

// Create backup directory if it doesn't exist
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0777, true);
}

// Get list of backup files
$backup_files = [];
$files = scandir($backup_dir);
foreach ($files as $file) {
    if ($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) == 'sql') {
        $file_path = $backup_dir . $file;
        $backup_files[] = [
            'name' => $file,
            'size' => filesize($file_path),
            'modified' => filemtime($file_path),
            'path' => $file_path
        ];
    }
}

// Sort by modified date (newest first)
usort($backup_files, function($a, $b) {
    return $b['modified'] - $a['modified'];
});

// Handle actions
$action = isset($_GET['action']) ? $_GET['action'] : '';
$file = isset($_GET['file']) ? $_GET['file'] : '';

if ($action == 'create') {
    // Create backup
    $backup_name = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    $backup_path = $backup_dir . $backup_name;
    
    // Get all tables
    $tables = [];
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
    
    // Create backup content
    $backup_content = "-- Staff Training System Database Backup\n";
    $backup_content .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $backup_content .= "-- Database: " . DB_NAME . "\n";
    $backup_content .= "-- Tables: " . count($tables) . "\n\n";
    $backup_content .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
    
    foreach ($tables as $table) {
        // Get table structure
        $result = $conn->query("SHOW CREATE TABLE $table");
        $row = $result->fetch_row();
        $backup_content .= "DROP TABLE IF EXISTS `$table`;\n";
        $backup_content .= $row[1] . ";\n\n";
        
        // Get table data
        $result = $conn->query("SELECT * FROM $table");
        if ($result->num_rows > 0) {
            $columns = array_keys($result->fetch_assoc());
            $result = $conn->query("SELECT * FROM $table");
            
            while ($row = $result->fetch_assoc()) {
                $values = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $values[] = 'NULL';
                    } else {
                        $values[] = "'" . addslashes($value) . "'";
                    }
                }
                $backup_content .= "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n";
            }
            $backup_content .= "\n";
        }
    }
    
    $backup_content .= "SET FOREIGN_KEY_CHECKS=1;\n";
    
    // Save backup file
    if (file_put_contents($backup_path, $backup_content)) {
        logAction($_SESSION['user_id'], 'backup_created', ['backup_file' => $backup_name]);
        $message = "Backup created successfully!";
        $message_type = 'success';
        
        // Redirect to refresh page
        header('Location: backup.php?created=success');
        exit();
    } else {
        $message = "Failed to create backup. Please check directory permissions.";
        $message_type = 'danger';
    }
}

if ($action == 'download' && $file) {
    $file_path = $backup_dir . $file;
    if (file_exists($file_path)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . filesize($file_path));
        readfile($file_path);
        logAction($_SESSION['user_id'], 'backup_downloaded', ['backup_file' => $file]);
        exit();
    } else {
        $message = "Backup file not found.";
        $message_type = 'danger';
    }
}

if ($action == 'restore' && $file) {
    $file_path = $backup_dir . $file;
    if (file_exists($file_path)) {
        // Read the backup file
        $sql_content = file_get_contents($file_path);
        
        // Split SQL into individual statements
        $statements = explode(";\n", $sql_content);
        
        $success_count = 0;
        $error_count = 0;
        $errors = [];
        
        // Disable foreign key checks
        $conn->query("SET FOREIGN_KEY_CHECKS=0");
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement) && !str_starts_with($statement, '--')) {
                if ($conn->query($statement)) {
                    $success_count++;
                } else {
                    $error_count++;
                    $errors[] = $conn->error;
                }
            }
        }
        
        // Enable foreign key checks
        $conn->query("SET FOREIGN_KEY_CHECKS=1");
        
        logAction($_SESSION['user_id'], 'backup_restored', [
            'backup_file' => $file,
            'success_count' => $success_count,
            'error_count' => $error_count
        ]);
        
        $message = "Restore completed! $success_count statements executed successfully.";
        if ($error_count > 0) {
            $message .= " $error_count errors occurred.";
            $message_type = 'warning';
        } else {
            $message_type = 'success';
        }
    } else {
        $message = "Backup file not found.";
        $message_type = 'danger';
    }
}

if ($action == 'delete' && $file) {
    $file_path = $backup_dir . $file;
    if (file_exists($file_path)) {
        if (unlink($file_path)) {
            logAction($_SESSION['user_id'], 'backup_deleted', ['backup_file' => $file]);
            $message = "Backup file deleted successfully!";
            $message_type = 'success';
            header('Location: backup.php?deleted=success');
            exit();
        } else {
            $message = "Failed to delete backup file.";
            $message_type = 'danger';
        }
    } else {
        $message = "Backup file not found.";
        $message_type = 'danger';
    }
}

// Get backup statistics
$total_backups = count($backup_files);
$total_size = array_sum(array_column($backup_files, 'size'));
$latest_backup = !empty($backup_files) ? $backup_files[0] : null;

$page_title = 'Backup Management';
$page_scripts = '
<script>
$(document).ready(function() {
    // Initialize DataTable
    if ($.fn.DataTable) {
        $("#backupTable").DataTable({
            responsive: true,
            pageLength: 25,
            ordering: true,
            searching: true,
            lengthChange: true,
            info: true,
            paging: true,
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search backups...",
                lengthMenu: "_MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ backups",
                infoEmpty: "No backups found",
                infoFiltered: "(filtered from _MAX_ total backups)"
            }
        });
    }
});

function restoreBackup(filename) {
    Swal.fire({
        title: "Restore Backup?",
        text: "This will restore the database from this backup. All current data will be replaced! This action cannot be undone.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Yes, restore it!",
        cancelButtonText: "Cancel",
        html: `<div class="text-left">
            <p><strong>File:</strong> ${filename}</p>
            <p class="text-danger"><i class="fas fa-exclamation-triangle"></i> This will overwrite all current data!</p>
        </div>`
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "backup.php?action=restore&file=" + encodeURIComponent(filename);
        }
    });
}

function deleteBackup(filename) {
    Swal.fire({
        title: "Delete Backup?",
        text: "Are you sure you want to delete this backup file?",
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Yes, delete it!",
        cancelButtonText: "Cancel"
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "backup.php?action=delete&file=" + encodeURIComponent(filename);
        }
    });
}

function createBackup() {
    Swal.fire({
        title: "Create Backup?",
        text: "This will create a complete database backup. This may take a few moments.",
        icon: "info",
        showCancelButton: true,
        confirmButtonColor: "#28a745",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Yes, create backup!",
        cancelButtonText: "Cancel",
        html: `<div class="text-left">
            <p><i class="fas fa-database"></i> <strong>Database:</strong> ${DB_NAME}</p>
            <p><i class="fas fa-table"></i> <strong>Tables:</strong> All tables will be backed up</p>
        </div>`
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "backup.php?action=create";
        }
    });
}

function downloadBackup(filename) {
    window.location.href = "backup.php?action=download&file=" + encodeURIComponent(filename);
}

// Format file size
function formatFileSize(bytes) {
    if (bytes === 0) return "0 Bytes";
    const k = 1024;
    const sizes = ["Bytes", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
}
</script>
';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<style>
/* Backup Page Styles */
.backup-stats {
    margin-bottom: 25px;
}
.stat-card {
    padding: 20px;
    border-radius: 10px;
    background: white;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    transition: all 0.3s ease;
    height: 100%;
    text-align: center;
}
.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.2);
}
.stat-card .stat-icon {
    font-size: 2.5rem;
    margin-bottom: 10px;
}
.stat-card .stat-number {
    font-size: 1.8rem;
    font-weight: 800;
}
.stat-card .stat-label {
    color: #6c757d;
    font-size: 0.85rem;
    font-weight: 500;
}
.stat-card.primary .stat-icon { color: #667eea; }
.stat-card.success .stat-icon { color: #48bb78; }
.stat-card.warning .stat-icon { color: #f6c23e; }
.stat-card.info .stat-icon { color: #36b9cc; }

.backup-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.backup-actions .btn {
    padding: 10px 25px;
    border-radius: 8px;
    font-weight: 600;
}
.backup-actions .btn i {
    margin-right: 8px;
}

.table th {
    font-weight: 600;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.table td {
    vertical-align: middle;
}
.file-size {
    font-weight: 600;
    color: #495057;
}
.backup-date {
    font-weight: 500;
}
.backup-date small {
    display: block;
    color: #6c757d;
    font-weight: 400;
}

.action-buttons .btn {
    padding: 4px 10px;
    font-size: 0.75rem;
    margin: 0 2px;
}
.action-buttons .btn i {
    font-size: 0.85rem;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
}
.empty-state i {
    font-size: 4rem;
    color: #cbd5e0;
    margin-bottom: 20px;
}
.empty-state h4 {
    color: #2d3748;
    margin-bottom: 10px;
}
.empty-state p {
    color: #6c757d;
    max-width: 400px;
    margin: 0 auto 20px;
}

.backup-info {
    background: #f8f9fc;
    padding: 15px 20px;
    border-radius: 8px;
    margin-top: 20px;
}
.backup-info .info-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 5px 0;
}
.backup-info .info-item i {
    color: #667eea;
    width: 20px;
}

@media (max-width: 768px) {
    .stat-card .stat-number {
        font-size: 1.4rem;
    }
    .backup-actions .btn {
        width: 100%;
        justify-content: center;
    }
    .action-buttons .btn {
        padding: 4px 8px;
        font-size: 0.7rem;
    }
}
</style>

<div class="main-content">
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <div>
                <h1><i class="fas fa-database text-primary"></i> Backup Management</h1>
                <p class="text-muted">Create, restore, and manage database backups</p>
            </div>
            <div class="backup-actions">
                <button onclick="createBackup()" class="btn btn-success">
                    <i class="fas fa-plus-circle"></i> Create Backup
                </button>
                <button onclick="window.location.reload()" class="btn btn-secondary">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_GET['created']) && $_GET['created'] == 'success'): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> Backup created successfully!
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 'success'): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> Backup file deleted successfully!
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
            <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : ($message_type == 'warning' ? 'exclamation-triangle' : 'exclamation-circle'); ?>"></i>
            <?php echo $message; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="row g-3 mb-4 backup-stats">
        <div class="col-md-3 col-6">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="fas fa-file-archive"></i>
                </div>
                <div class="stat-number"><?php echo $total_backups; ?></div>
                <div class="stat-label">Total Backups</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="fas fa-database"></i>
                </div>
                <div class="stat-number"><?php echo formatFileSize($total_size); ?></div>
                <div class="stat-label">Total Size</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number">
                    <?php echo $latest_backup ? date('M d, Y', $latest_backup['modified']) : 'N/A'; ?>
                </div>
                <div class="stat-label">Latest Backup</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="fas fa-hdd"></i>
                </div>
                <div class="stat-number">
                    <?php 
                    $free_space = disk_free_space($backup_dir);
                    echo $free_space ? formatFileSize($free_space) : 'N/A';
                    ?>
                </div>
                <div class="stat-label">Free Space</div>
            </div>
        </div>
    </div>

    <!-- Backup Info -->
    <div class="backup-info">
        <div class="row">
            <div class="col-md-4">
                <div class="info-item">
                    <i class="fas fa-folder"></i>
                    <span><strong>Backup Location:</strong> <?php echo realpath($backup_dir); ?></span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-item">
                    <i class="fas fa-table"></i>
                    <span><strong>Database:</strong> <?php echo DB_NAME; ?></span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-item">
                    <i class="fas fa-shield-alt"></i>
                    <span><strong>Status:</strong> <span class="text-success">● Read/Write</span></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Backup Files Table -->
    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-list"></i> Backup Files</h5>
            <?php if ($total_backups > 0): ?>
                <span class="badge badge-primary"><?php echo $total_backups; ?> files</span>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <?php if ($total_backups > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="backupTable">
                        <thead>
                            <tr>
                                <th>File Name</th>
                                <th>Size</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backup_files as $backup): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-file-archive text-primary mr-2"></i>
                                            <div>
                                                <strong><?php echo htmlspecialchars($backup['name']); ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <i class="far fa-clock"></i> 
                                                    <?php echo date('H:i:s', $backup['modified']); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="file-size"><?php echo formatFileSize($backup['size']); ?></span>
                                    </td>
                                    <td>
                                        <div class="backup-date">
                                            <?php echo date('M d, Y', $backup['modified']); ?>
                                            <small><?php echo timeAgo(date('Y-m-d H:i:s', $backup['modified'])); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm action-buttons">
                                            <button onclick="downloadBackup('<?php echo htmlspecialchars($backup['name']); ?>')" 
                                                    class="btn btn-outline-success" title="Download">
                                                <i class="fas fa-download"></i>
                                            </button>
                                            <button onclick="restoreBackup('<?php echo htmlspecialchars($backup['name']); ?>')" 
                                                    class="btn btn-outline-warning" title="Restore">
                                                <i class="fas fa-undo-alt"></i>
                                            </button>
                                            <button onclick="deleteBackup('<?php echo htmlspecialchars($backup['name']); ?>')" 
                                                    class="btn btn-outline-danger" title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-database"></i>
                    <h4>No Backups Found</h4>
                    <p>You haven't created any database backups yet. Click the "Create Backup" button to create your first backup.</p>
                    <button onclick="createBackup()" class="btn btn-success btn-lg">
                        <i class="fas fa-plus-circle"></i> Create First Backup
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Backup Tips -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-lightbulb text-warning"></i> Backup Tips</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="d-flex mb-3">
                        <i class="fas fa-check-circle text-success mr-2 mt-1"></i>
                        <div>
                            <strong>Regular Backups</strong>
                            <p class="text-muted small mb-0">Create backups regularly to ensure you have recent data available for recovery.</p>
                        </div>
                    </div>
                    <div class="d-flex mb-3">
                        <i class="fas fa-check-circle text-success mr-2 mt-1"></i>
                        <div>
                            <strong>Before Major Changes</strong>
                            <p class="text-muted small mb-0">Always create a backup before making significant changes to the system.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex mb-3">
                        <i class="fas fa-check-circle text-success mr-2 mt-1"></i>
                        <div>
                            <strong>Download Backups</strong>
                            <p class="text-muted small mb-0">Download important backups to your local machine for additional safety.</p>
                        </div>
                    </div>
                    <div class="d-flex mb-3">
                        <i class="fas fa-check-circle text-success mr-2 mt-1"></i>
                        <div>
                            <strong>Test Restores</strong>
                            <p class="text-muted small mb-0">Periodically test restoring backups to ensure they are working correctly.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Format file size function for JavaScript
function formatFileSize(bytes) {
    if (bytes === 0) return "0 Bytes";
    const k = 1024;
    const sizes = ["Bytes", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
}
</script>

<?php require_once 'includes/footer.php'; ?>