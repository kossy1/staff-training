<?php
// setup.php - Complete setup script
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'staff_training_system';

// Create connection
$conn = new mysqli($host, $user, $pass);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    echo "✅ Database created or already exists<br>";
} else {
    echo "❌ Error creating database: " . $conn->error . "<br>";
}

// Select database
$conn->select_db($dbname);

// Read and execute the SQL file
$sqlFile = file_get_contents('database.sql');
if ($sqlFile) {
    // Split SQL into individual statements
    $statements = explode(';', $sqlFile);
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            if ($conn->query($statement) === FALSE) {
                echo "❌ Error: " . $conn->error . "<br>";
            }
        }
    }
    echo "✅ Tables created successfully<br>";
}

// Check if admin exists
$check = $conn->query("SELECT id FROM users WHERE email = 'admin@example.com'");
if ($check->num_rows == 0) {
    // Create admin employee
    $conn->query("
        INSERT INTO employees (first_name, last_name, email, phone, department, position, employee_code, status, date_of_joining) 
        VALUES ('System', 'Administrator', 'admin@example.com', '1234567890', 'IT', 'System Administrator', 'ADMIN001', 'active', CURDATE())
    ");
    $employee_id = $conn->insert_id;
    
    // Create admin user
    $conn->query("
        INSERT INTO users (username, email, password, role, employee_id) 
        VALUES ('admin', 'admin@example.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', $employee_id)
    ");
    echo "✅ Admin user created successfully<br>";
} else {
    echo "ℹ️ Admin user already exists<br>";
}

echo "<hr>";
echo "<h2>Setup Complete!</h2>";
echo "<p><strong>Login Credentials:</strong></p>";
echo "<ul>";
echo "<li><strong>Email:</strong> admin@example.com</li>";
echo "<li><strong>Password:</strong> admin123</li>";
echo "</ul>";
echo "<a href='login.php' style='display:inline-block;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;'>Go to Login</a>";
echo "<br><br>";
echo "<small>⚠️ Delete this file (setup.php) after successful setup for security.</small>";

$conn->close();
?>