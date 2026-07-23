<?php
// classes/Certification.php - Certification Management Class
class Certification {
    private $db;
    private $id;
    private $data;
    
    public function __construct($id = null) {
        $this->db = Database::getInstance()->getConnection();
        if ($id) {
            $this->id = $id;
            $this->load();
        }
    }
    
    private function load() {
        $stmt = $this->db->prepare("SELECT * FROM certifications WHERE id = ?");
        $stmt->bind_param("i", $this->id);
        $stmt->execute();
        $result = $stmt->get_result();
        $this->data = $result->fetch_assoc();
    }
    
    public function create($data) {
        $cert_number = $data['certification_number'] ?? 'CERT' . date('Ymd') . rand(1000, 9999);
        
        $stmt = $this->db->prepare("
            INSERT INTO certifications (
                employee_id, training_id, certification_name, issuing_authority,
                issue_date, expiry_date, certification_number, file_path, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "iisssssss",
            $data['employee_id'],
            $data['training_id'],
            $data['certification_name'],
            $data['issuing_authority'],
            $data['issue_date'],
            $data['expiry_date'],
            $cert_number,
            $data['file_path'],
            $data['status']
        );
        
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }
    
    public function getByEmployee($employeeId) {
        $stmt = $this->db->prepare("
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
    
    public function getActiveByEmployee($employeeId) {
        $stmt = $this->db->prepare("
            SELECT * FROM certifications 
            WHERE employee_id = ? AND status = 'active'
            ORDER BY issue_date DESC
        ");
        $stmt->bind_param("i", $employeeId);
        $stmt->execute();
        return $stmt->get_result();
    }
    
    public function getAll() {
        return $this->db->query("
            SELECT c.*, 
                   CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                   tp.title as training_title
            FROM certifications c
            LEFT JOIN employees e ON c.employee_id = e.id
            LEFT JOIN training_programs tp ON c.training_id = tp.id
            ORDER BY c.created_at DESC
        ");
    }
    
    public function getExpiring($days = 30) {
        $stmt = $this->db->prepare("
            SELECT c.*, 
                   CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                   tp.title as training_title,
                   DATEDIFF(c.expiry_date, CURDATE()) as days_until_expiry
            FROM certifications c
            LEFT JOIN employees e ON c.employee_id = e.id
            LEFT JOIN training_programs tp ON c.training_id = tp.id
            WHERE c.status = 'active' 
              AND c.expiry_date IS NOT NULL 
              AND c.expiry_date >= CURDATE()
              AND c.expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
            ORDER BY c.expiry_date ASC
        ");
        $stmt->bind_param("i", $days);
        $stmt->execute();
        return $stmt->get_result();
    }
    
    public function getExpired() {
        return $this->db->query("
            SELECT c.*, 
                   CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                   tp.title as training_title,
                   DATEDIFF(CURDATE(), c.expiry_date) as days_expired
            FROM certifications c
            LEFT JOIN employees e ON c.employee_id = e.id
            LEFT JOIN training_programs tp ON c.training_id = tp.id
            WHERE c.status = 'expired' OR (c.expiry_date IS NOT NULL AND c.expiry_date < CURDATE())
            ORDER BY c.expiry_date ASC
        ");
    }
    
    public function updateStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE certifications SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        return $stmt->execute();
    }
    
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM certifications WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    public function getStats() {
        $stats = [];
        $result = $this->db->query("SELECT COUNT(*) as total FROM certifications");
        $stats['total'] = $result->fetch_assoc()['total'];
        
        $result = $this->db->query("SELECT COUNT(*) as total FROM certifications WHERE status = 'active'");
        $stats['active'] = $result->fetch_assoc()['total'];
        
        $result = $this->db->query("SELECT COUNT(*) as total FROM certifications WHERE status = 'expired'");
        $stats['expired'] = $result->fetch_assoc()['total'];
        
        return $stats;
    }
    
    public function getData() {
        return $this->data;
    }
}
?>