<?php
// fix_login.php - Complete fix script
require_once 'includes/config.php';

echo "<h1>🔧 Login Fix Script</h1>";

// Check database connection
if ($conn->connect_error) {
    die("❌ Database connection failed: " . $conn->connect_error);
}
echo "✅ Database connected<br>";

// Check if users table exists
$tables = $conn->query("SHOW TABLES LIKE 'users'");
if ($tables->num_rows == 0) {
    echo "❌ Users table not found! Creating tables...<br>";
    // Create tables (simplified version)
    $conn->query("
        CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'manager', 'employee') DEFAULT 'employee',
            employee_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    $conn->query("
        CREATE TABLE IF NOT EXISTS employees (
            id INT PRIMARY KEY AUTO_INCREMENT,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            phone VARCHAR(20),
            department VARCHAR(100),
            position VARCHAR(100),
            employee_code VARCHAR(20) UNIQUE,
            status ENUM('active', 'inactive', 'on_leave') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✅ Tables created<br>";
}

// Check if admin exists
$check = $conn->query("SELECT * FROM users WHERE email = 'admin@example.com'");
if ($check->num_rows == 0) {
    echo "Creating admin user...<br>";
    
    // Create employee
    $conn->query("
        INSERT INTO employees (first_name, last_name, email, phone, department, position, employee_code, status) 
        VALUES ('System', 'Administrator', 'admin@example.com', '1234567890', 'IT', 'System Administrator', 'ADMIN001', 'active')
    ");
    $employee_id = $conn->insert_id;
    
    // Create user with hashed password
    $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
    $conn->query("
        INSERT INTO users (username, email, password, role, employee_id) 
        VALUES ('admin', 'admin@example.com', '$hashed_password', 'admin', $employee_id)
    ");
    echo "✅ Admin user created!<br>";
} else {
    echo "✅ Admin user exists<br>";
    
    // Reset password
    $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
    $conn->query("UPDATE users SET password = '$hashed_password' WHERE email = 'admin@example.com'");
    echo "✅ Password reset to 'admin123'<br>";
}

// Display users
$users = $conn->query("SELECT * FROM users");
echo "<h3>Current Users:</h3>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Employee ID</th></tr>";
while ($user = $users->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$user['id']}</td>";
    echo "<td>{$user['username']}</td>";
    echo "<td>{$user['email']}</td>";
    echo "<td>{$user['role']}</td>";
    echo "<td>{$user['employee_id']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<br><a href='login.php' style='display:inline-block;padding:10px 20px;background:#28a745;color:white;text-decoration:none;border-radius:5px;'>Go to Login</a>";
echo "&nbsp;&nbsp;";
echo "<a href='test_login.php' style='display:inline-block;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;'>Test Login</a>";
?>