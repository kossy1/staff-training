<?php
// create_admin_form.php - Admin creation form with input fields
session_start();
require_once 'includes/config.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = trim($_POST['phone']);
    $department = trim($_POST['department']);
    $position = trim($_POST['position']);
    
    // Validation
    $errors = [];
    
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (empty($username)) $errors[] = "Username is required";
    if (strlen($username) < 3) $errors[] = "Username must be at least 3 characters";
    if (empty($password)) $errors[] = "Password is required";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match";
    if (empty($phone)) $errors[] = "Phone number is required";
    if (empty($department)) $errors[] = "Department is required";
    if (empty($position)) $errors[] = "Position is required";
    
    // Check if email already exists
    if (empty($errors)) {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $errors[] = "Email already exists!";
        }
    }
    
    // Check if username already exists
    if (empty($errors)) {
        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $errors[] = "Username already exists!";
        }
    }
    
    // If no errors, create admin
    if (empty($errors)) {
        $conn->begin_transaction();
        
        try {
            // Generate employee code
            $employee_code = 'ADMIN' . date('Ymd') . rand(100, 999);
            
            // Insert employee
            $stmt = $conn->prepare("
                INSERT INTO employees (
                    first_name, 
                    last_name, 
                    email, 
                    phone, 
                    department, 
                    position, 
                    employee_code, 
                    status,
                    date_of_joining
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'active', CURDATE())
            ");
            $stmt->bind_param("sssssss", $first_name, $last_name, $email, $phone, $department, $position, $employee_code);
            $stmt->execute();
            $employee_id = $conn->insert_id;
            
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $stmt = $conn->prepare("
                INSERT INTO users (username, email, password, role, employee_id) 
                VALUES (?, ?, ?, 'admin', ?)
            ");
            $stmt->bind_param("sssi", $username, $email, $hashed_password, $employee_id);
            $stmt->execute();
            $user_id = $conn->insert_id;
            
            // Log the creation
            logAction($user_id, 'admin_created', [
                'username' => $username,
                'email' => $email,
                'employee_id' => $employee_id
            ]);
            
            $conn->commit();
            
            $message = "✅ Admin user created successfully!";
            $message_type = 'success';
            
            // Clear form data after success
            $_POST = array();
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = "❌ Error: " . $e->getMessage();
            $message_type = 'error';
        }
    } else {
        $message = implode("<br>", $errors);
        $message_type = 'error';
    }
}

// Get existing admins count
$admin_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin User</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 50px 0;
        }
        .form-container {
            max-width: 700px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .form-header h2 {
            color: #333;
            font-weight: 700;
        }
        .form-header p {
            color: #6c757d;
        }
        .required:after {
            content: " *";
            color: red;
        }
        .btn-create {
            padding: 12px 40px;
            font-weight: 600;
            border-radius: 50px;
        }
        .password-requirements {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 5px;
        }
        .admin-count {
            background: #f8f9fa;
            padding: 10px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        @media (max-width: 576px) {
            .form-container {
                padding: 20px;
                margin: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <div class="form-header">
                <i class="fas fa-user-shield" style="font-size: 50px; color: #667eea;"></i>
                <h2>Create Admin Account</h2>
                <p>Fill in the details below to create a new administrator</p>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type == 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
                    <?php echo $message; ?>
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            
            <?php if ($admin_count > 0): ?>
                <div class="admin-count">
                    <i class="fas fa-info-circle text-info"></i>
                    <strong><?php echo $admin_count; ?></strong> admin(s) already exist in the system.
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="adminForm">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required">First Name</label>
                            <input type="text" name="first_name" class="form-control" 
                                   value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" 
                                   required placeholder="John">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required">Last Name</label>
                            <input type="text" name="last_name" class="form-control" 
                                   value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" 
                                   required placeholder="Doe">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required">Username</label>
                            <input type="text" name="username" class="form-control" 
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                                   required placeholder="admin" minlength="3">
                            <small class="form-text text-muted">At least 3 characters</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required">Email Address</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                   required placeholder="admin@example.com">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required">Password</label>
                            <input type="password" name="password" id="password" class="form-control" 
                                   required placeholder="••••••••" minlength="6">
                            <div class="password-requirements">
                                <i class="fas fa-info-circle"></i> At least 6 characters
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control" 
                                   required placeholder="••••••••">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="required">Phone Number</label>
                    <input type="tel" name="phone" class="form-control" 
                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" 
                           required placeholder="+1234567890">
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required">Department</label>
                            <select name="department" class="form-control" required>
                                <option value="">Select Department</option>
                                <option value="IT" <?php echo (isset($_POST['department']) && $_POST['department'] == 'IT') ? 'selected' : ''; ?>>IT</option>
                                <option value="HR" <?php echo (isset($_POST['department']) && $_POST['department'] == 'HR') ? 'selected' : ''; ?>>Human Resources</option>
                                <option value="Finance" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Finance') ? 'selected' : ''; ?>>Finance</option>
                                <option value="Sales" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Sales') ? 'selected' : ''; ?>>Sales</option>
                                <option value="Marketing" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Marketing') ? 'selected' : ''; ?>>Marketing</option>
                                <option value="Operations" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Operations') ? 'selected' : ''; ?>>Operations</option>
                                <option value="Engineering" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Engineering') ? 'selected' : ''; ?>>Engineering</option>
                                <option value="Management" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Management') ? 'selected' : ''; ?>>Management</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required">Position</label>
                            <input type="text" name="position" class="form-control" 
                                   value="<?php echo isset($_POST['position']) ? htmlspecialchars($_POST['position']) : ''; ?>" 
                                   required placeholder="System Administrator">
                        </div>
                    </div>
                </div>
                
                <div class="form-group text-center mt-4">
                    <button type="submit" class="btn btn-primary btn-create">
                        <i class="fas fa-user-plus"></i> Create Admin
                    </button>
                    <a href="login.php" class="btn btn-outline-secondary btn-create ml-2">
                        <i class="fas fa-sign-in-alt"></i> Go to Login
                    </a>
                </div>
                
                <div class="text-center mt-3">
                    <small class="text-muted">
                        <i class="fas fa-shield-alt"></i> Admin users have full system access
                    </small>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strength = getPasswordStrength(password);
            const indicator = document.querySelector('.password-requirements');
            
            if (password.length > 0) {
                if (password.length >= 6) {
                    indicator.innerHTML = '<i class="fas fa-check-circle text-success"></i> Password strength: <strong>' + strength + '</strong>';
                } else {
                    indicator.innerHTML = '<i class="fas fa-exclamation-circle text-warning"></i> Password must be at least 6 characters';
                }
            } else {
                indicator.innerHTML = '<i class="fas fa-info-circle"></i> At least 6 characters';
            }
        });
        
        function getPasswordStrength(password) {
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            if (strength <= 2) return 'Weak';
            if (strength <= 3) return 'Fair';
            if (strength <= 4) return 'Good';
            return 'Strong';
        }
        
        // Form validation
        document.getElementById('adminForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long!');
                return false;
            }
            return true;
        });
    </script>
</body>
</html>