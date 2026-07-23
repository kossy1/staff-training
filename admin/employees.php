<?php
// admin/employees.php - Manage Employees
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Get filter parameters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$department = isset($_GET['department']) ? sanitizeInput($_GET['department']) : '';
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';

// Build query
$query = "SELECT * FROM employees WHERE 1=1";
$params = [];
$types = "";

if ($search) {
    $query .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR employee_code LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $types .= "ssss";
}

if ($department) {
    $query .= " AND department = ?";
    $params[] = $department;
    $types .= "s";
}

if ($status) {
    $query .= " AND status = ?";
    $params[] = $status;
    $types .= "s";
}

$query .= " ORDER BY created_at DESC";

// Prepare and execute
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$employees = $stmt->get_result();

// Get departments for filter
$departments = $conn->query("SELECT DISTINCT department FROM employees WHERE department IS NOT NULL ORDER BY department");

$page_title = 'Employees';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <div>
                <h1><i class="fas fa-users text-primary"></i> Employees</h1>
                <p class="text-muted">Manage all employees in the system</p>
            </div>
            <div>
                <a href="add-employee.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Add Employee
                </a>
                <button class="btn btn-success" onclick="exportData()">
                    <i class="fas fa-file-export"></i> Export
                </button>
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
                               placeholder="Search employees..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Department</label>
                        <select name="department" class="form-control">
                            <option value="">All Departments</option>
                            <?php while ($dept = $departments->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($dept['department']); ?>" 
                                    <?php echo $department == $dept['department'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['department']); ?>
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
                            <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="on_leave" <?php echo $status == 'on_leave' ? 'selected' : ''; ?>>On Leave</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="employees.php" class="btn btn-secondary btn-block">
                        <i class="fas fa-undo"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Employees Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="employeesTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($employees && $employees->num_rows > 0): ?>
                            <?php while ($emp = $employees->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $emp['id']; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo !empty($emp['profile_picture']) ? '../uploads/profile-pictures/' . $emp['profile_picture'] : '../assets/images/default-avatar.png'; ?>" 
                                                 alt="Profile" class="rounded-circle mr-2" width="35" height="35" style="object-fit: cover;">
                                            <div>
                                                <strong><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($emp['employee_code']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($emp['department'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($emp['position'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($emp['email']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo getStatusBadgeClass($emp['status']); ?>">
                                            <?php echo ucfirst($emp['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="view-employee.php?id=<?php echo $emp['id']; ?>" 
                                               class="btn btn-outline-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit-employee.php?id=<?php echo $emp['id']; ?>" 
                                               class="btn btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="javascript:void(0)" 
                                               onclick="deleteEmployee(<?php echo $emp['id']; ?>)" 
                                               class="btn btn-outline-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-users fa-3x mb-3 d-block"></i>
                                    <h5>No employees found</h5>
                                    <p>Click "Add Employee" to create your first employee record.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function deleteEmployee(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'delete-employee.php?id=' + id;
        }
    });
}

function exportData() {
    window.location.href = 'export-employees.php';
}

$(document).ready(function() {
    // Initialize DataTable
    if ($.fn.DataTable) {
        $('#employeesTable').DataTable({
            responsive: true,
            pageLength: 25,
            ordering: true,
            searching: false,
            lengthChange: false,
            info: true,
            paging: true
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>