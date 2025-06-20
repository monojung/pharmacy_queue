<?php
// ตรวจสอบ path และใช้ path ที่ถูกต้อง
if (file_exists(__DIR__ . '/../config/database.php')) {
    require_once __DIR__ . '/../config/database.php';
} else {
    require_once 'config/database.php';
}

class QueueManager {
    private $conn;
    
    public function __construct() {
        $this->conn = getDB();
    }
    
    // สร้างคิวใหม่
    public function createQueue($hn, $medicine_list = '', $notes = '', $priority = 'normal') {
        try {
            // ค้นหาข้อมูลผู้ป่วย
            $patient = $this->getPatientByHN($hn);
            if (!$patient) {
                return ['success' => false, 'message' => 'ไม่พบข้อมูลผู้ป่วย HN: ' . $hn];
            }
            
            // สร้างหมายเลขคิว
            $queue_number = $this->generateQueueNumber();
            $patient_name = $patient['first_name'] . ' ' . $patient['last_name'];
            
            $stmt = $this->conn->prepare("
                INSERT INTO medicine_queue (queue_number, patient_id, hn, patient_name, medicine_list, notes, priority) 
                VALUES (:queue_number, :patient_id, :hn, :patient_name, :medicine_list, :notes, :priority)
            ");
            
            $stmt->bindParam(':queue_number', $queue_number);
            $stmt->bindParam(':patient_id', $patient['id']);
            $stmt->bindParam(':hn', $hn);
            $stmt->bindParam(':patient_name', $patient_name);
            $stmt->bindParam(':medicine_list', $medicine_list);
            $stmt->bindParam(':notes', $notes);
            $stmt->bindParam(':priority', $priority);
            
            if ($stmt->execute()) {
                return [
                    'success' => true, 
                    'queue_number' => $queue_number,
                    'patient_name' => $patient_name,
                    'message' => 'สร้างคิวสำเร็จ'
                ];
            }
            
            return ['success' => false, 'message' => 'ไม่สามารถสร้างคิวได้'];
            
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }
    
    // สร้างหมายเลขคิว
    private function generateQueueNumber() {
        $prefix = $this->getSetting('queue_prefix', 'M');
        $today = date('Y-m-d');
        
        // หาคิวล่าสุดของวันนี้
        $stmt = $this->conn->prepare("
            SELECT queue_number FROM medicine_queue 
            WHERE DATE(created_at) = :today 
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->bindParam(':today', $today);
        $stmt->execute();
        
        $last_queue = $stmt->fetch();
        
        if ($last_queue) {
            // ดึงตัวเลขจากคิวล่าสุด
            $last_number = intval(substr($last_queue['queue_number'], strlen($prefix)));
            $new_number = $last_number + 1;
        } else {
            $new_number = 1;
        }
        
        return $prefix . str_pad($new_number, 3, '0', STR_PAD_LEFT);
    }
    
    // ค้นหาผู้ป่วยจาก HN
    public function getPatientByHN($hn) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM patients WHERE hn = :hn");
            $stmt->bindParam(':hn', $hn);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch(PDOException $e) {
            return null;
        }
    }
    
    // รับรายการคิวทั้งหมด
    public function getAllQueues($status = null, $date = null) {
        try {
            $sql = "SELECT * FROM queue_view WHERE 1=1";
            $params = [];
            
            if ($status) {
                $sql .= " AND status = :status";
                $params[':status'] = $status;
            }
            
            if ($date) {
                $sql .= " AND DATE(created_at) = :date";
                $params[':date'] = $date;
            } else {
                $sql .= " AND DATE(created_at) = CURDATE()";
            }
            
            $sql .= " ORDER BY 
                CASE priority
                    WHEN 'emergency' THEN 1
                    WHEN 'urgent' THEN 2
                    WHEN 'normal' THEN 3
                END,
                created_at ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            return [];
        }
    }
    
    // เรียกคิว
    public function callQueue($queue_id, $user_id) {
        try {
            $this->conn->beginTransaction();
            
            // อัพเดทสถานะคิว
            $stmt = $this->conn->prepare("
                UPDATE medicine_queue 
                SET status = 'ready', called_at = NOW() 
                WHERE id = :queue_id
            ");
            $stmt->bindParam(':queue_id', $queue_id);
            $stmt->execute();
            
            // บันทึกการเรียกคิว
            $stmt = $this->conn->prepare("
                INSERT INTO queue_calls (queue_id, called_by, call_type) 
                VALUES (:queue_id, :called_by, 'first_call')
            ");
            $stmt->bindParam(':queue_id', $queue_id);
            $stmt->bindParam(':called_by', $user_id);
            $stmt->execute();
            
            $this->conn->commit();
            return true;
        } catch(PDOException $e) {
            $this->conn->rollback();
            return false;
        }
    }
    
    // อัพเดทสถานะคิว
    public function updateQueueStatus($queue_id, $status) {
        try {
            $stmt = $this->conn->prepare("UPDATE medicine_queue SET status = :status WHERE id = :queue_id");
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':queue_id', $queue_id);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }
    
    // รับข้อมูลคิวเดียว
    public function getQueueById($queue_id) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM queue_view WHERE id = :queue_id");
            $stmt->bindParam(':queue_id', $queue_id);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch(PDOException $e) {
            return null;
        }
    }
    
    // รับการตั้งค่า
    public function getSetting($key, $default = '') {
        try {
            $stmt = $this->conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = :key");
            $stmt->bindParam(':key', $key);
            $stmt->execute();
            
            $result = $stmt->fetch();
            return $result ? $result['setting_value'] : $default;
        } catch(PDOException $e) {
            return $default;
        }
    }
    
    // อัพเดทการตั้งค่า
    public function updateSetting($key, $value) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO system_settings (setting_key, setting_value) 
                VALUES (:key, :value) 
                ON DUPLICATE KEY UPDATE setting_value = :value2
            ");
            $stmt->bindParam(':key', $key);
            $stmt->bindParam(':value', $value);
            $stmt->bindParam(':value2', $value);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }
    
    // ลบคิว
    public function deleteQueue($queue_id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM medicine_queue WHERE id = :queue_id");
            $stmt->bindParam(':queue_id', $queue_id);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }
    
    // สถิติคิว
    public function getQueueStats($date = null) {
        try {
            if (!$date) {
                $date = date('Y-m-d');
            }
            
            $stmt = $this->conn->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'waiting' THEN 1 ELSE 0 END) as waiting,
                    SUM(CASE WHEN status = 'preparing' THEN 1 ELSE 0 END) as preparing,
                    SUM(CASE WHEN status = 'ready' THEN 1 ELSE 0 END) as ready,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
                FROM medicine_queue 
                WHERE DATE(created_at) = :date
            ");
            $stmt->bindParam(':date', $date);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch(PDOException $e) {
            return null;
        }
    }
}
?>