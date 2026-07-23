<?php
// admin/edit-employee.php - Edit Employee
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

$employee_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($employee_id <= 0) {
    header('Location: employees.php');
    exit();
}

// Get employee data
$stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();

if (!$employee) {
    header('Location: employees.php');
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
        'department' => trim($_POST['department'] ?? ''),
        'position' => trim($_POST['position'] ?? ''),
        'date_of_joining' => trim($_POST['date_of_joining'] ?? ''),
        'date_of_birth' => trim($_POST['date_of_birth'] ?? ''),
        'status' => trim($_POST['status'] ?? 'active')
    ];
    
    // Validation
    if (empty($form_data['first_name'])) $errors[] = "First name is required";
    if (empty($form_data['last_name'])) $errors[] = "Last name is required";
    if (empty($form_data['email'])) $errors[] = "Email is required";
    if (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (empty($form_data['phone'])) $errors[] = "Phone number is required";
    if (empty($form_data['department'])) $errors[] = "Department is required";
    if (empty($form_data['position'])) $errors[] = "Position is required";
    if (empty($form_data['date_of_joining'])) $errors[] = "Date of joining is required";
    
    // Check if email already exists for another employee
    if (empty($errors)) {
        $check = $conn->prepare("SELECT id FROM employees WHERE email = ? AND id != ?");
        $check->bind_param("si", $form_data['email'], $employee_id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $errors[] = "Email already exists!";
        }
    }
    
    // Handle profile picture
    $profile_picture = $employee['profile_picture'];
    if (empty($errors) && isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_picture']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_filename = 'profile_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $upload_path = '../uploads/profile-pictures/' . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                // Delete old picture
                if ($profile_picture && file_exists('../uploads/profile-pictures/' . $profile_picture)) {
                    unlink('../uploads/profile-pictures/' . $profile_picture);
                }
                $profile_picture = $new_filename;
            } else {
                $errors[] = "Failed to upload profile picture";
            }
        } else {
            $errors[] = "Invalid file type";
        }
    }
    
    // Update employee
    if (empty($errors)) {
        $stmt = $conn->prepare("
            UPDATE employees SET 
                first_name = ?, last_name = ?, email = ?, phone = ?, 
                department = ?, position = ?, date_of_joining = ?, 
                date_of_birth = ?, profile_picture = ?, status = ?
            WHERE id = ?
        ");
        
        $stmt->bind_param(
            "ssssssssssi",
            $form_data['first_name'],
            $form_data['last_name'],
            $form_data['email'],
            $form_data['phone'],
            $form_data['department'],
            $form_data['position'],
            $form_data['date_of_joining'],
            $form_data['date_of_birth'],
            $profile_picture,
            $form_data['status'],
            $employee_id
        );
        
        if ($stmt->execute()) {
            logAction($_SESSION['user_id'], 'employee_updated', ['employee_id' => $employee_id]);
            $success = true;
            // Refresh employee data
            $stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
            $stmt->bind_param("i", $employee_id);
            $stmt->execute();
            $employee = $stmt->get_result()->fetch_assoc();
        } else {
            $errors[] = "Failed to update employee: " . $conn->error;
        }
    }
}

$page_title = 'Edit Employee';
$page_scripts = '
<script>
$(document).ready(function() {
    $("#profile_picture").on("change", function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $("#profilePreview").attr("src", e.target.result);
            };
            reader.readAsDataURL(file);
        }
    });
});
</script>
';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <div>
                <h1><i class="fas fa-user-edit text-primary"></i> Edit Employee</h1>
                <p class="text-muted">Update employee #<?php echo $employee['id']; ?></p>
            </div>
            <div>
                <a href="employees.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Employees
                </a>
            </div>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> Employee updated successfully!
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

    <div class="card">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-3 text-center">
                        <div class="mb-3">
                            <img id="profilePreview" 
                                 src="<?php echo !empty($employee['profile_picture']) ? '../uploads/profile-pictures/' . $employee['profile_picture'] : '../assets/images/default-avatar.png'; ?>" 
                                 alt="Profile" class="rounded-circle" 
                                 style="width: 150px; height: 150px; object-fit: cover; border: 3px solid #e2e8f0;">
                        </div>
                        <div class="form-group">
                            <label>Change Profile Picture</label>
                            <input type="file" name="profile_picture" id="profile_picture" 
                                   class="form-control-file" accept="image/*">
                            <small class="text-muted">Max size: 5MB (JPG, PNG, GIF)</small>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">First Name</label>
                                    <input type="text" name="first_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($employee['first_name']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">Last Name</label>
                                    <input type="text" name="last_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($employee['last_name']); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">Email</label>
                                    <input type="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($employee['email']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">Phone</label>
                                    <input type="tel" name="phone" class="form-control" 
                                           value="<?php echo htmlspecialchars($employee['phone']); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">Department</label>
                                    <select name="department" class="form-control" required>
                                        <option value="IT" <?php echo $employee['department'] == 'IT' ? 'selected' : ''; ?>>IT</option>
                                        <option value="HR" <?php echo $employee['department'] == 'HR' ? 'selected' : ''; ?>>HR</option>
                                        <option value="Finance" <?php echo $employee['department'] == 'Finance' ? 'selected' : ''; ?>>Finance</option>
                                        <option value="Sales" <?php echo $employee['department'] == 'Sales' ? 'selected' : ''; ?>>Sales</option>
                                        <option value="Marketing" <?php echo $employee['department'] == 'Marketing' ? 'selected' : ''; ?>>Marketing</option>
                                        <option value="Operations" <?php echo $employee['department'] == 'Operations' ? 'selected' : ''; ?>>Operations</option>
                                        <option value="Engineering" <?php echo $employee['department'] == 'Engineering' ? 'selected' : ''; ?>>Engineering</option>
                                        <option value="Management" <?php echo $employee['department'] == 'Management' ? 'selected' : ''; ?>>Management</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">Position</label>
                                    <input type="text" name="position" class="form-control" 
                                           value="<?php echo htmlspecialchars($employee['position']); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">Date of Joining</label>
                                    <input type="date" name="date_of_joining" class="form-control" 
                                           value="<?php echo htmlspecialchars($employee['date_of_joining']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Date of Birth</label>
                                    <input type="date" name="date_of_birth" class="form-control" 
                                           value="<?php echo htmlspecialchars($employee['date_of_birth']); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="status" class="form-control">
                                        <option value="active" <?php echo $employee['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo $employee['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        <option value="on_leave" <?php echo $employee['status'] == 'on_leave' ? 'selected' : ''; ?>>On Leave</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Employee Code</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($employee['employee_code']); ?>" disabled>
                                    <small class="text-muted">Employee code cannot be changed</small>
                                </div>
                            </div>
                        </div>

                        <div class="text-center mt-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Update Employee
                            </button>
                            <a href="employees.php" class="btn btn-secondary btn-lg">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>