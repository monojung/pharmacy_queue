<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/queue_manager.php';

// ตรวจสอบการล็อกอิน
$auth->requireLogin();

$queue_manager = new QueueManager();
$page_title = 'จัดการคิว';

// ประมวลผลการดำเนินการ
$message = '';
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'called':
            $message = 'เรียกคิวสำเร็จ';
            break;
        case 'completed':
            $message = 'อัพเดทสถานะเสร็จสิ้น';
            break;
        case 'deleted':
            $message = 'ลบคิวเรียบร้อย';
            break;
    }
}

// รับรายการคิว
$filter_status = $_GET['status'] ?? '';
$filter_date = $_GET['date'] ?? date('Y-m-d');

$queues = $queue_manager->getAllQueues($filter_status, $filter_date);
$today_stats = $queue_manager->getQueueStats($filter_date);

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
                            <i class="fas fa-list-alt me-2"></i>จัดการคิว
                        </h2>
                        <p class="text-muted mb-0">จัดการและติดตามสถานะคิวรับยา</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <div class="btn-group" role="group">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createQueueModal">
                                <i class="fas fa-plus me-1"></i>สร้างคิวใหม่
                            </button>
                            <a href="/display.php" class="btn btn-success">
                                <i class="fas fa-tv me-1"></i>จอแสดงคิว
                            </a>
                            <button class="btn btn-info" onclick="location.reload()">
                                <i class="fas fa-sync-alt me-1"></i>รีเฟรช
                            </button>
                        </div>
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

