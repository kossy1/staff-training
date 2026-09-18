<?php
// trainer/includes/sidebar.php - Trainer Sidebar
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-brand">
            <i class="fas fa-user-tie"></i>
            <span>Trainer Panel</span>
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
        <img src="<?php echo $profile_pic; ?>" 
             alt="Trainer" 
             class="sidebar-user-avatar"
             onerror="this.src='../assets/images/default-avatar.png'">
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
                    $my_trainings = $conn->query("SELECT COUNT(*) as c FROM training_programs WHERE trainer_id = $trainer_id")->fetch_assoc()['c'] ?? 0;
                    if ($my_trainings > 0): 
                ?>
                    <span class="nav-badge"><?php echo $my_trainings; ?></span>
                <?php endif; ?>
            </a>
        </li>
        
        <li class="nav-item">
            <a href="my-students.php" class="nav-link <?php echo $current_page == 'my-students.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>My Students</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a href="sessions.php" class="nav-link <?php echo $current_page == 'sessions.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i>
                <span>Training Sessions</span>
            </a>
        </li>
        
        <li class="nav-section">Management</li>
        
        <li class="nav-item">
            <a href="requests.php" class="nav-link <?php echo $current_page == 'requests.php' ? 'active' : ''; ?>">
                <i class="fas fa-envelope-open-text"></i>
                <span>Requests</span>
                <?php if ($pending_requests > 0): ?>
                    <span class="nav-badge badge-danger"><?php echo $pending_requests; ?></span>
                <?php endif; ?>
            </a>
        </li>
        
        <li class="nav-item">
            <a href="departments.php" class="nav-link <?php echo $current_page == 'departments.php' ? 'active' : ''; ?>">
                <i class="fas fa-building"></i>
                <span>Departments</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a href="materials.php" class="nav-link <?php echo $current_page == 'materials.php' ? 'active' : ''; ?>">
                <i class="fas fa-book"></i>
                <span>Training Materials</span>
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
            <a href="logout.php" class="nav-link text-danger">
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
            <i class="fas fa-user-tie"></i> Trainer v1.0
        </div>
    </div>
</nav>

<button class="sidebar-toggle-btn" id="sidebarToggleBtn" type="button" onclick="toggleSidebar();">
    <i class="fas fa-bars"></i>
</button>

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
    z-index: 1050;
    transition: transform 0.35s ease;
    overflow-y: auto;
    box-shadow: 2px 0 20px rgba(0,0,0,0.2);
    transform: translateX(-100%);
}
.sidebar.open { transform: translateX(0); }
.sidebar-backdrop {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.6);
    z-index: 1040;
}
.sidebar-backdrop.show { display: block; }
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
}
@media (min-width: 992px) {
    .sidebar { transform: translateX(0) !important; }
    .sidebar-backdrop, .sidebar-toggle-btn, .sidebar-close { display: none !important; }
}
@media (max-width: 991.98px) {
    .sidebar-toggle-btn { display: block; }
    .sidebar.open { transform: translateX(0); }
    .sidebar-backdrop.show { display: block; }
    .sidebar-close { display: block; }
}
.sidebar-header {
    padding: 20px 25px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.sidebar-brand {
    color: #fff;
    font-size: 1.2rem;
    font-weight: 700;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 12px;
}
.sidebar-brand i {
    font-size: 1.6rem;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.sidebar-brand:hover { color: #fff; text-decoration: none; }
.sidebar-close {
    display: none;
    background: rgba(255,255,255,0.05);
    border: none;
    color: rgba(255,255,255,0.6);
    font-size: 1.3rem;
    cursor: pointer;
    padding: 8px 12px;
    border-radius: 6px;
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
}
.sidebar-institution .institution-dept {
    font-size: 0.6rem;
    color: rgba(255,255,255,0.5);
    margin-top: 2px;
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
    background: #2d2d44;
}
.sidebar-user-info h6 { margin: 0; font-weight: 600; font-size: 0.9rem; }
.sidebar-user-info small { color: rgba(255,255,255,0.5); font-size: 0.7rem; }
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
.sidebar-nav .nav-link span { flex: 1; }
.sidebar-nav .nav-link .nav-badge {
    background: rgba(102, 126, 234, 0.2);
    color: #667eea;
    padding: 2px 10px;
    border-radius: 50px;
    font-size: 0.7rem;
    font-weight: 600;
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
.sidebar-footer {
    padding: 15px 25px;
    border-top: 1px solid rgba(255,255,255,0.05);
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
</style>

<script>
function toggleSidebar() {
    var sidebar = document.getElementById('sidebar');
    var backdrop = document.getElementById('sidebarBackdrop');
    if (!sidebar) return;
    if (sidebar.classList.contains('open')) {
        closeSidebar();
    } else {
        sidebar.classList.add('open');
        sidebar.style.transform = 'translateX(0)';
        if (backdrop) { backdrop.classList.add('show'); backdrop.style.display = 'block'; }
        document.body.style.overflow = 'hidden';
    }
}
function closeSidebar() {
    var sidebar = document.getElementById('sidebar');
    var backdrop = document.getElementById('sidebarBackdrop');
    if (sidebar) { sidebar.classList.remove('open'); sidebar.style.transform = 'translateX(-100%)'; }
    if (backdrop) { backdrop.classList.remove('show'); backdrop.style.display = 'none'; }
    document.body.style.overflow = '';
}
document.addEventListener('DOMContentLoaded', function() {
    var backdrop = document.getElementById('sidebarBackdrop');
    var closeBtn = document.getElementById('sidebarClose');
    if (backdrop) backdrop.addEventListener('click', closeSidebar);
    if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeSidebar();
    });
});
</script>