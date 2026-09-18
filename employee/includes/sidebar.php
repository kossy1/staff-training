<?php
// employee/includes/sidebar.php - Employee Sidebar (Pure JavaScript - InfinityFree Compatible)
$current_page = basename($_SERVER['PHP_SELF']);
$employee_id = $_SESSION['employee_id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;

// Get profile picture
$profile_picture = '';
$default_avatar = '../assets/images/default-avatar.png';

if (isset($_SESSION['employee_id']) && $_SESSION['employee_id'] > 0) {
    $emp_id = (int)$_SESSION['employee_id'];
    $result = $conn->query("SELECT profile_picture FROM employees WHERE id = $emp_id");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (!empty($row['profile_picture'])) {
            $profile_picture = '../uploads/profile-pictures/' . $row['profile_picture'];
        }
    }
}

if (empty($profile_picture) || !file_exists($profile_picture)) {
    $profile_picture = $default_avatar;
}
?>

<!-- Sidebar Backdrop (Mobile) -->
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

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
        <img src="<?php echo $profile_picture; ?>" 
             alt="User" 
             class="sidebar-user-avatar"
             onerror="this.src='<?php echo $default_avatar; ?>'">
        <div class="sidebar-user-info">
            <h6><?php echo htmlspecialchars($full_name ?? $_SESSION['username'] ?? 'Employee'); ?></h6>
            <small><i class="fas fa-circle text-success" style="font-size: 8px;"></i> Online</small>
        </div>
    </div>
    
    <ul class="sidebar-nav">
        <li class="nav-section">Main</li>
        
        <!-- Dashboard -->
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>
        
        <!-- My Trainings -->
        <li class="nav-item">
            <a href="my-trainings.php" class="nav-link <?php echo $current_page == 'my-trainings.php' ? 'active' : ''; ?>">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>My Trainings</span>
                <?php 
                $pending = $conn->query("SELECT COUNT(*) as count FROM employee_trainings WHERE employee_id = $employee_id AND status = 'enrolled'")->fetch_assoc();
                if ($pending['count'] > 0): 
                ?>
                    <span class="nav-badge badge-danger"><?php echo $pending['count']; ?></span>
                <?php endif; ?>
            </a>
        </li>
        
        <!-- My Certificates -->
        <li class="nav-item">
            <a href="my-certificates.php" class="nav-link <?php echo $current_page == 'my-certificates.php' ? 'active' : ''; ?>">
                <i class="fas fa-certificate"></i>
                <span>My Certificates</span>
            </a>
        </li>
        
        <!-- Development Plan -->
        <li class="nav-item">
            <a href="development-plan.php" class="nav-link <?php echo $current_page == 'development-plan.php' ? 'active' : ''; ?>">
                <i class="fas fa-tasks"></i>
                <span>Development Plan</span>
            </a>
        </li>
        
        <li class="nav-section">Actions</li>
        
        <!-- Apply for Training -->
        <li class="nav-item">
            <a href="apply-training.php" class="nav-link <?php echo $current_page == 'apply-training.php' ? 'active' : ''; ?>">
                <i class="fas fa-plus-circle"></i>
                <span>Apply for Training</span>
            </a>
        </li>
        
        <!-- My Payments -->
        <li class="nav-item">
            <a href="payments.php" class="nav-link <?php echo $current_page == 'payments.php' ? 'active' : ''; ?>">
                <i class="fas fa-credit-card"></i>
                <span>My Payments</span>
            </a>
        </li>
        
        <!-- Give Feedback -->
        <li class="nav-item">
            <a href="feedback.php" class="nav-link <?php echo $current_page == 'feedback.php' ? 'active' : ''; ?>">
                <i class="fas fa-comment-dots"></i>
                <span>Give Feedback</span>
            </a>
        </li>
        
        <li class="nav-section">Account</li>
        
        <!-- My Profile -->
        <li class="nav-item">
            <a href="profile.php" class="nav-link <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-circle"></i>
                <span>My Profile</span>
            </a>
        </li>
        
        <!-- Logout -->
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
            <i class="fas fa-user"></i> Employee Panel v1.0
        </div>
    </div>
</nav>

<!-- Mobile Sidebar Toggle Button -->
<button class="sidebar-toggle-btn" id="sidebarToggleBtn" type="button" onclick="toggleSidebar();">
    <i class="fas fa-bars"></i>
</button>

<style>
/* ===== SIDEBAR STYLES ===== */
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 260px;
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
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    z-index: 1040;
}

