/**
 * Pharmacy Queue System - Main JavaScript File
 * Version: 1.0.0
 */

// Application Configuration
const QueueApp = {
    config: {
        refreshInterval: 30000, // 30 seconds
        soundEnabled: true,
        autoRefresh: true,
        ttsVoice: 'th-TH',
        apiEndpoints: {
            callQueue: '/ajax/call_queue.php',
            updateStatus: '/ajax/update_status.php',
            deleteQueue: '/ajax/delete_queue.php',
            searchPatient: '/ajax/search_patient.php'
        }
    },
    
    // Initialize application
    init() {
        this.setupEventListeners();
        this.loadUserPreferences();
        this.startAutoRefresh();
        this.initializeTooltips();
        this.setupFormValidation();
        this.initializeDateTime();
    },
    
    // Setup event listeners
    setupEventListeners() {
        // Global error handler
        window.addEventListener('error', this.handleError);
        
        // Visibility change handler for auto-refresh
        document.addEventListener('visibilitychange', this.handleVisibilityChange.bind(this));
        
        // Keyboard shortcuts
        document.addEventListener('keydown', this.handleKeyboardShortcuts.bind(this));
        
        // Form submissions
        this.setupFormHandlers();
        
        // Navigation
        this.setupNavigationHandlers();
    },
    
    // Setup form handlers
    setupFormHandlers() {
        // Create queue form
        const createQueueForm = document.getElementById('createQueueForm');
        if (createQueueForm) {
            createQueueForm.addEventListener('submit', this.handleCreateQueue.bind(this));
        }
        
        // Search patient
        const hnInput = document.getElementById('hn');
        if (hnInput) {
            hnInput.addEventListener('input', this.debounce(this.searchPatient.bind(this), 500));
        }
        
        // Modal forms
        const modalForms = document.querySelectorAll('.modal form');
        modalForms.forEach(form => {
            form.addEventListener('submit', this.handleModalFormSubmit.bind(this));
        });
    },
    
    // Setup navigation handlers
    setupNavigationHandlers() {
        // Mobile menu toggle
        const navToggle = document.querySelector('.navbar-toggler');
        if (navToggle) {
            navToggle.addEventListener('click', this.toggleMobileMenu);
        }
        
        // Smooth scroll for anchor links
        const anchorLinks = document.querySelectorAll('a[href^="#"]');
        anchorLinks.forEach(link => {
            link.addEventListener('click', this.handleSmoothScroll);
        });
    },
    
    // Handle create queue form submission
    async handleCreateQueue(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        
        this.showLoading(submitBtn);
        
        try {
            const response = await fetch('/index.php', {
                method: 'POST',
                body: formData
            });
            
            const text = await response.text();
            
            if (text.includes('สร้างคิวสำเร็จ')) {
                this.showSuccess('สร้างคิวเรียบร้อยแล้ว');
                form.reset();
                this.closeModal();
                this.refreshPage();
            } else {
                this.showError('ไม่สามารถสร้างคิวได้');
            }
        } catch (error) {
            this.showError('เกิดข้อผิดพลาดในการสร้างคิว');
        } finally {
            this.hideLoading(submitBtn);
        }
    },
    
    // Search patient by HN
    async searchPatient(hn) {
        if (!hn || hn.length < 3) {
            this.clearPatientInfo();
            return;
        }
        
        try {
            const response = await fetch(`/ajax/search_patient.php?hn=${encodeURIComponent(hn)}`);
            const data = await response.json();
            
            if (data.success && data.patient) {
                this.fillPatientInfo(data.patient);
            } else {
                this.clearPatientInfo();
            }
        } catch (error) {
            console.error('Search patient error:', error);
            this.clearPatientInfo();
        }
    },
    
    // Fill patient information
    fillPatientInfo(patient) {
        const fields = [
            { id: 'patient_name', value: `${patient.first_name} ${patient.last_name}` },
            { id: 'modal_patient_name', value: `${patient.first_name} ${patient.last_name}` },
            { id: 'patient_phone', value: patient.phone || '' },
            { id: 'modal_patient_phone', value: patient.phone || '' }
        ];
        
        fields.forEach(field => {
            const element = document.getElementById(field.id);
            if (element) {
                element.value = field.value;
            }
        });
    },
    
    // Clear patient information
    clearPatientInfo() {
        const fields = ['patient_name', 'modal_patient_name', 'patient_phone', 'modal_patient_phone'];
        fields.forEach(fieldId => {
            const element = document.getElementById(fieldId);
            if (element) {
                element.value = '';
            }
        });
    },
    
    // Call queue function
    async callQueue(queueId, queueNumber, patientName) {
        try {
            const response = await fetch('/ajax/call_queue.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `queue_id=${queueId}`
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Play announcement
                this.announceQueue(queueNumber, patientName);
                
                // Show success message
                this.showSuccess(`เรียกคิว ${queueNumber} - ${patientName}`);
                
                // Refresh page after delay
                setTimeout(() => this.refreshPage(), 2000);
            } else {
                this.showError(data.message || 'ไม่สามารถเรียกคิวได้');
            }
        } catch (error) {
            this.showError('เกิดข้อผิดพลาดในการเรียกคิว');
        }
    },
    
    // Update queue status
    async updateQueueStatus(queueId, status) {
        try {
            const response = await fetch('/ajax/update_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `queue_id=${queueId}&status=${status}`
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showSuccess('อัพเดทสถานะเรียบร้อยแล้ว');
                this.refreshPage();
            } else {
                this.showError(data.message || 'ไม่สามารถอัพเดทสถานะได้');
            }
        } catch (error) {
            this.showError('เกิดข้อผิดพลาดในการอัพเดทสถานะ');
        }
    },
    
    // Delete queue
    async deleteQueue(queueId) {
        const result = await this.showConfirmDialog(
            'ยืนยันการลบ',
            'คุณต้องการลบคิวนี้หรือไม่?',
            'ลบ',
            'ยกเลิก'
        );
        
        if (!result.isConfirmed) return;
        
        try {
            const response = await fetch('/ajax/delete_queue.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `queue_id=${queueId}`
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showSuccess('ลบคิวเรียบร้อยแล้ว');
                this.refreshPage();
            } else {
                this.showError(data.message || 'ไม่สามารถลบคิวได้');
            }
        } catch (error) {
            this.showError('เกิดข้อผิดพลาดในการลบคิว');
        }
    },
    
    // Text-to-Speech announcement
    announceQueue(queueNumber, patientName) {
        if (!this.config.soundEnabled || !('speechSynthesis' in window)) {
            return;
        }
        
        // Play notification sound first
        this.playNotificationSound();
        
        // Announce after a short delay
        setTimeout(() => {
            const text = `เรียกคิวหมายเลข ${queueNumber} คุณ${patientName} กรุณามารับยาที่เคาน์เตอร์`;
            
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = this.config.ttsVoice;
            utterance.rate = 0.8;
            utterance.pitch = 1;
            utterance.volume = 0.8;
            
            speechSynthesis.speak(utterance);
        }, 500);
    },
    
    // Play notification sound
    playNotificationSound() {
        if (!this.config.soundEnabled) return;
        
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
        } catch (error) {
            console.warn('Cannot play notification sound:', error);
        }
    },
    
    // Auto refresh functionality
    startAutoRefresh() {
        if (!this.config.autoRefresh) return;
        
        this.refreshTimer = setInterval(() => {
            if (!document.hidden && !this.hasActiveModal()) {
                this.refreshPage();
            }
        }, this.config.refreshInterval);
        
        this.startRefreshCountdown();
    },
    
    // Start refresh countdown
    startRefreshCountdown() {
        const countdownElement = document.getElementById('refresh-countdown');
        if (!countdownElement) return;
        
        let countdown = this.config.refreshInterval / 1000;
        
        const countdownTimer = setInterval(() => {
            countdown--;
            countdownElement.textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(countdownTimer);
                countdown = this.config.refreshInterval / 1000;
            }
        }, 1000);
    },
    
    // Check if there's an active modal
    hasActiveModal() {
        return document.querySelector('.modal.show') !== null;
    },
    
    // Refresh page
    refreshPage() {
        if (this.hasActiveModal()) return;
        window.location.reload();
    },
    
    // Initialize date/time display
    initializeDateTime() {
        this.updateDateTime();
        setInterval(this.updateDateTime, 1000);
    },
    
    // Update date/time display
    updateDateTime() {
        const now = new Date();
        
        // Update time
        const timeElements = document.querySelectorAll('#current-time');
        timeElements.forEach(element => {
            element.textContent = now.toLocaleTimeString('th-TH');
        });
        
        // Update date
        const dateElements = document.querySelectorAll('#current-date');
        dateElements.forEach(element => {
            element.textContent = now.toLocaleDateString('th-TH', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                weekday: 'long'
            });
        });
    },
    
    // Initialize tooltips
    initializeTooltips() {
        const tooltipElements = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltipElements.forEach(element => {
            new bootstrap.Tooltip(element);
        });
    },
    
    // Setup form validation
    setupFormValidation() {
        const forms = document.querySelectorAll('.needs-validation');
        forms.forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });
    },
    
    // Handle keyboard shortcuts
    handleKeyboardShortcuts(event) {
        // Ctrl + Enter to submit forms
        if (event.ctrlKey && event.key === 'Enter') {
            const activeForm = document.activeElement.closest('form');
            if (activeForm) {
                activeForm.dispatchEvent(new Event('submit'));
            }
        }
        
        // Escape to close modals
        if (event.key === 'Escape') {
            this.closeModal();
        }
        
        // F5 to refresh (prevent default and use custom refresh)
        if (event.key === 'F5') {
            event.preventDefault();
            this.refreshPage();
        }
    },
    
    // Handle visibility change
    handleVisibilityChange() {
        if (document.hidden) {
            // Page is hidden, pause auto-refresh
            if (this.refreshTimer) {
                clearInterval(this.refreshTimer);
            }
        } else {
            // Page is visible, resume auto-refresh
            this.startAutoRefresh();
        }
    },
    
    // Show loading state
    showLoading(button) {
        if (!button) return;
        
        button.disabled = true;
        const originalText = button.innerHTML;
        button.dataset.originalText = originalText;
        
        const loadingSpinner = '<div class="loading me-2"></div>';
        button.innerHTML = loadingSpinner + 'กำลังประมวลผล...';
    },
    
    // Hide loading state
    hideLoading(button) {
        if (!button) return;
        
        button.disabled = false;
        if (button.dataset.originalText) {
            button.innerHTML = button.dataset.originalText;
            delete button.dataset.originalText;
        }
    },
    
    // Show success message
    showSuccess(message, timer = 3000) {
        Swal.fire({
            icon: 'success',
            title: 'สำเร็จ',
            text: message,
            timer: timer,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    },
    
    // Show error message
    showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: message,
            confirmButtonText: 'ตกลง'
        });
    },
    
    // Show info message
    showInfo(message, timer = 3000) {
        Swal.fire({
            icon: 'info',
            title: 'แจ้งเตือน',
            text: message,
            timer: timer,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    },
    
    // Show confirm dialog
    showConfirmDialog(title, text, confirmText = 'ยืนยัน', cancelText = 'ยกเลิก') {
        return Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: confirmText,
            cancelButtonText: cancelText
        });
    },
    
    // Close active modal
    closeModal() {
        const activeModal = document.querySelector('.modal.show');
        if (activeModal) {
            const modal = bootstrap.Modal.getInstance(activeModal);
            if (modal) {
                modal.hide();
            }
        }
    },
    
    // Handle modal form submission
    async handleModalFormSubmit(event) {
        event.preventDefault();
        const form = event.target;
        const modalId = form.closest('.modal').id;
        
        // Handle different modal forms
        switch (modalId) {
            case 'createQueueModal':
                await this.handleCreateQueue(event);
                break;
            default:
                console.log('Unknown modal form:', modalId);
        }
    },
    
    // Toggle mobile menu
    toggleMobileMenu() {
        const navCollapse = document.querySelector('.navbar-collapse');
        if (navCollapse) {
            navCollapse.classList.toggle('show');
        }
    },
    
    // Handle smooth scroll
    handleSmoothScroll(event) {
        event.preventDefault();
        const targetId = event.target.getAttribute('href');
        const targetElement = document.querySelector(targetId);
        
        if (targetElement) {
            targetElement.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    },
    
    // Load user preferences
    loadUserPreferences() {
        const preferences = localStorage.getItem('queueAppPreferences');
        if (preferences) {
            try {
                const prefs = JSON.parse(preferences);
                this.config = { ...this.config, ...prefs };
                this.applyPreferences(prefs);
            } catch (error) {
                console.warn('Failed to load user preferences:', error);
            }
        }
    },
    
    // Save user preferences
    saveUserPreferences(preferences) {
        try {
            this.config = { ...this.config, ...preferences };
            localStorage.setItem('queueAppPreferences', JSON.stringify(this.config));
            this.applyPreferences(preferences);
        } catch (error) {
            console.warn('Failed to save user preferences:', error);
        }
    },
    
    // Apply user preferences
    applyPreferences(preferences) {
        // Apply dark mode
        if (preferences.darkMode) {
            document.body.classList.add('dark-mode');
        } else {
            document.body.classList.remove('dark-mode');
        }
        
        // Apply auto-refresh setting
        if (preferences.autoRefresh !== undefined) {
            this.config.autoRefresh = preferences.autoRefresh;
            if (!preferences.autoRefresh && this.refreshTimer) {
                clearInterval(this.refreshTimer);
            } else if (preferences.autoRefresh && !this.refreshTimer) {
                this.startAutoRefresh();
            }
        }
        
        // Apply sound setting
        if (preferences.soundEnabled !== undefined) {
            this.config.soundEnabled = preferences.soundEnabled;
        }
    },
    
    // Debounce function
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
    
    // Handle global errors
    handleError(error) {
        console.error('Application error:', error);
        
        // Show user-friendly error message
        if (!document.hidden) {
            QueueApp.showError('เกิดข้อผิดพลาดในระบบ กรุณาลองใหม่อีกครั้ง');
        }
    },
    
    // Check system health
    async checkSystemHealth() {
        try {
            const response = await fetch('/api/health-check.php');
            const data = await response.json();
            
            if (data.status === 'ok') {
                this.showSuccess('ระบบทำงานปกติ');
            } else {
                this.showError('ระบบมีปัญหา กรุณาติดต่อผู้ดูแล');
            }
        } catch (error) {
            this.showError('ไม่สามารถตรวจสอบสถานะระบบได้');
        }
    },
    
    // Export data functionality
    exportData(startDate, endDate, format = 'csv') {
        const params = new URLSearchParams({
            start_date: startDate,
            end_date: endDate,
            format: format
        });
        
        window.open(`/api/export.php?${params}`, '_blank');
    },
    
    // Wake lock for display screens
    async requestWakeLock() {
        if ('wakeLock' in navigator) {
            try {
                const wakeLock = await navigator.wakeLock.request('screen');
                console.log('Wake lock active');
                
                // Handle wake lock release
                wakeLock.addEventListener('release', () => {
                    console.log('Wake lock released');
                });
                
                return wakeLock;
            } catch (error) {
                console.warn('Wake lock failed:', error);
            }
        }
    },
    
    // Initialize PWA features
    initializePWA() {
        // Service worker registration
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js')
                .then(registration => {
                    console.log('SW registered:', registration);
                })
                .catch(error => {
                    console.log('SW registration failed:', error);
                });
        }
        
        // Install prompt
        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            
            // Show install button
            const installBtn = document.getElementById('install-app');
            if (installBtn) {
                installBtn.style.display = 'block';
                installBtn.addEventListener('click', () => {
                    deferredPrompt.prompt();
                    deferredPrompt.userChoice.then((choiceResult) => {
                        if (choiceResult.outcome === 'accepted') {
                            console.log('User accepted the install prompt');
                        }
                        deferredPrompt = null;
                    });
                });
            }
        });
    },
    
    // Cleanup function
    cleanup() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
        }
        
        // Remove event listeners
        window.removeEventListener('error', this.handleError);
        document.removeEventListener('visibilitychange', this.handleVisibilityChange);
        document.removeEventListener('keydown', this.handleKeyboardShortcuts);
    }
};

