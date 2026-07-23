<?php
// admin/includes/navbar.php - Admin Navigation Bar

// Get user information
$user_id = $_SESSION['user_id'] ?? 0;
$employee_id = $_SESSION['employee_id'] ?? 0;
$username = $_SESSION['username'] ?? 'Admin';

// Get employee details
$employee = getEmployeeById($employee_id);
$full_name = $employee ? $employee['first_name'] . ' ' . $employee['last_name'] : 'Administrator';
$profile_pic = $employee && !empty($employee['profile_picture']) ? '../uploads/profile-pictures/' . $employee['profile_picture'] : '../assets/images/default-avatar.png';

// Get unread notification count
$notif_count = 0;
if (isset($conn)) {
    $result = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE user_id = $user_id AND is_read = 0");
    if ($result) {
        $notif_count = $result->fetch_assoc()['count'];
    }
}

// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Top Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top" id="mainNav">
    <div class="container-fluid">
        <!-- Left Side -->
        <div class="d-flex align-items-center">
            <!-- Sidebar Toggle (Mobile) -->
            <button class="btn btn-link text-dark d-lg-none p-0 mr-3" id="sidebarToggle" title="Toggle Sidebar">
                <i class="fas fa-bars fa-lg"></i>
            </button>
            
            <!-- Brand (Mobile) -->
            <a class="navbar-brand d-lg-none" href="dashboard.php">
                <i class="fas fa-graduation-cap text-primary"></i>
                <span class="font-weight-bold">ST</span>
            </a>
            
            <!-- Breadcrumb (Desktop) -->
            <nav aria-label="breadcrumb" class="d-none d-md-block">
                <ol class="breadcrumb bg-transparent p-0 m-0">
                    <li class="breadcrumb-item">
                        <a href="dashboard.php" class="text-muted">
                            <i class="fas fa-home"></i>
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?php 
                            $page_name = str_replace(['.php', '_'], ['', ' '], $current_page);
                            echo ucwords($page_name);
                        ?>
                    </li>
                </ol>
            </nav>
        </div>
        
        <!-- Center: Search Bar -->
        <div class="d-none d-md-block flex-grow-1 mx-4" style="max-width: 500px;">
            <div class="search-wrapper">
                <i class="fas fa-search text-muted"></i>
                <input type="text" class="form-control form-control-sm" 
                       placeholder="Search employees, trainings, certifications..." 
                       id="globalSearch">
                <kbd class="search-shortcut">Ctrl+K</kbd>
            </div>
        </div>
        
        <!-- Right Side -->
        <div class="d-flex align-items-center">
            <!-- Quick Actions Dropdown -->
            <div class="dropdown mr-2 d-none d-sm-block">
                <button class="btn btn-link text-dark p-0" id="quickActions" data-toggle="dropdown" title="Quick Actions">
                    <i class="fas fa-plus-circle fa-lg"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-right quick-actions-dropdown" aria-labelledby="quickActions">
                    <h6 class="dropdown-header">Quick Actions</h6>
                    <a href="add-employee.php" class="dropdown-item">
                        <i class="fas fa-user-plus text-primary"></i> Add Employee
                    </a>
                    <a href="add-training.php" class="dropdown-item">
                        <i class="fas fa-chalkboard-teacher text-success"></i> Add Training
                    </a>
                    <a href="add-certification.php" class="dropdown-item">
                        <i class="fas fa-certificate text-warning"></i> Issue Certification
                    </a>
                    <a href="add-development-plan.php" class="dropdown-item">
                        <i class="fas fa-tasks text-info"></i> Create Development Plan
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="reports.php" class="dropdown-item">
                        <i class="fas fa-file-alt text-danger"></i> Generate Report
                    </a>
                </div>
            </div>
            
            <!-- Notification Bell -->
            <div class="dropdown mr-2">
                <button class="btn btn-link text-dark position-relative p-0" id="notificationDropdown" data-toggle="dropdown" title="Notifications">
                    <i class="fas fa-bell fa-lg"></i>
                    <?php if ($notif_count > 0): ?>
                        <span class="badge badge-danger notification-badge"><?php echo $notif_count; ?></span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-right notification-dropdown" aria-labelledby="notificationDropdown">
                    <div class="dropdown-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Notifications</h6>
                        <?php if ($notif_count > 0): ?>
                            <a href="mark-all-read.php" class="text-primary small">Mark all read</a>
                        <?php endif; ?>
                    </div>
                    <div id="notificationList">
                        <!-- Notifications will be loaded via AJAX -->
                        <div class="text-center text-muted py-3">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                            <p class="mt-2 mb-0">Loading notifications...</p>
                        </div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="notifications.php" class="dropdown-item text-center text-primary">
                        <i class="fas fa-bell"></i> View All Notifications
                    </a>
                </div>
            </div>
            
            <!-- User Dropdown -->
            <div class="dropdown">
                <button class="btn btn-link text-dark dropdown-toggle d-flex align-items-center p-0" id="userDropdown" data-toggle="dropdown">
                    <img src="<?php echo $profile_pic; ?>" alt="Profile" class="rounded-circle user-avatar" width="38" height="38">
                    <span class="ml-2 d-none d-sm-inline font-weight-medium"><?php echo htmlspecialchars($full_name); ?></span>
                    <i class="fas fa-chevron-down ml-1 d-none d-sm-inline" style="font-size: 0.7rem; opacity: 0.5;"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-right user-dropdown" aria-labelledby="userDropdown">
                    <div class="dropdown-header">
                        <div class="d-flex align-items-center">
                            <img src="<?php echo $profile_pic; ?>" alt="Profile" class="rounded-circle" width="50" height="50">
                            <div class="ml-3">
                                <h6 class="mb-0"><?php echo htmlspecialchars($full_name); ?></h6>
                                <small class="text-muted">@<?php echo htmlspecialchars($username); ?></small>
                                <div>
                                    <span class="badge badge-success badge-sm">Admin</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="profile.php" class="dropdown-item">
                        <i class="fas fa-user-circle"></i> My Profile
                        <span class="badge badge-info float-right">Edit</span>
                    </a>
                    <a href="settings.php" class="dropdown-item">
                        <i class="fas fa-cog"></i> System Settings
                    </a>
                    <a href="change-password.php" class="dropdown-item">
                        <i class="fas fa-key"></i> Change Password
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="help.php" class="dropdown-item">
                        <i class="fas fa-question-circle text-info"></i> Help &amp; Support
                    </a>
                    <a href="../logout.php" class="dropdown-item text-danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>

