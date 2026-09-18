<?php
// admin/add-training.php - Add Training with Trainer Selection
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

$errors = [];
$success = false;
$form_data = [];

// ===== GET TRAINERS FOR DROPDOWN =====
$trainers_list = [];
$trainers_query = $conn->query("
    SELECT id, first_name, last_name, email, specialization, qualification, experience_years 
    FROM trainers 
    WHERE status = 'active' 
    ORDER BY first_name ASC
");
if ($trainers_query && $trainers_query->num_rows > 0) {
    while ($t = $trainers_query->fetch_assoc()) {
        $trainers_list[] = $t;
    }
}

// ===== GET EXISTING CATEGORIES =====
$categories = $conn->query("
    SELECT DISTINCT category 
    FROM training_programs 
    WHERE category IS NOT NULL AND category != '' 
    ORDER BY category
");

// ===== HANDLE FORM SUBMISSION =====
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
        'trainer_id' => !empty($_POST['trainer_id']) ? (int)$_POST['trainer_id'] : null,
        'trainer_name' => trim($_POST['trainer_name'] ?? ''),
        'trainer_email' => trim($_POST['trainer_email'] ?? ''),
        'max_participants' => (int)($_POST['max_participants'] ?? 20),
        'cost' => (float)($_POST['cost'] ?? 0),
        'status' => trim($_POST['status'] ?? 'upcoming'),
        'prerequisites' => trim($_POST['prerequisites'] ?? ''),
        'learning_objectives' => trim($_POST['learning_objectives'] ?? ''),
        'is_certified' => isset($_POST['is_certified']) ? 1 : 0
    ];
    
    // If trainer_id is selected, auto-fill name and email
    if ($form_data['trainer_id']) {
        $stmt = $conn->prepare("SELECT first_name, last_name, email FROM trainers WHERE id = ?");
        $stmt->bind_param("i", $form_data['trainer_id']);
        $stmt->execute();
        $trainer_data = $stmt->get_result()->fetch_assoc();
        if ($trainer_data) {
            $form_data['trainer_name'] = $trainer_data['first_name'] . ' ' . $trainer_data['last_name'];
            $form_data['trainer_email'] = $trainer_data['email'];
        }
    }
    
    // ===== VALIDATION =====
    if (empty($form_data['title'])) {
        $errors[] = "Training title is required";
    }
    if (empty($form_data['start_date'])) {
        $errors[] = "Start date is required";
    }
    if (empty($form_data['end_date'])) {
        $errors[] = "End date is required";
    }
    if ($form_data['duration_hours'] <= 0) {
        $errors[] = "Duration must be greater than 0";
    }
    if ($form_data['max_participants'] <= 0) {
        $errors[] = "Maximum participants must be greater than 0";
    }
    if ($form_data['start_date'] && $form_data['end_date'] && $form_data['start_date'] > $form_data['end_date']) {
        $errors[] = "Start date cannot be after end date";
    }
    if ($form_data['trainer_email'] && !filter_var($form_data['trainer_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid trainer email format";
    }
    
    // Check duplicate title
    if (empty($errors)) {
        $check = $conn->prepare("SELECT id FROM training_programs WHERE title = ?");
        $check->bind_param("s", $form_data['title']);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $errors[] = "A training with this title already exists!";
        }
    }
    
    // ===== CREATE TRAINING =====
    if (empty($errors)) {
        // Check if trainer_id column exists
        $columns = $conn->query("SHOW COLUMNS FROM training_programs LIKE 'trainer_id'");
        $has_trainer_id = $columns->num_rows > 0;
        
        // Check if prerequisites and learning_objectives exist
        $columns = $conn->query("SHOW COLUMNS FROM training_programs LIKE 'prerequisites'");
        $has_prerequisites = $columns->num_rows > 0;
        
        $columns = $conn->query("SHOW COLUMNS FROM training_programs LIKE 'learning_objectives'");
        $has_objectives = $columns->num_rows > 0;
        
        $columns = $conn->query("SHOW COLUMNS FROM training_programs LIKE 'is_certified'");
        $has_certified = $columns->num_rows > 0;
        
        // Build dynamic INSERT
        $fields = "title, description, type, category, duration_hours, start_date, end_date, location, trainer_name, trainer_email, max_participants, cost, status, created_by";
        $placeholders = "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?";
        $types = "ssssisssssidsi";
        $params = [
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
            $_SESSION['user_id']
        ];
        
        if ($has_trainer_id) {
            $fields .= ", trainer_id";
            $placeholders .= ", ?";
            $types .= "i";
            $params[] = $form_data['trainer_id'];
        }
        
        if ($has_prerequisites) {
            $fields .= ", prerequisites";
            $placeholders .= ", ?";
            $types .= "s";
            $params[] = $form_data['prerequisites'];
        }
        
        if ($has_objectives) {
            $fields .= ", learning_objectives";
            $placeholders .= ", ?";
            $types .= "s";
            $params[] = $form_data['learning_objectives'];
        }
        
        if ($has_certified) {
            $fields .= ", is_certified";
            $placeholders .= ", ?";
            $types .= "i";
            $params[] = $form_data['is_certified'];
        }
        
        $sql = "INSERT INTO training_programs ($fields) VALUES ($placeholders)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            $training_id = $conn->insert_id;
            
            // Update trainer's total_trainings count
            if ($has_trainer_id && $form_data['trainer_id']) {
                $conn->query("UPDATE trainers SET total_trainings = total_trainings + 1 WHERE id = {$form_data['trainer_id']}");
            }
            
            logAction($_SESSION['user_id'], 'training_created', ['training_id' => $training_id]);
            $success = true;
            $form_data = [];
            
            // Redirect after success
            echo "<script>setTimeout(function(){ window.location.href='trainings.php?success=created'; }, 1500);</script>";
        } else {
            $errors[] = "Failed to create training: " . $conn->error;
        }
    }
}

