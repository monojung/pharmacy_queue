<?php
/**
 * Common functions for the pharmacy queue system
 */

// ฟังก์ชันสำหรับสร้าง Base URL
if (!function_exists('getBaseUrl')) {
    function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $basePath = dirname($scriptName);
        return $protocol . '://' . $host . ($basePath !== '/' ? $basePath : '');
    }
}

// ฟังก์ชันสำหรับสร้าง URL
if (!function_exists('url')) {
    function url($path = '') {
        $baseUrl = getBaseUrl();
        return $baseUrl . '/' . ltrim($path, '/');
    }
}

// ตรวจสอบหน้าปัจจุบัน
if (!function_exists('isCurrentPage')) {
    function isCurrentPage($page) {
        $current = basename($_SERVER['PHP_SELF'], '.php');
        return $current === $page;
    }
}

// ฟังก์ชันสำหรับแสดงข้อความแจ้งเตือน
if (!function_exists('showAlert')) {
    function showAlert($message, $type = 'info') {
        $alertClass = '';
        $icon = '';
        
        switch ($type) {
            case 'success':
                $alertClass = 'alert-success';
                $icon = 'fas fa-check-circle';
                break;
            case 'danger':
            case 'error':
                $alertClass = 'alert-danger';
                $icon = 'fas fa-exclamation-circle';
                break;
            case 'warning':
                $alertClass = 'alert-warning';
                $icon = 'fas fa-exclamation-triangle';
                break;
            default:
                $alertClass = 'alert-info';
                $icon = 'fas fa-info-circle';
        }
        
        return '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">
                    <i class="' . $icon . ' me-2"></i>' . htmlspecialchars($message) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
    }
}

// ฟังก์ชันสำหรับแปลงสถานะคิวเป็นข้อความ
if (!function_exists('getStatusText')) {
    function getStatusText($status) {
        $statusTexts = [
            'waiting' => 'รอเรียก',
            'preparing' => 'กำลังเตรียม',
            'ready' => 'พร้อมรับ',
            'completed' => 'เสร็จสิ้น',
            'cancelled' => 'ยกเลิก'
        ];
        
        return isset($statusTexts[$status]) ? $statusTexts[$status] : $status;
    }
}

// ฟังก์ชันสำหรับแปลงระดับความสำคัญเป็นข้อความ
if (!function_exists('getPriorityText')) {
    function getPriorityText($priority) {
        $priorityTexts = [
            'normal' => 'ปกติ',
            'urgent' => 'ด่วน',
            'emergency' => 'ฉุกเฉิน'
        ];
        
        return isset($priorityTexts[$priority]) ? $priorityTexts[$priority] : $priority;
    }
}

// ฟังก์ชันสำหรับ format วันที่เป็นภาษาไทย
if (!function_exists('formatThaiDate')) {
    function formatThaiDate($date, $format = 'full') {
        $timestamp = is_string($date) ? strtotime($date) : $date;
        
        if ($format === 'full') {
            return date('j', $timestamp) . ' ' . 
                   getThaiMonth(date('n', $timestamp)) . ' ' . 
                   (date('Y', $timestamp) + 543) . ' ' .
                   date('H:i', $timestamp) . ' น.';
        } else if ($format === 'date') {
            return date('j', $timestamp) . ' ' . 
                   getThaiMonth(date('n', $timestamp)) . ' ' . 
                   (date('Y', $timestamp) + 543);
        } else if ($format === 'time') {
            return date('H:i', $timestamp) . ' น.';
        }
        
        return date('Y-m-d H:i:s', $timestamp);
    }
}

// ฟังก์ชันสำหรับดึงชื่อเดือนภาษาไทย
if (!function_exists('getThaiMonth')) {
    function getThaiMonth($month) {
        $thaiMonths = [
            1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
            5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
            9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
        ];
        
        return isset($thaiMonths[$month]) ? $thaiMonths[$month] : '';
    }
}

// ฟังก์ชันสำหรับ sanitize input
if (!function_exists('sanitizeInput')) {
    function sanitizeInput($input) {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
}

// ฟังก์ชันสำหรับตรวจสอบ AJAX request
if (!function_exists('isAjaxRequest')) {
    function isAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}

// ฟังก์ชันสำหรับส่ง JSON response
if (!function_exists('jsonResponse')) {
    function jsonResponse($data, $httpCode = 200) {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ฟังก์ชันสำหรับตรวจสอบว่าเป็น POST request หรือไม่
if (!function_exists('isPostRequest')) {
    function isPostRequest() {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
}

// ฟังก์ชันสำหรับตรวจสอบว่าเป็น GET request หรือไม่
if (!function_exists('isGetRequest')) {
    function isGetRequest() {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }
}

// ฟังก์ชันสำหรับ redirect
if (!function_exists('redirect')) {
    function redirect($url, $permanent = false) {
        if ($permanent) {
            header('HTTP/1.1 301 Moved Permanently');
        }
        header('Location: ' . $url);
        exit;
    }
}
?>