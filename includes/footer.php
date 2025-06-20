</div>
    </div>
    
    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-pills me-2"></i>ระบบเรียกคิวรับยา</h5>
                    <p class="mb-0">พัฒนาด้วย PHP และ Bootstrap</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">© <?php echo date('Y'); ?> <?php echo $queue_manager->getSetting('hospital_name', 'โรงพยาบาล ABC'); ?></p>
                    <small class="text-muted">Version 1.0</small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Custom JS -->
    <script>
        // Text-to-Speech Function
        function speakText(text) {
            if ('speechSynthesis' in window) {
                const utterance = new SpeechSynthesisUtterance(text);
                utterance.lang = 'th-TH';
                utterance.rate = 0.8;
                utterance.pitch = 1;
                utterance.volume = 1;
                
                window.speechSynthesis.speak(utterance);
            }
        }
        
        // Call Queue Function
        function callQueue(queueId, queueNumber, patientName) {
            $.ajax({
                url: '/ajax/call_queue.php',
                method: 'POST',
                data: {
                    queue_id: queueId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // เล่นเสียงเรียกคิว
                        const text = `เรียกคิวหมายเลข ${queueNumber} คุณ${patientName} กรุณามารับยาที่เคาน์เตอร์`;
                        speakText(text);
                        
                        // แสดงข้อความแจ้งเตือน
                        Swal.fire({
                            icon: 'success',
                            title: 'เรียกคิวสำเร็จ',
                            text: `เรียกคิว ${queueNumber} - ${patientName}`,
                            timer: 3000,
                            showConfirmButton: false
                        });
                        
                        // รีโหลดหน้า
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: response.message
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถเรียกคิวได้'
                    });
                }
            });
        }
        
        // Update Queue Status
        function updateQueueStatus(queueId, status) {
            $.ajax({
                url: '/ajax/update_status.php',
                method: 'POST',
                data: {
                    queue_id: queueId,
                    status: status
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: response.message
                        });
                    }
                }
            });
        }
        
        // Delete Queue
        function deleteQueue(queueId) {
            Swal.fire({
                title: 'ยืนยันการลบ',
                text: 'คุณต้องการลบคิวนี้หรือไม่?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'ลบ',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '/ajax/delete_queue.php',
                        method: 'POST',
                        data: {
                            queue_id: queueId
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'ลบสำเร็จ',
                                    text: 'ลบคิวเรียบร้อยแล้ว',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                location.reload();
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'เกิดข้อผิดพลาด',
                                    text: response.message
                                });
                            }
                        }
                    });
                }
            });
        }
        
        // Search Patient by HN
        function searchPatient(hn) {
            if (hn.length < 3) return;
            
            $.ajax({
                url: '/ajax/search_patient.php',
                method: 'GET',
                data: { hn: hn },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.patient) {
                        $('#patient_name').val(response.patient.first_name + ' ' + response.patient.last_name);
                        $('#patient_phone').val(response.patient.phone || '');
                    } else {
                        $('#patient_name').val('');
                        $('#patient_phone').val('');
                    }
                }
            });
        }
        
        // Auto refresh for display page
        if (window.location.pathname.includes('display.php')) {
            setInterval(() => {
                location.reload();
            }, 30000); // รีเฟรชทุก 30 วินาที
        }
        
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
            
            if ($('#current-time').length) {
                $('#current-time').text(timeString);
            }
            if ($('#current-date').length) {
                $('#current-date').text(dateString);
            }
        }
        
        // Update clock every second
        setInterval(updateClock, 1000);
        updateClock();
        
        // Form validation
        $('.needs-validation').on('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            $(this).addClass('was-validated');
        });
        
        // Tooltips initialization
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Show loading spinner on form submit
        $('form').on('submit', function() {
            const submitBtn = $(this).find('button[type="submit"]');
            submitBtn.prop('disabled', true);
            submitBtn.html('<div class="loading me-2"></div>กำลังประมวลผล...');
        });
        
        // Notification sound
        function playNotificationSound() {
            const audio = new Audio('data:audio/wav;base64,UklGRvIAAABXQVZFZm10IAAAAAABAAEAK...');
            audio.play().catch(e => console.log('Cannot play sound:', e));
        }
    </script>
    
    <!-- Page specific scripts -->
    <?php if (isset($page_scripts)): ?>
        <?php echo $page_scripts; ?>
    <?php endif; ?>
</body>
</html>