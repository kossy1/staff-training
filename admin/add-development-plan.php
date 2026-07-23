<?php
// admin/add-development-plan.php - Create Development Plan
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
$employees = $conn->query("SELECT id, first_name, last_name, email FROM employees WHERE status = 'active' ORDER BY first_name");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $form_data = [
        'employee_id' => (int)$_POST['employee_id'] ?? 0,
        'title' => trim($_POST['title'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'objectives' => trim($_POST['objectives'] ?? ''),
        'start_date' => trim($_POST['start_date'] ?? ''),
        'target_date' => trim($_POST['target_date'] ?? ''),
        'priority' => trim($_POST['priority'] ?? 'medium'),
        'status' => trim($_POST['status'] ?? 'not_started')
    ];
    
    // Validation
    if ($form_data['employee_id'] <= 0) $errors[] = "Please select an employee";
    if (empty($form_data['title'])) $errors[] = "Plan title is required";
    if (empty($form_data['start_date'])) $errors[] = "Start date is required";
    if (empty($form_data['target_date'])) $errors[] = "Target date is required";
    
    if (empty($errors)) {
        $stmt = $conn->prepare("
            INSERT INTO development_plans (
                employee_id, title, description, objectives, start_date, target_date, 
                priority, status, assigned_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param(
            "isssssssi",
            $form_data['employee_id'],
            $form_data['title'],
            $form_data['description'],
            $form_data['objectives'],
            $form_data['start_date'],
            $form_data['target_date'],
            $form_data['priority'],
            $form_data['status'],
            $_SESSION['user_id']
        );
        
        if ($stmt->execute()) {
            $plan_id = $conn->insert_id;
            logAction($_SESSION['user_id'], 'development_plan_created', ['plan_id' => $plan_id]);
            $success = true;
            $form_data = [];
        } else {
            $errors[] = "Failed to create plan: " . $conn->error;
        }
    }
}

$page_title = 'Create Development Plan';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <div>
                <h1><i class="fas fa-plus-circle text-primary"></i> Create Development Plan</h1>
                <p class="text-muted">Create a new employee development plan</p>
            </div>
            <div>
                <a href="development-plans.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Plans
                </a>
            </div>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> Development plan created successfully!
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
                            <label class="required">Plan Title</label>
                            <input type="text" name="title" class="form-control" 
                                   value="<?php echo htmlspecialchars($form_data['title'] ?? ''); ?>" 
                                   placeholder="Enter plan title" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3" 
                              placeholder="Brief description of the plan"><?php echo htmlspecialchars($form_data['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Objectives</label>
                    <textarea name="objectives" class="form-control" rows="3" 
                              placeholder="List the objectives of this plan"><?php echo htmlspecialchars($form_data['objectives'] ?? ''); ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="required">Start Date</label>
                            <input type="date" name="start_date" class="form-control" 
                                   value="<?php echo htmlspecialchars($form_data['start_date'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="required">Target Date</label>
                            <input type="date" name="target_date" class="form-control" 
                                   value="<?php echo htmlspecialchars($form_data['target_date'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Priority</label>
                            <select name="priority" class="form-control">
                                <option value="low" <?php echo ($form_data['priority'] ?? '') == 'low' ? 'selected' : ''; ?>>Low</option>
                                <option value="medium" <?php echo ($form_data['priority'] ?? 'medium') == 'medium' ? 'selected' : ''; ?>>Medium</option>
                                <option value="high" <?php echo ($form_data['priority'] ?? '') == 'high' ? 'selected' : ''; ?>>High</option>
                                <option value="critical" <?php echo ($form_data['priority'] ?? '') == 'critical' ? 'selected' : ''; ?>>Critical</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="not_started" <?php echo ($form_data['status'] ?? 'not_started') == 'not_started' ? 'selected' : ''; ?>>Not Started</option>
                        <option value="in_progress" <?php echo ($form_data['status'] ?? '') == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="completed" <?php echo ($form_data['status'] ?? '') == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="delayed" <?php echo ($form_data['status'] ?? '') == 'delayed' ? 'selected' : ''; ?>>Delayed</option>
                    </select>
                </div>

                <div class="text-center mt-3">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i> Create Plan
                    </button>
                    <a href="development-plans.php" class="btn btn-secondary btn-lg">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>