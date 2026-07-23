<?php
// create_admin_simple.php - Simple admin creation with pre-filled data
require_once 'includes/config.php';

$message = '';
$success = false;

// Pre-fill with sample data
$default_data = [
    'first_name' => 'System',
    'last_name' => 'Administrator',
    'username' => 'admin',
    'email' => 'admin@example.com',
    'phone' => '1234567890',
    'department' => 'IT',
    'position' => 'System Administrator'
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $phone = trim($_POST['phone']);
    $department = trim($_POST['department']);
    $position = trim($_POST['position']);
    
    // Validate
    $errors = [];
    if (empty($first_name)) $errors[] = "First name required";
    if (empty($last_name)) $errors[] = "Last name required";
    if (empty($email)) $errors[] = "Email required";
    if (empty($username)) $errors[] = "Username required";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters";
    if (empty($phone)) $errors[] = "Phone required";
    if (empty($department)) $errors[] = "Department required";
    if (empty($position)) $errors[] = "Position required";
    
    if (empty($errors)) {
        // Check if email exists
        $check = $conn->query("SELECT id FROM users WHERE email = '$email'");
        if ($check->num_rows > 0) {
            $errors[] = "Email already exists!";
        }
    }
    
    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            // Create employee
            $employee_code = 'ADMIN' . date('Ymd') . rand(100, 999);
            $stmt = $conn->prepare("
                INSERT INTO employees (first_name, last_name, email, phone, department, position, employee_code, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
            ");
            $stmt->bind_param("sssssss", $first_name, $last_name, $email, $phone, $department, $position, $employee_code);
            $stmt->execute();
            $employee_id = $conn->insert_id;
            
            // Create user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, employee_id) VALUES (?, ?, ?, 'admin', ?)");
            $stmt->bind_param("sssi", $username, $email, $hashed_password, $employee_id);
            $stmt->execute();
            
            $conn->commit();
            $message = "✅ Admin created successfully!";
            $success = true;
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = "❌ Error: " . $e->getMessage();
        }
    } else {
        $message = implode("<br>", $errors);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { background: #f0f2f5; padding: 50px 0; }
        .container { max-width: 600px; }
        .card { border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .card-header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; border-radius: 15px 15px 0 0; padding: 20px; }
        .btn-create { background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; padding: 12px 40px; }
        .btn-create:hover { color: white; opacity: 0.9; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header text-center">
                <i class="fas fa-user-shield" style="font-size: 40px;"></i>
                <h3 class="mb-0">Create Admin Account</h3>
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $success ? 'success' : 'danger'; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>First Name *</label>
                                <input type="text" name="first_name" class="form-control" 
                                       value="<?php echo $_POST['first_name'] ?? $default_data['first_name']; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Last Name *</label>
                                <input type="text" name="last_name" class="form-control" 
                                       value="<?php echo $_POST['last_name'] ?? $default_data['last_name']; ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Username *</label>
                                <input type="text" name="username" class="form-control" 
                                       value="<?php echo $_POST['username'] ?? $default_data['username']; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email *</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?php echo $_POST['email'] ?? $default_data['email']; ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Password *</label>
                        <input type="password" name="password" class="form-control" 
                               value="admin123" required minlength="6">
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Phone *</label>
                        <input type="text" name="phone" class="form-control" 
                               value="<?php echo $_POST['phone'] ?? $default_data['phone']; ?>" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Department *</label>
                                <select name="department" class="form-control" required>
                                    <option value="">Select</option>
                                    <option value="IT" <?php echo (($_POST['department'] ?? $default_data['department']) == 'IT') ? 'selected' : ''; ?>>IT</option>
                                    <option value="HR" <?php echo (($_POST['department'] ?? '') == 'HR') ? 'selected' : ''; ?>>HR</option>
                                    <option value="Finance" <?php echo (($_POST['department'] ?? '') == 'Finance') ? 'selected' : ''; ?>>Finance</option>
                                    <option value="Sales" <?php echo (($_POST['department'] ?? '') == 'Sales') ? 'selected' : ''; ?>>Sales</option>
                                    <option value="Marketing" <?php echo (($_POST['department'] ?? '') == 'Marketing') ? 'selected' : ''; ?>>Marketing</option>
                                    <option value="Operations" <?php echo (($_POST['department'] ?? '') == 'Operations') ? 'selected' : ''; ?>>Operations</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Position *</label>
                                <input type="text" name="position" class="form-control" 
                                       value="<?php echo $_POST['position'] ?? $default_data['position']; ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-create">
                            <i class="fas fa-user-plus"></i> Create Admin
                        </button>
                        <a href="login.php" class="btn btn-outline-secondary ml-2">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>