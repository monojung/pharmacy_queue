-- สร้างฐานข้อมูลระบบเรียกคิวรับยา
CREATE DATABASE IF NOT EXISTS pharmacy_queue CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pharmacy_queue;

-- ตารางผู้ใช้งานระบบ
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'pharmacist', 'staff') DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ตารางข้อมูลผู้ป่วย
CREATE TABLE patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hn VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(15),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_hn (hn)
);

-- ตารางคิวรับยา
CREATE TABLE medicine_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    queue_number VARCHAR(10) NOT NULL,
    patient_id INT NOT NULL,
    hn VARCHAR(20) NOT NULL,
    patient_name VARCHAR(100) NOT NULL,
    status ENUM('waiting', 'preparing', 'ready', 'completed', 'cancelled') DEFAULT 'waiting',
    priority ENUM('normal', 'urgent', 'emergency') DEFAULT 'normal',
    medicine_list TEXT,
    notes TEXT,
    called_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    INDEX idx_queue_date (created_at),
    INDEX idx_status (status),
    INDEX idx_hn (hn)
);

-- ตารางการตั้งค่าระบบ
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ตารางบันทึกการเรียกคิว
CREATE TABLE queue_calls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    queue_id INT NOT NULL,
    called_by INT NOT NULL,
    call_type ENUM('first_call', 'repeat_call', 'final_call') DEFAULT 'first_call',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (queue_id) REFERENCES medicine_queue(id) ON DELETE CASCADE,
    FOREIGN KEY (called_by) REFERENCES users(id)
);

-- เพิ่มข้อมูลผู้ใช้งานเริ่มต้น (admin)
INSERT INTO users (username, password, full_name, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ผู้ดูแลระบบ', 'admin');
-- รหัสผ่าน: password

-- เพิ่มการตั้งค่าเริ่มต้น
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('queue_prefix', 'M', 'อักษรนำหน้าหมายเลขคิว'),
('queue_reset_time', '00:00', 'เวลารีเซ็ตคิวรายวัน'),
('max_queue_per_day', '999', 'จำนวนคิวสูงสุดต่อวัน'),
('tts_enabled', '1', 'เปิดใช้งานเสียงเรียกคิว'),
('tts_voice', 'th-TH', 'ภาษาเสียงเรียกคิว'),
('auto_call_interval', '30', 'ช่วงเวลาเรียกซ้ำอัตโนมัติ (วินาที)'),
('hospital_name', 'โรงพยาบาล ABC', 'ชื่อโรงพยาบาล'),
('pharmacy_name', 'ห้องยา', 'ชื่อห้องยา');

-- เพิ่มข้อมูลผู้ป่วยตัวอย่าง
INSERT INTO patients (hn, first_name, last_name, phone) VALUES
('HN001', 'สมชาย', 'ใจดี', '081-234-5678'),
('HN002', 'สมหญิง', 'รักสวย', '082-345-6789'),
('HN003', 'วิชัย', 'มั่นคง', '083-456-7890');

-- สร้าง View สำหรับแสดงข้อมูลคิว
CREATE VIEW queue_view AS
SELECT 
    mq.id,
    mq.queue_number,
    mq.hn,
    mq.patient_name,
    mq.status,
    mq.priority,
    mq.medicine_list,
    mq.notes,
    mq.created_at,
    mq.called_at,
    mq.completed_at,
    CASE 
        WHEN mq.status = 'waiting' THEN 'รอรับยา'
        WHEN mq.status = 'preparing' THEN 'กำลังเตรียมยา'
        WHEN mq.status = 'ready' THEN 'พร้อมรับยา'
        WHEN mq.status = 'completed' THEN 'รับยาแล้ว'
        WHEN mq.status = 'cancelled' THEN 'ยกเลิก'
    END as status_text,
    CASE 
        WHEN mq.priority = 'normal' THEN 'ปกติ'
        WHEN mq.priority = 'urgent' THEN 'ด่วน'
        WHEN mq.priority = 'emergency' THEN 'ฉุกเฉิน'
    END as priority_text
FROM medicine_queue mq
ORDER BY 
    CASE mq.priority
        WHEN 'emergency' THEN 1
        WHEN 'urgent' THEN 2
        WHEN 'normal' THEN 3
    END,
    mq.created_at ASC;