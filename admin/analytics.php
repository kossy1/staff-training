<?php
// admin/analytics.php - Analytics Dashboard
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Get analytics data
$total_employees = $conn->query("SELECT COUNT(*) as count FROM employees")->fetch_assoc()['count'];
$active_employees = $conn->query("SELECT COUNT(*) as count FROM employees WHERE status = 'active'")->fetch_assoc()['count'];
$total_trainings = $conn->query("SELECT COUNT(*) as count FROM training_programs")->fetch_assoc()['count'];
$completed_trainings = $conn->query("SELECT COUNT(*) as count FROM employee_trainings WHERE status = 'completed'")->fetch_assoc()['count'];
$total_enrollments = $conn->query("SELECT COUNT(*) as count FROM employee_trainings")->fetch_assoc()['count'];

// Training completion rate
$completion_rate = $total_enrollments > 0 ? round(($completed_trainings / $total_enrollments) * 100, 1) : 0;

// Department distribution
$dept_data = $conn->query("
    SELECT department, COUNT(*) as count 
    FROM employees 
    WHERE status = 'active' 
    GROUP BY department 
    ORDER BY count DESC
");

// Training type distribution
$type_data = $conn->query("
    SELECT type, COUNT(*) as count 
    FROM training_programs 
    GROUP BY type 
    ORDER BY count DESC
");

// Monthly training trends
$monthly_data = $conn->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count 
    FROM training_programs 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
");

$page_title = 'Analytics';
$page_scripts = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Department Chart
    var deptCtx = document.getElementById("deptChart");
    if (deptCtx) {
        new Chart(deptCtx, {
            type: "bar",
            data: {
                labels: ' . json_encode(array_column($dept_data->fetch_all(MYSQLI_ASSOC), 'department')) . ',
                datasets: [{
                    label: "Employees by Department",
                    data: ' . json_encode(array_column($dept_data->fetch_all(MYSQLI_ASSOC), 'count')) . ',
                    backgroundColor: ["#667eea", "#48bb78", "#f6c23e", "#e74a3b", "#36b9cc", "#764ba2"],
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
                        grid: { color: "rgba(0,0,0,0.05)" }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }
    
    // Training Type Chart
    var typeCtx = document.getElementById("typeChart");
    if (typeCtx) {
        new Chart(typeCtx, {
            type: "doughnut",
            data: {
                labels: ' . json_encode(array_column($type_data->fetch_all(MYSQLI_ASSOC), 'type')) . ',
                datasets: [{
                    data: ' . json_encode(array_column($type_data->fetch_all(MYSQLI_ASSOC), 'count')) . ',
                    backgroundColor: ["#667eea", "#48bb78", "#f6c23e", "#e74a3b", "#36b9cc"]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: "bottom",
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });
    }
    
    // Monthly Trends Chart
    var trendCtx = document.getElementById("trendChart");
    if (trendCtx) {
        const monthlyLabels = ' . json_encode(array_column($monthly_data->fetch_all(MYSQLI_ASSOC), 'month')) . ';
        const monthlyCounts = ' . json_encode(array_column($monthly_data->fetch_all(MYSQLI_ASSOC), 'count')) . ';
        
        new Chart(trendCtx, {
            type: "line",
            data: {
                labels: monthlyLabels,
                datasets: [{
                    label: "Trainings Created",
                    data: monthlyCounts,
                    borderColor: "#667eea",
                    backgroundColor: "rgba(102, 126, 234, 0.1)",
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: "#667eea",
                    pointBorderColor: "#fff",
                    pointBorderWidth: 2,
                    pointRadius: 4
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
                        grid: { color: "rgba(0,0,0,0.05)" }
                    },
                    x: {
                        grid: { display: false }
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
            <h1><i class="fas fa-chart-pie text-primary"></i> Analytics</h1>
            <p class="text-muted">View system analytics and statistics</p>
        </div>
        <div>
            <button class="btn btn-outline-secondary" onclick="window.location.reload()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-users"></i>
                </div>
                <h3 class="stat-number"><?php echo number_format($total_employees); ?></h3>
                <p class="stat-label">Total Employees</p>
                <div class="stat-change up">
                    <i class="fas fa-arrow-up"></i> <?php echo $active_employees; ?> active
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <h3 class="stat-number"><?php echo number_format($total_trainings); ?></h3>
                <p class="stat-label">Total Trainings</p>
                <div class="stat-change up">
                    <i class="fas fa-arrow-up"></i> <?php echo number_format($total_enrollments); ?> enrollments
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3 class="stat-number"><?php echo $completion_rate; ?>%</h3>
                <p class="stat-label">Completion Rate</p>
                <div class="stat-change up">
                    <i class="fas fa-arrow-up"></i> <?php echo number_format($completed_trainings); ?> completed
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon danger">
                    <i class="fas fa-certificate"></i>
                </div>
                <h3 class="stat-number"><?php echo number_format($conn->query("SELECT COUNT(*) as count FROM certifications")->fetch_assoc()['count']); ?></h3>
                <p class="stat-label">Certifications</p>
                <div class="stat-change">
                    <i class="fas fa-arrow-right"></i> Total issued
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-building"></i> Employees by Department</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="deptChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-tags"></i> Training Type Distribution</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="typeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-line"></i> Monthly Training Trends</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-list"></i> Quick Statistics</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Metric</th>
                            <th>Value</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Total Employees</td>
                            <td><strong><?php echo number_format($total_employees); ?></strong></td>
                            <td><?php echo number_format($active_employees); ?> active, <?php echo number_format($total_employees - $active_employees); ?> inactive</td>
                        </tr>
                        <tr>
                            <td>Training Programs</td>
                            <td><strong><?php echo number_format($total_trainings); ?></strong></td>
                            <td>
                                <?php 
                                $upcoming = $conn->query("SELECT COUNT(*) as count FROM training_programs WHERE status = 'upcoming'")->fetch_assoc()['count'];
                                $ongoing = $conn->query("SELECT COUNT(*) as count FROM training_programs WHERE status = 'ongoing'")->fetch_assoc()['count'];
                                echo number_format($upcoming) . ' upcoming, ' . number_format($ongoing) . ' ongoing';
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td>Enrollments</td>
                            <td><strong><?php echo number_format($total_enrollments); ?></strong></td>
                            <td>
                                <?php 
                                $enrolled = $conn->query("SELECT COUNT(*) as count FROM employee_trainings WHERE status = 'enrolled'")->fetch_assoc()['count'];
                                $in_progress = $conn->query("SELECT COUNT(*) as count FROM employee_trainings WHERE status = 'in_progress'")->fetch_assoc()['count'];
                                echo number_format($enrolled) . ' enrolled, ' . number_format($in_progress) . ' in progress';
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td>Certifications</td>
                            <td><strong><?php echo number_format($conn->query("SELECT COUNT(*) as count FROM certifications")->fetch_assoc()['count']); ?></strong></td>
                            <td>
                                <?php 
                                $active_certs = $conn->query("SELECT COUNT(*) as count FROM certifications WHERE status = 'active'")->fetch_assoc()['count'];
                                $expired_certs = $conn->query("SELECT COUNT(*) as count FROM certifications WHERE status = 'expired'")->fetch_assoc()['count'];
                                echo number_format($active_certs) . ' active, ' . number_format($expired_certs) . ' expired';
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td>Development Plans</td>
                            <td><strong><?php echo number_format($conn->query("SELECT COUNT(*) as count FROM development_plans")->fetch_assoc()['count']); ?></strong></td>
                            <td>
                                <?php 
                                $completed_plans = $conn->query("SELECT COUNT(*) as count FROM development_plans WHERE status = 'completed'")->fetch_assoc()['count'];
                                $in_progress_plans = $conn->query("SELECT COUNT(*) as count FROM development_plans WHERE status = 'in_progress'")->fetch_assoc()['count'];
                                echo number_format($completed_plans) . ' completed, ' . number_format($in_progress_plans) . ' in progress';
                                ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>