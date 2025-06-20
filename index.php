<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/queue_manager.php';

$queue_manager = new QueueManager();
$page_title = 'หน้าหลัก';

// ประมวลผลการสร้างคิวใหม่
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_queue'])) {
    $hn = trim($_POST['hn'] ?? '');
    $medicine_list = trim($_POST['medicine_list'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $priority = $_POST['priority'] ?? 'normal';
    
    if (empty($hn)) {
        $error_message = 'กรุณากรอกหมายเลข HN';
    } else {
        $result = $queue_manager->createQueue($hn, $medicine_list, $notes, $priority);
        
        if ($result['success']) {
            $success_message = 'สร้างคิวสำเร็จ - หมายเลขคิว: ' . $result['queue_number'] . ' (' . $result['patient_name'] . ')';
        } else {
            $error_message = $result['message'];
        }
    }
}

// รับรายการคิววันนี้
$today_queues = $queue_manager->getAllQueues();
$queue_stats = $queue_manager->getQueueStats();

include 'includes/header.php';
?>

<!-- Hero Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-pills fa-4x text-primary mb-3"></i>
                <h1 class="display-4 mb-3">ระบบเรียกคิวรับยา</h1>
                <p class="lead text-muted"><?php echo $queue_manager->getSetting('hospital_name', 'โรงพยาบาล ABC'); ?></p>
                <div class="row mt-4">
                    <div class="col-md-6">
                        <h5 id="current-time" class="text-primary"></h5>
                    </div>
                    <div class="col-md-6">
                        <h6 id="current-date" class="text-muted"></h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-list-ol fa-2x text-primary mb-2"></i>
                <h3 class="card-title"><?php echo $queue_stats['total'] ?? 0; ?></h3>
                <p class="card-text">ทั้งหมด</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                <h3 class="card-title"><?php echo $queue_stats['waiting'] ?? 0; ?></h3>
                <p class="card-text">รอรับยา</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-cog fa-2x text-info mb-2"></i>
                <h3 class="card-title"><?php echo $queue_stats['preparing'] ?? 0; ?></h3>
                <p class="card-text">กำลังเตรียม</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-bell fa-2x text-success mb-2"></i>
                <h3 class="card-title"><?php echo $queue_stats['ready'] ?? 0; ?></h3>
                <p class="card-text">พร้อมรับยา</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-check fa-2x text-secondary mb-2"></i>
                <h3 class="card-title"><?php echo $queue_stats['completed'] ?? 0; ?></h3>
                <p class="card-text">เสร็จสิ้น</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-times fa-2x text-danger mb-2"></i>
                <h3 class="card-title"><?php echo $queue_stats['cancelled'] ?? 0; ?></h3>
                <p class="card-text">ยกเลิก</p>
            </div>
        </div>
    </div>
</div>

<!-- Create Queue Form -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>สร้างคิวใหม่</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success d-flex align-items-center" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="hn" class="form-label">หมายเลข HN</label>
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" class="form-control" id="hn" name="hn" 
                                   placeholder="กรอกหมายเลข HN" required 
                                   onkeyup="searchPatient(this.value)">
                            <div class="invalid-feedback">
                                กรุณากรอกหมายเลข HN
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="patient_name" class="form-label">ชื่อผู้ป่วย</label>
                        <input type="text" class="form-control" id="patient_name" readonly 
                               placeholder="ชื่อผู้ป่วยจะแสดงอัตโนมัติ">
                    </div>
                    
                    <div class="mb-3">
                        <label for="patient_phone" class="form-label">เบอร์โทรศัพท์</label>
                        <input type="text" class="form-control" id="patient_phone" readonly 
                               placeholder="เบอร์โทรศัพท์จะแสดงอัตโนมัติ">
                    </div>
                    
                    <div class="mb-3">
                        <label for="priority" class="form-label">ระดับความสำคัญ</label>
                        <select class="form-control" id="priority" name="priority">
                            <option value="normal">ปกติ</option>
                            <option value="urgent">ด่วน</option>
                            <option value="emergency">ฉุกเฉิน</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="medicine_list" class="form-label">รายการยา</label>
                        <textarea class="form-control" id="medicine_list" name="medicine_list" 
                                  rows="3" placeholder="ระบุรายการยา (ถ้ามี)"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">หมายเหตุ</label>
                        <textarea class="form-control" id="notes" name="notes" 
                                  rows="2" placeholder="หมายเหตุเพิ่มเติม (ถ้ามี)"></textarea>
                    </div>
                    
                    <button type="submit" name="create_queue" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>สร้างคิว
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>เมนูด่วน</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-3">
                    <a href="/display.php" class="btn btn-success btn-lg">
                        <i class="fas fa-tv me-2"></i>จอแสดงคิว
                    </a>
                    
                    <?php if ($auth->isLoggedIn()): ?>
                        <a href="/admin/dashboard.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-tachometer-alt me-2"></i>แดชบอร์ด
                        </a>
                        
                        <a href="/admin/manage_queue.php" class="btn btn-warning btn-lg">
                            <i class="fas fa-list me-2"></i>จัดการคิว
                        </a>
                    <?php else: ?>
                        <a href="/login.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>เข้าสู่ระบบ
                        </a>
                    <?php endif; ?>
                    
                    <button class="btn btn-info btn-lg" onclick="location.reload()">
                        <i class="fas fa-sync-alt me-2"></i>รีเฟรช
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Today's Queue Summary -->
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>สรุปคิววันนี้</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <div class="border-end">
                            <h3 class="text-warning"><?php echo $queue_stats['waiting'] ?? 0; ?></h3>
                            <small>รอรับยา</small>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <h3 class="text-success"><?php echo $queue_stats['ready'] ?? 0; ?></h3>
                        <small>พร้อมรับยา</small>
                    </div>
                    <div class="col-6">
                        <div class="border-end">
                            <h3 class="text-info"><?php echo $queue_stats['preparing'] ?? 0; ?></h3>
                            <small>กำลังเตรียม</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <h3 class="text-secondary"><?php echo $queue_stats['completed'] ?? 0; ?></h3>
                        <small>เสร็จสิ้น</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Current Queue Display -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-list-ul me-2"></i>คิววันนี้</h5>
                <div>
                    <button class="btn btn-outline-primary btn-sm" onclick="location.reload()">
                        <i class="fas fa-sync-alt me-1"></i>รีเฟรช
                    </button>
                    <a href="/display.php" class="btn btn-success btn-sm">
                        <i class="fas fa-eye me-1"></i>ดูจอแสดงคิว
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($today_queues)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">ยังไม่มีคิววันนี้</h5>
                        <p class="text-muted">กรุณาสร้างคิวใหม่ด้านบน</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($today_queues as $queue): ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card queue-card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div class="queue-number"><?php echo htmlspecialchars($queue['queue_number']); ?></div>
                                            <div>
                                                <span class="badge priority-<?php echo $queue['priority']; ?> me-1">
                                                    <?php echo htmlspecialchars($queue['priority_text']); ?>
                                                </span>
                                                <span class="badge status-<?php echo $queue['status']; ?>">
                                                    <?php echo htmlspecialchars($queue['status_text']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <h6 class="mb-2">
                                            <i class="fas fa-user me-2"></i>
                                            <?php echo htmlspecialchars($queue['patient_name']); ?>
                                        </h6>
                                        
                                        <p class="text-muted mb-2">
                                            <i class="fas fa-id-card me-2"></i>
                                            HN: <?php echo htmlspecialchars($queue['hn']); ?>
                                        </p>
                                        
                                        <?php if (!empty($queue['medicine_list'])): ?>
                                            <p class="text-muted mb-2">
                                                <i class="fas fa-pills me-2"></i>
                                                <?php echo htmlspecialchars(substr($queue['medicine_list'], 0, 50)); ?>
                                                <?php if (strlen($queue['medicine_list']) > 50) echo '...'; ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo date('H:i น.', strtotime($queue['created_at'])); ?>
                                        </small>
                                        
                                        <?php if ($queue['status'] === 'ready'): ?>
                                            <div class="mt-3">
                                                <button class="btn btn-success btn-sm" 
                                                        onclick="callQueue(<?php echo $queue['id']; ?>, '<?php echo $queue['queue_number']; ?>', '<?php echo htmlspecialchars($queue['patient_name']); ?>')">
                                                    <i class="fas fa-volume-up me-1"></i>เรียกคิว
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
    </div>
</div>

