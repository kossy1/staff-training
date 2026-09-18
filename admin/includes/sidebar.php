<?php
// admin/includes/sidebar.php - Admin Sidebar Navigation (Fixed Trainers Menu)
$current_page = basename($_SERVER['PHP_SELF']);

// Get user profile picture path
$profile_picture = '';
$default_avatar = '../assets/images/avatar.jpg';

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
        <button class="sidebar-close" id="sidebarClose" type="button">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <div class="sidebar-institution">
        <div class="institution-name">THE POLYTECHNIC, IBADAN</div>
        <div class="institution-dept">SKILL DEVELOPMENT CENTRE</div>
    </div>
    
    <div class="sidebar-user">
        <img src="<?php echo $profile_picture; ?>" 
             alt="User Avatar" 
             class="sidebar-user-avatar"
             id="sidebarUserAvatar"
             onerror="this.src='<?php echo $default_avatar; ?>'">
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
                <?php if ($current_page == 'dashboard.php'): ?>
                    <span class="nav-badge">Active</span>
                <?php endif; ?>
            </a>
        </li>
        
        <!-- ===== MANAGEMENT SECTION ===== -->
        <li class="nav-section">Management</li>
        
        <!-- Employees -->
        <li class="nav-item">
            <a href="javascript:void(0)" class="nav-link <?php echo in_array($current_page, ['employees.php', 'add-employee.php', 'edit-employee.php', 'view-employee.php', 'departments.php']) ? 'active' : ''; ?>" 
               onclick="toggleSubMenu(event, 'employeesMenu')">
                <i class="fas fa-users"></i>
                <span>Employees</span>
                <i class="fas fa-chevron-down ml-auto"></i>
            </a>
            <div class="submenu <?php echo in_array($current_page, ['employees.php', 'add-employee.php', 'edit-employee.php', 'view-employee.php', 'departments.php']) ? 'show' : ''; ?>" id="employeesMenu">
                <ul class="nav-sub">
                    <li class="nav-item">
                        <a href="employees.php" class="nav-link <?php echo $current_page == 'employees.php' ? 'active' : ''; ?>">
                            <i class="fas fa-list"></i> All Employees
                            <span class="nav-badge"><?php 
                                $count = $conn->query("SELECT COUNT(*) as count FROM employees WHERE status = 'active'")->fetch_assoc();
                                echo $count['count'] ?? 0;
                            ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="add-employee.php" class="nav-link <?php echo $current_page == 'add-employee.php' ? 'active' : ''; ?>">
                            <i class="fas fa-user-plus"></i> Add Employee
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="departments.php" class="nav-link <?php echo $current_page == 'departments.php' ? 'active' : ''; ?>">
                            <i class="fas fa-building"></i> Departments
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        <!-- ===== TRAINERS (Separate Top-Level Menu) ===== -->
        <li class="nav-item">
            <a href="javascript:void(0)" class="nav-link <?php echo in_array($current_page, ['trainers.php', 'add-trainer.php', 'edit-trainer.php', 'view-trainer.php', 'trainer-stats.php']) ? 'active' : ''; ?>" 
               onclick="toggleSubMenu(event, 'trainersMenu')">
                <i class="fas fa-user-tie"></i>
                <span>Trainers</span>
                <i class="fas fa-chevron-down ml-auto"></i>
            </a>
            <div class="submenu <?php echo in_array($current_page, ['trainers.php', 'add-trainer.php', 'edit-trainer.php', 'view-trainer.php', 'trainer-stats.php']) ? 'show' : ''; ?>" id="trainersMenu">
                <ul class="nav-sub">
                    <li class="nav-item">
                        <a href="trainers.php" class="nav-link <?php echo $current_page == 'trainers.php' ? 'active' : ''; ?>">
                            <i class="fas fa-list"></i> All Trainers
                            <?php 
                                $trainer_count = $conn->query("SELECT COUNT(*) as count FROM trainers WHERE status = 'active'")->fetch_assoc();
                                if (($trainer_count['count'] ?? 0) > 0): 
                            ?>
                                <span class="nav-badge"><?php echo $trainer_count['count']; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="add-trainer.php" class="nav-link <?php echo $current_page == 'add-trainer.php' ? 'active' : ''; ?>">
                            <i class="fas fa-user-plus"></i> Add Trainer
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="trainer-stats.php" class="nav-link <?php echo $current_page == 'trainer-stats.php' ? 'active' : ''; ?>">
                            <i class="fas fa-chart-pie"></i> Statistics
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        <!-- Trainings -->
        <li class="nav-item">
            <a href="javascript:void(0)" class="nav-link <?php echo in_array($current_page, ['trainings.php', 'add-training.php', 'edit-training.php', 'training-calendar.php', 'view-training.php', 'training-types.php']) ? 'active' : ''; ?>" 
               onclick="toggleSubMenu(event, 'trainingsMenu')">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Trainings</span>
                <i class="fas fa-chevron-down ml-auto"></i>
            </a>
            <div class="submenu <?php echo in_array($current_page, ['trainings.php', 'add-training.php', 'edit-training.php', 'training-calendar.php', 'view-training.php', 'training-types.php']) ? 'show' : ''; ?>" id="trainingsMenu">
                <ul class="nav-sub">
                    <li class="nav-item">
                        <a href="trainings.php" class="nav-link <?php echo $current_page == 'trainings.php' ? 'active' : ''; ?>">
                            <i class="fas fa-list"></i> All Trainings
                            <span class="nav-badge"><?php 
                                $count = $conn->query("SELECT COUNT(*) as count FROM training_programs WHERE status != 'cancelled'")->fetch_assoc();
                                echo $count['count'] ?? 0;
                            ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="add-training.php" class="nav-link <?php echo $current_page == 'add-training.php' ? 'active' : ''; ?>">
                            <i class="fas fa-plus-circle"></i> Add Training
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="training-calendar.php" class="nav-link <?php echo $current_page == 'training-calendar.php' ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-alt"></i> Calendar View
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="training-types.php" class="nav-link <?php echo $current_page == 'training-types.php' ? 'active' : ''; ?>">
                            <i class="fas fa-tags"></i> Training Types
                        </a>
                    </li>
                </ul>
            </div>
        </li>
        
        <!-- Enrollments -->
        <li class="nav-item">
            <a href="enrollments.php" class="nav-link <?php echo $current_page == 'enrollments.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-graduate"></i>
                <span>Enrollments</span>
                <?php 
                    $pending = $conn->query("SELECT COUNT(*) as count FROM employee_trainings WHERE status = 'enrolled'")->fetch_assoc();
                    if (($pending['count'] ?? 0) > 0): 
                ?>
                    <span class="nav-badge badge-danger"><?php echo $pending['count']; ?></span>
                <?php endif; ?>
            </a>
        </li>
        
        <!-- Certifications -->
        <li class="nav-item">
            <a href="javascript:void(0)" class="nav-link <?php echo in_array($current_page, ['certifications.php', 'add-certification.php', 'view-certification.php', 'expired-certifications.php']) ? 'active' : ''; ?>" 
               onclick="toggleSubMenu(event, 'certificationsMenu')">
                <i class="fas fa-certificate"></i>
                <span>Certifications</span>
                <i class="fas fa-chevron-down ml-auto"></i>
            </a>
            <div class="submenu <?php echo in_array($current_page, ['certifications.php', 'add-certification.php', 'view-certification.php', 'expired-certifications.php']) ? 'show' : ''; ?>" id="certificationsMenu">
                <ul class="nav-sub">
                    <li class="nav-item">
                        <a href="certifications.php" class="nav-link <?php echo $current_page == 'certifications.php' ? 'active' : ''; ?>">
                            <i class="fas fa-list"></i> All Certifications
                            <span class="nav-badge"><?php 
                                $count = $conn->query("SELECT COUNT(*) as count FROM certifications WHERE status = 'active'")->fetch_assoc();
                                echo $count['count'] ?? 0;
                            ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="add-certification.php" class="nav-link <?php echo $current_page == 'add-certification.php' ? 'active' : ''; ?>">
                            <i class="fas fa-plus-circle"></i> Issue Certification
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="expired-certifications.php" class="nav-link <?php echo $current_page == 'expired-certifications.php' ? 'active' : ''; ?>">
                            <i class="fas fa-exclamation-triangle text-warning"></i> Expired Certifications
                        </a>
                    </li>
                </ul>
            </div>
        </li>
        
        <!-- Development Plans -->
        <li class="nav-item">
            <a href="javascript:void(0)" class="nav-link <?php echo in_array($current_page, ['development-plans.php', 'add-development-plan.php', 'edit-development-plan.php', 'view-development-plan.php']) ? 'active' : ''; ?>" 
               onclick="toggleSubMenu(event, 'developmentMenu')">
                <i class="fas fa-tasks"></i>
                <span>Development Plans</span>
                <i class="fas fa-chevron-down ml-auto"></i>
            </a>
            <div class="submenu <?php echo in_array($current_page, ['development-plans.php', 'add-development-plan.php', 'edit-development-plan.php', 'view-development-plan.php']) ? 'show' : ''; ?>" id="developmentMenu">
                <ul class="nav-sub">
                    <li class="nav-item">
                        <a href="development-plans.php" class="nav-link <?php echo $current_page == 'development-plans.php' ? 'active' : ''; ?>">
                            <i class="fas fa-list"></i> All Plans
                            <span class="nav-badge"><?php 
                                $count = $conn->query("SELECT COUNT(*) as count FROM development_plans WHERE status != 'completed'")->fetch_assoc();
                                echo $count['count'] ?? 0;
                            ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="add-development-plan.php" class="nav-link <?php echo $current_page == 'add-development-plan.php' ? 'active' : ''; ?>">
                            <i class="fas fa-plus-circle"></i> Create Plan
                        </a>
                    </li>
                </ul>
            </div>
        </li>
        
        <!-- Payments -->
        <li class="nav-item">
            <a href="payments.php" class="nav-link <?php echo $current_page == 'payments.php' ? 'active' : ''; ?>">
                <i class="fas fa-credit-card"></i>
                <span>Payments</span>
                <?php 
                    $pending_payments = $conn->query("SELECT COUNT(*) as count FROM payments WHERE status = 'pending'")->fetch_assoc();
                    if (($pending_payments['count'] ?? 0) > 0): 
                ?>
                    <span class="nav-badge badge-danger"><?php echo $pending_payments['count']; ?></span>
                <?php endif; ?>
            </a>
        </li>
        
        <!-- ===== ANALYTICS SECTION ===== -->
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
        
        <!-- ===== SYSTEM SECTION ===== -->
        <li class="nav-section">System</li>
        
        <li class="nav-item">
            <a href="javascript:void(0)" class="nav-link <?php echo in_array($current_page, ['settings.php', 'profile.php', 'change-password.php', 'backup.php']) ? 'active' : ''; ?>" 
               onclick="toggleSubMenu(event, 'settingsMenu')">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
                <i class="fas fa-chevron-down ml-auto"></i>
            </a>
            <div class="submenu <?php echo in_array($current_page, ['settings.php', 'profile.php', 'change-password.php', 'backup.php']) ? 'show' : ''; ?>" id="settingsMenu">
                <ul class="nav-sub">
                    <li class="nav-item">
                        <a href="profile.php" class="nav-link <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                            <i class="fas fa-user-circle"></i> My Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="change-password.php" class="nav-link <?php echo $current_page == 'change-password.php' ? 'active' : ''; ?>">
                            <i class="fas fa-key"></i> Change Password
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="settings.php" class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                            <i class="fas fa-sliders-h"></i> System Settings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="backup.php" class="nav-link <?php echo $current_page == 'backup.php' ? 'active' : ''; ?>">
                            <i class="fas fa-database"></i> Backup
                        </a>
                    </li>
                </ul>
            </div>
        </li>
        
        <!-- Logout -->
        <li class="nav-item mt-3">
            <a href="../logout.php" class="nav-link text-danger">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
    
    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="sidebar-institution-footer">
            <div class="footer-institution">THE POLYTECHNIC, IBADAN</div>
            <div class="footer-dept">SKILL DEVELOPMENT CENTRE</div>
        </div>
        <div class="sidebar-version">
            <i class="fas fa-code-branch"></i> v1.0.0
        </div>
        <div class="sidebar-stats">
            <div class="stat-item">
                <span class="stat-label">Status</span>
                <span class="stat-value text-success">● Online</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Server</span>
                <span class="stat-value">PHP <?php echo phpversion(); ?></span>
            </div>
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
}

