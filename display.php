<?php
require_once 'config/database.php';
require_once 'includes/queue_manager.php';

$queue_manager = new QueueManager();
$page_title = 'จอแสดงคิว';

// รับคิวที่กำลังเรียก (สถานะ ready)
$calling_queues = $queue_manager->getAllQueues('ready');

// รับคิวที่รอ (สถานะ waiting และ preparing)
$waiting_queues = $queue_manager->getAllQueues('waiting');
$preparing_queues = $queue_manager->getAllQueues('preparing');

// รวมคิวที่รอและกำลังเตรียม
$pending_queues = array_merge($waiting_queues, $preparing_queues);

// เรียงตามความสำคัญ
usort($pending_queues, function($a, $b) {
    $priority_order = ['emergency' => 1, 'urgent' => 2, 'normal' => 3];
    if ($priority_order[$a['priority']] != $priority_order[$b['priority']]) {
        return $priority_order[$a['priority']] - $priority_order[$b['priority']];
    }
    return strtotime($a['created_at']) - strtotime($b['created_at']);
});

$hospital_name = $queue_manager->getSetting('hospital_name', 'โรงพยาบาล ABC');
$pharmacy_name = $queue_manager->getSetting('pharmacy_name', 'ห้องยา');
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - ระบบเรียกคิวรับยา</title>
    
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
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
        
        .display-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px 0;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .hospital-name {
            font-size: 2.5rem;
            font-weight: 800;
            color: #2c5aa0;
            text-align: center;
            margin: 0;
        }
        
        .pharmacy-name {
            font-size: 1.5rem;
            font-weight: 600;
            color: #4472c4;
            text-align: center;
            margin: 5px 0 0 0;
        }
        
        .current-time {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2c5aa0;
            text-align: center;
            margin-top: 10px;
        }
        
        .calling-section {
            padding: 30px 0;
            min-height: 300px;
        }
        
        .calling-card {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            border-radius: 25px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            margin-bottom: 20px;
            animation: pulse-glow 2s infinite;
            position: relative;
            overflow: hidden;
        }
        
        .calling-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            animation: shine 3s infinite;
        }
        
        @keyframes pulse-glow {
            0%, 100% { 
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2), 0 0 50px rgba(255, 107, 107, 0.5);
            }
            50% { 
                box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3), 0 0 80px rgba(255, 107, 107, 0.8);
            }
        }
        
        @keyframes shine {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }
        
        .calling-queue-number {
            font-size: 5rem;
            font-weight: 900;
            text-align: center;
            margin: 20px 0;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .calling-patient-name {
            font-size: 2.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 15px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        .calling-message {
            font-size: 1.5rem;
            font-weight: 600;
            text-align: center;
            margin-bottom: 20px;
            animation: blink 1.5s infinite;
        }
        
        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0.7; }
        }
        
        .waiting-section {
            padding: 20px 0;
        }
        
        .section-title {
            font-size: 2rem;
            font-weight: 700;
            color: white;
            text-align: center;
            margin-bottom: 30px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .queue-item {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border-left: 5px solid #2c5aa0;
        }
        
        .queue-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        
        .queue-emergency {
            border-left-color: #dc3545;
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.1), rgba(255, 255, 255, 0.95));
        }
        
        .queue-urgent {
            border-left-color: #ffc107;
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.1), rgba(255, 255, 255, 0.95));
        }
        
        .queue-number-small {
            font-size: 2.5rem;
            font-weight: 800;
            color: #2c5aa0;
            margin: 0;
        }
        
        .patient-name-small {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
            margin: 5px 0;
        }
        
        .queue-details {
            font-size: 1rem;
            color: #666;
            margin: 5px 0;
        }
        
        .status-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-block;
        }
        
        .status-waiting {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-preparing {
            background: #cce5ff;
            color: #0066cc;
        }
        
        .priority-badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 0.8rem;
            display: inline-block;
            margin-left: 10px;
        }
        
        .priority-emergency {
            background: #dc3545;
            color: white;
            animation: priority-pulse 1.5s infinite;
        }
        
        .priority-urgent {
            background: #ffc107;
            color: #333;
        }
        
        .priority-normal {
            background: #6c757d;
            color: white;
        }
        
        @keyframes priority-pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .no-queue-message {
            text-align: center;
            padding: 50px 20px;
            color: white;
        }
        
        .no-queue-message i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.7;
        }
        
        .no-queue-message h3 {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .footer-info {
            background: rgba(0, 0, 0, 0.3);
            color: white;
            padding: 15px 0;
            text-align: center;
            margin-top: 30px;
        }
        
        .auto-refresh {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.9);
            color: #2c5aa0;
            padding: 10px 15px;
            border-radius: 25px;
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .back-button {
            position: fixed;
            top: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.9);
            color: #2c5aa0;
            padding: 10px 15px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            z-index: 1001;
        }
        
        .back-button:hover {
            background: #2c5aa0;
            color: white;
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .hospital-name {
                font-size: 1.8rem;
            }
            
            .pharmacy-name {
                font-size: 1.2rem;
            }
            
            .calling-queue-number {
                font-size: 3.5rem;
            }
            
            .calling-patient-name {
                font-size: 1.8rem;
            }
            
            .calling-message {
                font-size: 1.2rem;
            }
            
            .queue-number-small {
                font-size: 2rem;
            }
            
            .patient-name-small {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <a href="/index.php" class="back-button">
        <i class="fas fa-arrow-left me-2"></i>กลับ
    </a>
    
    <!-- Header -->
    <div class="display-header">
        <div class="container">
            <h1 class="hospital-name"><?php echo htmlspecialchars($hospital_name); ?></h1>
            <h2 class="pharmacy-name"><?php echo htmlspecialchars($pharmacy_name); ?></h2>
            <div class="current-time" id="current-time"></div>
        </div>
    </div>
    
    <!-- Calling Queue Section -->
    <div class="calling-section">
        <div class="container">
            <?php if (!empty($calling_queues)): ?>
                <div class="row">
                    <?php foreach ($calling_queues as $queue): ?>
                        <div class="col-md-6 mb-4">
                            <div class="calling-card">
                                <div class="card-body p-4">
                                    <div class="text-center">
                                        <i class="fas fa-volume-up fa-3x mb-3"></i>
                                        <div class="calling-message">กรุณามารับยา</div>
                                        <div class="calling-queue-number"><?php echo htmlspecialchars($queue['queue_number']); ?></div>
                                        <div class="calling-patient-name">คุณ<?php echo htmlspecialchars($queue['patient_name']); ?></div>
                                        <div style="font-size: 1.2rem; margin-top: 15px;">
                                            <i class="fas fa-hand-point-right me-2"></i>ที่เคาน์เตอร์รับยา
                                        </div>
            <?php else: ?>
                <div class="no-queue-message">
                    <i class="fas fa-bell-slash"></i>
                    <h3>ไม่มีการเรียกคิวในขณะนี้</h3>
                    <p>กรุณารอการเรียกคิว</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Waiting Queue Section -->
    <div class="waiting-section">
        <div class="container">
            <h2 class="section-title">
                <i class="fas fa-clock me-3"></i>คิวที่รอรับยา
            </h2>
            
            <?php if (!empty($pending_queues)): ?>
                <div class="row">
                    <?php 
                    $display_count = 0;
                    foreach ($pending_queues as $queue): 
                        if ($display_count >= 12) break; // แสดงสูงสุด 12 คิว
                        $display_count++;
                    ?>
                        <div class="col-md-4 col-lg-3 mb-3">
                            <div class="queue-item <?php echo $queue['priority'] != 'normal' ? 'queue-' . $queue['priority'] : ''; ?>">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="queue-number-small"><?php echo htmlspecialchars($queue['queue_number']); ?></div>
                                    <div>
                                        <?php if ($queue['priority'] != 'normal'): ?>
                                            <span class="priority-badge priority-<?php echo $queue['priority']; ?>">
                                                <?php echo $queue['priority_text']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="patient-name-small">
                                    <i class="fas fa-user me-2"></i>
                                    <?php echo htmlspecialchars($queue['patient_name']); ?>
                                </div>
                                
                                <div class="queue-details">
                                    <i class="fas fa-id-card me-2"></i>
                                    HN: <?php echo htmlspecialchars($queue['hn']); ?>
                                </div>
                                
                                <div class="queue-details">
                                    <i class="fas fa-clock me-2"></i>
                                    <?php echo date('H:i น.', strtotime($queue['created_at'])); ?>
                                </div>
                                
                                <div class="mt-2">
                                    <span class="status-badge status-<?php echo $queue['status']; ?>">
                                        <?php echo $queue['status_text']; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (count($pending_queues) > 12): ?>
                        <div class="col-12 text-center mt-3">
                            <div style="background: rgba(255, 255, 255, 0.9); padding: 15px; border-radius: 15px; display: inline-block;">
                                <span style="color: #666; font-weight: 600;">
                                    และอีก <?php echo count($pending_queues) - 12; ?> คิว...
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="no-queue-message">
                    <i class="fas fa-check-circle"></i>
                    <h3>ไม่มีคิวรอ</h3>
                    <p>คิวทั้งหมดได้รับการดำเนินการแล้ว</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Footer Info -->
    <div class="footer-info">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <i class="fas fa-info-circle me-2"></i>
                    ระบบจะอัพเดทอัตโนมัติทุก 30 วินาที
                </div>
                <div class="col-md-6 text-md-end">
                    <i class="fas fa-calendar me-2"></i>
                    วันที่ <span id="current-date"></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Auto Refresh Indicator -->
    <div class="auto-refresh">
        <i class="fas fa-sync-alt me-2"></i>
        <span id="refresh-countdown">30</span>s
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // เสียงแจ้งเตือนเมื่อมีการเรียกคิว
        function playCallSound() {
            // ใช้ Web Audio API สำหรับเสียงแจ้งเตือน
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            
            // สร้างเสียงแจ้งเตือน
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
        }
        
        // Text-to-Speech สำหรับเรียกชื่อ
        function announceQueue() {
            <?php if (!empty($calling_queues)): ?>
                <?php foreach ($calling_queues as $queue): ?>
                    const text = "เรียกคิวหมายเลข <?php echo $queue['queue_number']; ?> คุณ<?php echo $queue['patient_name']; ?> กรุณามารับยาที่เคาน์เตอร์";
                    
                    if ('speechSynthesis' in window) {
                        const utterance = new SpeechSynthesisUtterance(text);
                        utterance.lang = 'th-TH';
                        utterance.rate = 0.8;
                        utterance.pitch = 1;
                        utterance.volume = 0.8;
                        
                        window.speechSynthesis.speak(utterance);
                    }
                <?php endforeach; ?>
                
                // เล่นเสียงแจ้งเตือนก่อนประกาศ
                playCallSound();
            <?php endif; ?>
        }
        
        // อัพเดทเวลา
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
        let refreshCountdown = 30;
        function updateRefreshCountdown() {
            document.getElementById('refresh-countdown').textContent = refreshCountdown;
            refreshCountdown--;
            
            if (refreshCountdown < 0) {
                location.reload();
            }
        }
        
        // เริ่มต้น
        document.addEventListener('DOMContentLoaded', function() {
            updateClock();
            
            // ประกาศคิวเมื่อโหลดหน้า (ถ้ามีคิวที่เรียก)
            setTimeout(announceQueue, 1000);
            
            // อัพเดทเวลาทุกวินาที
            setInterval(updateClock, 1000);
            
            // นับถอยหลังการรีเฟรช
            setInterval(updateRefreshCountdown, 1000);
        });
        
        // ป้องกันการใช้ right-click และ F12
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'F12' || 
                (e.ctrlKey && e.shiftKey && e.key === 'I') ||
                (e.ctrlKey && e.shiftKey && e.key === 'C') ||
                (e.ctrlKey && e.key === 'u')) {
                e.preventDefault();
            }
        });
        
        // ซ่อน cursor หลังจาก 5 วินาที
        let cursorTimeout;
        function hideCursor() {
            document.body.style.cursor = 'none';
        }
        
        function showCursor() {
            document.body.style.cursor = 'default';
            clearTimeout(cursorTimeout);
            cursorTimeout = setTimeout(hideCursor, 5000);
        }
        
        document.addEventListener('mousemove', showCursor);
        cursorTimeout = setTimeout(hideCursor, 5000);
        
        // Wake lock เพื่อป้องกันหน้าจอดับ
        if ('wakeLock' in navigator) {
            navigator.wakeLock.request('screen').catch(err => {
                console.log('Wake lock failed:', err);
            });
        }
    </script>
</body>
</html>>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div