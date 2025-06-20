<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/queue_manager.php';

// ตรวจสอบการล็อกอิน
$auth->requireLogin();

$queue_manager = new QueueManager();
$page_title = 'โปรไฟล์ผู้ใช้';

$user = $auth->getCurrentUser();
$message = '';
$error_message = '';

// ประมวลผลการอัพเดทโปรไฟล์
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    
    if (empty($full_name)) {
        $error_message = 'กรุณากรอกชื่อ-นามสกุล';
    } else {
        try {
            $stmt = getDB()->prepare("UPDATE users SET full_name = :full_name WHERE id = :id");
            $stmt->bindParam(':full_name', $full_name);
            $stmt->bindParam(':id', $user['id']);
            
            if ($stmt->execute()) {
                $_SESSION['full_name'] = $full_name;
                $user['full_name'] = $full_name;
                $message = 'อัพเดทโปรไฟล์เรียบร้อยแล้ว';
            } else {
                $error_message = 'เกิดข้อผิดพลาดในการอัพเดทโปรไฟล์';
            }
        } catch(PDOException $e) {
            $error_message = 'เกิดข้อผิดพลาดในการอัพเดทโปรไฟล์';
        }
    }
}

// ประมวลผลการเปลี่ยนรหัสผ่าน
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } elseif ($new_password !== $confirm_password) {
        $error_message = 'รหัสผ่านใหม่และรหัสผ่านยืนยันไม่ตรงกัน';
    } elseif (strlen($new_password) < 6) {
        $error_message = 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร';
    } else {
        try {
            // ตรวจสอบรหัสผ่านเดิม
            $stmt = getDB()->prepare("SELECT password FROM users WHERE id = :id");
            $stmt->bindParam(':id', $user['id']);
            $stmt->execute();
            $user_data = $stmt->fetch();
            
            if ($user_data && password_verify($current_password, $user_data['password'])) {
                // อัพเดทรหัสผ่านใหม่
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = getDB()->prepare("UPDATE users SET password = :password WHERE id = :id");
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':id', $user['id']);
                
                if ($stmt->execute()) {
                    $message = 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว';
                } else {
                    $error_message = 'เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน';
                }
            } else {
                $error_message = 'รหัสผ่านเดิมไม่ถูกต้อง';
            }
        } catch(PDOException $e) {
            $error_message = 'เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน';
        }
    }
}

