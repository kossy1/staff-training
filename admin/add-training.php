<?php
// admin/add-training.php - Add New Training with Full Functionality
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

$errors = [];
$success = false;
$form_data = [];

// Get existing categories for dropdown
$categories = $conn->query("SELECT DISTINCT category FROM training_programs WHERE category IS NOT NULL AND category != '' ORDER BY category");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
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
        'status' => trim($_POST['status'] ?? 'upcoming'),
        'is_certified' => isset($_POST['is_certified']) ? 1 : 0,
        'prerequisites' => trim($_POST['prerequisites'] ?? ''),
        'learning_objectives' => trim($_POST['learning_objectives'] ?? '')
    ];
    
    // Validation
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
    
    // Check for duplicate training title
    if (empty($errors)) {
        $check = $conn->prepare("SELECT id FROM training_programs WHERE title = ?");
        $check->bind_param("s", $form_data['title']);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $errors[] = "A training with this title already exists!";
        }
    }
    
    // Create training
    if (empty($errors)) {
        $stmt = $conn->prepare("
            INSERT INTO training_programs (
                title, description, type, category, duration_hours,
                start_date, end_date, location, trainer_name, trainer_email,
                max_participants, cost, status, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
            $_SESSION['user_id']
        );
        
        if ($stmt->execute()) {
            $training_id = $conn->insert_id;
            
            // Log the action
            logAction($_SESSION['user_id'], 'training_created', ['training_id' => $training_id]);
            
            // Send notification to admin (if needed)
            // You can add notification logic here
            
            $success = true;
            $form_data = []; // Clear form
            
            // Redirect to training list or show success message
            $redirect_url = "trainings.php?success=created";
            echo "<script>setTimeout(function(){ window.location.href = '$redirect_url'; }, 2000);</script>";
        } else {
            $errors[] = "Failed to create training: " . $conn->error;
        }
    }
}

$page_title = 'Add Training';
$page_scripts = '
<script>
$(document).ready(function() {
    // Initialize Select2 for category
    $(".select2").select2({
        theme: "bootstrap4",
        placeholder: "Select or enter category",
        tags: true,
        allowClear: true
    });
    
    // Date validation
    $("#start_date, #end_date").on("change", function() {
        const start = $("#start_date").val();
        const end = $("#end_date").val();
        if (start && end && start > end) {
            alert("Start date cannot be after end date!");
            $(this).val("");
        }
    });
    
    // Preview form data
    $("#previewBtn").on("click", function(e) {
        e.preventDefault();
        const formData = {
            title: $("#title").val(),
            type: $("#type").val(),
            category: $("#category").val(),
            duration: $("#duration_hours").val(),
            start_date: $("#start_date").val(),
            end_date: $("#end_date").val(),
            location: $("#location").val(),
            trainer: $("#trainer_name").val(),
            max_participants: $("#max_participants").val(),
            cost: $("#cost").val(),
            status: $("#status").val()
        };
        
        let html = "<div class=\'table-responsive\'><table class=\'table table-bordered\'><tbody>";
        for (const [key, value] of Object.entries(formData)) {
            if (value) {
                const label = key.replace(/_/g, " ").toUpperCase();
                html += `<tr><th>${label}</th><td>${value}</td></tr>`;
            }
        }
        html += "</tbody></table></div>";
        
        Swal.fire({
            title: "Training Preview",
            html: html,
            icon: "info",
            confirmButtonText: "Looks Good",
            confirmButtonColor: "#28a745"
        });
    });
    
    // Auto-generate duration from dates
    $("#start_date, #end_date").on("change", function() {
        const start = new Date($("#start_date").val());
        const end = new Date($("#end_date").val());
        if (start && end && end > start) {
            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            if (diffDays > 0 && !$("#duration_hours").val()) {
                const hours = diffDays * 8; // Assuming 8 hours per day
                $("#duration_hours").val(hours);
            }
        }
    });
    
    // Form validation on submit
    $("#trainingForm").on("submit", function(e) {
        const start = $("#start_date").val();
        const end = $("#end_date").val();
        if (start && end && start > end) {
            e.preventDefault();
            Swal.fire({
                icon: "error",
                title: "Invalid Dates",
                text: "Start date cannot be after end date!",
                confirmButtonColor: "#d33"
            });
        }
    });
});

