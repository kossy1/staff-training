<?php
// admin/edit-trainer.php - Edit Trainer
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

$trainer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($trainer_id <= 0) {
    header('Location: trainers.php');
    exit();
}

// Get trainer
$trainer = $conn->query("SELECT * FROM trainers WHERE id = $trainer_id")->fetch_assoc();

if (!$trainer) {
    header('Location: trainers.php');
    exit();
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $form_data = [
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'specialization' => trim($_POST['specialization'] ?? ''),
        'qualification' => trim($_POST['qualification'] ?? ''),
        'experience_years' => (int)($_POST['experience_years'] ?? 0),
        'bio' => trim($_POST['bio'] ?? ''),
        'linkedin_url' => trim($_POST['linkedin_url'] ?? ''),
        'twitter_url' => trim($_POST['twitter_url'] ?? ''),
        'website_url' => trim($_POST['website_url'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'city' => trim($_POST['city'] ?? ''),
        'state' => trim($_POST['state'] ?? ''),
        'country' => trim($_POST['country'] ?? 'Nigeria'),
        'status' => trim($_POST['status'] ?? 'active')
    ];
    
    // Validation
    if (empty($form_data['first_name'])) $errors[] = "First name is required";
    if (empty($form_data['last_name'])) $errors[] = "Last name is required";
    if (empty($form_data['email'])) $errors[] = "Email is required";
    if (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email";
    
    // Check email uniqueness
    if (empty($errors)) {
        $check = $conn->prepare("SELECT id FROM trainers WHERE email = ? AND id != ?");
        $check->bind_param("si", $form_data['email'], $trainer_id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $errors[] = "Email already exists!";
        }
    }
    
    // Handle profile picture
    $profile_picture = $trainer['profile_picture'];
    if (empty($errors) && isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $upload_dir = '../uploads/trainers/';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
            
            // Delete old
            if (!empty($trainer['profile_picture']) && file_exists($upload_dir . $trainer['profile_picture'])) {
                unlink($upload_dir . $trainer['profile_picture']);
            }
            
            $new_filename = 'trainer_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_dir . $new_filename)) {
                $profile_picture = $new_filename;
            }
        }
    }
    
    // Update
    if (empty($errors)) {
        $stmt = $conn->prepare("
            UPDATE trainers SET 
                first_name = ?, last_name = ?, email = ?, phone = ?,
                specialization = ?, qualification = ?, experience_years = ?,
                bio = ?, profile_picture = ?, linkedin_url = ?, twitter_url = ?,
                website_url = ?, address = ?, city = ?, state = ?, country = ?, status = ?
            WHERE id = ?
        ");
        
        $stmt->bind_param(
            "ssssssissssssssssi",
            $form_data['first_name'],
            $form_data['last_name'],
            $form_data['email'],
            $form_data['phone'],
            $form_data['specialization'],
            $form_data['qualification'],
            $form_data['experience_years'],
            $form_data['bio'],
            $profile_picture,
            $form_data['linkedin_url'],
            $form_data['twitter_url'],
            $form_data['website_url'],
            $form_data['address'],
            $form_data['city'],
            $form_data['state'],
            $form_data['country'],
            $form_data['status'],
            $trainer_id
        );
        
        if ($stmt->execute()) {
            logAction($_SESSION['user_id'], 'trainer_updated', ['trainer_id' => $trainer_id]);
            $success = true;
            
            // Refresh
            $trainer = $conn->query("SELECT * FROM trainers WHERE id = $trainer_id")->fetch_assoc();
        } else {
            $errors[] = "Failed to update: " . $conn->error;
        }
    }
}

$page_title = 'Edit Trainer';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <div>
                <h1><i class="fas fa-user-edit text-primary"></i> Edit Trainer</h1>
                <p class="text-muted">Update trainer information</p>
            </div>
            <div>
                <a href="view-trainer.php?id=<?php echo $trainer_id; ?>" class="btn btn-info">
                    <i class="fas fa-eye"></i> View Profile
                </a>
                <a href="trainers.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> Trainer updated successfully!
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">First Name</label>
                                    <input type="text" name="first_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($trainer['first_name']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">Last Name</label>
                                    <input type="text" name="last_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($trainer['last_name']); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">Email</label>
                                    <input type="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($trainer['email']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">Phone</label>
                                    <input type="tel" name="phone" class="form-control" 
                                           value="<?php echo htmlspecialchars($trainer['phone']); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">Specialization</label>
                                    <input type="text" name="specialization" class="form-control" 
                                           value="<?php echo htmlspecialchars($trainer['specialization']); ?>" required>
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
                            <label>Years of Experience</label>
                            <input type="number" name="experience_years" class="form-control" 
                                   value="<?php echo $trainer['experience_years']; ?>" min="0">
                        </div>
                        
                        <div class="form-group">
                            <label>Bio</label>
                            <textarea name="bio" class="form-control" rows="4"><?php echo htmlspecialchars($trainer['bio'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Address</label>
                            <input type="text" name="address" class="form-control" 
                                   value="<?php echo htmlspecialchars($trainer['address'] ?? ''); ?>">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>City</label>
                                    <input type="text" name="city" class="form-control" 
                                           value="<?php echo htmlspecialchars($trainer['city'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>State</label>
                                    <input type="text" name="state" class="form-control" 
                                           value="<?php echo htmlspecialchars($trainer['state'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Country</label>
                                    <input type="text" name="country" class="form-control" 
                                           value="<?php echo htmlspecialchars($trainer['country'] ?? 'Nigeria'); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><i class="fab fa-linkedin text-primary"></i> LinkedIn</label>
                                    <input type="url" name="linkedin_url" class="form-control" 
                                           value="<?php echo htmlspecialchars($trainer['linkedin_url'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><i class="fab fa-twitter text-info"></i> Twitter</label>
                                    <input type="url" name="twitter_url" class="form-control" 
                                           value="<?php echo htmlspecialchars($trainer['twitter_url'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><i class="fas fa-globe text-success"></i> Website</label>
                                    <input type="url" name="website_url" class="form-control" 
                                           value="<?php echo htmlspecialchars($trainer['website_url'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-camera"></i> Profile Picture</h5>
                    </div>
                    <div class="card-body text-center">
                        <img id="previewImage" 
                             src="<?php echo !empty($trainer['profile_picture']) ? '../uploads/trainers/' . $trainer['profile_picture'] : '../assets/images/default-avatar.png'; ?>" 
                             style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid #e2e8f0; margin-bottom: 15px;"
                             onerror="this.src='../assets/images/default-avatar.png'">
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="profile_picture" 
                                   name="profile_picture" accept="image/*" onchange="previewImage(this)">
                            <label class="custom-file-label" for="profile_picture">Change image...</label>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-toggle-on"></i> Status</h5>
                    </div>
                    <div class="card-body">
                        <select name="status" class="form-control">
                            <option value="active" <?php echo $trainer['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $trainer['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="on_leave" <?php echo $trainer['status'] == 'on_leave' ? 'selected' : ''; ?>>On Leave</option>
                        </select>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary btn-block btn-lg">
                            <i class="fas fa-save"></i> Update Trainer
                        </button>
                        <a href="trainers.php" class="btn btn-secondary btn-block mt-2">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
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