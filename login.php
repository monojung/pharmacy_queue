<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/queue_manager.php';

$queue_manager = new QueueManager();

// ถ้าล็อกอินแล้วให้ไปหน้า dashboard
if ($auth->isLoggedIn()) {
    $dashboard_url = function_exists('url') ? url('admin/dashboard.php') : '/admin/dashboard.php';
    header('Location: ' . $dashboard_url);
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error_message = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    } else {
        if ($auth->login($username, $password)) {
            $dashboard_url = function_exists('url') ? url('admin/dashboard.php') : '/admin/dashboard.php';
            header('Location: ' . $dashboard_url);
            exit();
        } else {
            $error_message = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
        }
    }
}

$page_title = 'เข้าสู่ระบบ';
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
        
        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 0 20px;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: none;
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, #2c5aa0, #4472c4);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .login-header h2 {
            margin: 0;
            font-weight: 600;
        }
        
        .login-header i {
            font-size: 3rem;
            margin-bottom: 15px;
            display: block;
        }
        
        .login-body {
            padding: 30px;
        }
        
        .form-floating {
            margin-bottom: 20px;
        }
        
        .form-control {
            border-radius: 15px;
            border: 2px solid #e9ecef;
            padding: 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #2c5aa0;
            box-shadow: 0 0 0 0.2rem rgba(44, 90, 160, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #2c5aa0, #4472c4);
            border: none;
            border-radius: 15px;
            padding: 15px;
            font-weight: 600;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(44, 90, 160, 0.3);
            color: white;
        }
        
        .alert {
            border-radius: 15px;
            border: none;
            margin-bottom: 20px;
        }
        
        .back-to-home {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-to-home a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .back-to-home a:hover {
            color: #f8f9fa;
            text-decoration: underline;
        }
        
        .loading {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
            margin-right: 10px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="card login-card">
            <div class="login-header">
                <i class="fas fa-pills"></i>
                <h2>เข้าสู่ระบบ</h2>
                <p class="mb-0">ระบบเรียกคิวรับยา</p>
            </div>
            
            <div class="login-body">
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="needs-validation" novalidate>
                    <div class="form-floating">
                        <input type="text" class="form-control" id="username" name="username" 
                               placeholder="ชื่อผู้ใช้" required>
                        <label for="username"><i class="fas fa-user me-2"></i>ชื่อผู้ใช้</label>
                        <div class="invalid-feedback">
                            กรุณากรอกชื่อผู้ใช้
                        </div>
                    </div>
                    
                    <div class="form-floating">
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="รหัสผ่าน" required>
                        <label for="password"><i class="fas fa-lock me-2"></i>รหัสผ่าน</label>
                        <div class="invalid-feedback">
                            กรุณากรอกรหัสผ่าน
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-login" id="loginBtn">
                        <div class="loading" id="loadingSpinner"></div>
                        <i class="fas fa-sign-in-alt me-2"></i>เข้าสู่ระบบ
                    </button>
                </form>
                
                <div class="text-center mt-4">
                    <small class="text-muted">
                        ผู้ดูแลระบบ: admin / password
                    </small>
                </div>
            </div>
        </div>
        
        <div class="back-to-home">
            <a href="<?php echo function_exists('url') ? url('index.php') : 'index.php'; ?>">
                <i class="fas fa-arrow-left me-2"></i>กลับสู่หน้าหลัก
            </a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Form validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        } else {
                            // Show loading
                            document.getElementById('loadingSpinner').style.display = 'inline-block';
                            document.getElementById('loginBtn').disabled = true;
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
        
        // Auto focus on username field
        document.getElementById('username').focus();
        
        // Enter key to submit
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.querySelector('form').dispatchEvent(new Event('submit'));
            }
        });
    </script>
</body>
</html>