<style>
/* ===== Navbar Styles ===== */
#mainNav {
    height: var(--header-height, 70px);
    padding: 0 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.03);
    z-index: 999;
    background: rgba(255,255,255,0.95) !important;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

/* Breadcrumb */
.breadcrumb {
    font-size: 0.85rem;
}
.breadcrumb-item a {
    color: #6c757d;
    text-decoration: none;
    transition: color 0.2s ease;
}
.breadcrumb-item a:hover {
    color: #667eea;
}
.breadcrumb-item.active {
    color: #2d3748;
    font-weight: 600;
}
.breadcrumb-item + .breadcrumb-item::before {
    content: "›";
    font-size: 1.2rem;
    color: #cbd5e0;
}

/* Search Wrapper */
.search-wrapper {
    position: relative;
    width: 100%;
}
.search-wrapper i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    z-index: 1;
    color: #a0aec0;
}
.search-wrapper input {
    padding-left: 40px;
    padding-right: 60px;
    height: 40px;
    border-radius: 50px;
    border: 1.5px solid #e2e8f0;
    background: #f7fafc;
    transition: all 0.3s ease;
    font-size: 0.9rem;
    width: 100%;
}
.search-wrapper input:focus {
    background: white;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    outline: none;
}
.search-wrapper input::placeholder {
    color: #a0aec0;
}
.search-shortcut {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    padding: 2px 10px;
    background: #edf2f7;
    border-radius: 4px;
    font-size: 0.7rem;
    color: #718096;
    border: 1px solid #e2e8f0;
    font-weight: 600;
}

/* Notification Badge */
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

/* Notification Dropdown */
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
.notification-dropdown .dropdown-header h6 {
    font-weight: 700;
    color: #2d3748;
}
.notification-dropdown .dropdown-header a {
    font-size: 0.8rem;
}
#notificationList {
    max-height: 350px;
    overflow-y: auto;
}
#notificationList::-webkit-scrollbar {
    width: 4px;
}
#notificationList::-webkit-scrollbar-track {
    background: transparent;
}
#notificationList::-webkit-scrollbar-thumb {
    background: #cbd5e0;
    border-radius: 10px;
}

/* Notification Items */
.notification-item {
    padding: 12px 20px;
    border-bottom: 1px solid #f1f3f5;
    transition: all 0.2s ease;
    cursor: pointer;
}
.notification-item:hover {
    background: #f8fafc;
}
.notification-item.unread {
    background: #f0f4ff;
    border-left: 3px solid #667eea;
}
.notification-item:last-child {
    border-bottom: none;
}
.notification-item .notif-icon {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 0.9rem;
}
.notification-item .notif-icon.info {
    background: #e3f2fd;
    color: #1976d2;
}
.notification-item .notif-icon.success {
    background: #e8f5e9;
    color: #388e3c;
}
.notification-item .notif-icon.warning {
    background: #fff3e0;
    color: #f57c00;
}
.notification-item .notif-icon.error {
    background: #ffebee;
    color: #d32f2f;
}
.notification-item .notif-content {
    flex: 1;
    min-width: 0;
}
.notification-item .notif-content p {
    margin: 0;
    font-size: 0.85rem;
    color: #2d3748;
    line-height: 1.4;
}
.notification-item .notif-content small {
    color: #a0aec0;
    font-size: 0.7rem;
}
.notification-item .notif-time {
    font-size: 0.65rem;
    color: #a0aec0;
    white-space: nowrap;
    margin-left: 10px;
}

