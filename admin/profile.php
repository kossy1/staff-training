<?php
// admin/profile.php - Admin Profile (All Bugs Fixed)
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Null-safe helper
if (!function_exists('safeHtml')) {
    function safeHtml($value, $flags = ENT_QUOTES, $encoding = 'UTF-8') {
        return htmlspecialchars((string)($value ?? ''), $flags, $encoding);
    }
}

$user_id = $_SESSION['user_id'];
$employee_id = $_SESSION['employee_id'];

// ============================================
// FLASH MESSAGES (prevent re-submission)
// ============================================
$success = false;
$success_message = '';
$errors = [];

if (isset($_SESSION['flash_success'])) {
    $success = true;
    $success_message = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

if (isset($_SESSION['flash_errors'])) {
    $errors = $_SESSION['flash_errors'];
    unset($_SESSION['flash_errors']);
}

// ============================================
// LOAD PROFILE
// ============================================
$stmt = $conn->prepare("
    SELECT u.*, e.* 
    FROM users u 
    LEFT JOIN employees e ON u.employee_id = e.id 
    WHERE u.id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

if (!$profile) {
    header('Location: dashboard.php');
    exit();
}

$profile_picture_updated = false;

// ============================================
// IMAGE COMPRESSION
// ============================================
function compressImage($source, $destination, $ext, $quality = 85) {
    if (!extension_loaded('gd')) return false;
    
    $info = @getimagesize($source);
    if ($info === false) return false;
    
    $image = null;
    switch ($ext) {
        case 'jpg': case 'jpeg':
            if (function_exists('imagecreatefromjpeg')) $image = @imagecreatefromjpeg($source);
            break;
        case 'png':
            if (function_exists('imagecreatefrompng')) {
                $image = @imagecreatefrompng($source);
                if ($image) { imagealphablending($image, true); imagesavealpha($image, true); }
            }
            break;
        case 'gif':
            if (function_exists('imagecreatefromgif')) $image = @imagecreatefromgif($source);
            break;
        case 'webp':
            if (function_exists('imagecreatefromwebp')) $image = @imagecreatefromwebp($source);
            break;
        default: return false;
    }
    
    if (!$image) return false;
    
    $width = imagesx($image);
    $height = imagesy($image);
    $max_dimension = 500;
    
    if ($width > $max_dimension || $height > $max_dimension) {
        $ratio = min($max_dimension / $width, $max_dimension / $height);
        $new_width = round($width * $ratio);
        $new_height = round($height * $ratio);
        
        $resized = imagecreatetruecolor($new_width, $new_height);
        if ($ext == 'png') {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
            imagefilledrectangle($resized, 0, 0, $new_width, $new_height, $transparent);
        }
        
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        imagedestroy($image);
        $image = $resized;
    }
    
    $result = false;
    switch ($ext) {
        case 'jpg': case 'jpeg': $result = imagejpeg($image, $destination, $quality); break;
        case 'png': $result = imagepng($image, $destination, 9); break;
        case 'gif': $result = imagegif($image, $destination); break;
        case 'webp': $result = function_exists('imagewebp') ? imagewebp($image, $destination, $quality) : false; break;
    }
    
    imagedestroy($image);
    return $result;
}

// ============================================
// UPLOAD PROFILE PICTURE
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'upload_picture') {
    $upload_errors = [];
    
    if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] == UPLOAD_ERR_NO_FILE) {
        $upload_errors[] = "Please select a file to upload.";
    } elseif ($_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
        $upload_errors[] = "Upload error (code: {$_FILES['profile_picture']['error']})";
    } else {
        $file = $_FILES['profile_picture'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $max_size = 5 * 1024 * 1024;
        
        if (!in_array($ext, $allowed)) {
            $upload_errors[] = "Invalid file type. Allowed: JPG, PNG, GIF, WEBP";
        } elseif ($file['size'] > $max_size) {
            $upload_errors[] = "File too large. Max: 5MB";
        } else {
            $upload_dir = '../uploads/profile-pictures/';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
            
            if (!empty($profile['profile_picture'])) {
                $old = $upload_dir . $profile['profile_picture'];
                if (file_exists($old)) @unlink($old);
            }
            
            $new_filename = 'profile_' . $employee_id . '_' . time() . '.' . $ext;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                if (extension_loaded('gd')) {
                    @compressImage($upload_path, $upload_path, $ext);
                }
                
                $stmt = $conn->prepare("UPDATE employees SET profile_picture = ? WHERE id = ?");
                $stmt->bind_param("si", $new_filename, $employee_id);
                
                if ($stmt->execute()) {
                    logAction($_SESSION['user_id'], 'profile_picture_updated');
                    $_SESSION['flash_success'] = 'Profile picture updated successfully!';
                    header('Location: profile.php');
                    exit();
                } else {
                    $upload_errors[] = "Database error: " . $conn->error;
                    @unlink($upload_path);
                }
            } else {
                $upload_errors[] = "Failed to save file. Check folder permissions.";
            }
        }
    }
    
    if (!empty($upload_errors)) {
        $_SESSION['flash_errors'] = $upload_errors;
        header('Location: profile.php');
        exit();
    }
}

