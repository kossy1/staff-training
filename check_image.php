<?php
require_once 'includes/config.php';


$user_id = $_SESSION['user_id'] ?? 0;
$employee_id = $_SESSION['employee_id'] ?? 0;

echo "<h1>🔍 Profile Image Check</h1>";
echo "<style>body{font-family:Arial;padding:20px;} .ok{color:green;font-weight:bold;} .fail{color:red;font-weight:bold;} img{border:3px solid #667eea;border-radius:50%;margin:10px;} .box{background:#f8f9fc;padding:20px;border-radius:10px;margin:15px 0;}</style>";

// Get employee record
$emp = $conn->query("SELECT * FROM employees WHERE id = $employee_id")->fetch_assoc();

if (!$emp) {
    die("Employee not found");
}

echo "<div class='box'>";
echo "<h2>Database Record</h2>";
echo "<strong>Employee ID:</strong> {$emp['id']}<br>";
echo "<strong>Name:</strong> {$emp['first_name']} {$emp['last_name']}<br>";
echo "<strong>Profile Picture (DB):</strong> " . ($emp['profile_picture'] ?? '<span class="fail">NULL</span>') . "<br>";
echo "</div>";

if (!empty($emp['profile_picture'])) {
    $file_path = __DIR__ . '/uploads/profile-pictures/' . $emp['profile_picture'];
    $web_path = 'uploads/profile-pictures/' . $emp['profile_picture'];
    
    echo "<div class='box'>";
    echo "<h2>File Check</h2>";
    echo "<strong>Expected Path:</strong> <code>$file_path</code><br>";
    
    if (file_exists($file_path)) {
        $size = filesize($file_path);
        $mtime = date('Y-m-d H:i:s', filemtime($file_path));
        echo "<span class='ok'>✅ File EXISTS</span><br>";
        echo "<strong>Size:</strong> " . number_format($size) . " bytes<br>";
        echo "<strong>Modified:</strong> $mtime<br>";
        echo "<strong>Permissions:</strong> " . substr(sprintf('%o', fileperms($file_path)), -4) . "<br>";
        
        echo "<h3>Preview:</h3>";
        echo "<img src='$web_path?v=" . time() . "' style='width:150px;height:150px;object-fit:cover;'>";
        
        echo "<h3>Web URL:</h3>";
        echo "<code>" . rtrim(SITE_URL, '/') . "/$web_path</code><br>";
        echo "<a href='$web_path' target='_blank' class='btn' style='background:#667eea;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;margin-top:10px;'>Open Image Directly</a>";
    } else {
        echo "<span class='fail'>❌ File NOT FOUND!</span><br>";
        echo "The database says the file is <code>{$emp['profile_picture']}</code>, but it doesn't exist on the server.";
    }
    echo "</div>";
    
    // List all files in the folder
    echo "<div class='box'>";
    echo "<h2>All Files in uploads/profile-pictures/</h2>";
    $dir = __DIR__ . '/uploads/profile-pictures/';
    $files = scandir($dir);
    if (count($files) > 2) {
        echo "<ul>";
        foreach ($files as $f) {
            if ($f != '.' && $f != '..') {
                $size = filesize($dir . $f);
                echo "<li><code>$f</code> (" . number_format($size) . " bytes)</li>";
            }
        }
        echo "</ul>";
    } else {
        echo "<span class='fail'>Folder is empty!</span>";
    }
    echo "</div>";
}
?>