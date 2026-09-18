<?php
// admin/departments.php - Manage Departments
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Null-safe helper
if (!function_exists('safeHtml')) {
    function safeHtml($value, $flags = ENT_QUOTES, $encoding = 'UTF-8') {
        return htmlspecialchars((string)($value ?? ''), $flags, $encoding);
    }
}

$user_id = $_SESSION['user_id'];

// Flash messages
$success_message = '';
$error_messages = [];

if (isset($_SESSION['flash_success'])) {
    $success_message = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}
if (isset($_SESSION['flash_errors'])) {
    $error_messages = $_SESSION['flash_errors'];
    unset($_SESSION['flash_errors']);
}

// ============================================
// ENSURE DEPARTMENTS TABLE EXISTS
// ============================================
$table_exists = $conn->query("SHOW TABLES LIKE 'departments'")->num_rows > 0;
if (!$table_exists) {
    $conn->query("
        CREATE TABLE IF NOT EXISTS `departments` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL UNIQUE,
            `code` VARCHAR(20) UNIQUE DEFAULT NULL,
            `description` TEXT DEFAULT NULL,
            `head_of_department` VARCHAR(100) DEFAULT NULL,
            `email` VARCHAR(100) DEFAULT NULL,
            `phone` VARCHAR(20) DEFAULT NULL,
            `location` VARCHAR(255) DEFAULT NULL,
            `budget` DECIMAL(15,2) DEFAULT 0.00,
            `employee_count` INT DEFAULT 0,
            `status` ENUM('active', 'inactive') DEFAULT 'active',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

// ============================================
// HANDLE ACTIONS
// ============================================

// DELETE
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Get department name first
    $stmt = $conn->prepare("SELECT name FROM departments WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $dept = $stmt->get_result()->fetch_assoc();
    
    if ($dept) {
        // Check if employees are assigned
        $check = $conn->prepare("SELECT COUNT(*) as c FROM employees WHERE department = ?");
        $check->bind_param("s", $dept['name']);
        $check->execute();
        $count = $check->get_result()->fetch_assoc()['c'];
        
        if ($count > 0) {
            $_SESSION['flash_errors'] = ["Cannot delete '{$dept['name']}' — $count employee(s) are assigned to it. Reassign them first."];
        } else {
            $del = $conn->prepare("DELETE FROM departments WHERE id = ?");
            $del->bind_param("i", $id);
            if ($del->execute()) {
                logAction($user_id, 'department_deleted', ['name' => $dept['name']]);
                $_SESSION['flash_success'] = "Department '{$dept['name']}' deleted successfully!";
            }
        }
    }
    header('Location: departments.php');
    exit();
}

// TOGGLE STATUS
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $conn->prepare("UPDATE departments SET status = IF(status='active', 'inactive', 'active') WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        logAction($user_id, 'department_status_toggled', ['id' => $id]);
        $_SESSION['flash_success'] = "Status updated successfully!";
    }
    header('Location: departments.php');
    exit();
}

// ADD / EDIT (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $errors = [];
    
    $name = trim($_POST['name'] ?? '');
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $description = trim($_POST['description'] ?? '');
    $head = trim($_POST['head_of_department'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $budget = (float)($_POST['budget'] ?? 0);
    $status = $_POST['status'] ?? 'active';
    
    // Validation
    if (empty($name)) $errors[] = "Department name is required";
    if (empty($code)) $errors[] = "Department code is required";
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email address";
    
    // Check duplicates
    if (empty($errors)) {
        if ($_POST['action'] === 'add') {
            $check = $conn->prepare("SELECT id FROM departments WHERE name = ? OR code = ?");
            $check->bind_param("ss", $name, $code);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $errors[] = "Department name or code already exists";
            }
        } else {
            $id = (int)$_POST['id'];
            $check = $conn->prepare("SELECT id FROM departments WHERE (name = ? OR code = ?) AND id != ?");
            $check->bind_param("ssi", $name, $code, $id);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $errors[] = "Department name or code already exists";
            }
        }
    }
    
    if (empty($errors)) {
        if ($_POST['action'] === 'add') {
            $stmt = $conn->prepare("
                INSERT INTO departments (name, code, description, head_of_department, email, phone, location, budget, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("sssssssds", $name, $code, $description, $head, $email, $phone, $location, $budget, $status);
            
            if ($stmt->execute()) {
                logAction($user_id, 'department_created', ['name' => $name]);
                $_SESSION['flash_success'] = "Department '{$name}' created successfully!";
            } else {
                $_SESSION['flash_errors'] = ["Database error: " . $conn->error];
            }
        } else {
            $id = (int)$_POST['id'];
            
            // Get old name for updating employees
            $old_stmt = $conn->prepare("SELECT name FROM departments WHERE id = ?");
            $old_stmt->bind_param("i", $id);
            $old_stmt->execute();
            $old_name = $old_stmt->get_result()->fetch_assoc()['name'] ?? '';
            
            $stmt = $conn->prepare("
                UPDATE departments SET 
                    name = ?, code = ?, description = ?, head_of_department = ?,
                    email = ?, phone = ?, location = ?, budget = ?, status = ?
                WHERE id = ?
            ");
            $stmt->bind_param("sssssssdsi", $name, $code, $description, $head, $email, $phone, $location, $budget, $status, $id);
            
            if ($stmt->execute()) {
                // Update employees if name changed
                if ($old_name !== $name && !empty($old_name)) {
                    $conn->query("UPDATE employees SET department = '" . $conn->real_escape_string($name) . "' WHERE department = '" . $conn->real_escape_string($old_name) . "'");
                }
                logAction($user_id, 'department_updated', ['name' => $name]);
                $_SESSION['flash_success'] = "Department '{$name}' updated successfully!";
            } else {
                $_SESSION['flash_errors'] = ["Database error: " . $conn->error];
            }
        }
    } else {
        $_SESSION['flash_errors'] = $errors;
    }
    
    header('Location: departments.php');
    exit();
}

// ============================================
// FILTERS
// ============================================
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name';

// Build query
$query = "SELECT * FROM departments WHERE 1=1";
$params = [];
$types = "";

if ($search) {
    $query .= " AND (name LIKE ? OR code LIKE ? OR description LIKE ? OR head_of_department LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
    $types .= "ssss";
}

if ($status_filter) {
    $query .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// Sorting
switch ($sort) {
    case 'code':
        $query .= " ORDER BY code ASC";
        break;
    case 'employees':
        $query .= " ORDER BY employee_count DESC, name ASC";
        break;
    case 'budget':
        $query .= " ORDER BY budget DESC";
        break;
    case 'newest':
        $query .= " ORDER BY created_at DESC";
        break;
    default:
        $query .= " ORDER BY name ASC";
}

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$departments = $stmt->get_result();

// ============================================
// STATISTICS
// ============================================
$stats = [
    'total' => $conn->query("SELECT COUNT(*) as c FROM departments")->fetch_assoc()['c'] ?? 0,
    'active' => $conn->query("SELECT COUNT(*) as c FROM departments WHERE status = 'active'")->fetch_assoc()['c'] ?? 0,
    'inactive' => $conn->query("SELECT COUNT(*) as c FROM departments WHERE status = 'inactive'")->fetch_assoc()['c'] ?? 0,
    'with_employees' => $conn->query("SELECT COUNT(*) as c FROM departments WHERE employee_count > 0")->fetch_assoc()['c'] ?? 0,
    'total_budget' => $conn->query("SELECT SUM(budget) as s FROM departments")->fetch_assoc()['s'] ?? 0,
    'total_employees' => $conn->query("SELECT SUM(employee_count) as s FROM departments")->fetch_assoc()['s'] ?? 0
];

$page_title = 'Departments';
$page_scripts = '
<script>
$(document).ready(function() {
    if ($.fn.DataTable) {
        $("#departmentsTable").DataTable({
            responsive: true,
            pageLength: 25,
            ordering: false,
            searching: false,
            lengthChange: false,
            info: true,
            paging: true,
            language: {
                info: "Showing _START_ to _END_ of _TOTAL_ departments",
                infoEmpty: "No departments found"
            }
        });
    }
});

function showAddModal() {
    document.getElementById("modalTitle").innerHTML = \'<i class="fas fa-plus-circle"></i> Add Department\';
    document.getElementById("deptForm").reset();
    document.getElementById("deptAction").value = "add";
    document.getElementById("deptId").value = "";
    $("#deptModal").modal("show");
}

function showEditModal(dept) {
    document.getElementById("modalTitle").innerHTML = \'<i class="fas fa-edit"></i> Edit Department\';
    document.getElementById("deptAction").value = "edit";
    document.getElementById("deptId").value = dept.id;
    document.getElementById("deptName").value = dept.name;
    document.getElementById("deptCode").value = dept.code || "";
    document.getElementById("deptDescription").value = dept.description || "";
    document.getElementById("deptHead").value = dept.head_of_department || "";
    document.getElementById("deptEmail").value = dept.email || "";
    document.getElementById("deptPhone").value = dept.phone || "";
    document.getElementById("deptLocation").value = dept.location || "";
    document.getElementById("deptBudget").value = dept.budget || 0;
    document.getElementById("deptStatus").value = dept.status || "active";
    $("#deptModal").modal("show");
}

function deleteDept(id, name) {
    Swal.fire({
        title: "Delete Department?",
        html: "Are you sure you want to delete <strong>" + name + "</strong>?<br><small class=\'text-danger\'>This cannot be undone.</small>",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Yes, delete it!",
        cancelButtonText: "Cancel"
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "departments.php?delete=" + id;
        }
    });
}

function toggleStatus(id, name, currentStatus) {
    var newStatus = currentStatus === "active" ? "inactive" : "active";
    Swal.fire({
        title: "Change Status?",
        html: "Set <strong>" + name + "</strong> to <strong>" + newStatus + "</strong>?",
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#667eea",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Yes, change it!"
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "departments.php?toggle=" + id;
        }
    });
}
</script>
';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<style>
.dept-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}
.dept-stat-card {
    background: white;
    border-radius: 12px;
    padding: 18px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    border-left: 4px solid #667eea;
    transition: all 0.3s ease;
}
.dept-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}
.dept-stat-card.success { border-left-color: #48bb78; }
.dept-stat-card.warning { border-left-color: #f6c23e; }
.dept-stat-card.info { border-left-color: #36b9cc; }
.dept-stat-card.danger { border-left-color: #e74a3b; }
.dept-stat-card .stat-value {
    font-size: 1.5rem;
    font-weight: 800;
    color: #2d3748;
    line-height: 1;
}
.dept-stat-card .stat-label {
    font-size: 0.75rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
    margin-top: 5px;
}

.dept-code {
    display: inline-block;
    padding: 4px 10px;
    background: rgba(102, 126, 234, 0.1);
    color: #667eea;
    font-weight: 700;
    border-radius: 6px;
    font-size: 0.75rem;
    font-family: monospace;
}

.dept-avatar {
    width: 45px; height: 45px;
    border-radius: 10px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    font-weight: 700;
}

.dept-status {
    padding: 5px 12px;
    border-radius: 50px;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
}
.dept-status.active { background: #d4edda; color: #155724; }
.dept-status.inactive { background: #f8d7da; color: #721c24; }

.table td {
    vertical-align: middle;
}

.action-btns .btn {
    padding: 5px 10px;
    font-size: 0.75rem;
    margin: 0 2px;
}

.filter-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    margin-bottom: 25px;
}
</style>

<div class="main-content">
    
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <div>
                <h1><i class="fas fa-building text-primary"></i> Departments</h1>
                <p class="text-muted">Manage all departments in the institution</p>
            </div>
            <div>
                <button onclick="showAddModal()" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Add Department
                </button>
                <a href="reports.php?type=departments" class="btn btn-outline-secondary">
                    <i class="fas fa-file-export"></i> Export
                </a>
            </div>
        </div>
    </div>
    
    <!-- Success/Error Messages -->
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?php echo safeHtml($success_message); ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error_messages)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i>
            <ul class="mb-0">
                <?php foreach ($error_messages as $e): ?>
                    <li><?php echo safeHtml($e); ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>
    
    <!-- Statistics -->
    <div class="dept-stats">
        <div class="dept-stat-card">
            <div class="stat-value"><?php echo $stats['total']; ?></div>
            <div class="stat-label">Total Departments</div>
        </div>
        <div class="dept-stat-card success">
            <div class="stat-value text-success"><?php echo $stats['active']; ?></div>
            <div class="stat-label">Active</div>
        </div>
        <div class="dept-stat-card danger">
            <div class="stat-value text-danger"><?php echo $stats['inactive']; ?></div>
            <div class="stat-label">Inactive</div>
        </div>
        <div class="dept-stat-card info">
            <div class="stat-value text-info"><?php echo $stats['with_employees']; ?></div>
            <div class="stat-label">With Employees</div>
        </div>
        <div class="dept-stat-card warning">
            <div class="stat-value text-warning"><?php echo $stats['total_employees']; ?></div>
            <div class="stat-label">Total Employees</div>
        </div>
        <div class="dept-stat-card">
            <div class="stat-value"><?php echo formatNairaShort($stats['total_budget']); ?></div>
            <div class="stat-label">Total Budget</div>
        </div>
    </div>
    
    <!-- Filter -->
    <div class="filter-card">
        <form method="GET" class="row align-items-end">
            <div class="col-md-4 mb-2">
                <label>Search</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                    </div>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Name, code, head..." 
                           value="<?php echo safeHtml($search); ?>">
                </div>
            </div>
            <div class="col-md-2 mb-2">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <label>Sort By</label>
                <select name="sort" class="form-control">
                    <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Name (A-Z)</option>
                    <option value="code" <?php echo $sort === 'code' ? 'selected' : ''; ?>>Code</option>
                    <option value="employees" <?php echo $sort === 'employees' ? 'selected' : ''; ?>>Most Employees</option>
                    <option value="budget" <?php echo $sort === 'budget' ? 'selected' : ''; ?>>Highest Budget</option>
                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </div>
            <div class="col-md-2 mb-2">
                <a href="departments.php" class="btn btn-secondary btn-block">
                    <i class="fas fa-undo"></i> Reset
                </a>
            </div>
        </form>
    </div>
    
    <!-- Departments Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-list"></i> All Departments
                <span class="badge badge-primary ml-2"><?php echo $departments->num_rows; ?></span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="departmentsTable">
                    <thead>
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 20%;">Department</th>
                            <th style="width: 8%;">Code</th>
                            <th style="width: 15%;">Head</th>
                            <th style="width: 12%;">Contact</th>
                            <th style="width: 8%;">Employees</th>
                            <th style="width: 10%;">Budget</th>
                            <th style="width: 8%;">Status</th>
                            <th style="width: 14%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($departments && $departments->num_rows > 0): ?>
                            <?php $i = 1; while ($d = $departments->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="dept-avatar mr-2">
                                                <?php echo strtoupper(substr($d['name'], 0, 2)); ?>
                                            </div>
                                            <div>
                                                <strong><?php echo safeHtml($d['name']); ?></strong>
                                                <?php if (!empty($d['location'])): ?>
                                                    <br><small class="text-muted">
                                                        <i class="fas fa-map-marker-alt"></i> 
                                                        <?php echo safeHtml($d['location']); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="dept-code"><?php echo safeHtml($d['code'] ?? '—'); ?></span>
                                    </td>
                                    <td>
                                        <?php if (!empty($d['head_of_department'])): ?>
                                            <small><?php echo safeHtml($d['head_of_department']); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small>
                                            <?php if (!empty($d['email'])): ?>
                                                <i class="fas fa-envelope text-muted"></i> <?php echo safeHtml($d['email']); ?><br>
                                            <?php endif; ?>
                                            <?php if (!empty($d['phone'])): ?>
                                                <i class="fas fa-phone text-muted"></i> <?php echo safeHtml($d['phone']); ?>
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <a href="employees.php?department=<?php echo urlencode($d['name']); ?>" 
                                           class="badge badge-primary" style="text-decoration: none;">
                                            <i class="fas fa-users"></i> <?php echo (int)$d['employee_count']; ?>
                                        </a>
                                    </td>
                                    <td>
                                        <small class="font-weight-bold"><?php echo formatNairaShort($d['budget'] ?? 0); ?></small>
                                    </td>
                                    <td>
                                        <span class="dept-status <?php echo $d['status']; ?>">
                                            <?php echo ucfirst($d['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm action-btns">
                                            <button onclick='showEditModal(<?php echo json_encode([
                                                "id" => $d["id"],
                                                "name" => $d["name"],
                                                "code" => $d["code"],
                                                "description" => $d["description"],
                                                "head_of_department" => $d["head_of_department"],
                                                "email" => $d["email"],
                                                "phone" => $d["phone"],
                                                "location" => $d["location"],
                                                "budget" => $d["budget"],
                                                "status" => $d["status"]
                                            ], JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' 
                                                    class="btn btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="toggleStatus(<?php echo $d['id']; ?>, '<?php echo addslashes($d['name']); ?>', '<?php echo $d['status']; ?>')" 
                                                    class="btn btn-outline-warning" title="Toggle Status">
                                                <i class="fas fa-power-off"></i>
                                            </button>
                                            <button onclick="deleteDept(<?php echo $d['id']; ?>, '<?php echo addslashes($d['name']); ?>')" 
                                                    class="btn btn-outline-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-5">
                                    <i class="fas fa-building fa-4x mb-3 d-block" style="opacity: 0.3;"></i>
                                    <h4>No Departments Found</h4>
                                    <p>Click "Add Department" to create your first department.</p>
                                    <button onclick="showAddModal()" class="btn btn-primary">
                                        <i class="fas fa-plus-circle"></i> Add Department
                                    </button>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($departments->num_rows > 0): ?>
            <div class="card-footer text-muted small">
                <i class="fas fa-info-circle"></i>
                Showing <?php echo $departments->num_rows; ?> of <?php echo $stats['total']; ?> departments
            </div>
        <?php endif; ?>
    </div>
    
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="deptModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="departments.php">
                <input type="hidden" name="action" id="deptAction" value="add">
                <input type="hidden" name="id" id="deptId">
                
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">
                        <i class="fas fa-plus-circle"></i> Add Department
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label class="required">Department Name</label>
                                <input type="text" name="name" id="deptName" class="form-control" 
                                       placeholder="e.g., Computer Science" required maxlength="100">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="required">Code</label>
                                <input type="text" name="code" id="deptCode" class="form-control" 
                                       placeholder="e.g., CSC" required maxlength="10" 
                                       style="text-transform: uppercase;">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" id="deptDescription" class="form-control" 
                                  rows="2" placeholder="Brief description of the department"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Head of Department</label>
                                <input type="text" name="head_of_department" id="deptHead" 
                                       class="form-control" placeholder="e.g., Dr. Adebayo Ogundipe">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Location</label>
                                <input type="text" name="location" id="deptLocation" 
                                       class="form-control" placeholder="e.g., Block A, Floor 1">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" id="deptEmail" 
                                       class="form-control" placeholder="dept@polyibadan.edu.ng">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="text" name="phone" id="deptPhone" 
                                       class="form-control" placeholder="08012345678">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Annual Budget (₦)</label>
                                <input type="number" name="budget" id="deptBudget" 
                                       class="form-control" placeholder="0.00" min="0" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" id="deptStatus" class="form-control">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Department
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.required::after {
    content: " *";
    color: #e74a3b;
    font-weight: bold;
}
</style>

<?php require_once 'includes/footer.php'; ?>