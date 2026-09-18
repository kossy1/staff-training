<?php
// trainer/includes/header.php - Trainer Panel Header
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is trainer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trainer') {
    header('Location: ../login.php');
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);
$page_title = ucfirst(str_replace(['.php', '_'], ['', ' '], $current_page));

// Get trainer info
$trainer_id = $_SESSION['trainer_id'] ?? 0;
$trainer = $conn->query("SELECT * FROM trainers WHERE id = $trainer_id")->fetch_assoc();

if (!$trainer) {
    session_destroy();
    header('Location: ../login.php');
    exit();
}

$full_name = $trainer['first_name'] . ' ' . $trainer['last_name'];
$profile_pic = !empty($trainer['profile_picture']) ? '../uploads/trainers/' . $trainer['profile_picture'] : '../assets/images/default-avatar.png';

// Get unread notifications
$notif_count = 0;
$notif_result = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE user_id = {$_SESSION['user_id']} AND is_read = 0");
if ($notif_result) {
    $notif_count = $notif_result->fetch_assoc()['count'];
}

// Get pending requests count
$pending_requests = $conn->query("SELECT COUNT(*) as count FROM trainer_requests WHERE trainer_id = $trainer_id AND status = 'pending'")->fetch_assoc()['count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Trainer Panel | <?php echo SITE_SHORT_NAME; ?></title>
    
    <link rel="icon" href="../assets/images/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.min.css">
    
    <style>
        :root {
            --header-height: 70px;
            --sidebar-width: 260px;
            --primary: #667eea;
            --secondary: #764ba2;
        }
        
        body {
            padding-top: var(--header-height);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8f9fc;
            margin: 0;
            overflow-x: hidden;
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
            display: block !important;
        }
        
        .wrapper {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }
        
        .main-content {
            padding: 25px 30px;
            min-height: calc(100vh - var(--header-height) - 60px);
        }
        
        @media (max-width: 991.98px) {
            .wrapper { margin-left: 0; }
            .main-content {
                padding: 20px 15px;
                padding-top: 80px;
            }
        }
        
        @media print {
            .navbar, .sidebar, .sidebar-backdrop, .no-print, .sidebar-toggle-btn {
                display: none !important;
            }
            body { padding-top: 0 !important; }
            .wrapper { margin-left: 0 !important; }
            .main-content { padding: 20px !important; }
        }
    </style>
</head>
<body>
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
    <div class="wrapper">