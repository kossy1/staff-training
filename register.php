<?php
// register.php - User Registration with New Branding
session_start();
require_once 'includes/config.php';

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$errors = [];
$success = false;
$form_data = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $form_data = [
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'username' => trim($_POST['username'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'phone' => trim($_POST['phone'] ?? ''),
        'department' => trim($_POST['department'] ?? ''),
        'position' => trim($_POST['position'] ?? '')
    ];
    
    // Validation
    if (empty($form_data['first_name'])) $errors[] = "First name is required";
    if (empty($form_data['last_name'])) $errors[] = "Last name is required";
    if (empty($form_data['email'])) $errors[] = "Email is required";
    if (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (empty($form_data['username'])) $errors[] = "Username is required";
    if (strlen($form_data['username']) < 3) $errors[] = "Username must be at least 3 characters";
    if (empty($form_data['password'])) $errors[] = "Password is required";
    if (strlen($form_data['password']) < 6) $errors[] = "Password must be at least 6 characters";
    if ($form_data['password'] !== $form_data['confirm_password']) $errors[] = "Passwords do not match";
    if (empty($form_data['phone'])) $errors[] = "Phone number is required";
    
    // Check if email exists
    if (empty($errors)) {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $form_data['email']);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $errors[] = "Email already exists!";
        }
    }
    
    // Check if username exists
    if (empty($errors)) {
        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $form_data['username']);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $errors[] = "Username already exists!";
        }
    }
    
    // Create account
    if (empty($errors)) {
        $conn->begin_transaction();
        
        try {
            // Create employee
            $employee_code = generateUniqueCode();
            $stmt = $conn->prepare("
                INSERT INTO employees (first_name, last_name, email, phone, department, position, employee_code, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
            ");
            $stmt->bind_param("sssssss", 
                $form_data['first_name'],
                $form_data['last_name'],
                $form_data['email'],
                $form_data['phone'],
                $form_data['department'],
                $form_data['position'],
                $employee_code
            );
            $stmt->execute();
            $employee_id = $conn->insert_id;
            
            // Create user
            $hashed_password = password_hash($form_data['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("
                INSERT INTO users (username, email, password, role, employee_id) 
                VALUES (?, ?, ?, 'employee', ?)
            ");
            $stmt->bind_param("sssi", 
                $form_data['username'],
                $form_data['email'],
                $hashed_password,
                $employee_id
            );
            $stmt->execute();
            
            $conn->commit();
            $success = true;
            $form_data = [];
            
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
}

$page_title = 'Register';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            padding: 50px 0;
        }
        .register-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .register-header .institution-name {
            font-size: 1rem;
            font-weight: 700;
            color: #667eea;
            letter-spacing: 1px;
        }
        .register-header .institution-sub {
            font-size: 0.8rem;
            color: #764ba2;
            font-weight: 600;
        }
        .register-header h2 {
            color: #2d3748;
            font-weight: 700;
            margin-top: 10px;
        }
        .register-header p {
            color: #6c757d;
        }
        .required:after {
            content: " *";
            color: red;
        }
        .register-divider {
            border: none;
            height: 2px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            width: 60px;
            margin: 10px auto;
            border-radius: 10px;
        }
        .institution-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
        }
        .institution-footer p {
            font-size: 0.75rem;
            color: #6c757d;
            margin: 0;
        }
        @media (max-width: 576px) {
            .register-container {
                margin: 15px;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <i class="fas fa-user-plus" style="font-size: 3rem; color: #667eea;"></i>
            <div class="institution-name">THE POLYTECHNIC, IBADAN</div>
            <div class="institution-sub">SKILL DEVELOPMENT CENTRE</div>
            <hr class="register-divider">
            <h2>Create Account</h2>
            <p>Register to access the staff training system</p>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Registration successful! You can now <a href="login.php">login</a>.
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
        
        <form method="POST">
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
                        <label class="required">Username</label>
                        <input type="text" name="username" class="form-control" 
                               value="<?php echo htmlspecialchars($form_data['username'] ?? ''); ?>" required minlength="3">
                        <small class="text-muted">At least 3 characters</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="required">Email</label>
                        <input type="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="required">Password</label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="required">Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="required">Phone Number</label>
                <input type="tel" name="phone" class="form-control" 
                       value="<?php echo htmlspecialchars($form_data['phone'] ?? ''); ?>" required>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Department</label>
                        <select name="department" class="form-control">
                            <option value="">Select Department</option>
                            <option value="IT" <?php echo ($form_data['department'] ?? '') == 'IT' ? 'selected' : ''; ?>>IT</option>
                            <option value="HR" <?php echo ($form_data['department'] ?? '') == 'HR' ? 'selected' : ''; ?>>HR</option>
                            <option value="Finance" <?php echo ($form_data['department'] ?? '') == 'Finance' ? 'selected' : ''; ?>>Finance</option>
                            <option value="Sales" <?php echo ($form_data['department'] ?? '') == 'Sales' ? 'selected' : ''; ?>>Sales</option>
                            <option value="Marketing" <?php echo ($form_data['department'] ?? '') == 'Marketing' ? 'selected' : ''; ?>>Marketing</option>
                            <option value="Operations" <?php echo ($form_data['department'] ?? '') == 'Operations' ? 'selected' : ''; ?>>Operations</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Position</label>
                        <input type="text" name="position" class="form-control" 
                               value="<?php echo htmlspecialchars($form_data['position'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block btn-lg">
                <i class="fas fa-user-plus"></i> Register
            </button>
            
            <div class="text-center mt-3">
                Already have an account? <a href="login.php">Login here</a>
            </div>
            
            <div class="institution-footer">
                <p>&copy; <?php echo date('Y'); ?> THE POLYTECHNIC, IBADAN - SKILL DEVELOPMENT CENTRE</p>
                <p>Staff Training &amp; Development Tracking System</p>
            </div>
        </form>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>