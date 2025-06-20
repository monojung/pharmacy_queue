<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ตรวจสอบ path และใช้ path ที่ถูกต้อง
if (file_exists(__DIR__ . '/../config/database.php')) {
    require_once __DIR__ . '/../config/database.php';
} else if (file_exists('config/database.php')) {
    require_once 'config/database.php';
} else {
    // Create inline database connection if config file not found
    if (!function_exists('getDB')) {
        function getDB() {
            static $db = null;
            if ($db === null) {
                try {
                    $dsn = "mysql:host=localhost;dbname=pharmacy_queue;charset=utf8mb4";
                    $db = new PDO($dsn, 'root', '', [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]);
                } catch(PDOException $e) {
                    throw new Exception("Database connection failed: " . $e->getMessage());
                }
            }
            return $db;
        }
    }
}

class Auth {
    private $conn;
    private $session_timeout = 3600; // 1 hour
    
    public function __construct() {
        try {
            $this->conn = getDB();
        } catch(Exception $e) {
            error_log("Auth initialization error: " . $e->getMessage());
            throw $e;
        }
        $this->checkSessionTimeout();
    }
    
    // ตรวจสอบ session timeout
    private function checkSessionTimeout() {
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > $this->session_timeout) {
                $this->logout();
                return false;
            }
        }
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    // ล็อกอิน
    public function login($username, $password) {
        try {
            $stmt = $this->conn->prepare("
                SELECT id, username, password, full_name, role, created_at 
                FROM users 
                WHERE username = :username AND status = 'active'
            ");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // บันทึก session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();
                $_SESSION['last_activity'] = time();
                
                // บันทึก login log
                $this->logUserActivity($user['id'], 'login', 'เข้าสู่ระบบ');
                
                // อัพเดท last login
                $this->updateLastLogin($user['id']);
                
                return true;
            }
            
            // บันทึก failed login attempt
            $this->logFailedLogin($username);
            
            return false;
        } catch(PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }
    
    // ล็อกเอาต์
    public function logout() {
        if ($this->isLoggedIn()) {
            // บันทึก logout log
            $this->logUserActivity($_SESSION['user_id'], 'logout', 'ออกจากระบบ');
        }
        
        // ลบ session
        session_unset();
        session_destroy();
        
        // เริ่ม session ใหม่
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return true;
    }
    
    // ตรวจสอบการล็อกอิน
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && $this->checkSessionTimeout();
    }
    
    // ตรวจสอบสิทธิ์
    public function hasRole($roles) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        if (is_string($roles)) {
            $roles = [$roles];
        }
        
        return in_array($_SESSION['role'], $roles);
    }
    
    // รีไดเร็กไปหน้าล็อกอิน
    public function requireLogin($redirect_url = null) {
        if (!$this->isLoggedIn()) {
            $current_url = $_SERVER['REQUEST_URI'] ?? '';
            $login_url = $this->getBaseUrl() . '/login.php';
            
            if ($redirect_url) {
                $login_url .= '?redirect=' . urlencode($redirect_url);
            } elseif (!empty($current_url) && $current_url !== '/') {
                $login_url .= '?redirect=' . urlencode($current_url);
            }
            
            header('Location: ' . $login_url);
            exit();
        }
    }
    
    // ตรวจสอบสิทธิ์และรีไดเร็ก
    public function requireRole($roles) {
        $this->requireLogin();
        
        if (!$this->hasRole($roles)) {
            header('Location: ' . $this->getBaseUrl() . '/unauthorized.php');
            exit();
        }
    }
    
    // สร้างผู้ใช้ใหม่
    public function createUser($username, $password, $full_name, $role = 'staff') {
        try {
            // ตรวจสอบว่า username ซ้ำหรือไม่
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            if ($stmt->fetch()) {
                return false; // username ซ้ำ
            }
            
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $this->conn->prepare("
                INSERT INTO users (username, password, full_name, role, status) 
                VALUES (:username, :password, :full_name, :role, 'active')
            ");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':full_name', $full_name);
            $stmt->bindParam(':role', $role);
            
            $result = $stmt->execute();
            
            if ($result) {
                $user_id = $this->conn->lastInsertId();
                $this->logUserActivity($user_id, 'created', 'สร้างบัญชีผู้ใช้');
            }
            
            return $result;
        } catch(PDOException $e) {
            error_log("Create user error: " . $e->getMessage());
            return false;
        }
    }
    
    // รับข้อมูลผู้ใช้ปัจจุบัน
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        try {
            $stmt = $this->conn->prepare("
                SELECT id, username, full_name, role, created_at, last_login 
                FROM users 
                WHERE id = :user_id
            ");
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch(PDOException $e) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'full_name' => $_SESSION['full_name'],
                'role' => $_SESSION['role']
            ];
        }
    }
    
    // บันทึกกิจกรรมผู้ใช้
    private function logUserActivity($user_id, $action, $description = '') {
        try {
            // Check if table exists first
            $stmt = $this->conn->prepare("SHOW TABLES LIKE 'user_activity_log'");
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                // Table doesn't exist, skip logging
                return true;
            }
            
            // Store variables to pass by reference
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $stmt = $this->conn->prepare("
                INSERT INTO user_activity_log (user_id, action, description, ip_address, user_agent) 
                VALUES (:user_id, :action, :description, :ip_address, :user_agent)
            ");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':action', $action);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':ip_address', $ip_address);
            $stmt->bindParam(':user_agent', $user_agent);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            // Silent fail for logging
            error_log("Log activity error: " . $e->getMessage());
            return false;
        }
    }
    
    // บันทึก failed login
    private function logFailedLogin($username) {
        try {
            // Check if table exists first
            $stmt = $this->conn->prepare("SHOW TABLES LIKE 'failed_login_log'");
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                // Table doesn't exist, skip logging
                return true;
            }
            
            // Store variables to pass by reference
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $stmt = $this->conn->prepare("
                INSERT INTO failed_login_log (username, ip_address, user_agent) 
                VALUES (:username, :ip_address, :user_agent)
            ");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':ip_address', $ip_address);
            $stmt->bindParam(':user_agent', $user_agent);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            // Silent fail for logging
            error_log("Log failed login error: " . $e->getMessage());
            return false;
        }
    }
    
    // อัพเดท last login
    private function updateLastLogin($user_id) {
        try {
            $stmt = $this->conn->prepare("UPDATE users SET last_login = NOW() WHERE id = :user_id");
            $stmt->bindParam(':user_id', $user_id);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Update last login error: " . $e->getMessage());
            return false;
        }
    }
    
    // Get base URL (ใช้ฟังก์ชันจาก database.php ถ้ามี)
    private function getBaseUrl() {
        if (function_exists('getBaseUrl')) {
            return getBaseUrl();
        }
        
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
}

// สร้าง instance สำหรับใช้งาน
try {
    $auth = new Auth();
} catch(Exception $e) {
    // Create a mock auth object if database is not available
    $auth = new class {
        public function isLoggedIn() { return false; }
        public function requireLogin() { 
            header('Location: /login.php');
            exit();
        }
        public function hasRole($roles) { return false; }
        public function getCurrentUser() { return null; }
    };
}