<?php
// Include common functions only if not already included
if (!function_exists('isCurrentPage')) {
    if (file_exists(__DIR__ . '/functions.php')) {
        require_once __DIR__ . '/functions.php';
    } else if (file_exists('includes/functions.php')) {
        require_once 'includes/functions.php';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'ระบบเรียกคิวรับยา'; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <?php 
    // สร้าง URL สำหรับ CSS
    $css_url = '';
    if (function_exists('url')) {
        $css_url = url('assets/css/style.css');
    } else {
        $css_url = 'assets/css/style.css';
    }
    ?>
    <link rel="stylesheet" href="<?php echo $css_url; ?>">
    
    <style>
        :root {
            --primary-color: #2c5aa0;
            --secondary-color: #4472c4;
            --success-color: #198754;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #0dcaf0;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }
        
        body {
            font-family: 'Prompt', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .navbar {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color) !important;
            font-size: 1.5rem;
        }
        
        .navbar-nav .nav-link {
            font-weight: 500;
            color: var(--dark-color) !important;
            transition: all 0.3s ease;
            margin: 0 5px;
            border-radius: 25px;
            padding: 8px 16px !important;
        }
        
        .navbar-nav .nav-link:hover {
            background: var(--primary-color);
            color: white !important;
            transform: translateY(-2px);
        }
        
        .navbar-nav .nav-link.active {
            background: var(--primary-color);
            color: white !important;
        }
        
        .main-content {
            padding: 20px 0;
            min-height: calc(100vh - 160px);
        }
        
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 20px 20px 0 0 !important;
            padding: 20px;
            border: none;
        }
        
        .btn {
            border-radius: 25px;
            padding: 10px 25px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }
        
        .btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #dc3545, #e83e8c);
        }
        
        .alert {
            border-radius: 15px;
            border: none;
            padding: 15px 20px;
        }
        
        .form-control {
            border-radius: 15px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(44, 90, 160, 0.25);
        }
        
        .table {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .table thead th {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            font-weight: 600;
            padding: 15px;
        }
        
        .table tbody td {
            padding: 15px;
            vertical-align: middle;
            border-color: #e9ecef;
        }
        
        .queue-card {
            border-left: 5px solid var(--primary-color);
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        
        .queue-card:hover {
            border-left-color: var(--secondary-color);
        }
        
        .queue-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .status-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .status-waiting {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-preparing {
            background: #cce5ff;
            color: #0066cc;
        }
        
        .status-ready {
            background: #d4edda;
            color: #155724;
        }
        
        .status-completed {
            background: #e2e3e5;
            color: #383d41;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .priority-emergency {
            background: linear-gradient(135deg, #dc3545, #e83e8c);
            color: white;
            animation: pulse 2s infinite;
        }
        
        .priority-urgent {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            color: white;
        }
        
        .priority-normal {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .search-box {
            position: relative;
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        .search-box input {
            padding-left: 45px;
        }
        
        @media (max-width: 768px) {
            .navbar-nav {
                text-align: center;
            }
            
            .queue-number {
                font-size: 1.5rem;
            }
            
            .card {
                margin-bottom: 20px;
            }
        }
    </style>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <?php
            // สร้าง URL สำหรับ brand link
            $brand_url = '';
            if (function_exists('url')) {
                $brand_url = url('index.php');
            } else {
                $brand_url = 'index.php';
            }
            ?>
            <a class="navbar-brand" href="<?php echo $brand_url; ?>">
                <i class="fas fa-pills me-2"></i>
                <?php echo isset($queue_manager) ? $queue_manager->getSetting('hospital_name', 'โรงพยาบาล ABC') : 'โรงพยาบาล ABC'; ?> - 
                <?php echo isset($queue_manager) ? $queue_manager->getSetting('pharmacy_name', 'ห้องยา') : 'ห้องยา'; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo (function_exists('isCurrentPage') && isCurrentPage('index')) ? 'active' : ''; ?>" href="<?php echo function_exists('url') ? url('index.php') : 'index.php'; ?>">
                            <i class="fas fa-home me-1"></i>หน้าหลัก
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (function_exists('isCurrentPage') && isCurrentPage('display')) ? 'active' : ''; ?>" href="<?php echo function_exists('url') ? url('display.php') : 'display.php'; ?>">
                            <i class="fas fa-tv me-1"></i>จอแสดงคิว
                        </a>
                    </li>
                    <?php if (isset($auth) && $auth->isLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (function_exists('isCurrentPage') && isCurrentPage('dashboard')) ? 'active' : ''; ?>" href="<?php echo function_exists('url') ? url('admin/dashboard.php') : 'admin/dashboard.php'; ?>">
                            <i class="fas fa-tachometer-alt me-1"></i>แดชบอร์ด
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (function_exists('isCurrentPage') && isCurrentPage('manage_queue')) ? 'active' : ''; ?>" href="<?php echo function_exists('url') ? url('admin/manage_queue.php') : 'admin/manage_queue.php'; ?>">
                            <i class="fas fa-list me-1"></i>จัดการคิว
                        </a>
                    </li>
                    <?php if ($auth->hasRole(['admin'])): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (function_exists('isCurrentPage') && isCurrentPage('settings')) ? 'active' : ''; ?>" href="<?php echo function_exists('url') ? url('admin/settings.php') : 'admin/settings.php'; ?>">
                            <i class="fas fa-cog me-1"></i>การตั้งค่า
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if (isset($auth) && $auth->isLoggedIn()): ?>
                        <?php $user = $auth->getCurrentUser(); ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($user['full_name']); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?php echo function_exists('url') ? url('admin/profile.php') : 'admin/profile.php'; ?>"><i class="fas fa-user-edit me-2"></i>โปรไฟล์</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo function_exists('url') ? url('logout.php') : 'logout.php'; ?>"><i class="fas fa-sign-out-alt me-2"></i>ออกจากระบบ</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo function_exists('url') ? url('login.php') : 'login.php'; ?>">
                                <i class="fas fa-sign-in-alt me-1"></i>เข้าสู่ระบบ
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">