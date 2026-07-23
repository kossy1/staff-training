<?php
// admin/trainings.php - Manage All Trainings
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Get filter parameters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$type = isset($_GET['type']) ? sanitizeInput($_GET['type']) : '';
$category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';
$sort = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'newest';
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $per_page;

// Build the query
$query = "SELECT * FROM training_programs WHERE 1=1";
$count_query = "SELECT COUNT(*) as total FROM training_programs WHERE 1=1";
$params = [];
$types = "";

if ($search) {
    $query .= " AND (title LIKE ? OR description LIKE ? OR trainer_name LIKE ? OR location LIKE ?)";
    $count_query .= " AND (title LIKE ? OR description LIKE ? OR trainer_name LIKE ? OR location LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $types .= "ssss";
}

if ($status) {
    $query .= " AND status = ?";
    $count_query .= " AND status = ?";
    $params[] = $status;
    $types .= "s";
}

if ($type) {
    $query .= " AND type = ?";
    $count_query .= " AND type = ?";
    $params[] = $type;
    $types .= "s";
}

if ($category) {
    $query .= " AND category = ?";
    $count_query .= " AND category = ?";
    $params[] = $category;
    $types .= "s";
}

// Sorting
switch ($sort) {
    case 'oldest':
        $query .= " ORDER BY created_at ASC";
        break;
    case 'title':
        $query .= " ORDER BY title ASC";
        break;
    case 'title_desc':
        $query .= " ORDER BY title DESC";
        break;
    case 'cost_high':
        $query .= " ORDER BY cost DESC";
        break;
    case 'cost_low':
        $query .= " ORDER BY cost ASC";
        break;
    case 'start_date':
        $query .= " ORDER BY start_date ASC";
        break;
    default:
        $query .= " ORDER BY created_at DESC";
        break;
}

$query .= " LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$types .= "ii";

// Prepare and execute main query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$trainings = $stmt->get_result();

// Get total count for pagination
$count_stmt = $conn->prepare($count_query);
$count_params = array_slice($params, 0, -2); // Remove limit and offset params
if (!empty($count_params)) {
    $count_types = substr($types, 0, -2);
    $count_stmt->bind_param($count_types, ...$count_params);
}
$count_stmt->execute();
$total_result = $count_stmt->get_result();
$total_trainings = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_trainings / $per_page);

// Get statistics
$stats = [
    'total' => $conn->query("SELECT COUNT(*) as count FROM training_programs")->fetch_assoc()['count'],
    'upcoming' => $conn->query("SELECT COUNT(*) as count FROM training_programs WHERE status = 'upcoming'")->fetch_assoc()['count'],
    'ongoing' => $conn->query("SELECT COUNT(*) as count FROM training_programs WHERE status = 'ongoing'")->fetch_assoc()['count'],
    'completed' => $conn->query("SELECT COUNT(*) as count FROM training_programs WHERE status = 'completed'")->fetch_assoc()['count'],
    'cancelled' => $conn->query("SELECT COUNT(*) as count FROM training_programs WHERE status = 'cancelled'")->fetch_assoc()['count'],
    'total_cost' => $conn->query("SELECT SUM(cost) as total FROM training_programs WHERE cost > 0")->fetch_assoc()['total'],
    'avg_cost' => $conn->query("SELECT AVG(cost) as avg FROM training_programs WHERE cost > 0")->fetch_assoc()['avg']
];

// Get unique categories and types for filters
$categories = $conn->query("SELECT DISTINCT category FROM training_programs WHERE category IS NOT NULL AND category != '' ORDER BY category");
$types_list = $conn->query("SELECT DISTINCT type FROM training_programs ORDER BY type");

$page_title = 'Manage Trainings';
$page_scripts = '
<script>
$(document).ready(function() {
    // Initialize DataTable
    if ($.fn.DataTable) {
        $("#trainingsTable").DataTable({
            responsive: true,
            pageLength: 25,
            ordering: true,
            searching: false,
            lengthChange: true,
            info: true,
            paging: true,
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search trainings...",
                lengthMenu: "_MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "No entries found",
                infoFiltered: "(filtered from _MAX_ total entries)"
            }
        });
    }
    
    // Export functionality
    $(".export-btn").on("click", function() {
        const type = $(this).data("type") || "csv";
        const status = $("#filterStatus").val();
        const url = `export-trainings.php?type=${type}&status=${status}`;
        window.location.href = url;
    });
});

