<?php
require_once 'includes/auth.php';

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

// กำหนด redirect URL โดยใช้ฟังก์ชัน url ถ้ามี
if (!empty($redirect)) {
    // ป้องกัน open redirect vulnerability
    if (in_array($redirect, ['index', 'display', 'login'])) {
        $redirect_url = (function_exists('url') ? url($redirect . '.php') : $redirect . '.php') . '?message=' . $message;
    } else {
        $redirect_url = (function_exists('url') ? url('index.php') : 'index.php') . '?message=' . $message;
    }
} else {
    $redirect_url = (function_exists('url') ? url('index.php') : 'index.php') . '?message=' . $message;
}

// รีไดเร็ก
header('Location: ' . $redirect_url);
exit();
?>