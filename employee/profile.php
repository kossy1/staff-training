<?php
// employee/profile.php - Employee Profile with Working Image Upload
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isEmployee()) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$employee_id = $_SESSION['employee_id'];

// Get user and employee data
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

$errors = [];
$success = false;
$profile_picture_updated = false;

// ===== HANDLE PROFILE PICTURE UPLOAD =====
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_picture'])) {
    
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
        
        $file = $_FILES['profile_picture'];
        $filename = $file['name'];
        $tmp_name = $file['tmp_name'];
        $file_size = $file['size'];
        
        // Get file extension
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        // Validate
        if (!in_array($ext, $allowed)) {
            $errors[] = "Invalid file type. Allowed: " . implode(', ', $allowed);
        } elseif ($file_size > $max_size) {
            $errors[] = "File is too large. Maximum size is " . ($max_size / 1024 / 1024) . "MB.";
        } else {
            // Create upload directory
            $upload_dir = '../uploads/profile-pictures/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Delete old profile picture
            if (!empty($profile['profile_picture']) && file_exists($upload_dir . $profile['profile_picture'])) {
                unlink($upload_dir . $profile['profile_picture']);
            }
            
            // Generate unique filename
            $new_filename = 'profile_' . $employee_id . '_' . time() . '.' . $ext;
            $upload_path = $upload_dir . $new_filename;
            
            // Move uploaded file
            if (move_uploaded_file($tmp_name, $upload_path)) {
                // Update database
                $stmt = $conn->prepare("UPDATE employees SET profile_picture = ? WHERE id = ?");
                $stmt->bind_param("si", $new_filename, $employee_id);
                
                if ($stmt->execute()) {
                    $profile_picture_updated = true;
                    $success = true;
                    $_SESSION['profile_picture'] = $new_filename;
                    
                    // Refresh profile data
                    $stmt = $conn->prepare("
                        SELECT u.*, e.* 
                        FROM users u 
                        LEFT JOIN employees e ON u.employee_id = e.id 
                        WHERE u.id = ?
                    ");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $profile = $stmt->get_result()->fetch_assoc();
                    
                    logAction($_SESSION['user_id'], 'profile_picture_updated');
                } else {
                    $errors[] = "Failed to update database: " . $conn->error;
                    if (file_exists($upload_path)) {
                        unlink($upload_path);
                    }
                }
            } else {
                $errors[] = "Failed to upload image. Please check directory permissions.";
            }
        }
    } else {
        $error_code = $_FILES['profile_picture']['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error_code == UPLOAD_ERR_NO_FILE) {
            $errors[] = "Please select a file to upload.";
        } else {
            $errors[] = "Upload error. Please try again.";
        }
    }
}

// ===== HANDLE PROFILE UPDATE =====
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    
    if (empty($errors)) {
        // Check if bio column exists
        $columns = $conn->query("SHOW COLUMNS FROM employees LIKE 'bio'");
        $has_bio = $columns->num_rows > 0;
        
        if ($has_bio) {
            $stmt = $conn->prepare("
                UPDATE employees SET 
                    first_name = ?, last_name = ?, phone = ?, bio = ?
                WHERE id = ?
            ");
            $stmt->bind_param("ssssi", $first_name, $last_name, $phone, $bio, $employee_id);
        } else {
            $stmt = $conn->prepare("
                UPDATE employees SET 
                    first_name = ?, last_name = ?, phone = ?
                WHERE id = ?
            ");
            $stmt->bind_param("sssi", $first_name, $last_name, $phone, $employee_id);
        }
        
        if ($stmt->execute()) {
            $success = true;
            
            // Refresh profile data
            $stmt = $conn->prepare("
                SELECT u.*, e.* 
                FROM users u 
                LEFT JOIN employees e ON u.employee_id = e.id 
                WHERE u.id = ?
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $profile = $stmt->get_result()->fetch_assoc();
            
            logAction($_SESSION['user_id'], 'profile_updated');
        } else {
            $errors[] = "Failed to update profile: " . $conn->error;
        }
    }
}

// ===== HANDLE PASSWORD CHANGE =====
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password)) $errors[] = "Current password is required";
    if (empty($new_password)) $errors[] = "New password is required";
    if (strlen($new_password) < 6) $errors[] = "New password must be at least 6 characters";
    if ($new_password !== $confirm_password) $errors[] = "Passwords do not match";
    
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if (password_verify($current_password, $user['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt->execute()) {
                $success = true;
                logAction($_SESSION['user_id'], 'password_changed');
            }
        } else {
            $errors[] = "Current password is incorrect";
        }
    }
}

