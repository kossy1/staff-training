<?php
// admin/add-employee.php - Add New Employee
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

$errors = [];
$success = false;
$form_data = [];

// Get departments for dropdown
$departments = $conn->query("SELECT DISTINCT department FROM employees WHERE department IS NOT NULL ORDER BY department");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
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
    if (empty($form_data['first_name'])) {
        $errors[] = "First name is required";
    }
    if (empty($form_data['last_name'])) {
        $errors[] = "Last name is required";
    }
    if (empty($form_data['email'])) {
        $errors[] = "Email is required";
    } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    if (empty($form_data['phone'])) {
        $errors[] = "Phone number is required";
    }
    if (empty($form_data['department'])) {
        $errors[] = "Department is required";
    }
    if (empty($form_data['position'])) {
        $errors[] = "Position is required";
    }
    if (empty($form_data['date_of_joining'])) {
        $errors[] = "Date of joining is required";
    }
    
    // Check if email already exists
    if (empty($errors)) {
        $check = $conn->prepare("SELECT id FROM employees WHERE email = ?");
        $check->bind_param("s", $form_data['email']);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $errors[] = "Email already exists!";
        }
    }
    
    // Handle profile picture upload
    $profile_picture = '';
    if (empty($errors) && isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_picture']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_filename = 'profile_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $upload_path = '../uploads/profile-pictures/' . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                $profile_picture = $new_filename;
            } else {
                $errors[] = "Failed to upload profile picture";
            }
        } else {
            $errors[] = "Invalid file type. Allowed: JPG, JPEG, PNG, GIF";
        }
    }
    
    // Create employee
    if (empty($errors)) {
        $employee_code = generateUniqueCode();
        
        $stmt = $conn->prepare("
            INSERT INTO employees (
                first_name, last_name, email, phone, department, position, 
                date_of_joining, date_of_birth, profile_picture, employee_code, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param(
            "sssssssssss",
            $form_data['first_name'],
            $form_data['last_name'],
            $form_data['email'],
            $form_data['phone'],
            $form_data['department'],
            $form_data['position'],
            $form_data['date_of_joining'],
            $form_data['date_of_birth'],
            $profile_picture,
            $employee_code,
            $form_data['status']
        );
        
        if ($stmt->execute()) {
            $employee_id = $conn->insert_id;
            logAction($_SESSION['user_id'], 'employee_created', ['employee_id' => $employee_id]);
            $success = true;
            $form_data = []; // Clear form
        } else {
            $errors[] = "Failed to create employee: " . $conn->error;
        }
    }
}

$page_title = 'Add Employee';
$page_scripts = '
<script>
$(document).ready(function() {
    // Preview profile picture
    $("#profile_picture").on("change", function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $("#profilePreview").attr("src", e.target.result).show();
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
                <h1><i class="fas fa-user-plus text-primary"></i> Add Employee</h1>
                <p class="text-muted">Create a new employee record</p>
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
            <i class="fas fa-check-circle"></i> Employee created successfully!
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
                            <img id="profilePreview" src="../assets/images/default-avatar.png" 
                                 alt="Profile Preview" class="rounded-circle" 
                                 style="width: 150px; height: 150px; object-fit: cover; border: 3px solid #e2e8f0;">
                        </div>
                        <div class="form-group">
                            <label>Profile Picture</label>
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
                                           value="<?php echo htmlspecialchars($form_data['first_name'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">Last Name</label>
                                    <input type="text" name="last_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($form_data['last_name'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">Email</label>
                                    <input type="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">Phone</label>
                                    <input type="tel" name="phone" class="form-control" 
                                           value="<?php echo htmlspecialchars($form_data['phone'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">Department</label>
                                    <select name="department" class="form-control" required>
                                        <option value="">Select Department</option>
                                        <option value="IT" <?php echo ($form_data['department'] ?? '') == 'IT' ? 'selected' : ''; ?>>IT</option>
                                        <option value="HR" <?php echo ($form_data['department'] ?? '') == 'HR' ? 'selected' : ''; ?>>HR</option>
                                        <option value="Finance" <?php echo ($form_data['department'] ?? '') == 'Finance' ? 'selected' : ''; ?>>Finance</option>
                                        <option value="Sales" <?php echo ($form_data['department'] ?? '') == 'Sales' ? 'selected' : ''; ?>>Sales</option>
                                        <option value="Marketing" <?php echo ($form_data['department'] ?? '') == 'Marketing' ? 'selected' : ''; ?>>Marketing</option>
                                        <option value="Operations" <?php echo ($form_data['department'] ?? '') == 'Operations' ? 'selected' : ''; ?>>Operations</option>
                                        <option value="Engineering" <?php echo ($form_data['department'] ?? '') == 'Engineering' ? 'selected' : ''; ?>>Engineering</option>
                                        <option value="Management" <?php echo ($form_data['department'] ?? '') == 'Management' ? 'selected' : ''; ?>>Management</option>
                                        <?php while ($dept = $departments->fetch_assoc()): ?>
                                            <option value="<?php echo htmlspecialchars($dept['department']); ?>" 
                                                <?php echo ($form_data['department'] ?? '') == $dept['department'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($dept['department']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">Position</label>
                                    <input type="text" name="position" class="form-control" 
                                           value="<?php echo htmlspecialchars($form_data['position'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">Date of Joining</label>
                                    <input type="date" name="date_of_joining" class="form-control" 
                                           value="<?php echo htmlspecialchars($form_data['date_of_joining'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Date of Birth</label>
                                    <input type="date" name="date_of_birth" class="form-control" 
                                           value="<?php echo htmlspecialchars($form_data['date_of_birth'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="status" class="form-control">
                                        <option value="active" <?php echo ($form_data['status'] ?? 'active') == 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo ($form_data['status'] ?? '') == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        <option value="on_leave" <?php echo ($form_data['status'] ?? '') == 'on_leave' ? 'selected' : ''; ?>>On Leave</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="text-center mt-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Create Employee
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