// ดึงสถิติการใช้งานของผู้ใช้
try {
    $stmt = getDB()->prepare("
        SELECT 
            COUNT(*) as total_calls,
            DATE(MAX(created_at)) as last_activity
        FROM queue_calls 
        WHERE called_by = :user_id
    ");
    $stmt->bindParam(':user_id', $user['id']);
    $stmt->execute();
    $user_stats = $stmt->fetch();
} catch(PDOException $e) {
    $user_stats = ['total_calls' => 0, 'last_activity' => null];
}

include '../includes/header.php';
?>

<!-- Page Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h2 class="mb-0">
                            <i class="fas fa-user-edit me-2"></i>โปรไฟล์ผู้ใช้
                        </h2>
                        <p class="text-muted mb-0">จัดการข้อมูลส่วนตัวและการตั้งค่าบัญชี</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <a href="/admin/dashboard.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-1"></i>กลับสู่แดชบอร์ด
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($message)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error_message); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row">
    <!-- Profile Information -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-user me-2"></i>ข้อมูลส่วนตัว
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="username" class="form-label">ชื่อผู้ใช้</label>
                        <input type="text" class="form-control" id="username" 
                               value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                        <div class="form-text">ไม่สามารถเปลี่ยนชื่อผู้ใช้ได้</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="full_name" class="form-label">ชื่อ-นามสกุล</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" 
                               value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        <div class="invalid-feedback">กรุณากรอกชื่อ-นามสกุล</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="role" class="form-label">บทบาท</label>
                        <input type="text" class="form-control" id="role" 
                               value="<?php 
                                   echo $user['role'] === 'admin' ? 'ผู้ดูแลระบบ' : 
                                       ($user['role'] === 'pharmacist' ? 'เภสัชกร' : 'เจ้าหน้าที่'); 
                               ?>" disabled>
                        <div class="form-text">ติดต่อผู้ดูแลระบบเพื่อเปลี่ยนบทบาท</div>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>อัพเดทโปรไฟล์
                    </button>
                </form>
            </div>
        </div>
        
        <!-- User Statistics -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-chart-bar me-2"></i>สถิติการใช้งาน
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="border-end">
                            <h3 class="text-primary"><?php echo $user_stats['total_calls'] ?? 0; ?></h3>
                            <small class="text-muted">จำนวนครั้งที่เรียกคิว</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <h3 class="text-success">
                            <?php 
                            if ($user_stats['last_activity']) {
                                echo date('d/m/Y', strtotime($user_stats['last_activity'])); 
                            } else {
                                echo '-';
                            }
                            ?>
                        </h3>
                        <small class="text-muted">กิจกรรมล่าสุด</small>
                    </div>
                </div>
                
                <hr>
                
                <div class="row text-center">
                    <div class="col-12">
                        <small class="text-muted">
                            <i class="fas fa-calendar me-1"></i>
                            เข้าร่วมระบบเมื่อ: 
                            <?php echo date('d/m/Y H:i', strtotime($user['created_at'] ?? 'now')); ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Change Password -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-lock me-2"></i>เปลี่ยนรหัสผ่าน
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="current_password" class="form-label">รหัสผ่านเดิม</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="current_password" 
                                   name="current_password" required>
                            <button class="btn btn-outline-secondary" type="button" 
                                    onclick="togglePassword('current_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback">กรุณากรอกรหัสผ่านเดิม</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">รหัสผ่านใหม่</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="new_password" 
                                   name="new_password" minlength="6" required>
                            <button class="btn btn-outline-secondary" type="button" 
                                    onclick="togglePassword('new_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback">รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร</div>
                        <div class="form-text">รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">ยืนยันรหัสผ่านใหม่</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirm_password" 
                                   name="confirm_password" minlength="6" required>
                            <button class="btn btn-outline-secondary" type="button" 
                                    onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback">กรุณายืนยันรหัสผ่าน</div>
                    </div>
                    
                    <button type="submit" name="change_password" class="btn btn-warning">
                        <i class="fas fa-key me-2"></i>เปลี่ยนรหัสผ่าน
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Account Security -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-shield-alt me-2"></i>ความปลอดภัยบัญชี
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12 mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">การล็อกอิน</h6>
                                <small class="text-muted">ล็อกอินล่าสุด: วันนี้</small>
                            </div>
                            <div>
                                <span class="badge bg-success">
                                    <i class="fas fa-check me-1"></i>ปลอดภัย
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12 mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">รหัสผ่าน</h6>
                                <small class="text-muted">แนะนำให้เปลี่ยนรหัสผ่านเป็นระยะ</small>
                            </div>
                            <div>
                                <span class="badge bg-info">
                                    <i class="fas fa-info me-1"></i>ปกติ
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">เซสชัน</h6>
                                <small class="text-muted">การเชื่อมต่อปัจจุบัน</small>
                            </div>
                            <div>
                                <button class="btn btn-outline-danger btn-sm" onclick="logoutAllSessions()">
                                    <i class="fas fa-sign-out-alt me-1"></i>ออกจากทุกเซสชัน
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Activity Log -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2"></i>กิจกรรมล่าสุด
                </h5>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-marker bg-success"></div>
                        <div class="timeline-content">
                            <h6 class="mb-1">เข้าสู่ระบบ</h6>
                            <small class="text-muted">วันนี้ เวลา <?php echo date('H:i'); ?></small>
                        </div>
                    </div>
                    
                    <?php if ($user_stats['total_calls'] > 0): ?>
                    <div class="timeline-item">
                        <div class="timeline-marker bg-info"></div>
                        <div class="timeline-content">
                            <h6 class="mb-1">เรียกคิวล่าสุด</h6>
                            <small class="text-muted">
                                <?php echo $user_stats['last_activity'] ? date('d/m/Y', strtotime($user_stats['last_activity'])) : 'ไม่มีข้อมูล'; ?>
                            </small>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="timeline-item">
                        <div class="timeline-marker bg-secondary"></div>
                        <div class="timeline-content">
                            <h6 class="mb-1">สร้างบัญชี</h6>
                            <small class="text-muted">เข้าร่วมระบบ</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Additional Settings -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-cogs me-2"></i>การตั้งค่าเพิ่มเติม
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <i class="fas fa-volume-up fa-2x text-primary mb-2"></i>
                                <h6>การแจ้งเตือนเสียง</h6>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="soundNotification" checked>
                                    <label class="form-check-label" for="soundNotification">เปิดใช้งาน</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <i class="fas fa-sync-alt fa-2x text-success mb-2"></i>
                                <h6>รีเฟรชอัตโนมัติ</h6>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="autoRefresh" checked>
                                    <label class="form-check-label" for="autoRefresh">เปิดใช้งาน</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <i class="fas fa-moon fa-2x text-warning mb-2"></i>
                                <h6>โหมดมืด</h6>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="darkMode">
                                    <label class="form-check-label" for="darkMode">เปิดใช้งาน</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <button class="btn btn-outline-primary" onclick="saveUserPreferences()">
                        <i class="fas fa-save me-1"></i>บันทึกการตั้งค่า
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 10px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -25px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #dee2e6;
}

