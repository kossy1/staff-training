<?php
// admin/development-plans.php - Manage Development Plans
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Get development plans
$plans = $conn->query("
    SELECT dp.*, 
           CONCAT(e.first_name, ' ', e.last_name) as employee_name,
           u.username as assigned_by_name
    FROM development_plans dp
    LEFT JOIN employees e ON dp.employee_id = e.id
    LEFT JOIN users u ON dp.assigned_by = u.id
    ORDER BY dp.created_at DESC
");

$page_title = 'Development Plans';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <div>
                <h1><i class="fas fa-tasks text-primary"></i> Development Plans</h1>
                <p class="text-muted">Manage employee development plans</p>
            </div>
            <div>
                <a href="add-development-plan.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Create Development Plan
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <?php if ($plans && $plans->num_rows > 0): ?>
            <?php while ($plan = $plans->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-start">
                                <h6 class="mb-0"><?php echo htmlspecialchars($plan['title']); ?></h6>
                                <span class="badge badge-<?php echo $plan['status'] == 'completed' ? 'success' : ($plan['status'] == 'in_progress' ? 'warning' : 'secondary'); ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $plan['status'])); ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small"><?php echo htmlspecialchars(substr($plan['description'] ?? '', 0, 100)) . '...'; ?></p>
                            <div class="mb-2">
                                <strong>Employee:</strong> <?php echo htmlspecialchars($plan['employee_name'] ?? 'N/A'); ?>
                            </div>
                            <div class="mb-2">
                                <strong>Priority:</strong>
                                <span class="badge badge-<?php echo $plan['priority'] == 'critical' ? 'danger' : ($plan['priority'] == 'high' ? 'warning' : ($plan['priority'] == 'medium' ? 'info' : 'secondary')); ?>">
                                    <?php echo ucfirst($plan['priority']); ?>
                                </span>
                            </div>
                            <div class="mb-2">
                                <strong>Progress:</strong>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar" style="width: <?php echo $plan['progress']; ?>%;">
                                        <?php echo $plan['progress']; ?>%
                                    </div>
                                </div>
                            </div>
                            <div class="small text-muted">
                                <i class="far fa-calendar-alt"></i> Start: <?php echo formatDate($plan['start_date']); ?>
                                <br>
                                <i class="far fa-calendar-check"></i> Target: <?php echo formatDate($plan['target_date']); ?>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent">
                            <div class="btn-group btn-group-sm w-100">
                                <a href="view-development-plan.php?id=<?php echo $plan['id']; ?>" 
                                   class="btn btn-outline-info">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="edit-development-plan.php?id=<?php echo $plan['id']; ?>" 
                                   class="btn btn-outline-primary">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="javascript:void(0)" 
                                   onclick="deletePlan(<?php echo $plan['id']; ?>)" 
                                   class="btn btn-outline-danger">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="text-center text-muted py-5">
                    <i class="fas fa-tasks fa-4x mb-3 d-block"></i>
                    <h4>No development plans found</h4>
                    <p>Click "Create Development Plan" to get started.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function deletePlan(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This will also delete all tasks associated with this plan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'delete-development-plan.php?id=' + id;
        }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>