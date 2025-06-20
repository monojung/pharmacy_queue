<?php
session_start();

// ตรวจสอบ path และใช้ path ที่ถูกต้อง
if (file_exists(__DIR__ . '/../config/database.php')) {
    require_once __DIR__ . '/../config/database.php';
} else {
    require_once 'config/database.php';
}

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

class Auth {
    private $conn;
    private $session_timeout = 3600; // 1 hour
    
    public function __construct() {
        $this->conn = getDB();
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
        session_start();
        
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
            $login_url = url('login.php');
            
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
            header('Location: ' . url('unauthorized.php'));
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
    
    // อัพเดทข้อมูลผู้ใช้
    public function updateUser($user_id, $data) {
        try {
            $allowed_fields = ['full_name', 'role', 'status'];
            $set_clauses = [];
            $params = [];
            
            foreach ($data as $field => $value) {
                if (in_array($field, $allowed_fields)) {
                    $set_clauses[] = "$field = :$field";
                    $params[":$field"] = $value;
                }
            }
            
            if (empty($set_clauses)) {
                return false;
            }
            
            $sql = "UPDATE users SET " . implode(', ', $set_clauses) . " WHERE id = :user_id";
            $params[':user_id'] = $user_id;
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                $this->logUserActivity($user_id, 'updated', 'อัพเดทข้อมูลผู้ใช้');
                
                // อัพเดท session ถ้าเป็นผู้ใช้ปัจจุบัน
                if ($user_id == $_SESSION['user_id']) {
                    if (isset($data['full_name'])) {
                        $_SESSION['full_name'] = $data['full_name'];
                    }
                    if (isset($data['role'])) {
                        $_SESSION['role'] = $data['role'];
                    }
                }
            }
            
            return $result;
        } catch(PDOException $e) {
            error_log("Update user error: " . $e->getMessage());
            return false;
        }
    }
    
    // เปลี่ยนรหัสผ่าน
    public function changePassword($user_id, $current_password, $new_password) {
        try {
            // ตรวจสอบรหัสผ่านเดิม
            $stmt = $this->conn->prepare("SELECT password FROM users WHERE id = :user_id");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($current_password, $user['password'])) {
                return false;
            }
            
            // อัพเดทรหัสผ่านใหม่
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare("UPDATE users SET password = :password WHERE id = :user_id");
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':user_id', $user_id);
            
            $result = $stmt->execute();
            
            if ($result) {
                $this->logUserActivity($user_id, 'password_changed', 'เปลี่ยนรหัสผ่าน');
            }
            
            return $result;
        } catch(PDOException $e) {
            error_log("Change password error: " . $e->getMessage());
            return false;
        }
    }
    
    // รับรายการผู้ใช้ทั้งหมด
    public function getAllUsers() {
        try {
            $stmt = $this->conn->prepare("
                SELECT id, username, full_name, role, status, created_at, last_login 
                FROM users 
                ORDER BY created_at DESC
            ");
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Get all users error: " . $e->getMessage());
            return [];
        }
    }
    
    // ลบผู้ใช้
    public function deleteUser($user_id) {
        try {
            // ไม่สามารถลบตัวเอง
            if ($user_id == $_SESSION['user_id']) {
                return false;
            }
            
            $stmt = $this->conn->prepare("DELETE FROM users WHERE id = :user_id");
            $stmt->bindParam(':user_id', $user_id);
            $result = $stmt->execute();
            
            if ($result) {
                $this->logUserActivity($_SESSION['user_id'], 'deleted_user', "ลบผู้ใช้ ID: $user_id");
            }
            
            return $result;
        } catch(PDOException $e) {
            error_log("Delete user error: " . $e->getMessage());
            return false;
        }
    }
    
    // บันทึกกิจกรรมผู้ใช้
    private function logUserActivity($user_id, $action, $description = '') {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO user_activity_log (user_id, action, description, ip_address, user_agent) 
                VALUES (:user_id, :action, :description, :ip_address, :user_agent)
            ");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':action', $action);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR'] ?? '');
            $stmt->bindParam(':user_agent', $_SERVER['HTTP_USER_AGENT'] ?? '');
            
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
            $stmt = $this->conn->prepare("
                INSERT INTO failed_login_log (username, ip_address, user_agent) 
                VALUES (:username, :ip_address, :user_agent)
            ");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR'] ?? '');
            $stmt->bindParam(':user_agent', $_SERVER['HTTP_USER_AGENT'] ?? '');
            
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
    
    // ตรวจสอบ brute force attack
    public function checkBruteForce($username, $max_attempts = 5, $lockout_time = 900) { // 15 minutes
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as attempts 
                FROM failed_login_log 
                WHERE username = :username 
                AND ip_address = :ip_address 
                AND created_at > DATE_SUB(NOW(), INTERVAL :lockout_time SECOND)
            ");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR'] ?? '');
            $stmt->bindParam(':lockout_time', $lockout_time);
            $stmt->execute();
            
            $result = $stmt->fetch();
            
            return $result['attempts'] >= $max_attempts;
        } catch(PDOException $e) {
            error_log("Check brute force error: " . $e->getMessage());
            return false;
        }
    }
    
    // รีเซ็ต failed login attempts
    public function resetFailedAttempts($username) {
        try {
            $stmt = $this->conn->prepare("
                DELETE FROM failed_login_log 
                WHERE username = :username 
                AND ip_address = :ip_address
            ");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR'] ?? '');
            
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Reset failed attempts error: " . $e->getMessage());
            return false;
        }
    }
    
    // รับสถิติผู้ใช้
    public function getUserStats($user_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    COUNT(*) as total_logins,
                    MAX(created_at) as last_activity,
                    MIN(created_at) as first_login
                FROM user_activity_log 
                WHERE user_id = :user_id 
                AND action = 'login'
            ");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch(PDOException $e) {
            error_log("Get user stats error: " . $e->getMessage());
            return [
                'total_logins' => 0,
                'last_activity' => null,
                'first_login' => null
            ];
        }
    }
    
    // ตรวจสอบความแข็งแรงของรหัสผ่าน
    public function validatePasswordStrength($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'รหัสผ่านต้องมีตัวอักษรพิมพ์ใหญ่อย่างน้อย 1 ตัว';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'รหัสผ่านต้องมีตัวอักษรพิมพ์เล็กอย่างน้อย 1 ตัว';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'รหัสผ่านต้องมีตัวเลขอย่างน้อย 1 ตัว';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}

// สร้าง instance สำหรับใช้งาน
$auth = new Auth();