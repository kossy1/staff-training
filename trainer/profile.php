<?php
// trainer/profile.php - Trainer Profile
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'trainer') {
    header('Location: ../login.php');
    exit();
}

$trainer_id = $_SESSION['trainer_id'];
$trainer = $conn->query("SELECT * FROM trainers WHERE id = $trainer_id")->fetch_assoc();

$message = '';
$error = '';

// Update profile
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $phone = trim($_POST['phone']);
    $specialization = trim($_POST['specialization']);
    $qualification = trim($_POST['qualification']);
    $bio = trim($_POST['bio']);
    $linkedin = trim($_POST['linkedin_url']);
    
    $profile_picture = $trainer['profile_picture'];
    
    // Handle image upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $upload_dir = '../uploads/trainers/';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $new_filename = 'trainer_' . $trainer_id . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_dir . $new_filename)) {
                if (!empty($trainer['profile_picture']) && file_exists($upload_dir . $trainer['profile_picture'])) {
                    unlink($upload_dir . $trainer['profile_picture']);
                }
                $profile_picture = $new_filename;
            }
        }
    }
    
    $stmt = $conn->prepare("
        UPDATE trainers SET 
            first_name = ?, last_name = ?, phone = ?, specialization = ?,
            qualification = ?, bio = ?, linkedin_url = ?, profile_picture = ?
        WHERE id = ?
    ");
    $stmt->bind_param("ssssssssi", 
        $first_name, $last_name, $phone, $specialization,
        $qualification, $bio, $linkedin, $profile_picture, $trainer_id
    );
    
    if ($stmt->execute()) {
        $message = "Profile updated successfully!";
        $trainer = $conn->query("SELECT * FROM trainers WHERE id = $trainer_id")->fetch_assoc();
    } else {
        $error = "Update failed: " . $conn->error;
    }
}

$page_title = 'My Profile';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h1><i class="fas fa-user-circle text-primary"></i> My Profile</h1>
        <p class="text-muted">Manage your personal information</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <img id="previewImage" 
                         src="<?php echo !empty($trainer['profile_picture']) ? '../uploads/trainers/' . $trainer['profile_picture'] : '../assets/images/default-avatar.png'; ?>" 
                         style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid #e2e8f0; margin-bottom: 15px;"
                         onerror="this.src='../assets/images/default-avatar.png'">
                    <h4><?php echo htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name']); ?></h4>
                    <p class="text-muted"><?php echo htmlspecialchars($trainer['specialization']); ?></p>
                    <span class="badge badge-success">Active Trainer</span>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-edit"></i> Edit Profile</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>First Name</label>
                                    <input type="text" name="first_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($trainer['first_name']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Last Name</label>
                                    <input type="text" name="last_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($trainer['last_name']); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Email (read-only)</label>
                                    <input type="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($trainer['email']); ?>" disabled>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Phone</label>
                                    <input type="tel" name="phone" class="form-control" 
                                           value="<?php echo htmlspecialchars($trainer['phone'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Specialization</label>
                                    <input type="text" name="specialization" class="form-control" 
                                           value="<?php echo htmlspecialchars($trainer['specialization'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Qualification</label>
                                    <input type="text" name="qualification" class="form-control" 
                                           value="<?php echo htmlspecialchars($trainer['qualification'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>LinkedIn Profile</label>
                            <input type="url" name="linkedin_url" class="form-control" 
                                   value="<?php echo htmlspecialchars($trainer['linkedin_url'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Bio / About</label>
                            <textarea name="bio" class="form-control" rows="4"><?php echo htmlspecialchars($trainer['bio'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Profile Picture</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" name="profile_picture" 
                                       accept="image/*" onchange="previewImage(this)">
                                <label class="custom-file-label">Choose image...</label>
                            </div>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewImage').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>