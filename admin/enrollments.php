<?php
// admin/enrollments.php - Manage Enrollments
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Get filter parameters
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$training_id = isset($_GET['training_id']) ? (int)$_GET['training_id'] : 0;
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Build query
$query = "
    SELECT et.*, 
           CONCAT(e.first_name, ' ', e.last_name) as employee_name,
           e.email as employee_email,
           tp.title as training_title,
           tp.start_date as training_start,
           tp.end_date as training_end
    FROM employee_trainings et
    LEFT JOIN employees e ON et.employee_id = e.id
    LEFT JOIN training_programs tp ON et.training_id = tp.id
    WHERE 1=1
";
$params = [];
$types = "";

if ($status) {
    $query .= " AND et.status = ?";
    $params[] = $status;
    $types .= "s";
}

if ($training_id > 0) {
    $query .= " AND et.training_id = ?";
    $params[] = $training_id;
    $types .= "i";
}

if ($search) {
    $query .= " AND (e.first_name LIKE ? OR e.last_name LIKE ? OR e.email LIKE ? OR tp.title LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $types .= "ssss";
}

$query .= " ORDER BY et.enrollment_date DESC";

// Prepare and execute
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$enrollments = $stmt->get_result();

// Get trainings for filter
$trainings = $conn->query("SELECT id, title FROM training_programs ORDER BY title");

$page_title = 'Enrollments';
$page_scripts = '
<script>
$(document).ready(function() {
    $("#enrollmentsTable").DataTable({
        responsive: true,
        pageLength: 25,
        order: [[0, "desc"]]
    });
});

function updateStatus(id, status) {
    Swal.fire({
        title: "Update Status",
        text: "Are you sure you want to change this enrollment status?",
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#28a745",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Yes, update it!"
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "update-enrollment-status.php?id=" + id + "&status=" + status;
        }
    });
}

function deleteEnrollment(id) {
    Swal.fire({
        title: "Are you sure?",
        text: "This action cannot be undone!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Yes, delete it!"
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "delete-enrollment.php?id=" + id;
        }
    });
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
                <h1><i class="fas fa-user-graduate text-primary"></i> Enrollments</h1>
                <p class="text-muted">Manage training enrollments</p>
            </div>
            <div>
                <a href="add-enrollment.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Add Enrollment
                </a>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row align-items-end">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Search</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Training</label>
                        <select name="training_id" class="form-control">
                            <option value="">All Trainings</option>
                            <?php while ($t = $trainings->fetch_assoc()): ?>
                                <option value="<?php echo $t['id']; ?>" 
                                    <?php echo $training_id == $t['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($t['title']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
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
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="enrollments.php" class="btn btn-secondary btn-block">
                        <i class="fas fa-undo"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Enrollments Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="enrollmentsTable">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Training</th>
                            <th>Enrollment Date</th>
                            <th>Progress</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($enrollments && $enrollments->num_rows > 0): ?>
                            <?php while ($enrollment = $enrollments->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($enrollment['employee_name'] ?? 'N/A'); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($enrollment['employee_email']); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($enrollment['training_title']); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <i class="far fa-calendar-alt"></i> <?php echo formatDate($enrollment['training_start']); ?>
                                        </small>
                                    </td>
                                    <td><?php echo formatDate($enrollment['enrollment_date']); ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1" style="height: 6px; width: 80px;">
                                                <div class="progress-bar" style="width: <?php echo $enrollment['progress']; ?>%;">
                                                </div>
                                            </div>
                                            <span class="ml-2 small"><?php echo $enrollment['progress']; ?>%</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo getStatusBadgeClass($enrollment['status']); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $enrollment['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="view-enrollment.php?id=<?php echo $enrollment['id']; ?>" 
                                               class="btn btn-outline-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <div class="dropdown d-inline">
                                                <button class="btn btn-outline-secondary dropdown-toggle" 
                                                        data-toggle="dropdown" title="Update Status">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <div class="dropdown-menu">
                                                    <a class="dropdown-item" href="javascript:void(0)" 
                                                       onclick="updateStatus(<?php echo $enrollment['id']; ?>, 'enrolled')">
                                                        <i class="fas fa-check-circle text-info"></i> Enrolled
                                                    </a>
                                                    <a class="dropdown-item" href="javascript:void(0)" 
                                                       onclick="updateStatus(<?php echo $enrollment['id']; ?>, 'in_progress')">
                                                        <i class="fas fa-spinner text-warning"></i> In Progress
                                                    </a>
                                                    <a class="dropdown-item" href="javascript:void(0)" 
                                                       onclick="updateStatus(<?php echo $enrollment['id']; ?>, 'completed')">
                                                        <i class="fas fa-check-circle text-success"></i> Completed
                                                    </a>
                                                    <a class="dropdown-item" href="javascript:void(0)" 
                                                       onclick="updateStatus(<?php echo $enrollment['id']; ?>, 'dropped')">
                                                        <i class="fas fa-times-circle text-danger"></i> Dropped
                                                    </a>
                                                </div>
                                            </div>
                                            <a href="javascript:void(0)" 
                                               onclick="deleteEnrollment(<?php echo $enrollment['id']; ?>)" 
                                               class="btn btn-outline-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="fas fa-user-graduate fa-3x mb-3 d-block"></i>
                                    <h5>No enrollments found</h5>
                                    <p>Click "Add Enrollment" to enroll an employee in a training.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>