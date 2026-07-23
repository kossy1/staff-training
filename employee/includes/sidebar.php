<?php
// employee/includes/sidebar.php - Employee Sidebar
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-brand">
            <i class="fas fa-graduation-cap"></i>
            <span>StaffTraining</span>
        </a>
        <button class="sidebar-close" id="sidebarClose">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <div class="sidebar-user">
        <img src="<?php echo $profile_pic; ?>" alt="User" class="sidebar-user-avatar">
        <div class="sidebar-user-info">
            <h6><?php echo htmlspecialchars($full_name); ?></h6>
            <small><i class="fas fa-circle text-success" style="font-size: 8px;"></i> Online</small>
        </div>
    </div>
    
    <ul class="sidebar-nav">
        <li class="nav-section">Main</li>
        
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>
        
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
        
        <li class="nav-item">
            <a href="my-certificates.php" class="nav-link <?php echo $current_page == 'my-certificates.php' ? 'active' : ''; ?>">
                <i class="fas fa-certificate"></i>
                <span>My Certificates</span>
                <?php 
                $certs = $conn->query("SELECT COUNT(*) as count FROM certifications WHERE employee_id = $employee_id AND status = 'active'")->fetch_assoc();
                if ($certs['count'] > 0): 
                ?>
                    <span class="nav-badge"><?php echo $certs['count']; ?></span>
                <?php endif; ?>
            </a>
        </li>
        
        <li class="nav-item">
            <a href="development-plan.php" class="nav-link <?php echo $current_page == 'development-plan.php' ? 'active' : ''; ?>">
                <i class="fas fa-tasks"></i>
                <span>Development Plan</span>
            </a>
        </li>
        
        <li class="nav-section">Actions</li>
        
        <li class="nav-item">
            <a href="apply-training.php" class="nav-link <?php echo $current_page == 'apply-training.php' ? 'active' : ''; ?>">
                <i class="fas fa-plus-circle"></i>
                <span>Apply for Training</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a href="feedback.php" class="nav-link <?php echo $current_page == 'feedback.php' ? 'active' : ''; ?>">
                <i class="fas fa-comment-dots"></i>
                <span>Give Feedback</span>
            </a>
        </li>
        
        <li class="nav-section">Account</li>
        
        <li class="nav-item">
            <a href="profile.php" class="nav-link <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-circle"></i>
                <span>My Profile</span>
            </a>
        </li>
        
        <li class="nav-item mt-3">
            <a href="../logout.php" class="nav-link text-danger">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
    
    <div class="sidebar-footer">
        <div class="sidebar-version">
            <i class="fas fa-user"></i> Employee Panel v1.0
        </div>
    </div>
</nav>

<style>
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
    z-index: 1000;
    transition: transform 0.3s ease;
    overflow-y: auto;
    box-shadow: 2px 0 20px rgba(0,0,0,0.2);
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

.sidebar-close {
    display: none;
    background: none;
    border: none;
    color: rgba(255,255,255,0.5);
    font-size: 1.2rem;
    cursor: pointer;
}

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
}

.sidebar-footer {
    padding: 15px 25px;
    border-top: 1px solid rgba(255,255,255,0.05);
}
.sidebar-version {
    text-align: center;
    color: rgba(255,255,255,0.2);
    font-size: 0.7rem;
}

/* Mobile */
@media (max-width: 992px) {
    .sidebar {
        transform: translateX(-100%);
        width: 300px;
    }
    .sidebar.open { transform: translateX(0); box-shadow: 2px 0 30px rgba(0,0,0,0.4); }
    .sidebar-close { display: block; }
    .sidebar-backdrop.show { display: block; }
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
$(document).ready(function() {
    $('#sidebarToggle, #sidebarToggleBtn').on('click', function() {
        $('#sidebar').toggleClass('open');
        $('.sidebar-backdrop').toggleClass('show');
    });
    
    $('#sidebarClose, .sidebar-backdrop').on('click', function() {
        $('#sidebar').removeClass('open');
        $('.sidebar-backdrop').removeClass('show');
    });
});
</script>