// ============================================
// UPDATE PROFILE
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_profile') {
    $update_errors = [];
    
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    
    if (empty($first_name)) $update_errors[] = "First name is required";
    if (empty($last_name)) $update_errors[] = "Last name is required";
    
    if (empty($update_errors)) {
        $has_bio = $conn->query("SHOW COLUMNS FROM employees LIKE 'bio'")->num_rows > 0;
        
        if ($has_bio) {
            $stmt = $conn->prepare("UPDATE employees SET first_name=?, last_name=?, phone=?, department=?, position=?, bio=? WHERE id=?");
            $stmt->bind_param("ssssssi", $first_name, $last_name, $phone, $department, $position, $bio, $employee_id);
        } else {
            $stmt = $conn->prepare("UPDATE employees SET first_name=?, last_name=?, phone=?, department=?, position=? WHERE id=?");
            $stmt->bind_param("sssssi", $first_name, $last_name, $phone, $department, $position, $employee_id);
        }
        
        if ($stmt->execute()) {
            logAction($_SESSION['user_id'], 'profile_updated');
            $_SESSION['flash_success'] = 'Profile updated successfully!';
            header('Location: profile.php');
            exit();
        } else {
            $update_errors[] = "Failed to update profile.";
        }
    }
    
    if (!empty($update_errors)) {
        $_SESSION['flash_errors'] = $update_errors;
        header('Location: profile.php');
        exit();
    }
}

// ============================================
// CHANGE PASSWORD
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'change_password') {
    $pwd_errors = [];
    
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    if (empty($current)) $pwd_errors[] = "Current password is required";
    if (empty($new)) $pwd_errors[] = "New password is required";
    if (strlen($new) < 6) $pwd_errors[] = "New password must be at least 6 characters";
    if ($new !== $confirm) $pwd_errors[] = "Passwords do not match";
    
    if (empty($pwd_errors)) {
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $u = $stmt->get_result()->fetch_assoc();
        
        if (password_verify($current, $u['password'])) {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ?, password_changed_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $hashed, $user_id);
            
            if ($stmt->execute()) {
                logAction($_SESSION['user_id'], 'password_changed');
                $_SESSION['flash_success'] = 'Password changed successfully!';
                header('Location: profile.php');
                exit();
            }
        } else {
            $pwd_errors[] = "Current password is incorrect";
        }
    }
    
    if (!empty($pwd_errors)) {
        $_SESSION['flash_errors'] = $pwd_errors;
        header('Location: profile.php');
        exit();
    }
}

// ============================================
// ⭐ BUILD PROFILE PICTURE URL (FIXED)
// ============================================
$default_avatar = '../assets/images/default-avatar.png';
$profile_pic = $default_avatar; // Start with default

if (!empty($profile['profile_picture'])) {
    $file_path = '../uploads/profile-pictures/' . $profile['profile_picture'];
    
    if (file_exists($file_path)) {
        // Cache-busting: append file modification time
        $profile_pic = $file_path . '?v=' . filemtime($file_path);
    }
}

$page_title = 'My Profile';
$page_scripts = '
<script>
$(document).ready(function() {
    $("#profile_picture_input").on("change", function() {
        const file = this.files[0];
        if (!file) return;
        
        if (file.size > 5 * 1024 * 1024) {
            Swal.fire({icon: "error", title: "File Too Large", text: "Maximum 5MB"});
            this.value = "";
            return;
        }
        
        const valid = ["image/jpeg", "image/jpg", "image/png", "image/gif", "image/webp"];
        if (!valid.includes(file.type)) {
            Swal.fire({icon: "error", title: "Invalid Type", text: "Only JPG, PNG, GIF, WEBP allowed"});
            this.value = "";
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            $("#profilePreview").attr("src", e.target.result);
            $("#uploadButton").show();
        };
        reader.readAsDataURL(file);
    });
});

