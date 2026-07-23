<?php
// admin/training-types.php - Manage Training Types
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Get training types with counts
$types = $conn->query("
    SELECT 
        type,
        COUNT(*) as total,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'upcoming' THEN 1 ELSE 0 END) as upcoming,
        SUM(CASE WHEN status = 'ongoing' THEN 1 ELSE 0 END) as ongoing
    FROM training_programs
    GROUP BY type
    ORDER BY total DESC
");

$page_title = 'Training Types';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-tags text-primary"></i> Training Types</h1>
            <p class="text-muted">Overview of all training types and their statistics</p>
        </div>
        <div>
            <a href="add-training.php" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Add Training
            </a>
        </div>
    </div>

    <div class="row">
        <?php if ($types && $types->num_rows > 0): ?>
            <?php while ($type = $types->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <span class="badge badge-<?php 
                                    echo $type['type'] == 'technical' ? 'primary' : 
                                        ($type['type'] == 'soft_skill' ? 'success' : 
                                        ($type['type'] == 'management' ? 'info' : 
                                        ($type['type'] == 'compliance' ? 'warning' : 'secondary'))); 
                                ?> badge-lg">
                                    <?php echo ucfirst(str_replace('_', ' ', $type['type'])); ?>
                                </span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6">
                                    <h2 class="text-primary"><?php echo $type['total']; ?></h2>
                                    <small class="text-muted">Total Trainings</small>
                                </div>
                                <div class="col-6">
                                    <h2 class="text-success"><?php echo $type['completed']; ?></h2>
                                    <small class="text-muted">Completed</small>
                                </div>
                            </div>
                            <div class="row text-center mt-3">
                                <div class="col-6">
                                    <h5 class="text-warning"><?php echo $type['upcoming']; ?></h5>
                                    <small class="text-muted">Upcoming</small>
                                </div>
                                <div class="col-6">
                                    <h5 class="text-info"><?php echo $type['ongoing']; ?></h5>
                                    <small class="text-muted">Ongoing</small>
                                </div>
                            </div>
                            <div class="mt-3">
                                <div class="progress" style="height: 8px;">
                                    <?php 
                                    $completed_percent = $type['total'] > 0 ? ($type['completed'] / $type['total']) * 100 : 0;
                                    $upcoming_percent = $type['total'] > 0 ? ($type['upcoming'] / $type['total']) * 100 : 0;
                                    $ongoing_percent = $type['total'] > 0 ? ($type['ongoing'] / $type['total']) * 100 : 0;
                                    ?>
                                    <div class="progress-bar bg-success" style="width: <?php echo $completed_percent; ?>%"></div>
                                    <div class="progress-bar bg-warning" style="width: <?php echo $upcoming_percent; ?>%"></div>
                                    <div class="progress-bar bg-info" style="width: <?php echo $ongoing_percent; ?>%"></div>
                                </div>
                                <div class="d-flex justify-content-between small text-muted mt-1">
                                    <span>Completed (<?php echo round($completed_percent); ?>%)</span>
                                    <span>Upcoming (<?php echo round($upcoming_percent); ?>%)</span>
                                    <span>Ongoing (<?php echo round($ongoing_percent); ?>%)</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent">
                            <a href="trainings.php?type=<?php echo $type['type']; ?>" 
                               class="btn btn-outline-primary btn-block">
                                <i class="fas fa-eye"></i> View Trainings
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="text-center text-muted py-5">
                    <i class="fas fa-tags fa-4x mb-3 d-block"></i>
                    <h4>No training types found</h4>
                    <p>Create your first training program to get started.</p>
                    <a href="add-training.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> Add Training
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Training Type Descriptions -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-info-circle"></i> Training Type Descriptions</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="d-flex mb-3">
                        <span class="badge badge-primary badge-lg mr-3">Technical</span>
                        <div>
                            <strong>Technical Skills</strong>
                            <p class="text-muted small mb-0">Training focused on technical skills, programming, IT, engineering, and specific job-related technical competencies.</p>
                        </div>
                    </div>
                    <div class="d-flex mb-3">
                        <span class="badge badge-success badge-lg mr-3">Soft Skills</span>
                        <div>
                            <strong>Soft Skills</strong>
                            <p class="text-muted small mb-0">Training focused on interpersonal skills, communication, leadership, teamwork, and emotional intelligence.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex mb-3">
                        <span class="badge badge-info badge-lg mr-3">Management</span>
                        <div>
                            <strong>Management</strong>
                            <p class="text-muted small mb-0">Training focused on management skills, project management, strategic planning, and organizational leadership.</p>
                        </div>
                    </div>
                    <div class="d-flex mb-3">
                        <span class="badge badge-warning badge-lg mr-3">Compliance</span>
                        <div>
                            <strong>Compliance</strong>
                            <p class="text-muted small mb-0">Training focused on regulatory requirements, safety standards, legal compliance, and industry certifications.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>