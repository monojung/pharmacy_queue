<?php
require_once 'includes/auth.php';

// ฟังก์ชันสำหรับสร้าง URL
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $scriptName = $_SERVER['SCRIPT_NAME'];
    $basePath = dirname($scriptName);
    return $protocol . '://' . $host . ($basePath !== '/' ? $basePath : '');
}

function url($path = '') {
    $baseUrl = getBaseUrl();
    return $baseUrl . '/' . ltrim($path, '/');
}

// ตรวจสอบว่ามีการล็อกอินอยู่หรือไม่
$was_logged_in = $auth->isLoggedIn();

// ล็อกเอาต์
$auth->logout();

// ลบ remember me cookie
if (isset($_COOKIE['remember_user'])) {
    setcookie('remember_user', '', time() - 3600, '/');
}

// ตรวจสอบ redirect parameter
$redirect = $_GET['redirect'] ?? '';
$message = '';

if ($was_logged_in) {
    $message = 'logout_success';
} else {
    $message = 'already_logged_out';
}

// กำหนด redirect URL
if (!empty($redirect)) {
    // ป้องกัน open redirect vulnerability
    if (in_array($redirect, ['index', 'display', 'login'])) {
        $redirect_url = url($redirect . '.php') . '?message=' . $message;
    } else {
        $redirect_url = url('index.php') . '?message=' . $message;
    }
} else {
    $redirect_url = url('index.php') . '?message=' . $message;
}

// รีไดเร็ก
header('Location: ' . $redirect_url);
exit();
?>