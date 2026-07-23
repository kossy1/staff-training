<?php
// admin/includes/header.php - Admin Panel Header
if (!isset($_SESSION)) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get current page name
$current_page = basename($_SERVER['PHP_SELF']);
$page_title = ucfirst(str_replace(['.php', '_'], ['', ' '], $current_page));

// Get user info for header
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title . ' - ' . SITE_NAME; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" href="../assets/images/favicon.ico" type="image/x-icon">
    
    <!-- Bootstrap 4 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    
    <!-- Font Awesome 5 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">
    
    <!-- Select2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.min.css">
    
    <!-- FullCalendar CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/main.min.css">
    
    <!-- Custom Admin CSS -->
    <link rel="stylesheet" href="assets/css/admin-style.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    
    <!-- CSRF Token -->
    <?php
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    ?>
    
    <style>
        /* Additional header specific styles */
        :root {
            --header-height: 70px;
            --sidebar-width: 280px;
        }
        
        body {
            padding-top: var(--header-height);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        /* Sidebar backdrop */
        .sidebar-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 998;
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
        }
        
        .sidebar-backdrop.show {
            display: block;
        }
        
        /* Main wrapper */
        .wrapper {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }
        
        .main-content {
            padding: 25px 30px;
            min-height: calc(100vh - var(--header-height) - 60px);
        }
        
        @media (max-width: 992px) {
            .wrapper {
                margin-left: 0;
            }
        }
        
        /* Print styles */
        @media print {
            .navbar,
            .sidebar,
            .sidebar-backdrop,
            .no-print {
                display: none !important;
            }
            body {
                padding-top: 0 !important;
            }
            .wrapper {
                margin-left: 0 !important;
            }
            .main-content {
                padding: 20px !important;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Backdrop (Mobile) -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
    
    <!-- Main Wrapper -->
    <div class="wrapper">