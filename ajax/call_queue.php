<?php
// ajax/call_queue.php
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

$queue_id = intval($_POST['queue_id'] ?? 0);

if ($queue_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid queue ID']);
    exit();
}

$queue_manager = new QueueManager();
$user = $auth->getCurrentUser();

// ตรวจสอบว่าคิวมีอยู่จริง
$queue = $queue_manager->getQueueById($queue_id);
if (!$queue) {
    echo json_encode(['success' => false, 'message' => 'Queue not found']);
    exit();
}

// เรียกคิว
$result = $queue_manager->callQueue($queue_id, $user['id']);

if ($result) {
    echo json_encode([
        'success' => true,
        'message' => 'Queue called successfully',
        'queue' => [
            'id' => $queue['id'],
            'queue_number' => $queue['queue_number'],
            'patient_name' => $queue['patient_name']
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to call queue']);
}
?>