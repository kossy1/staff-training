<?php
// trainer/sessions.php - Manage Training Sessions
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'trainer') {
    header('Location: ../login.php');
    exit();
}

$trainer_id = $_SESSION['trainer_id'];
$message = '';

// Add session
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_session'])) {
    $training_id = (int)$_POST['training_id'];
    $session_date = $_POST['session_date'];
    $duration = (int)$_POST['duration_hours'];
    $location = trim($_POST['location']);
    $topic = trim($_POST['topic']);
    $notes = trim($_POST['notes']);
    
    $stmt = $conn->prepare("
        INSERT INTO trainer_sessions (trainer_id, training_id, session_date, duration_hours, location, topic, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iisiss s", $trainer_id, $training_id, $session_date, $duration, $location, $topic, $notes);
    
    if ($stmt->execute()) {
        $message = "Session added successfully!";
    }
}

// Get sessions
$sessions = $conn->query("
    SELECT ts.*, tp.title as training_title
    FROM trainer_sessions ts
    LEFT JOIN training_programs tp ON ts.training_id = tp.id
    WHERE ts.trainer_id = $trainer_id
    ORDER BY ts.session_date DESC
");

// Get trainer's trainings for dropdown
$my_trainings = $conn->query("SELECT id, title FROM training_programs WHERE trainer_id = $trainer_id ORDER BY title");

$page_title = 'Training Sessions';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1><i class="fas fa-calendar-check text-primary"></i> Training Sessions</h1>
                <p class="text-muted">Schedule and manage your training sessions</p>
            </div>
            <button class="btn btn-primary" data-toggle="modal" data-target="#addSessionModal">
                <i class="fas fa-plus-circle"></i> Add Session
            </button>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Sessions Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Session</th>
                            <th>Training</th>
                            <th>Date & Time</th>
                            <th>Duration</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($sessions && $sessions->num_rows > 0): ?>
                            <?php while ($s = $sessions->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($s['topic'] ?? 'Session'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($s['training_title']); ?></td>
                                    <td><?php echo formatDateTime($s['session_date']); ?></td>
                                    <td><?php echo $s['duration_hours']; ?> hours</td>
                                    <td><?php echo htmlspecialchars($s['location'] ?? 'TBD'); ?></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $s['status'] == 'scheduled' ? 'primary' : 
                                                ($s['status'] == 'completed' ? 'success' : 'danger'); 
                                        ?>">
                                            <?php echo ucfirst($s['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="fas fa-calendar fa-3x mb-3 d-block" style="opacity: 0.3;"></i>
                                    <h5>No sessions scheduled</h5>
                                    <button class="btn btn-primary" data-toggle="modal" data-target="#addSessionModal">
                                        <i class="fas fa-plus-circle"></i> Add First Session
                                    </button>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Session Modal -->
<div class="modal fade" id="addSessionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Add Training Session</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Training</label>
                        <select name="training_id" class="form-control" required>
                            <option value="">Select Training</option>
                            <?php while ($t = $my_trainings->fetch_assoc()): ?>
                                <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['title']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Session Date & Time</label>
                        <input type="datetime-local" name="session_date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Duration (Hours)</label>
                        <input type="number" name="duration_hours" class="form-control" value="2" min="1" required>
                    </div>
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" class="form-control" placeholder="Room, Online, etc.">
                    </div>
                    <div class="form-group">
                        <label>Topic</label>
                        <input type="text" name="topic" class="form-control" placeholder="Session topic">
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_session" class="btn btn-primary">Add Session</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>