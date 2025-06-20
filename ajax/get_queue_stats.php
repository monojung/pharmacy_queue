<?php
header("Content-Type: application/json; charset=utf-8");

require_once "../config/database.php";
require_once "../includes/auth.php";
require_once "../includes/queue_manager.php";

if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "ไม่ได้รับอนุญาต"]);
    exit;
}

$queue_manager = new QueueManager();
$stats = $queue_manager->getQueueStats();

echo json_encode(["success" => true, "stats" => $stats]);
?>