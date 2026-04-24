// ========================================================
// Shikhbo Admin Panel — Enhanced JS v6
// Modern UI Interactions • Smooth Animations • Dark Mode
// ========================================================

(function() {
    'use strict';

    // ── DOM Elements ──
    const dom = {
        sidebar: document.getElementById('sidebar'),
        sidebarToggle: document.getElementById('sidebarToggle'),
        sidebarClose: document.getElementById('sidebarClose'),
        sidebarOverlay: document.getElementById('sidebarOverlay'),
        profileButton: document.getElementById('profileButton'),
        profileMenu: document.getElementById('profileMenu'),
        dropdownChevron: document.getElementById('dropdownChevron'),
        notifButton: document.getElementById('notifButton'),
        notifPanel: document.getElementById('notifPanel'),
        ticketButton: document.getElementById('ticketButton'),
        ticketPanel: document.getElementById('ticketPanel'),
        themeToggle: document.getElementById('themeToggle'),
        themeIcon: document.getElementById('themeIcon'),
        toastContainer: document.getElementById('toastContainer')
    };

    // ── Sidebar Toggle (Mobile) ──
    function openSidebar() {
        if (!dom.sidebar) return;
        dom.sidebar.classList.add('open', 'translate-x-0');
        dom.sidebar.classList.remove('-translate-x-full');
        dom.sidebarOverlay?.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        setTimeout(() => {
            dom.sidebarOverlay?.style.setProperty('opacity', '1');
        }, 10);
    }

    function closeSidebar() {
        if (!dom.sidebar) return;
        dom.sidebarOverlay?.style.setProperty('opacity', '0');
        setTimeout(() => {
            dom.sidebar.classList.remove('open', 'translate-x-0');
            dom.sidebar.classList.add('-translate-x-full');
            dom.sidebarOverlay?.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }, 200);
    }

    dom.sidebarToggle?.addEventListener('click', openSidebar);
    dom.sidebarClose?.addEventListener('click', closeSidebar);
    dom.sidebarOverlay?.addEventListener('click', closeSidebar);

    // ── Profile Dropdown ──
    let profileOpen = false;

    function toggleProfile() {
        profileOpen = !profileOpen;
        if (profileOpen) {
            dom.profileMenu?.classList.remove('hidden', 'opacity-0', 'scale-95');
            dom.profileMenu?.classList.add('opacity-100', 'scale-100', 'dropdown-menu');
            dom.dropdownChevron && (dom.dropdownChevron.style.transform = 'rotate(180deg)');
            closeAllPanelsExcept('profile');
        } else {
            hideProfile();
        }
    }

    function hideProfile() {
        profileOpen = false;
        dom.profileMenu?.classList.add('hidden', 'opacity-0', 'scale-95');
        dom.profileMenu?.classList.remove('opacity-100', 'scale-100', 'dropdown-menu');
        dom.dropdownChevron && (dom.dropdownChevron.style.transform = 'rotate(0deg)');
    }

    dom.profileButton?.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleProfile();
    });

    document.addEventListener('click', (e) => {
        if (profileOpen && 
            !dom.profileButton?.contains(e.target) && 
            !dom.profileMenu?.contains(e.target)) {
            hideProfile();
        }
    });

    // ── Logout Modal ──
    const logoutModal = document.getElementById('logoutModal');
    const logoutBtn = document.getElementById('logoutBtn');
    const logoutCancel = document.getElementById('logoutCancel');

    logoutBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        logoutModal?.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    });

    logoutCancel?.addEventListener('click', () => {
        logoutModal?.classList.add('hidden');
        document.body.style.overflow = '';
    });

    logoutModal?.addEventListener('click', (e) => {
        if (e.target === logoutModal) {
            logoutModal.classList.add('hidden');
            document.body.style.overflow = '';
        }
    });

    // ── Notification Panel ──
    function toggleNotifications() {
        const panel = dom.notifPanel;
        if (!panel) return;
        
        const isHidden = panel.classList.contains('hidden');
        
        closeAllPanelsExcept('notifications');
        
        if (isHidden) {
            panel.classList.remove('hidden');
            requestAnimationFrame(() => {
                panel.classList.remove('opacity-0', 'scale-95');
                panel.classList.add('opacity-100', 'scale-100', 'dropdown-menu');
            });
        } else {
            closePanel(panel);
        }
    }

    dom.notifButton?.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleNotifications();
    });

    // ── Ticket Panel ──
    function toggleTickets() {
        const panel = dom.ticketPanel;
        if (!panel) return;
        
        const isHidden = panel.classList.contains('hidden');
        
        closeAllPanelsExcept('tickets');
        
        if (isHidden) {
            panel.classList.remove('hidden');
            requestAnimationFrame(() => {
                panel.classList.remove('opacity-0', 'scale-95');
                panel.classList.add('opacity-100', 'scale-100', 'dropdown-menu');
            });
        } else {
            closePanel(panel);
        }
    }

    dom.ticketButton?.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleTickets();
    });

    // ── Panel Helpers ──
    function closePanel(panel) {
        if (!panel || panel.classList.contains('hidden')) return;
        panel.classList.add('opacity-0', 'scale-95');
        panel.classList.remove('opacity-100', 'scale-100', 'dropdown-menu');
        setTimeout(() => panel.classList.add('hidden'), 200);
    }

    function closeAllPanelsExcept(except) {
        if (except !== 'notifications') closePanel(dom.notifPanel);
        if (except !== 'tickets') closePanel(dom.ticketPanel);
        if (except !== 'profile') hideProfile();
    }

    // Close panels on outside click
    document.addEventListener('click', (e) => {
        if (dom.notifPanel && !dom.notifPanel.classList.contains('hidden') && 
            !dom.notifButton?.contains(e.target) && !dom.notifPanel.contains(e.target)) {
            closePanel(dom.notifPanel);
        }
        if (dom.ticketPanel && !dom.ticketPanel.classList.contains('hidden') && 
            !dom.ticketButton?.contains(e.target) && !dom.ticketPanel.contains(e.target)) {
            closePanel(dom.ticketPanel);
        }
    });

    // ── Dark / Light Mode ──
    const theme = {
        saved: localStorage.getItem('shikhbo-theme'),
        system: window.matchMedia('(prefers-color-scheme: dark)').matches,
        
        apply(themeName) {
            const isDark = themeName === 'dark';
            document.documentElement.classList.toggle('dark', isDark);
            
            if (dom.themeIcon) {
                dom.themeIcon.classList.toggle('fa-moon', !isDark);
                dom.themeIcon.classList.toggle('fa-sun', isDark);
            }
            
            localStorage.setItem('shikhbo-theme', themeName);
        },
        
        toggle() {
            const isDark = document.documentElement.classList.contains('dark');
            this.apply(isDark ? 'light' : 'dark');
        },
        
        init() {
            this.apply(this.saved || (this.system ? 'dark' : 'light'));
        }
    };

    theme.init();
    dom.themeToggle?.addEventListener('click', () => theme.toggle());

    // ── Toast Notifications ──
    window.showToast = function(message, type = 'info', duration = 4000) {
        const container = dom.toastContainer;
        if (!container) return;
        
        const icons = {
            success: 'fa-circle-check',
            error: 'fa-circle-exclamation',
            warning: 'fa-triangle-exclamation',
            info: 'fa-circle-info'
        };
        
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <i class="fa-solid ${icons[type] || icons.info} text-lg"></i>
            <span class="flex-1">${message}</span>
            <button onclick="this.parentElement.remove()" class="ml-2 opacity-70 hover:opacity-100 transition-opacity">
                <i class="fa-solid fa-xmark"></i>
            </button>
        `;
        
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('toast-exit');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    };

    // ── Session Timeout Warning ──
    (function() {
        const SESSION_DURATION = 1800000; // 30 minutes
        const WARNING_BEFORE = 300000;    // 5 minutes before
        let timeout;
        
        function resetTimer() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                const stay = confirm('Session expiring soon. Click OK to stay logged in or Cancel to logout.');
                if (stay) {
                    window.location.reload();
                } else {
                    window.location.href = '/pages/logout.php';
                }
            }, SESSION_DURATION - WARNING_BEFORE);
        }
        
        ['click', 'keypress', 'scroll', 'mousemove'].forEach(event => {
            document.addEventListener(event, resetTimer, { passive: true });
        });
        
        resetTimer();
    })();

    // ── Resize Handler ──
    function handleResize() {
        if (window.innerWidth >= 1024) {
            dom.sidebar?.classList.remove('-translate-x-full');
            dom.sidebar?.classList.add('translate-x-0');
            dom.sidebarOverlay?.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        } else {
            if (!dom.sidebar?.classList.contains('open')) {
                dom.sidebar?.classList.add('-translate-x-full');
                dom.sidebar?.classList.remove('translate-x-0');
            }
        }
    }

    window.addEventListener('resize', handleResize, { passive: true });
    handleResize();

    // ── Keyboard Shortcuts ──
    document.addEventListener('keydown', (e) => {
        if (e.ctrlKey || e.metaKey) {
            switch (e.key.toLowerCase()) {
                case 'd': e.preventDefault(); window.location.href = 'index.php?page=dashboard'; break;
                case 'e': e.preventDefault(); window.location.href = 'index.php?page=exams'; break;
                case 'q': e.preventDefault(); window.location.href = 'index.php?page=questions'; break;
                case 's': e.preventDefault(); window.location.href = 'index.php?page=students'; break;
            }
        }
        
        if (e.key === 'Escape') {
            closeAllPanelsExcept(null);
            if (window.innerWidth < 1024) closeSidebar();
        }
    });

    // ── Active Nav Scroll ──
    const activeLink = document.querySelector('.nav-link.active');
    if (activeLink) {
        activeLink.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }

    // ── Form Submit Prevention ──
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const btn = this.querySelector('button[type="submit"]');
            if (btn && !btn.disabled) {
                btn.disabled = true;
                const orig = btn.innerHTML;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Processing...';
                btn.classList.add('opacity-75');
                
                setTimeout(() => {
                    btn.disabled = false;
                    btn.innerHTML = orig;
                    btn.classList.remove('opacity-75');
                }, 3000);
            }
        });
    });

    // ── Mobile Touch Handling ──
    document.addEventListener('touchstart', (e) => {
        if (dom.notifPanel && !dom.notifPanel.classList.contains('hidden') && 
            !dom.notifPanel.contains(e.target) && !dom.notifButton?.contains(e.target)) {
            closePanel(dom.notifPanel);
        }
        if (dom.ticketPanel && !dom.ticketPanel.classList.contains('hidden') && 
            !dom.ticketPanel.contains(e.target) && !dom.ticketButton?.contains(e.target)) {
            closePanel(dom.ticketPanel);
        }
    }, { passive: true });

    // ── Auto-dismiss Alerts ──
    document.querySelectorAll('.alert-auto-dismiss').forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });

    // ── Table Row Actions ──
    document.querySelectorAll('.table-row[data-href]').forEach(row => {
        row.style.cursor = 'pointer';
        row.addEventListener('click', () => {
            window.location.href = row.dataset.href;
        });
    });

    // ── Search Input Enhancement ──
    document.querySelectorAll('input[type="search"], input[type="text"][placeholder*="Search"]').forEach(input => {
        input.addEventListener('input', function() {
            this.classList.toggle('has-value', this.value.length > 0);
        });
    });

    // ── Confirmation Dialogs ──
    window.confirmAction = function(message, callback) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 z-50 flex items-center justify-center modal-backdrop bg-black/50';
        modal.innerHTML = `
            <div class="modal-content bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-6 max-w-md mx-4 text-center">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                    <i class="fa-solid fa-triangle-exclamation text-2xl text-red-600 dark:text-red-400"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">Confirm Action</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-6">${message}</p>
                <div class="flex justify-center gap-3">
                    <button class="cancel-btn px-5 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Cancel</button>
                    <button class="confirm-btn px-5 py-2.5 bg-red-600 text-white rounded-xl hover:bg-red-700 transition-colors">Confirm</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        modal.querySelector('.cancel-btn').addEventListener('click', () => {
            modal.remove();
        });
        
        modal.querySelector('.confirm-btn').addEventListener('click', () => {
            modal.remove();
            callback && callback();
        });
        
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.remove();
        });
    };

})();

