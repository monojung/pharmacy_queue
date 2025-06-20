-- สร้างฐานข้อมูลและตารางที่จำเป็น
CREATE DATABASE IF NOT EXISTS pharmacy_queue CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pharmacy_queue;

-- ตาราง users สำหรับผู้ใช้งานระบบ
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'pharmacist', 'staff') DEFAULT 'staff',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- ตาราง patients สำหรับข้อมูลผู้ป่วย
CREATE TABLE IF NOT EXISTS patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hn VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ตาราง medicine_queue สำหรับคิวรับยา
CREATE TABLE IF NOT EXISTS medicine_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    queue_number VARCHAR(20) NOT NULL,
    patient_id INT,
    hn VARCHAR(20) NOT NULL,
    patient_name VARCHAR(100) NOT NULL,
    medicine_list TEXT,
    notes TEXT,
    priority ENUM('normal', 'urgent', 'emergency') DEFAULT 'normal',
    status ENUM('waiting', 'preparing', 'ready', 'completed', 'cancelled') DEFAULT 'waiting',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    called_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_created_at (created_at),
    INDEX idx_hn (hn)
);

-- ตาราง system_settings สำหรับการตั้งค่าระบบ
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ตาราง user_activity_log สำหรับบันทึกกิจกรรมผู้ใช้
CREATE TABLE IF NOT EXISTS user_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(50) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
);

-- ตาราง failed_login_log สำหรับบันทึกการล็อกอินที่ล้มเหลว
CREATE TABLE IF NOT EXISTS failed_login_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50),
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_ip_address (ip_address),
    INDEX idx_created_at (created_at)
);

-- ตาราง queue_calls สำหรับบันทึกการเรียกคิว
CREATE TABLE IF NOT EXISTS queue_calls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    queue_id INT NOT NULL,
    called_by INT,
    call_type ENUM('first_call', 'recall', 'final_call') DEFAULT 'first_call',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (queue_id) REFERENCES medicine_queue(id) ON DELETE CASCADE,
    FOREIGN KEY (called_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_queue_id (queue_id),
    INDEX idx_called_by (called_by),
    INDEX idx_created_at (created_at)
);

-- สร้าง View สำหรับดูข้อมูลคิวพร้อมข้อมูลผู้ป่วย
CREATE OR REPLACE VIEW queue_view AS
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
    p.email as patient_email,
    p.address as patient_address
FROM medicine_queue mq
LEFT JOIN patients p ON mq.patient_id = p.id;

-- แทรกข้อมูลผู้ใช้เริ่มต้น
INSERT IGNORE INTO users (username, password, full_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ผู้ดูแลระบบ', 'admin'),
('pharmacist', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'เภสัชกร', 'pharmacist'),
('staff', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'เจ้าหน้าที่', 'staff');
-- รหัสผ่านทั้งหมดคือ "password"

-- แทรกการตั้งค่าเริ่มต้น
INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES
('hospital_name', 'โรงพยาบาล ABC'),
('pharmacy_name', 'ห้องยา'),
('queue_prefix', 'M'),
('tts_enabled', '1'),
('tts_voice', 'th-TH'),
('auto_call_interval', '30'),
('max_queue_per_day', '999');

-- แทรกข้อมูลผู้ป่วยตัวอย่าง
INSERT IGNORE INTO patients (hn, first_name, last_name, phone) VALUES
('HN001', 'สมชาย', 'ใจดี', '081-234-5678'),
('HN002', 'สมหญิง', 'รักดี', '082-345-6789'),
('HN003', 'สมศักดิ์', 'มีสุข', '083-456-7890'),
('HN004', 'สมปอง', 'สุขใจ', '084-567-8901'),
('HN005', 'สมจิตร', 'ดีใจ', '085-678-9012');

-- สร้าง Stored Procedure สำหรับดึงสถิติคิว
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS GetQueueStats(IN target_date DATE)
BEGIN
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'waiting' THEN 1 ELSE 0 END) as waiting,
        SUM(CASE WHEN status = 'preparing' THEN 1 ELSE 0 END) as preparing,
        SUM(CASE WHEN status = 'ready' THEN 1 ELSE 0 END) as ready,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
        AVG(CASE 
            WHEN status = 'completed' AND completed_at IS NOT NULL 
            THEN TIMESTAMPDIFF(MINUTE, created_at, completed_at) 
            ELSE NULL 
        END) as avg_waiting_time
    FROM medicine_queue 
    WHERE DATE(created_at) = IFNULL(target_date, CURDATE());
END //
DELIMITER ;

-- สร้าง Trigger สำหรับอัพเดท updated_at
DELIMITER //
CREATE TRIGGER IF NOT EXISTS update_queue_timestamp 
    BEFORE UPDATE ON medicine_queue
    FOR EACH ROW
BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
    
    -- อัพเดท completed_at เมื่อสถานะเป็น completed
    IF NEW.status = 'completed' AND OLD.status != 'completed' THEN
        SET NEW.completed_at = CURRENT_TIMESTAMP;
    END IF;
    
    -- อัพเดท called_at เมื่อสถานะเป็น ready
    IF NEW.status = 'ready' AND OLD.status != 'ready' THEN
        SET NEW.called_at = CURRENT_TIMESTAMP;
    END IF;
END //
DELIMITER ;

-- สร้าง Index เพิ่มเติมเพื่อเพิ่มประสิทธิภาพ
-- แทนที่จะใช้ INDEX idx_created_date (DATE(created_at)) ให้ใช้ดังนี้:
CREATE INDEX IF NOT EXISTS idx_queue_status_created ON medicine_queue(status, created_at);
CREATE INDEX IF NOT EXISTS idx_queue_priority_created ON medicine_queue(priority, created_at);
CREATE INDEX IF NOT EXISTS idx_patient_hn_name ON patients(hn, first_name, last_name);

-- สร้าง Event สำหรับล้างข้อมูลเก่า (รันทุกวันตอน 02:00)
SET GLOBAL event_scheduler = ON;

DELIMITER //
CREATE EVENT IF NOT EXISTS cleanup_old_data
ON SCHEDULE EVERY 1 DAY STARTS '2024-01-01 02:00:00'
DO
BEGIN
    -- ลบข้อมูล activity log เก่ากว่า 90 วัน
    DELETE FROM user_activity_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
    
    -- ลบข้อมูล failed login log เก่ากว่า 30 วัน
    DELETE FROM failed_login_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
    
    -- ลบข้อมูล queue calls เก่ากว่า 90 วัน
    DELETE FROM queue_calls WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
    
    -- อัพเดทสถิติฐานข้อมูล
    ANALYZE TABLE medicine_queue, patients, users;
END //
DELIMITER ;

-- แสดงข้อมูลผู้ใช้ที่สร้างเสร็จแล้ว
SELECT 'Database setup completed successfully!' as message;
SELECT username, full_name, role FROM users;