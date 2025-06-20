<?php
// ajax/update_status.php
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
$status = trim($_POST['status'] ?? '');

if ($queue_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid queue ID']);
    exit();
}

$valid_statuses = ['waiting', 'preparing', 'ready', 'completed', 'cancelled'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

$queue_manager = new QueueManager();

// ตรวจสอบว่าคิวมีอยู่จริง
$queue = $queue_manager->getQueueById($queue_id);
if (!$queue) {
    echo json_encode(['success' => false, 'message' => 'Queue not found']);
    exit();
}

// อัพเดทสถานะ
$result = $queue_manager->updateQueueStatus($queue_id, $status);

if ($result) {
    echo json_encode([
        'success' => true,
        'message' => 'Status updated successfully',
        'queue_id' => $queue_id,
        'new_status' => $status
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update status']);
}
?>