<?php
// employee/development-plan.php - View Development Plan
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isEmployee()) {
    header('Location: ../login.php');
    exit();
}

$employee_id = $_SESSION['employee_id'];

// Get development plan
$plan = $conn->query("
    SELECT dp.*, u.username as assigned_by_name 
    FROM development_plans dp
    LEFT JOIN users u ON dp.assigned_by = u.id
    WHERE dp.employee_id = $employee_id
    ORDER BY dp.created_at DESC
    LIMIT 1
")->fetch_assoc();

// Get tasks if plan exists
$tasks = [];
if ($plan) {
    $tasks_result = $conn->query("SELECT * FROM plan_tasks WHERE plan_id = {$plan['id']} ORDER BY completion_date ASC");
    while ($task = $tasks_result->fetch_assoc()) {
        $tasks[] = $task;
    }
}

$page_title = 'Development Plan';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-tasks text-primary"></i> My Development Plan</h1>
            <p class="text-muted">View your career development plan</p>
        </div>
    </div>

    <?php if ($plan): ?>
        <!-- Plan Overview -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?php echo htmlspecialchars($plan['title']); ?></h5>
                    <div>
                        <span class="badge badge-<?php echo $plan['status'] == 'completed' ? 'success' : ($plan['status'] == 'in_progress' ? 'warning' : 'secondary'); ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $plan['status'])); ?>
                        </span>
                        <span class="badge badge-<?php echo $plan['priority'] == 'critical' ? 'danger' : ($plan['priority'] == 'high' ? 'warning' : ($plan['priority'] == 'medium' ? 'info' : 'secondary')); ?>">
                            <i class="fas fa-flag"></i> <?php echo ucfirst($plan['priority']); ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <p><?php echo nl2br(htmlspecialchars($plan['description'] ?? 'No description provided.')); ?></p>
                        
                        <h6 class="mt-3">Objectives:</h6>
                        <p><?php echo nl2br(htmlspecialchars($plan['objectives'] ?? 'No objectives defined.')); ?></p>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Plan Details</h6>
                                <p class="mb-1"><strong>Start Date:</strong> <?php echo formatDate($plan['start_date']); ?></p>
                                <p class="mb-1"><strong>Target Date:</strong> <?php echo formatDate($plan['target_date']); ?></p>
                                <?php if ($plan['completion_date']): ?>
                                    <p class="mb-1"><strong>Completed:</strong> <?php echo formatDate($plan['completion_date']); ?></p>
                                <?php endif; ?>
                                <p class="mb-1"><strong>Assigned By:</strong> <?php echo htmlspecialchars($plan['assigned_by_name'] ?? 'System'); ?></p>
                                <hr>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Progress</span>
                                    <span class="font-weight-bold"><?php echo $plan['progress']; ?>%</span>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar" style="width: <?php echo $plan['progress']; ?>%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tasks -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-tasks"></i> Tasks</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php if (!empty($tasks)): ?>
                        <?php foreach ($tasks as $task): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0 <?php echo $task['is_completed'] ? 'text-muted' : ''; ?>">
                                            <?php echo $task['is_completed'] ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-circle text-muted"></i>'; ?>
                                            <?php echo htmlspecialchars($task['task_name']); ?>
                                        </h6>
                                        <?php if ($task['description']): ?>
                                            <small class="text-muted"><?php echo htmlspecialchars($task['description']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <span class="badge badge-<?php echo $task['is_completed'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $task['is_completed'] ? 'Completed' : 'Pending'; ?>
                                        </span>
                                        <small class="text-muted ml-2">
                                            <i class="far fa-calendar-alt"></i> <?php echo formatDate($task['completion_date']); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-tasks fa-2x mb-2 d-block"></i>
                            <p>No tasks defined for this plan.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-tasks fa-4x text-muted mb-3 d-block"></i>
                <h4>No Development Plan Assigned</h4>
                <p class="text-muted">Your manager hasn't assigned a development plan yet.</p>
                <p class="text-muted small">Contact your manager for career development opportunities.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>