// ── Ticket Modal Functions (global) ──
function openTicketModal() {
    const ticketPanel = document.getElementById('ticketPanel');
    if (ticketPanel) {
        ticketPanel.classList.add('opacity-0', 'scale-95', 'hidden');
        ticketPanel.classList.remove('opacity-100', 'scale-100');
    }
    document.getElementById('ticketModal')?.classList.remove('hidden');
    document.getElementById('ticketModal')?.classList.add('modal-backdrop');
}

function closeTicketModal() {
    const modal = document.getElementById('ticketModal');
    if (modal) {
        modal.classList.add('modal-exit');
        setTimeout(() => {
            modal.classList.remove('hidden', 'modal-exit', 'modal-backdrop');
        }, 200);
    }
}

function submitTicket(e) {
    e.preventDefault();
    const subject = document.getElementById('ticketSubject')?.value.trim();
    const message = document.getElementById('ticketMessage')?.value.trim();
    const csrf = document.getElementById('csrf_token')?.value;
    
    if (!subject || !message) {
        showToast('Please fill all fields', 'warning');
        return;
    }
    
    const btn = e.target.querySelector('button[type="submit"]');
    const origHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Submitting...';
    
    fetch('/api/submit_ticket.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ subject, message, csrf_token: csrf })
    })
    .then(r => r.json())
    .then(d => {
        if (d.status === 'success') {
            showToast('Ticket submitted successfully!', 'success');
            closeTicketModal();
            document.getElementById('ticketSubject').value = '';
            document.getElementById('ticketMessage').value = '';
        } else {
            showToast(d.message || 'Error submitting ticket', 'error');
        }
    })
    .catch(() => showToast('Network error. Please try again.', 'error'))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = origHTML;
    });
}

// ── Loading Overlay ──
window.showLoading = function() {
    const overlay = document.createElement('div');
    overlay.id = 'loading-overlay';
    overlay.className = 'fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 backdrop-blur-sm';
    overlay.innerHTML = `
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 text-center">
            <div class="w-12 h-12 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
            <p class="text-gray-600 dark:text-gray-300">Loading...</p>
        </div>
    `;
    document.body.appendChild(overlay);
};

window.hideLoading = function() {
    document.getElementById('loading-overlay')?.remove();
};

// ── Copy to Clipboard ──
window.copyToClipboard = function(text, message = 'Copied!') {
    navigator.clipboard.writeText(text).then(() => {
        showToast(message, 'success');
    }).catch(() => {
        showToast('Failed to copy', 'error');
    });
};

// ── Debounce Utility ──
window.debounce = function(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
};

// ── Format Number ──
window.formatNumber = function(num) {
    return new Intl.NumberFormat().format(num);
};

// ── Format Date ──
window.formatDate = function(date, format = 'short') {
    const d = new Date(date);
    const options = format === 'short' 
        ? { month: 'short', day: 'numeric', year: 'numeric' }
        : { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return d.toLocaleDateString('en-US', options);
};