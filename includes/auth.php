<?php
session_start();

// ตรวจสอบ path และใช้ path ที่ถูกต้อง
if (file_exists(__DIR__ . '/../config/database.php')) {
    require_once __DIR__ . '/../config/database.php';
} else {
    require_once 'config/database.php';
}

class Auth {
    private $conn;
    
    public function __construct() {
        $this->conn = getDB();
    }
    
    // ล็อกอิน
    public function login($username, $password) {
        try {
            $stmt = $this->conn->prepare("SELECT id, username, password, full_name, role FROM users WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                
                return true;
            }
            
            return false;
        } catch(PDOException $e) {
            return false;
        }
    }
    
    // ล็อกเอาต์
    public function logout() {
        session_destroy();
        return true;
    }
    
    // ตรวจสอบการล็อกอิน
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
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
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: /login.php');
            exit();
        }
    }
    
    // ตรวจสอบสิทธิ์และรีไดเร็ก
    public function requireRole($roles) {
        $this->requireLogin();
        
        if (!$this->hasRole($roles)) {
            header('Location: /unauthorized.php');
            exit();
        }
    }
    
    // สร้างผู้ใช้ใหม่
    public function createUser($username, $password, $full_name, $role = 'staff') {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $this->conn->prepare("INSERT INTO users (username, password, full_name, role) VALUES (:username, :password, :full_name, :role)");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':full_name', $full_name);
            $stmt->bindParam(':role', $role);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }
    
    // รับข้อมูลผู้ใช้ปัจจุบัน
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'full_name' => $_SESSION['full_name'],
            'role' => $_SESSION['role']
        ];
    }
}

// สร้าง instance สำหรับใช้งาน
$auth = new Auth();
?>