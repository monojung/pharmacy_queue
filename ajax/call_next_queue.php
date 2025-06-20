<?php
// ajax/call_next_queue.php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/queue_manager.php';

header('Content-Type: application/json');

// ตรวจสอบการล็อกอิน
if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$queue_manager = new QueueManager();
$user = $auth->getCurrentUser();

try {
    // หาคิวถัดไปที่รอเรียก (เรียงตาม priority แล้วตาม created_at)
    $stmt = getDB()->prepare("
        SELECT * FROM medicine_queue 
        WHERE status = 'waiting' 
        AND DATE(created_at) = CURDATE()
        ORDER BY 
            CASE priority
                WHEN 'emergency' THEN 1
                WHEN 'urgent' THEN 2
                WHEN 'normal' THEN 3
            END,
            created_at ASC
        LIMIT 1
    ");
    $stmt->execute();
    
    $next_queue = $stmt->fetch();
    
    if (!$next_queue) {
        echo json_encode([
            'success' => false, 
            'message' => 'ไม่มีคิวที่รอเรียก'
        ]);
        exit();
    }
    
    // เรียกคิว
    $result = $queue_manager->callQueue($next_queue['id'], $user['id']);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'เรียกคิวถัดไปสำเร็จ',
            'queue_number' => $next_queue['queue_number'],
            'patient_name' => $next_queue['patient_name'],
            'queue' => [
                'id' => $next_queue['id'],
                'queue_number' => $next_queue['queue_number'],
                'patient_name' => $next_queue['patient_name'],
                'priority' => $next_queue['priority']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'ไม่สามารถเรียกคิวได้'
        ]);
    }
    
} catch(Exception $e) {
    error_log("Call next queue error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'เกิดข้อผิดพลาดในการเรียกคิว'
    ]);
}
?>