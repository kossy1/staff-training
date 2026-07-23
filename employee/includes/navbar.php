<?php
// employee/includes/navbar.php - Employee Navigation Bar

$user_id = $_SESSION['user_id'] ?? 0;
$employee_id = $_SESSION['employee_id'] ?? 0;
$username = $_SESSION['username'] ?? 'Employee';

$employee = getEmployeeById($employee_id);
$full_name = $employee ? $employee['first_name'] . ' ' . $employee['last_name'] : 'Employee';
$profile_pic = $employee && !empty($employee['profile_picture']) ? '../uploads/profile-pictures/' . $employee['profile_picture'] : '../assets/images/default-avatar.png';

$notif_count = 0;
if (isset($conn)) {
    $result = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE user_id = $user_id AND is_read = 0");
    if ($result) {
        $notif_count = $result->fetch_assoc()['count'];
    }
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top" id="mainNav">
    <div class="container-fluid">
        <div class="d-flex align-items-center">
            <button class="btn btn-link text-dark d-lg-none p-0 mr-3" id="sidebarToggle">
                <i class="fas fa-bars fa-lg"></i>
            </button>
            <a class="navbar-brand d-lg-none" href="dashboard.php">
                <i class="fas fa-graduation-cap text-primary"></i>
                <span class="font-weight-bold">ST</span>
            </a>
            <nav aria-label="breadcrumb" class="d-none d-md-block">
                <ol class="breadcrumb bg-transparent p-0 m-0">
                    <li class="breadcrumb-item">
                        <a href="dashboard.php" class="text-muted">
                            <i class="fas fa-home"></i>
                        </a>
                    </li>
                    <li class="breadcrumb-item active">
                        <?php echo ucwords(str_replace(['.php', '_'], ['', ' '], $current_page)); ?>
                    </li>
                </ol>
            </nav>
        </div>
        
        <div class="d-flex align-items-center">
            <!-- Notification Bell -->
            <div class="dropdown mr-3">
                <button class="btn btn-link text-dark position-relative p-0" id="notificationDropdown" data-toggle="dropdown">
                    <i class="fas fa-bell fa-lg"></i>
                    <?php if ($notif_count > 0): ?>
                        <span class="badge badge-danger notification-badge"><?php echo $notif_count; ?></span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-right notification-dropdown">
                    <div class="dropdown-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Notifications</h6>
                        <?php if ($notif_count > 0): ?>
                            <a href="mark-all-read.php" class="text-primary small">Mark all read</a>
                        <?php endif; ?>
                    </div>
                    <div id="notificationList">
                        <div class="text-center text-muted py-3">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                            <p class="mt-2 mb-0">Loading notifications...</p>
                        </div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="notifications.php" class="dropdown-item text-center text-primary">
                        <i class="fas fa-bell"></i> View All
                    </a>
                </div>
            </div>
            
            <!-- User Dropdown -->
            <div class="dropdown">
                <button class="btn btn-link text-dark dropdown-toggle d-flex align-items-center p-0" id="userDropdown" data-toggle="dropdown">
                    <img src="<?php echo $profile_pic; ?>" alt="Profile" class="rounded-circle user-avatar" width="38" height="38">
                    <span class="ml-2 d-none d-sm-inline font-weight-medium"><?php echo htmlspecialchars($full_name); ?></span>
                </button>
                <div class="dropdown-menu dropdown-menu-right user-dropdown">
                    <div class="dropdown-header">
                        <div class="d-flex align-items-center">
                            <img src="<?php echo $profile_pic; ?>" alt="Profile" class="rounded-circle" width="50" height="50">
                            <div class="ml-3">
                                <h6 class="mb-0"><?php echo htmlspecialchars($full_name); ?></h6>
                                <small class="text-muted">@<?php echo htmlspecialchars($username); ?></small>
                                <div><span class="badge badge-info badge-sm">Employee</span></div>
                            </div>
                        </div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="profile.php" class="dropdown-item">
                        <i class="fas fa-user-circle"></i> My Profile
                    </a>
                    <a href="my-trainings.php" class="dropdown-item">
                        <i class="fas fa-chalkboard-teacher"></i> My Trainings
                    </a>
                    <a href="my-certificates.php" class="dropdown-item">
                        <i class="fas fa-certificate"></i> My Certificates
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="../logout.php" class="dropdown-item text-danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>

<style>
#mainNav {
    height: var(--header-height, 70px);
    padding: 0 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    z-index: 999;
    background: rgba(255,255,255,0.95) !important;
    backdrop-filter: blur(10px);
}

