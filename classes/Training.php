<?php
// classes/Training.php - Training Management Class
class Training {
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
        $stmt = $this->db->prepare("SELECT * FROM training_programs WHERE id = ?");
        $stmt->bind_param("i", $this->id);
        $stmt->execute();
        $result = $stmt->get_result();
        $this->data = $result->fetch_assoc();
    }
    
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO training_programs (
                title, description, type, category, duration_hours,
                start_date, end_date, location, trainer_name, trainer_email,
                max_participants, cost, status, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "ssssisssssidsi",
            $data['title'],
            $data['description'],
            $data['type'],
            $data['category'],
            $data['duration_hours'],
            $data['start_date'],
            $data['end_date'],
            $data['location'],
            $data['trainer_name'],
            $data['trainer_email'],
            $data['max_participants'],
            $data['cost'],
            $data['status'],
            $data['created_by']
        );
        
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }
    
    public function update($data) {
        $sql = "
            UPDATE training_programs SET 
                title = ?, description = ?, type = ?, category = ?, duration_hours = ?,
                start_date = ?, end_date = ?, location = ?, trainer_name = ?, trainer_email = ?,
                max_participants = ?, cost = ?, status = ?
            WHERE id = ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            "ssssisssssidsi",
            $data['title'],
            $data['description'],
            $data['type'],
            $data['category'],
            $data['duration_hours'],
            $data['start_date'],
            $data['end_date'],
            $data['location'],
            $data['trainer_name'],
            $data['trainer_email'],
            $data['max_participants'],
            $data['cost'],
            $data['status'],
            $this->id
        );
        return $stmt->execute();
    }
    
    public function enrollEmployee($employeeId, $trainingId) {
        // Check if already enrolled
        $check = $this->db->prepare("SELECT id FROM employee_trainings WHERE employee_id = ? AND training_id = ?");
        $check->bind_param("ii", $employeeId, $trainingId);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            return false;
        }
        
        // Check capacity
        $training = $this->getById($trainingId);
        if ($training && $training['current_participants'] >= $training['max_participants']) {
            return false;
        }
        
        // Enroll
        $stmt = $this->db->prepare("
            INSERT INTO employee_trainings (employee_id, training_id, enrollment_date, status) 
            VALUES (?, ?, CURDATE(), 'enrolled')
        ");
        $stmt->bind_param("ii", $employeeId, $trainingId);
        
        if ($stmt->execute()) {
            // Update participant count
            $this->db->query("UPDATE training_programs SET current_participants = current_participants + 1 WHERE id = $trainingId");
            return true;
        }
        return false;
    }
    
    public function getEmployeeTrainings($employeeId) {
        $sql = "
            SELECT et.*, tp.title, tp.description, tp.type, tp.start_date, tp.end_date, 
                   tp.location, tp.trainer_name 
            FROM employee_trainings et 
            JOIN training_programs tp ON et.training_id = tp.id 
            WHERE et.employee_id = ? 
            ORDER BY et.enrollment_date DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $employeeId);
        $stmt->execute();
        return $stmt->get_result();
    }
    
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM training_programs WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function getAll($status = null, $type = null) {
        $sql = "SELECT * FROM training_programs WHERE 1=1";
        if ($status) {
            $sql .= " AND status = '$status'";
        }
        if ($type) {
            $sql .= " AND type = '$type'";
        }
        $sql .= " ORDER BY start_date DESC";
        return $this->db->query($sql);
    }
    
    public function getUpcoming($limit = 5) {
        $sql = "SELECT * FROM training_programs WHERE status = 'upcoming' ORDER BY start_date ASC LIMIT $limit";
        return $this->db->query($sql);
    }
    
    public function getOngoing() {
        return $this->db->query("SELECT * FROM training_programs WHERE status = 'ongoing'");
    }
    
    public function getCompleted() {
        return $this->db->query("SELECT * FROM training_programs WHERE status = 'completed'");
    }
    
    public function updateProgress($employeeId, $trainingId, $progress) {
        $stmt = $this->db->prepare("
            UPDATE employee_trainings SET progress = ? WHERE employee_id = ? AND training_id = ?
        ");
        $stmt->bind_param("dii", $progress, $employeeId, $trainingId);
        return $stmt->execute();
    }
    
    public function completeTraining($employeeId, $trainingId, $score = null) {
        $stmt = $this->db->prepare("
            UPDATE employee_trainings SET 
                status = 'completed', 
                completion_date = CURDATE(),
                progress = 100,
                score = ? 
            WHERE employee_id = ? AND training_id = ?
        ");
        $stmt->bind_param("dii", $score, $employeeId, $trainingId);
        return $stmt->execute();
    }
    
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM training_programs WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    public function getStats() {
        $stats = [];
        $result = $this->db->query("SELECT COUNT(*) as total FROM training_programs");
        $stats['total'] = $result->fetch_assoc()['total'];
        
        $result = $this->db->query("SELECT COUNT(*) as total FROM training_programs WHERE status = 'upcoming'");
        $stats['upcoming'] = $result->fetch_assoc()['total'];
        
        $result = $this->db->query("SELECT COUNT(*) as total FROM training_programs WHERE status = 'ongoing'");
        $stats['ongoing'] = $result->fetch_assoc()['total'];
        
        $result = $this->db->query("SELECT COUNT(*) as total FROM training_programs WHERE status = 'completed'");
        $stats['completed'] = $result->fetch_assoc()['total'];
        
        return $stats;
    }
    
    public function getData() {
        return $this->data;
    }
}
?>