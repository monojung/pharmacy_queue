<?php
require_once 'includes/auth.php';

// ล็อกเอาต์
$auth->logout();

// รีไดเร็กไปหน้าหลัก
header('Location: /index.php?message=logout_success');
exit();
?>