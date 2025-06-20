<?php
// ajax/search_patient.php
require_once '../config/database.php';
require_once '../includes/queue_manager.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$hn = trim($_GET['hn'] ?? '');

if (empty($hn)) {
    echo json_encode(['success' => false, 'message' => 'HN is required']);
    exit();
}

$queue_manager = new QueueManager();
$patient = $queue_manager->getPatientByHN($hn);

if ($patient) {
    echo json_encode([
        'success' => true,
        'patient' => [
            'id' => $patient['id'],
            'hn' => $patient['hn'],
            'first_name' => $patient['first_name'],
            'last_name' => $patient['last_name'],
            'phone' => $patient['phone'],
            'address' => $patient['address']
        ]
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Patient not found'
    ]);
}
?>