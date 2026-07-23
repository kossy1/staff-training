<?php
// admin/add-certification.php - Issue Certification
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

$errors = [];
$success = false;
$form_data = [];

// Get employees for dropdown
$employees = $conn->query("
    SELECT DISTINCT e.id, e.first_name, e.last_name, e.email 
    FROM employees e 
    WHERE e.status = 'active' 
    ORDER BY e.first_name
");

// Get trainings for dropdown
$trainings = $conn->query("
    SELECT id, title 
    FROM training_programs 
    WHERE status = 'completed' 
    ORDER BY title
");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $form_data = [
        'employee_id' => (int)$_POST['employee_id'] ?? 0,
        'training_id' => (int)$_POST['training_id'] ?? 0,
        'certification_name' => trim($_POST['certification_name'] ?? ''),
        'issuing_authority' => trim($_POST['issuing_authority'] ?? ''),
        'issue_date' => trim($_POST['issue_date'] ?? ''),
        'expiry_date' => trim($_POST['expiry_date'] ?? ''),
        'status' => trim($_POST['status'] ?? 'active')
    ];
    
    // Validation
    if ($form_data['employee_id'] <= 0) $errors[] = "Please select an employee";
    if ($form_data['training_id'] <= 0) $errors[] = "Please select a training";
    if (empty($form_data['certification_name'])) $errors[] = "Certification name is required";
    if (empty($form_data['issue_date'])) $errors[] = "Issue date is required";
    
    // Handle file upload
    $file_path = '';
    if (empty($errors) && isset($_FILES['certificate_file']) && $_FILES['certificate_file']['error'] == 0) {
        $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
        $filename = $_FILES['certificate_file']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_filename = 'cert_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $upload_path = '../uploads/certificates/' . $new_filename;
            
            if (move_uploaded_file($_FILES['certificate_file']['tmp_name'], $upload_path)) {
                $file_path = $new_filename;
            } else {
                $errors[] = "Failed to upload certificate file";
            }
        } else {
            $errors[] = "Invalid file type. Allowed: PDF, JPG, JPEG, PNG";
        }
    }
    
    if (empty($errors)) {
        $cert_number = 'CERT' . date('Ymd') . rand(1000, 9999);
        
        $stmt = $conn->prepare("
            INSERT INTO certifications (
                employee_id, training_id, certification_name, issuing_authority,
                issue_date, expiry_date, certification_number, file_path, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param(
            "iisssssss",
            $form_data['employee_id'],
            $form_data['training_id'],
            $form_data['certification_name'],
            $form_data['issuing_authority'],
            $form_data['issue_date'],
            $form_data['expiry_date'],
            $cert_number,
            $file_path,
            $form_data['status']
        );
        
        if ($stmt->execute()) {
            $cert_id = $conn->insert_id;
            logAction($_SESSION['user_id'], 'certification_issued', ['certification_id' => $cert_id]);
            $success = true;
            $form_data = [];
        } else {
            $errors[] = "Failed to issue certification: " . $conn->error;
        }
    }
}

$page_title = 'Issue Certification';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <div>
                <h1><i class="fas fa-certificate text-primary"></i> Issue Certification</h1>
                <p class="text-muted">Issue a new certification to an employee</p>
            </div>
            <div>
                <a href="certifications.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Certifications
                </a>
            </div>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> Certification issued successfully!
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i>
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required">Employee</label>
                            <select name="employee_id" class="form-control select2" required>
                                <option value="">Select Employee</option>
                                <?php while ($emp = $employees->fetch_assoc()): ?>
                                    <option value="<?php echo $emp['id']; ?>" 
                                        <?php echo ($form_data['employee_id'] ?? '') == $emp['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $emp['email'] . ')'); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required">Training</label>
                            <select name="training_id" class="form-control select2" required>
                                <option value="">Select Training</option>
                                <?php while ($training = $trainings->fetch_assoc()): ?>
                                    <option value="<?php echo $training['id']; ?>" 
                                        <?php echo ($form_data['training_id'] ?? '') == $training['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($training['title']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required">Certification Name</label>
                            <input type="text" name="certification_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($form_data['certification_name'] ?? ''); ?>" 
                                   placeholder="Enter certification name" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Issuing Authority</label>
                            <input type="text" name="issuing_authority" class="form-control" 
                                   value="<?php echo htmlspecialchars($form_data['issuing_authority'] ?? ''); ?>" 
                                   placeholder="e.g., AWS, Microsoft, etc.">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="required">Issue Date</label>
                            <input type="date" name="issue_date" class="form-control" 
                                   value="<?php echo htmlspecialchars($form_data['issue_date'] ?? date('Y-m-d')); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Expiry Date</label>
                            <input type="date" name="expiry_date" class="form-control" 
                                   value="<?php echo htmlspecialchars($form_data['expiry_date'] ?? ''); ?>">
                            <small class="text-muted">Leave empty if no expiry</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="active" <?php echo ($form_data['status'] ?? 'active') == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="expired" <?php echo ($form_data['status'] ?? '') == 'expired' ? 'selected' : ''; ?>>Expired</option>
                                <option value="revoked" <?php echo ($form_data['status'] ?? '') == 'revoked' ? 'selected' : ''; ?>>Revoked</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Certificate File</label>
                    <input type="file" name="certificate_file" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png">
                    <small class="text-muted">Max size: 5MB (PDF, JPG, JPEG, PNG)</small>
                </div>

                <div class="text-center mt-3">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-certificate"></i> Issue Certification
                    </button>
                    <a href="certifications.php" class="btn btn-secondary btn-lg">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>