<?php
require_once 'config/database.php';
require_once 'includes/queue_manager.php';

$queue_manager = new QueueManager();
$page_title = 'จอแสดงคิว';

// รับข้อมูลคิวปัจจุบัน
$calling_queues = $queue_manager->getAllQueues('ready');
$waiting_queues = $queue_manager->getAllQueues('waiting');
$preparing_queues = $queue_manager->getAllQueues('preparing');
$completed_queues = $queue_manager->getAllQueues('completed', date('Y-m-d'));

// รับการตั้งค่า
$hospital_name = $queue_manager->getSetting('hospital_name', 'โรงพยาบาล ABC');
$pharmacy_name = $queue_manager->getSetting('pharmacy_name', 'ห้องยา');
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo $hospital_name; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
        
        .display-container {
            padding: 20px;
            max-width: 100vw;
        }
        
        .header-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .header-section h1 {
            color: #2c5aa0;
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 10px;
        }
        
        .header-section h2 {
            color: #4472c4;
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .current-time {
            font-size: 1.5rem;
            color: #666;
            font-weight: 500;
        }
        
        .calling-section {
            background: linear-gradient(135deg, #dc3545, #e83e8c);
            color: white;
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 15px 35px rgba(220, 53, 69, 0.3);
            animation: pulse 2s infinite;
        }
        
        .calling-section h3 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 30px;
        }
        
        .calling-queue {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            backdrop-filter: blur(10px);
        }
        
        .calling-queue:last-child {
            margin-bottom: 0;
        }
        
        .queue-number {
            font-size: 4rem;
            font-weight: 900;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .patient-name {
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .counter-info {
            font-size: 1.5rem;
            font-weight: 500;
            opacity: 0.9;
        }
        
        .status-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .status-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .status-card h4 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .waiting-card h4 { color: #ffc107; }
        .preparing-card h4 { color: #0dcaf0; }
        .completed-card h4 { color: #198754; }
        
        .queue-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }
        
        .queue-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }
        
        .queue-item:last-child {
            margin-bottom: 0;
        }
        
        .item-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2c5aa0;
        }
        
        .item-name {
            font-size: 1.1rem;
            font-weight: 500;
            color: #495057;
        }
        
        .item-time {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .priority-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .priority-emergency {
            background: #dc3545;
            color: white;
            animation: blink 1s infinite;
        }
        
        .priority-urgent {
            background: #fd7e14;
            color: white;
        }
        
        .priority-normal {
            background: #6c757d;
            color: white;
        }
        
        .no-queue {
            text-align: center;
            padding: 40px;
            color: #6c757d;
            font-size: 1.2rem;
        }
        
        .no-queue i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.8; }
            100% { opacity: 1; }
        }
        
        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0.3; }
        }
        
        @media (max-width: 768px) {
            .header-section h1 { font-size: 2rem; }
            .header-section h2 { font-size: 1.5rem; }
            .calling-section h3 { font-size: 2rem; }
            .queue-number { font-size: 3rem; }
            .patient-name { font-size: 2rem; }
            .counter-info { font-size: 1.2rem; }
            .status-section {
                grid-template-columns: 1fr;
            }
        }
        
        .refresh-indicator {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 10px 15px;
            border-radius: 25px;
            font-size: 0.9rem;
            z-index: 1000;
        }
        
        .footer-info {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <!-- Refresh Indicator -->
    <div class="refresh-indicator">
        <i class="fas fa-sync-alt me-2"></i>
        <span id="refresh-timer">30</span> วินาที
    </div>

    <div class="display-container">
        <!-- Header Section -->
        <div class="header-section">
            <h1><i class="fas fa-pills me-3"></i><?php echo htmlspecialchars($hospital_name); ?></h1>
            <h2><?php echo htmlspecialchars($pharmacy_name); ?></h2>
            <div class="current-time">
                <i class="fas fa-clock me-2"></i>
                <span id="current-time"><?php echo date('H:i:s'); ?></span>
                <span class="ms-3" id="current-date"><?php echo date('d/m/Y'); ?></span>
            </div>
        </div>

        <!-- Calling Section -->
        <?php if (!empty($calling_queues)): ?>
        <div class="calling-section">
            <h3><i class="fas fa-volume-up me-3"></i>กำลังเรียกคิว</h3>
            
            <?php foreach ($calling_queues as $queue): ?>
            <div class="calling-queue">
                <div class="queue-number"><?php echo htmlspecialchars($queue['queue_number']); ?></div>
                <div class="patient-name"><?php echo htmlspecialchars($queue['patient_name']); ?></div>
                <div class="counter-info">
                    <i class="fas fa-arrow-right me-2"></i>กรุณามารับยาที่เคาน์เตอร์
                </div>
                <?php if ($queue['priority'] !== 'normal'): ?>
                <div class="mt-3">
                    <span class="priority-badge priority-<?php echo $queue['priority']; ?>">
                        <?php echo htmlspecialchars($queue['priority_text']); ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Status Sections -->
        <div class="status-section">
            <!-- Waiting Queue -->
            <div class="status-card waiting-card">
                <h4>
                    <i class="fas fa-clock"></i>
                    รอเรียก (<?php echo count($waiting_queues); ?>)
                </h4>
                
                <?php if (empty($waiting_queues)): ?>
                    <div class="no-queue">
                        <i class="fas fa-check-circle"></i>
                        <div>ไม่มีคิวที่รอเรียก</div>
                    </div>
                <?php else: ?>
                    <?php foreach (array_slice($waiting_queues, 0, 8) as $queue): ?>
                    <div class="queue-item">
                        <div>
                            <div class="item-number"><?php echo htmlspecialchars($queue['queue_number']); ?></div>
                            <div class="item-name"><?php echo htmlspecialchars($queue['patient_name']); ?></div>
                        </div>
                        <div class="text-end">
                            <?php if ($queue['priority'] !== 'normal'): ?>
                                <span class="priority-badge priority-<?php echo $queue['priority']; ?>">
                                    <?php echo htmlspecialchars($queue['priority_text']); ?>
                                </span>
                            <?php endif; ?>
                            <div class="item-time"><?php echo date('H:i', strtotime($queue['created_at'])); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if (count($waiting_queues) > 8): ?>
                    <div class="text-center mt-3">
                        <small class="text-muted">และอีก <?php echo count($waiting_queues) - 8; ?> คิว...</small>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Preparing Queue -->
            <div class="status-card preparing-card">
                <h4>
                    <i class="fas fa-cog"></i>
                    กำลังเตรียม (<?php echo count($preparing_queues); ?>)
                </h4>
                
                <?php if (empty($preparing_queues)): ?>
                    <div class="no-queue">
                        <i class="fas fa-pause-circle"></i>
                        <div>ไม่มีคิวที่กำลังเตรียม</div>
                    </div>
                <?php else: ?>
                    <?php foreach (array_slice($preparing_queues, 0, 8) as $queue): ?>
                    <div class="queue-item">
                        <div>
                            <div class="item-number"><?php echo htmlspecialchars($queue['queue_number']); ?></div>
                            <div class="item-name"><?php echo htmlspecialchars($queue['patient_name']); ?></div>
                        </div>
                        <div class="text-end">
                            <?php if ($queue['priority'] !== 'normal'): ?>
                                <span class="priority-badge priority-<?php echo $queue['priority']; ?>">
                                    <?php echo htmlspecialchars($queue['priority_text']); ?>
                                </span>
                            <?php endif; ?>
                            <div class="item-time"><?php echo date('H:i', strtotime($queue['created_at'])); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if (count($preparing_queues) > 8): ?>
                    <div class="text-center mt-3">
                        <small class="text-muted">และอีก <?php echo count($preparing_queues) - 8; ?> คิว...</small>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Completed Queue -->
            <div class="status-card completed-card">
                <h4>
                    <i class="fas fa-check"></i>
                    เสร็จสิ้นแล้ว (<?php echo count($completed_queues); ?>)
                </h4>
                
                <?php if (empty($completed_queues)): ?>
                    <div class="no-queue">
                        <i class="fas fa-hourglass-start"></i>
                        <div>ยังไม่มีคิวที่เสร็จสิ้น</div>
                    </div>
                <?php else: ?>
                    <?php foreach (array_slice(array_reverse($completed_queues), 0, 8) as $queue): ?>
                    <div class="queue-item">
                        <div>
                            <div class="item-number"><?php echo htmlspecialchars($queue['queue_number']); ?></div>
                            <div class="item-name"><?php echo htmlspecialchars($queue['patient_name']); ?></div>
                        </div>
                        <div class="text-end">
                            <div class="item-time">
                                <?php echo $queue['completed_at'] ? date('H:i', strtotime($queue['completed_at'])) : date('H:i', strtotime($queue['updated_at'])); ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if (count($completed_queues) > 8): ?>
                    <div class="text-center mt-3">
                        <small class="text-muted">และอีก <?php echo count($completed_queues) - 8; ?> คิว...</small>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Footer Info -->
        <div class="footer-info">
            <div class="row align-items-center">
                <div class="col-md-6 text-start">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>หมายเหตุ:</strong> หน้าจอจะรีเฟรชอัตโนมัติทุก 30 วินาที
                </div>
                <div class="col-md-6 text-end">
                    <a href="index.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-home me-1"></i>กลับหน้าหลัก
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Real-time clock
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('th-TH');
            const dateString = now.toLocaleDateString('th-TH', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                weekday: 'long'
            });
            
            document.getElementById('current-time').textContent = timeString;
            document.getElementById('current-date').textContent = dateString;
        }
        
        // Auto refresh countdown
        let refreshCounter = 30;
        function updateRefreshTimer() {
            document.getElementById('refresh-timer').textContent = refreshCounter;
            refreshCounter--;
            
            if (refreshCounter < 0) {
                location.reload();
            }
        }
        
        // Initialize
        updateClock();
        updateRefreshTimer();
        
        // Update every second
        setInterval(updateClock, 1000);
        setInterval(updateRefreshTimer, 1000);
        
        // Auto refresh every 30 seconds
        setTimeout(() => {
            location.reload();
        }, 30000);
        
        // Prevent sleep/screensaver
        let wakeLock = null;
        
        if ('wakeLock' in navigator) {
            navigator.wakeLock.request('screen').then(wl => {
                wakeLock = wl;
                console.log('Screen wake lock active');
            }).catch(err => {
                console.log('Wake lock failed:', err);
            });
        }
        
        // Keep screen active with video method (fallback)
        const video = document.createElement('video');
        video.setAttribute('muted', '');
        video.setAttribute('playsinline', '');
        video.style.position = 'absolute';
        video.style.top = '-1px';
        video.style.left = '-1px';
        video.style.width = '1px';
        video.style.height = '1px';
        video.style.opacity = '0.01';
        
        const canvas = document.createElement('canvas');
        canvas.width = 1;
        canvas.height = 1;
        const ctx = canvas.getContext('2d');
        ctx.fillRect(0, 0, 1, 1);
        
        const stream = canvas.captureStream(1);
        video.srcObject = stream;
        video.play();
        
        document.body.appendChild(video);
        
        // Visibility change handler
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && wakeLock === null && 'wakeLock' in navigator) {
                navigator.wakeLock.request('screen').then(wl => {
                    wakeLock = wl;
                });
            }
        });
        
        // Sound notification for new calls (if supported)
        function playNotificationSound() {
            try {
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                
                oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
                oscillator.frequency.setValueAtTime(600, audioContext.currentTime + 0.1);
                oscillator.frequency.setValueAtTime(800, audioContext.currentTime + 0.2);
                
                gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
                
                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 0.3);
            } catch (e) {
                console.log('Cannot play sound:', e);
            }
        }
        
        // Check for updates periodically
        let lastCallingCount = <?php echo count($calling_queues); ?>;
        
        setInterval(() => {
            fetch('ajax/get_queue_stats.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const currentCalling = data.stats.ready || 0;
                        if (currentCalling > lastCallingCount) {
                            playNotificationSound();
                        }
                        lastCallingCount = currentCalling;
                    }
                })
                .catch(error => console.log('Stats check failed:', error));
        }, 10000); // Check every 10 seconds
    </script>
</body>
</html>