.sidebar-backdrop.show {
    display: block;
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

/* Desktop - Always show sidebar */
@media (min-width: 992px) {
    .sidebar {
        transform: translateX(0) !important;
        -webkit-transform: translateX(0) !important;
    }
    
    .sidebar-backdrop {
        display: none !important;
    }
    
    .sidebar-toggle-btn {
        display: none !important;
    }
    
    .sidebar-close {
        display: none !important;
    }
}

/* Mobile - Show toggle button, hide sidebar by default */
@media (max-width: 991.98px) {
    .sidebar-toggle-btn {
        display: block;
    }
    
    .sidebar.open {
        transform: translateX(0);
        -webkit-transform: translateX(0);
    }
    
    .sidebar-backdrop.show {
        display: block;
    }
    
    .sidebar-close {
        display: block;
    }
}

/* Scrollbar */
.sidebar::-webkit-scrollbar { width: 4px; }
.sidebar::-webkit-scrollbar-track { background: rgba(255,255,255,0.05); }
.sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 10px; }

/* Sidebar Header */
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

/* Institution Header */
.sidebar-institution {
    padding: 8px 25px 12px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    text-align: center;
    flex-shrink: 0;
}

.sidebar-institution .institution-name {
    font-size: 0.7rem;
    font-weight: 700;
    color: #667eea;
    letter-spacing: 1px;
    text-transform: uppercase;
}

.sidebar-institution .institution-dept {
    font-size: 0.6rem;
    color: rgba(255,255,255,0.5);
    letter-spacing: 0.5px;
    margin-top: 2px;
}

/* User Info */
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

/* Navigation */
.sidebar-nav {
    flex: 1;
    padding: 15px 0;
    list-style: none;
    margin: 0;
    overflow-y: auto;
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

.sidebar-nav .nav-link .nav-badge {
    background: rgba(102, 126, 234, 0.2);
    color: #667eea;
    padding: 2px 10px;
    border-radius: 50px;
    font-size: 0.7rem;
    font-weight: 600;
    margin-left: auto;
}

.sidebar-nav .nav-link .nav-badge.badge-danger {
    background: rgba(231, 74, 59, 0.2);
    color: #e74a3b;
    animation: pulse-badge 2s infinite;
}

@keyframes pulse-badge {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

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
    letter-spacing: 0.5px;
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

/* Main Content Offset */
.wrapper {
    margin-left: 260px;
    min-height: 100vh;
    transition: margin-left 0.3s ease;
}

@media (max-width: 991.98px) {
    .wrapper { margin-left: 0; }
}

@media (max-width: 576px) {
    .sidebar { width: 100%; max-width: 320px; }
    .sidebar-user { padding: 15px 20px; }
    .sidebar-nav .nav-link { padding: 10px 20px; font-size: 0.85rem; }
    .sidebar-header { padding: 15px 20px; }
    .sidebar-brand { font-size: 1.1rem; }
}
</style>

<script>
// ============================================
// PURE JAVASCRIPT SIDEBAR CONTROLS
// Works without jQuery - InfinityFree Compatible
// ============================================

function toggleSidebar() {
    var sidebar = document.getElementById('sidebar');
    if (!sidebar) {
        console.error('Sidebar element not found!');
        return;
    }
    
    if (sidebar.classList.contains('open')) {
        closeSidebar();
    } else {
        openSidebar();
    }
}

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
    console.log('Sidebar opened');
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
    console.log('Sidebar closed');
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    var sidebar = document.getElementById('sidebar');
    var backdrop = document.getElementById('sidebarBackdrop');
    var toggleBtn = document.getElementById('sidebarToggleBtn');
    var closeBtn = document.getElementById('sidebarClose');
    
    console.log('Employee Sidebar initialization:');
    console.log('- Sidebar:', sidebar ? 'Found' : 'NOT FOUND');
    console.log('- Backdrop:', backdrop ? 'Found' : 'NOT FOUND');
    console.log('- Toggle Button:', toggleBtn ? 'Found' : 'NOT FOUND');
    console.log('- Close Button:', closeBtn ? 'Found' : 'NOT FOUND');
    
    // Backdrop click
    if (backdrop) {
        backdrop.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            closeSidebar();
        });
    }
    
    // Close button click
    if (closeBtn) {
        closeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            closeSidebar();
        });
    }
    
    // Toggle button click
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleSidebar();
        });
    }
    
    // ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar && sidebar.classList.contains('open')) {
            closeSidebar();
        }
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 991) {
            closeSidebar();
        }
    });
    
    console.log('Employee Sidebar controls initialized successfully!');
});
</script>