<?php
// trainer/includes/navbar.php - Trainer Navbar
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top" id="mainNav" style="height: 70px; padding: 0 20px; z-index: 1030;">
    <div class="container-fluid">
        <div class="d-flex align-items-center">
            <button class="btn btn-link text-dark d-lg-none p-0 mr-3" 
                    type="button"
                    onclick="toggleSidebar(); return false;"
                    style="border: none; background: transparent; font-size: 1.3rem;">
                <i class="fas fa-bars"></i>
            </button>
            
            <a class="navbar-brand d-lg-none" href="dashboard.php" style="color: #667eea; font-weight: 700;">
                <i class="fas fa-user-tie"></i>
                <span>Trainer</span>
            </a>
            
            <nav aria-label="breadcrumb" class="d-none d-md-block">
                <ol class="breadcrumb bg-transparent p-0 m-0">
                    <li class="breadcrumb-item">
                        <a href="dashboard.php" class="text-muted">
                            <i class="fas fa-home"></i>
                        </a>
                    </li>
                    <li class="breadcrumb-item active">
                        <?php echo ucwords(str_replace(['.php', '_'], ['', ' '], basename($_SERVER['PHP_SELF']))); ?>
                    </li>
                </ol>
            </nav>
        </div>
        
        <div class="d-flex align-items-center">
            <!-- Quick Stats -->
            <div class="d-none d-md-flex align-items-center mr-3">
                <span class="badge badge-warning mr-2" title="Pending Requests">
                    <i class="fas fa-clock"></i> <?php echo $pending_requests; ?>
                </span>
            </div>
            
            <!-- Notifications -->
            <div class="dropdown mr-3">
                <button class="btn btn-link text-dark position-relative p-0" 
                        data-toggle="dropdown"
                        style="border: none; background: transparent;">
                    <i class="fas fa-bell fa-lg"></i>
                    <?php if ($notif_count > 0): ?>
                        <span class="badge badge-danger" style="position: absolute; top: -5px; right: -5px; font-size: 9px; padding: 3px 6px; border-radius: 50%;">
                            <?php echo $notif_count; ?>
                        </span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-right" style="min-width: 300px;">
                    <h6 class="dropdown-header">Notifications</h6>
                    <div class="dropdown-divider"></div>
                    <a href="notifications.php" class="dropdown-item text-center text-primary">
                        <i class="fas fa-bell"></i> View All
                    </a>
                </div>
            </div>
            
            <!-- User Dropdown -->
            <div class="dropdown">
                <button class="btn btn-link text-dark dropdown-toggle d-flex align-items-center p-0" 
                        data-toggle="dropdown"
                        style="border: none; background: transparent;">
                    <img src="<?php echo $profile_pic; ?>" 
                         alt="Profile" 
                         class="rounded-circle" 
                         width="38" 
                         height="38"
                         style="object-fit: cover; border: 2px solid #e2e8f0;"
                         onerror="this.src='../assets/images/default-avatar.png'">
                    <span class="ml-2 d-none d-sm-inline" style="font-weight: 500;">
                        <?php echo htmlspecialchars($full_name); ?>
                    </span>
                </button>
                <div class="dropdown-menu dropdown-menu-right">
                    <div class="dropdown-header">
                        <strong><?php echo htmlspecialchars($full_name); ?></strong>
                        <br>
                        <small class="text-muted"><?php echo htmlspecialchars($trainer['specialization']); ?></small>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="profile.php" class="dropdown-item">
                        <i class="fas fa-user-circle"></i> My Profile
                    </a>
                    <a href="my-trainings.php" class="dropdown-item">
                        <i class="fas fa-chalkboard-teacher"></i> My Trainings
                    </a>
                    <a href="my-students.php" class="dropdown-item">
                        <i class="fas fa-users"></i> My Students
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="logout.php" class="dropdown-item text-danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>