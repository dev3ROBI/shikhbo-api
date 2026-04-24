// ========================================================
// Shikhbo Admin Panel — Enhanced JS v5
// Notification Panel · Tickets · Mobile Optimised · Dark/Light Mode Toggle
// ========================================================

document.addEventListener('DOMContentLoaded', function () {

    // ── DOM Elements ──
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarClose = document.getElementById('sidebarClose');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const profileButton = document.getElementById('profileButton');
    const profileMenu = document.getElementById('profileMenu');
    const dropdownChevron = document.getElementById('dropdownChevron');

    // ── Sidebar (Mobile) ──
    function openSidebar() {
        sidebar.classList.add('open'); sidebar.classList.remove('-translate-x-full');
        sidebar.classList.add('translate-x-0'); sidebarOverlay.classList.remove('hidden');
        document.body.classList.add('sidebar-open'); sidebarOverlay.style.opacity = '1';
    }
    function closeSidebar() {
        sidebar.classList.remove('open'); sidebar.classList.add('-translate-x-full');
        sidebar.classList.remove('translate-x-0'); sidebarOverlay.classList.add('hidden');
        document.body.classList.remove('sidebar-open');
    }
    if (sidebarToggle) sidebarToggle.addEventListener('click', openSidebar);
    if (sidebarClose) sidebarClose.addEventListener('click', closeSidebar);
    if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);

    // ── Profile Dropdown ──
    if (profileButton && profileMenu) {
        profileButton.addEventListener('click', function(e) {
            e.stopPropagation();
            const hidden = profileMenu.classList.contains('hidden');
            if (hidden) {
                profileMenu.classList.remove('hidden');
                requestAnimationFrame(() => { profileMenu.classList.remove('opacity-0','scale-95'); profileMenu.classList.add('opacity-100','scale-100'); });
                if (dropdownChevron) dropdownChevron.style.transform = 'rotate(180deg)';
                closeAllPanelsExcept('profileMenu');
            } else { hideProfileDropdown(); }
        });
        document.addEventListener('click', function(e) {
            if (!profileButton.contains(e.target) && !profileMenu.contains(e.target)) hideProfileDropdown();
        });
    }
    function hideProfileDropdown() {
        profileMenu.classList.add('opacity-0','scale-95','hidden');
        profileMenu.classList.remove('opacity-100','scale-100');
        if (dropdownChevron) dropdownChevron.style.transform = 'rotate(0deg)';
    }

    // ── Notification Panel ──
    const notifButton = document.getElementById('notifButton');
    const notifPanel = document.getElementById('notifPanel');
    if (notifButton && notifPanel) {
        notifButton.addEventListener('click', function(e) {
            e.stopPropagation();
            togglePanel(notifPanel);
            closePanel(document.getElementById('ticketPanel'));
            hideProfileDropdown();
        });
    }

    // ── Ticket Panel ──
    const ticketButton = document.getElementById('ticketButton');
    const ticketPanel = document.getElementById('ticketPanel');
    if (ticketButton && ticketPanel) {
        ticketButton.addEventListener('click', function(e) {
            e.stopPropagation();
            togglePanel(ticketPanel);
            closePanel(notifPanel);
            hideProfileDropdown();
        });
    }

    function togglePanel(panel) {
        if (!panel) return;
        const hidden = panel.classList.contains('hidden');
        if (hidden) {
            panel.classList.remove('hidden');
            requestAnimationFrame(() => { panel.classList.remove('opacity-0','scale-95'); panel.classList.add('opacity-100','scale-100'); });
        } else {
            panel.classList.add('opacity-0','scale-95'); panel.classList.remove('opacity-100','scale-100');
            setTimeout(() => panel.classList.add('hidden'), 200);
        }
    }
    function closePanel(panel) { if (panel && !panel.classList.contains('hidden')) { panel.classList.add('opacity-0','scale-95','hidden'); panel.classList.remove('opacity-100','scale-100'); } }
    function closeAllPanelsExcept(exceptId) {
        [notifPanel, ticketPanel].forEach(p => { if (p && p.id !== exceptId) closePanel(p); });
    }

    // Close panels on outside click
    document.addEventListener('click', function(e) {
        if (notifPanel && !notifButton.contains(e.target) && !notifPanel.contains(e.target)) closePanel(notifPanel);
        if (ticketPanel && !ticketButton.contains(e.target) && !ticketPanel.contains(e.target)) closePanel(ticketPanel);
    });

    // ── Dark / Light Mode Toggle ──
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');

    function applyTheme(theme) {
        if (theme === 'dark') {
            document.documentElement.classList.add('dark');
            if (themeIcon) {
                themeIcon.classList.remove('fa-moon');
                themeIcon.classList.add('fa-sun');
            }
        } else {
            document.documentElement.classList.remove('dark');
            if (themeIcon) {
                themeIcon.classList.remove('fa-sun');
                themeIcon.classList.add('fa-moon');
            }
        }
        localStorage.setItem('shikhbo-theme', theme);
    }

    // Determine initial theme
    const savedTheme = localStorage.getItem('shikhbo-theme');
    if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        applyTheme('dark');
    } else {
        applyTheme('light');
    }

    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            const isDark = document.documentElement.classList.contains('dark');
            applyTheme(isDark ? 'light' : 'dark');
        });
    }

    // ── Toast ──
    window.showToast = function(message, type = 'info', duration = 4000) {
        const container = document.getElementById('toastContainer');
        if (!container) return;
        const icons = { success:'fa-circle-check', error:'fa-circle-exclamation', warning:'fa-triangle-exclamation', info:'fa-circle-info' };
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `<i class="fa-solid ${icons[type]||icons.info}"></i><span>${message}</span>`;
        container.appendChild(toast);
        setTimeout(() => { toast.classList.add('toast-exit'); setTimeout(() => toast.remove(), 300); }, duration);
    };

    // ── Session Timeout ──
    let sessionTimeout;
    const SESSION_DURATION = 1800000, WARNING_BEFORE = 300000;
    function resetSessionTimer() {
        clearTimeout(sessionTimeout);
        sessionTimeout = setTimeout(function() {
            if (confirm('Session expiring. Continue?')) window.location.reload();
            else window.location.href = '/pages/logout.php';
        }, SESSION_DURATION - WARNING_BEFORE);
    }
    document.addEventListener('click', resetSessionTimer);
    document.addEventListener('keypress', resetSessionTimer);
    document.addEventListener('scroll', resetSessionTimer);
    resetSessionTimer();

    // ── Resize Handler ──
    function handleResize() {
        if (window.innerWidth >= 1024) {
            sidebar.classList.remove('-translate-x-full'); sidebar.classList.add('translate-x-0');
            sidebarOverlay.classList.add('hidden'); document.body.classList.remove('sidebar-open');
        } else {
            if (!sidebar.classList.contains('open')) { sidebar.classList.add('-translate-x-full'); sidebar.classList.remove('translate-x-0'); }
        }
    }
    window.addEventListener('resize', handleResize); handleResize();

    // ── Keyboard Shortcuts ──
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'd') { e.preventDefault(); window.location.href = 'index.php?page=dashboard'; }
        else if (e.ctrlKey && e.key === 'e') { e.preventDefault(); window.location.href = 'index.php?page=exams'; }
        else if (e.ctrlKey && e.key === 'q') { e.preventDefault(); window.location.href = 'index.php?page=questions'; }
        else if (e.ctrlKey && e.key === 's') { e.preventDefault(); window.location.href = 'index.php?page=students'; }
        if (e.key === 'Escape') { closePanel(notifPanel); closePanel(ticketPanel); hideProfileDropdown(); if (window.innerWidth < 1024) closeSidebar(); }
    });

    // ── Active Nav Scroll ──
    const activeLink = document.querySelector('.nav-link.bg-shikhbo-primary');
    if (activeLink) activeLink.scrollIntoView({ block: 'nearest', behavior: 'smooth' });

    // ── Double Submit Prevention ──
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            const btn = this.querySelector('button[type="submit"]');
            if (btn && !btn.disabled) { btn.disabled = true; const orig = btn.innerHTML; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i>Saving...'; setTimeout(() => { btn.disabled = false; btn.innerHTML = orig; }, 3000); }
        });
    });

    // ── Mobile Touch: Close panels on swipe/outside touch ──
    document.addEventListener('touchstart', function(e) {
        if (notifPanel && !notifPanel.classList.contains('hidden') && !notifPanel.contains(e.target) && !notifButton.contains(e.target)) closePanel(notifPanel);
        if (ticketPanel && !ticketPanel.classList.contains('hidden') && !ticketPanel.contains(e.target) && !ticketButton.contains(e.target)) closePanel(ticketPanel);
    }, { passive: true });

});

// ── Ticket Modal Functions (global) ──
function openTicketModal() {
    const ticketPanel = document.getElementById('ticketPanel');
    if (ticketPanel) {
        ticketPanel.classList.add('opacity-0','scale-95','hidden');
        ticketPanel.classList.remove('opacity-100','scale-100');
    }
    document.getElementById('ticketModal').classList.remove('hidden');
}
function closeTicketModal() { document.getElementById('ticketModal').classList.add('hidden'); }
function submitTicket(e) {
    e.preventDefault();
    const subject = document.getElementById('ticketSubject').value.trim();
    const message = document.getElementById('ticketMessage').value.trim();
    if (!subject || !message) return showToast('Please fill all fields', 'warning');
    fetch('/api/submit_ticket.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ subject, message, csrf_token: document.getElementById('csrf_token').value })
    }).then(r => r.json()).then(d => {
        if (d.status === 'success') { showToast('Ticket submitted!', 'success'); closeTicketModal(); }
        else showToast(d.message || 'Error', 'error');
    }).catch(() => showToast('Network error', 'error'));
}