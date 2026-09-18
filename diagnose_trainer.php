<?php
// diagnose_trainer.php - Diagnose and FIX trainer login issues
require_once 'includes/config.php';

// Change these to your actual trainer credentials
$TEST_EMAIL = 'trainer@example.com';  // ⭐ CHANGE THIS
$TEST_PASSWORD = 'trainer123';         // ⭐ CHANGE THIS

?>
<!DOCTYPE html>
<html>
<head>
    <title>Trainer Login Diagnostic</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
        body { background: #f8f9fc; padding: 30px; font-family: Arial; }
        .ok { color: #48bb78; font-weight: bold; }
        .fail { color: #e74a3b; font-weight: bold; }
        .card { margin-bottom: 15px; border-radius: 10px; }
        pre { background: #1a1a2e; color: #48bb78; padding: 15px; border-radius: 8px; }
        .step { background: white; padding: 20px; border-radius: 10px; margin-bottom: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 Trainer Login Diagnostic</h1>
    <p class="text-muted">Testing: <strong><?php echo htmlspecialchars($TEST_EMAIL); ?></strong></p>

<?php
// ===== STEP 1: Check users table =====
echo "<div class='step'>";
echo "<h4>Step 1: Check <code>users</code> Table</h4>";

$stmt = $conn->prepare("SELECT id, username, email, role, password FROM users WHERE email = ?");
$stmt->bind_param("s", $TEST_EMAIL);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user) {
    echo "<p class='ok'>✅ User EXISTS in users table</p>";
    echo "<table class='table table-sm'>";
    echo "<tr><td><strong>ID</strong></td><td>{$user['id']}</td></tr>";
    echo "<tr><td><strong>Username</strong></td><td>{$user['username']}</td></tr>";
    echo "<tr><td><strong>Email</strong></td><td>{$user['email']}</td></tr>";
    echo "<tr><td><strong>Role</strong></td><td>" . 
        ($user['role'] === 'trainer' ? "<span class='ok'>{$user['role']}</span>" : "<span class='fail'>{$user['role']} ❌ (should be 'trainer')</span>") . 
        "</td></tr>";
    echo "</table>";
} else {
    echo "<p class='fail'>❌ User NOT FOUND in users table</p>";
    echo "<p>Fix: Run the SQL below to create the user account.</p>";
}
echo "</div>";

// ===== STEP 2: Check trainers table =====
echo "<div class='step'>";
echo "<h4>Step 2: Check <code>trainers</code> Table</h4>";

$stmt = $conn->prepare("SELECT id, first_name, last_name, email, user_id, status FROM trainers WHERE email = ?");
$stmt->bind_param("s", $TEST_EMAIL);
$stmt->execute();
$trainer = $stmt->get_result()->fetch_assoc();

if ($trainer) {
    echo "<p class='ok'>✅ Trainer EXISTS in trainers table</p>";
    echo "<table class='table table-sm'>";
    echo "<tr><td><strong>Trainer ID</strong></td><td>{$trainer['id']}</td></tr>";
    echo "<tr><td><strong>Name</strong></td><td>{$trainer['first_name']} {$trainer['last_name']}</td></tr>";
    echo "<tr><td><strong>Email</strong></td><td>{$trainer['email']}</td></tr>";
    echo "<tr><td><strong>user_id (link)</strong></td><td>" . 
        (empty($trainer['user_id']) ? "<span class='fail'>NULL ❌</span>" : "<span class='ok'>{$trainer['user_id']}</span>") . 
        "</td></tr>";
    echo "<tr><td><strong>Status</strong></td><td>{$trainer['status']}</td></tr>";
    echo "</table>";
} else {
    echo "<p class='fail'>❌ Trainer NOT FOUND in trainers table</p>";
}
echo "</div>";

// ===== STEP 3: Test password =====
echo "<div class='step'>";
echo "<h4>Step 3: Test Password</h4>";

if ($user) {
    if (password_verify($TEST_PASSWORD, $user['password'])) {
        echo "<p class='ok'>✅ Password CORRECT — login should work!</p>";
    } else {
        echo "<p class='fail'>❌ Password INCORRECT</p>";
        echo "<p>The password you're trying doesn't match the stored hash.</p>";
    }
} else {
    echo "<p class='text-muted'>Skipped — no user found</p>";
}
echo "</div>";

// ===== STEP 4: Check session variables that WOULD be set =====
echo "<div class='step'>";
echo "<h4>Step 4: Simulate Login Session</h4>";

if ($user) {
    echo "<p>If login succeeds, these session variables will be set:</p>";
    echo "<pre>";
    echo "\$_SESSION['user_id']     = {$user['id']}\n";
    echo "\$_SESSION['username']    = {$user['username']}\n";
    echo "\$_SESSION['role']        = {$user['role']}\n";
    echo "\$_SESSION['email']       = {$user['email']}\n";
    echo "\$_SESSION['logged_in']   = true\n";
    
    if ($trainer) {
        echo "\$_SESSION['trainer_id']  = {$trainer['id']}\n";
    } else {
        echo "\$_SESSION['trainer_id']  = ❌ NOT SET (no trainer record)\n";
    }
    echo "</pre>";
    
    // Determine expected redirect
    echo "<p><strong>Expected redirect:</strong> ";
    if ($user['role'] === 'trainer') {
        echo "<code class='ok'>trainer/dashboard.php</code></p>";
    } elseif ($user['role'] === 'admin') {
        echo "<code>admin/dashboard.php</code></p>";
    } else {
        echo "<code>employee/dashboard.php</code></p>";
    }
}
echo "</div>";

// ===== STEP 5: Check if trainer/dashboard.php exists =====
echo "<div class='step'>";
echo "<h4>Step 5: Check Files</h4>";

$files = [
    'trainer/dashboard.php' => 'Trainer Dashboard',
    'trainer/includes/header.php' => 'Trainer Header',
    'trainer/includes/sidebar.php' => 'Trainer Sidebar',
    'trainer/includes/navbar.php' => 'Trainer Navbar',
    'trainer/includes/footer.php' => 'Trainer Footer',
];

foreach ($files as $file => $label) {
    if (file_exists($file)) {
        echo "<p class='ok'>✅ {$label}: <code>{$file}</code> exists</p>";
    } else {
        echo "<p class='fail'>❌ {$label}: <code>{$file}</code> MISSING!</p>";
    }
}
echo "</div>";

// ===== STEP 6: Check uploads directory =====
echo "<div class='step'>";
echo "<h4>Step 6: Check Uploads Folder</h4>";

$uploads = [
    'uploads/trainers' => 'Trainer Uploads',
    'uploads/profile-pictures' => 'Employee Uploads',
];

foreach ($uploads as $dir => $label) {
    if (is_dir($dir)) {
        echo "<p class='ok'>✅ {$label}: <code>{$dir}</code> exists</p>";
    } else {
        echo "<p class='fail'>❌ {$label}: <code>{$dir}</code> MISSING — creating...</p>";
        if (mkdir($dir, 0777, true)) {
            echo "<p class='ok'>✅ Created {$dir}</p>";
        }
    }
}
echo "</div>";

// ===== FIX SECTION =====
echo "<div class='step'>";
echo "<h4>🔧 One-Click Fix</h4>";

if ($user && $trainer) {
    // Both records exist — link them
    if (empty($trainer['user_id']) || $trainer['user_id'] != $user['id']) {
        echo "<p>Linking trainer to user account...</p>";
        $conn->query("UPDATE trainers SET user_id = {$user['id']} WHERE id = {$trainer['id']}");
        echo "<p class='ok'>✅ Trainer linked to user</p>";
    }
    
    // Fix role if wrong
    if ($user['role'] !== 'trainer') {
        echo "<p>Fixing role...</p>";
        $conn->query("UPDATE users SET role = 'trainer' WHERE id = {$user['id']}");
        echo "<p class='ok'>✅ Role set to 'trainer'</p>";
    }
    
    // Reset password if requested
    if (isset($_GET['reset_password']) && $_GET['reset_password'] == '1') {
        $new_hash = password_hash($TEST_PASSWORD, PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password = '$new_hash' WHERE id = {$user['id']}");
        echo "<p class='ok'>✅ Password reset to: <strong>{$TEST_PASSWORD}</strong></p>";
    }
    
    echo "<hr>";
    echo "<h5 class='ok'>✅ Everything looks good!</h5>";
    echo "<p><a href='login.php' class='btn btn-primary'>Try Login Now</a> ";
    echo "<a href='?reset_password=1&t=" . time() . "' class='btn btn-warning'>Reset Password to '{$TEST_PASSWORD}'</a></p>";
    
} elseif (!$user && $trainer) {
    // Trainer exists but no user — CREATE one
    echo "<p>Creating user account for existing trainer...</p>";
    
    $username = strtolower(preg_replace('/[^a-z0-9]/', '', explode('@', $TEST_EMAIL)[0]));
    $hash = password_hash($TEST_PASSWORD, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'trainer')");
    $stmt->bind_param("sss", $username, $TEST_EMAIL, $hash);
    $stmt->execute();
    $new_user_id = $conn->insert_id;
    
    $conn->query("UPDATE trainers SET user_id = $new_user_id WHERE id = {$trainer['id']}");
    
    echo "<p class='ok'>✅ User created with ID: {$new_user_id}</p>";
    echo "<p class='ok'>✅ Trainer linked</p>";
    echo "<p><strong>Login:</strong> {$TEST_EMAIL} / {$TEST_PASSWORD}</p>";
    echo "<p><a href='login.php' class='btn btn-primary'>Try Login Now</a></p>";
    
} elseif ($user && !$trainer) {
    // User exists but no trainer — needs trainer record
    echo "<p class='fail'>❌ User exists but no trainer record!</p>";
    echo "<p>You need to create a trainer record. Go to <a href='admin/add-trainer.php'>Add Trainer</a></p>";
    
} else {
    // Neither exists
    echo "<p class='fail'>❌ No user AND no trainer found!</p>";
    echo "<p>Go to <a href='admin/add-trainer.php' class='btn btn-primary'>Add Trainer</a> to create one.</p>";
}
echo "</div>";

// ===== Show all trainers =====
echo "<div class='step'>";
echo "<h4>📋 All Trainers in Database</h4>";

$all = $conn->query("
    SELECT t.id as trainer_id, t.first_name, t.last_name, t.email, 
           t.user_id, t.status,
           u.id as uid, u.username, u.role
    FROM trainers t
    LEFT JOIN users u ON u.id = t.user_id OR u.email = t.email
    ORDER BY t.id DESC
");

if ($all && $all->num_rows > 0) {
    echo "<div class='table-responsive'><table class='table table-sm table-bordered'>";
    echo "<thead class='thead-dark'><tr>
        <th>ID</th><th>Name</th><th>Email</th><th>user_id</th>
        <th>User ID</th><th>Username</th><th>Role</th><th>Status</th>
    </tr></thead><tbody>";
    
    while ($t = $all->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$t['trainer_id']}</td>";
        echo "<td>{$t['first_name']} {$t['last_name']}</td>";
        echo "<td>{$t['email']}</td>";
        echo "<td>" . (empty($t['user_id']) ? "<span class='fail'>NULL</span>" : $t['user_id']) . "</td>";
        echo "<td>" . (empty($t['uid']) ? "<span class='fail'>NONE</span>" : $t['uid']) . "</td>";
        echo "<td>" . ($t['username'] ?? '-') . "</td>";
        echo "<td>" . ($t['role'] === 'trainer' ? "<span class='ok'>{$t['role']}</span>" : "<span class='fail'>" . ($t['role'] ?? 'NO USER') . "</span>") . "</td>";
        echo "<td>{$t['status']}</td>";
        echo "</tr>";
    }
    echo "</tbody></table></div>";
} else {
    echo "<p>No trainers found.</p>";
}
echo "</div>";
?>

    <div class="step">
        <h4>🔑 Login Credentials to Try</h4>
        <table class="table table-sm">
            <tr>
                <td><strong>Email:</strong></td>
                <td><code><?php echo htmlspecialchars($TEST_EMAIL); ?></code></td>
            </tr>
            <tr>
                <td><strong>Password:</strong></td>
                <td><code><?php echo htmlspecialchars($TEST_PASSWORD); ?></code></td>
            </tr>
        </table>
        <a href="login.php" class="btn btn-primary">Go to Login</a>
        <a href="diagnose_trainer.php?reset_password=1" class="btn btn-warning">Reset Password</a>
    </div>

</div>
</body>
</html>