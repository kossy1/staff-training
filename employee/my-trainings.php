<?php
// employee/my-trainings.php - View Employee's Trainings with Payment Status
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isEmployee()) {
    header('Location: ../login.php');
    exit();
}

$employee_id = $_SESSION['employee_id'];

// Get filter parameters
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Build query
$query = "
    SELECT et.*, 
           tp.title, tp.description, tp.type, tp.category,
           tp.start_date, tp.end_date, tp.location, tp.trainer_name,
           tp.duration_hours, tp.status as training_status,
           tp.cost
    FROM employee_trainings et
    JOIN training_programs tp ON et.training_id = tp.id
    WHERE et.employee_id = ?
";
$params = [$employee_id];
$types = "i";

if ($status) {
    $query .= " AND et.status = ?";
    $params[] = $status;
    $types .= "s";
}

if ($search) {
    $query .= " AND (tp.title LIKE ? OR tp.description LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

$query .= " ORDER BY et.enrollment_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$trainings = $stmt->get_result();

// Get statistics
$total = $conn->query("SELECT COUNT(*) as count FROM employee_trainings WHERE employee_id = $employee_id")->fetch_assoc()['count'];
$completed = $conn->query("SELECT COUNT(*) as count FROM employee_trainings WHERE employee_id = $employee_id AND status = 'completed'")->fetch_assoc()['count'];
$in_progress = $conn->query("SELECT COUNT(*) as count FROM employee_trainings WHERE employee_id = $employee_id AND status = 'in_progress'")->fetch_assoc()['count'];
$enrolled = $conn->query("SELECT COUNT(*) as count FROM employee_trainings WHERE employee_id = $employee_id AND status = 'enrolled'")->fetch_assoc()['count'];

$page_title = 'My Trainings';
$page_scripts = '
<script>
$(document).ready(function() {
    $("#trainingsTable").DataTable({
        responsive: true,
        pageLength: 25,
        order: [[0, "desc"]],
        columnDefs: [
            { orderable: false, targets: [6] }
        ]
    });
});

function cancelEnrollment(id) {
    Swal.fire({
        title: "Cancel Enrollment?",
        text: "Are you sure you want to cancel this training enrollment?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Yes, cancel it!",
        cancelButtonText: "No, keep it"
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "cancel-enrollment.php?id=" + id;
        }
    });
}

function payForTraining(trainingId) {
    window.location.href = "pay-training.php?training_id=" + trainingId;
}
</script>
';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <div>
                <h1><i class="fas fa-chalkboard-teacher text-primary"></i> My Trainings</h1>
                <p class="text-muted">View all your enrolled trainings</p>
            </div>
            <div>
                <a href="apply-training.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Apply for Training
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-list"></i>
                </div>
                <h3 class="stat-number"><?php echo $total; ?></h3>
                <p class="stat-label">Total Trainings</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3 class="stat-number"><?php echo $completed; ?></h3>
                <p class="stat-label">Completed</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-spinner"></i>
                </div>
                <h3 class="stat-number"><?php echo $in_progress; ?></h3>
                <p class="stat-label">In Progress</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="fas fa-clock"></i>
                </div>
                <h3 class="stat-number"><?php echo $enrolled; ?></h3>
                <p class="stat-label">Enrolled</p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row align-items-end">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Search</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search trainings..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="enrolled" <?php echo $status == 'enrolled' ? 'selected' : ''; ?>>Enrolled</option>
                            <option value="in_progress" <?php echo $status == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="completed" <?php echo $status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="dropped" <?php echo $status == 'dropped' ? 'selected' : ''; ?>>Dropped</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="my-trainings.php" class="btn btn-secondary btn-block">
                        <i class="fas fa-undo"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Trainings Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="trainingsTable">
                    <thead>
                        <tr>
                            <th>Training</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Progress</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($trainings && $trainings->num_rows > 0): ?>
                            <?php while ($training = $trainings->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($training['title']); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($training['trainer_name'] ?? 'TBD'); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $training['type'] == 'technical' ? 'primary' : 
                                                ($training['type'] == 'soft_skill' ? 'success' : 
                                                ($training['type'] == 'management' ? 'info' : 'secondary')); 
                                        ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $training['type'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small>
                                            <i class="far fa-calendar-alt"></i> <?php echo formatDate($training['start_date']); ?>
                                            <br>
                                            <i class="far fa-clock"></i> <?php echo $training['duration_hours']; ?> hours
                                        </small>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1" style="height: 6px; width: 80px;">
                                                <div class="progress-bar" style="width: <?php echo $training['progress']; ?>%;">
                                                </div>
                                            </div>
                                            <span class="ml-2 small"><?php echo $training['progress']; ?>%</span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($training['cost'] > 0): ?>
                                            <?php if ($training['payment_status'] == 'paid'): ?>
                                                <span class="badge badge-success">
                                                    <i class="fas fa-check-circle"></i> Paid
                                                </span>
                                            <?php elseif ($training['payment_status'] == 'pending'): ?>
                                                <span class="badge badge-warning">
                                                    <i class="fas fa-clock"></i> Pending
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">
                                                    <i class="fas fa-exclamation-circle"></i> Unpaid
                                                </span>
                                            <?php endif; ?>
                                            <br>
                                            <small class="text-muted"><?php echo formatNaira($training['cost']); ?></small>
                                        <?php else: ?>
                                            <span class="badge badge-success">Free</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo getStatusBadgeClass($training['status']); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $training['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="training-details.php?id=<?php echo $training['training_id']; ?>" 
                                               class="btn btn-outline-info" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($training['status'] == 'enrolled' && $training['cost'] > 0 && $training['payment_status'] != 'paid'): ?>
                                                <button onclick="payForTraining(<?php echo $training['training_id']; ?>)" 
                                                        class="btn btn-outline-success" title="Pay Now">
                                                    <i class="fas fa-credit-card"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($training['status'] == 'enrolled'): ?>
                                                <a href="javascript:void(0)" 
                                                   onclick="cancelEnrollment(<?php echo $training['id']; ?>)" 
                                                   class="btn btn-outline-danger" title="Cancel">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($training['status'] == 'completed' && $training['certificate_issued']): ?>
                                                <a href="download-certificate.php?id=<?php echo $training['training_id']; ?>" 
                                                   class="btn btn-outline-success" title="Download Certificate">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-chalkboard-teacher fa-3x mb-3 d-block"></i>
                                    <h5>No trainings found</h5>
                                    <p>You haven't enrolled in any trainings yet.</p>
                                    <a href="apply-training.php" class="btn btn-primary">
                                        <i class="fas fa-plus-circle"></i> Apply for Training
                                    </a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.stat-card {
    padding: 20px;
    border-radius: 10px;
    background: white;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    transition: all 0.3s ease;
    height: 100%;
}
.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.2);
}
.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 15px;
}
.stat-icon.primary { background: rgba(102, 126, 234, 0.1); color: #667eea; }
.stat-icon.success { background: rgba(72, 187, 120, 0.1); color: #48bb78; }
.stat-icon.warning { background: rgba(246, 194, 62, 0.1); color: #f6c23e; }
.stat-icon.info { background: rgba(54, 185, 204, 0.1); color: #36b9cc; }
.stat-number { font-size: 2rem; font-weight: 800; margin: 0; line-height: 1.2; }
.stat-label { color: #6c757d; font-size: 0.9rem; font-weight: 500; margin: 0; }
</style>

<?php require_once 'includes/footer.php'; ?>