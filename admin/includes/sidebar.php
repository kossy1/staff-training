<?php
// admin/includes/sidebar.php - Admin Sidebar (Pure JavaScript - Works Everywhere)
$current_page = basename($_SERVER['PHP_SELF']);

// Get user profile picture
$profile_picture = '';
$default_avatar = '../assets/images/default-avatar.png';

if (isset($_SESSION['employee_id']) && $_SESSION['employee_id'] > 0) {
    $emp_id = (int)$_SESSION['employee_id'];
    $result = $conn->query("SELECT profile_picture FROM employees WHERE id = $emp_id");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (!empty($row['profile_picture'])) {
            $file = '../uploads/profile-pictures/' . $row['profile_picture'];
            if (file_exists($file)) {
                $profile_picture = $file;
            }
        }
    }
}

if (empty($profile_picture)) {
    $profile_picture = $default_avatar;
}
?>

<!-- Sidebar Backdrop -->
<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="closeSidebar();"></div>

<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-brand">
            <i class="fas fa-graduation-cap"></i>
            <span>PolyIbadan SDC</span>
        </a>
        <button class="sidebar-close" id="sidebarClose" type="button" onclick="closeSidebar();">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <div class="sidebar-institution">
        <div class="institution-name">THE POLYTECHNIC, IBADAN</div>
        <div class="institution-dept">SKILL DEVELOPMENT CENTRE</div>
    </div>
    
    <div class="sidebar-user">
        <img src="<?php echo htmlspecialchars($profile_picture); ?>" 
             alt="User" 
             class="sidebar-user-avatar"
             onerror="this.onerror=null; this.src='<?php echo $default_avatar; ?>'">
        <div class="sidebar-user-info">
            <h6><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></h6>
            <small><i class="fas fa-circle text-success" style="font-size: 8px;"></i> Online</small>
        </div>
    </div>
    
    <ul class="sidebar-nav">
        <!-- Dashboard -->
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>
        
        <li class="nav-section">Management</li>
        
        <!-- Employees -->
        <li class="nav-item">
            <a href="javascript:void(0)" class="nav-link" onclick="toggleSubmenu('employeesMenu');">
                <i class="fas fa-users"></i>
                <span>Employees</span>
                <i class="fas fa-chevron-down ml-auto"></i>
            </a>
            <div class="submenu" id="employeesMenu">
                <ul class="nav-sub">
                    <li><a href="employees.php" class="nav-link"><i class="fas fa-list"></i> All Employees</a></li>
                    <li><a href="add-employee.php" class="nav-link"><i class="fas fa-user-plus"></i> Add Employee</a></li>
                </ul>
            </div>
        </li>
        
        <!-- Trainers -->
        <li class="nav-item">
            <a href="javascript:void(0)" class="nav-link" onclick="toggleSubmenu('trainersMenu');">
                <i class="fas fa-user-tie"></i>
                <span>Trainers</span>
                <i class="fas fa-chevron-down ml-auto"></i>
            </a>
            <div class="submenu" id="trainersMenu">
                <ul class="nav-sub">
                    <li><a href="trainers.php" class="nav-link"><i class="fas fa-list"></i> All Trainers</a></li>
                    <li><a href="add-trainer.php" class="nav-link"><i class="fas fa-user-plus"></i> Add Trainer</a></li>
                    <li><a href="trainer-stats.php" class="nav-link"><i class="fas fa-chart-pie"></i> Statistics</a></li>
                </ul>
            </div>
        </li>
        
        <!-- Trainings -->
        <li class="nav-item">
            <a href="javascript:void(0)" class="nav-link" onclick="toggleSubmenu('trainingsMenu');">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Trainings</span>
                <i class="fas fa-chevron-down ml-auto"></i>
            </a>
            <div class="submenu" id="trainingsMenu">
                <ul class="nav-sub">
                    <li><a href="trainings.php" class="nav-link"><i class="fas fa-list"></i> All Trainings</a></li>
                    <li><a href="add-training.php" class="nav-link"><i class="fas fa-plus-circle"></i> Add Training</a></li>
                    <li><a href="training-calendar.php" class="nav-link"><i class="fas fa-calendar-alt"></i> Calendar</a></li>
                    <li><a href="training-types.php" class="nav-link"><i class="fas fa-tags"></i> Training Types</a></li>
                </ul>
            </div>
        </li>
        
        <!-- Enrollments -->
        <li class="nav-item">
            <a href="enrollments.php" class="nav-link <?php echo $current_page == 'enrollments.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-graduate"></i>
                <span>Enrollments</span>
            </a>
        </li>
        
        <!-- Certifications -->
        <li class="nav-item">
            <a href="javascript:void(0)" class="nav-link" onclick="toggleSubmenu('certificationsMenu');">
                <i class="fas fa-certificate"></i>
                <span>Certifications</span>
                <i class="fas fa-chevron-down ml-auto"></i>
            </a>
            <div class="submenu" id="certificationsMenu">
                <ul class="nav-sub">
                    <li><a href="certifications.php" class="nav-link"><i class="fas fa-list"></i> All Certifications</a></li>
                    <li><a href="add-certification.php" class="nav-link"><i class="fas fa-plus-circle"></i> Issue Certificate</a></li>
                    <li><a href="expired-certifications.php" class="nav-link"><i class="fas fa-exclamation-triangle"></i> Expired</a></li>
                </ul>
            </div>
        </li>
        
        <!-- Development Plans -->
        <li class="nav-item">
            <a href="development-plans.php" class="nav-link <?php echo $current_page == 'development-plans.php' ? 'active' : ''; ?>">
                <i class="fas fa-tasks"></i>
                <span>Development Plans</span>
            </a>
        </li>
        
        <!-- Departments -->
        <li class="nav-item">
            <a href="departments.php" class="nav-link <?php echo $current_page == 'departments.php' ? 'active' : ''; ?>">
                <i class="fas fa-building"></i>
                <span>Departments</span>
            </a>
        </li>
        
        <!-- Payments -->
        <li class="nav-item">
            <a href="payments.php" class="nav-link <?php echo $current_page == 'payments.php' ? 'active' : ''; ?>">
                <i class="fas fa-credit-card"></i>
                <span>Payments</span>
            </a>
        </li>
        
        <li class="nav-section">Analytics</li>
        
        <li class="nav-item">
            <a href="reports.php" class="nav-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                <i class="fas fa-file-alt"></i>
                <span>Reports</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a href="analytics.php" class="nav-link <?php echo $current_page == 'analytics.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-pie"></i>
                <span>Analytics</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a href="logs.php" class="nav-link <?php echo $current_page == 'logs.php' ? 'active' : ''; ?>">
                <i class="fas fa-history"></i>
                <span>Activity Logs</span>
            </a>
        </li>
        
        <li class="nav-section">System</li>
        
        <li class="nav-item">
            <a href="javascript:void(0)" class="nav-link" onclick="toggleSubmenu('settingsMenu');">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
                <i class="fas fa-chevron-down ml-auto"></i>
            </a>
            <div class="submenu" id="settingsMenu">
                <ul class="nav-sub">
                    <li><a href="profile.php" class="nav-link"><i class="fas fa-user-circle"></i> My Profile</a></li>
                    <li><a href="change-password.php" class="nav-link"><i class="fas fa-key"></i> Change Password</a></li>
                    <li><a href="settings.php" class="nav-link"><i class="fas fa-sliders-h"></i> System Settings</a></li>
                    <li><a href="backup.php" class="nav-link"><i class="fas fa-database"></i> Backup</a></li>
                </ul>
            </div>
        </li>
        
        <li class="nav-item mt-3">
            <a href="../logout.php" class="nav-link text-danger">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
    
    <div class="sidebar-footer">
        <div class="sidebar-institution-footer">
            <div class="footer-institution">THE POLYTECHNIC, IBADAN</div>
            <div class="footer-dept">SKILL DEVELOPMENT CENTRE</div>
        </div>
        <div class="sidebar-version">
            <i class="fas fa-code-branch"></i> v1.0.0
        </div>
    </div>
