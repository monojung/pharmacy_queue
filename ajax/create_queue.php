<?php
// ajax/create_queue.php
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

$hn = trim($_POST['hn'] ?? '');
$medicine_list = trim($_POST['medicine_list'] ?? '');
$notes = trim($_POST['notes'] ?? '');
$priority = $_POST['priority'] ?? 'normal';

if (empty($hn)) {
    echo json_encode(['success' => false, 'message' => 'กรุณากรอกหมายเลข HN']);
    exit();
}

// ตรวจสอบ priority ที่อนุญาต
$allowed_priorities = ['normal', 'urgent', 'emergency'];
if (!in_array($priority, $allowed_priorities)) {
    $priority = 'normal';
}

$queue_manager = new QueueManager();

try {
    $result = $queue_manager->createQueue($hn, $medicine_list, $notes, $priority);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'สร้างคิวสำเร็จ',
            'queue_number' => $result['queue_number'],
            'patient_name' => $result['patient_name']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }
    
} catch(Exception $e) {
    error_log("Create queue error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในการสร้างคิว'
    ]);
}
?>