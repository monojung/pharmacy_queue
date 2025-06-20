<?php
/**
 * สคริปต์สำหรับสร้างโครงสร้างโฟลเดอร์และไฟล์ AJAX endpoints
 * รันไฟล์นี้ครั้งเดียวเพื่อสร้างโครงสร้างที่จำเป็น
 */

// สร้างโฟลเดอร์ ajax ถ้าไม่มี
if (!is_dir('ajax')) {
    mkdir('ajax', 0755, true);
    echo "✅ สร้างโฟลเดอร์ ajax เรียบร้อย\n";
} else {
    echo "📁 โฟลเดอร์ ajax มีอยู่แล้ว\n";
}

// สร้างโฟลเดอร์ assets/css ถ้าไม่มี
if (!is_dir('assets/css')) {
    mkdir('assets/css', 0755, true);
    echo "✅ สร้างโฟลเดอร์ assets/css เรียบร้อย\n";
} else {
    echo "📁 โฟลเดอร์ assets/css มีอยู่แล้ว\n";
}

// สร้างไฟล์ AJAX endpoints
$ajax_files = [
    'call_queue.php' => '<?php
header("Content-Type: application/json; charset=utf-8");

require_once "../config/database.php";
require_once "../includes/auth.php";
require_once "../includes/queue_manager.php";

if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "ไม่ได้รับอนุญาต"]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit;
}

$queue_id = $_POST["queue_id"] ?? 0;

if (!$queue_id) {
    echo json_encode(["success" => false, "message" => "ไม่พบรหัสคิว"]);
    exit;
}

$queue_manager = new QueueManager();
$user = $auth->getCurrentUser();

if ($queue_manager->callQueue($queue_id, $user["id"])) {
    echo json_encode(["success" => true, "message" => "เรียกคิวสำเร็จ"]);
} else {
    echo json_encode(["success" => false, "message" => "ไม่สามารถเรียกคิวได้"]);
}
?>',

    'update_status.php' => '<?php
header("Content-Type: application/json; charset=utf-8");

require_once "../config/database.php";
require_once "../includes/auth.php";
require_once "../includes/queue_manager.php";

if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "ไม่ได้รับอนุญาต"]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit;
}

$queue_id = $_POST["queue_id"] ?? 0;
$status = $_POST["status"] ?? "";

if (!$queue_id || !$status) {
    echo json_encode(["success" => false, "message" => "ข้อมูลไม่ครบถ้วน"]);
    exit;
}

$allowed_statuses = ["waiting", "preparing", "ready", "completed", "cancelled"];
if (!in_array($status, $allowed_statuses)) {
    echo json_encode(["success" => false, "message" => "สถานะไม่ถูกต้อง"]);
    exit;
}

$queue_manager = new QueueManager();

if ($queue_manager->updateQueueStatus($queue_id, $status)) {
    echo json_encode(["success" => true, "message" => "อัพเดทสถานะสำเร็จ"]);
} else {
    echo json_encode(["success" => false, "message" => "ไม่สามารถอัพเดทสถานะได้"]);
}
?>',

    'search_patient.php' => '<?php
header("Content-Type: application/json; charset=utf-8");

require_once "../config/database.php";
require_once "../includes/auth.php";
require_once "../includes/queue_manager.php";

if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "ไม่ได้รับอนุญาต"]);
    exit;
}

$hn = $_GET["hn"] ?? "";

if (strlen($hn) < 3) {
    echo json_encode(["success" => false, "message" => "กรุณากรอก HN อย่างน้อย 3 ตัวอักษร"]);
    exit;
}

$queue_manager = new QueueManager();
$patient = $queue_manager->getPatientByHN($hn);

if ($patient) {
    echo json_encode(["success" => true, "patient" => $patient]);
} else {
    echo json_encode(["success" => false, "message" => "ไม่พบข้อมูลผู้ป่วย"]);
}
?>',

    'call_next_queue.php' => '<?php
header("Content-Type: application/json; charset=utf-8");

require_once "../config/database.php";
require_once "../includes/auth.php";
require_once "../includes/queue_manager.php";