/* Quick Actions Dropdown */
.quick-actions-dropdown {
    min-width: 240px;
    padding: 0;
    border: none;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.12);
}
.quick-actions-dropdown .dropdown-header {
    padding: 12px 20px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    font-weight: 700;
    color: #2d3748;
}
.quick-actions-dropdown .dropdown-item {
    padding: 10px 20px;
    font-size: 0.85rem;
    transition: all 0.2s ease;
}
.quick-actions-dropdown .dropdown-item i {
    width: 20px;
    margin-right: 10px;
    text-align: center;
}
.quick-actions-dropdown .dropdown-item:hover {
    background: #f8fafc;
}

/* User Dropdown */
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
.user-dropdown .dropdown-item {
    padding: 10px 20px;
    font-size: 0.85rem;
    transition: all 0.2s ease;
}
.user-dropdown .dropdown-item i {
    width: 20px;
    margin-right: 12px;
    text-align: center;
    font-size: 0.9rem;
}
.user-dropdown .dropdown-item .badge {
    font-size: 0.65rem;
    padding: 2px 8px;
}
.user-dropdown .dropdown-item:hover {
    background: #f8fafc;
}
.user-dropdown .dropdown-item.text-danger:hover {
    background: #fff5f5;
}

/* User Avatar */
.user-avatar {
    object-fit: cover;
    border: 2px solid #e2e8f0;
    transition: border-color 0.2s ease;
}
.user-avatar:hover {
    border-color: #667eea;
}

/* Button overrides */
#mainNav .btn-link {
    color: #4a5568;
    text-decoration: none;
    transition: color 0.2s ease;
    position: relative;
}
#mainNav .btn-link:hover {
    color: #667eea;
}
#mainNav .btn-link:focus {
    outline: none;
    box-shadow: none;
}

/* Badge size */
.badge-sm {
    font-size: 0.65rem;
    padding: 2px 8px;
}

/* ===== Responsive ===== */
@media (max-width: 992px) {
    #mainNav {
        padding: 0 15px;
        height: 60px;
    }
    .search-wrapper input {
        font-size: 0.85rem;
        height: 36px;
    }
    .search-shortcut {
        display: none;
    }
    .notification-dropdown {
        min-width: 320px;
        right: -20px !important;
    }
    .user-dropdown {
        min-width: 250px;
    }
}

@media (max-width: 768px) {
    #mainNav {
        height: 56px;
        padding: 0 12px;
    }
    .notification-dropdown {
        min-width: 300px;
        right: -10px !important;
    }
    .notification-dropdown .dropdown-header {
        padding: 12px 15px;
    }
    .notification-item {
        padding: 10px 15px;
    }
    .user-dropdown {
        min-width: 240px;
    }
    .user-dropdown .dropdown-header {
        padding: 15px;
    }
    .quick-actions-dropdown {
        min-width: 200px;
    }
    #sidebarToggle {
        padding: 0;
        font-size: 1.1rem;
    }
}