function deleteTraining(id) {
    Swal.fire({
        title: "Delete Training?",
        text: "This will also remove all enrollments for this training! This action cannot be undone.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Yes, delete it!",
        cancelButtonText: "Cancel"
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "delete-training.php?id=" + id;
        }
    });
}

function changeStatus(id, status) {
    Swal.fire({
        title: "Update Status",
        text: `Are you sure you want to change this training to "${status}"?`,
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#28a745",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Yes, update it!",
        cancelButtonText: "Cancel"
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "update-training-status.php?id=" + id + "&status=" + status;
        }
    });
}

function viewTraining(id) {
    window.location.href = "view-training.php?id=" + id;
}

function editTraining(id) {
    window.location.href = "edit-training.php?id=" + id;
}

function duplicateTraining(id) {
    Swal.fire({
        title: "Duplicate Training?",
        text: "This will create a copy of this training program.",
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#667eea",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Yes, duplicate it!",
        cancelButtonText: "Cancel"
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "duplicate-training.php?id=" + id;
        }
    });
}

// Apply filters
function applyFilters() {
    const form = document.getElementById("filterForm");
    const formData = new FormData(form);
    const params = new URLSearchParams();
    for (const [key, value] of formData.entries()) {
        if (value) params.append(key, value);
    }
    window.location.href = "?" + params.toString();
}

// Reset filters
function resetFilters() {
    window.location.href = "trainings.php";
}