$page_title = 'Add Training';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<style>
.training-form-section {
    background: #f8f9fc;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    border-left: 4px solid #667eea;
}
.section-title {
    font-weight: 700;
    color: #2d3748;
    margin-bottom: 15px;
    font-size: 1.05rem;
}
.section-title i {
    color: #667eea;
    margin-right: 8px;
}
.required-field::after {
    content: " *";
    color: #e74a3b;
    font-weight: bold;
}
.cost-preview {
    font-size: 1.5rem;
    font-weight: 700;
    color: #28a745;
    text-align: center;
    padding: 15px;
    background: #f8f9fc;
    border-radius: 8px;
    border: 2px dashed #28a745;
}
.form-hint {
    font-size: 0.8rem;
    color: #6c757d;
    margin-top: 4px;
}
.trainer-option {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.trainer-info-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 10px;
    margin-top: 15px;
    display: none;
}
.trainer-info-card.show {
    display: block;
}
.trainer-info-card h6 {
    color: white;
    margin-bottom: 10px;
}
.trainer-info-card .info-row {
    display: flex;
    justify-content: space-between;
    padding: 5px 0;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    font-size: 0.9rem;
}
.trainer-info-card .info-row:last-child {
    border-bottom: none;
}
.trainer-info-card .info-row span:first-child {
    opacity: 0.8;
}
.trainer-info-card .info-row span:last-child {
    font-weight: 600;
}
.select2-container {
    width: 100% !important;
}
</style>

