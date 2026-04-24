// ========================================================
// Shikhbo Admin Panel - Enhanced Custom JavaScript
// ========================================================

document.addEventListener('DOMContentLoaded', function () {

    // ---------- DOM Elements ----------
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarClose = document.getElementById('sidebarClose');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const profileButton = document.getElementById('profileButton');
    const profileMenu = document.getElementById('profileMenu');
    const dropdownChevron = document.getElementById('dropdownChevron');

    // ---------- Sidebar Toggle (Mobile) ----------
    function openSidebar() {
        sidebar.classList.add('open');
        sidebar.classList.remove('-translate-x-full');
        sidebar.classList.add('translate-x-0');
        sidebarOverlay.classList.remove('hidden');
        document.body.classList.add('sidebar-open');
        sidebarOverlay.style.opacity = '1';
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        sidebar.classList.add('-translate-x-full');
        sidebar.classList.remove('translate-x-0');
        sidebarOverlay.classList.add('hidden');
        document.body.classList.remove('sidebar-open');
    }

    if (sidebarToggle) sidebarToggle.addEventListener('click', openSidebar);
    if (sidebarClose) sidebarClose.addEventListener('click', closeSidebar);
    if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);

    // ---------- Profile Dropdown (with animation) ----------
    if (profileButton && profileMenu) {
        profileButton.addEventListener('click', function (e) {
            e.stopPropagation();
            const isHidden = profileMenu.classList.contains('hidden');
            if (isHidden) {
                profileMenu.classList.remove('hidden');
                requestAnimationFrame(() => {
                    profileMenu.classList.remove('opacity-0', 'scale-95');
                    profileMenu.classList.add('opacity-100', 'scale-100');
                });
                if (dropdownChevron) dropdownChevron.style.transform = 'rotate(180deg)';
            } else {
                profileMenu.classList.add('opacity-0', 'scale-95');
                profileMenu.classList.remove('opacity-100', 'scale-100');
                if (dropdownChevron) dropdownChevron.style.transform = 'rotate(0deg)';
                setTimeout(() => profileMenu.classList.add('hidden'), 200);
            }
        });

        document.addEventListener('click', function (e) {
            if (!profileButton.contains(e.target) && !profileMenu.contains(e.target)) {
                profileMenu.classList.add('opacity-0', 'scale-95', 'hidden');
                profileMenu.classList.remove('opacity-100', 'scale-100');
                if (dropdownChevron) dropdownChevron.style.transform = 'rotate(0deg)';
            }
        });
    }

    // ---------- Toast Notification System ----------
    window.showToast = function(message, type = 'info', duration = 4000) {
        const container = document.getElementById('toastContainer');
        if (!container) return;
        const toast = document.createElement('div');
        const icons = { success: 'fa-circle-check', error: 'fa-circle-exclamation', warning: 'fa-triangle-exclamation', info: 'fa-circle-info' };
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `<i class="fa-solid ${icons[type] || icons.info}"></i><span>${message}</span>`;
        container.appendChild(toast);
        setTimeout(() => {
            toast.classList.add('toast-exit');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    };

    // ---------- Session Timeout Warning ----------
    let sessionTimeout;
    const SESSION_DURATION = 1800000; // 30 minutes
    const WARNING_BEFORE = 300000; // 5 minutes before

    function resetSessionTimer() {
        clearTimeout(sessionTimeout);
        sessionTimeout = setTimeout(function() {
            if (confirm('Your session is about to expire. Would you like to continue?')) {
                window.location.reload();
            } else {
                window.location.href = '/pages/logout.php';
            }
        }, SESSION_DURATION - WARNING_BEFORE);
    }

    document.addEventListener('click', resetSessionTimer);
    document.addEventListener('keypress', resetSessionTimer);
    document.addEventListener('scroll', resetSessionTimer);
    resetSessionTimer();

    // ---------- Mobile Responsive Helper ----------
    function handleResize() {
        if (window.innerWidth >= 1024) {
            sidebar.classList.remove('-translate-x-full');
            sidebar.classList.add('translate-x-0');
            sidebarOverlay.classList.add('hidden');
            document.body.classList.remove('sidebar-open');
        } else {
            if (!sidebar.classList.contains('open')) {
                sidebar.classList.add('-translate-x-full');
                sidebar.classList.remove('translate-x-0');
            }
        }
    }

    window.addEventListener('resize', handleResize);
    handleResize();

    // ---------- Keyboard Shortcuts ----------
    document.addEventListener('keydown', function(e) {
        // Ctrl+D → Dashboard
        if (e.ctrlKey && e.key === 'd') {
            e.preventDefault();
            window.location.href = 'index.php?page=dashboard';
        }
        // Ctrl+E → Exams
        else if (e.ctrlKey && e.key === 'e') {
            e.preventDefault();
            window.location.href = 'index.php?page=exams';
        }
        // Ctrl+Q → Questions
        else if (e.ctrlKey && e.key === 'q') {
            e.preventDefault();
            window.location.href = 'index.php?page=questions';
        }
        // Ctrl+S → Students
        else if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            window.location.href = 'index.php?page=students';
        }
        // Escape → Close sidebar on mobile
        if (e.key === 'Escape' && window.innerWidth < 1024) {
            closeSidebar();
        }
    });

    // ---------- Active Nav Auto-Scroll ----------
    const activeLink = document.querySelector('.nav-link.bg-shikhbo-primary');
    if (activeLink) {
        activeLink.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }

    // ---------- Prevent Double Form Submission ----------
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Saving...';
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Save';
                }, 3000);
            }
        });
    });

});