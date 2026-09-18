<?php
// employee/training-details.php - Training Details with Payment Info
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isEmployee()) {
    header('Location: ../login.php');
    exit();
}

$training_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($training_id <= 0) {
    header('Location: my-trainings.php');
    exit();
}

$employee_id = $_SESSION['employee_id'];

// Get training details
$training = $conn->query("
    SELECT tp.*, 
           et.id as enrollment_id, et.status as enrollment_status, et.progress,
           et.enrollment_date, et.completion_date, et.score,
           et.payment_status, et.payment_reference, et.payment_amount, et.payment_date
    FROM training_programs tp
    LEFT JOIN employee_trainings et ON tp.id = et.training_id AND et.employee_id = $employee_id
    WHERE tp.id = $training_id
")->fetch_assoc();

if (!$training) {
    header('Location: my-trainings.php');
    exit();
}

$page_title = 'Training Details';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-info-circle text-primary"></i> Training Details</h1>
            <p class="text-muted">View detailed information about this training</p>
        </div>
        <div>
            <a href="my-trainings.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to My Trainings
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo htmlspecialchars($training['title']); ?></h5>
                </div>
                <div class="card-body">
                    <h6>Description</h6>
                    <p><?php echo nl2br(htmlspecialchars($training['description'] ?? 'No description available.')); ?></p>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="info-item">
                                <label><i class="fas fa-tag"></i> Type</label>
                                <p>
                                    <span class="badge badge-<?php 
                                        echo $training['type'] == 'technical' ? 'primary' : 
                                            ($training['type'] == 'soft_skill' ? 'success' : 
                                            ($training['type'] == 'management' ? 'info' : 'secondary')); 
                                    ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $training['type'])); ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label><i class="fas fa-folder"></i> Category</label>
                                <p><?php echo htmlspecialchars($training['category'] ?? 'Uncategorized'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-item">
                                <label><i class="far fa-calendar-alt"></i> Start Date</label>
                                <p><?php echo formatDate($training['start_date']); ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label><i class="far fa-calendar-check"></i> End Date</label>
                                <p><?php echo formatDate($training['end_date']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-item">
                                <label><i class="fas fa-clock"></i> Duration</label>
                                <p><?php echo $training['duration_hours']; ?> hours</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label><i class="fas fa-money-bill-wave text-success"></i> Cost</label>
                                <p class="font-weight-bold">
                                    <?php if ($training['cost'] > 0): ?>
                                        <span class="text-success"><?php echo formatNaira($training['cost']); ?></span>
                                        <?php if ($training['payment_status'] == 'paid'): ?>
                                            <span class="badge badge-success ml-2">Paid</span>
                                        <?php elseif ($training['payment_status'] == 'pending'): ?>
                                            <span class="badge badge-warning ml-2">Pending</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-success">Free</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-item">
                                <label><i class="fas fa-map-marker-alt"></i> Location</label>
                                <p><?php echo htmlspecialchars($training['location'] ?? 'TBD'); ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label><i class="fas fa-user-tie"></i> Trainer</label>
                                <p><?php echo htmlspecialchars($training['trainer_name'] ?? 'TBD'); ?></p>
                                <?php if ($training['trainer_email']): ?>
                                    <small class="text-muted">
                                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($training['trainer_email']); ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($training['payment_status'] == 'paid' && $training['payment_amount']): ?>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="payment-info">
                                    <h6><i class="fas fa-receipt text-success"></i> Payment Details</h6>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <small class="text-muted">Amount Paid</small>
                                            <p class="font-weight-bold"><?php echo formatNaira($training['payment_amount']); ?></p>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted">Reference</small>
                                            <p class="font-weight-bold small"><?php echo htmlspecialchars($training['payment_reference']); ?></p>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted">Payment Date</small>
                                            <p class="font-weight-bold"><?php echo formatDateTime($training['payment_date']); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Enrollment Status -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user-graduate"></i> Enrollment Status</h5>
                </div>
                <div class="card-body">
                    <?php if ($training['enrollment_id']): ?>
                        <div class="text-center">
                            <div class="mb-3">
                                <span class="badge badge-<?php echo getStatusBadgeClass($training['enrollment_status']); ?> badge-lg">
                                    <?php echo ucfirst(str_replace('_', ' ', $training['enrollment_status'])); ?>
                                </span>
                            </div>
                            
                            <div class="mb-3">
                                <label>Progress</label>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar" style="width: <?php echo $training['progress']; ?>%;">
                                        <?php echo $training['progress']; ?>%
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-left small">
                                <p><strong>Enrolled:</strong> <?php echo formatDate($training['enrollment_date']); ?></p>
                                <?php if ($training['completion_date']): ?>
                                    <p><strong>Completed:</strong> <?php echo formatDate($training['completion_date']); ?></p>
                                <?php endif; ?>
                                <?php if ($training['score'] !== null): ?>
                                    <p><strong>Score:</strong> <?php echo $training['score']; ?>%</p>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($training['cost'] > 0 && $training['payment_status'] != 'paid'): ?>
                                <a href="pay-training.php?training_id=<?php echo $training['training_id']; ?>" 
                                   class="btn btn-success btn-block mt-3">
                                    <i class="fas fa-credit-card"></i> Pay Now
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="fas fa-clock fa-2x text-muted mb-2 d-block"></i>
                            <p>You are not enrolled in this training.</p>
                            <a href="apply-training.php" class="btn btn-primary">
                                <i class="fas fa-plus-circle"></i> Apply Now
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Training Stats -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Training Stats</h5>
                </div>
                <div class="card-body">
                    <div class="info-item">
                        <label>Status</label>
                        <p>
                            <span class="badge badge-<?php echo getStatusBadgeClass($training['status']); ?>">
                                <?php echo ucfirst($training['status']); ?>
                            </span>
                        </p>
                    </div>
                    <div class="info-item">
                        <label>Participants</label>
                        <p><?php echo $training['current_participants']; ?> / <?php echo $training['max_participants']; ?></p>
                        <div class="progress" style="height: 5px;">
                            <div class="progress-bar" style="width: <?php echo ($training['current_participants'] / $training['max_participants']) * 100; ?>%;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.info-item {
    margin-bottom: 15px;
}
.info-item label {
    display: block;
    font-size: 0.8rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 2px;
}
.info-item p {
    margin: 0;
    font-weight: 500;
}
.badge-lg {
    font-size: 1rem;
    padding: 8px 20px;
}
.payment-info {
    background: #f8f9fc;
    padding: 15px;
    border-radius: 8px;
    margin-top: 15px;
}
</style>

<?php require_once 'includes/footer.php'; ?>