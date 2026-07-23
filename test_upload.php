<?php
// test_image_upload.php - Test image upload functionality
$message = '';
$upload_success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES['test_image']) && $_FILES['test_image']['error'] == 0) {
        $upload_dir = 'uploads/test/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $filename = time() . '_' . $_FILES['test_image']['name'];
        $upload_path = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['test_image']['tmp_name'], $upload_path)) {
            $upload_success = true;
            $message = "✅ Upload successful!";
            $image_path = $upload_path;
        } else {
            $message = "❌ Upload failed. Error: " . $_FILES['test_image']['error'];
        }
    } else {
        $message = "❌ No file uploaded or upload error occurred.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Image Upload</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <h4>Test Image Upload</h4>
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $upload_success ? 'success' : 'danger'; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($upload_success): ?>
                    <div class="text-center">
                        <img src="<?php echo $image_path; ?>" alt="Uploaded Image" class="img-fluid" style="max-height: 300px;">
                        <p class="mt-2"><strong>Path:</strong> <?php echo $image_path; ?></p>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Select Image</label>
                        <input type="file" name="test_image" class="form-control-file" accept="image/*" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </form>
                
                <hr>
                <h5>Upload Directory</h5>
                <p><strong>Path:</strong> <?php echo realpath('uploads/test/'); ?></p>
                <p><strong>Writable:</strong> <?php echo is_writable('uploads/test/') ? '✅ Yes' : '❌ No'; ?></p>
                
                <h5>PHP Info</h5>
                <ul>
                    <li><strong>upload_max_filesize:</strong> <?php echo ini_get('upload_max_filesize'); ?></li>
                    <li><strong>post_max_size:</strong> <?php echo ini_get('post_max_size'); ?></li>
                    <li><strong>file_uploads:</strong> <?php echo ini_get('file_uploads') ? '✅ On' : '❌ Off'; ?></li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>