if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "ไม่ได้รับอนุญาต"]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit;
}

$queue_manager = new QueueManager();
$user = $auth->getCurrentUser();

$result = $queue_manager->callNextQueue($user["id"]);
echo json_encode($result);
?>',

    'delete_queue.php' => '<?php
header("Content-Type: application/json; charset=utf-8");

require_once "../config/database.php";
require_once "../includes/auth.php";
require_once "../includes/queue_manager.php";

if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "ไม่ได้รับอนุญาต"]);
    exit;
}

if (!$auth->hasRole(["admin", "pharmacist"])) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "ไม่มีสิทธิ์ลบคิว"]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit;
}

$queue_id = $_POST["queue_id"] ?? 0;

if (!$queue_id) {
    echo json_encode(["success" => false, "message" => "ไม่พบรหัสคิว"]);
    exit;
}

$queue_manager = new QueueManager();

if ($queue_manager->deleteQueue($queue_id)) {
    echo json_encode(["success" => true, "message" => "ลบคิวสำเร็จ"]);
} else {
    echo json_encode(["success" => false, "message" => "ไม่สามารถลบคิวได้"]);
}
?>',

    'get_queue_details.php' => '<?php
header("Content-Type: application/json; charset=utf-8");

require_once "../config/database.php";
require_once "../includes/auth.php";
require_once "../includes/queue_manager.php";

if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "ไม่ได้รับอนุญาต"]);
    exit;
}

$queue_id = $_GET["queue_id"] ?? 0;

if (!$queue_id) {
    echo json_encode(["success" => false, "message" => "ไม่พบรหัสคิว"]);
    exit;
}

$queue_manager = new QueueManager();
$queue = $queue_manager->getQueueById($queue_id);

if ($queue) {
    // Format dates
    $queue["created_at"] = date("d/m/Y H:i:s", strtotime($queue["created_at"]));
    if ($queue["called_at"]) {
        $queue["called_at"] = date("d/m/Y H:i:s", strtotime($queue["called_at"]));
    }
    if ($queue["completed_at"]) {
        $queue["completed_at"] = date("d/m/Y H:i:s", strtotime($queue["completed_at"]));
    }
    
    echo json_encode(["success" => true, "queue" => $queue]);
} else {
    echo json_encode(["success" => false, "message" => "ไม่พบข้อมูลคิว"]);
}
?>',

    'get_queue_stats.php' => '<?php
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
?>'
];

// สร้างไฟล์ AJAX
foreach ($ajax_files as $filename => $content) {
    $filepath = "ajax/$filename";
    if (!file_exists($filepath)) {
        file_put_contents($filepath, $content);
        echo "✅ สร้างไฟล์ $filepath เรียบร้อย\n";
    } else {
        echo "📄 ไฟล์ $filepath มีอยู่แล้ว\n";
    }
}

// สร้างไฟล์ .htaccess สำหรับ security
$htaccess_content = 'RewriteEngine On

# ป้องกันการเข้าถึงไฟล์ config
<Files "database.php">
    Order Allow,Deny
    Deny from all
</Files>

# ป้องกันการเข้าถึงไฟล์ .htaccess
<Files ".htaccess">
    Order Allow,Deny
    Deny from all
</Files>

# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>

# Set cache headers
<IfModule mod_expires.c>
    ExpiresActive on
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
</IfModule>';

if (!file_exists('.htaccess')) {
    file_put_contents('.htaccess', $htaccess_content);
    echo "✅ สร้างไฟล์ .htaccess เรียบร้อย\n";
} else {
    echo "📄 ไฟล์ .htaccess มีอยู่แล้ว\n";
}

echo "\n🎉 สร้างโครงสร้างโปรเจกต์เรียบร้อยแล้ว!\n";
echo "📋 ขั้นตอนต่อไป:\n";
echo "1. สร้างฐานข้อมูลด้วย schema.sql\n";
echo "2. ตรวจสอบการตั้งค่าในไฟล์ config/database.php\n";
echo "3. เปิดเว็บไซต์ที่ index.php\n";
?>