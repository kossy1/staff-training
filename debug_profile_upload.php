<?php
// debug_profile_upload.php - Diagnose profile upload issues
session_start();
require_once 'includes/config.php';

if (!isLoggedIn()) {
    die("Please login first. <a href='login.php'>Login</a>");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Profile Upload</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
        body { padding: 30px; background: #f8f9fc; font-family: Arial; }
        .ok { color: green; font-weight: bold; }
        .fail { color: red; font-weight: bold; }
        .warn { color: orange; font-weight: bold; }
        .card { margin-bottom: 15px; border-radius: 10px; }
        pre { background: #1a1a2e; color: #48bb78; padding: 15px; border-radius: 8px; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 Profile Upload Diagnostic</h1>

    <?php
    echo "<div class='card'><div class='card-body'>";
    echo "<h4>Step 1: Session</h4>";
    echo "User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "<br>";
    echo "Role: " . ($_SESSION['role'] ?? 'NOT SET') . "<br>";
    echo "Employee ID: " . ($_SESSION['employee_id'] ?? 'NOT SET') . "<br>";
    echo "</div></div>";

    // Check upload directories
    echo "<div class='card'><div class='card-body'>";
    echo "<h4>Step 2: Upload Directories</h4>";
    
    $dirs = [
        'uploads',
        'uploads/profile-pictures',
        'uploads/trainers',
        'uploads/certificates',
        'uploads/materials'
    ];
    
    foreach ($dirs as $dir) {
        $path = __DIR__ . '/' . $dir;
        if (is_dir($path)) {
            $writable = is_writable($path) ? "<span class='ok'>✅ Writable</span>" : "<span class='fail'>❌ NOT WRITABLE</span>";
            $perms = substr(sprintf('%o', fileperms($path)), -4);
            echo "✅ <code>$dir</code> exists $writable (permissions: $perms)<br>";
        } else {
            echo "<span class='fail'>❌ $dir does NOT exist</span><br>";
            // Try to create
            if (mkdir($path, 0777, true)) {
                echo "<span class='ok'>   → Created successfully!</span><br>";
            } else {
                echo "<span class='fail'>   → Failed to create</span><br>";
            }
        }
    }
    echo "</div></div>";

    // Check PHP settings
    echo "<div class='card'><div class='card-body'>";
    echo "<h4>Step 3: PHP Configuration</h4>";
    
    $file_uploads = ini_get('file_uploads') ? "<span class='ok'>✅ ON</span>" : "<span class='fail'>❌ OFF</span>";
    echo "file_uploads: $file_uploads<br>";
    echo "upload_max_filesize: <strong>" . ini_get('upload_max_filesize') . "</strong><br>";
    echo "post_max_size: <strong>" . ini_get('post_max_size') . "</strong><br>";
    echo "max_file_uploads: <strong>" . ini_get('max_file_uploads') . "</strong><br>";
    echo "upload_tmp_dir: <strong>" . (ini_get('upload_tmp_dir') ?: sys_get_temp_dir()) . "</strong><br>";
    
    $tmp_writable = is_writable(sys_get_temp_dir()) ? "<span class='ok'>✅ Writable</span>" : "<span class='fail'>❌ NOT writable</span>";
    echo "tmp_dir writable: $tmp_writable<br>";
    
    echo "</div></div>";

    // Check GD extension
    echo "<div class='card'><div class='card-body'>";
    echo "<h4>Step 4: GD Extension</h4>";
    
    if (extension_loaded('gd')) {
        echo "<span class='ok'>✅ GD is loaded</span><br>";
        $funcs = ['imagecreatefromjpeg', 'imagecreatefrompng', 'imagecreatefromgif', 'imagejpeg'];
        foreach ($funcs as $f) {
            echo (function_exists($f) ? "✅" : "❌") . " $f()<br>";
        }
    } else {
        echo "<span class='warn'>⚠️ GD is NOT loaded — compression will be skipped, but upload should still work</span><br>";
    }
    echo "</div></div>";

    // Test actual upload
    echo "<div class='card'><div class='card-body'>";
    echo "<h4>Step 5: Test Upload</h4>";
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
        $file = $_FILES['test_file'];
        
        echo "<h5>Upload Result:</h5>";
        echo "<pre>";
        print_r($file);
        echo "</pre>";
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors = [
                1 => 'File exceeds upload_max_filesize',
                2 => 'File exceeds MAX_FILE_SIZE in HTML',
                3 => 'File was only partially uploaded',
                4 => 'No file was uploaded',
                6 => 'Missing a temporary folder',
                7 => 'Failed to write to disk',
                8 => 'A PHP extension stopped upload'
            ];
            echo "<span class='fail'>❌ Upload error: " . ($errors[$file['error']] ?? 'Unknown') . "</span><br>";
        } else {
            echo "<span class='ok'>✅ Upload reached server</span><br>";
            echo "Size: " . number_format($file['size']) . " bytes<br>";
            echo "Tmp name: {$file['tmp_name']}<br>";
            echo "Type: {$file['type']}<br>";
            
            $target = __DIR__ . '/uploads/test_upload_' . time() . '.jpg';
            if (move_uploaded_file($file['tmp_name'], $target)) {
                echo "<span class='ok'>✅ File saved to: $target</span><br>";
                echo "<img src='uploads/" . basename($target) . "' style='max-width: 200px; margin-top: 10px;'>";
            } else {
                echo "<span class='fail'>❌ Failed to move uploaded file</span><br>";
                echo "Error: " . error_get_last()['message'] . "<br>";
            }
        }
    } else {
        ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Select a test image:</label>
                <input type="file" name="test_file" class="form-control-file" accept="image/*" required>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-upload"></i> Test Upload
            </button>
        </form>
        <?php
    }
    echo "</div></div>";
    ?>

    <div class="card">
        <div class="card-body">
            <h4>Next Steps</h4>
            <p>If all checks pass but upload still fails, do this:</p>
            <ol>
                <li>Open <strong>Chrome DevTools (F12)</strong></li>
                <li>Go to <strong>Console</strong> tab</li>
                <li>Try uploading from <code>admin/profile.php</code></li>
                <li>Look for any errors in the console</li>
                <li>Go to <strong>Network</strong> tab</li>
                <li>Try uploading again</li>
                <li>Look at the POST request — check if it's sending the file</li>
            </ol>
        </div>
    </div>
</div>
</body>
</html>