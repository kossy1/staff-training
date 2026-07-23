<?php
// admin/edit-training.php - Edit Training with Naira
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

$training_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($training_id <= 0) {
    header('Location: trainings.php');
    exit();
}

// Get training data
$stmt = $conn->prepare("SELECT * FROM training_programs WHERE id = ?");
$stmt->bind_param("i", $training_id);
$stmt->execute();
$training = $stmt->get_result()->fetch_assoc();

if (!$training) {
    header('Location: trainings.php');
    exit();
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $form_data = [
        'title' => trim($_POST['title'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'type' => trim($_POST['type'] ?? 'technical'),
        'category' => trim($_POST['category'] ?? ''),
        'duration_hours' => (int)($_POST['duration_hours'] ?? 0),
        'start_date' => trim($_POST['start_date'] ?? ''),
        'end_date' => trim($_POST['end_date'] ?? ''),
        'location' => trim($_POST['location'] ?? ''),
        'trainer_name' => trim($_POST['trainer_name'] ?? ''),
        'trainer_email' => trim($_POST['trainer_email'] ?? ''),
        'max_participants' => (int)($_POST['max_participants'] ?? 20),
        'cost' => (float)($_POST['cost'] ?? 0),
        'status' => trim($_POST['status'] ?? 'upcoming')
    ];
    
    // Validation
    if (empty($form_data['title'])) $errors[] = "Training title is required";
    if (empty($form_data['start_date'])) $errors[] = "Start date is required";
    if (empty($form_data['end_date'])) $errors[] = "End date is required";
    if ($form_data['duration_hours'] <= 0) $errors[] = "Duration must be greater than 0";
    if ($form_data['max_participants'] <= 0) $errors[] = "Maximum participants must be greater than 0";
    
    if (empty($errors)) {
        $stmt = $conn->prepare("
            UPDATE training_programs SET 
                title = ?, description = ?, type = ?, category = ?, duration_hours = ?,
                start_date = ?, end_date = ?, location = ?, trainer_name = ?, trainer_email = ?,
                max_participants = ?, cost = ?, status = ?
            WHERE id = ?
        ");
        
        $stmt->bind_param(
            "ssssisssssidsi",
            $form_data['title'],
            $form_data['description'],
            $form_data['type'],
            $form_data['category'],
            $form_data['duration_hours'],
            $form_data['start_date'],
            $form_data['end_date'],
            $form_data['location'],
            $form_data['trainer_name'],
            $form_data['trainer_email'],
            $form_data['max_participants'],
            $form_data['cost'],
            $form_data['status'],
            $training_id
        );
        
        if ($stmt->execute()) {
            logAction($_SESSION['user_id'], 'training_updated', ['training_id' => $training_id]);
            $success = true;
            // Refresh training data
            $stmt = $conn->prepare("SELECT * FROM training_programs WHERE id = ?");
            $stmt->bind_param("i", $training_id);
            $stmt->execute();
            $training = $stmt->get_result()->fetch_assoc();
        } else {
            $errors[] = "Failed to update training: " . $conn->error;
        }
    }
}

$page_title = 'Edit Training';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <div>
                <h1><i class="fas fa-edit text-primary"></i> Edit Training</h1>
                <p class="text-muted">Update training #<?php echo $training['id']; ?></p>
            </div>
            <div>
                <a href="trainings.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Trainings
                </a>
            </div>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> Training updated successfully!
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
            <form method="POST">
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label class="required">Training Title</label>
                            <input type="text" name="title" class="form-control" 
                                   value="<?php echo htmlspecialchars($training['title']); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="required">Type</label>
                            <select name="type" class="form-control" required>
                                <option value="technical" <?php echo $training['type'] == 'technical' ? 'selected' : ''; ?>>Technical</option>
                                <option value="soft_skill" <?php echo $training['type'] == 'soft_skill' ? 'selected' : ''; ?>>Soft Skill</option>
                                <option value="management" <?php echo $training['type'] == 'management' ? 'selected' : ''; ?>>Management</option>
                                <option value="compliance" <?php echo $training['type'] == 'compliance' ? 'selected' : ''; ?>>Compliance</option>
                                <option value="other" <?php echo $training['type'] == 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($training['description']); ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Category</label>
                            <input type="text" name="category" class="form-control" 
                                   value="<?php echo htmlspecialchars($training['category']); ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required">Duration (Hours)</label>
                            <input type="number" name="duration_hours" class="form-control" 
                                   value="<?php echo htmlspecialchars($training['duration_hours']); ?>" required min="1">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required">Start Date</label>
                            <input type="date" name="start_date" class="form-control" 
                                   value="<?php echo htmlspecialchars($training['start_date']); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required">End Date</label>
                            <input type="date" name="end_date" class="form-control" 
                                   value="<?php echo htmlspecialchars($training['end_date']); ?>" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Location</label>
                            <input type="text" name="location" class="form-control" 
                                   value="<?php echo htmlspecialchars($training['location']); ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Trainer Name</label>
                            <input type="text" name="trainer_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($training['trainer_name']); ?>">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Trainer Email</label>
                            <input type="email" name="trainer_email" class="form-control" 
                                   value="<?php echo htmlspecialchars($training['trainer_email']); ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required">Max Participants</label>
                            <input type="number" name="max_participants" class="form-control" 
                                   value="<?php echo htmlspecialchars($training['max_participants']); ?>" required min="1">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Cost (₦ Naira)</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">₦</span>
                                </div>
                                <input type="number" step="0.01" name="cost" class="form-control" 
                                       value="<?php echo htmlspecialchars($training['cost']); ?>" 
                                       placeholder="0.00">
                            </div>
                            <small class="text-muted">Enter training cost in Naira (₦)</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="upcoming" <?php echo $training['status'] == 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                                <option value="ongoing" <?php echo $training['status'] == 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                                <option value="completed" <?php echo $training['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $training['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i> Update Training
                    </button>
                    <a href="trainings.php" class="btn btn-secondary btn-lg">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>