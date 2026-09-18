<?php
// admin/delete-trainer.php - Delete Trainer
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

// Get trainer
$trainer = $conn->query("SELECT * FROM trainers WHERE id = $trainer_id")->fetch_assoc();

if ($trainer) {
    // Delete profile picture
    if (!empty($trainer['profile_picture'])) {
        $file_path = '../uploads/trainers/' . $trainer['profile_picture'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    // Unassign from trainings
    $conn->query("UPDATE training_programs SET trainer_id = NULL WHERE trainer_id = $trainer_id");
    
    // Delete trainer
    $conn->query("DELETE FROM trainers WHERE id = $trainer_id");
    
    logAction($_SESSION['user_id'], 'trainer_deleted', ['trainer_id' => $trainer_id]);
}

header('Location: trainers.php?success=deleted');
exit();
?>