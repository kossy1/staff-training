<?php
// admin/departments.php - Manage Departments
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Get departments with statistics
$departments = $conn->query("
    SELECT 
        e.department,
        COUNT(DISTINCT e.id) as employee_count,
        SUM(CASE WHEN e.status = 'active' THEN 1 ELSE 0 END) as active_employees,
        COUNT(DISTINCT et.id) as training_count,
        COUNT(DISTINCT c.id) as certification_count
    FROM employees e
    LEFT JOIN employee_trainings et ON e.id = et.employee_id
    LEFT JOIN certifications c ON e.id = c.employee_id
    WHERE e.department IS NOT NULL AND e.department != ''
    GROUP BY e.department
    ORDER BY employee_count DESC
");

$page_title = 'Departments';
$page_scripts = '
<script>
$(document).ready(function() {
    $("#departmentsTable").DataTable({
        responsive: true,
        pageLength: 25,
        order: [[1, "desc"]]
    });
});
</script>
';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-building text-primary"></i> Departments</h1>
            <p class="text-muted">Manage and view department statistics</p>
        </div>
        <div>
            <button class="btn btn-primary" onclick="showAddDepartment()">
                <i class="fas fa-plus-circle"></i> Add Department
            </button>
        </div>
    </div>

    <!-- Department Statistics -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Departments</h6>
                    <h2><?php echo number_format($departments->num_rows); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Employees</h6>
                    <h2><?php 
                        $total = $conn->query("SELECT COUNT(*) as count FROM employees WHERE status = 'active'")->fetch_assoc();
                        echo number_format($total['count']);
                    ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title">Avg Employees/Dept</h6>
                    <h2><?php 
                        $avg = $departments->num_rows > 0 ? round($total['count'] / $departments->num_rows, 1) : 0;
                        echo number_format($avg);
                    ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="card-title">Active Departments</h6>
                    <h2><?php 
                        $active = $conn->query("SELECT COUNT(DISTINCT department) as count FROM employees WHERE status = 'active' AND department IS NOT NULL")->fetch_assoc();
                        echo number_format($active['count']);
                    ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Departments Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="departmentsTable">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Total Employees</th>
                            <th>Active Employees</th>
                            <th>Trainings</th>
                            <th>Certifications</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($departments && $departments->num_rows > 0): ?>
                            <?php while ($dept = $departments->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($dept['department']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge badge-primary"><?php echo $dept['employee_count']; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge badge-success"><?php echo $dept['active_employees']; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge badge-info"><?php echo $dept['training_count']; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge badge-warning"><?php echo $dept['certification_count']; ?></span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="employees.php?department=<?php echo urlencode($dept['department']); ?>" 
                                               class="btn btn-outline-primary" title="View Employees">
                                                <i class="fas fa-users"></i>
                                            </a>
                                            <a href="trainings.php?department=<?php echo urlencode($dept['department']); ?>" 
                                               class="btn btn-outline-success" title="View Trainings">
                                                <i class="fas fa-chalkboard-teacher"></i>
                                            </a>
                                            <button onclick="editDepartment('<?php echo htmlspecialchars($dept['department']); ?>')" 
                                                    class="btn btn-outline-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="deleteDepartment('<?php echo htmlspecialchars($dept['department']); ?>')" 
                                                    class="btn btn-outline-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="fas fa-building fa-3x mb-3 d-block"></i>
                                    <h5>No departments found</h5>
                                    <p>Add a department to get started.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Department Statistics Chart -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Department Distribution</h5>
        </div>
        <div class="card-body">
            <div class="chart-container" style="height: 300px;">
                <canvas id="deptDistributionChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Department Modal -->
<div class="modal fade" id="departmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="departmentModalTitle">Add Department</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST" id="departmentForm">
                <div class="modal-body">
                    <input type="hidden" name="action" id="deptAction" value="add">
                    <input type="hidden" name="old_name" id="oldDepartmentName">
                    <div class="form-group">
                        <label class="required">Department Name</label>
                        <input type="text" name="department_name" id="departmentName" 
                               class="form-control" placeholder="Enter department name" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" id="departmentDescription" 
                                  class="form-control" rows="3" 
                                  placeholder="Brief description of the department"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Department Chart
document.addEventListener("DOMContentLoaded", function() {
    const deptCtx = document.getElementById("deptDistributionChart");
    if (deptCtx) {
        const deptData = <?php 
            $data = [];
            $depts = $conn->query("SELECT department, COUNT(*) as count FROM employees WHERE status = 'active' AND department IS NOT NULL GROUP BY department ORDER BY count DESC LIMIT 10");
            while ($d = $depts->fetch_assoc()) {
                $data[] = ['label' => $d['department'], 'value' => $d['count']];
            }
            echo json_encode($data);
        ?>;
        
        new Chart(deptCtx, {
            type: 'bar',
            data: {
                labels: deptData.map(item => item.label),
                datasets: [{
                    label: 'Employees',
                    data: deptData.map(item => item.value),
                    backgroundColor: ['#667eea', '#48bb78', '#f6c23e', '#e74a3b', '#36b9cc', '#764ba2', '#6c757d', '#20c9a6', '#f8b4b4', '#b4c6e7'],
                    borderWidth: 2,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }
});

function showAddDepartment() {
    $('#departmentModalTitle').text('Add Department');
    $('#deptAction').val('add');
    $('#departmentName').val('');
    $('#departmentDescription').val('');
    $('#oldDepartmentName').val('');
    $('#departmentModal').modal('show');
}

function editDepartment(name) {
    $('#departmentModalTitle').text('Edit Department');
    $('#deptAction').val('edit');
    $('#departmentName').val(name);
    $('#oldDepartmentName').val(name);
    $('#departmentModal').modal('show');
}

function deleteDepartment(name) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This will remove the department from all employees!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'delete-department.php?name=' + encodeURIComponent(name);
        }
    });
}

// Department Form Submission
$('#departmentForm').on('submit', function(e) {
    e.preventDefault();
    const formData = $(this).serialize();
    
    $.ajax({
        url: 'save-department.php',
        method: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: response.message,
                    timer: 2000,
                    showConfirmButton: false
                });
                setTimeout(function() {
                    location.reload();
                }, 2000);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: response.message
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Failed to save department. Please try again.'
            });
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>