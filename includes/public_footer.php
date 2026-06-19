<!-- Logout Confirm Modal -->
<div class="modal-overlay" id="logoutModal">
    <div class="modal-backdrop" onclick="closeLogoutModal()"></div>
    <div class="modal-panel">
        <div class="text-center">
            <div class="w-16 h-16 mx-auto bg-gradient-to-br from-red-400 to-red-600 rounded-2xl flex items-center justify-center shadow-lg mb-4">
                <i class="fa-solid fa-right-from-bracket text-2xl text-white"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">Logout</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Are you sure you want to leave?<br>You can always come back.</p>
        </div>
        <div class="mt-6 flex gap-3">
            <button onclick="closeLogoutModal()" class="flex-1 px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">Cancel</button>
            <a href="/pages/logout.php" id="logoutConfirmBtn" class="flex-1 px-4 py-3 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-xl text-sm font-semibold hover:from-red-600 hover:to-red-700 transition-all text-center shadow-lg shadow-red-200 dark:shadow-none">Logout</a>
        </div>
    </div>
</div>

<!-- Download Modal -->
<div class="dl-modal" id="dlModal">
    <div class="dl-backdrop" onclick="closeDownloadModal()"></div>
    <div class="dl-panel">
        <div class="text-center">
            <div class="w-14 h-14 mx-auto bg-blue-50 dark:bg-blue-900/30 rounded-2xl flex items-center justify-center mb-3">
                <img src="/image/app_logo.png" alt="" class="w-8 h-8 object-contain">
            </div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100" id="dlTitle">Downloading Shikhbo</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" id="dlMeta">Shikhbo v2.1.0 &middot; 24.5 MB</p>
        </div>
        <div class="mt-6 space-y-3">
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500 dark:text-gray-400" id="dlStatus">Preparing download...</span>
                <span class="text-gray-900 dark:text-gray-100 font-medium dl-speed" id="dlPercent">0%</span>
            </div>
            <div class="dl-progress-track">
                <div class="dl-progress-bar" id="dlProgressBar"></div>
            </div>
            <div class="flex items-center justify-between text-xs text-gray-400 dark:text-gray-500">
                <span id="dlSpeed">0 MB/s</span>
                <span id="dlRemaining">--</span>
            </div>
        </div>
        <div class="mt-6 flex gap-3" id="dlActions">
            <button onclick="closeDownloadModal()" id="dlCancelBtn" class="flex-1 px-4 py-2.5 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Cancel</button>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="bg-gray-900 dark:bg-gray-950">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 text-center">
        <p class="text-gray-500 text-sm">&copy; <?php echo date('Y'); ?> Shikhbo. All rights reserved.</p>
    </div>
</footer>
</div><!-- /.min-h-screen -->

