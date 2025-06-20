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
                            <i class="fas fa-tachometer-alt me-2"></i>แดชบอร์ด
                        </h2>
                        <p class="text-muted mb-0">ภาพรวมระบบเรียกคิวรับยา</p>
                        <small class="text-success">
                            <i class="fas fa-circle me-1"></i>ออนไลน์ - 
                            ผู้ใช้: <?php echo htmlspecialchars($auth->getCurrentUser()['full_name']); ?>
                        </small>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <div class="btn-group" role="group">
                            <a href="manage_queue.php" class="btn btn-primary">
                                <i class="fas fa-list me-1"></i>จัดการคิว
                            </a>
                            <a href="../display.php" class="btn btn-success" target="_blank">
                                <i class="fas fa-tv me-1"></i>จอแสดงคิว
                            </a>
                            <?php if ($auth->hasRole(['admin'])): ?>
                            <a href="settings.php" class="btn btn-warning">
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
                    
                    <a href="manage_queue.php" class="btn btn-warning">
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
</div>

<!-- Recent Activity -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>กิจกรรมล่าสุด</h5>
            </div>
            <div class="card-body">
                <?php 
                $recent_completed = $queue_manager->getAllQueues('completed', null);
                $recent_completed = array_slice($recent_completed, 0, 10); // เอา 10 รายการล่าสุด
                if (!empty($recent_completed)): 
                ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>เลขคิว</th>
                                    <th>ชื่อผู้ป่วย</th>
                                    <th>เวลาที่เสร็จสิ้น</th>
                                    <th>สถานะ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_completed as $queue): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($queue['queue_number']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($queue['patient_name']); ?></td>
                                        <td><?php echo $queue['completed_at'] ? date('H:i:s', strtotime($queue['completed_at'])) : '-'; ?></td>
                                        <td>
                                            <span class="badge bg-success">เสร็จสิ้น</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-history fa-2x mb-2"></i>
                        <p class="mb-0">ยังไม่มีกิจกรรมล่าสุด</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Create Queue Modal -->
<div class="modal fade" id="createQueueModal" tabindex="-1" aria-labelledby="createQueueModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createQueueModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>สร้างคิวใหม่
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createQueueForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="patientHN" class="form-label">หมายเลข HN <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="patientHN" name="hn" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="patientName" class="form-label">ชื่อผู้ป่วย</label>
                        <input type="text" class="form-control" id="patientName" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="patientPhone" class="form-label">เบอร์โทรศัพท์</label>
                        <input type="tel" class="form-control" id="patientPhone" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="priority" class="form-label">ระดับความสำคัญ</label>
                        <select class="form-select" id="priority" name="priority">
                            <option value="normal">ปกติ</option>
                            <option value="urgent">ด่วน</option>
                            <option value="emergency">ฉุกเฉิน</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="medicineList" class="form-label">รายการยา</label>
                        <textarea class="form-control" id="medicineList" name="medicine_list" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">หมายเหตุ</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>สร้างคิว
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// สร้างกราฟสถิติรายสัปดาห์
const weeklyStats = <?php echo json_encode($weekly_stats); ?>;
const ctx = document.getElementById('weeklyChart').getContext('2d');

const chart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: weeklyStats.map(stat => {
            const date = new Date(stat.date);
            return date.toLocaleDateString('th-TH', { 
                weekday: 'short', 
                month: 'short', 
                day: 'numeric' 
            });
        }),
        datasets: [{
            label: 'คิวทั้งหมด',
            data: weeklyStats.map(stat => stat.total),
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.1
        }, {
            label: 'เสร็จสิ้น',
            data: weeklyStats.map(stat => stat.completed),
            borderColor: 'rgb(54, 162, 235)',
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        },
        plugins: {
            legend: {
                position: 'top',
            }
        }
    }
});

// ฟังก์ชันสร้างคิวใหม่
document.getElementById('createQueueForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('create_queue', '1');
    formData.append('hn', document.getElementById('patientHN').value);
    formData.append('medicine_list', document.getElementById('medicineList').value);
    formData.append('notes', document.getElementById('notes').value);
    formData.append('priority', document.getElementById('priority').value);
    
    fetch('../index.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        if (data.includes('สร้างคิวสำเร็จ')) {
            bootstrap.Modal.getInstance(document.getElementById('createQueueModal')).hide();
            location.reload();
        } else {
            alert('เกิดข้อผิดพลาดในการสร้างคิว');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
    });
});

// ฟังก์ชันค้นหาผู้ป่วย
document.getElementById('patientHN').addEventListener('input', function() {
    const hn = this.value;
    if (hn.length >= 3) {
        searchPatient(hn);
    } else {
        document.getElementById('patientName').value = '';
        document.getElementById('patientPhone').value = '';
    }
});

// Auto refresh every 30 seconds
setInterval(() => {
    if (!document.querySelector('.modal.show')) {
        location.reload();
    }
}, 30000);
</script>

<?php include '../includes/footer.php'; ?>