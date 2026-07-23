<?php
// create_employee.php - Create Employee Account
require_once 'includes/config.php';

echo "<h1>Create Employee Account</h1>";

// Employee details
$first_name = 'John';
$last_name = 'Doe';
$email = 'employee@example.com';
$phone = '1234567890';
$department = 'Sales';
$position = 'Sales Representative';
$password = 'employee123';

// Check if employee exists
$check = $conn->query("SELECT id FROM employees WHERE email = '$email'");
if ($check->num_rows > 0) {
    echo "Employee already exists!<br>";
} else {
    // Create employee
    $employee_code = generateUniqueCode();
    $stmt = $conn->prepare("
        INSERT INTO employees (first_name, last_name, email, phone, department, position, employee_code, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
    ");
    $stmt->bind_param("sssssss", $first_name, $last_name, $email, $phone, $department, $position, $employee_code);
    $stmt->execute();
    $employee_id = $conn->insert_id;
    echo "✅ Employee created! ID: $employee_id<br>";
    
    // Create user account
    $username = 'employee';
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $role = 'employee';
    
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, employee_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $username, $email, $hashed_password, $role, $employee_id);
    $stmt->execute();
    echo "✅ User account created!<br>";
}

echo "<hr>";
echo "<h3>Login Credentials:</h3>";
echo "<p><strong>Email:</strong> employee@example.com</p>";
echo "<p><strong>Password:</strong> employee123</p>";
echo "<p><a href='login.php'>Go to Login</a></p>";
?>