// Quick filter by status
function filterByStatus(status) {
    window.location.href = "?status=" + status;
}
</script>
';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<style>
/* Trainings Page Styles */
.stats-card {
    padding: 15px 20px;
    border-radius: 10px;
    background: white;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    transition: all 0.3s ease;
    height: 100%;
    border-left: 4px solid #667eea;
}
.stats-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.2);
}
.stats-card .stat-number {
    font-size: 1.5rem;
    font-weight: 800;
}
.stats-card .stat-label {
    color: #6c757d;
    font-size: 0.85rem;
    font-weight: 500;
}
.stats-card .stat-icon {
    font-size: 1.5rem;
    opacity: 0.5;
}
.stats-card.primary { border-left-color: #667eea; }
.stats-card.success { border-left-color: #48bb78; }
.stats-card.warning { border-left-color: #f6c23e; }
.stats-card.info { border-left-color: #36b9cc; }
.stats-card.danger { border-left-color: #e74a3b; }

.filter-section {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    margin-bottom: 20px;
}
.status-badge {
    padding: 5px 12px;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 600;
}
.status-badge.upcoming { background: #fff3cd; color: #856404; }
.status-badge.ongoing { background: #d4edda; color: #155724; }
.status-badge.completed { background: #cce5ff; color: #004085; }
.status-badge.cancelled { background: #f8d7da; color: #721c24; }

.action-buttons .btn {
    padding: 3px 8px;
    font-size: 0.75rem;
    margin: 0 2px;
}
.action-buttons .btn i {
    font-size: 0.85rem;
}

.table th {
    font-weight: 600;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.table td {
    vertical-align: middle;
}

.pagination .page-link {
    padding: 8px 14px;
    margin: 0 3px;
    border-radius: 6px;
    color: #667eea;
}
.pagination .page-item.active .page-link {
    background: #667eea;
    border-color: #667eea;
    color: white;
}
.pagination .page-link:hover {
    background: rgba(102, 126, 234, 0.1);
    color: #667eea;
}

@media (max-width: 768px) {
    .stats-card .stat-number {
        font-size: 1.2rem;
    }
    .filter-section .row > div {
        margin-bottom: 10px;
    }
}
</style>

<div class="main-content">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <div>
                <h1><i class="fas fa-chalkboard-teacher text-primary"></i> Manage Trainings</h1>
                <p class="text-muted">View and manage all training programs</p>
            </div>
            <div>
                <a href="add-training.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Add New Training
                </a>
                <button class="btn btn-success" onclick="exportTrainings()">
                    <i class="fas fa-file-export"></i> Export
                </button>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-2">
            <div class="stats-card primary">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-number"><?php echo $stats['total']; ?></div>
                        <div class="stat-label">Total</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-list"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="stats-card warning">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-number"><?php echo $stats['upcoming']; ?></div>
                        <div class="stat-label">Upcoming</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="stats-card success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-number"><?php echo $stats['ongoing']; ?></div>
                        <div class="stat-label">Ongoing</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-play"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="stats-card info">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-number"><?php echo $stats['completed']; ?></div>
                        <div class="stat-label">Completed</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="stats-card danger">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-number"><?php echo $stats['cancelled']; ?></div>
                        <div class="stat-label">Cancelled</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="stats-card primary">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-number"><?php echo formatNairaShort($stats['total_cost'] ?? 0); ?></div>
                        <div class="stat-label">Total Cost</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Status Filters -->
    <div class="mb-3 d-flex flex-wrap gap-2">
        <button class="btn btn-sm btn-outline-secondary" onclick="resetFilters()">All</button>
        <button class="btn btn-sm btn-outline-warning" onclick="filterByStatus('upcoming')">
            <i class="fas fa-clock"></i> Upcoming
        </button>
        <button class="btn btn-sm btn-outline-success" onclick="filterByStatus('ongoing')">
            <i class="fas fa-play"></i> Ongoing
        </button>
        <button class="btn btn-sm btn-outline-info" onclick="filterByStatus('completed')">
            <i class="fas fa-check-circle"></i> Completed
        </button>
        <button class="btn btn-sm btn-outline-danger" onclick="filterByStatus('cancelled')">
            <i class="fas fa-times-circle"></i> Cancelled
        </button>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <form id="filterForm" method="GET" onsubmit="event.preventDefault(); applyFilters();">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <div class="form-group mb-0">
                        <label>Search</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search trainings..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group mb-0">
                        <label>Status</label>
                        <select name="status" id="filterStatus" class="form-control">
                            <option value="">All Status</option>
                            <option value="upcoming" <?php echo $status == 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                            <option value="ongoing" <?php echo $status == 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                            <option value="completed" <?php echo $status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group mb-0">
                        <label>Type</label>
                        <select name="type" class="form-control">
                            <option value="">All Types</option>
                            <?php while ($t = $types_list->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($t['type']); ?>" 
                                    <?php echo $type == $t['type'] ? 'selected' : ''; ?>>
                                    <?php echo ucfirst(str_replace('_', ' ', $t['type'])); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group mb-0">
                        <label>Category</label>
                        <select name="category" class="form-control">
                            <option value="">All Categories</option>
                            <?php while ($cat = $categories->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($cat['category']); ?>" 
                                    <?php echo $category == $cat['category'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-filter"></i>
                    </button>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-secondary btn-block" onclick="resetFilters()">
                        <i class="fas fa-undo"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Trainings Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="trainingsTable">
                    <thead>
                        <tr>
                            <th style="width: 5%;">ID</th>
                            <th style="width: 25%;">Training</th>
                            <th style="width: 12%;">Type</th>
                            <th style="width: 12%;">Date</th>
                            <th style="width: 10%;">Capacity</th>
                            <th style="width: 10%;">Cost (₦)</th>
                            <th style="width: 10%;">Status</th>
                            <th style="width: 16%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($trainings && $trainings->num_rows > 0): ?>
                            <?php while ($training = $trainings->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <span class="badge badge-light">#<?php echo $training['id']; ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="training-icon mr-2">
                                                <i class="fas fa-<?php 
                                                    echo $training['type'] == 'technical' ? 'code' : 
                                                        ($training['type'] == 'soft_skill' ? 'comments' : 
                                                        ($training['type'] == 'management' ? 'users-cog' : 
                                                        ($training['type'] == 'compliance' ? 'shield-alt' : 'book'))); 
                                                ?> text-primary"></i>
                                            </div>
                                            <div>
                                                <a href="view-training.php?id=<?php echo $training['id']; ?>" 
                                                   class="text-dark font-weight-bold">
                                                    <?php echo htmlspecialchars($training['title']); ?>
                                                </a>
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($training['category'] ?? 'Uncategorized'); ?>
                                                    <?php if ($training['location']): ?>
                                                        <span class="mx-1">•</span>
                                                        <i class="fas fa-map-marker-alt"></i> 
                                                        <?php echo htmlspecialchars($training['location']); ?>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $training['type'] == 'technical' ? 'primary' : 
                                                ($training['type'] == 'soft_skill' ? 'success' : 
                                                ($training['type'] == 'management' ? 'info' : 
                                                ($training['type'] == 'compliance' ? 'warning' : 'secondary'))); 
                                        ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $training['type'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small>
                                            <i class="far fa-calendar-alt"></i> 
                                            <?php echo formatDate($training['start_date']); ?>
                                            <br>
                                            <i class="far fa-clock"></i> 
                                            <?php echo $training['duration_hours']; ?> hrs
                                        </small>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span><?php echo $training['current_participants']; ?>/<?php echo $training['max_participants']; ?></span>
                                            <div class="progress ml-2" style="height: 4px; width: 40px;">
                                                <div class="progress-bar" style="width: <?php echo ($training['max_participants'] > 0) ? ($training['current_participants'] / $training['max_participants']) * 100 : 0; ?>%;"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($training['cost'] > 0): ?>
                                            <span class="text-success font-weight-bold">
                                                <?php echo formatNaira($training['cost']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">Free</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $training['status']; ?>">
                                            <i class="fas fa-<?php 
                                                echo $training['status'] == 'upcoming' ? 'clock' : 
                                                    ($training['status'] == 'ongoing' ? 'play' : 
                                                    ($training['status'] == 'completed' ? 'check-circle' : 'times-circle')); 
                                            ?>"></i>
                                            <?php echo ucfirst($training['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button onclick="viewTraining(<?php echo $training['id']; ?>)" 
                                                    class="btn btn-outline-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button onclick="editTraining(<?php echo $training['id']; ?>)" 
                                                    class="btn btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-outline-secondary dropdown-toggle" 
                                                        data-toggle="dropdown" title="More Actions">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-right">
                                                    <a class="dropdown-item" href="view-training.php?id=<?php echo $training['id']; ?>">
                                                        <i class="fas fa-eye"></i> View Details
                                                    </a>
                                                    <a class="dropdown-item" href="edit-training.php?id=<?php echo $training['id']; ?>">
                                                        <i class="fas fa-edit"></i> Edit Training
                                                    </a>
                                                    <a class="dropdown-item" href="enrollments.php?training_id=<?php echo $training['id']; ?>">
                                                        <i class="fas fa-users"></i> View Enrollments
                                                    </a>
                                                    <a class="dropdown-item" href="training-attendance.php?id=<?php echo $training['id']; ?>">
                                                        <i class="fas fa-clipboard-list"></i> Attendance
                                                    </a>
                                                    <div class="dropdown-divider"></div>
                                                    <a class="dropdown-item" href="javascript:void(0)" 
                                                       onclick="duplicateTraining(<?php echo $training['id']; ?>)">
                                                        <i class="fas fa-copy"></i> Duplicate
                                                    </a>
                                                    <?php if ($training['status'] != 'cancelled'): ?>
                                                        <a class="dropdown-item" href="javascript:void(0)" 
                                                           onclick="changeStatus(<?php echo $training['id']; ?>, 'cancelled')">
                                                            <i class="fas fa-times-circle text-danger"></i> Cancel
                                                        </a>
                                                    <?php endif; ?>
                                                    <div class="dropdown-divider"></div>
                                                    <a class="dropdown-item text-danger" href="javascript:void(0)" 
                                                       onclick="deleteTraining(<?php echo $training['id']; ?>)">
                                                        <i class="fas fa-trash-alt"></i> Delete
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="fas fa-chalkboard-teacher fa-4x mb-3 d-block"></i>
                                    <h4>No trainings found</h4>
                                    <p>Click "Add New Training" to create your first training program.</p>
                                    <a href="add-training.php" class="btn btn-primary">
                                        <i class="fas fa-plus-circle"></i> Add Training
                                    </a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($total_pages > 1): ?>
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted">
                            Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $per_page, $total_trainings); ?> of <?php echo $total_trainings; ?> trainings
                        </small>
                    </div>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&type=<?php echo urlencode($type); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&type=<?php echo urlencode($type); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&type=<?php echo urlencode($type); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function exportTrainings() {
    const status = document.getElementById('filterStatus').value;
    const url = 'export-trainings.php?status=' + status;
    window.location.href = url;
}
</script>

<?php require_once 'includes/footer.php'; ?>