<?php
// ตั้งค่าฐานข้อมูล
$db_config = [
    'host' => 'localhost',
    'dbname' => 'pharmacy_queue',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4'
];

// Global database connection variable
$db_connection = null;

function getDB() {
    global $db_connection, $db_config;
    
    if ($db_connection === null) {
        try {
            $dsn = "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset={$db_config['charset']}";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            $db_connection = new PDO($dsn, $db_config['username'], $db_config['password'], $options);
            
        } catch(PDOException $e) {
            // Log error instead of displaying it
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("การเชื่อมต่อฐานข้อมูลไม่สำเร็จ");
        }
    }
    
    return $db_connection;
}

// Base URL functions
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $script = $_SERVER['SCRIPT_NAME'];
    $path = dirname($script);
    
    // Remove /admin from path if present
    if (strpos($path, '/admin') !== false) {
        $path = str_replace('/admin', '', $path);
    }
    
    return $protocol . $host . ($path === '/' ? '' : $path);
}

function url($path = '') {
    $base_url = getBaseUrl();
    return rtrim($base_url, '/') . '/' . ltrim($path, '/');
}

// Test database connection
function testDatabaseConnection() {
    try {
        $db = getDB();
        return ['success' => true, 'message' => 'การเชื่อมต่อฐานข้อมูลสำเร็จ'];
    } catch(Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
?>