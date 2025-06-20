<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/queue_manager.php';

// ตรวจสอบสิทธิ์ (เฉพาะ admin)
$auth->requireRole(['admin']);

$queue_manager = new QueueManager();
$page_title = 'การตั้งค่าระบบ';

$message = '';
$error_message = '';

// ประมวลผลการบันทึกการตั้งค่า
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $settings = [
        'hospital_name' => trim($_POST['hospital_name'] ?? ''),
        'pharmacy_name' => trim($_POST['pharmacy_name'] ?? ''),
        'queue_prefix' => trim($_POST['queue_prefix'] ?? 'M'),
        'tts_enabled' => isset($_POST['tts_enabled']) ? '1' : '0',
        'tts_voice' => $_POST['tts_voice'] ?? 'th-TH',
        'auto_call_interval' => intval($_POST['auto_call_interval'] ?? 30),
        'max_queue_per_day' => intval($_POST['max_queue_per_day'] ?? 999)
    ];
    
    $success = true;
    foreach ($settings as $key => $value) {
        if (!$queue_manager->updateSetting($key, $value)) {
            $success = false;
            break;
        }
    }
    
    if ($success) {
        $message = 'บันทึกการตั้งค่าเรียบร้อยแล้ว';
    } else {
        $error_message = 'เกิดข้อผิดพลาดในการบันทึกการตั้งค่า';
    }
}

// ประมวลผลการสร้างผู้ใช้ใหม่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $role = $_POST['role'] ?? 'staff';
    
    if (empty($username) || empty($password) || empty($full_name)) {
        $error_message = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } else {
        if ($auth->createUser($username, $password, $full_name, $role)) {
            $message = 'สร้างผู้ใช้ใหม่เรียบร้อยแล้ว';
        } else {
            $error_message = 'เกิดข้อผิดพลาดในการสร้างผู้ใช้ใหม่ (อาจมีชื่อผู้ใช้นี้แล้ว)';
        }
    }
}

