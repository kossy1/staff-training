<?php
// employee/includes/header.php - Employee Panel Header (InfinityFree Compatible)
if (!isset($_SESSION)) {
    session_start();
}

// Check if user is logged in and is employee
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header('Location: ../login.php');
    exit();
}

// Get current page name
$current_page = basename($_SERVER['PHP_SELF']);
$page_title = ucfirst(str_replace(['.php', '_'], ['', ' '], $current_page));

// Get user info
$user_id = $_SESSION['user_id'] ?? 0;
$employee_id = $_SESSION['employee_id'] ?? 0;
$username = $_SESSION['username'] ?? 'Employee';

// Get employee details
$employee = getEmployeeById($employee_id);
$full_name = $employee ? $employee['first_name'] . ' ' . $employee['last_name'] : 'Employee';
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
    <title><?php echo $page_title . ' - ' . SITE_SHORT_NAME; ?></title>
    
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
    
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.min.css">
    
    <!-- Custom Employee CSS -->
    <link rel="stylesheet" href="assets/css/employee-style.css">
    
    <!-- CSRF Token -->
    <?php
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    ?>
    
    <style>
        :root {
            --header-height: 70px;
            --sidebar-width: 260px;
            --primary: #667eea;
            --secondary: #764ba2;
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            padding-top: var(--header-height);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8f9fc;
            margin: 0;
            overflow-x: hidden;
        }
        
        /* ===== SIDEBAR BACKDROP ===== */
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
            display: block !important;
        }
        
        /* ===== MAIN WRAPPER ===== */
        .wrapper {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }
        
        .main-content {
            padding: 25px 30px;
            min-height: calc(100vh - var(--header-height) - 60px);
        }
        
        /* ===== MOBILE RESPONSIVE ===== */
        @media (max-width: 991.98px) {
            .wrapper {
                margin-left: 0;
            }
            
            .main-content {
                padding: 20px 15px;
                padding-top: 80px;
            }
        }
        
        @media (max-width: 576px) {
            .main-content {
                padding: 15px 10px;
                padding-top: 80px;
            }
        }
        
        /* ===== PRINT STYLES ===== */
        @media print {
            .navbar, 
            .sidebar, 
            .sidebar-backdrop, 
            .no-print,
            .sidebar-toggle-btn {
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
    <!-- Sidebar Backdrop - MUST BE FIRST for z-index -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
    
    <!-- Main Wrapper -->
    <div class="wrapper">