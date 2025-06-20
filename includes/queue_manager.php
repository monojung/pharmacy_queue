<?php
// ตรวจสอบ path และใช้ path ที่ถูกต้อง
if (file_exists(__DIR__ . '/../config/database.php')) {
    require_once __DIR__ . '/../config/database.php';
} else if (file_exists('config/database.php')) {
    require_once 'config/database.php';
} else {
    // Create inline database connection if config file not found
    function getDB() {
        static $db = null;
        if ($db === null) {
            try {
                $dsn = "mysql:host=localhost;dbname=pharmacy_queue;charset=utf8mb4";
                $db = new PDO($dsn, 'root', '', [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch(PDOException $e) {
                throw new Exception("Database connection failed: " . $e->getMessage());
            }
        }
        return $db;
    }
}

class QueueManager {
    private $conn;
    
    public function __construct() {
        try {
            $this->conn = getDB();
        } catch(Exception $e) {
            error_log("QueueManager initialization error: " . $e->getMessage());
            throw $e;
        }
    }
    
    // สร้างคิวใหม่
    public function createQueue($hn, $medicine_list = '', $notes = '', $priority = 'normal') {
        try {
            // ค้นหาข้อมูลผู้ป่วย
            $patient = $this->getPatientByHN($hn);
            if (!$patient) {
                // สร้างผู้ป่วยใหม่ถ้าไม่พบ
                $patient = $this->createPatient($hn);
                if (!$patient) {
                    return ['success' => false, 'message' => 'ไม่สามารถสร้างข้อมูลผู้ป่วยได้'];
                }
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
            error_log("Create queue error: " . $e->getMessage());
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }
    
    // สร้างหมายเลขคิว
    private function generateQueueNumber() {
        $prefix = $this->getSetting('queue_prefix', 'M');
        $today = date('Y-m-d');
        
        try {
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
        } catch(PDOException $e) {
            // Fallback: สร้างหมายเลขจาก timestamp
            return $prefix . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        }
    }
    
    // สร้างผู้ป่วยใหม่
    private function createPatient($hn) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO patients (hn, first_name, last_name) 
                VALUES (:hn, :first_name, :last_name)
            ");
            
            $first_name = 'ผู้ป่วย';
            $last_name = $hn;
            
            $stmt->bindParam(':hn', $hn);
            $stmt->bindParam(':first_name', $first_name);
            $stmt->bindParam(':last_name', $last_name);
            
            if ($stmt->execute()) {
                return [
                    'id' => $this->conn->lastInsertId(),
                    'hn' => $hn,
                    'first_name' => $first_name,
                    'last_name' => $last_name
                ];
            }
            
            return null;
        } catch(PDOException $e) {
            error_log("Create patient error: " . $e->getMessage());
            return null;
        }
    }
    
    // ค้นหาผู้ป่วยจาก HN
    public function getPatientByHN($hn) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM patients WHERE hn = :hn");
            $stmt->bindParam(':hn', $hn);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch(PDOException $e) {
            error_log("Get patient error: " . $e->getMessage());
            return null;
        }
    }
    
    // รับรายการคิวทั้งหมด
    public function getAllQueues($status = null, $date = null) {
        try {
            // ตรวจสอบว่ามี view อยู่หรือไม่
            $view_exists = $this->checkViewExists('queue_view');
            
            if ($view_exists) {
                $sql = "SELECT * FROM queue_view WHERE 1=1";
            } else {
                // ใช้ query ธรรมดาถ้าไม่มี view
                $sql = "SELECT 
                    mq.id,
                    mq.queue_number,
                    mq.patient_id,
                    mq.hn,
                    mq.patient_name,
                    mq.medicine_list,
                    mq.notes,
                    mq.priority,
                    CASE mq.priority
                        WHEN 'emergency' THEN 'ฉุกเฉิน'
                        WHEN 'urgent' THEN 'ด่วน'
                        WHEN 'normal' THEN 'ปกติ'
                    END as priority_text,
                    mq.status,
                    CASE mq.status
                        WHEN 'waiting' THEN 'รอเรียก'
                        WHEN 'preparing' THEN 'กำลังเตรียม'
                        WHEN 'ready' THEN 'พร้อมรับ'
                        WHEN 'completed' THEN 'เสร็จสิ้น'
                        WHEN 'cancelled' THEN 'ยกเลิก'
                    END as status_text,
                    mq.created_at,
                    mq.called_at,
                    mq.completed_at,
                    mq.updated_at,
                    p.phone as patient_phone,
                    p.email as patient_email
                FROM medicine_queue mq
                LEFT JOIN patients p ON mq.patient_id = p.id
                WHERE 1=1";
            }
            
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
            error_log("Get all queues error: " . $e->getMessage());
            return [];
        }
    }
    
    // ตรวจสอบว่ามี view อยู่หรือไม่
    private function checkViewExists($view_name) {
        try {
            $stmt = $this->conn->prepare("SHOW TABLES LIKE :view_name");
            $stmt->bindParam(':view_name', $view_name);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch(PDOException $e) {
            return false;
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
            
            // บันทึกการเรียกคิว (ถ้ามี table)
            try {
                $stmt = $this->conn->prepare("
                    INSERT INTO queue_calls (queue_id, called_by, call_type) 
                    VALUES (:queue_id, :called_by, 'first_call')
                ");
                $stmt->bindParam(':queue_id', $queue_id);
                $stmt->bindParam(':called_by', $user_id);
                $stmt->execute();
            } catch(PDOException $e) {
                // Ignore if table doesn't exist
            }
            
            $this->conn->commit();
            return true;
        } catch(PDOException $e) {
            $this->conn->rollback();
            error_log("Call queue error: " . $e->getMessage());
            return false;
        }
    }
    
    // อัพเดทสถานะคิว
    public function updateQueueStatus($queue_id, $status) {
        try {
            $update_fields = "status = :status";
            $params = [':status' => $status, ':queue_id' => $queue_id];
            
            // เพิ่มการอัพเดท timestamp ตามสถานะ
            if ($status === 'completed') {
                $update_fields .= ", completed_at = NOW()";
            }
            
            $stmt = $this->conn->prepare("UPDATE medicine_queue SET $update_fields WHERE id = :queue_id");
            return $stmt->execute($params);
        } catch(PDOException $e) {
            error_log("Update queue status error: " . $e->getMessage());
            return false;
        }
    }
    
    // รับข้อมูลคิวเดียว
    public function getQueueById($queue_id) {
        try {
            // ใช้ query ธรรมดาแทน view
            $stmt = $this->conn->prepare("
                SELECT 
                    mq.id,
                    mq.queue_number,
                    mq.patient_id,
                    mq.hn,
                    mq.patient_name,
                    mq.medicine_list,
                    mq.notes,
                    mq.priority,
                    CASE mq.priority
                        WHEN 'emergency' THEN 'ฉุกเฉิน'
                        WHEN 'urgent' THEN 'ด่วน'
                        WHEN 'normal' THEN 'ปกติ'
                    END as priority_text,
                    mq.status,
                    CASE mq.status
                        WHEN 'waiting' THEN 'รอเรียก'
                        WHEN 'preparing' THEN 'กำลังเตรียม'
                        WHEN 'ready' THEN 'พร้อมรับ'
                        WHEN 'completed' THEN 'เสร็จสิ้น'
                        WHEN 'cancelled' THEN 'ยกเลิก'
                    END as status_text,
                    mq.created_at,
                    mq.called_at,
                    mq.completed_at,
                    mq.updated_at,
                    p.phone as patient_phone,
                    p.email as patient_email
                FROM medicine_queue mq
                LEFT JOIN patients p ON mq.patient_id = p.id
                WHERE mq.id = :queue_id
            ");
            $stmt->bindParam(':queue_id', $queue_id);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch(PDOException $e) {
            error_log("Get queue by ID error: " . $e->getMessage());
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
            error_log("Get setting error: " . $e->getMessage());
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
            error_log("Update setting error: " . $e->getMessage());
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
            error_log("Delete queue error: " . $e->getMessage());
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
            
            $result = $stmt->fetch();
            
            // แปลงค่า null เป็น 0
            foreach ($result as $key => $value) {
                $result[$key] = $value ?? 0;
            }
            
            return $result;
        } catch(PDOException $e) {
            error_log("Get queue stats error: " . $e->getMessage());
            return [
                'total' => 0,
                'waiting' => 0,
                'preparing' => 0,
                'ready' => 0,
                'completed' => 0,
                'cancelled' => 0
            ];
        }
    }
    
    // เรียกคิวถัดไป
    public function callNextQueue($user_id) {
        try {
            // หาคิวถัดไปที่รอเรียก
            $waiting_queues = $this->getAllQueues('waiting');
            
            if (empty($waiting_queues)) {
                return ['success' => false, 'message' => 'ไม่มีคิวที่รอเรียก'];
            }
            
            $next_queue = $waiting_queues[0]; // เอาคิวแรก
            
            if ($this->callQueue($next_queue['id'], $user_id)) {
                return [
                    'success' => true, 
                    'message' => 'เรียกคิวถัดไปสำเร็จ',
                    'queue' => $next_queue
                ];
            } else {
                return ['success' => false, 'message' => 'ไม่สามารถเรียกคิวได้'];
            }
        } catch(Exception $e) {
            error_log("Call next queue error: " . $e->getMessage());
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด'];
        }
    }
    
    // ตรวจสอบการเชื่อมต่อฐานข้อมูล
    public function testConnection() {
        try {
            $stmt = $this->conn->query("SELECT 1");
            return ['success' => true, 'message' => 'การเชื่อมต่อฐานข้อมูลปกติ'];
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'การเชื่อมต่อฐานข้อมูลล้มเหลว: ' . $e->getMessage()];
        }
    }
}
?>