</nav>

<!-- Mobile Sidebar Toggle Button -->
<button class="sidebar-toggle-btn" id="sidebarToggleBtn" type="button" onclick="openSidebar();">
    <i class="fas fa-bars"></i>
</button>

<style>
/* ===== SIDEBAR STYLES ===== */
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 280px;
    height: 100vh;
    background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
    color: #fff;
    display: flex;
    flex-direction: column;
    z-index: 1050;
    transition: transform 0.35s ease, box-shadow 0.35s ease;
    overflow-y: auto;
    overflow-x: hidden;
    box-shadow: 2px 0 20px rgba(0,0,0,0.2);
    transform: translateX(-100%);
    -webkit-transform: translateX(-100%);
}

.sidebar.open {
    transform: translateX(0);
    -webkit-transform: translateX(0);
}

/* Backdrop */
.sidebar-backdrop {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    z-index: 1040;
    backdrop-filter: blur(3px);
}

.sidebar-backdrop.show {
    display: block !important;
}

/* Toggle Button */
.sidebar-toggle-btn {
    display: none;
    position: fixed;
    top: 80px;
    left: 15px;
    z-index: 1030;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 12px 16px;
    font-size: 1.2rem;
    cursor: pointer;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
}

.sidebar-toggle-btn:hover {
    transform: scale(1.05);
}