.timeline-content h6 {
    margin-bottom: 5px;
    color: #495057;
}

.timeline-content small {
    color: #6c757d;
}
</style>

<?php
$page_scripts = '
<script>
// Toggle Password Visibility
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const button = field.nextElementSibling;
    const icon = button.querySelector("i");
    
    if (field.type === "password") {
        field.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
    } else {
        field.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    }
}

// Validate Password Match
document.getElementById("confirm_password").addEventListener("input", function() {
    const newPassword = document.getElementById("new_password").value;
    const confirmPassword = this.value;
    
    if (newPassword !== confirmPassword) {
        this.setCustomValidity("รหัสผ่านไม่ตรงกัน");
    } else {
        this.setCustomValidity("");
    }
});

// Logout All Sessions
function logoutAllSessions() {
    Swal.fire({
        title: "ออกจากทุกเซสชัน",
        text: "คุณต้องการออกจากการเชื่อมต่อทั้งหมดหรือไม่?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "ออกจากระบบ",
        cancelButtonText: "ยกเลิก"
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "/logout.php";
        }
    });
}

// Save User Preferences
function saveUserPreferences() {
    const soundNotification = document.getElementById("soundNotification").checked;
    const autoRefresh = document.getElementById("autoRefresh").checked;
    const darkMode = document.getElementById("darkMode").checked;
    
    // Save to localStorage
    localStorage.setItem("userPreferences", JSON.stringify({
        soundNotification,
        autoRefresh,
        darkMode
    }));
    
    // Apply dark mode if enabled
    if (darkMode) {
        document.body.classList.add("dark-mode");
    } else {
        document.body.classList.remove("dark-mode");
    }
    
    Swal.fire({
        icon: "success",
        title: "บันทึกแล้ว",
        text: "บันทึกการตั้งค่าเรียบร้อยแล้ว",
        timer: 2000,
        showConfirmButton: false
    });
}

// Load User Preferences
function loadUserPreferences() {
    const preferences = localStorage.getItem("userPreferences");
    if (preferences) {
        const prefs = JSON.parse(preferences);
        document.getElementById("soundNotification").checked = prefs.soundNotification;
        document.getElementById("autoRefresh").checked = prefs.autoRefresh;
        document.getElementById("darkMode").checked = prefs.darkMode;
        
        if (prefs.darkMode) {
            document.body.classList.add("dark-mode");
        }
    }
}

// Load preferences on page load
document.addEventListener("DOMContentLoaded", loadUserPreferences);

// Form validation
(function() {
    "use strict";
    window.addEventListener("load", function() {
        var forms = document.getElementsByClassName("needs-validation");
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener("submit", function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add("was-validated");
            }, false);
        });
    }, false);
})();
</script>
';

include '../includes/footer.php';
?>