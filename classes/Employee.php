<?php
// classes/Employee.php - Employee Management Class
class Employee {
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
        $stmt = $this->db->prepare("SELECT * FROM employees WHERE id = ?");
        $stmt->bind_param("i", $this->id);
        $stmt->execute();
        $result = $stmt->get_result();
        $this->data = $result->fetch_assoc();
    }
    
    public function create($data) {
        $employee_code = $data['employee_code'] ?? generateUniqueCode();
        
        $stmt = $this->db->prepare("
            INSERT INTO employees (
                first_name, last_name, email, phone, department, position,
                date_of_joining, date_of_birth, profile_picture, employee_code, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "sssssssssss",
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['phone'],
            $data['department'],
            $data['position'],
            $data['date_of_joining'],
            $data['date_of_birth'],
            $data['profile_picture'],
            $employee_code,
            $data['status']
        );
        
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }
    
    public function update($data) {
        $sql = "
            UPDATE employees SET 
                first_name = ?, last_name = ?, email = ?, phone = ?,
                department = ?, position = ?, date_of_joining = ?,
                date_of_birth = ?, profile_picture = ?, status = ?
            WHERE id = ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            "ssssssssssi",
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['phone'],
            $data['department'],
            $data['position'],
            $data['date_of_joining'],
            $data['date_of_birth'],
            $data['profile_picture'],
            $data['status'],
            $this->id
        );
        return $stmt->execute();
    }
    
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM employees WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function getByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM employees WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function getAll($status = null) {
        $sql = "SELECT * FROM employees";
        if ($status) {
            $sql .= " WHERE status = '$status'";
        }
        $sql .= " ORDER BY created_at DESC";
        return $this->db->query($sql);
    }
    
    public function getByDepartment($department) {
        $stmt = $this->db->prepare("SELECT * FROM employees WHERE department = ? ORDER BY first_name");
        $stmt->bind_param("s", $department);
        $stmt->execute();
        return $stmt->get_result();
    }
    
    public function getActiveCount() {
        $result = $this->db->query("SELECT COUNT(*) as count FROM employees WHERE status = 'active'");
        return $result->fetch_assoc()['count'];
    }
    
    public function getDepartments() {
        return $this->db->query("SELECT DISTINCT department FROM employees WHERE department IS NOT NULL ORDER BY department");
    }
    
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM employees WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    public function updateProfilePicture($picture) {
        $stmt = $this->db->prepare("UPDATE employees SET profile_picture = ? WHERE id = ?");
        $stmt->bind_param("si", $picture, $this->id);
        return $stmt->execute();
    }
    
    public function getData() {
        return $this->data;
    }
}
?>