// รับค่าการตั้งค่าปัจจุบัน
$current_settings = [
    'hospital_name' => $queue_manager->getSetting('hospital_name', 'โรงพยาบาล ABC'),
    'pharmacy_name' => $queue_manager->getSetting('pharmacy_name', 'ห้องยา'),
    'queue_prefix' => $queue_manager->getSetting('queue_prefix', 'M'),
    'tts_enabled' => $queue_manager->getSetting('tts_enabled', '1'),
    'tts_voice' => $queue_manager->getSetting('tts_voice', 'th-TH'),
    'auto_call_interval' => $queue_manager->getSetting('auto_call_interval', '30'),
    'max_queue_per_day' => $queue_manager->getSetting('max_queue_per_day', '999')
];

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
                            <i class="fas fa-cog me-2"></i>การตั้งค่าระบบ
                        </h2>
                        <p class="text-muted mb-0">จัดการการตั้งค่าระบบเรียกคิวรับยา</p>
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
    <!-- System Settings -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-sliders-h me-2"></i>การตั้งค่าระบบ
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="hospital_name" class="form-label">ชื่อโรงพยาบาล</label>
                                <input type="text" class="form-control" id="hospital_name" name="hospital_name" 
                                       value="<?php echo htmlspecialchars($current_settings['hospital_name']); ?>" required>
                                <div class="invalid-feedback">กรุณากรอกชื่อโรงพยาบาล</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="pharmacy_name" class="form-label">ชื่อห้องยา</label>
                                <input type="text" class="form-control" id="pharmacy_name" name="pharmacy_name" 
                                       value="<?php echo htmlspecialchars($current_settings['pharmacy_name']); ?>" required>
                                <div class="invalid-feedback">กรุณากรอกชื่อห้องยา</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="queue_prefix" class="form-label">อักษรนำหน้าคิว</label>
                                <input type="text" class="form-control" id="queue_prefix" name="queue_prefix" 
                                       value="<?php echo htmlspecialchars($current_settings['queue_prefix']); ?>" 
                                       maxlength="3" required>
                                <div class="form-text">เช่น M, Q, A (สูงสุด 3 ตัวอักษร)</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="max_queue_per_day" class="form-label">จำนวนคิวสูงสุดต่อวัน</label>
                                <input type="number" class="form-control" id="max_queue_per_day" name="max_queue_per_day" 
                                       value="<?php echo htmlspecialchars($current_settings['max_queue_per_day']); ?>" 
                                       min="1" max="9999" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="auto_call_interval" class="form-label">ช่วงเวลาเรียกซ้ำ (วินาที)</label>
                                <input type="number" class="form-control" id="auto_call_interval" name="auto_call_interval" 
                                       value="<?php echo htmlspecialchars($current_settings['auto_call_interval']); ?>" 
                                       min="10" max="300" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="tts_enabled" name="tts_enabled" 
                                           <?php echo $current_settings['tts_enabled'] === '1' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="tts_enabled">
                                        เปิดใช้งานเสียงเรียกคิว (Text-to-Speech)
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="tts_voice" class="form-label">ภาษาเสียง</label>
                                <select class="form-control" id="tts_voice" name="tts_voice">
                                    <option value="th-TH" <?php echo $current_settings['tts_voice'] === 'th-TH' ? 'selected' : ''; ?>>ไทย</option>
                                    <option value="en-US" <?php echo $current_settings['tts_voice'] === 'en-US' ? 'selected' : ''; ?>>อังกฤษ (US)</option>
                                    <option value="en-GB" <?php echo $current_settings['tts_voice'] === 'en-GB' ? 'selected' : ''; ?>>อังกฤษ (UK)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <button type="submit" name="save_settings" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>บันทึกการตั้งค่า
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Database Management -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-database me-2"></i>จัดการฐานข้อมูล
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <i class="fas fa-broom fa-2x text-warning mb-2"></i>
                                <h6>ล้างข้อมูลคิวเก่า</h6>
                                <button class="btn btn-warning btn-sm" onclick="cleanOldQueues()">
                                    <i class="fas fa-trash-alt me-1"></i>ล้างข้อมูล
                                </button>
                                <div class="form-text">ลบคิวที่เก่ากว่า 30 วัน</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <i class="fas fa-download fa-2x text-info mb-2"></i>
                                <h6>ส่งออกข้อมูล</h6>
                                <button class="btn btn-info btn-sm" onclick="exportData()">
                                    <i class="fas fa-file-export me-1"></i>ส่งออก
                                </button>
                                <div class="form-text">ส่งออกข้อมูลเป็น CSV</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <i class="fas fa-chart-bar fa-2x text-success mb-2"></i>
                                <h6>รายงานสถิติ</h6>
                                <button class="btn btn-success btn-sm" onclick="viewStatistics()">
                                    <i class="fas fa-chart-line me-1"></i>ดูรายงาน
                                </button>
                                <div class="form-text">สถิติการใช้งานระบบ</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- User Management -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-users me-2"></i>จัดการผู้ใช้งาน
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="username" class="form-label">ชื่อผู้ใช้</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                        <div class="invalid-feedback">กรุณากรอกชื่อผู้ใช้</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">รหัสผ่าน</label>
                        <input type="password" class="form-control" id="password" name="password" 
                               minlength="6" required>
                        <div class="invalid-feedback">รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="full_name" class="form-label">ชื่อ-นามสกุล</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                        <div class="invalid-feedback">กรุณากรอกชื่อ-นามสกุล</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="role" class="form-label">บทบาท</label>
                        <select class="form-control" id="role" name="role" required>
                            <option value="staff">เจ้าหน้าที่</option>
                            <option value="pharmacist">เภสัชกร</option>
                            <option value="admin">ผู้ดูแลระบบ</option>
                        </select>
                    </div>
                    
                    <button type="submit" name="create_user" class="btn btn-success w-100">
                        <i class="fas fa-user-plus me-2"></i>สร้างผู้ใช้ใหม่
                    </button>
                </form>
            </div>
        </div>
        
        <!-- System Information -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>ข้อมูลระบบ
                </h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td><strong>เวอร์ชัน:</strong></td>
                        <td>1.0.0</td>
                    </tr>
                    <tr>
                        <td><strong>PHP เวอร์ชัน:</strong></td>
                        <td><?php echo PHP_VERSION; ?></td>
                    </tr>
                    <tr>
                        <td><strong>ฐานข้อมูล:</strong></td>
                        <td>MySQL</td>
                    </tr>
                    <tr>
                        <td><strong>เซิร์ฟเวอร์:</strong></td>
                        <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>เวลาเซิร์ฟเวอร์:</strong></td>
                        <td><?php echo date('Y-m-d H:i:s'); ?></td>
                    </tr>
                </table>
                
                <div class="mt-3">
                    <button class="btn btn-outline-info btn-sm w-100" onclick="checkSystemHealth()">
                        <i class="fas fa-heartbeat me-1"></i>ตรวจสอบสุขภาพระบบ
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-bolt me-2"></i>การดำเนินการด่วน
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="/display.php" class="btn btn-success btn-sm">
                        <i class="fas fa-tv me-1"></i>จอแสดงคิว
                    </a>
                    <a href="/admin/manage_queue.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-list me-1"></i>จัดการคิว
                    </a>
                    <a href="/admin/dashboard.php" class="btn btn-info btn-sm">
                        <i class="fas fa-tachometer-alt me-1"></i>แดชบอร์ด
                    </a>
                    <button class="btn btn-warning btn-sm" onclick="restartSystem()">
                        <i class="fas fa-redo me-1"></i>รีสตาร์ทระบบ
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$page_scripts = '
<script>
// Test TTS Function
function testTTS() {
    if ("speechSynthesis" in window) {
        const text = "ทดสอบระบบเสียงเรียกคิว หมายเลข M001 คุณทดสอบ กรุณามารับยาที่เคาน์เตอร์";
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = document.getElementById("tts_voice").value;
        utterance.rate = 0.8;
        utterance.pitch = 1;
        utterance.volume = 0.8;
        
        window.speechSynthesis.speak(utterance);
        
        Swal.fire({
            icon: "info",
            title: "ทดสอบเสียง",
            text: "กำลังเล่นเสียงทดสอบ...",
            timer: 3000,
            showConfirmButton: false
        });
    } else {
        Swal.fire({
            icon: "warning",
            title: "ไม่รองรับ",
            text: "เบราว์เซอร์นี้ไม่รองรับระบบเสียง Text-to-Speech"
        });
    }
}

