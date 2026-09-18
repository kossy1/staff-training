<?php
// employee/includes/navbar.php - Employee Navigation Bar
$employee = getEmployeeById($_SESSION['employee_id'] ?? 0);
$full_name = $employee ? $employee['first_name'] . ' ' . $employee['last_name'] : 'Employee';
$profile_pic = $employee && !empty($employee['profile_picture']) ? '../uploads/profile-pictures/' . $employee['profile_picture'] : '../assets/images/default-avatar.png';
?>
<!-- Top Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top" id="mainNav" style="height: 70px; padding: 0 20px; z-index: 1030;">
    <div class="container-fluid">
        <!-- Left Side -->
        <div class="d-flex align-items-center">
            <!-- Mobile Sidebar Toggle -->
            <button class="btn btn-link text-dark d-lg-none p-0 mr-3" 
                    type="button"
                    id="sidebarToggle"
                    onclick="toggleSidebar(); return false;"
                    style="border: none; background: transparent; font-size: 1.3rem;">
                <i class="fas fa-bars"></i>
            </button>
            
            <!-- Brand (Mobile) -->
            <a class="navbar-brand d-lg-none" href="dashboard.php" style="color: #667eea; font-weight: 700;">
                <i class="fas fa-graduation-cap"></i>
                <span>SDC</span>
            </a>
            
            <!-- Breadcrumb (Desktop) -->
            <nav aria-label="breadcrumb" class="d-none d-md-block">
                <ol class="breadcrumb bg-transparent p-0 m-0">
                    <li class="breadcrumb-item">
                        <a href="dashboard.php" class="text-muted">
                            <i class="fas fa-home"></i>
                        </a>
                    </li>
                    <li class="breadcrumb-item active">
                        <?php 
                            $page_name = str_replace(['.php', '_'], ['', ' '], basename($_SERVER['PHP_SELF']));
                            echo ucwords($page_name);
                        ?>
                    </li>
                </ol>
            </nav>
        </div>
        
        <!-- Right Side -->
        <div class="d-flex align-items-center">
            <!-- Notification Bell -->
            <div class="dropdown mr-3">
                <button class="btn btn-link text-dark position-relative p-0" 
                        id="notificationDropdown" 
                        data-toggle="dropdown"
                        style="border: none; background: transparent;">
                    <i class="fas fa-bell fa-lg"></i>
                    <?php if ($notif_count > 0): ?>
                        <span class="badge badge-danger" style="position: absolute; top: -5px; right: -5px; font-size: 9px; padding: 3px 6px; border-radius: 50%;">
                            <?php echo $notif_count; ?>
                        </span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-right">
                    <div class="dropdown-header">
                        <strong>Notifications</strong>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="notifications.php" class="dropdown-item text-center text-primary">
                        <i class="fas fa-bell"></i> View All
                    </a>
                </div>
            </div>
            
            <!-- User Dropdown -->
            <div class="dropdown">
                <button class="btn btn-link text-dark dropdown-toggle d-flex align-items-center p-0" 
                        id="userDropdown" 
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
                        <small class="text-muted">@<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></small>
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