@media (max-width: 576px) {
    #mainNav {
        height: 50px;
        padding: 0 10px;
    }
    .notification-dropdown {
        min-width: 280px;
        max-height: 400px;
        right: -5px !important;
        left: -5px !important;
        width: auto !important;
        border-radius: 10px;
    }
    .user-dropdown {
        min-width: 220px;
        right: -5px !important;
    }
    .user-avatar {
        width: 32px;
        height: 32px;
    }
    .user-dropdown .dropdown-header img {
        width: 40px;
        height: 40px;
    }
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
    #mainNav {
        background: rgba(26, 26, 46, 0.95) !important;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    #mainNav .btn-link {
        color: #e2e8f0;
    }
    #mainNav .btn-link:hover {
        color: #667eea;
    }
    .search-wrapper input {
        background: #2d2d44;
        border-color: #3d3d5c;
        color: #e2e8f0;
    }
    .search-wrapper input::placeholder {
        color: #718096;
    }
    .search-wrapper input:focus {
        background: #2d2d44;
        border-color: #667eea;
    }
    .search-shortcut {
        background: #3d3d5c;
        color: #a0aec0;
        border-color: #4d4d6c;
    }
    .breadcrumb-item a {
        color: #a0aec0;
    }
    .breadcrumb-item.active {
        color: #e2e8f0;
    }
    .breadcrumb-item + .breadcrumb-item::before {
        color: #4a4a6a;
    }
    .notification-dropdown {
        background: #1a1a2e;
    }
    .notification-dropdown .dropdown-header {
        background: #2d2d44;
        border-color: #3d3d5c;
    }
    .notification-dropdown .dropdown-header h6 {
        color: #e2e8f0;
    }
    .notification-item {
        border-color: #2d2d44;
    }
    .notification-item:hover {
        background: #2d2d44;
    }
    .notification-item.unread {
        background: rgba(102, 126, 234, 0.1);
    }
    .notification-item .notif-content p {
        color: #e2e8f0;
    }
    .user-dropdown {
        background: #1a1a2e;
    }
    .user-dropdown .dropdown-header {
        background: #2d2d44;
    }
    .user-dropdown .dropdown-item {
        color: #e2e8f0;
    }
    .user-dropdown .dropdown-item:hover {
        background: #2d2d44;
    }
    .user-dropdown .dropdown-item.text-danger:hover {
        background: rgba(231, 74, 59, 0.1);
    }
    .quick-actions-dropdown {
        background: #1a1a2e;
    }
    .quick-actions-dropdown .dropdown-header {
        background: #2d2d44;
        border-color: #3d3d5c;
        color: #e2e8f0;
    }
    .quick-actions-dropdown .dropdown-item {
        color: #e2e8f0;
    }
    .quick-actions-dropdown .dropdown-item:hover {
        background: #2d2d44;
    }
}
</style>

<script>
$(document).ready(function() {
    // ===== Load Notifications =====
    loadNotifications();
    
    // ===== Global Search =====
    $('#globalSearch').on('keyup', function(e) {
        if (e.key === 'Enter') {
            const query = $(this).val().trim();
            if (query.length > 0) {
                window.location.href = 'search.php?q=' + encodeURIComponent(query);
            }
        }
    });
    
    // ===== Keyboard Shortcut: Ctrl+K for Search =====
    $(document).on('keydown', function(e) {
        // Ctrl+K or Cmd+K
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            $('#globalSearch').focus();
        }
        // Escape to clear search
        if (e.key === 'Escape' && $('#globalSearch').is(':focus')) {
            $('#globalSearch').val('').blur();
        }
    });
    
    // ===== Auto-refresh notifications every 60 seconds =====
    setInterval(function() {
        loadNotifications();
    }, 60000);
});

// ===== Load Notifications Function =====
function loadNotifications() {
    $.ajax({
        url: '../api/get-notifications.php',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            let html = '';
            if (data && data.length > 0) {
                data.forEach(function(notif) {
                    const iconClass = getNotificationIcon(notif.type);
                    const iconName = getNotificationIconName(notif.type);
                    const isUnread = !notif.is_read;
                    
                    html += `
                        <div class="notification-item ${isUnread ? 'unread' : ''}" data-id="${notif.id}">
                            <div class="d-flex align-items-start">
                                <div class="notif-icon ${iconClass}">
                                    <i class="fas fa-${iconName}"></i>
                                </div>
                                <div class="notif-content">
                                    <p>${notif.message}</p>
                                    <small><i class="far fa-clock"></i> ${timeAgo(notif.created_at)}</small>
                                </div>
                                ${isUnread ? '<span class="notif-time"><span class="badge badge-primary">New</span></span>' : ''}
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
            
            // ===== Mark notification as read on click =====
            $('.notification-item.unread').on('click', function(e) {
                e.stopPropagation();
                const id = $(this).data('id');
                const $item = $(this);
                
                $.ajax({
                    url: '../api/mark-notification-read.php',
                    method: 'POST',
                    data: { id: id },
                    success: function() {
                        $item.removeClass('unread');
                        $item.find('.notif-time').remove();
                        
                        // Update badge count
                        const badge = $('.notification-badge');
                        if (badge.length) {
                            const count = parseInt(badge.text()) - 1;
                            if (count > 0) {
                                badge.text(count);
                            } else {
                                badge.remove();
                            }
                        }
                    }
                });
            });
        },
        error: function() {
            $('#notificationList').html(`
                <div class="text-center text-muted py-3">
                    <i class="fas fa-exclamation-circle"></i>
                    <p class="mb-0">Could not load notifications</p>
                </div>
            `);
        }
    });
}

// ===== Helper Functions =====
function getNotificationIcon(type) {
    const icons = {
        'info': 'info',
        'success': 'success',
        'warning': 'warning',
        'error': 'error'
    };
    return icons[type] || 'info';
}

function getNotificationIconName(type) {
    const icons = {
        'info': 'info-circle',
        'success': 'check-circle',
        'warning': 'exclamation-triangle',
        'error': 'times-circle'
    };
    return icons[type] || 'bell';
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