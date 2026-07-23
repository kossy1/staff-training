<?php
// admin/settings.php - System Settings
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

$settings = [
    'site_name' => SITE_NAME,
    'site_url' => SITE_URL,
    'items_per_page' => 10,
    'max_file_size' => 5,
    'allowed_extensions' => 'jpg,jpeg,png,gif,pdf,doc,docx',
    'maintenance_mode' => false,
    'registration_enabled' => true,
    'email_notifications' => true
];

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // In a real application, save settings to database
    // For now, just show success message
    $success = true;
    logAction($_SESSION['user_id'], 'settings_updated');
}

$page_title = 'Settings';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h1><i class="fas fa-cog text-primary"></i> System Settings</h1>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> Settings saved successfully!
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-sliders-h"></i> General Settings</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label>Site Name</label>
                            <input type="text" name="site_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['site_name']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Site URL</label>
                            <input type="url" name="site_url" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['site_url']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Items Per Page</label>
                            <input type="number" name="items_per_page" class="form-control" 
                                   value="<?php echo $settings['items_per_page']; ?>" min="5" max="100">
                        </div>
                        <div class="form-group">
                            <label>Max File Size (MB)</label>
                            <input type="number" name="max_file_size" class="form-control" 
                                   value="<?php echo $settings['max_file_size']; ?>" min="1" max="20">
                        </div>
                        <div class="form-group">
                            <label>Allowed File Extensions</label>
                            <input type="text" name="allowed_extensions" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['allowed_extensions']); ?>">
                            <small class="text-muted">Comma separated list</small>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-toggle-on"></i> System Status</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>PHP Version</span>
                        <span class="badge badge-success"><?php echo phpversion(); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>MySQL Version</span>
                        <span class="badge badge-success"><?php echo $conn->server_info; ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Maintenance Mode</span>
                        <span class="badge badge-<?php echo $settings['maintenance_mode'] ? 'danger' : 'success'; ?>">
                            <?php echo $settings['maintenance_mode'] ? 'Enabled' : 'Disabled'; ?>
                        </span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Registration</span>
                        <span class="badge badge-<?php echo $settings['registration_enabled'] ? 'success' : 'danger'; ?>">
                            <?php echo $settings['registration_enabled'] ? 'Enabled' : 'Disabled'; ?>
                        </span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Email Notifications</span>
                        <span class="badge badge-<?php echo $settings['email_notifications'] ? 'success' : 'danger'; ?>">
                            <?php echo $settings['email_notifications'] ? 'Enabled' : 'Disabled'; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>