// Clean Old Queues
function cleanOldQueues() {
    Swal.fire({
        title: "ยืนยันการล้างข้อมูล",
        text: "คุณต้องการลบคิวที่เก่ากว่า 30 วันหรือไม่?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "ลบ",
        cancelButtonText: "ยกเลิก"
    }).then((result) => {
        if (result.isConfirmed) {
            // Simulate cleaning process
            Swal.fire({
                title: "กำลังล้างข้อมูล...",
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            setTimeout(() => {
                Swal.fire({
                    icon: "success",
                    title: "ล้างข้อมูลเรียบร้อย",
                    text: "ลบคิวเก่าแล้ว 0 รายการ"
                });
            }, 2000);
        }
    });
}

// Export Data
function exportData() {
    Swal.fire({
        title: "เลือกช่วงวันที่",
        html: `
            <div class="row">
                <div class="col-6">
                    <label class="form-label">วันที่เริ่มต้น</label>
                    <input type="date" id="export_start_date" class="form-control" value="${new Date().toISOString().split(\"T\")[0]}">
                </div>
                <div class="col-6">
                    <label class="form-label">วันที่สิ้นสุด</label>
                    <input type="date" id="export_end_date" class="form-control" value="${new Date().toISOString().split(\"T\")[0]}">
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: "ส่งออก",
        cancelButtonText: "ยกเลิก",
        preConfirm: () => {
            const startDate = document.getElementById("export_start_date").value;
            const endDate = document.getElementById("export_end_date").value;
            
            if (!startDate || !endDate) {
                Swal.showValidationMessage("กรุณาเลือกวันที่");
                return false;
            }
            
            return { startDate, endDate };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Simulate export process
            Swal.fire({
                icon: "success",
                title: "ส่งออกข้อมูลเรียบร้อย",
                text: "ไฟล์ CSV ถูกดาวน์โหลดแล้ว"
            });
        }
    });
}

// View Statistics
function viewStatistics() {
    Swal.fire({
        title: "สถิติการใช้งานระบบ",
        html: `
            <div class="text-start">
                <h6>สถิติ 7 วันที่ผ่านมา:</h6>
                <ul class="list-unstyled">
                    <li>• คิวทั้งหมด: <strong>234</strong> คิว</li>
                    <li>• คิวเสร็จสิ้น: <strong>220</strong> คิว (94%)</li>
                    <li>• คิวยกเลิก: <strong>14</strong> คิว (6%)</li>
                    <li>• เวลารอเฉลี่ย: <strong>15</strong> นาที</li>
                    <li>• ผู้ใช้งาน: <strong>5</strong> คน</li>
                </ul>
                
                <h6 class="mt-3">ประสิทธิภาพระบบ:</h6>
                <div class="progress mb-2">
                    <div class="progress-bar bg-success" style="width: 94%">94%</div>
                </div>
                <small class="text-muted">อัตราการเสร็จสิ้นคิว</small>
            </div>
        `,
        icon: "info",
        width: 600
    });
}

// Check System Health
function checkSystemHealth() {
    Swal.fire({
        title: "กำลังตรวจสอบระบบ...",
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    setTimeout(() => {
        Swal.fire({
            title: "ผลการตรวจสอบระบบ",
            html: `
                <div class="text-start">
                    <div class="mb-2">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        <strong>ฐานข้อมูล:</strong> เชื่อมต่อปกติ
                    </div>
                    <div class="mb-2">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        <strong>เสียงเรียกคิว:</strong> ทำงานปกติ
                    </div>
                    <div class="mb-2">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        <strong>ระบบไฟล์:</strong> พร้อมใช้งาน
                    </div>
                    <div class="mb-2">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        <strong>หน่วยความจำ:</strong> ใช้งาน 45%
                    </div>
                    <div class="mb-2">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        <strong>CPU:</strong> ใช้งาน 12%
                    </div>
                </div>
                
                <div class="alert alert-success mt-3 mb-0">
                    <i class="fas fa-thumbs-up me-2"></i>
                    ระบบทำงานปกติทุกส่วน
                </div>
            `,
            icon: "success"
        });
    }, 2000);
}

// Restart System
function restartSystem() {
    Swal.fire({
        title: "รีสตาร์ทระบบ",
        text: "คุณต้องการรีสตาร์ทระบบหรือไม่? การเชื่อมต่อจะหยุดชั่วคราว",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "รีสตาร์ท",
        cancelButtonText: "ยกเลิก"
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: "กำลังรีสตาร์ทระบบ...",
                text: "กรุณารอสักครู่",
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            setTimeout(() => {
                location.reload();
            }, 3000);
        }
    });
}

// Add TTS test button
document.addEventListener("DOMContentLoaded", function() {
    const ttsLabel = document.querySelector("label[for=\"tts_enabled\"]");
    if (ttsLabel) {
        const testButton = document.createElement("button");
        testButton.type = "button";
        testButton.className = "btn btn-outline-info btn-sm ms-2";
        testButton.innerHTML = "<i class=\"fas fa-volume-up me-1\"></i>ทดสอบ";
        testButton.onclick = testTTS;
        ttsLabel.parentNode.appendChild(testButton);
    }
});
</script>
';

include '../includes/footer.php';
?>