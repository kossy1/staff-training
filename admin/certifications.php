<?php
// admin/certifications.php - Manage Certifications (Complete)
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Get filter parameters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$employee_id = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;
$date_from = isset($_GET['date_from']) ? sanitizeInput($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitizeInput($_GET['date_to']) : '';

// Build query
$query = "
    SELECT c.*, 
           CONCAT(e.first_name, ' ', e.last_name) as employee_name,
           e.email as employee_email,
           e.department as employee_department,
           e.position as employee_position,
           tp.title as training_title,
           tp.type as training_type
    FROM certifications c
    LEFT JOIN employees e ON c.employee_id = e.id
    LEFT JOIN training_programs tp ON c.training_id = tp.id
    WHERE 1=1
";
$params = [];
$types = "";

if ($search) {
    $query .= " AND (c.certification_name LIKE ? OR c.certification_number LIKE ? OR CONCAT(e.first_name, ' ', e.last_name) LIKE ? OR e.email LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $types .= "ssss";
}

if ($status) {
    $query .= " AND c.status = ?";
    $params[] = $status;
    $types .= "s";
}

if ($employee_id > 0) {
    $query .= " AND c.employee_id = ?";
    $params[] = $employee_id;
    $types .= "i";
}

if ($date_from) {
    $query .= " AND DATE(c.issue_date) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if ($date_to) {
    $query .= " AND DATE(c.issue_date) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$query .= " ORDER BY c.created_at DESC";

// Prepare and execute
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$certifications = $stmt->get_result();

// Get statistics
$stats = [
    'total' => $conn->query("SELECT COUNT(*) as count FROM certifications")->fetch_assoc()['count'],
    'active' => $conn->query("SELECT COUNT(*) as count FROM certifications WHERE status = 'active'")->fetch_assoc()['count'],
    'expired' => $conn->query("SELECT COUNT(*) as count FROM certifications WHERE status = 'expired'")->fetch_assoc()['count'],
    'revoked' => $conn->query("SELECT COUNT(*) as count FROM certifications WHERE status = 'revoked'")->fetch_assoc()['count'],
    'expiring_soon' => $conn->query("
        SELECT COUNT(*) as count 
        FROM certifications 
        WHERE status = 'active' 
        AND expiry_date IS NOT NULL 
        AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        AND expiry_date >= CURDATE()
    ")->fetch_assoc()['count']
];

// Get employees for filter
$employees = $conn->query("SELECT id, first_name, last_name, email FROM employees WHERE status = 'active' ORDER BY first_name");

$page_title = 'Certifications';
$page_scripts = '
<script>
$(document).ready(function() {
    // Initialize DataTable
    if ($.fn.DataTable) {
        $("#certificationsTable").DataTable({
            responsive: true,
            pageLength: 25,
            ordering: true,
            searching: false,
            lengthChange: true,
            info: true,
            paging: true,
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search certifications...",
                lengthMenu: "_MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ certifications",
                infoEmpty: "No certifications found",
                infoFiltered: "(filtered from _MAX_ total certifications)"
            }
        });
    }
});

function deleteCertification(id) {
    Swal.fire({
        title: "Delete Certification?",
        text: "This action cannot be undone!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Yes, delete it!",
        cancelButtonText: "Cancel"
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "delete-certificate.php?id=" + id;
        }
    });
}

function updateStatus(id, status) {
    const statusLabels = {
        "active": "Active",
        "expired": "Expired",
        "revoked": "Revoked"
    };
    
    Swal.fire({
        title: "Update Status",
        text: "Are you sure you want to change this certification status to \"" + statusLabels[status] + "\"?",
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#28a745",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Yes, update it!",
        cancelButtonText: "Cancel"
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "update-certificate-status.php?id=" + id + "&status=" + status;
        }
    });
}

function viewCertificate(id) {
    window.location.href = "view-certificate.php?id=" + id;
}

function downloadCertificate(id) {
    window.location.href = "download-certificate.php?id=" + id;
}

function renewCertificate(id) {
    Swal.fire({
        title: "Renew Certification",
        text: "This will extend the expiry date by 1 year.",
        icon: "info",
        showCancelButton: true,
        confirmButtonColor: "#28a745",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Yes, renew it!",
        cancelButtonText: "Cancel"
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "renew-certificate.php?id=" + id;
        }
    });
}

function sendCertificateEmail(id) {
    Swal.fire({
        title: "Send Certificate via Email?",
        text: "This will send the certificate to the employees email.",
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#28a745",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Yes, send it!",
        cancelButtonText: "Cancel"
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "send-certificate-email.php?id=" + id;
        }
    });
}

function exportCertificates() {
    const status = document.getElementById("filterStatus").value;
    const url = "export-certificates.php?status=" + status;
    window.location.href = url;
}

function applyFilters() {
    const form = document.getElementById("filterForm");
    const formData = new FormData(form);
    const params = new URLSearchParams();
    for (const [key, value] of formData.entries()) {
        if (value) params.append(key, value);
    }
    window.location.href = "?" + params.toString();
}