function togglePassword(inputId, btn) {
    var input = document.getElementById(inputId);
    var icon = btn.querySelector("i");
    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
    } else {
        input.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    }
}
</script>
';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<style>
.profile-header-card {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    border-radius: 20px;
    padding: 40px;
    color: white;
    margin-bottom: 30px;
    position: relative;
    overflow: hidden;
}
.profile-header-card::before {
    content: '';
    position: absolute;
    top: -50%; right: -10%;
    width: 400px; height: 400px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(102, 126, 234, 0.2) 0%, transparent 70%);
}
.profile-header-card .profile-avatar {
    width: 150px; height: 150px;
    border-radius: 50%;
    object-fit: cover;
    border: 5px solid rgba(255, 255, 255, 0.2);
    position: relative;
    z-index: 1;
    transition: all 0.3s ease;
    background: #2d2d44;
}
.profile-header-card .profile-avatar:hover {
    border-color: #667eea;
    transform: scale(1.05);
}
.profile-header-card h2 {
    font-weight: 800;
    margin-bottom: 5px;
    position: relative;
    z-index: 1;
}
.profile-header-card .profile-role {
    color: #667eea;
    font-weight: 600;
    font-size: 1.1rem;
    position: relative;
    z-index: 1;
}
.profile-header-card .profile-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-top: 15px;
    position: relative;
    z-index: 1;
}
.profile-header-card .profile-meta span {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.9rem;
}
.profile-header-card .profile-meta i {
    color: #667eea;
    margin-right: 5px;
}

.profile-picture-container {
    position: relative;
    width: 180px; height: 180px;
    margin: 0 auto;
    border-radius: 50%;
    overflow: hidden;
    cursor: pointer;
    border: 4px solid #e2e8f0;
    transition: all 0.3s ease;
    background: #f8f9fa;
}
.profile-picture-container:hover {
    border-color: #667eea;
    transform: scale(1.02);
}
.profile-picture {
    width: 100%; height: 100%;
    object-fit: cover;
}
.profile-picture-overlay {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    color: white;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
    border-radius: 50%;
}
.profile-picture-container:hover .profile-picture-overlay { opacity: 1; }
.profile-picture-overlay i { font-size: 2rem; margin-bottom: 5px; }
.profile-picture-overlay span { font-size: 0.85rem; font-weight: 600; }

.info-item { margin-bottom: 15px; }
.info-item label {
    display: block;
    font-size: 0.75rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 3px;
    font-weight: 600;
}
.info-item .value {
    font-weight: 600;
    color: #2d3748;
    font-size: 0.95rem;
}

@media (max-width: 768px) {
    .profile-header-card { padding: 25px; text-align: center; }
    .profile-header-card .profile-meta { justify-content: center; }
    .profile-avatar { width: 120px !important; height: 120px !important; margin-bottom: 15px; }
    .profile-picture-container { width: 140px; height: 140px; }
}
</style>