.breadcrumb {
    font-size: 0.85rem;
}
.breadcrumb-item a { color: #6c757d; text-decoration: none; }
.breadcrumb-item a:hover { color: #667eea; }
.breadcrumb-item.active { color: #2d3748; font-weight: 600; }
.breadcrumb-item + .breadcrumb-item::before {
    content: "›";
    font-size: 1.2rem;
    color: #cbd5e0;
}

.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    font-size: 9px;
    padding: 3px 6px;
    min-width: 18px;
    height: 18px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: pulse-badge 2s infinite;
    background: #e74a3b;
    border: 2px solid white;
}

@keyframes pulse-badge {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.notification-dropdown {
    min-width: 380px;
    max-height: 500px;
    padding: 0;
    border: none;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.12);
    overflow: hidden;
}
.notification-dropdown .dropdown-header {
    padding: 15px 20px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}
#notificationList {
    max-height: 350px;
    overflow-y: auto;
}
.notification-item {
    padding: 12px 20px;
    border-bottom: 1px solid #f1f3f5;
    transition: all 0.2s ease;
    cursor: pointer;
}
.notification-item:hover { background: #f8fafc; }
.notification-item.unread {
    background: #f0f4ff;
    border-left: 3px solid #667eea;
}
.notification-item .notif-icon {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.notification-item .notif-icon.info { background: #e3f2fd; color: #1976d2; }
.notification-item .notif-icon.success { background: #e8f5e9; color: #388e3c; }
.notification-item .notif-icon.warning { background: #fff3e0; color: #f57c00; }
.notification-item .notif-icon.error { background: #ffebee; color: #d32f2f; }

.user-dropdown {
    min-width: 280px;
    padding: 0;
    border: none;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.12);
}
.user-dropdown .dropdown-header {
    padding: 20px 20px 15px;
    background: #f8fafc;
    border-radius: 12px 12px 0 0;
}
.user-avatar {
    object-fit: cover;
    border: 2px solid #e2e8f0;
}

#mainNav .btn-link {
    color: #4a5568;
    text-decoration: none;
    transition: color 0.2s ease;
}
#mainNav .btn-link:hover { color: #667eea; }
#mainNav .btn-link:focus { outline: none; box-shadow: none; }

@media (max-width: 992px) {
    #mainNav { padding: 0 15px; height: 60px; }
    .notification-dropdown { min-width: 320px; right: -20px !important; }
    .user-dropdown { min-width: 250px; }
}

@media (max-width: 768px) {
    #mainNav { height: 56px; padding: 0 12px; }
    .notification-dropdown { min-width: 300px; right: -10px !important; }
    .user-dropdown { min-width: 240px; }
}

@media (max-width: 576px) {
    #mainNav { height: 50px; padding: 0 10px; }
    .notification-dropdown { min-width: 280px; right: -5px !important; left: -5px !important; }
    .user-dropdown { min-width: 220px; right: -5px !important; }
    .user-avatar { width: 32px; height: 32px; }
}
</style>

<script>
$(document).ready(function() {
    loadNotifications();
    setInterval(loadNotifications, 60000);
});

function loadNotifications() {
    $.ajax({
        url: '../api/get-notifications.php',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            let html = '';
            if (data && data.length > 0) {
                data.forEach(function(notif) {
                    const iconClass = notif.type === 'info' ? 'info' : 
                                     notif.type === 'success' ? 'success' : 
                                     notif.type === 'warning' ? 'warning' : 'error';
                    const iconName = notif.type === 'info' ? 'info-circle' : 
                                     notif.type === 'success' ? 'check-circle' : 
                                     notif.type === 'warning' ? 'exclamation-triangle' : 'times-circle';
                    const isUnread = !notif.is_read;
                    
                    html += `
                        <div class="notification-item ${isUnread ? 'unread' : ''}" data-id="${notif.id}">
                            <div class="d-flex align-items-start">
                                <div class="notif-icon ${iconClass}">
                                    <i class="fas fa-${iconName}"></i>
                                </div>
                                <div class="flex-grow-1 ml-3">
                                    <p class="mb-0 small">${notif.message}</p>
                                    <small class="text-muted"><i class="far fa-clock"></i> ${timeAgo(notif.created_at)}</small>
                                </div>
                                ${isUnread ? '<span class="badge badge-primary">New</span>' : ''}
                            </div>
                        </div>
                    `;
                });
            } else {
                html = `
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-bell-slash fa-2x mb-2 d-block"></i>
                        <p class="mb-0">No notifications</p>
                    </div>
                `;
            }
            $('#notificationList').html(html);
            
            $('.notification-item.unread').on('click', function() {
                const id = $(this).data('id');
                const $item = $(this);
                $.ajax({
                    url: '../api/mark-notification-read.php',
                    method: 'POST',
                    data: { id: id },
                    success: function() {
                        $item.removeClass('unread');
                        $item.find('.badge').remove();
                        const badge = $('.notification-badge');
                        if (badge.length) {
                            const count = parseInt(badge.text()) - 1;
                            if (count > 0) { badge.text(count); } 
                            else { badge.remove(); }
                        }
                    }
                });
            });
        }
    });
}

function timeAgo(datetime) {
    const now = new Date();
    const past = new Date(datetime);
    const diff = Math.floor((now - past) / 1000);
    if (diff < 60) return 'Just now';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
    if (diff < 2592000) return Math.floor(diff / 604800) + 'w ago';
    if (diff < 31536000) return Math.floor(diff / 2592000) + 'mo ago';
    return Math.floor(diff / 31536000) + 'y ago';
}
</script>