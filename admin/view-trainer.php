<?php
// admin/view-trainer.php - View Trainer Profile
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

$trainer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($trainer_id <= 0) {
    header('Location: trainers.php');
    exit();
}

// Get trainer details
$trainer = $conn->query("SELECT * FROM trainers WHERE id = $trainer_id")->fetch_assoc();

if (!$trainer) {
    header('Location: trainers.php');
    exit();
}

// Get trainer's trainings
$trainings = $conn->query("
    SELECT * FROM training_programs 
    WHERE trainer_id = $trainer_id OR trainer_email = '{$trainer['email']}'
    ORDER BY start_date DESC
");

$trainings_count = $trainings->num_rows;

// Update total_trainings
$conn->query("UPDATE trainers SET total_trainings = $trainings_count WHERE id = $trainer_id");

$page_title = 'View Trainer';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<style>
.trainer-hero {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    border-radius: 20px;
    padding: 40px;
    color: white;
    margin-bottom: 30px;
    position: relative;
    overflow: hidden;
}
.trainer-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 400px;
    height: 400px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(102, 126, 234, 0.2) 0%, transparent 70%);
}
.trainer-hero .trainer-avatar-lg {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    border: 5px solid rgba(255,255,255,0.2);
    position: relative;
    z-index: 1;
}
.trainer-hero h2 {
    font-weight: 800;
    margin-bottom: 5px;
    position: relative;
    z-index: 1;
}
.trainer-hero .specialization {
    color: #667eea;
    font-weight: 600;
    font-size: 1.1rem;
    position: relative;
    z-index: 1;
}
.info-item {
    margin-bottom: 15px;
}
.info-item label {
    display: block;
    font-size: 0.75rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 3px;
    font-weight: 600;
}
.info-item .value {
    font-weight: 600;
    color: #2d3748;
}
.social-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.85rem;
    margin-right: 8px;
    margin-bottom: 8px;
    transition: all 0.3s ease;
}
.social-link.linkedin { background: #0077b5; color: white; }
.social-link.twitter { background: #1da1f2; color: white; }
.social-link.website { background: #48bb78; color: white; }
.social-link:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); color: white; }
</style>

<div class="main-content">
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <div>
                <h1><i class="fas fa-user-tie text-primary"></i> Trainer Profile</h1>
                <p class="text-muted">View trainer details and assigned trainings</p>
            </div>
            <div>
                <a href="edit-trainer.php?id=<?php echo $trainer_id; ?>" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <a href="trainers.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
    </div>

    <!-- Trainer Hero -->
    <div class="trainer-hero">
        <div class="row align-items-center">
            <div class="col-md-3 text-center text-md-left">
                <img src="<?php echo !empty($trainer['profile_picture']) ? '../uploads/trainers/' . $trainer['profile_picture'] : '../assets/images/default-avatar.png'; ?>" 
                     alt="<?php echo htmlspecialchars($trainer['first_name']); ?>" 
                     class="trainer-avatar-lg"
                     onerror="this.src='../assets/images/default-avatar.png'">
            </div>
            <div class="col-md-6 text-center text-md-left">
                <h2><?php echo htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name']); ?></h2>
                <div class="specialization"><?php echo htmlspecialchars($trainer['specialization']); ?></div>
                <p class="mt-3" style="color: rgba(255,255,255,0.7);">
                    <i class="fas fa-award text-warning"></i> 
                    <?php echo $trainer['experience_years']; ?> years experience
                    <span class="mx-2">•</span>
                    <i class="fas fa-chalkboard-teacher text-info"></i> 
                    <?php echo $trainings_count; ?> trainings
                </p>
                <div class="mt-3">
                    <?php if (!empty($trainer['linkedin_url'])): ?>
                        <a href="<?php echo $trainer['linkedin_url']; ?>" target="_blank" class="social-link linkedin">
                            <i class="fab fa-linkedin"></i> LinkedIn
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($trainer['twitter_url'])): ?>
                        <a href="<?php echo $trainer['twitter_url']; ?>" target="_blank" class="social-link twitter">
                            <i class="fab fa-twitter"></i> Twitter
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($trainer['website_url'])): ?>
                        <a href="<?php echo $trainer['website_url']; ?>" target="_blank" class="social-link website">
                            <i class="fas fa-globe"></i> Website
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-3 text-center text-md-right">
                <span class="badge badge-<?php 
                    echo $trainer['status'] == 'active' ? 'success' : 
                        ($trainer['status'] == 'inactive' ? 'danger' : 'warning'); 
                ?>" style="padding: 10px 25px; font-size: 0.9rem;">
                    <i class="fas fa-circle" style="font-size: 8px;"></i>
                    <?php echo ucfirst(str_replace('_', ' ', $trainer['status'])); ?>
                </span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <!-- Contact Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-address-card"></i> Contact Information</h5>
                </div>
                <div class="card-body">
                    <div class="info-item">
                        <label><i class="fas fa-envelope"></i> Email</label>
                        <div class="value"><?php echo htmlspecialchars($trainer['email']); ?></div>
                    </div>
                    <div class="info-item">
                        <label><i class="fas fa-phone"></i> Phone</label>
                        <div class="value"><?php echo htmlspecialchars($trainer['phone'] ?? 'N/A'); ?></div>
                    </div>
                    <?php if (!empty($trainer['address'])): ?>
                        <div class="info-item">
                            <label><i class="fas fa-map-marker-alt"></i> Address</label>
                            <div class="value">
                                <?php echo htmlspecialchars($trainer['address']); ?>
                                <?php if ($trainer['city']): ?>, <?php echo htmlspecialchars($trainer['city']); ?><?php endif; ?>
                                <?php if ($trainer['state']): ?>, <?php echo htmlspecialchars($trainer['state']); ?><?php endif; ?>
                                <?php if ($trainer['country']): ?>, <?php echo htmlspecialchars($trainer['country']); ?><?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="info-item">
                        <label><i class="fas fa-user-graduate"></i> Qualification</label>
                        <div class="value"><?php echo htmlspecialchars($trainer['qualification'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-item">
                        <label><i class="fas fa-star"></i> Experience</label>
                        <div class="value"><?php echo $trainer['experience_years']; ?> years</div>
                    </div>
                    <div class="info-item mb-0">
                        <label><i class="fas fa-calendar-alt"></i> Joined</label>
                        <div class="value"><?php echo formatDate($trainer['created_at']); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Bio -->
            <?php if (!empty($trainer['bio'])): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user"></i> About</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($trainer['bio'])); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="col-lg-8">
            <!-- Assigned Trainings -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-chalkboard-teacher"></i> Assigned Trainings</h5>
                    <span class="badge badge-primary"><?php echo $trainings_count; ?> total</span>
                </div>
                <div class="card-body p-0">
                    <?php if ($trainings && $trainings->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Training</th>
                                        <th>Type</th>
                                        <th>Start Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($training = $trainings->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($training['title']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($training['category'] ?? 'Uncategorized'); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge badge-info">
                                                    <?php echo ucfirst(str_replace('_', ' ', $training['type'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo formatDate($training['start_date']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo $training['status'] == 'upcoming' ? 'warning' : 
                                                        ($training['status'] == 'ongoing' ? 'success' : 
                                                        ($training['status'] == 'completed' ? 'info' : 'danger')); 
                                                ?>">
                                                    <?php echo ucfirst($training['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="view-training.php?id=<?php echo $training['id']; ?>" 
                                                   class="btn btn-sm btn-outline-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-chalkboard-teacher fa-3x mb-3 d-block"></i>
                            <h5>No trainings assigned</h5>
                            <p>This trainer has no assigned trainings yet.</p>
                            <a href="add-training.php" class="btn btn-primary">
                                <i class="fas fa-plus-circle"></i> Add Training
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>