<div class="main-content">
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?php echo safeHtml($success_message); ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i>
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                    <li><?php echo safeHtml($e); ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Profile Header -->
    <div class="profile-header-card">
        <div class="row align-items-center">
            <div class="col-md-3 text-center text-md-left">
                <img src="<?php echo safeHtml($profile_pic); ?>" 
                     alt="Profile" 
                     class="profile-avatar"
                     onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMDAiIGhlaWdodD0iMjAwIiB2aWV3Qm94PSIwIDAgMjAwIDIwMCI+PGRlZnM+PGxpbmVhckdyYWRpZW50IGlkPSJnIiB4MT0iMCUiIHkxPSIwJSIgeDI9IjEwMCUiIHkyPSIxMDAlIj48c3RvcCBvZmZzZXQ9IjAlIiBzdHlsZT0ic3RvcC1jb2xvcjojNjY3ZWVhIi8+PHN0b3Agb2Zmc2V0PSIxMDAlIiBzdHlsZT0ic3RvcC1jb2xvcjojNzY0YmEyIi8+PC9saW5lYXJHcmFkaWVudD48L2RlZnM+PGNpcmNsZSBjeD0iMTAwIiBjeT0iMTAwIiByPSIxMDAiIGZpbGw9InVybCgjZykiLz48Y2lyY2xlIGN4PSIxMDAiIGN5PSI4MCIgcj0iMzUiIGZpbGw9IndoaXRlIi8+PGVsbGlwc2UgY3g9IjEwMCIgY3k9IjE3MCIgcng9IjY1IiByeT0iNDUiIGZpbGw9IndoaXRlIi8+PC9zdmc+';">
            </div>
            <div class="col-md-6 text-center text-md-left">
                <h2><?php echo safeHtml(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? '')); ?></h2>
                <div class="profile-role"><?php echo safeHtml($profile['position'] ?? 'Administrator'); ?></div>
                <div class="profile-meta">
                    <span><i class="fas fa-id-badge"></i> <?php echo safeHtml($profile['employee_code'] ?? 'N/A'); ?></span>
                    <span><i class="fas fa-building"></i> <?php echo safeHtml($profile['department'] ?? 'N/A'); ?></span>
                    <span><i class="fas fa-envelope"></i> <?php echo safeHtml($profile['email'] ?? ''); ?></span>
                    <span><i class="fas fa-phone"></i> <?php echo safeHtml($profile['phone'] ?? 'N/A'); ?></span>
                </div>
            </div>
            <div class="col-md-3 text-center text-md-right mt-3 mt-md-0">
                <span class="badge badge-success" style="padding: 8px 20px; font-size: 0.9rem;">
                    <i class="fas fa-circle" style="font-size: 8px;"></i> Active Admin
                </span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-camera"></i> Profile Picture</h5>
                </div>
                <div class="card-body text-center">
                    <div class="profile-picture-container">
                        <img id="profilePreview" 
                             src="<?php echo safeHtml($profile_pic); ?>" 
                             alt="Profile Picture" 
                             class="profile-picture"
                             onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMDAiIGhlaWdodD0iMjAwIiB2aWV3Qm94PSIwIDAgMjAwIDIwMCI+PGRlZnM+PGxpbmVhckdyYWRpZW50IGlkPSJnIiB4MT0iMCUiIHkxPSIwJSIgeDI9IjEwMCUiIHkyPSIxMDAlIj48c3RvcCBvZmZzZXQ9IjAlIiBzdHlsZT0ic3RvcC1jb2xvcjojNjY3ZWVhIi8+PHN0b3Agb2Zmc2V0PSIxMDAlIiBzdHlsZT0ic3RvcC1jb2xvcjojNzY0YmEyIi8+PC9saW5lYXJHcmFkaWVudD48L2RlZnM+PGNpcmNsZSBjeD0iMTAwIiBjeT0iMTAwIiByPSIxMDAiIGZpbGw9InVybCgjZykiLz48Y2lyY2xlIGN4PSIxMDAiIGN5PSI4MCIgcj0iMzUiIGZpbGw9IndoaXRlIi8+PGVsbGlwc2UgY3g9IjEwMCIgY3k9IjE3MCIgcng9IjY1IiByeT0iNDUiIGZpbGw9IndoaXRlIi8+PC9zdmc+';">
                        <div class="profile-picture-overlay">
                            <i class="fas fa-camera"></i>
                            <span>Change Photo</span>
                        </div>
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data" class="mt-3" id="uploadForm">
                        <input type="hidden" name="action" value="upload_picture">
                        
                        <div class="custom-file">
                            <input type="file" 
                                   class="custom-file-input" 
                                   id="profile_picture_input" 
                                   name="profile_picture" 
                                   accept="image/*">
                            <label class="custom-file-label" for="profile_picture_input">
                                <i class="fas fa-folder-open"></i> Choose image...
                            </label>
                        </div>
                        
                        <button type="submit" 
                                id="uploadButton" 
                                class="btn btn-primary btn-block mt-3" 
                                style="display: none;">
                            <i class="fas fa-upload"></i> Upload Photo
                        </button>
                    </form>
                    
                    <div class="mt-3 text-muted small">
                        <i class="fas fa-info-circle"></i> Max 5MB • JPG, PNG, GIF, WEBP
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Account Information</h5>
                </div>
                <div class="card-body">
                    <div class="info-item">
                        <label>Username</label>
                        <div class="value"><?php echo safeHtml($profile['username'] ?? ''); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Email</label>
                        <div class="value"><?php echo safeHtml($profile['email'] ?? ''); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Role</label>
                        <div class="value"><span class="badge badge-primary">Administrator</span></div>
                    </div>
                    <div class="info-item mb-0">
                        <label>Member Since</label>
                        <div class="value"><?php echo formatDate($profile['created_at'] ?? ''); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user-edit"></i> Edit Profile</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">First Name</label>
                                    <input type="text" name="first_name" class="form-control" 
                                           value="<?php echo safeHtml($profile['first_name'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">Last Name</label>
                                    <input type="text" name="last_name" class="form-control" 
                                           value="<?php echo safeHtml($profile['last_name'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Phone Number</label>
                                    <input type="tel" name="phone" class="form-control" 
                                           value="<?php echo safeHtml($profile['phone'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Department</label>
                                    <input type="text" name="department" class="form-control" 
                                           value="<?php echo safeHtml($profile['department'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Position</label>
                            <input type="text" name="position" class="form-control" 
                                   value="<?php echo safeHtml($profile['position'] ?? ''); ?>">
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-key"></i> Change Password</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="form-group">
                            <label class="required">Current Password</label>
                            <div class="input-group">
                                <input type="password" name="current_password" id="current_password" 
                                       class="form-control" required>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary" 
                                            onclick="togglePassword('current_password', this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="required">New Password</label>
                            <input type="password" name="new_password" id="new_password" 
                                   class="form-control" required minlength="6">
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="required">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-key"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>