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

    // ---------- Sidebar Toggle (Mobile) ----------
    function openSidebar() {
        sidebar.classList.add('open');
        sidebar.classList.remove('-translate-x-full');
        sidebar.classList.add('translate-x-0');
        sidebarOverlay.classList.remove('hidden');
        document.body.classList.add('sidebar-open');
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        sidebar.classList.add('-translate-x-full');
        sidebar.classList.remove('translate-x-0');
        sidebarOverlay.classList.add('hidden');
        document.body.classList.remove('sidebar-open');
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', openSidebar);
    }

    if (sidebarClose) {
        sidebarClose.addEventListener('click', closeSidebar);
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', closeSidebar);
    }

    // ---------- Profile Dropdown ----------
    if (profileButton && profileMenu) {
        profileButton.addEventListener('click', function (e) {
            e.stopPropagation();
            profileMenu.classList.toggle('hidden');
        });

        document.addEventListener('click', function (e) {
            if (!profileButton.contains(e.target) && !profileMenu.contains(e.target)) {
                profileMenu.classList.add('hidden');
            }
        });
    }

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
        if (e.ctrlKey && e.key === 'd') {
            e.preventDefault();
            window.location.href = 'index.php?page=dashboard';
        } else if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            window.location.href = 'index.php?page=students';
        }
    });

});