.sidebar-toggle-btn:active {
    transform: scale(0.95);
}

/* Close Button */
.sidebar-close {
    display: none;
    background: rgba(255,255,255,0.05);
    border: none;
    color: rgba(255,255,255,0.6);
    font-size: 1.3rem;
    cursor: pointer;
    padding: 8px 12px;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.sidebar-close:hover {
    background: rgba(255,255,255,0.1);
    color: #fff;
}

/* Desktop - Always show */
@media (min-width: 992px) {
    .sidebar {
        transform: translateX(0) !important;
        -webkit-transform: translateX(0) !important;
    }
    .sidebar-backdrop { display: none !important; }
    .sidebar-toggle-btn { display: none !important; }
    .sidebar-close { display: none !important; }
}

/* Mobile - Show toggle, hide sidebar */
@media (max-width: 991.98px) {
    .sidebar-toggle-btn { display: block; }
    .sidebar.open { 
        transform: translateX(0);
        -webkit-transform: translateX(0);
        box-shadow: 2px 0 30px rgba(0,0,0,0.4);
    }
    .sidebar-backdrop.show { display: block; }
    .sidebar-close { display: block; }
}

/* Scrollbar */
.sidebar::-webkit-scrollbar { width: 4px; }
.sidebar::-webkit-scrollbar-track { background: rgba(255,255,255,0.05); }
.sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 10px; }

/* Header */
.sidebar-header {
    padding: 20px 25px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}

.sidebar-brand {
    color: #fff;
    font-size: 1.3rem;
    font-weight: 700;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 12px;
}

