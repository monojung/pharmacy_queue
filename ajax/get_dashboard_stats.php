<?php
// ajax/get_dashboard_stats.php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/queue_manager.php';

header('Content-Type: application/json');

// ตรวจสอบการล็อกอิน
if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$queue_manager = new QueueManager();

try {
    // รับสถิติวันนี้
    $today_stats = $queue_manager->getQueueStats();
    
    // รับคิวที่กำลังเรียก
    $calling_queues = $queue_manager->getAllQueues('ready');
    
    // รับคิวที่รอ
    $waiting_queues = $queue_manager->getAllQueues('waiting');
    
    // รับคิวที่กำลังเตรียม
    $preparing_queues = $queue_manager->getAllQueues('preparing');
    
    // สถิติเพิ่มเติม
    $additional_stats = [];
    
    // เวลารอเฉลี่ยของคิวที่เสร็จสิ้นวันนี้
    $stmt = getDB()->prepare("
        SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, completed_at)) as avg_wait_time
        FROM medicine_queue 
        WHERE status = 'completed' 
        AND DATE(created_at) = CURDATE()
        AND completed_at IS NOT NULL
    ");
    $stmt->execute();
    $wait_time_result = $stmt->fetch();
    $additional_stats['avg_wait_time'] = round($wait_time_result['avg_wait_time'] ?? 0, 1);
    
    // คิวที่มี priority สูง
    $stmt = getDB()->prepare("
        SELECT 
            SUM(CASE WHEN priority = 'emergency' THEN 1 ELSE 0 END) as emergency_count,
            SUM(CASE WHEN priority = 'urgent' THEN 1 ELSE 0 END) as urgent_count
        FROM medicine_queue 
        WHERE status IN ('waiting', 'preparing', 'ready')
        AND DATE(created_at) = CURDATE()
    ");
    $stmt->execute();
    $priority_result = $stmt->fetch();
    $additional_stats['emergency_count'] = $priority_result['emergency_count'] ?? 0;
    $additional_stats['urgent_count'] = $priority_result['urgent_count'] ?? 0;
    
    // คิวต่อชั่วโมงในวันนี้
    $stmt = getDB()->prepare("
        SELECT 
            HOUR(created_at) as hour,
            COUNT(*) as count
        FROM medicine_queue 
        WHERE DATE(created_at) = CURDATE()
        GROUP BY HOUR(created_at)
        ORDER BY hour
    ");
    $stmt->execute();
    $hourly_stats = $stmt->fetchAll();
    
    // ประสิทธิภาพการทำงาน (คิวเสร็จสิ้น / คิวทั้งหมด * 100)
    $total_today = $today_stats['total'] ?? 0;
    $completed_today = $today_stats['completed'] ?? 0;
    $efficiency = $total_today > 0 ? round(($completed_today / $total_today) * 100, 1) : 0;
    
    echo json_encode([
        'success' => true,
        'stats' => $today_stats,
        'calling_count' => count($calling_queues),
        'waiting_count' => count($waiting_queues),
        'preparing_count' => count($preparing_queues),
        'additional_stats' => $additional_stats,
        'hourly_stats' => $hourly_stats,
        'efficiency' => $efficiency,
        'timestamp' => time(),
        'datetime' => date('Y-m-d H:i:s')
    ]);

} catch(PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลสถิติ'
    ]);
}
?>