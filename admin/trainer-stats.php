<?php
// admin/trainer-stats.php - Trainer Statistics & Analytics
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Get comprehensive trainer stats
$stats = [
    'total' => $conn->query("SELECT COUNT(*) as c FROM trainers")->fetch_assoc()['c'] ?? 0,
    'active' => $conn->query("SELECT COUNT(*) as c FROM trainers WHERE status = 'active'")->fetch_assoc()['c'] ?? 0,
    'avg_experience' => $conn->query("SELECT AVG(experience_years) as avg FROM trainers WHERE status = 'active'")->fetch_assoc()['avg'] ?? 0,
    'total_trainings_assigned' => $conn->query("SELECT SUM(total_trainings) as total FROM trainers")->fetch_assoc()['total'] ?? 0
];

// Top trainers by training count
$top_trainers = $conn->query("
    SELECT * FROM trainers 
    WHERE status = 'active' 
    ORDER BY total_trainings DESC, experience_years DESC 
    LIMIT 10
");

// Specialization distribution
$specializations = $conn->query("
    SELECT specialization, COUNT(*) as count 
    FROM trainers 
    WHERE specialization IS NOT NULL AND specialization != ''
    GROUP BY specialization 
    ORDER BY count DESC
");

// Recent trainers
$recent_trainers = $conn->query("
    SELECT * FROM trainers 
    ORDER BY created_at DESC 
    LIMIT 5
");

$page_title = 'Trainer Statistics';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <div>
                <h1><i class="fas fa-chart-pie text-primary"></i> Trainer Statistics</h1>
                <p class="text-muted">Analytics and insights about your trainers</p>
            </div>
            <div>
                <a href="trainers.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Trainers
                </a>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6>Total Trainers</h6>
                    <h2 class="mb-0"><?php echo $stats['total']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6>Active Trainers</h6>
                    <h2 class="mb-0"><?php echo $stats['active']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6>Avg Experience</h6>
                    <h2 class="mb-0"><?php echo round($stats['avg_experience'], 1); ?> yrs</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6>Total Assignments</h6>
                    <h2 class="mb-0"><?php echo $stats['total_trainings_assigned']; ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Top Trainers -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-trophy text-warning"></i> Top Trainers</h5>
                </div>
                <div class="card-body p-0">
                    <?php if ($top_trainers && $top_trainers->num_rows > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php $rank = 1; while ($t = $top_trainers->fetch_assoc()): ?>
                                <div class="list-group-item">
                                    <div class="d-flex align-items-center">
                                        <div class="mr-3" style="font-size: 1.5rem; font-weight: 800; color: #667eea; width: 40px;">
                                            #<?php echo $rank++; ?>
                                        </div>
                                        <img src="<?php echo !empty($t['profile_picture']) ? '../uploads/trainers/' . $t['profile_picture'] : '../assets/images/default-avatar.png'; ?>" 
                                             class="rounded-circle mr-3" width="50" height="50" style="object-fit: cover;"
                                             onerror="this.src='../assets/images/default-avatar.png'">
                                        <div class="flex-grow-1">
                                            <strong><?php echo htmlspecialchars($t['first_name'] . ' ' . $t['last_name']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($t['specialization']); ?></small>
                                        </div>
                                        <div class="text-right">
                                            <span class="badge badge-primary"><?php echo $t['total_trainings']; ?> trainings</span>
                                            <br>
                                            <small class="text-muted"><?php echo $t['experience_years']; ?> yrs</small>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-trophy fa-2x mb-2 d-block"></i>
                            No trainer data available
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Specialization Distribution -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-bar text-primary"></i> Specialization Distribution</h5>
                </div>
                <div class="card-body">
                    <?php if ($specializations && $specializations->num_rows > 0): ?>
                        <?php while ($spec = $specializations->fetch_assoc()): ?>
                            <?php 
                                $percentage = $stats['total'] > 0 ? ($spec['count'] / $stats['total']) * 100 : 0;
                            ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span><?php echo htmlspecialchars($spec['specialization']); ?></span>
                                    <span><strong><?php echo $spec['count']; ?></strong> trainers</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar" style="width: <?php echo $percentage; ?>%;"></div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-chart-bar fa-2x mb-2 d-block"></i>
                            No specialization data
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Trainers -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-clock text-info"></i> Recently Added Trainers</h5>
        </div>
        <div class="card-body p-0">
            <?php if ($recent_trainers && $recent_trainers->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Trainer</th>
                                <th>Specialization</th>
                                <th>Email</th>
                                <th>Joined</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($t = $recent_trainers->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <img src="<?php echo !empty($t['profile_picture']) ? '../uploads/trainers/' . $t['profile_picture'] : '../assets/images/default-avatar.png'; ?>" 
                                             class="rounded-circle mr-2" width="35" height="35" style="object-fit: cover;"
                                             onerror="this.src='../assets/images/default-avatar.png'">
                                        <strong><?php echo htmlspecialchars($t['first_name'] . ' ' . $t['last_name']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge badge-info"><?php echo htmlspecialchars($t['specialization']); ?></span>
                                    </td>
                                    <td><small><?php echo htmlspecialchars($t['email']); ?></small></td>
                                    <td><small><?php echo timeAgo($t['created_at']); ?></small></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $t['status'] == 'active' ? 'success' : 
                                                ($t['status'] == 'inactive' ? 'danger' : 'warning'); 
                                        ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $t['status'])); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center text-muted py-4">No recent trainers</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>