<script>
(function() {
    const nav = document.getElementById('siteNav');
    if (nav) {
        let ticking = false;
        function updateNav() {
            if (window.scrollY > 60) nav.classList.add('scrolled');
            else nav.classList.remove('scrolled');
            ticking = false;
        }
        window.addEventListener('scroll', () => {
            if (!ticking) { requestAnimationFrame(updateNav); ticking = true; }
        }, { passive: true });
        updateNav();
    }

    const menuToggle = document.getElementById('menuToggle');
    const mobileMenu = document.getElementById('mobileMenu');
    const mobileOverlay = document.getElementById('mobileOverlay');
    const mobileClose = document.getElementById('mobileClose');

    if (menuToggle && mobileMenu && mobileOverlay) {
        function openMenu() { mobileMenu.classList.add('open'); mobileOverlay.classList.add('open'); document.body.style.overflow = 'hidden'; }
        function closeMenu() { mobileMenu.classList.remove('open'); mobileOverlay.classList.remove('open'); document.body.style.overflow = ''; }
        menuToggle.addEventListener('click', openMenu);
        if (mobileClose) mobileClose.addEventListener('click', closeMenu);
        mobileOverlay.addEventListener('click', closeMenu);
        document.querySelectorAll('.mobile-nav-link').forEach(l => l.addEventListener('click', closeMenu));
    }

    const navLinks = document.querySelectorAll('.nav-link');
    function updateActive() {
        const fromTop = window.scrollY + 100;
        let current = '';
        document.querySelectorAll('.scroll-section, #home').forEach(s => {
            if (s.offsetTop <= fromTop) current = s.getAttribute('id');
        });
        navLinks.forEach(l => {
            l.classList.toggle('active', l.getAttribute('href') === '#' + current);
        });
    }
    window.addEventListener('scroll', updateActive, { passive: true });
    updateActive();

    document.querySelectorAll('a[href^="#"], a[href^="/#"]').forEach(a => {
        a.addEventListener('click', function(e) {
            const hash = this.getAttribute('href').replace(/^\//, '');
            if (hash === '#') return;
            const t = document.querySelector(hash);
            if (t) { e.preventDefault(); t.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
        });
    });

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('show');
                entry.target.querySelectorAll(':scope > *').forEach((c, i) => c.style.transitionDelay = (i * 0.08) + 's');
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
    document.querySelectorAll('.anim-fade-up, .anim-stagger').forEach(el => observer.observe(el));

    const pContainer = document.getElementById('particles');
    if (pContainer) {
        for (let i = 0; i < 25; i++) {
            const p = document.createElement('div');
            p.className = 'particle';
            const s = 2 + Math.random() * 4;
            p.style.cssText = `width:${s}px;height:${s}px;left:${Math.random()*100}%;top:${Math.random()*100}%;animation-duration:${8+Math.random()*12}s;animation-delay:${Math.random()*5}s`;
            pContainer.appendChild(p);
        }
    }
})();

let dlCancel = false;
function openDownloadModal(type) {
    dlCancel = false;
    const modal = document.getElementById('dlModal');
    const bar = document.getElementById('dlProgressBar');
    const pct = document.getElementById('dlPercent');
    const status = document.getElementById('dlStatus');
    const speed = document.getElementById('dlSpeed');
    const remain = document.getElementById('dlRemaining');
    const title = document.getElementById('dlTitle');
    const meta = document.getElementById('dlMeta');
    const actions = document.getElementById('dlActions');

    if (type === 'play') {
        window.open('https://play.google.com/store/apps/details?id=com.shikhbo.app', '_blank');
        return;
    }

    title.textContent = 'Downloading Shikhbo';
    meta.textContent = 'Shikhbo v2.1.0 \u00b7 24.5 MB';
    bar.style.width = '0%';
    pct.textContent = '0%';
    status.textContent = 'Preparing download...';
    status.className = 'text-sm text-gray-500 dark:text-gray-400';
    speed.textContent = '0 MB/s';
    remain.textContent = '--';
    actions.innerHTML = '<button onclick="closeDownloadModal()" class="flex-1 px-4 py-2.5 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Cancel</button>';

    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
    startDownload(bar, pct, status, speed, remain, actions);
}
function closeDownloadModal() {
    dlCancel = true;
    const modal = document.getElementById('dlModal');
    modal.classList.remove('open');
    document.body.style.overflow = '';
}
function startDownload(bar, pct, status, speed, remain, actions) {
    const totalSize = 24.5;
    let downloaded = 0;
    const startTime = Date.now();
    const speeds = [];
    let phase = 0;
    const phaseInterval = setInterval(() => {
        if (dlCancel) { clearInterval(phaseInterval); return; }
        status.textContent = 'Preparing' + '.'.repeat((phase % 3) + 1);
        phase++;
    }, 400);
    setTimeout(() => {
        clearInterval(phaseInterval);
        if (dlCancel) return;
        status.textContent = 'Downloading...';
        status.className = 'text-sm text-blue-600 dark:text-blue-400 font-medium dl-status-pulse';
        const dlInterval = setInterval(() => {
            if (dlCancel) { clearInterval(dlInterval); status.textContent = 'Cancelled'; status.className = 'text-sm text-gray-500 dark:text-gray-400'; speed.textContent = '0 MB/s'; return; }
            const increment = 0.3 + Math.random() * 1.2;
            downloaded = Math.min(downloaded + increment, totalSize);
            const progress = (downloaded / totalSize) * 100;
            bar.style.width = progress + '%';
            pct.textContent = Math.round(progress) + '%';
            const elapsed = (Date.now() - startTime) / 1000;
            const currentSpeed = downloaded / elapsed;
            speeds.push(currentSpeed);
            if (speeds.length > 10) speeds.shift();
            const avgSpeed = speeds.reduce((a, b) => a + b, 0) / speeds.length;
            speed.textContent = avgSpeed.toFixed(1) + ' MB/s';
            const remaining = (totalSize - downloaded) / (avgSpeed || 1);
            remain.textContent = remaining > 60 ? Math.floor(remaining/60) + 'm ' + Math.floor(remaining%60) + 's remaining' : Math.max(1, Math.ceil(remaining)) + 's remaining';
            if (progress >= 100) { clearInterval(dlInterval); finishDownload(bar, pct, status, speed, remain, actions); }
        }, 180);
    }, 1500);
}
function finishDownload(bar, pct, status, speed, remain, actions) {
    bar.style.width = '100%';
    pct.textContent = '100%';
    status.textContent = 'Verifying package...';
    status.className = 'text-sm text-blue-600 dark:text-blue-400 font-medium';
    setTimeout(() => {
        if (dlCancel) return;
        status.textContent = '\u2705 Download complete';
        status.className = 'text-sm text-emerald-600 dark:text-emerald-400 font-medium';
        speed.textContent = '';
        remain.textContent = '';
        document.getElementById('dlTitle').textContent = 'Ready to Install';
        actions.innerHTML = '<button onclick="closeDownloadModal()" class="flex-1 px-4 py-2.5 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Close</button><a href="/api/download.php" class="flex-1 px-4 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700 transition-colors text-center"><i class="fa-solid fa-download mr-1.5"></i> Save File</a>';
    }, 800);
}

function openLogoutModal() {
    const m = document.getElementById('logoutModal');
    if (!m) return;
    document.body.style.overflow = 'hidden';
    m.classList.add('open');
}
function closeLogoutModal() {
    const m = document.getElementById('logoutModal');
    if (!m) return;
    m.classList.remove('open');
    document.body.style.overflow = '';
}
function showToast(title, message, type) {
    type = type || 'success';
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'fixed bottom-4 right-4 z-[100] flex flex-col-reverse space-y-reverse space-y-2';
        document.body.appendChild(container);
    }
    const toast = document.createElement('div');
    const colorClass = type === 'success' ? 'bg-emerald-500 text-white' : type === 'info' ? 'bg-blue-500 text-white' : 'bg-red-500 text-white';
    const iconClass = type === 'success' ? 'fa-circle-check' : type === 'info' ? 'fa-circle-info' : 'fa-circle-xmark';
    toast.className = colorClass + ' px-5 py-3.5 rounded-xl shadow-lg flex items-center gap-3 text-sm font-semibold transition-all duration-300 transform translate-y-4 opacity-0';
    toast.innerHTML = '<i class="fa-solid ' + iconClass + ' text-lg"></i> <div><span class="block font-bold">' + title + '</span><span class="text-xs opacity-90">' + message + '</span></div>';
    container.appendChild(toast);
    setTimeout(function() { toast.classList.remove('translate-y-4', 'opacity-0'); }, 10);
    setTimeout(function() { toast.classList.add('opacity-0', 'translate-y-2'); setTimeout(function() { toast.remove(); }, 300); }, 4000);
}

var _uid = <?php echo $loggedInUser ? intval($loggedInUser['id']) : 0; ?>;
function enrollCourse(courseId, isFree) {
    if (isFree) {
        showToast('Enrolling', 'Please wait...', 'success');
        var body = { course_id: courseId, action: 'enroll' };
        if (_uid > 0) { body.uid = _uid; body.season = 'web'; body.u_state = '1'; }
        fetch('/api/enroll_course_app.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        }).then(function(r){ return r.json(); }).then(function(data){
            if (data.status === 'success') { showToast('Success', 'Enrolled successfully!', 'success'); setTimeout(function(){ window.location.reload(); }, 1500); }
            else { showToast('Error', data.message || 'Enrollment failed', 'error'); }
        }).catch(function(){ showToast('Error', 'An error occurred during enrollment', 'error'); });
    } else {
        checkoutCourse(courseId);
    }
}

function checkoutCourse(courseId) {
    showToast('Redirecting', 'Preparing payment gateway...', 'success');
    fetch('/api/piprapay_initiate.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ course_id: courseId })
    }).then(function(r){ return r.json(); }).then(function(data){
        if (data.status === 'success' && data.checkout_url) { window.location.href = data.checkout_url; }
        else { showToast('Error', data.message || 'Payment initiation failed', 'error'); }
    }).catch(function(){ showToast('Error', 'An error occurred starting payment checkout', 'error'); });
}

</script>
</body>
</html>