$page_title = 'My Profile';
$page_scripts = '
<script>
$(document).ready(function() {
    // Preview profile picture
    $("#profile_picture_input").on("change", function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $("#profilePreview").attr("src", e.target.result);
                $("#fileInfo").text("Selected: " + file.name + " (" + (file.size / 1024).toFixed(1) + " KB)");
                $("#uploadButton").prop("disabled", false);
            };
            reader.readAsDataURL(file);
        } else {
            $("#fileInfo").text("");
            $("#uploadButton").prop("disabled", true);
        }
    });
    
    // Password strength
    $("#new_password").on("keyup", function() {
        const password = $(this).val();
        const strength = getPasswordStrength(password);
        const indicator = $("#passwordStrength");
        
        if (password.length === 0) {
            indicator.html("").removeClass("visible");
            return;
        }
        
        indicator.addClass("visible");
        let color = "danger";
        let text = "Weak";
        let width = "25%";
        
        if (strength >= 4) {
            color = "success";
            text = "Strong";
            width = "100%";
        } else if (strength >= 3) {
            color = "primary";
            text = "Good";
            width = "75%";
        } else if (strength >= 2) {
            color = "warning";
            text = "Fair";
            width = "50%";
        }
        
        indicator.html(`
            <div class="progress" style="height: 5px; margin-top: 5px;">
                <div class="progress-bar bg-${color}" style="width: ${width};"></div>
            </div>
            <small class="text-${color}">${text} password</small>
        `);
    });
});

function getPasswordStrength(password) {
    let strength = 0;
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]/)) strength++;
    if (password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    if (password.match(/[^a-zA-Z0-9]/)) strength++;
    return strength;
}

