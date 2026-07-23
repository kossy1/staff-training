<?php
// admin/reports.php - Reports with Naira
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Get statistics
$total_employees = $conn->query("SELECT COUNT(*) as count FROM employees WHERE status = 'active'")->fetch_assoc()['count'];
$total_trainings = $conn->query("SELECT COUNT(*) as count FROM training_programs")->fetch_assoc()['count'];
$total_certifications = $conn->query("SELECT COUNT(*) as count FROM certifications")->fetch_assoc()['count'];

// Get cost summary
$cost_summary = $conn->query("
    SELECT 
        SUM(cost) as total_cost,
        AVG(cost) as avg_cost,
        MIN(cost) as min_cost,
        MAX(cost) as max_cost,
        COUNT(*) as total_trainings,
        SUM(CASE WHEN cost = 0 OR cost IS NULL THEN 1 ELSE 0 END) as free_trainings
    FROM training_programs
")->fetch_assoc();

// Training completion rate
$completion_rate = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
    FROM employee_trainings
")->fetch_assoc();

$completion_percentage = $completion_rate['total'] > 0 ? 
    round(($completion_rate['completed'] / $completion_rate['total']) * 100, 1) : 0;

// Department wise stats
$dept_stats = $conn->query("
    SELECT department, COUNT(*) as count 
    FROM employees 
    WHERE status = 'active' 
    GROUP BY department
");

$page_title = 'Reports';
$page_scripts = '
<script>
$(document).ready(function() {
    var ctx = document.getElementById("deptChart");
    if (ctx) {
        new Chart(ctx, {
            type: "bar",
            data: {
                labels: ' . json_encode(array_column($dept_stats->fetch_all(MYSQLI_ASSOC), 'department')) . ',
                datasets: [{
                    label: "Employees by Department",
                    data: ' . json_encode(array_column($dept_stats->fetch_all(MYSQLI_ASSOC), 'count')) . ',
                    backgroundColor: "rgba(102, 126, 234, 0.7)",
                    borderColor: "#667eea",
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
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
            <h1><i class="fas fa-file-alt text-primary"></i> Reports</h1>
            <p class="text-muted">Generate and view system reports</p>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Employees</h6>
                    <h2 class="mb-0"><?php echo number_format($total_employees); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Trainings</h6>
                    <h2 class="mb-0"><?php echo number_format($total_trainings); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title">Certifications</h6>
                    <h2 class="mb-0"><?php echo number_format($total_certifications); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="card-title">Completion Rate</h6>
                    <h2 class="mb-0"><?php echo $completion_percentage; ?>%</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Cost Summary -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-money-bill-wave text-success"></i> Training Cost Summary (₦ Naira)</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="text-center">
                        <h6 class="text-muted">Total Cost</h6>
                        <h3 class="text-primary"><?php echo formatNaira($cost_summary['total_cost'] ?? 0); ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h6 class="text-muted">Average Cost</h6>
                        <h3 class="text-success"><?php echo formatNaira($cost_summary['avg_cost'] ?? 0); ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h6 class="text-muted">Minimum Cost</h6>
                        <h3 class="text-info"><?php echo formatNaira($cost_summary['min_cost'] ?? 0); ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h6 class="text-muted">Maximum Cost</h6>
                        <h3 class="text-danger"><?php echo formatNaira($cost_summary['max_cost'] ?? 0); ?></h3>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-6">
                    <p><strong>Total Trainings with Cost:</strong> <?php echo number_format($cost_summary['total_trainings'] ?? 0); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Free Trainings:</strong> <?php echo number_format($cost_summary['free_trainings'] ?? 0); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Department Chart -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Employees by Department</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="deptChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Generation -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Generate Reports</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="export-employees.php" class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-users text-primary fa-fw"></i>
                                    Employee Report
                                </div>
                                <i class="fas fa-download text-muted"></i>
                            </div>
                            <small class="text-muted">Export all employee data</small>
                        </a>
                        <a href="export-trainings.php" class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-chalkboard-teacher text-success fa-fw"></i>
                                    Training Report
                                </div>
                                <i class="fas fa-download text-muted"></i>
                            </div>
                            <small class="text-muted">Export training programs data</small>
                        </a>
                        <a href="export-certifications.php" class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-certificate text-warning fa-fw"></i>
                                    Certification Report
                                </div>
                                <i class="fas fa-download text-muted"></i>
                            </div>
                            <small class="text-muted">Export certifications data</small>
                        </a>
                        <a href="export-enrollments.php" class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-user-graduate text-info fa-fw"></i>
                                    Enrollment Report
                                </div>
                                <i class="fas fa-download text-muted"></i>
                            </div>
                            <small class="text-muted">Export training enrollments</small>
                        </a>
                        <a href="export-cost-report.php" class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-money-bill-wave text-danger fa-fw"></i>
                                    Cost Report (₦)
                                </div>
                                <i class="fas fa-download text-muted"></i>
                            </div>
                            <small class="text-muted">Export training cost analysis</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>