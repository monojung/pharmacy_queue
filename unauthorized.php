<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/queue_manager.php';

$queue_manager = new QueueManager();
$page_title = 'ไม่มีสิทธิ์เข้าถึง';
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
            max-width: 500px;
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
            background: linear-gradient(135deg, #dc3545, #e83e8c);
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
            color: #dc3545;
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
        
        .btn-login {
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
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(40, 167, 69, 0.3);
            color: white;
        }
        
        .error-details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            text-align: left;
        }
        
        .error-details h6 {
            color: #495057;
            margin-bottom: 15px;
        }
        
        .error-details ul {
            color: #6c757d;
            margin: 0;
            padding-left: 20px;
        }
        
        .error-details li {
            margin-bottom: 5px;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .error-card {
            animation: fadeIn 0.6s ease-out;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="card error-card">
            <div class="error-header">
                <i class="fas fa-shield-alt"></i>
                <h1>ไม่มีสิทธิ์เข้าถึง</h1>
            </div>
            
            <div class="error-body">
                <div class="error-code">403</div>
                
                <div class="error-message">
                    คุณไม่มีสิทธิ์เข้าถึงหน้านี้<br>
                    กรุณาติดต่อผู้ดูแลระบบเพื่อขอสิทธิ์เข้าใช้งาน
                </div>
                
                <div class="mb-4">
                    <a href="/index.php" class="btn-back">
                        <i class="fas fa-home me-2"></i>กลับสู่หน้าหลัก
                    </a>
                    
                    <?php if (!$auth->isLoggedIn()): ?>
                        <a href="/login.php" class="btn-login">
                            <i class="fas fa-sign-in-alt me-2"></i>เข้าสู่ระบบ
                        </a>
                    <?php else: ?>
                        <a href="/logout.php" class="btn-login">
                            <i class="fas fa-sign-out-alt me-2"></i>ออกจากระบบ
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="error-details">
                    <h6><i class="fas fa-info-circle me-2"></i>ข้อมูลเพิ่มเติม</h6>
                    <ul>
                        <li>หน้านี้ต้องการสิทธิ์พิเศษในการเข้าถึง</li>
                        <li>กรุณาตรวจสอบว่าคุณมีบัญชีผู้ใช้และสิทธิ์ที่เหมาะสม</li>
                        <li>หากคิดว่าเป็นข้อผิดพลาด กรุณาติดต่อผู้ดูแลระบบ</li>
                        <?php if ($auth->isLoggedIn()): ?>
                            <li>คุณล็อกอินในฐานะ: <strong><?php echo htmlspecialchars($auth->getCurrentUser()['full_name']); ?></strong></li>
                            <li>ระดับสิทธิ์ของคุณ: <strong><?php echo htmlspecialchars($auth->getCurrentUser()['role']); ?></strong></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="mt-4">
                    <small class="text-muted">
                        <i class="fas fa-clock me-1"></i>
                        เวลา: <?php echo date('d/m/Y H:i:s'); ?>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto redirect to home page after 10 seconds
        let countdown = 10;
        const countdownInterval = setInterval(() => {
            countdown--;
            if (countdown <= 0) {
                clearInterval(countdownInterval);
                window.location.href = '/index.php';
            }
        }, 1000);
        
        // Add keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                window.location.href = '/index.php';
            } else if (e.key === 'Escape') {
                window.history.back();
            }
        });
        
        // Show additional help on double click
        document.addEventListener('dblclick', function() {
            alert('คำแนะนำ:\n' +
                  '- กด Enter หรือ Space เพื่อกลับหน้าหลัก\n' +
                  '- กด Esc เพื่อกลับหน้าก่อนหน้า\n' +
                  '- ระบบจะเปลี่ยนหน้าอัตโนมัติใน 10 วินาที');
        });
    </script>
</body>
</html>