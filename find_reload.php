<?php
// find_reload.php - Find auto-reload code in your project
$root = __DIR__;

$patterns = [
    'window.location.reload' => 'Auto page reload',
    'location.reload' => 'Auto page reload',
    'location.href = location' => 'Auto page reload',
    'location.href=location' => 'Auto page reload',
    'setInterval(' => 'Possible loop (check if it reloads)',
    'setTimeout(' => 'Possible loop (check if it reloads)',
    'meta http-equiv="refresh"' => 'HTML meta refresh',
    "meta http-equiv='refresh'" => 'HTML meta refresh',
    '.submit();' => 'Auto form submit',
    '.submit()' => 'Auto form submit',
    'history.go(' => 'History navigation',
    'location.replace(' => 'Location replace',
];

// Get all PHP, JS, HTML files
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS)
);

$found = [];

foreach ($iterator as $file) {
    if ($file->isFile()) {
        $ext = strtolower($file->getExtension());
        if (!in_array($ext, ['php', 'js', 'html', 'htm'])) continue;
        
        // Skip this file
        if ($file->getPathname() == __FILE__) continue;
        
        $content = file_get_contents($file->getPathname());
        $lines = explode("\n", $content);
        
        foreach ($patterns as $pattern => $desc) {
            foreach ($lines as $line_num => $line) {
                // Skip commented lines
                $trimmed = trim($line);
                if (strpos($trimmed, '//') === 0 || strpos($trimmed, '*') === 0) continue;
                
                if (stripos($line, $pattern) !== false) {
                    $relative = str_replace($root, '', $file->getPathname());
                    $found[] = [
                        'file' => $relative,
                        'line' => $line_num + 1,
                        'code' => trim($line),
                        'pattern' => $desc,
                        'raw_pattern' => $pattern
                    ];
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Find Auto-Reload Code</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
        body { padding: 30px; background: #f8f9fc; font-family: monospace; }
        .hit { background: #fff5f5; border-left: 4px solid #e74a3b; padding: 15px; margin: 10px 0; border-radius: 8px; }
        .info { background: #e7f3ff; border-left: 4px solid #0284c7; padding: 15px; margin: 10px 0; border-radius: 8px; }
        code { background: #1a1a2e; color: #48bb78; padding: 3px 8px; border-radius: 4px; font-size: 13px; }
        .badge { font-size: 11px; padding: 4px 8px; }
    </style>
</head>
<body>
<div class="container-fluid">
    <h1>🔍 Auto-Reload Code Finder</h1>
    
    <?php if (empty($found)): ?>
        <div class="alert alert-success">
            <h4>✅ No auto-reload code found!</h4>
            <p>The issue must be elsewhere. Check the browser console for JavaScript errors.</p>
        </div>
    <?php else: ?>
        <div class="alert alert-danger">
            <h4>⚠️ Found <?php echo count($found); ?> potential auto-reload issue(s)</h4>
            <p>Review each location below and remove or fix the problematic code.</p>
        </div>
        
        <?php 
        // Group by file
        $by_file = [];
        foreach ($found as $item) {
            $by_file[$item['file']][] = $item;
        }
        
        foreach ($by_file as $file => $items): 
        ?>
            <div class="card mb-3">
                <div class="card-header bg-dark text-white">
                    <strong>📄 <?php echo htmlspecialchars($file); ?></strong>
                    <span class="badge badge-danger float-right"><?php echo count($items); ?> issue(s)</span>
                </div>
                <div class="card-body">
                    <?php foreach ($items as $item): ?>
                        <div class="hit">
                            <div><strong>Line <?php echo $item['line']; ?>:</strong> 
                                <span class="badge badge-warning"><?php echo $item['pattern']; ?></span>
                            </div>
                            <div style="margin-top: 8px;">
                                <code><?php echo htmlspecialchars($item['code']); ?></code>
                            </div>
                            <div style="margin-top: 8px; font-size: 12px; color: #666;">
                                <strong>Why this is a problem:</strong>
                                <?php 
                                switch($item['raw_pattern']) {
                                    case 'window.location.reload':
                                    case 'location.reload':
                                    case 'location.href = location':
                                    case 'location.href=location':
                                        echo "This reloads the page. If it's inside a loop or on page load, it causes infinite reload.";
                                        break;
                                    case 'setInterval(':
                                        echo "Runs repeatedly. If it calls location.reload() or form.submit(), it creates a loop.";
                                        break;
                                    case 'setTimeout(':
                                        echo "Runs once after delay. If it calls location.reload() or form.submit(), it may loop.";
                                        break;
                                    case 'meta http-equiv="refresh"':
                                        echo "This meta tag auto-refreshes the page after a delay. Remove it.";
                                        break;
                                    case '.submit();':
                                    case '.submit()':
                                        echo "Auto-submits a form. If the form re-renders on load, it loops.";
                                        break;
                                    default:
                                        echo "May cause unexpected behavior. Review carefully.";
                                }
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <div class="card mt-4">
        <div class="card-header bg-primary text-white">
            <strong>🎯 Common Culprits to Check</strong>
        </div>
        <div class="card-body">
            <p>Auto-reload usually comes from these locations:</p>
            <ol>
                <li><strong>admin/includes/footer.php</strong> — Check for any <code>setInterval</code> or <code>location.reload</code></li>
                <li><strong>admin/includes/header.php</strong> — Check for meta refresh tags</li>
                <li><strong>admin/includes/navbar.php</strong> — Check for notification refresh loops</li>
                <li><strong>admin/includes/sidebar.php</strong> — Check for sidebar reload code</li>
                <li><strong>admin/profile.php</strong> — Check for form auto-submit</li>
                <li><strong>assets/js/admin-scripts.js</strong> — Check for any reload code</li>
                <li><strong>includes/functions.php</strong> — Check for <code>header('Refresh:')</code> PHP calls</li>
            </ol>
        </div>
    </div>
    
    <div class="card mt-3">
        <div class="card-header bg-warning">
            <strong>📋 How to Fix Each Issue</strong>
        </div>
        <div class="card-body">
            <h6>If you see <code>setInterval(function() { location.reload(); }, X)</code>:</h6>
            <p>Change to a longer interval OR remove entirely:
            <br><code>// setInterval(function() { location.reload(); }, 5000);</code>
            </p>
            
            <h6>If you see <code>window.location.reload()</code> in a page's &lt;script&gt; tag:</h6>
            <p>Remove the entire line. Consider using AJAX instead.</p>
            
            <h6>If you see a <code>&lt;meta http-equiv="refresh"&gt;</code> tag:</h6>
            <p>Remove the meta tag from the &lt;head&gt; section.</p>
            
            <h6>If you see a form with <code>onload</code> or auto-submit:</h6>
            <p>Remove the auto-submit. Use a proper submit button.</p>
        </div>
    </div>
</div>
</body>
</html>