.sidebar-brand i {
    font-size: 1.8rem;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.sidebar-brand:hover { color: #fff; text-decoration: none; }

/* Institution */
.sidebar-institution {
    padding: 10px 25px 15px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    text-align: center;
    flex-shrink: 0;
}

.sidebar-institution .institution-name {
    font-size: 0.7rem;
    font-weight: 700;
    color: #667eea;
    letter-spacing: 1px;
}

.sidebar-institution .institution-dept {
    font-size: 0.6rem;
    color: rgba(255,255,255,0.5);
    margin-top: 2px;
}

/* User */
.sidebar-user {
    padding: 20px 25px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    display: flex;
    align-items: center;
    gap: 15px;
    flex-shrink: 0;
}

.sidebar-user-avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid rgba(255,255,255,0.1);
    background: #2d2d44;
}

.sidebar-user-info h6 { margin: 0; font-weight: 600; font-size: 0.95rem; }
.sidebar-user-info small { color: rgba(255,255,255,0.5); font-size: 0.75rem; }

/* Nav */
.sidebar-nav {
    flex: 1;
    padding: 15px 0;
    list-style: none;
    margin: 0;
}

.nav-section {
    padding: 12px 25px 8px;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: rgba(255,255,255,0.3);
    font-weight: 600;
}

.sidebar-nav .nav-item { margin-bottom: 1px; }

.sidebar-nav .nav-link {
    display: flex;
    align-items: center;
    padding: 11px 25px;
    color: rgba(255,255,255,0.6);
    text-decoration: none;
    transition: all 0.25s ease;
    border-left: 3px solid transparent;
    cursor: pointer;
    font-size: 0.9rem;
    gap: 12px;
}

.sidebar-nav .nav-link:hover {
    color: #fff;
    background: rgba(255,255,255,0.05);
}

.sidebar-nav .nav-link.active {
    color: #fff;
    background: rgba(102, 126, 234, 0.15);
    border-left-color: #667eea;
}

.sidebar-nav .nav-link i:first-child {
    width: 20px;
    text-align: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.sidebar-nav .nav-link span { flex: 1; }

.sidebar-nav .nav-link .fa-chevron-down {
    font-size: 0.7rem;
    transition: transform 0.3s ease;
    opacity: 0.5;
}

.sidebar-nav .nav-link.expanded .fa-chevron-down {
    transform: rotate(180deg);
    opacity: 1;
}

/* Submenu */
.submenu {
    display: none;
    background: rgba(0,0,0,0.2);
}

.submenu.show {
    display: block !important;
}

.nav-sub {
    list-style: none;
    padding: 0;
    margin: 0;
}

.nav-sub .nav-link {
    padding: 8px 25px 8px 60px;
    font-size: 0.85rem;
    color: rgba(255,255,255,0.4);
}

.nav-sub .nav-link i {
    font-size: 0.75rem;
    width: 16px;
}

.nav-sub .nav-link:hover { color: #fff; background: rgba(255,255,255,0.03); }
.nav-sub .nav-link.active { color: #667eea; background: rgba(102, 126, 234, 0.1); }

/* Footer */
.sidebar-footer {
    padding: 15px 25px;
    border-top: 1px solid rgba(255,255,255,0.05);
    flex-shrink: 0;
}

.sidebar-institution-footer {
    text-align: center;
    padding-bottom: 8px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    margin-bottom: 8px;
}

.sidebar-institution-footer .footer-institution {
    font-size: 0.65rem;
    font-weight: 700;
    color: #667eea;
}

.sidebar-institution-footer .footer-dept {
    font-size: 0.55rem;
    color: rgba(255,255,255,0.4);
}

.sidebar-version {
    text-align: center;
    color: rgba(255,255,255,0.2);
    font-size: 0.7rem;
}

/* Wrapper */
.wrapper {
    margin-left: 280px;
    min-height: 100vh;
    transition: margin-left 0.3s ease;
}

@media (max-width: 991.98px) {
    .wrapper { margin-left: 0; }
}

/* Mobile */
@media (max-width: 576px) {
    .sidebar { width: 100%; max-width: 320px; }
    .sidebar-user { padding: 15px 20px; }
    .sidebar-nav .nav-link { padding: 10px 20px; font-size: 0.85rem; }
    .nav-sub .nav-link { padding: 7px 20px 7px 50px; font-size: 0.8rem; }
    .sidebar-header { padding: 15px 20px; }
    .sidebar-brand { font-size: 1.1rem; }
}

/* Print */
@media print {
    .sidebar, .sidebar-toggle-btn, .sidebar-backdrop { display: none !important; }
    .wrapper { margin-left: 0 !important; }
}
</style>

<script>
// ============================================
// SIDEBAR CONTROLS - PURE JAVASCRIPT
// ============================================

function openSidebar() {
    var sidebar = document.getElementById('sidebar');
    var backdrop = document.getElementById('sidebarBackdrop');
    
    if (sidebar) {
        sidebar.classList.add('open');
        sidebar.style.transform = 'translateX(0)';
        sidebar.style.webkitTransform = 'translateX(0)';
    }
    if (backdrop) {
        backdrop.classList.add('show');
        backdrop.style.display = 'block';
    }
    document.body.style.overflow = 'hidden';
    console.log('✓ Sidebar opened');
}

function closeSidebar() {
    var sidebar = document.getElementById('sidebar');
    var backdrop = document.getElementById('sidebarBackdrop');
    
    if (sidebar) {
        sidebar.classList.remove('open');
        sidebar.style.transform = 'translateX(-100%)';
        sidebar.style.webkitTransform = 'translateX(-100%)';
    }
    if (backdrop) {
        backdrop.classList.remove('show');
        backdrop.style.display = 'none';
    }
    document.body.style.overflow = '';
    console.log('✓ Sidebar closed');
}

function toggleSidebar() {
    var sidebar = document.getElementById('sidebar');
    if (sidebar && sidebar.classList.contains('open')) {
        closeSidebar();
    } else {
        openSidebar();
    }
}

function toggleSubmenu(menuId) {
    var menu = document.getElementById(menuId);
    var link = event.currentTarget;
    
    if (!menu) return;
    
    if (menu.classList.contains('show')) {
        menu.classList.remove('show');
        menu.style.display = 'none';
        link.classList.remove('expanded');
    } else {
        // Close other submenus
        document.querySelectorAll('.submenu.show').forEach(function(m) {
            if (m.id !== menuId) {
                m.classList.remove('show');
                m.style.display = 'none';
            }
        });
        document.querySelectorAll('.nav-link.expanded').forEach(function(l) {
            if (l !== link) l.classList.remove('expanded');
        });
        
        menu.classList.add('show');
        menu.style.display = 'block';
        link.classList.add('expanded');
    }
}

// Initialize on page load
(function() {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        console.log('=== Sidebar Init ===');
        
        var sidebar = document.getElementById('sidebar');
        var backdrop = document.getElementById('sidebarBackdrop');
        var toggleBtn = document.getElementById('sidebarToggleBtn');
        
        console.log('Sidebar:', sidebar ? 'FOUND' : 'MISSING');
        console.log('Backdrop:', backdrop ? 'FOUND' : 'MISSING');
        console.log('ToggleBtn:', toggleBtn ? 'FOUND' : 'MISSING');
        
        // Auto-open submenu if a child is active
        document.querySelectorAll('.nav-sub .nav-link.active').forEach(function(link) {
            var submenu = link.closest('.submenu');
            if (submenu) {
                submenu.classList.add('show');
                submenu.style.display = 'block';
                var parent = submenu.previousElementSibling;
                if (parent) parent.classList.add('expanded');
            }
        });
        
        // Keyboard shortcut
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar && sidebar.classList.contains('open')) {
                closeSidebar();
            }
            if (e.ctrlKey && e.key === 'b') {
                e.preventDefault();
                toggleSidebar();
            }
        });
        
        // Close on resize to desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth > 991 && sidebar && sidebar.classList.contains('open')) {
                closeSidebar();
            }
        });
        
        // Close when clicking outside (mobile)
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 991 && sidebar && sidebar.classList.contains('open')) {
                var target = e.target;
                if (!sidebar.contains(target) && 
                    !target.closest('#sidebarToggleBtn') && 
                    !target.closest('#sidebarToggle')) {
                    closeSidebar();
                }
            }
        });
        
        console.log('✓ Sidebar initialized');
    });
})();
</script>