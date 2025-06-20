<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/queue_manager.php';

// ตรวจสอบการล็อกอิน
$auth->requireLogin();

$queue_manager = new QueueManager();
$page_title = 'แดชบอร์ด';

// รับสถิติ
$today_stats = $queue_manager->getQueueStats();
$today_queues = $queue_manager->getAllQueues();

// สถิติรายสัปดาห์
$weekly_stats = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stats = $queue_manager->getQueueStats($date);
    $weekly_stats[] = [
        'date' => $date,
        'total' => $stats['total'] ?? 0,
        'completed' => $stats['completed'] ?? 0
    ];
}

include '../includes/header.php';
?>

<!-- Dashboard Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h2 class="mb-0">
                            <i class="fas fa-tachometer-alt me-2"></i>แดशบอร์ด
                        </h2>
                        <p class="text-muted mb-0">ภาพรวมระบบเรียกคิวรับยา</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <div class="btn-group" role="group">
                            <a href="/admin/manage_queue.php" class="btn btn-primary">
                                <i class="fas fa-list me-1"></i>จัดการคิว
                            </a>
                            <a href="/display.php" class="btn btn-success">
                                <i class="fas fa-tv me-1"></i>จอแสดงคิว
                            </a>
                            <?php if ($auth->hasRole(['admin'])): ?>
                            <a href="/admin/settings.php" class="btn btn-warning">
                                <i class="fas fa-cog me-1"></i>การตั้งค่า
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center bg-primary text-white">
            <div class="card-body">
                <i class="fas fa-list-ol fa-3x mb-3"></i>
                <h3><?php echo $today_stats['total'] ?? 0; ?></h3>
                <p class="mb-0">คิวทั้งหมดวันนี้</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-center bg-warning text-white">
            <div class="card-body">
                <i class="fas fa-clock fa-3x mb-3"></i>
                <h3><?php echo $today_stats['waiting'] ?? 0; ?></h3>
                <p class="mb-0">รอรับยา</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-center bg-info text-white">
            <div class="card-body">
                <i class="fas fa-cog fa-3x mb-3"></i>
                <h3><?php echo $today_stats['preparing'] ?? 0; ?></h3>
                <p class="mb-0">กำลังเตรียมยา</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-center bg-success text-white">
            <div class="card-body">
                <i class="fas fa-check fa-3x mb-3"></i>
                <h3><?php echo $today_stats['completed'] ?? 0; ?></h3>
                <p class="mb-0">เสร็จสิ้น</p>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>การดำเนินการด่วน</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createQueueModal">
                        <i class="fas fa-plus me-2"></i>สร้างคิวใหม่
                    </button>
                    
                    <a href="/admin/manage_queue.php" class="btn btn-warning">
                        <i class="fas fa-edit me-2"></i>จัดการคิว
                    </a>
                    
                    <button class="btn btn-success" onclick="callNextQueue()">
                        <i class="fas fa-volume-up me-2"></i>เรียกคิวถัดไป
                    </button>
                    
                    <button class="btn btn-info" onclick="location.reload()">
                        <i class="fas fa-sync-alt me-2"></i>รีเฟรชข้อมูล
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>สถิติรายสัปดาห์</h5>
            </div>
            <div class="card-body">
                <canvas id="weeklyChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Current Queue Status -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-bell me-2"></i>คิวที่กำลังเรียก</h5>
            </div>
            <div class="card-body">
                <?php 
                $calling_queues = $queue_manager->getAllQueues('ready');
                if (!empty($calling_queues)): 
                ?>
                    <?php foreach ($calling_queues as $queue): ?>
                        <div class="alert alert-success d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">คิว <?php echo htmlspecialchars($queue['queue_number']); ?></h6>
                                <p class="mb-0"><?php echo htmlspecialchars($queue['patient_name']); ?></p>
                            </div>
                            <div>
                                <button class="btn btn-sm btn-primary" 
                                        onclick="callQueue(<?php echo $queue['id']; ?>, '<?php echo $queue['queue_number']; ?>', '<?php echo htmlspecialchars($queue['patient_name']); ?>')">
                                    <i class="fas fa-volume-up"></i>
                                </button>
                                <button class="btn btn-sm btn-success" 
                                        onclick="updateQueueStatus(<?php echo $queue['id']; ?>, 'completed')">
                                    <i class="fas fa-check"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-bell-slash fa-2x mb-2"></i>
                        <p class="mb-0">ไม่มีคิวที่กำลังเรียก</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-clock me-2"></i>คิวที่รอ</h5>
            </div>
            <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                <?php 
                $waiting_queues = $queue_manager->getAllQueues('waiting');
                if (!empty($waiting_queues)): 
                ?>
                    <?php foreach (array_slice($waiting_queues, 0, 5) as $queue): ?>
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                <strong><?php echo htmlspecialchars($queue['queue_number']); ?></strong>
                                - <?php echo htmlspecialchars($queue['patient_name']); ?>
                                <?php if ($queue['priority'] != 'normal'): ?>
                                    <span class="badge priority-<?php echo $queue['priority']; ?> ms-1">
                                        <?php echo $queue['priority_text']; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <button class="btn btn-sm btn-outline-primary" 
                                        onclick="updateQueueStatus(<?php echo $queue['id']; ?>, 'preparing')">
                                    <i class="fas fa-play"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (count($waiting_queues) > 5): ?>
                        <div class="text-center mt-2">
                            <small class="text-muted">และอีก <?php echo count($waiting_queues) - 5; ?> คิว...</small>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <p class="mb-0">ไม่มีคิวที่รอ</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div