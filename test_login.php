<?php
// test_login.php - Test login functionality
require_once 'includes/config.php';

$email = 'admin@example.com';
$password = 'admin123';

echo "<h2>Testing Login</h2>";

// Check if user exists
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo "✅ User found in database<br>";
    echo "User ID: " . $row['id'] . "<br>";
    echo "Username: " . $row['username'] . "<br>";
    echo "Email: " . $row['email'] . "<br>";
    echo "Role: " . $row['role'] . "<br>";
    echo "Password hash: " . $row['password'] . "<br>";
    
    // Verify password
    if (password_verify($password, $row['password'])) {
        echo "✅ Password is correct!<br>";
        echo "<a href='login.php'>Go to Login</a>";
    } else {
        echo "❌ Password is incorrect<br>";
        echo "Let's create a new admin user...<br>";
        
        // Create new admin
        $new_password = password_hash($password, PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password = '$new_password' WHERE email = 'admin@example.com'");
        echo "✅ Password updated! Try logging in now.<br>";
        echo "<a href='login.php'>Go to Login</a>";
    }
} else {
    echo "❌ User not found! Creating admin...<br>";
    
    // Create employee
    $conn->query("
        INSERT INTO employees (first_name, last_name, email, phone, department, position, employee_code, status, date_of_joining) 
        VALUES ('System', 'Administrator', 'admin@example.com', '1234567890', 'IT', 'System Administrator', 'ADMIN001', 'active', CURDATE())
    ");
    $employee_id = $conn->insert_id;
    
    // Create user
    $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
    $conn->query("
        INSERT INTO users (username, email, password, role, employee_id) 
        VALUES ('admin', 'admin@example.com', '$hashed_password', 'admin', $employee_id)
    ");
    
    echo "✅ Admin user created!<br>";
    echo "<a href='login.php'>Go to Login</a>";
}
?>