function resetFilters() {
    window.location.href = "certifications.php";
}
</script>
';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<style>
/* Certifications Page Styles */
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
.stats-card.danger { border-left-color: #e74a3b; }
.stats-card.info { border-left-color: #36b9cc; }

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
.status-badge.active { background: #d4edda; color: #155724; }
.status-badge.expired { background: #f8d7da; color: #721c24; }
.status-badge.revoked { background: #fff3cd; color: #856404; }

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

.cert-badge {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 600;
    background: #e2e8f0;
    color: #4a5568;
}
.cert-badge.has-file {
    background: #d4edda;
    color: #155724;
}
.cert-badge.no-file {
    background: #f8d7da;
    color: #721c24;
}

.expiring-soon {
    animation: pulse-warning 2s infinite;
}
@keyframes pulse-warning {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}

@media (max-width: 768px) {
    .stats-card .stat-number {
        font-size: 1.2rem;
    }
    .filter-section .row > div {
        margin-bottom: 10px;
    }
    .action-buttons .btn {
        padding: 3px 6px;
        font-size: 0.7rem;
    }
}
</style>

<div class="main-content">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <div>
                <h1><i class="fas fa-certificate text-primary"></i> Certifications</h1>
                <p class="text-muted">Manage all employee certifications</p>
            </div>
            <div>
                <a href="add-certification.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Issue Certification
                </a>
                <button class="btn btn-success" onclick="exportCertificates()">
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
                        <i class="fas fa-certificate"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="stats-card success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-number"><?php echo $stats['active']; ?></div>
                        <div class="stat-label">Active</div>
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
                        <div class="stat-number"><?php echo $stats['expired']; ?></div>
                        <div class="stat-label">Expired</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="stats-card warning">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-number"><?php echo $stats['revoked']; ?></div>
                        <div class="stat-label">Revoked</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-ban"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="stats-card info">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-number"><?php echo $stats['expiring_soon']; ?></div>
                        <div class="stat-label">Expiring Soon</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="stats-card primary">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-number">
                            <?php 
                            $has_file = $conn->query("SELECT COUNT(*) as count FROM certifications WHERE file_path IS NOT NULL AND file_path != ''")->fetch_assoc();
                            echo $has_file['count'];
                            ?>
                        </div>
                        <div class="stat-label">Has Files</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Status Filters -->
    <div class="mb-3 d-flex flex-wrap gap-2">
        <button class="btn btn-sm btn-outline-secondary" onclick="resetFilters()">All</button>
        <button class="btn btn-sm btn-outline-success" onclick="filterByStatus('active')">
            <i class="fas fa-check-circle"></i> Active
        </button>
        <button class="btn btn-sm btn-outline-danger" onclick="filterByStatus('expired')">
            <i class="fas fa-times-circle"></i> Expired
        </button>
        <button class="btn btn-sm btn-outline-warning" onclick="filterByStatus('revoked')">
            <i class="fas fa-ban"></i> Revoked
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
                               placeholder="Search certifications..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group mb-0">
                        <label>Status</label>
                        <select name="status" id="filterStatus" class="form-control">
                            <option value="">All Status</option>
                            <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="expired" <?php echo $status == 'expired' ? 'selected' : ''; ?>>Expired</option>
                            <option value="revoked" <?php echo $status == 'revoked' ? 'selected' : ''; ?>>Revoked</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group mb-0">
                        <label>Employee</label>
                        <select name="employee_id" class="form-control">
                            <option value="">All Employees</option>
                            <?php while ($emp = $employees->fetch_assoc()): ?>
                                <option value="<?php echo $emp['id']; ?>" 
                                    <?php echo $employee_id == $emp['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $emp['email'] . ')'); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group mb-0">
                        <label>Date From</label>
                        <input type="date" name="date_from" class="form-control" 
                               value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group mb-0">
                        <label>Date To</label>
                        <input type="date" name="date_to" class="form-control" 
                               value="<?php echo htmlspecialchars($date_to); ?>">
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

    <!-- Certifications Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="certificationsTable">
                    <thead>
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 15%;">Employee</th>
                            <th style="width: 15%;">Certification</th>
                            <th style="width: 15%;">Training</th>
                            <th style="width: 10%;">Issue Date</th>
                            <th style="width: 10%;">Expiry Date</th>
                            <th style="width: 10%;">File</th>
                            <th style="width: 8%;">Status</th>
                            <th style="width: 12%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($certifications && $certifications->num_rows > 0): ?>
                            <?php $count = 1; ?>
                            <?php while ($cert = $certifications->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <span class="badge badge-light"><?php echo $count++; ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="mr-2">
                                                <i class="fas fa-user-circle text-primary" style="font-size: 1.5rem;"></i>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($cert['employee_name'] ?? 'N/A'); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($cert['employee_email']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($cert['certification_name']); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-hashtag"></i> <?php echo htmlspecialchars($cert['certification_number']); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($cert['training_title'] ?? 'N/A'); ?>
                                        <br>
                                        <small class="text-muted">
                                            <span class="badge badge-<?php 
                                                echo $cert['training_type'] == 'technical' ? 'primary' : 
                                                    ($cert['training_type'] == 'soft_skill' ? 'success' : 
                                                    ($cert['training_type'] == 'management' ? 'info' : 'secondary')); 
                                            ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $cert['training_type'] ?? 'N/A')); ?>
                                            </span>
                                        </small>
                                    </td>
                                    <td>
                                        <small>
                                            <i class="far fa-calendar-alt"></i> 
                                            <?php echo formatDate($cert['issue_date']); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($cert['expiry_date']): ?>
                                            <small>
                                                <i class="far fa-calendar-check"></i> 
                                                <?php echo formatDate($cert['expiry_date']); ?>
                                            </small>
                                            <?php if (strtotime($cert['expiry_date']) < time()): ?>
                                                <span class="badge badge-danger">Expired</span>
                                            <?php elseif (strtotime($cert['expiry_date']) < strtotime('+30 days')): ?>
                                                <span class="badge badge-warning expiring-soon">Expiring Soon</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">Never</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="cert-badge <?php echo !empty($cert['file_path']) ? 'has-file' : 'no-file'; ?>">
                                            <i class="fas <?php echo !empty($cert['file_path']) ? 'fa-file-pdf' : 'fa-file'; ?>"></i>
                                            <?php echo !empty($cert['file_path']) ? 'Yes' : 'No'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $cert['status']; ?>">
                                            <?php echo ucfirst($cert['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm action-buttons">
                                            <button onclick="viewCertificate(<?php echo $cert['id']; ?>)" 
                                                    class="btn btn-outline-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button onclick="downloadCertificate(<?php echo $cert['id']; ?>)" 
                                                    class="btn btn-outline-success" title="Download">
                                                <i class="fas fa-download"></i>
                                            </button>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-outline-secondary dropdown-toggle" 
                                                        data-toggle="dropdown" title="More Actions">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-right">
                                                    <a class="dropdown-item" href="view-certificate.php?id=<?php echo $cert['id']; ?>">
                                                        <i class="fas fa-eye"></i> View Details
                                                    </a>
                                                    <a class="dropdown-item" href="download-certificate.php?id=<?php echo $cert['id']; ?>">
                                                        <i class="fas fa-download"></i> Download
                                                    </a>
                                                    <?php if ($cert['status'] != 'revoked'): ?>
                                                        <a class="dropdown-item" href="javascript:void(0)" 
                                                           onclick="updateStatus(<?php echo $cert['id']; ?>, 'revoked')">
                                                            <i class="fas fa-ban text-warning"></i> Revoke
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if ($cert['status'] == 'active' && $cert['expiry_date']): ?>
                                                        <a class="dropdown-item" href="javascript:void(0)" 
                                                           onclick="renewCertificate(<?php echo $cert['id']; ?>)">
                                                            <i class="fas fa-sync-alt text-success"></i> Renew
                                                        </a>
                                                    <?php endif; ?>
                                                    <a class="dropdown-item" href="javascript:void(0)" 
                                                       onclick="sendCertificateEmail(<?php echo $cert['id']; ?>)">
                                                        <i class="fas fa-envelope text-primary"></i> Send Email
                                                    </a>
                                                    <div class="dropdown-divider"></div>
                                                    <a class="dropdown-item text-danger" href="javascript:void(0)" 
                                                       onclick="deleteCertification(<?php echo $cert['id']; ?>)">
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
                                <td colspan="9" class="text-center text-muted py-5">
                                    <i class="fas fa-certificate fa-4x mb-3 d-block"></i>
                                    <h4>No certifications found</h4>
                                    <p>Click "Issue Certification" to create your first certification.</p>
                                    <a href="add-certification.php" class="btn btn-primary">
                                        <i class="fas fa-plus-circle"></i> Issue Certification
                                    </a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Quick Stats Footer -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <h5 class="text-primary"><?php echo $stats['active']; ?></h5>
                            <small class="text-muted">Active Certifications</small>
                        </div>
                        <div class="col-md-3">
                            <h5 class="text-danger"><?php echo $stats['expired']; ?></h5>
                            <small class="text-muted">Expired Certifications</small>
                        </div>
                        <div class="col-md-3">
                            <h5 class="text-warning"><?php echo $stats['expiring_soon']; ?></h5>
                            <small class="text-muted">Expiring Within 30 Days</small>
                        </div>
                        <div class="col-md-3">
                            <h5 class="text-success"><?php echo $stats['total']; ?></h5>
                            <small class="text-muted">Total Certifications</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Quick filter functions
function filterByStatus(status) {
    window.location.href = "?status=" + status;
}

// Export function
function exportCertificates() {
    const status = document.getElementById('filterStatus').value;
    window.location.href = "export-certificates.php?status=" + status;
}
</script>

<?php require_once 'includes/footer.php'; ?>