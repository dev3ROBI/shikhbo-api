// ========================================================
// Shikhbo Admin Panel - Custom JavaScript
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

        // Close dropdown when clicking outside
        document.addEventListener('click', function (e) {
            if (!profileButton.contains(e.target) && !profileMenu.contains(e.target)) {
                profileMenu.classList.add('hidden');
            }
        });
    }

    // ---------- Active Navigation Highlight ----------
    const navLinks = document.querySelectorAll('nav a');
    const currentPath = window.location.pathname;

    navLinks.forEach(link => {
        if (link.getAttribute('href') === currentPath || 
            (currentPath.endsWith('/') && link.getAttribute('href') === 'index.php')) {
            link.classList.add('bg-shikhbo-primary', 'text-white');
            link.classList.remove('text-gray-600', 'hover:bg-gray-100', 'hover:text-shikhbo-primary');
        }
    });

    // ---------- Mobile Responsive Helper ----------
    function handleResize() {
        if (window.innerWidth >= 1024) {
            // Ensure sidebar is visible on desktop
            sidebar.classList.remove('-translate-x-full');
            sidebar.classList.add('translate-x-0');
            sidebarOverlay.classList.add('hidden');
            document.body.classList.remove('sidebar-open');
        } else {
            // Hide sidebar on mobile initially
            if (!sidebar.classList.contains('open')) {
                sidebar.classList.add('-translate-x-full');
                sidebar.classList.remove('translate-x-0');
            }
        }
    }

    window.addEventListener('resize', handleResize);
    handleResize(); // Initial check

});