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
                    <p class="mb-0">© <?php echo date('Y'); ?> <?php echo isset($queue_manager) ? $queue_manager->getSetting('hospital_name', 'โรงพยาบาล ABC') : 'โรงพยาบาล ABC'; ?></p>
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
        // Base URL สำหรับ AJAX calls
        const BASE_URL = '<?php echo getBaseUrl(); ?>';
        
        // Helper function สำหรับสร้าง URL
        function apiUrl(path) {
            return BASE_URL + '/' + path.replace(/^\//, '');
        }
        
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
                url: apiUrl('ajax/call_queue.php'),
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
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถเรียกคิวได้ กรุณาลองใหม่อีกครั้ง'
                    });
                }
            });
        }
        
        // Update Queue Status
        function updateQueueStatus(queueId, status) {
            $.ajax({
                url: apiUrl('ajax/update_status.php'),
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
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถอัพเดทสถานะได้'
                    });
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
                        url: apiUrl('ajax/delete_queue.php'),
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
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX Error:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'เกิดข้อผิดพลาด',
                                text: 'ไม่สามารถลบคิวได้'
                            });
                        }
                    });
                }
            });
        }
        
        // Search Patient by HN
        function searchPatient(hn) {
            if (hn.length < 3) {
                clearPatientInfo();
                return;
            }
            
            $.ajax({
                url: apiUrl('ajax/search_patient.php'),
                method: 'GET',
                data: { hn: hn },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.patient) {
                        fillPatientInfo(response.patient);
                    } else {
                        clearPatientInfo();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Search Error:', error);
                    clearPatientInfo();
                }
            });
        }
        
        // Fill patient information
        function fillPatientInfo(patient) {
            const fullName = patient.first_name + ' ' + patient.last_name;
            $('#patient_name, #modal_patient_name').val(fullName);
            $('#patient_phone, #modal_patient_phone').val(patient.phone || '');
        }
        
        // Clear patient information
        function clearPatientInfo() {
            $('#patient_name, #modal_patient_name, #patient_phone, #modal_patient_phone').val('');
        }
        
        // Call next queue
        function callNextQueue() {
            $.ajax({
                url: apiUrl('ajax/call_next_queue.php'),
                method: 'POST',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const queue = response.queue;
                        const text = `เรียกคิวหมายเลข ${queue.queue_number} คุณ${queue.patient_name} กรุณามารับยาที่เคาน์เตอร์`;
                        speakText(text);
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'เรียกคิวถัดไป',
                            text: `เรียกคิว ${queue.queue_number} - ${queue.patient_name}`,
                            timer: 3000,
                            showConfirmButton: false
                        });
                        
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        Swal.fire({
                            icon: 'info',
                            title: 'ไม่มีคิว',
                            text: response.message
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถเรียกคิวถัดไปได้'
                    });
                }
            });
        }
        
        // Auto refresh for display page
        if (window.location.pathname.includes('display.php')) {
            setInterval(() => {
                if (!document.hidden) {
                    location.reload();
                }
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
            
            $('#current-time').text(timeString);
            $('#current-date').text(dateString);
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
            if (submitBtn.length) {
                submitBtn.prop('disabled', true);
                const originalText = submitBtn.html();
                submitBtn.data('original-text', originalText);
                submitBtn.html('<div class="loading me-2"></div>กำลังประมวลผล...');
                
                // Reset after 10 seconds (failsafe)
                setTimeout(() => {
                    submitBtn.prop('disabled', false);
                    if (submitBtn.data('original-text')) {
                        submitBtn.html(submitBtn.data('original-text'));
                    }
                }, 10000);
            }
        });
        
        // Notification sound
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
        
        // View queue details
        function viewQueueDetails(queueId) {
            $.ajax({
                url: apiUrl('ajax/get_queue_details.php'),
                method: 'GET',
                data: { queue_id: queueId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const queue = response.queue;
                        Swal.fire({
                            title: 'รายละเอียดคิว',
                            html: `
                                <div class="text-start">
                                    <p><strong>หมายเลขคิว:</strong> ${queue.queue_number}</p>
                                    <p><strong>ชื่อผู้ป่วย:</strong> ${queue.patient_name}</p>
                                    <p><strong>HN:</strong> ${queue.hn}</p>
                                    <p><strong>สถานะ:</strong> ${queue.status_text}</p>
                                    <p><strong>ความสำคัญ:</strong> ${queue.priority_text}</p>
                                    <p><strong>เวลาสร้าง:</strong> ${queue.created_at}</p>
                                    ${queue.medicine_list ? `<p><strong>รายการยา:</strong><br>${queue.medicine_list}</p>` : ''}
                                    ${queue.notes ? `<p><strong>หมายเหตุ:</strong><br>${queue.notes}</p>` : ''}
                                </div>
                            `,
                            width: 600,
                            showConfirmButton: true,
                            confirmButtonText: 'ปิด'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: 'ไม่สามารถดูรายละเอียดได้'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถดูรายละเอียดได้'
                    });
                }
            });
        }
        
        // Handle connection errors
        $(document).ajaxError(function(event, xhr, settings, thrownError) {
            if (xhr.status === 404) {
                console.error('API endpoint not found:', settings.url);
            } else if (xhr.status === 500) {
                console.error('Server error:', thrownError);
            } else if (xhr.status === 0) {
                console.error('Network error or CORS issue');
            }
        });
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
        
        // Keyboard shortcuts
        $(document).keydown(function(e) {
            // Ctrl + R for refresh
            if (e.ctrlKey && e.keyCode === 82) {
                e.preventDefault();
                location.reload();
            }
            
            // F5 for refresh
            if (e.keyCode === 116) {
                e.preventDefault();
                location.reload();
            }
            
            // Escape to close modals
            if (e.keyCode === 27) {
                $('.modal').modal('hide');
            }
        });
    </script>
    
    <!-- Page specific scripts -->
    <?php if (isset($page_scripts)): ?>
        <?php echo $page_scripts; ?>
    <?php endif; ?>
</body>
</html>