function removeProfilePicture() {
    Swal.fire({
        title: "Remove Profile Picture?",
        text: "This action cannot be undone.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Yes, remove it!"
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "remove-profile-picture.php";
        }
    });
}
</script>
';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h1><i class="fas fa-user-circle text-primary"></i> My Profile</h1>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> 
            <?php echo $profile_picture_updated ? 'Profile picture updated successfully!' : 'Profile updated successfully!'; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i>
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Left Column - Profile Picture -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-image"></i> Profile Picture</h5>
                </div>
                <div class="card-body text-center">
                    <!-- Profile Picture Display -->
                    <div class="profile-picture-container">
                        <img id="profilePreview" 
                             src="<?php 
                                $profile_pic = !empty($profile['profile_picture']) ? 
                                    '../uploads/profile-pictures/' . $profile['profile_picture'] : 
                                    '../assets/images/default-avatar.png';
                                echo $profile_pic; 
                            ?>" 
                             alt="Profile Picture" 
                             class="profile-picture"
                             onerror="this.src='../assets/images/default-avatar.png'">
                        <div class="profile-picture-overlay">
                            <i class="fas fa-camera"></i>
                            <span>Change Photo</span>
                        </div>
                    </div>
                    
                    <!-- Upload Form -->
                    <form method="POST" enctype="multipart/form-data" class="mt-3">
                        <input type="hidden" name="MAX_FILE_SIZE" value="5242880">
                        
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" 
                                   id="profile_picture_input" name="profile_picture" 
                                   accept="image/*">
                            <label class="custom-file-label" for="profile_picture_input">
                                <i class="fas fa-upload"></i> Choose image...
                            </label>
                        </div>
                        <div id="fileInfo" class="text-muted small mt-1"></div>
                        
                        <button type="submit" name="upload_picture" id="uploadButton" class="btn btn-primary btn-block mt-2">
                            <i class="fas fa-upload"></i> Upload Photo
                        </button>
                    </form>
                    
                    <?php if (!empty($profile['profile_picture'])): ?>
                        <button onclick="removeProfilePicture()" class="btn btn-danger btn-block mt-2">
                            <i class="fas fa-trash-alt"></i> Remove Photo
                        </button>
                    <?php endif; ?>
                    
                    <div class="mt-3 text-muted small">
                        <i class="fas fa-info-circle"></i> 
                        Recommended: Square image, max 5MB.<br>
                        Supported: JPG, JPEG, PNG, GIF, WEBP
                    </div>
                </div>
            </div>

            <!-- Account Info -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Account Info</h5>
                </div>
                <div class="card-body">
                    <div class="profile-info-item">
                        <label>Username</label>
                        <p class="font-weight-bold"><?php echo htmlspecialchars($profile['username']); ?></p>
                    </div>
                    <div class="profile-info-item">
                        <label>Email</label>
                        <p class="font-weight-bold"><?php echo htmlspecialchars($profile['email']); ?></p>
                    </div>
                    <div class="profile-info-item">
                        <label>Role</label>
                        <p><span class="badge badge-info">Employee</span></p>
                    </div>
                    <div class="profile-info-item">
                        <label>Employee ID</label>
                        <p class="font-weight-bold"><?php echo htmlspecialchars($profile['employee_code'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="profile-info-item">
                        <label>Department</label>
                        <p class="font-weight-bold"><?php echo htmlspecialchars($profile['department'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="profile-info-item">
                        <label>Position</label>
                        <p class="font-weight-bold"><?php echo htmlspecialchars($profile['position'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="profile-info-item">
                        <label>Member Since</label>
                        <p class="font-weight-bold"><?php echo formatDate($profile['created_at']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-8">
            <!-- Profile Information -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user-edit"></i> Edit Profile</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">First Name</label>
                                    <input type="text" name="first_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($profile['first_name']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">Last Name</label>
                                    <input type="text" name="last_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($profile['last_name']); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>">
                        </div>

                        <?php 
                        // Check if bio column exists
                        $columns = $conn->query("SHOW COLUMNS FROM employees LIKE 'bio'");
                        $has_bio = $columns->num_rows > 0;
                        if ($has_bio): 
                        ?>
                        <div class="form-group">
                            <label>Bio / About</label>
                            <textarea name="bio" class="form-control" rows="3" 
                                      placeholder="Tell us a little about yourself..."><?php echo htmlspecialchars($profile['bio'] ?? ''); ?></textarea>
                        </div>
                        <?php endif; ?>

                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </form>
                </div>
            </div>

            <!-- Change Password -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-key"></i> Change Password</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label class="required">Current Password</label>
                            <div class="input-group">
                                <input type="password" name="current_password" id="current_password" 
                                       class="form-control" required>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary" id="showPassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="required">New Password</label>
                            <input type="password" name="new_password" id="new_password" 
                                   class="form-control" required minlength="6">
                            <div id="passwordStrength"></div>
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="required">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn btn-warning">
                            <i class="fas fa-key"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.profile-picture-container {
    position: relative;
    width: 200px;
    height: 200px;
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
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.profile-picture-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
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
.profile-picture-container:hover .profile-picture-overlay {
    opacity: 1;
}
.profile-picture-overlay i {
    font-size: 2rem;
    margin-bottom: 5px;
}
.profile-picture-overlay span {
    font-size: 0.85rem;
    font-weight: 600;
}
.profile-info-item {
    margin-bottom: 12px;
}
.profile-info-item label {
    display: block;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6c757d;
    margin-bottom: 2px;
}
.profile-info-item p {
    margin: 0;
    font-size: 0.95rem;
}
#passwordStrength {
    margin-top: 5px;
    min-height: 30px;
}
#passwordStrength .progress {
    max-width: 200px;
}
.custom-file-label::after {
    content: "Browse";
}
#fileInfo {
    font-size: 0.8rem;
    color: #6c757d;
}
@media (max-width: 992px) {
    .profile-picture-container {
        width: 150px;
        height: 150px;
    }
}
@media (max-width: 576px) {
    .profile-picture-container {
        width: 120px;
        height: 120px;
    }
}
</style>

<script>
// Show/Hide Password
$('#showPassword').on('click', function() {
    const input = $('#current_password');
    const icon = $(this).find('i');
    if (input.attr('type') === 'password') {
        input.attr('type', 'text');
        icon.removeClass('fa-eye').addClass('fa-eye-slash');
    } else {
        input.attr('type', 'password');
        icon.removeClass('fa-eye-slash').addClass('fa-eye');
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>