.sidebar.open {
    transform: translateX(0);
}

.sidebar-institution {
    padding: 10px 25px 15px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    text-align: center;
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
    letter-spacing: 0.3px;
}

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
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

@media (min-width: 992px) {
    .sidebar { transform: translateX(0) !important; }
    .sidebar-backdrop { display: none !important; }
    .sidebar-toggle-btn { display: none !important; }
    .sidebar-close { display: none !important; }
}

@media (max-width: 991.98px) {
    .sidebar-toggle-btn { display: block; }
    .sidebar.open {
        transform: translateX(0);
        box-shadow: 2px 0 30px rgba(0,0,0,0.4);
    }
    .sidebar-backdrop.show { display: block; }
    .sidebar-close { display: block; }
}

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
    transform: rotate(90deg);
}

.sidebar::-webkit-scrollbar { width: 4px; }
.sidebar::-webkit-scrollbar-track { background: rgba(255,255,255,0.05); }
.sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 10px; }

.sidebar-header {
    padding: 20px 25px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    display: flex;
    align-items: center;
    justify-content: space-between;
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

.sidebar-user {
    padding: 20px 25px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    display: flex;
    align-items: center;
    gap: 15px;
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
    position: relative;
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

.submenu {
    display: none;
    background: rgba(0,0,0,0.2);
}

.submenu.show { display: block; }

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

.nav-sub .nav-link:hover {
    color: #fff;
    background: rgba(255,255,255,0.03);
}

.nav-sub .nav-link.active {
    color: #667eea;
    background: rgba(102, 126, 234, 0.1);
    border-left-color: #667eea;
}

.nav-sub .nav-link .nav-badge {
    background: rgba(255,255,255,0.05);
    color: rgba(255,255,255,0.5);
    padding: 1px 8px;
    border-radius: 50px;
    font-size: 0.65rem;
    margin-left: auto;
}

.sidebar-footer {
    padding: 15px 25px;
    border-top: 1px solid rgba(255,255,255,0.05);
    margin-top: auto;
}

.sidebar-version {
    text-align: center;
    color: rgba(255,255,255,0.2);
    font-size: 0.7rem;
    margin-bottom: 8px;
}

.sidebar-stats {
    display: flex;
    justify-content: space-around;
    padding-top: 8px;
    border-top: 1px solid rgba(255,255,255,0.03);
}

.sidebar-stats .stat-item { text-align: center; }

.sidebar-stats .stat-label {
    display: block;
    font-size: 0.6rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: rgba(255,255,255,0.2);
}

.sidebar-stats .stat-value {
    font-size: 0.75rem;
    color: rgba(255,255,255,0.5);
}

.sidebar-stats .stat-value.text-success { color: #48bb78; }

.wrapper {
    margin-left: 280px;
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
    .nav-sub .nav-link { padding: 7px 20px 7px 50px; font-size: 0.8rem; }
    .sidebar-header { padding: 15px 20px; }
    .sidebar-brand { font-size: 1.1rem; }
}

@media print {
    .sidebar, .sidebar-toggle-btn, .sidebar-backdrop { display: none !important; }
    .wrapper { margin-left: 0 !important; padding-top: 0 !important; }
}
</style>

<script>
// ============================================
// SIDEBAR CONTROLS - PURE JAVASCRIPT
// ============================================

function toggleSidebar() {
    var sidebar = document.getElementById('sidebar');
    var backdrop = document.getElementById('sidebarBackdrop');
    if (!sidebar) return;
    
    if (sidebar.classList.contains('open')) {
        closeSidebar();
    } else {
        sidebar.classList.add('open');
        sidebar.style.transform = 'translateX(0)';
        if (backdrop) {
            backdrop.classList.add('show');
            backdrop.style.display = 'block';
        }
        document.body.style.overflow = 'hidden';
    }
}

function closeSidebar() {
    var sidebar = document.getElementById('sidebar');
    var backdrop = document.getElementById('sidebarBackdrop');
    if (sidebar) {
        sidebar.classList.remove('open');
        sidebar.style.transform = 'translateX(-100%)';
    }
    if (backdrop) {
        backdrop.classList.remove('show');
        backdrop.style.display = 'none';
    }
    document.body.style.overflow = '';
}

function toggleSubMenu(event, menuId) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    var menu = document.getElementById(menuId);
    if (!menu) return;
    
    if (menu.classList.contains('show')) {
        menu.classList.remove('show');
        menu.style.display = 'none';
    } else {
        menu.classList.add('show');
        menu.style.display = 'block';
    }
    
    if (event && event.currentTarget) {
        var chevron = event.currentTarget.querySelector('.fa-chevron-down');
        if (chevron) {
            chevron.style.transform = menu.classList.contains('show') ? 'rotate(180deg)' : 'rotate(0deg)';
        }
    }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    var sidebar = document.getElementById('sidebar');
    var backdrop = document.getElementById('sidebarBackdrop');
    var toggleBtn = document.getElementById('sidebarToggleBtn');
    var closeBtn = document.getElementById('sidebarClose');
    
    console.log('Sidebar init:');
    console.log('- Sidebar:', sidebar ? 'OK' : 'MISSING');
    console.log('- Backdrop:', backdrop ? 'OK' : 'MISSING');
    console.log('- Toggle:', toggleBtn ? 'OK' : 'MISSING');
    console.log('- Close:', closeBtn ? 'OK' : 'MISSING');
    
    if (backdrop) {
        backdrop.addEventListener('click', function(e) {
            e.preventDefault();
            closeSidebar();
        });
    }
    
    if (closeBtn) {
        closeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            closeSidebar();
        });
    }
    
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleSidebar();
        });
    }
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar && sidebar.classList.contains('open')) {
            closeSidebar();
        }
    });
    
    // Auto-open active submenu
    var activeSubLinks = document.querySelectorAll('.nav-sub .nav-link.active');
    activeSubLinks.forEach(function(link) {
        var submenu = link.closest('.submenu');
        if (submenu) {
            submenu.classList.add('show');
            submenu.style.display = 'block';
            var parentLink = submenu.previousElementSibling;
            if (parentLink) {
                var chevron = parentLink.querySelector('.fa-chevron-down');
                if (chevron) {
                    chevron.style.transform = 'rotate(180deg)';
                }
            }
        }
    });
    
    window.addEventListener('resize', function() {
        if (window.innerWidth > 991) {
            closeSidebar();
        }
    });
    
    console.log('Sidebar initialized successfully!');
});
</script>