// Custom function to format Naira
function formatNairaDisplay(amount) {
    return "₦" + parseFloat(amount).toLocaleString("en-NG", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Update cost display on input
$("#cost").on("keyup", function() {
    const val = $(this).val();
    if (val && !isNaN(val)) {
        $("#costDisplay").text("₦" + parseFloat(val).toLocaleString("en-NG", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }));
    } else {
        $("#costDisplay").text("₦0.00");
    }
});
</script>
';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<style>
/* Add Training Page Styles */
.training-form-section {
    background: #f8f9fc;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
}
.section-title {
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 15px;
    font-size: 1.1rem;
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
    padding: 10px;
    background: #f8f9fc;
    border-radius: 8px;
}
.form-hint {
    font-size: 0.8rem;
    color: #6c757d;
    margin-top: 4px;
}
.status-badge {
    padding: 5px 12px;
    border-radius: 50px;
    font-size: 0.8rem;
    font-weight: 600;
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
    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="trainingTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="basic-tab" data-toggle="tab" href="#basic" role="tab">
                        <i class="fas fa-info-circle"></i> Basic Info
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="schedule-tab" data-toggle="tab" href="#schedule" role="tab">
                        <i class="fas fa-calendar-alt"></i> Schedule
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="trainer-tab" data-toggle="tab" href="#trainer" role="tab">
                        <i class="fas fa-user-tie"></i> Trainer Info
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="cost-tab" data-toggle="tab" href="#cost" role="tab">
                        <i class="fas fa-money-bill-wave"></i> Cost &amp; Capacity
                    </a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <form method="POST" id="trainingForm">
                <div class="tab-content" id="trainingTabsContent">
                    
                    <!-- Tab 1: Basic Info -->
                    <div class="tab-pane fade show active" id="basic" role="tabpanel">
                        <div class="training-form-section">
                            <h6 class="section-title"><i class="fas fa-info-circle"></i> Basic Information</h6>
                            
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
                                <div class="form-hint">Include what participants will learn, who it's for, and any prerequisites.</div>
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
                                        <div class="form-hint">Select the category that best describes this training.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Category</label>
                                        <select name="category" id="category" class="form-control select2">
                                            <option value="">Select or enter category</option>
                                            <?php while ($cat = $categories->fetch_assoc()): ?>
                                                <option value="<?php echo htmlspecialchars($cat['category']); ?>" 
                                                    <?php echo ($form_data['category'] ?? '') == $cat['category'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($cat['category']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                            <option value="Programming">Programming</option>
                                            <option value="Leadership">Leadership</option>
                                            <option value="Communication">Communication</option>
                                            <option value="Project Management">Project Management</option>
                                            <option value="Data Analysis">Data Analysis</option>
                                            <option value="AI & Machine Learning">AI & Machine Learning</option>
                                            <option value="Cloud Computing">Cloud Computing</option>
                                            <option value="Cybersecurity">Cybersecurity</option>
                                            <option value="Agile Methodologies">Agile Methodologies</option>
                                        </select>
                                        <div class="form-hint">You can type a new category and it will be saved.</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Prerequisites</label>
                                <textarea name="prerequisites" class="form-control" rows="2" 
                                          placeholder="List any prerequisites for this training"><?php echo htmlspecialchars($form_data['prerequisites'] ?? ''); ?></textarea>
                                <div class="form-hint">What knowledge or skills should participants have before attending?</div>
                            </div>
                            
                            <div class="form-group">
                                <label>Learning Objectives</label>
                                <textarea name="learning_objectives" class="form-control" rows="3" 
                                          placeholder="List the key learning objectives"><?php echo htmlspecialchars($form_data['learning_objectives'] ?? ''); ?></textarea>
                                <div class="form-hint">What will participants be able to do after completing this training?</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab 2: Schedule -->
                    <div class="tab-pane fade" id="schedule" role="tabpanel">
                        <div class="training-form-section">
                            <h6 class="section-title"><i class="fas fa-calendar-alt"></i> Schedule &amp; Location</h6>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="required-field">Start Date</label>
                                        <input type="date" name="start_date" id="start_date" class="form-control" 
                                               value="<?php echo htmlspecialchars($form_data['start_date'] ?? ''); ?>" required>
                                        <div class="form-hint">When does the training begin?</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="required-field">End Date</label>
                                        <input type="date" name="end_date" id="end_date" class="form-control" 
                                               value="<?php echo htmlspecialchars($form_data['end_date'] ?? ''); ?>" required>
                                        <div class="form-hint">When does the training end?</div>
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
                                               placeholder="e.g., Conference Room A, Online, etc.">
                                        <div class="form-hint">Physical location or platform for online training.</div>
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
                                <div class="form-hint">Current status of the training program.</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab 3: Trainer Info -->
                    <div class="tab-pane fade" id="trainer" role="tabpanel">
                        <div class="training-form-section">
                            <h6 class="section-title"><i class="fas fa-user-tie"></i> Trainer Information</h6>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Trainer Name</label>
                                        <input type="text" name="trainer_name" id="trainer_name" class="form-control" 
                                               value="<?php echo htmlspecialchars($form_data['trainer_name'] ?? ''); ?>" 
                                               placeholder="Full name of trainer">
                                        <div class="form-hint">Name of the person delivering the training.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Trainer Email</label>
                                        <input type="email" name="trainer_email" id="trainer_email" class="form-control" 
                                               value="<?php echo htmlspecialchars($form_data['trainer_email'] ?? ''); ?>" 
                                               placeholder="trainer@example.com">
                                        <div class="form-hint">Email address for contacting the trainer.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab 4: Cost & Capacity -->
                    <div class="tab-pane fade" id="cost" role="tabpanel">
                        <div class="training-form-section">
                            <h6 class="section-title"><i class="fas fa-money-bill-wave"></i> Cost &amp; Capacity</h6>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="required-field">Max Participants</label>
                                        <input type="number" name="max_participants" id="max_participants" class="form-control" 
                                               value="<?php echo htmlspecialchars($form_data['max_participants'] ?? 20); ?>" 
                                               required min="1" max="999">
                                        <div class="form-hint">Maximum number of participants allowed.</div>
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
                                        <div class="form-hint">Cost in Naira (₦). Enter 0 for free training.</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-12">
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
                                <div class="form-hint">Check if participants receive a certificate upon completion.</div>
                            </div>
                        </div>
                    </div>
                    
                </div><!-- end tab-content -->
                
                <!-- Form Actions -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <hr>
                        <div class="d-flex justify-content-between flex-wrap">
                            <div>
                                <button type="button" id="previewBtn" class="btn btn-info">
                                    <i class="fas fa-eye"></i> Preview
                                </button>
                                <button type="reset" class="btn btn-secondary">
                                    <i class="fas fa-undo"></i> Reset
                                </button>
                            </div>
                            <div>
                                <a href="trainings.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save"></i> Create Training
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>