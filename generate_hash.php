<?php
// generate_hash.php - Generate password hashes
?>
<!DOCTYPE html>
<html>
<head>
    <title>Generate Password Hash</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>body { padding: 40px; background: #f8f9fc; } pre { background: #1a1a2e; color: #48bb78; padding: 20px; border-radius: 8px; }</style>
</head>
<body>
<div class="container">
    <h1>🔐 Password Hash Generator</h1>
    <p class="text-muted">Generate a hash for any password. Use this to update your database.</p>
    
    <?php
    $passwords = ['admin123', 'employee123', 'trainer123', 'password'];
    
    if (isset($_POST['custom_password']) && !empty($_POST['custom_password'])) {
        $passwords = [$_POST['custom_password']];
    }
    ?>
    
    <form method="POST" class="mb-4">
        <div class="form-group">
            <label><strong>Enter a custom password:</strong></label>
            <div class="input-group">
                <input type="text" name="custom_password" class="form-control" 
                       placeholder="Enter password to hash" 
                       value="<?php echo htmlspecialchars($_POST['custom_password'] ?? ''); ?>">
                <div class="input-group-append">
                    <button type="submit" class="btn btn-primary">Generate Hash</button>
                </div>
            </div>
        </div>
    </form>
    
    <h3>Default Passwords (Admin/Employee/Trainer):</h3>
    
    <?php foreach ($passwords as $password): ?>
        <?php $hash = password_hash($password, PASSWORD_DEFAULT); ?>
        
        <div class="card mb-3">
            <div class="card-header">
                <strong>Password:</strong> <code><?php echo htmlspecialchars($password); ?></code>
            </div>
            <div class="card-body">
                <p class="mb-2"><strong>Hash (copy this):</strong></p>
                <pre id="hash_<?php echo md5($password); ?>"><?php echo $hash; ?></pre>
                <button class="btn btn-sm btn-success" onclick="copyHash('<?php echo $hash; ?>', this)">
                    <i class="fas fa-copy"></i> Copy Hash
                </button>
                
                <p class="mt-3 mb-2"><strong>SQL to update:</strong></p>
                <pre>UPDATE users SET password = '<?php echo $hash; ?>' WHERE email = 'admin@example.com';</pre>
                <button class="btn btn-sm btn-info" onclick="copySql('<?php echo $hash; ?>', this)">
                    <i class="fas fa-database"></i> Copy SQL
                </button>
            </div>
        </div>
    <?php endforeach; ?>
    
    <hr>
    <div class="card border-success">
        <div class="card-header bg-success text-white">
            <strong>⚡ One-Click Fix — Update All Default Users</strong>
        </div>
        <div class="card-body">
            <p>This will reset ALL default user passwords to their documented values:</p>
            <ul>
                <li>admin@example.com → admin123</li>
                <li>employee@example.com → employee123</li>
                <li>trainer@example.com → trainer123</li>
            </ul>
            
            <?php
            if (isset($_POST['fix_now'])) {
                $users = [
                    'admin@example.com' => 'admin123',
                    'employee@example.com' => 'employee123',
                    'trainer@example.com' => 'trainer123'
                ];
                
                require_once 'includes/config.php';
                
                echo "<div class='alert alert-info'><strong>Processing...</strong></div>";
                
                foreach ($users as $email => $password) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
                    $stmt->bind_param("ss", $hash, $email);
                    
                    if ($stmt->execute()) {
                        if ($stmt->affected_rows > 0) {
                            echo "<p class='text-success'>✅ Updated <strong>{$email}</strong> → password: <code>{$password}</code></p>";
                        } else {
                            echo "<p class='text-warning'>⚠️ No user found with email: <strong>{$email}</strong> (skipped)</p>";
                        }
                    } else {
                        echo "<p class='text-danger'>❌ Failed for {$email}: " . $conn->error . "</p>";
                    }
                }
                
                echo "<hr>";
                echo "<h4 class='text-success'>✅ All passwords reset!</h4>";
                echo "<p><strong>Login now with:</strong></p>";
                echo "<ul>";
                echo "<li>Admin: <code>admin@example.com</code> / <code>admin123</code></li>";
                echo "<li>Employee: <code>employee@example.com</code> / <code>employee123</code></li>";
                echo "<li>Trainer: <code>trainer@example.com</code> / <code>trainer123</code></li>";
                echo "</ul>";
                echo "<a href='login.php' class='btn btn-primary'>Go to Login</a>";
            } else {
            ?>
                <form method="POST">
                    <button type="submit" name="fix_now" class="btn btn-success btn-lg">
                        <i class="fas fa-wrench"></i> FIX ALL PASSWORDS NOW
                    </button>
                </form>
            <?php } ?>
        </div>
    </div>
    
    <hr>
    <div class="card mt-4">
        <div class="card-header"><strong>📋 Manual SQL (Run in phpMyAdmin)</strong></div>
        <div class="card-body">
            <p>Copy and paste this into phpMyAdmin's SQL tab:</p>
<pre style="background: #1a1a2e; color: #48bb78; padding: 20px; border-radius: 8px; font-size: 12px;">
-- Reset all default users
UPDATE users SET password = '<?php echo password_hash('admin123', PASSWORD_DEFAULT); ?>' WHERE email = 'admin@example.com';
UPDATE users SET password = '<?php echo password_hash('employee123', PASSWORD_DEFAULT); ?>' WHERE email = 'employee@example.com';
UPDATE users SET password = '<?php echo password_hash('trainer123', PASSWORD_DEFAULT); ?>' WHERE email = 'trainer@example.com';

-- Verify
SELECT email, role, LENGTH(password) as hash_length FROM users;
</pre>
        </div>
    </div>
    
</div>

<script>
function copyHash(hash, btn) {
    navigator.clipboard.writeText(hash).then(function() {
        var original = btn.innerHTML;
        btn.innerHTML = '✅ Copied!';
        setTimeout(function() { btn.innerHTML = original; }, 1500);
    });
}

function copySql(hash, btn) {
    var sql = "UPDATE users SET password = '" + hash + "' WHERE email = 'admin@example.com';";
    navigator.clipboard.writeText(sql).then(function() {
        var original = btn.innerHTML;
        btn.innerHTML = '✅ SQL Copied!';
        setTimeout(function() { btn.innerHTML = original; }, 1500);
    });
}
</script>
</body>
</html>