<!-- Statistics Summary -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="card text-center bg-primary text-white">
            <div class="card-body py-3">
                <h4><?php echo $today_stats['total'] ?? 0; ?></h4>
                <small>ทั้งหมด</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center bg-warning text-white">
            <div class="card-body py-3">
                <h4><?php echo $today_stats['waiting'] ?? 0; ?></h4>
                <small>รอรับยา</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center bg-info text-white">
            <div class="card-body py-3">
                <h4><?php echo $today_stats['preparing'] ?? 0; ?></h4>
                <small>กำลังเตรียม</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center bg-success text-white">
            <div class="card-body py-3">
                <h4><?php echo $today_stats['ready'] ?? 0; ?></h4>
                <small>พร้อมรับยา</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center bg-secondary text-white">
            <div class="card-body py-3">
                <h4><?php echo $today_stats['completed'] ?? 0; ?></h4>
                <small>เสร็จสิ้น</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center bg-danger text-white">
            <div class="card-body py-3">
                <h4><?php echo $today_stats['cancelled'] ?? 0; ?></h4>
                <small>ยกเลิก</small>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="date" class="form-label">วันที่</label>
                        <input type="date" class="form-control" id="date" name="date" 
                               value="<?php echo htmlspecialchars($filter_date); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">สถานะ</label>
                        <select class="form-control" id="status" name="status">
                            <option value="">ทั้งหมด</option>
                            <option value="waiting" <?php echo $filter_status === 'waiting' ? 'selected' : ''; ?>>รอรับยา</option>
                            <option value="preparing" <?php echo $filter_status === 'preparing' ? 'selected' : ''; ?>>กำลังเตรียมยา</option>
                            <option value="ready" <?php echo $filter_status === 'ready' ? 'selected' : ''; ?>>พร้อมรับยา</option>
                            <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>เสร็จสิ้น</option>
                            <option value="cancelled" <?php echo $filter_status === 'cancelled' ? 'selected' : ''; ?>>ยกเลิก</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-search me-1"></i>ค้นหา
                        </button>
                        <a href="/admin/manage_queue.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>ล้าง
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Queue List -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>รายการคิว
                    <?php if ($filter_date !== date('Y-m-d')): ?>
                        - วันที่ <?php echo date('d/m/Y', strtotime($filter_date)); ?>
                    <?php else: ?>
                        - วันนี้
                    <?php endif; ?>
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($queues)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">ไม่พบข้อมูลคิว</h5>
                        <p class="text-muted">ไม่มีคิวในช่วงเวลาที่เลือก</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>หมายเลขคิว</th>
                                    <th>ผู้ป่วย</th>
                                    <th>HN</th>
                                    <th>ความสำคัญ</th>
                                    <th>สถานะ</th>
                                    <th>เวลาสร้าง</th>
                                    <th>รายการยา</th>
                                    <th>การดำเนินการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($queues as $queue): ?>
                                    <tr id="queue-row-<?php echo $queue['id']; ?>">
                                        <td>
                                            <span class="fw-bold fs-5 text-primary">
                                                <?php echo htmlspecialchars($queue['queue_number']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($queue['patient_name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($queue['hn']); ?></td>
                                        <td>
                                            <span class="badge priority-<?php echo $queue['priority']; ?>">
                                                <?php echo htmlspecialchars($queue['priority_text']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge status-<?php echo $queue['status']; ?>">
                                                <?php echo htmlspecialchars($queue['status_text']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small>
                                                <?php echo date('H:i', strtotime($queue['created_at'])); ?><br>
                                                <?php echo date('d/m/Y', strtotime($queue['created_at'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php if (!empty($queue['medicine_list'])): ?>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars(substr($queue['medicine_list'], 0, 30)); ?>
                                                    <?php if (strlen($queue['medicine_list']) > 30) echo '...'; ?>
                                                </small>
                                            <?php else: ?>
                                                <small class="text-muted">-</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <?php if ($queue['status'] === 'waiting'): ?>
                                                    <button class="btn btn-info" 
                                                            onclick="updateQueueStatus(<?php echo $queue['id']; ?>, 'preparing')"
                                                            title="เริ่มเตรียมยา">
                                                        <i class="fas fa-play"></i>
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <?php if ($queue['status'] === 'preparing'): ?>
                                                    <button class="btn btn-success" 
                                                            onclick="updateQueueStatus(<?php echo $queue['id']; ?>, 'ready')"
                                                            title="พร้อมรับยา">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <?php if ($queue['status'] === 'ready'): ?>
                                                    <button class="btn btn-primary" 
                                                            onclick="callQueue(<?php echo $queue['id']; ?>, '<?php echo $queue['queue_number']; ?>', '<?php echo htmlspecialchars($queue['patient_name']); ?>')"
                                                            title="เรียกคิว">
                                                        <i class="fas fa-volume-up"></i>
                                                    </button>
                                                    <button class="btn btn-success" 
                                                            onclick="updateQueueStatus(<?php echo $queue['id']; ?>, 'completed')"
                                                            title="เสร็จสิ้น">
                                                        <i class="fas fa-check-double"></i>
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <?php if (in_array($queue['status'], ['waiting', 'preparing', 'ready'])): ?>
                                                    <button class="btn btn-warning" 
                                                            onclick="updateQueueStatus(<?php echo $queue['id']; ?>, 'cancelled')"
                                                            title="ยกเลิก">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <?php if ($auth->hasRole(['admin', 'pharmacist'])): ?>
                                                    <button class="btn btn-danger" 
                                                            onclick="deleteQueue(<?php echo $queue['id']; ?>)"
                                                            title="ลบคิว">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <button class="btn btn-outline-secondary" 
                                                        onclick="viewQueueDetails(<?php echo $queue['id']; ?>)"
                                                        title="ดูรายละเอียด">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Create Queue Modal -->
<div class="modal fade" id="createQueueModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle me-2"></i>สร้างคิวใหม่
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createQueueForm" class="needs-validation" novalidate>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="modal_hn" class="form-label">หมายเลข HN *</label>
                                <div class="search-box">
                                    <i class="fas fa-search"></i>
                                    <input type="text" class="form-control" id="modal_hn" name="hn" 
                                           placeholder="กรอกหมายเลข HN" required 
                                           onkeyup="searchPatient(this.value)">
                                    <div class="invalid-feedback">กรุณากรอกหมายเลข HN</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="modal_priority" class="form-label">ความสำคัญ</label>
                                <select class="form-control" id="modal_priority" name="priority">
                                    <option value="normal">ปกติ</option>
                                    <option value="urgent">ด่วน</option>
                                    <option value="emergency">ฉุกเฉิน</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="modal_patient_name" class="form-label">ชื่อผู้ป่วย</label>
                                <input type="text" class="form-control" id="modal_patient_name" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="modal_patient_phone" class="form-label">เบอร์โทรศัพท์</label>
                                <input type="text" class="form-control" id="modal_patient_phone" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="modal_medicine_list" class="form-label">รายการยา</label>
                        <textarea class="form-control" id="modal_medicine_list" name="medicine_list" 
                                  rows="3" placeholder="ระบุรายการยา (ถ้ามี)"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="modal_notes" class="form-label">หมายเหตุ</label>
                        <textarea class="form-control" id="modal_notes" name="notes" 
                                  rows="2" placeholder="หมายเหตุเพิ่มเติม (ถ้ามี)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>สร้างคิว
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Queue Details Modal -->
<div class="modal fade" id="queueDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle me-2"></i>รายละเอียดคิว
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="queueDetailsContent">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<?php
$page_scripts = '
<script>
// Create Queue Form Submission
document.getElementById("createQueueForm").addEventListener("submit", function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append("create_queue", "1");
    
    fetch("/index.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        if (data.includes("สร้างคิวสำเร็จ")) {
            bootstrap.Modal.getInstance(document.getElementById("createQueueModal")).hide();
            location.reload();
        } else {
            Swal.fire({
                icon: "error",
                title: "เกิดข้อผิดพลาด",
                text: "ไม่สามารถสร้างคิวได้"
            });
        }
    })
    .catch(error => {
        Swal.fire({
            icon: "error",
            title: "เกิดข้อผิดพลาด",
            text: "เกิดข้อผิดพลาดในการสร้างคิว"
        });
    });
});

// Search Patient for Modal
function searchPatient(hn) {
    if (hn.length < 3) return;
    
    fetch(`/ajax/search_patient.php?hn=${hn}`)
    .then(response => response.json())
    .then(data => {
        if (data.success && data.patient) {
            document.getElementById("modal_patient_name").value = data.patient.first_name + " " + data.patient.last_name;
            document.getElementById("modal_patient_phone").value = data.patient.phone || "";
        } else {
            document.getElementById("modal_patient_name").value = "";
            document.getElementById("modal_patient_phone").value = "";
        }
    });
}

// View Queue Details
function viewQueueDetails(queueId) {
    // For now, just show basic info. Can be enhanced to show full details
    Swal.fire({
        title: "รายละเอียดคิว",
        text: "คิว ID: " + queueId,
        icon: "info"
    });
}

// Auto refresh every 30 seconds
setInterval(() => {
    if (!document.querySelector(".modal.show")) {
        location.reload();
    }
}, 30000);
</script>
';

include '../includes/footer.php';
?>