// Global functions for backward compatibility
window.callQueue = (queueId, queueNumber, patientName) => {
    QueueApp.callQueue(queueId, queueNumber, patientName);
};

window.updateQueueStatus = (queueId, status) => {
    QueueApp.updateQueueStatus(queueId, status);
};

window.deleteQueue = (queueId) => {
    QueueApp.deleteQueue(queueId);
};

window.searchPatient = (hn) => {
    QueueApp.searchPatient(hn);
};

// Additional utility functions
const Utils = {
    // Format date
    formatDate(date, format = 'dd/mm/yyyy') {
        const d = new Date(date);
        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();
        
        switch (format) {
            case 'dd/mm/yyyy':
                return `${day}/${month}/${year}`;
            case 'yyyy-mm-dd':
                return `${year}-${month}-${day}`;
            default:
                return d.toLocaleDateString('th-TH');
        }
    },
    
    // Format time
    formatTime(date) {
        return new Date(date).toLocaleTimeString('th-TH', {
            hour: '2-digit',
            minute: '2-digit'
        });
    },
    
    // Validate HN format
    validateHN(hn) {
        return /^[A-Z0-9]{3,10}$/.test(hn);
    },
    
    // Generate queue number
    generateQueueNumber(prefix = 'M', number = 1) {
        return prefix + String(number).padStart(3, '0');
    },
    
    // Get priority color class
    getPriorityClass(priority) {
        const classes = {
            normal: 'priority-normal',
            urgent: 'priority-urgent',
            emergency: 'priority-emergency'
        };
        return classes[priority] || classes.normal;
    },
    
    // Get status color class
    getStatusClass(status) {
        const classes = {
            waiting: 'status-waiting',
            preparing: 'status-preparing',
            ready: 'status-ready',
            completed: 'status-completed',
            cancelled: 'status-cancelled'
        };
        return classes[status] || classes.waiting;
    },
    
    // Copy text to clipboard
    async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            QueueApp.showSuccess('คัดลอกแล้ว');
        } catch (error) {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            QueueApp.showSuccess('คัดลอกแล้ว');
        }
    },
    
    // Detect mobile device
    isMobile() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    },
    
    // Get current page name
    getCurrentPage() {
        return window.location.pathname.split('/').pop().replace('.php', '') || 'index';
    }
};

// Initialize application when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    QueueApp.init();
    
    // Initialize PWA features for display page
    if (Utils.getCurrentPage() === 'display') {
        QueueApp.initializePWA();
        QueueApp.requestWakeLock();
    }
});

// Cleanup when page is unloaded
window.addEventListener('beforeunload', () => {
    QueueApp.cleanup();
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { QueueApp, Utils };
}