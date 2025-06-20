<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/queue_manager.php';

$queue_manager = new QueueManager();
$page_title = 'ไม่พบหน้าที่ต้องการ';

// Set 404 status
http_response_code(404);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - ระบบเรียกคิวรับยา</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .error-container {
            max-width: 600px;
            width: 100%;
            padding: 0 20px;
        }
        
        .error-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: none;
            overflow: hidden;
            text-align: center;
        }
        
        .error-header {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            color: white;
            padding: 40px 30px;
        }
        
        .error-header i {
            font-size: 4rem;
            margin-bottom: 20px;
            display: block;
        }
        
        .error-header h1 {
            margin: 0;
            font-weight: 600;
            font-size: 2rem;
        }
        
        .error-body {
            padding: 40px 30px;
        }
        
        .error-code {
            font-size: 4rem;
            font-weight: 800;
            color: #ffc107;
            margin-bottom: 20px;
        }
        
        .error-message {
            font-size: 1.2rem;
            color: #6c757d;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .btn-back {
            background: linear-gradient(135deg, #2c5aa0, #4472c4);
            border: none;
            border-radius: 15px;
            padding: 15px 30px;
            font-weight: 600;
            color: white;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            margin: 0 10px 10px 0;
        }
        
        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(44, 90, 160, 0.3);
            color: white;
        }
        
        .btn-home {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            border-radius: 15px;
            padding: 15px 30px;
            font-weight: 600;
            color: white;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            margin: 0 10px 10px 0;
        }
        
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(40, 167, 69, 0.3);
            color: white;
        }
        
        .suggestions {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            text-align: left;
        }
        
        .suggestions h6 {
            color: #495057;
            margin-bottom: 15px;
        }
        
        .suggestions ul {
            color: #6c757d;
            margin: 0;
            padding-left: 20px;
        }
        
        .suggestions li {
            margin-bottom: 8px;
        }
        
        .suggestions a {
            color: #2c5aa0;
            text-decoration: none;
            font-weight: 500;
        }
        
        .suggestions a:hover {
            text-decoration: underline;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }
        
        .error-code {
            animation: bounce 2s infinite;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="card error-card">
            <div class="error-header">
                <i class="fas fa-search"></i>
                <h1>ไม่พบหน้าที่ต้องการ</h1>
            </div>
            
            <div class="error-body">
                <div class="error-code">404</div>
                
                <div class="error-message">
                    ขออภัย ไม่พบหน้าที่คุณต้องการ<br>
                    URL อาจไม่ถูกต้องหรือหน้านี้อาจถูกย้ายแล้ว
                </div>
                
                <div class="mb-4">
                    <a href="javascript:history.back()" class="btn-back">
                        <i class="fas fa-arrow-left me-2"></i>ย้อนกลับ
                    </a>
                    
                    <a href="index.php" class="btn-home">
                        <i class="fas fa-home me-2"></i>กลับหน้าหลัก
                    </a>
                </div>
                
                <div class="suggestions">
                    <h6><i class="fas fa-lightbulb me-2"></i>หน้าที่คุณอาจต้องการ</h6>
                    <ul>
                        <li><a href="index.php">หน้าหลัก - สร้างคิวใหม่</a></li>
                        <li><a href="display.php">จอแสดงคิว</a></li>
                        <?php if (isset($auth) && $auth->isLoggedIn()): ?>
                            <li><a href="admin/dashboard.php">แดชบอร์ด</a></li>
                            <li><a href="admin/manage_queue.php">จัดการคิว</a></li>
                            <?php if ($auth->hasRole(['admin'])): ?>
                                <li><a href="admin/settings.php">การตั้งค่าระบบ</a></li>
                            <?php endif; ?>
                        <?php else: ?>
                            <li><a href="login.php">เข้าสู่ระบบ</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="mt-4">
                    <small class="text-muted">
                        <i class="fas fa-clock me-1"></i>
                        เวลา: <?php echo date('d/m/Y H:i:s'); ?><br>
                        <i class="fas fa-link me-1"></i>
                        URL: <?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? ''); ?>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Search suggestion
        let searchTimeout;
        
        // Add keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                window.location.href = 'index.php';
            } else if (e.key === 'Escape') {
                window.history.back();
            } else if (e.key === 'h' || e.key === 'H') {
                window.location.href = 'index.php';
            } else if (e.key === 'd' || e.key === 'D') {
                window.location.href = 'display.php';
            }
        });
        
        // Show help on question mark
        document.addEventListener('keydown', function(e) {
            if (e.key === '?' || (e.shiftKey && e.key === '/')) {
                alert('แป้นพิมพ์ลัด:\n' +
                      'Enter/Space - กลับหน้าหลัก\n' +
                      'Esc - ย้อนกลับ\n' +
                      'H - หน้าหลัก\n' +
                      'D - จอแสดงคิว\n' +
                      '? - ช่วยเหลือ');
            }
        });
        
        // Auto-complete search
        const urlPath = window.location.pathname;
        const suggestions = {
            'dashboard': 'admin/dashboard.php',
            'manage': 'admin/manage_queue.php',
            'queue': 'admin/manage_queue.php',
            'setting': 'admin/settings.php',
            'profile': 'admin/profile.php',
            'display': 'display.php',
            'login': 'login.php',
            'home': 'index.php'
        };
        
        // Check if URL contains common misspellings and suggest corrections
        for (const [keyword, correctPath] of Object.entries(suggestions)) {
            if (urlPath.toLowerCase().includes(keyword)) {
                setTimeout(() => {
                    if (confirm(`คุณหมายถึง "${correctPath}" ใช่ไหม?`)) {
                        window.location.href = correctPath;
                    }
                }, 2000);
                break;
            }
        }
    </script>
</body>
</html>