<div class="main-content">
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <div>
                <h1><i class="fas fa-plus-circle text-primary"></i> Add New Training</h1>
                <p class="text-muted">Create a new training program with detailed information</p>
            </div>
            <div>
                <a href="trainings.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Trainings
                </a>
            </div>
        </div>
    </div>

    <!-- Success Message -->
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> 
            Training created successfully! Redirecting to trainings list...
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Error Messages -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i>
            <strong>Please fix the following errors:</strong>
            <ul class="mb-0 mt-2">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Training Form -->
    <form method="POST" id="trainingForm">
        <div class="row">
            <div class="col-lg-8">
                <!-- Basic Info -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="training-form-section">
                            <div class="form-group">
                                <label class="required-field">Training Title</label>
                                <input type="text" name="title" id="title" class="form-control form-control-lg" 
                                       value="<?php echo htmlspecialchars($form_data['title'] ?? ''); ?>" 
                                       placeholder="Enter a descriptive training title" required maxlength="255">
                                <div class="form-hint">Be specific and descriptive. Max 255 characters.</div>
                            </div>
                            
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" class="form-control" rows="4" 
                                          placeholder="Provide a detailed description of the training program"><?php echo htmlspecialchars($form_data['description'] ?? ''); ?></textarea>
                                <div class="form-hint">Include what participants will learn and who it's for.</div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="required-field">Training Type</label>
                                        <select name="type" id="type" class="form-control" required>
                                            <option value="technical" <?php echo ($form_data['type'] ?? '') == 'technical' ? 'selected' : ''; ?>>Technical</option>
                                            <option value="soft_skill" <?php echo ($form_data['type'] ?? '') == 'soft_skill' ? 'selected' : ''; ?>>Soft Skill</option>
                                            <option value="management" <?php echo ($form_data['type'] ?? '') == 'management' ? 'selected' : ''; ?>>Management</option>
                                            <option value="compliance" <?php echo ($form_data['type'] ?? '') == 'compliance' ? 'selected' : ''; ?>>Compliance</option>
                                            <option value="other" <?php echo ($form_data['type'] ?? '') == 'other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Category</label>
                                        <input type="text" name="category" id="category" class="form-control" 
                                               list="categoryList"
                                               value="<?php echo htmlspecialchars($form_data['category'] ?? ''); ?>" 
                                               placeholder="Select or type category">
                                        <datalist id="categoryList">
                                            <?php while ($cat = $categories->fetch_assoc()): ?>
                                                <option value="<?php echo htmlspecialchars($cat['category']); ?>">
                                            <?php endwhile; ?>
                                            <option value="Programming">
                                            <option value="Leadership">
                                            <option value="Communication">
                                            <option value="Project Management">
                                            <option value="Data Analysis">
                                            <option value="AI & Machine Learning">
                                            <option value="Cloud Computing">
                                            <option value="Cybersecurity">
                                            <option value="Agile Methodologies">
                                        </datalist>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Prerequisites</label>
                                <textarea name="prerequisites" class="form-control" rows="2" 
                                          placeholder="List any prerequisites for this training"><?php echo htmlspecialchars($form_data['prerequisites'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>Learning Objectives</label>
                                <textarea name="learning_objectives" class="form-control" rows="3" 
                                          placeholder="List the key learning objectives"><?php echo htmlspecialchars($form_data['learning_objectives'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Schedule -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-calendar-alt"></i> Schedule &amp; Location</h5>
                    </div>
                    <div class="card-body">
                        <div class="training-form-section">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="required-field">Start Date</label>
                                        <input type="date" name="start_date" id="start_date" class="form-control" 
                                               value="<?php echo htmlspecialchars($form_data['start_date'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="required-field">End Date</label>
                                        <input type="date" name="end_date" id="end_date" class="form-control" 
                                               value="<?php echo htmlspecialchars($form_data['end_date'] ?? ''); ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="required-field">Duration (Hours)</label>
                                        <input type="number" name="duration_hours" id="duration_hours" class="form-control" 
                                               value="<?php echo htmlspecialchars($form_data['duration_hours'] ?? ''); ?>" 
                                               placeholder="Total hours" required min="1">
                                        <div class="form-hint">Total duration in hours. Auto-calculated from dates.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Location</label>
                                        <input type="text" name="location" id="location" class="form-control" 
                                               value="<?php echo htmlspecialchars($form_data['location'] ?? ''); ?>" 
                                               placeholder="e.g., Conference Room A, Online">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" id="status" class="form-control">
                                    <option value="upcoming" <?php echo ($form_data['status'] ?? 'upcoming') == 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                                    <option value="ongoing" <?php echo ($form_data['status'] ?? '') == 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                                    <option value="completed" <?php echo ($form_data['status'] ?? '') == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo ($form_data['status'] ?? '') == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Trainer -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user-tie"></i> Trainer Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="training-form-section">
                            <?php if (!empty($trainers_list)): ?>
                                <div class="form-group">
                                    <label>Select Trainer</label>
                                    <select name="trainer_id" id="trainerSelect" class="form-control select2">
                                        <option value="">-- Select a Trainer --</option>
                                        <?php foreach ($trainers_list as $trainer): ?>
                                            <option value="<?php echo $trainer['id']; ?>" 
                                                    data-name="<?php echo htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name']); ?>"
                                                    data-email="<?php echo htmlspecialchars($trainer['email']); ?>"
                                                    data-specialization="<?php echo htmlspecialchars($trainer['specialization'] ?? ''); ?>"
                                                    data-qualification="<?php echo htmlspecialchars($trainer['qualification'] ?? ''); ?>"
                                                    data-experience="<?php echo (int)$trainer['experience_years']; ?>"
                                                    <?php echo ($form_data['trainer_id'] ?? '') == $trainer['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name']); ?> 
                                                (<?php echo htmlspecialchars($trainer['specialization'] ?? 'General'); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-hint">
                                        <i class="fas fa-info-circle"></i> 
                                        Select a registered trainer from the list. 
                                        <a href="add-trainer.php" target="_blank">Add new trainer</a>
                                    </div>
                                </div>
                                
                                <!-- Trainer Info Card -->
                                <div class="trainer-info-card" id="trainerInfoCard">
                                    <h6><i class="fas fa-user-circle"></i> Selected Trainer</h6>
                                    <div class="info-row">
                                        <span>Name:</span>
                                        <span id="infoName">-</span>
                                    </div>
                                    <div class="info-row">
                                        <span>Email:</span>
                                        <span id="infoEmail">-</span>
                                    </div>
                                    <div class="info-row">
                                        <span>Specialization:</span>
                                        <span id="infoSpecialization">-</span>
                                    </div>
                                    <div class="info-row">
                                        <span>Qualification:</span>
                                        <span id="infoQualification">-</span>
                                    </div>
                                    <div class="info-row">
                                        <span>Experience:</span>
                                        <span id="infoExperience">-</span>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>No trainers available!</strong> 
                                    <a href="add-trainer.php" class="font-weight-bold">Add a trainer first</a>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Hidden fields to store trainer name and email -->
                            <input type="hidden" name="trainer_name" id="trainerName" value="<?php echo htmlspecialchars($form_data['trainer_name'] ?? ''); ?>">
                            <input type="hidden" name="trainer_email" id="trainerEmail" value="<?php echo htmlspecialchars($form_data['trainer_email'] ?? ''); ?>">
                            
                            <!-- Manual override fields (optional) -->
                            <div class="mt-3">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="manualTrainer" onchange="toggleManualTrainer()">
                                    <label class="custom-control-label" for="manualTrainer">
                                        Enter trainer details manually (if not in list)
                                    </label>
                                </div>
                            </div>
                            
                            <div id="manualTrainerFields" style="display: none; margin-top: 15px;">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Trainer Name</label>
                                            <input type="text" name="manual_trainer_name" class="form-control" 
                                                   placeholder="Full name">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Trainer Email</label>
                                            <input type="email" name="manual_trainer_email" class="form-control" 
                                                   placeholder="trainer@example.com">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cost & Capacity -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-money-bill-wave"></i> Cost &amp; Capacity</h5>
                    </div>
                    <div class="card-body">
                        <div class="training-form-section">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="required-field">Max Participants</label>
                                        <input type="number" name="max_participants" id="max_participants" class="form-control" 
                                               value="<?php echo htmlspecialchars($form_data['max_participants'] ?? 20); ?>" 
                                               required min="1" max="999">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Cost (₦ Naira)</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">₦</span>
                                            </div>
                                            <input type="number" step="0.01" name="cost" id="cost" class="form-control" 
                                                   value="<?php echo htmlspecialchars($form_data['cost'] ?? 0); ?>" 
                                                   placeholder="0.00" min="0">
                                        </div>
                                        <div class="form-hint">Enter 0 for free training</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-12">
                                    <div class="cost-preview">
                                        <small class="text-muted d-block">Preview Cost</small>
                                        <span id="costDisplay">₦<?php echo number_format($form_data['cost'] ?? 0, 2); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group mt-3">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="is_certified" name="is_certified" 
                                           <?php echo ($form_data['is_certified'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="is_certified">
                                        <i class="fas fa-certificate text-primary"></i> This training includes certification
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Form Actions -->
                <div class="card mb-4 sticky-top" style="top: 90px;">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-check-circle"></i> Actions</h5>
                    </div>
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary btn-block btn-lg">
                            <i class="fas fa-save"></i> Create Training
                        </button>
                        <button type="reset" class="btn btn-secondary btn-block mt-2">
                            <i class="fas fa-undo"></i> Reset Form
                        </button>
                        <a href="trainings.php" class="btn btn-outline-secondary btn-block mt-2">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </div>
                
                <!-- Quick Stats -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Quick Info</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Total Trainings:</span>
                            <strong><?php echo $conn->query("SELECT COUNT(*) as c FROM training_programs")->fetch_assoc()['c']; ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Available Trainers:</span>
                            <strong><?php echo count($trainers_list); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Upcoming:</span>
                            <strong><?php echo $conn->query("SELECT COUNT(*) as c FROM training_programs WHERE status = 'upcoming'")->fetch_assoc()['c']; ?></strong>
                        </div>
                    </div>
                </div>
                
                <!-- Tips -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-lightbulb text-warning"></i> Tips</h5>
                    </div>
                    <div class="card-body">
                        <ul class="small mb-0 pl-3">
                            <li>Use clear, descriptive titles</li>
                            <li>Set realistic duration based on content</li>
                            <li>Add prerequisites for advanced trainings</li>
                            <li>Free trainings should have cost = 0</li>
                            <li>Enable certification for accredited courses</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    // ===== TRAINER SELECTION =====
    $('#trainerSelect').on('change', function() {
        var selected = this.options[this.selectedIndex];
        var trainerId = this.value;
        
        if (trainerId) {
            var name = selected.getAttribute('data-name') || '';
            var email = selected.getAttribute('data-email') || '';
            var specialization = selected.getAttribute('data-specialization') || '-';
            var qualification = selected.getAttribute('data-qualification') || '-';
            var experience = selected.getAttribute('data-experience') || '0';
            
            // Fill hidden fields
            $('#trainerName').val(name);
            $('#trainerEmail').val(email);
            
            // Show info card
            $('#infoName').text(name);
            $('#infoEmail').text(email);
            $('#infoSpecialization').text(specialization);
            $('#infoQualification').text(qualification);
            $('#infoExperience').text(experience + ' years');
            $('#trainerInfoCard').addClass('show');
        } else {
            $('#trainerName').val('');
            $('#trainerEmail').val('');
            $('#trainerInfoCard').removeClass('show');
        }
    });
    
    // Trigger on page load if trainer is pre-selected
    if ($('#trainerSelect').val()) {
        $('#trainerSelect').trigger('change');
    }
    
    // ===== COST PREVIEW =====
    $('#cost').on('keyup change', function() {
        var val = parseFloat($(this).val()) || 0;
        $('#costDisplay').text('₦' + val.toLocaleString('en-NG', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }));
    });
    
    // ===== AUTO-CALCULATE DURATION =====
    $('#start_date, #end_date').on('change', function() {
        var start = new Date($('#start_date').val());
        var end = new Date($('#end_date').val());
        
        if (start && end && end >= start && !isNaN(start) && !isNaN(end)) {
            var diffDays = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
            var hours = diffDays * 8; // 8 hours per day
            
            if (!$('#duration_hours').val()) {
                $('#duration_hours').val(hours);
            }
        }
    });
    
    // ===== DATE VALIDATION =====
    $('#trainingForm').on('submit', function(e) {
        var start = $('#start_date').val();
        var end = $('#end_date').val();
        
        if (start && end && start > end) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Invalid Dates',
                text: 'Start date cannot be after end date!'
            });
            return false;
        }
    });
});

// ===== MANUAL TRAINER TOGGLE =====
function toggleManualTrainer() {
    var checkbox = document.getElementById('manualTrainer');
    var fields = document.getElementById('manualTrainerFields');
    var select = document.getElementById('trainerSelect');
    
    if (checkbox.checked) {
        fields.style.display = 'block';
        if (select) select.disabled = true;
    } else {
        fields.style.display = 'none';
        if (select) select.disabled = false;
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>