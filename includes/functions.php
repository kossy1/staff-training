<?php
// includes/functions.php - Complete Functions File

// ============================================
// DATABASE FUNCTIONS
// ============================================

/**
 * Get database connection
 * @return mysqli
 */
function getDB() {
    global $conn;
    return $conn;
}

/**
 * Escape string for database
 * @param string $string
 * @return string
 */
function escapeString($string) {
    global $conn;
    return $conn->real_escape_string($string);
}

/**
 * Get last inserted ID
 * @return int
 */
function getLastInsertId() {
    global $conn;
    return $conn->insert_id;
}

// ============================================
// USER FUNCTIONS
// ============================================

/**
 * Get employee name by ID
 * @param int $employeeId
 * @return string
 */
function getEmployeeName($employeeId) {
    global $conn;
    $stmt = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) as name FROM employees WHERE id = ?");
    $stmt->bind_param("i", $employeeId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['name'];
    }
    return 'Unknown';
}

/**
 * Get employee by ID
 * @param int $employeeId
 * @return array|null
 */
function getEmployeeById($employeeId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->bind_param("i", $employeeId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Get user by ID
 * @param int $userId
 * @return array|null
 */
function getUserById($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Get user by email
 * @param string $email
 * @return array|null
 */
function getUserByEmail($email) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// ============================================
// TRAINER FUNCTIONS
// ============================================

/**
 * Get trainer by ID
 * @param int $trainerId
 * @return array|null
 */
function getTrainerById($trainerId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM trainers WHERE id = ?");
    $stmt->bind_param("i", $trainerId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Get trainer by email
 * @param string $email
 * @return array|null
 */
function getTrainerByEmail($email) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM trainers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Get trainer by user ID
 * @param int $userId
 * @return array|null
 */
function getTrainerByUserId($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM trainers WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Get trainer name by ID
 * @param int $trainerId
 * @return string
 */
function getTrainerName($trainerId) {
    global $conn;
    $stmt = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) as name FROM trainers WHERE id = ?");
    $stmt->bind_param("i", $trainerId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['name'];
    }
    return 'Unknown Trainer';
}

/**
 * Get all active trainers
 * @return mysqli_result
 */
function getActiveTrainers() {
    global $conn;
    return $conn->query("SELECT * FROM trainers WHERE status = 'active' ORDER BY first_name ASC");
}

/**
 * Get trainer statistics
 * @param int $trainerId
 * @return array
 */
function getTrainerStats($trainerId) {
    global $conn;
    $stats = [];
    
    // Total trainings
    $result = $conn->query("SELECT COUNT(*) as count FROM training_programs WHERE trainer_id = $trainerId");
    $stats['total_trainings'] = $result->fetch_assoc()['count'] ?? 0;
    
    // Active trainings
    $result = $conn->query("SELECT COUNT(*) as count FROM training_programs WHERE trainer_id = $trainerId AND status = 'ongoing'");
    $stats['active_trainings'] = $result->fetch_assoc()['count'] ?? 0;
    
    // Completed trainings
    $result = $conn->query("SELECT COUNT(*) as count FROM training_programs WHERE trainer_id = $trainerId AND status = 'completed'");
    $stats['completed_trainings'] = $result->fetch_assoc()['count'] ?? 0;
    
    // Total students
    $result = $conn->query("
        SELECT COUNT(DISTINCT et.employee_id) as count 
        FROM employee_trainings et
        JOIN training_programs tp ON et.training_id = tp.id
        WHERE tp.trainer_id = $trainerId
    ");
    $stats['total_students'] = $result->fetch_assoc()['count'] ?? 0;
    
    // Upcoming sessions
    $result = $conn->query("SELECT COUNT(*) as count FROM trainer_sessions WHERE trainer_id = $trainerId AND status = 'scheduled' AND session_date >= NOW()");
    $stats['upcoming_sessions'] = $result->fetch_assoc()['count'] ?? 0;
    
    // Pending requests
    $result = $conn->query("SELECT COUNT(*) as count FROM trainer_requests WHERE trainer_id = $trainerId AND status = 'pending'");
    $stats['pending_requests'] = $result->fetch_assoc()['count'] ?? 0;
    
    return $stats;
}

/**
 * Get trainer's assigned trainings
 * @param int $trainerId
 * @param string|null $status
 * @return mysqli_result
 */
function getTrainerTrainings($trainerId, $status = null) {
    global $conn;
    
    $sql = "
        SELECT tp.*,
               (SELECT COUNT(*) FROM employee_trainings WHERE training_id = tp.id) as enrolled_count,
               (SELECT COUNT(*) FROM employee_trainings WHERE training_id = tp.id AND status = 'completed') as completed_count
        FROM training_programs tp
        WHERE tp.trainer_id = ?
    ";
    
    if ($status) {
        $sql .= " AND tp.status = ?";
    }
    
    $sql .= " ORDER BY tp.start_date DESC";
    
    $stmt = $conn->prepare($sql);
    if ($status) {
        $stmt->bind_param("is", $trainerId, $status);
    } else {
        $stmt->bind_param("i", $trainerId);
    }
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Get trainer's students
 * @param int $trainerId
 * @return mysqli_result
 */
function getTrainerStudents($trainerId) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT DISTINCT e.id, e.first_name, e.last_name, e.email, e.phone,
               e.department, e.position, e.profile_picture,
               COUNT(et.id) as training_count,
               SUM(CASE WHEN et.status = 'completed' THEN 1 ELSE 0 END) as completed_count
        FROM employee_trainings et
        JOIN employees e ON et.employee_id = e.id
        JOIN training_programs tp ON et.training_id = tp.id
        WHERE tp.trainer_id = ?
        GROUP BY e.id
        ORDER BY e.first_name ASC
    ");
    $stmt->bind_param("i", $trainerId);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Get trainer's requests
 * @param int $trainerId
 * @param string|null $status
 * @return mysqli_result
 */
function getTrainerRequests($trainerId, $status = null) {
    global $conn;
    
    $sql = "
        SELECT tr.*, 
               CONCAT(e.first_name, ' ', e.last_name) as employee_name,
               e.email as employee_email,
               tp.title as training_title
        FROM trainer_requests tr
        LEFT JOIN employees e ON tr.employee_id = e.id
        LEFT JOIN training_programs tp ON tr.training_id = tp.id
        WHERE tr.trainer_id = ?
    ";
    
    if ($status) {
        $sql .= " AND tr.status = ?";
    }
    
    $sql .= " ORDER BY 
        CASE tr.priority 
            WHEN 'urgent' THEN 1 
            WHEN 'high' THEN 2 
            WHEN 'medium' THEN 3 
            ELSE 4 
        END, 
        tr.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    if ($status) {
        $stmt->bind_param("is", $trainerId, $status);
    } else {
        $stmt->bind_param("i", $trainerId);
    }
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Get trainer's sessions
 * @param int $trainerId
 * @param string|null $status
 * @return mysqli_result
 */
function getTrainerSessions($trainerId, $status = null) {
    global $conn;
    
    $sql = "
        SELECT ts.*, tp.title as training_title
        FROM trainer_sessions ts
        LEFT JOIN training_programs tp ON ts.training_id = tp.id
        WHERE ts.trainer_id = ?
    ";
    
    if ($status) {
        $sql .= " AND ts.status = ?";
    }
    
    $sql .= " ORDER BY ts.session_date DESC";
    
    $stmt = $conn->prepare($sql);
    if ($status) {
        $stmt->bind_param("is", $trainerId, $status);
    } else {
        $stmt->bind_param("i", $trainerId);
    }
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Get trainer's departments
 * @param int $trainerId
 * @return mysqli_result
 */
function getTrainerDepartments($trainerId) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT e.department, 
               COUNT(DISTINCT e.id) as student_count,
               COUNT(DISTINCT tp.id) as training_count
        FROM employee_trainings et
        JOIN employees e ON et.employee_id = e.id
        JOIN training_programs tp ON et.training_id = tp.id
        WHERE tp.trainer_id = ? 
          AND e.department IS NOT NULL 
          AND e.department != ''
        GROUP BY e.department
        ORDER BY student_count DESC
    ");
    $stmt->bind_param("i", $trainerId);
    $stmt->execute();
    return $stmt->get_result();
}

// ============================================
// LOGGING FUNCTIONS
// ============================================

/**
 * Log user action with foreign key handling
 * @param int $user_id
 * @param string $action
 * @param array|null $details
 * @return bool
 */
function logAction($user_id, $action, $details = null) {
    global $conn;
    
    // If user_id is 0 or null, use NULL
    if (empty($user_id) || $user_id <= 0) {
        $user_id = null;
    } else {
        // Check if user exists
        $check = $conn->prepare("SELECT id FROM users WHERE id = ?");
        $check->bind_param("i", $user_id);
        $check->execute();
        $result = $check->get_result();
        
        // If user doesn't exist, set to null
        if ($result->num_rows == 0) {
            $user_id = null;
        }
    }
    
    $detailsJson = $details ? json_encode($details) : null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    try {
        if ($user_id === null) {
            $stmt = $conn->prepare("INSERT INTO system_logs (user_id, action, details, ip_address) VALUES (NULL, ?, ?, ?)");
            $stmt->bind_param("sss", $action, $detailsJson, $ip);
        } else {
            $stmt = $conn->prepare("INSERT INTO system_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $user_id, $action, $detailsJson, $ip);
        }
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Log action failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get recent activity logs
 * @param int $limit
 * @return mysqli_result
 */
function getRecentActivities($limit = 10) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT l.*, u.username 
        FROM system_logs l 
        LEFT JOIN users u ON l.user_id = u.id 
        ORDER BY l.created_at DESC 
        LIMIT ?
    ");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    return $stmt->get_result();
}

// ============================================
// TRAINING FUNCTIONS
// ============================================

/**
 * Get training status
 * @param int $trainingId
 * @return string|null
 */
function getTrainingStatus($trainingId) {
    global $conn;
    $stmt = $conn->prepare("SELECT status FROM training_programs WHERE id = ?");
    $stmt->bind_param("i", $trainingId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['status'];
    }
    return null;
}

/**
 * Get training by ID
 * @param int $trainingId
 * @return array|null
 */
function getTrainingById($trainingId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM training_programs WHERE id = ?");
    $stmt->bind_param("i", $trainingId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Get all trainings
 * @param string|null $status
 * @param int|null $trainer_id
 * @return mysqli_result
 */
function getAllTrainings($status = null, $trainer_id = null) {
    global $conn;
    
    $sql = "SELECT tp.*, 
            CONCAT(t.first_name, ' ', t.last_name) as trainer_name,
            (SELECT COUNT(*) FROM employee_trainings WHERE training_id = tp.id) as enrolled_count
            FROM training_programs tp
            LEFT JOIN trainers t ON tp.trainer_id = t.id
            WHERE 1=1";
    
    $params = [];
    $types = "";
    
    if ($status) {
        $sql .= " AND tp.status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    if ($trainer_id) {
        $sql .= " AND tp.trainer_id = ?";
        $params[] = $trainer_id;
        $types .= "i";
    }
    
    $sql .= " ORDER BY tp.created_at DESC";
    
    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result();
    }
    
    return $conn->query($sql);
}

/**
 * Calculate training progress for employee
 * @param int $employeeId
 * @param int $trainingId
 * @return float
 */
function calculateTrainingProgress($employeeId, $trainingId) {
    global $conn;
    $stmt = $conn->prepare("SELECT progress FROM employee_trainings WHERE employee_id = ? AND training_id = ?");
    $stmt->bind_param("ii", $employeeId, $trainingId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return (float)$row['progress'];
    }
    return 0;
}

/**
 * Get training enrollment statistics
 * @param int $trainingId
 * @return array
 */
function getTrainingEnrollmentStats($trainingId) {
    global $conn;
    $stats = [];
    
    $result = $conn->query("SELECT COUNT(*) as total FROM employee_trainings WHERE training_id = $trainingId");
    $stats['total'] = $result->fetch_assoc()['total'] ?? 0;
    
    $result = $conn->query("SELECT COUNT(*) as count FROM employee_trainings WHERE training_id = $trainingId AND status = 'enrolled'");
    $stats['enrolled'] = $result->fetch_assoc()['count'] ?? 0;
    
    $result = $conn->query("SELECT COUNT(*) as count FROM employee_trainings WHERE training_id = $trainingId AND status = 'in_progress'");
    $stats['in_progress'] = $result->fetch_assoc()['count'] ?? 0;
    
    $result = $conn->query("SELECT COUNT(*) as count FROM employee_trainings WHERE training_id = $trainingId AND status = 'completed'");
    $stats['completed'] = $result->fetch_assoc()['count'] ?? 0;
    
    $result = $conn->query("SELECT COUNT(*) as count FROM employee_trainings WHERE training_id = $trainingId AND status = 'dropped'");
    $stats['dropped'] = $result->fetch_assoc()['count'] ?? 0;
    
    return $stats;
}

// ============================================
// CERTIFICATION FUNCTIONS
// ============================================

/**
 * Get certification by ID
 * @param int $certId
 * @return array|null
 */
function getCertificationById($certId) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT c.*, 
               CONCAT(e.first_name, ' ', e.last_name) as employee_name,
               e.email as employee_email,
               tp.title as training_title
        FROM certifications c
        LEFT JOIN employees e ON c.employee_id = e.id
        LEFT JOIN training_programs tp ON c.training_id = tp.id
        WHERE c.id = ?
    ");
    $stmt->bind_param("i", $certId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Get employee certifications
 * @param int $employeeId
 * @return mysqli_result
 */
function getEmployeeCertifications($employeeId) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT c.*, tp.title as training_title
        FROM certifications c
        LEFT JOIN training_programs tp ON c.training_id = tp.id
        WHERE c.employee_id = ?
        ORDER BY c.issue_date DESC
    ");
    $stmt->bind_param("i", $employeeId);
    $stmt->execute();
    return $stmt->get_result();
}

// ============================================
// DATE FUNCTIONS
// ============================================

/**
 * Format date
 * @param string $date
 * @param string $format
 * @return string
 */
function formatDate($date, $format = 'M d, Y') {
    if (!$date || $date == '0000-00-00' || $date == '0000-00-00 00:00:00') {
        return 'N/A';
    }
    return date($format, strtotime($date));
}

/**
 * Format datetime
 * @param string $datetime
 * @param string $format
 * @return string
 */
function formatDateTime($datetime, $format = 'M d, Y H:i') {
    if (!$datetime) {
        return 'N/A';
    }
    return date($format, strtotime($datetime));
}

/**
 * Get time ago string
 * @param string $datetime
 * @return string
 */
function timeAgo($datetime) {
    if (!$datetime) {
        return 'N/A';
    }
    
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 2592000) {
        $weeks = floor($diff / 604800);
        return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 31536000) {
        $months = floor($diff / 2592000);
        return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
    }
    $years = floor($diff / 31536000);
    return $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
}

/**
 * Calculate days between dates
 * @param string $start
 * @param string $end
 * @return int
 */
function daysBetween($start, $end) {
    $start = strtotime($start);
    $end = strtotime($end);
    return floor(abs($end - $start) / 86400);
}

// ============================================
// PERCENTAGE FUNCTIONS
// ============================================

/**
 * Calculate percentage
 * @param float $part
 * @param float $total
 * @return float
 */
function getPercentage($part, $total) {
    if ($total == 0) return 0;
    return round(($part / $total) * 100, 2);
}

// ============================================
// STRING FUNCTIONS
// ============================================

/**
 * Sanitize input
 * @param string $data
 * @return string
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Generate unique employee code
 * @return string
 */
function generateUniqueCode() {
    return 'EMP' . date('Ymd') . rand(1000, 9999);
}

/**
 * Generate unique trainer code
 * @return string
 */
function generateTrainerCode() {
    return 'TRN' . date('Ymd') . rand(1000, 9999);
}

/**
 * Generate slug from string
 * @param string $string
 * @return string
 */
function generateSlug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

/**
 * Truncate text
 * @param string $text
 * @param int $length
 * @param string $suffix
 * @return string
 */
function truncateText($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $suffix;
}

/**
 * Get initials from name
 * @param string $name
 * @return string
 */
function getInitials($name) {
    $words = explode(' ', trim($name));
    $initials = '';
    foreach ($words as $word) {
        if (!empty($word)) {
            $initials .= strtoupper($word[0]);
        }
    }
    return substr($initials, 0, 2);
}

/**
 * Format file size
 * @param int $bytes
 * @return string
 */
function formatFileSize($bytes) {
    if ($bytes === 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

// ============================================
// NOTIFICATION FUNCTIONS
// ============================================

/**
 * Get unread notifications for user
 * @param int $userId
 * @param int $limit
 * @return mysqli_result
 */
function getNotifications($userId, $limit = 5) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->bind_param("ii", $userId, $limit);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Get unread notification count
 * @param int $userId
 * @return int
 */
function getUnreadNotificationCount($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['count'] ?? 0;
}

/**
 * Create notification
 * @param int $userId
 * @param string $title
 * @param string $message
 * @param string $type
 * @param string|null $link
 * @return bool
 */
function createNotification($userId, $title, $message, $type = 'info', $link = null) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $userId, $title, $message, $type, $link);
    return $stmt->execute();
}

/**
 * Mark notification as read
 * @param int $notificationId
 * @return bool
 */
function markNotificationRead($notificationId) {
    global $conn;
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    $stmt->bind_param("i", $notificationId);
    return $stmt->execute();
}

/**
 * Mark all notifications as read
 * @param int $userId
 * @return bool
 */
function markAllNotificationsRead($userId) {
    global $conn;
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    return $stmt->execute();
}

// ============================================
// STATUS FUNCTIONS
// ============================================

/**
 * Get status badge class
 * @param string $status
 * @return string
 */
function getStatusBadgeClass($status) {
    $map = [
        'active' => 'success',
        'inactive' => 'danger',
        'pending' => 'warning',
        'completed' => 'success',
        'cancelled' => 'danger',
        'ongoing' => 'info',
        'upcoming' => 'primary',
        'enrolled' => 'info',
        'in_progress' => 'warning',
        'dropped' => 'danger',
        'on_leave' => 'warning',
        'expired' => 'danger',
        'revoked' => 'danger',
        'paid' => 'success',
        'unpaid' => 'danger',
        'failed' => 'danger',
        'scheduled' => 'primary',
        'approved' => 'success',
        'rejected' => 'danger',
        'resolved' => 'info',
        'not_started' => 'secondary',
        'delayed' => 'warning',
        'urgent' => 'danger',
        'high' => 'warning',
        'medium' => 'info',
        'low' => 'secondary',
        'success' => 'success',
        'refunded' => 'secondary'
    ];
    return $map[$status] ?? 'secondary';
}

/**
 * Get status label
 * @param string $status
 * @return string
 */
function getStatusLabel($status) {
    return ucwords(str_replace('_', ' ', $status));
}

/**
 * Get priority badge class
 * @param string $priority
 * @return string
 */
function getPriorityBadgeClass($priority) {
    $map = [
        'urgent' => 'danger',
        'high' => 'warning',
        'medium' => 'info',
        'low' => 'secondary'
    ];
    return $map[$priority] ?? 'secondary';
}

// ============================================
// DASHBOARD FUNCTIONS
// ============================================

/**
 * Get dashboard statistics
 * @return array
 */
function getDashboardStats() {
    global $conn;
    $stats = [];
    
    $result = $conn->query("SELECT COUNT(*) as total FROM employees WHERE status = 'active'");
    $stats['total_employees'] = $result->fetch_assoc()['total'] ?? 0;
    
    $result = $conn->query("SELECT COUNT(*) as total FROM training_programs");
    $stats['total_trainings'] = $result->fetch_assoc()['total'] ?? 0;
    
    $result = $conn->query("SELECT COUNT(*) as total FROM training_programs WHERE status = 'ongoing'");
    $stats['ongoing_trainings'] = $result->fetch_assoc()['total'] ?? 0;
    
    $result = $conn->query("SELECT COUNT(*) as total FROM training_programs WHERE status = 'completed'");
    $stats['completed_trainings'] = $result->fetch_assoc()['total'] ?? 0;
    
    $result = $conn->query("SELECT COUNT(*) as total FROM certifications");
    $stats['certifications'] = $result->fetch_assoc()['total'] ?? 0;
    
    // Trainers count
    $result = $conn->query("SELECT COUNT(*) as total FROM trainers WHERE status = 'active'");
    $stats['total_trainers'] = $result->fetch_assoc()['total'] ?? 0;
    
    // Enrollments
    $result = $conn->query("SELECT COUNT(*) as total FROM employee_trainings");
    $stats['total_enrollments'] = $result->fetch_assoc()['total'] ?? 0;
    
    return $stats;
}

/**
 * Get payment statistics
 * @return array
 */
function getPaymentStats() {
    global $conn;
    $stats = [
        'total_payments' => 0,
        'successful_payments' => 0,
        'pending_payments' => 0,
        'failed_payments' => 0,
        'total_revenue' => 0,
        'avg_payment' => 0
    ];
    
    $table_check = $conn->query("SHOW TABLES LIKE 'payments'");
    if ($table_check && $table_check->num_rows > 0) {
        $result = $conn->query("
            SELECT 
                COUNT(*) as total_payments,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful_payments,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_payments,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_payments,
                SUM(CASE WHEN status = 'success' THEN amount ELSE 0 END) as total_revenue,
                AVG(CASE WHEN status = 'success' THEN amount ELSE NULL END) as avg_payment
            FROM payments
        ");
        if ($result) {
            $row = $result->fetch_assoc();
            if ($row) {
                $stats = array_merge($stats, $row);
            }
        }
    }
    
    return $stats;
}

// ============================================
// REDIRECT FUNCTIONS
// ============================================

/**
 * Redirect to URL
 * @param string $url
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}


// ============================================
// EMAIL FUNCTIONS
// ============================================

/**
 * Send email
 * @param string $to
 * @param string $subject
 * @param string $message
 * @param string $from
 * @return bool
 */
function sendEmail($to, $subject, $message, $from = null) {
    if (!$from) {
        $from = SITE_NAME . ' <noreply@' . $_SERVER['HTTP_HOST'] . '>';
    }
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: ' . $from . "\r\n";
    $headers .= 'Reply-To: ' . $from . "\r\n";
    
    return @mail($to, $subject, $message, $headers);
}

// ============================================
// FILE FUNCTIONS
// ============================================

/**
 * Upload file
 * @param array $file
 * @param string $target_dir
 * @param array $allowed_types
 * @param int $max_size
 * @return array
 */
function uploadFile($file, $target_dir, $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'pdf'], $max_size = 5242880) {
    $errors = [];
    $uploaded_file = '';
    
    if ($file['error'] != UPLOAD_ERR_OK) {
        $errors[] = "Upload error: " . $file['error'];
        return ['success' => false, 'errors' => $errors];
    }
    
    $filename = $file['name'];
    $tmp_name = $file['tmp_name'];
    $file_size = $file['size'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed_types)) {
        $errors[] = "Invalid file type. Allowed: " . implode(', ', $allowed_types);
    }
    
    if ($file_size > $max_size) {
        $errors[] = "File is too large. Maximum size: " . ($max_size / 1024 / 1024) . "MB";
    }
    
    if (empty($errors)) {
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $new_filename = 'file_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        $upload_path = $target_dir . $new_filename;
        
        if (move_uploaded_file($tmp_name, $upload_path)) {
            $uploaded_file = $new_filename;
        } else {
            $errors[] = "Failed to move uploaded file";
        }
    }
    
    return [
        'success' => empty($errors),
        'errors' => $errors,
        'filename' => $uploaded_file,
        'original_name' => $filename,
        'size' => $file_size,
        'extension' => $ext
    ];
}

// ============================================
// VALIDATION FUNCTIONS
// ============================================

/**
 * Validate email
 * @param string $email
 * @return bool
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number
 * @param string $phone
 * @return bool
 */
function isValidPhone($phone) {
    return preg_match('/^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/', $phone);
}

/**
 * Validate date
 * @param string $date
 * @param string $format
 * @return bool
 */
function isValidDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}



// ============================================
// ASSET FUNCTIONS
// ============================================

/**
 * Get profile picture URL
 * @param string|null $filename
 * @param string $type
 * @param string $base_path
 * @return string
 */
function getProfilePicture($filename, $type = 'employee', $base_path = '../') {
    $default = $base_path . 'assets/images/default-avatar.png';
    
    if (empty($filename)) {
        return $default;
    }
    
    switch ($type) {
        case 'trainer':
            $path = $base_path . 'uploads/trainers/' . $filename;
            break;
        case 'employee':
        default:
            $path = $base_path . 'uploads/profile-pictures/' . $filename;
            break;
    }
    
    return file_exists($path) ? $path : $default;
}

/**
 * Get avatar initials or image
 * @param array $user
 * @param string $type
 * @param string $base_path
 * @return string
 */
function getAvatar($user, $type = 'employee', $base_path = '../') {
    if (!empty($user['profile_picture'])) {
        return '<img src="' . getProfilePicture($user['profile_picture'], $type, $base_path) . '" alt="Avatar" class="rounded-circle" onerror="this.src=\'' . $base_path . 'assets/images/default-avatar.png\'">';
    }
    
    $name = ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '');
    $initials = getInitials($name);
    
    return '<div class="avatar-initials">' . $initials . '</div>';
}

// ============================================
// SECURITY FUNCTIONS
// ============================================

/**
 * Generate CSRF token
 * @return string
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token
 * @return bool
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate random password
 * @param int $length
 * @return string
 */
function generatePassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    $max = strlen($chars) - 1;
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, $max)];
    }
    
    return $password;
}

// ============================================
// JSON RESPONSE FUNCTIONS
// ============================================

/**
 * Send JSON response
 * @param array $data
 * @param int $status_code
 */
function jsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

/**
 * Send success JSON response
 * @param mixed $data
 * @param string $message
 */
function jsonSuccess($data = null, $message = 'Success') {
    jsonResponse([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
}

/**
 * Send error JSON response
 * @param string $message
 * @param int $status_code
 */
function jsonError($message = 'Error', $status_code = 400) {
    jsonResponse([
        'success' => false,
        'message' => $message
    ], $status_code);
}

?>