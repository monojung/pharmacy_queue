<?php
$page_title = 'เกิดข้อผิดพลาดภายในเซิร์ฟเวอร์';

// Set 500 status
http_response_code(500);

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
            background: linear-gradient(135deg, #dc3545, #e83e8c);
            color: white;
            padding: 40px 30px;
        }
        
        .error-header i {
            font-size: 4rem;
            margin-bottom: 20px;
            display: block;
            animation: spin 2s linear infinite;
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
        
        .btn-report {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
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
        
        .btn-report:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255, 193, 7, 0.3);
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
            margin-bottom: 8px;
        }
        
        .error-details code {
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .error-card {
            animation: fadeIn 0.6s ease-out;
        }
        
        .retry-timer {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            color: #1976d2;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="card error-card">
            <div class="error-header">
                <i class="fas fa-cog"></i>
                <h1>เกิดข้อผิดพลาดภายในเซิร์ฟเวอร์</h1>
            </div>
            
            <div class="error-body">
                <div class="error-code">500</div>
                
                <div class="error-message">
                    ขออภัย เกิดข้อผิดพลาดภายในเซิร์ฟเวอร์<br>
                    กรุณาลองใหม่อีกครั้งหรือติดต่อผู้ดูแลระบบ
                </div>
                
                <div class="retry-timer">
                    <i class="fas fa-clock me-2"></i>
                    ระบบจะรีเฟรชอัตโนมัติใน <strong id="countdown">10</strong> วินาที
                </div>
                
                <div class="mb-4">
                    <a href="javascript:location.reload()" class="btn-back">
                        <i class="fas fa-redo me-2"></i>ลองใหม่
                    </a>
                    
                    <a href="<?php echo url('index.php'); ?>" class="btn-home">
                        <i class="fas fa-home me-2"></i>กลับหน้าหลัก
                    </a>
                    
                    <a href="mailto:admin@hospital.com?subject=เกิดข้อผิดพลาด 500&body=URL: <?php echo urlencode($_SERVER['REQUEST_URI'] ?? ''); ?>%0ATime: <?php echo urlencode(date('Y-m-d H:i:s')); ?>" class="btn-report">
                        <i class="fas fa-bug me-2"></i>รายงานปัญหา
                    </a>
                </div>
                
                <div class="error-details">
                    <h6><i class="fas fa-tools me-2"></i>วิธีแก้ไขปัญหา</h6>
                    <ul>
                        <li>รีเฟรชหน้าเว็บ (กด F5 หรือ Ctrl+R)</li>
                        <li>ลองเข้าใช้งานใหม่ในอีกสักครู่</li>
                        <li>ตรวจสอบการเชื่อมต่ออินเทอร์เน็ต</li>
                        <li>หากปัญหายังคงอยู่ กรุณาติดต่อผู้ดูแลระบบ</li>
                    </ul>
                </div>
                
                <div class="error-details mt-3">
                    <h6><i class="fas fa-info-circle me-2"></i>ข้อมูลเพิ่มเติม</h6>
                    <ul>
                        <li><strong>เวลา:</strong> <?php echo date('d/m/Y H:i:s'); ?></li>
                        <li><strong>URL:</strong> <code><?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? ''); ?></code></li>
                        <li><strong>Method:</strong> <code><?php echo htmlspecialchars($_SERVER['REQUEST_METHOD'] ?? ''); ?></code></li>
                        <li><strong>User Agent:</strong> <code><?php echo htmlspecialchars(substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 50)); ?>...</code></li>
                        <li><strong>Error ID:</strong> <code><?php echo uniqid(); ?></code></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto refresh countdown
        let countdown = 10;
        const countdownElement = document.getElementById('countdown');
        
        const countdownInterval = setInterval(() => {
            countdown--;
            countdownElement.textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(countdownInterval);
                location.reload();
            }
        }, 1000);
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            switch(e.key) {
                case 'r':
                case 'R':
                    if (e.ctrlKey) {
                        e.preventDefault();
                        location.reload();
                    }
                    break;
                case 'F5':
                    e.preventDefault();
                    location.reload();
                    break;
                case 'h':
                case 'H':
                    if (!e.ctrlKey && !e.altKey) {
                        window.location.href = '<?php echo url('index.php'); ?>';
                    }
                    break;
                case 'Escape':
                    clearInterval(countdownInterval);
                    break;
            }
        });
        
        // Stop countdown when user interacts
        document.addEventListener('click', function() {
            clearInterval(countdownInterval);
            countdownElement.textContent = 'หยุดแล้ว';
        });
        
        // Health check
        function checkServerHealth() {
            fetch('<?php echo url('index.php'); ?>', { method: 'HEAD' })
                .then(response => {
                    if (response.ok) {
                        // Server is back online
                        window.location.href = '<?php echo url('index.php'); ?>';
                    }
                })
                .catch(error => {
                    console.log('Server still down:', error);
                });
        }
        
        // Check server health every 30 seconds
        const healthCheckInterval = setInterval(checkServerHealth, 30000);
        
        // Clear intervals on page unload
        window.addEventListener('beforeunload', function() {
            clearInterval(countdownInterval);
            clearInterval(healthCheckInterval);
        });
        
        // Error reporting
        function reportError() {
            const errorData = {
                url: window.location.href,
                timestamp: new Date().toISOString(),
                userAgent: navigator.userAgent,
                errorType: '500 Internal Server Error'
            };
            
            // Log to console for debugging
            console.error('Server Error 500:', errorData);
            
            // Could send to error tracking service here
            // Example: sendToErrorTracker(errorData);
        }
        
        // Report error on page load
        reportError();
    </script>
</body>
</html>