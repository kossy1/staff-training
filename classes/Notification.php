<?php
// classes/Notification.php - Notification Management Class
class Notification {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function create($userId, $title, $message, $type = 'info', $link = null) {
        $stmt = $this->db->prepare("
            INSERT INTO notifications (user_id, title, message, type, link) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("issss", $userId, $title, $message, $type, $link);
        return $stmt->execute();
    }
    
    public function getByUser($userId, $limit = 10) {
        $stmt = $this->db->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->bind_param("ii", $userId, $limit);
        $stmt->execute();
        return $stmt->get_result();
    }
    
    public function getUnreadCount($userId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count FROM notifications 
            WHERE user_id = ? AND is_read = 0
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['count'];
    }
    
    public function markAsRead($id) {
        $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    public function markAllAsRead($userId) {
        $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        return $stmt->execute();
    }
    
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM notifications WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    public function deleteAll($userId) {
        $stmt = $this->db->prepare("DELETE FROM notifications WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        return $stmt->execute();
    }
    
    public function sendBulk($userIds, $title, $message, $type = 'info') {
        $success = 0;
        foreach ($userIds as $userId) {
            if ($this->create($userId, $title, $message, $type)) {
                $success++;
            }
        }
        return $success;
    }
}
?>