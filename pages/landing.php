<style>
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(40px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50%      { transform: translateY(-14px); }
    }
    @keyframes gradientShift {
        0%   { background-position: 0% 50%; }
        50%  { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    .anim-fade-up     { opacity: 0; transform: translateY(40px); transition: opacity 0.7s ease, transform 0.7s ease; }
    .anim-fade-up.show{ opacity: 1; transform: translateY(0); }
    .anim-stagger > *  { opacity: 0; transform: translateY(30px); transition: opacity 0.5s ease, transform 0.5s ease; }
    .anim-stagger.show > * { opacity: 1; transform: translateY(0); }

    .delay-1 { transition-delay: 0.1s; }
    .delay-2 { transition-delay: 0.2s; }
    .delay-3 { transition-delay: 0.3s; }
    .delay-4 { transition-delay: 0.4s; }

    .hero-fade-in { opacity: 0; animation: fadeInUp 0.8s ease forwards; }
    .hero-fade-in.delay-1 { animation-delay: 0.1s; }
    .hero-fade-in.delay-2 { animation-delay: 0.3s; }
    .hero-fade-in.delay-3 { animation-delay: 0.5s; }
    .hero-fade-in.delay-4 { animation-delay: 0.7s; }

    .landing-gradient {
        background: linear-gradient(135deg, #0F172A 0%, #1E3A5F 40%, #312E81 100%);
        background-size: 200% 200%;
        animation: gradientShift 12s ease infinite;
    }
    .landing-gradient-solid {
        background: linear-gradient(135deg, #1E3A5F 0%, #312E81 100%);
    }

    .feature-card {
        transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        border: 1px solid rgba(0,0,0,0.04);
    }
    .feature-card:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 24px 48px -12px rgba(37, 99, 235, 0.15);
    }

    /* ── Download Modal ── */
    .dl-modal {
        position: fixed; inset: 0; z-index: 100;
        display: flex; align-items: center; justify-content: center;
        padding: 1rem;
        opacity: 0; pointer-events: none;
        transition: opacity 0.4s ease;
    }
    .dl-modal.open { opacity: 1; pointer-events: auto; }
    .dl-modal .dl-backdrop {
        position: absolute; inset: 0;
        background: rgba(0,0,0,0.5);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
    }
    .dl-modal .dl-panel {
        position: relative;
        background: #fff; border-radius: 1.25rem;
        width: 100%; max-width: 400px;
        padding: 2rem;
        box-shadow: 0 25px 60px rgba(0,0,0,0.2);
        transform: translateY(30px) scale(0.95);
        transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .dark .dl-modal .dl-panel { background: #1E293B; }
    .dl-modal.open .dl-panel { transform: translateY(0) scale(1); }

    .dl-progress-track {
        width: 100%; height: 6px;
        background: #E5E7EB; border-radius: 3px;
        overflow: hidden;
    }
    .dark .dl-progress-track { background: #374151; }
    .dl-progress-bar {
        height: 100%; width: 0%;
        background: linear-gradient(90deg, #2563EB, #3B82F6);
        border-radius: 3px;
        transition: width 0.3s ease;
    }

    .dl-speed {
        font-variant-numeric: tabular-nums;
    }

    @keyframes dlPulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    .dl-status-pulse {
        animation: dlPulse 1s ease-in-out infinite;
    }

    .download-btn {
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .download-btn:hover {
        transform: translateY(-3px) scale(1.03);
        box-shadow: 0 12px 30px -8px rgba(37, 99, 235, 0.4);
    }
    .download-btn:active {
        transform: scale(0.97);
    }

    .admin-section {
        background: linear-gradient(135deg, #1E293B 0%, #334155 100%);
    }
    .admin-link-card {
        transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .admin-link-card:hover {
        transform: translateY(-4px) scale(1.04);
        box-shadow: 0 12px 28px -8px rgba(79, 70, 229, 0.35);
    }

    .particles {
        position: absolute; inset: 0; overflow: hidden; pointer-events: none;
    }
    .particle {
        position: absolute;
        width: 4px; height: 4px;
        background: rgba(255,255,255,0.12);
        border-radius: 50%;
        animation: float linear infinite;
    }

    .landing-scroll::-webkit-scrollbar { width: 6px; }
    .landing-scroll::-webkit-scrollbar-track { background: transparent; }
    .landing-scroll::-webkit-scrollbar-thumb { background: rgba(37,99,235,0.3); border-radius: 3px; }

    .logo-glow { filter: drop-shadow(0 0 10px rgba(37,99,235,0.4)); }
    .logo-img { width: 36px; height: 36px; object-fit: contain; }
    .logo-img-sm { width: 28px; height: 28px; object-fit: contain; }
    .logo-img-lg { width: 72px; height: 72px; object-fit: contain; }
    .logo-img-xl { width: 56px; height: 56px; object-fit: contain; }

    /* ── Navbar ── */
    .site-nav {
        position: fixed; top: 0; left: 0; right: 0; z-index: 50;
        transition: background 0.4s ease, box-shadow 0.4s ease, padding 0.3s ease, backdrop-filter 0.4s ease;
        padding-top: 1rem; padding-bottom: 1rem;
    }
    .site-nav.scrolled {
        background: rgba(255,255,255,0.9);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        padding-top: 0.5rem; padding-bottom: 0.5rem;
    }
    .dark .site-nav.scrolled {
        background: rgba(15,23,42,0.9);
    }
    .site-nav .nav-link {
        position: relative;
        color: rgba(255,255,255,0.8);
        font-size: 0.875rem;
        font-weight: 500;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        transition: color 0.3s ease, background 0.3s ease;
    }
    .site-nav .nav-link:hover {
        color: #fff;
        background: rgba(255,255,255,0.08);
    }
    .site-nav .nav-link.active,
    .site-nav .nav-link.active:hover {
        background: transparent;
        color: rgba(255,255,255,0.8);
    }
    .site-nav .nav-link::after {
        content: '';
        position: absolute;
        bottom: 2px; left: 50%;
        width: 0; height: 2px;
        background: #60A5FA;
        border-radius: 2px;
        transform: translateX(-50%);
        transition: width 0.3s ease;
    }
    .site-nav .nav-link:hover::after,
    .site-nav .nav-link.active::after {
        width: 60%;
    }
    .site-nav.scrolled .nav-link {
        color: #4B5563;
    }
    .dark .site-nav.scrolled .nav-link {
        color: #D1D5DB;
    }
    .site-nav.scrolled .nav-link:hover {
        color: #2563EB;
    }
    .site-nav.scrolled .nav-link.active,
    .site-nav.scrolled .nav-link.active:hover {
        color: #4B5563;
    }
    .dark .site-nav.scrolled .nav-link.active,
    .dark .site-nav.scrolled .nav-link.active:hover {
        color: #D1D5DB;
    }
    .site-nav.scrolled .nav-link::after {
        background: #2563EB;
    }
    .site-nav.scrolled .nav-brand-text {
        color: #1F2937;
    }
    .dark .site-nav.scrolled .nav-brand-text {
        color: #F3F4F6;
    }
    .site-nav.scrolled .login-btn-nav {
        background: #2563EB;
        border-color: #2563EB;
        color: #fff;
    }
    .site-nav.scrolled .login-btn-nav:hover {
        background: #1D4ED8;
    }

    .login-btn-nav {
        transition: all 0.3s ease;
    }

    /* ── Mobile Menu ── */
    .mobile-menu {
        position: fixed; top: 0; right: -100%;
        width: 280px; height: 100vh;
        background: #fff;
        z-index: 60;
        transition: right 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: -8px 0 30px rgba(0,0,0,0.08);
        display: flex; flex-direction: column;
    }
    .dark .mobile-menu {
        background: #0F172A;
    }
    .mobile-menu.open { right: 0; }

    .mobile-overlay {
        position: fixed; inset: 0;
        background: rgba(0,0,0,0.3);
        z-index: 55;
        opacity: 0; pointer-events: none;
        transition: opacity 0.35s ease;
        backdrop-filter: blur(4px);
    }
    .mobile-overlay.open { opacity: 1; pointer-events: auto; }

    .scroll-section { scroll-margin-top: 80px; }
</style>

<div class="min-h-screen bg-white dark:bg-gray-900 landing-scroll">
    <?php if (isLoggedIn()): $user = getCurrentUser(); ?>
    <!-- LOGGED IN STATE -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="anim-fade-up show">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 mb-8">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center space-x-4">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['name']); ?>&background=2563EB&color=fff&size=48" alt="" class="w-12 h-12 rounded-full">
                    <div>
                        <h1 class="text-xl font-bold text-gray-900 dark:text-gray-100">Welcome back, <?php echo sanitizeOutput($user['name']); ?></h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400"><?php echo sanitizeOutput($user['email']); ?> &middot; <?php echo ucfirst(sanitizeOutput($user['role'])); ?> Account</p>
                    </div>
                </div>
                <a href="/pages/logout.php" class="inline-flex items-center px-4 py-2 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-lg text-sm font-medium hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors">
                    <i class="fa-solid fa-right-from-bracket mr-2"></i> Logout
                </a>
            </div>
        </div>
        </div>

        <?php if (isAdminLoggedIn()): $admin = getCurrentAdmin(); ?>
        <div class="anim-fade-up show delay-1">
        <div class="admin-section rounded-2xl p-6 sm:p-8 mb-8">
            <div class="flex items-center space-x-3 mb-6">
                <div class="w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-shield-halved text-xl text-white"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-white">Admin Panel</h2>
                    <p class="text-sm text-white/70">Manage your application</p>
                </div>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <?php
                $adminLinks = [
                    ['dashboard', 'fa-chart-pie', 'Dashboard'],
                    ['students', 'fa-users', 'Students'],
                    ['exams', 'fa-file-alt', 'Exams'],
                    ['questions', 'fa-database', 'Questions'],
                    ['categories', 'fa-layer-group', 'Categories'],
                    ['app_control', 'fa-mobile-screen', 'App Control'],
                ];
                foreach ($adminLinks as $i => [$linkPage, $linkIcon, $linkLabel]):
                ?>
                <a href="/index.php?page=<?php echo $linkPage; ?>" class="admin-link-card bg-white/10 hover:bg-white/20 rounded-xl p-4 text-center transition-all group" style="animation: fadeInUp 0.5s ease <?php echo 0.1 * $i; ?>s both;">
                    <div class="w-10 h-10 mx-auto bg-white/10 group-hover:bg-white/20 rounded-lg flex items-center justify-center mb-2 transition-colors">
                        <i class="fa-solid <?php echo $linkIcon; ?> text-white text-lg"></i>
                    </div>
                    <p class="text-sm font-medium text-white"><?php echo $linkLabel; ?></p>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        </div>
        <p class="text-gray-500 dark:text-gray-400 anim-fade-up show delay-3">Use the links above to manage students, exams, questions, and application settings.</p>
        <?php endif; ?>

        <?php if (!isAdminLoggedIn()): ?>
        <div class="anim-fade-up show delay-1">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 sm:p-8">
            <div class="flex items-center space-x-3 mb-4">
                <img src="/image/app_logo.png" alt="Shikhbo" class="logo-img-sm">
                <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">Shikhbo Learning App</h2>
            </div>
            <p class="text-gray-500 dark:text-gray-400 mb-6">Access your exams, track your progress, and continue learning on the go. Download the Shikhbo Android app from the section below.</p>
            <div class="flex flex-col sm:flex-row gap-4">
                <button onclick="openDownloadModal('apk')" class="download-btn inline-flex items-center justify-center px-6 py-3 bg-gray-900 dark:bg-gray-700 text-white rounded-xl font-medium hover:bg-gray-800 dark:hover:bg-gray-600 transition-colors cursor-pointer">
                    <i class="fa-brands fa-android text-xl mr-3"></i> Download APK
                </button>
                <button onclick="openDownloadModal('play')" class="download-btn inline-flex items-center justify-center px-6 py-3 bg-black text-white rounded-xl font-medium hover:bg-gray-900 transition-colors cursor-pointer">
                    <i class="fa-brands fa-google-play text-xl mr-3"></i> Google Play
                </button>
            </div>
        </div>
        </div>
        <?php endif; ?>
    </div>

    <?php else: ?>
    <!-- PUBLIC / NOT LOGGED IN -->

    <!-- Mobile Overlay -->
    <div class="mobile-overlay" id="mobileOverlay"></div>

    <!-- Mobile Menu -->
    <div class="mobile-menu" id="mobileMenu">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-800">
            <div class="flex items-center space-x-2">
                <img src="/image/app_logo.png" alt="Shikhbo" class="w-7 h-7 object-contain">
                <span class="font-bold text-gray-900 dark:text-gray-100">Shikhbo</span>
            </div>
            <button id="mobileClose" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1">
                <i class="fa-solid fa-xmark text-2xl"></i>
            </button>
        </div>
        <nav class="flex-1 px-3 py-4 space-y-0.5">
            <a href="#home" class="mobile-nav-link flex items-center px-3 py-2.5 text-gray-600 dark:text-gray-400 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 font-medium transition-colors text-sm">
                <i class="fa-solid fa-house w-6 text-blue-500 text-xs"></i> Home
            </a>
            <a href="#features" class="mobile-nav-link flex items-center px-3 py-2.5 text-gray-600 dark:text-gray-400 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 font-medium transition-colors text-sm">
                <i class="fa-solid fa-star w-6 text-blue-500 text-xs"></i> Features
            </a>
            <a href="#download" class="mobile-nav-link flex items-center px-3 py-2.5 text-gray-600 dark:text-gray-400 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 font-medium transition-colors text-sm">
                <i class="fa-solid fa-download w-6 text-blue-500 text-xs"></i> Download
            </a>
            <a href="#privacy" class="mobile-nav-link flex items-center px-3 py-2.5 text-gray-600 dark:text-gray-400 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 font-medium transition-colors text-sm">
                <i class="fa-solid fa-shield-halved w-6 text-blue-500 text-xs"></i> Privacy
            </a>
            <a href="#terms" class="mobile-nav-link flex items-center px-3 py-2.5 text-gray-600 dark:text-gray-400 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 font-medium transition-colors text-sm">
                <i class="fa-solid fa-file-lines w-6 text-blue-500 text-xs"></i> Terms
            </a>
            <div class="pt-3 mt-3 border-t border-gray-100 dark:border-gray-800">
                <a href="/pages/admin_login.php" class="flex items-center justify-center px-4 py-2.5 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors text-sm">
                    <i class="fa-solid fa-lock mr-2"></i> Login
                </a>
            </div>
        </nav>
    </div>

    <!-- Sticky Navbar -->
    <nav class="site-nav" id="siteNav">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between">
                <a href="#home" class="flex items-center space-x-2.5 hero-fade-in">
                    <img src="/image/app_logo.png" alt="Shikhbo" class="logo-img logo-glow">
                    <span class="text-lg font-bold text-white nav-brand-text">Shikhbo</span>
                </a>
                <div class="hidden md:flex items-center hero-fade-in delay-1">
                    <a href="#home" class="nav-link">Home</a>
                    <a href="#features" class="nav-link">Features</a>
                    <a href="#download" class="nav-link">Download</a>
                    <a href="#privacy" class="nav-link">Privacy</a>
                    <a href="#terms" class="nav-link">Terms</a>
                </div>
                <div class="flex items-center hero-fade-in delay-1">
                    <a href="/pages/admin_login.php" class="login-btn-nav hidden sm:inline-flex items-center px-4 py-2 bg-white/10 hover:bg-white/20 text-white text-sm font-medium rounded-lg transition-all border border-white/20">
                        <i class="fa-solid fa-lock mr-2"></i> Login
                    </a>
                    <button id="menuToggle" class="md:hidden flex items-center justify-center w-10 h-10 text-white hover:bg-white/10 rounded-lg transition-colors ml-2">
                        <i class="fa-solid fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <section id="home" class="landing-gradient relative overflow-hidden min-h-screen flex items-center" style="padding-top: 4rem;">
        <div class="particles" id="particles"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16 relative z-10 w-full">
            <div class="grid lg:grid-cols-2 gap-10 lg:gap-16 items-center">
                <div class="text-center lg:text-left">
                    <div class="inline-flex items-center px-3 py-1 bg-white/10 backdrop-blur-sm rounded-full text-white/70 text-xs sm:text-sm mb-5 border border-white/10 hero-fade-in delay-1">
                        <i class="fa-solid fa-graduation-cap mr-2"></i> Your Learning Companion
                    </div>
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white leading-tight mb-5 hero-fade-in delay-2">
                        Master Every<br>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-300 to-cyan-300">Exam with Confidence</span>
                    </h1>
                    <p class="text-base sm:text-lg text-white/70 mb-7 max-w-xl mx-auto lg:mx-0 hero-fade-in delay-3 leading-relaxed">
                        Practice with thousands of questions, track your progress, and ace your academic, job, and general knowledge exams.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-3 justify-center lg:justify-start hero-fade-in delay-4">
                        <button onclick="openDownloadModal('apk')" class="download-btn inline-flex items-center justify-center px-7 py-3.5 bg-white text-blue-600 rounded-xl font-bold hover:bg-gray-50 transition-all shadow-xl text-sm cursor-pointer">
                            <i class="fa-brands fa-android text-xl mr-2.5"></i> Download APK
                        </button>
                        <button onclick="openDownloadModal('play')" class="download-btn inline-flex items-center justify-center px-7 py-3.5 bg-white/10 backdrop-blur-sm text-white rounded-xl font-bold hover:bg-white/20 transition-all border border-white/20 text-sm cursor-pointer">
                            <i class="fa-brands fa-google-play text-xl mr-2.5"></i> Google Play
                        </button>
                    </div>
                </div>
                <div class="hidden lg:flex justify-center hero-fade-in delay-3">
                    <div class="grid grid-cols-2 gap-3 max-w-sm">
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 text-center border border-white/10">
                            <div class="text-3xl font-bold text-white">10K+</div>
                            <div class="text-white/60 text-xs mt-1">Questions</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 text-center border border-white/10">
                            <div class="text-3xl font-bold text-white">50+</div>
                            <div class="text-white/60 text-xs mt-1">Categories</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 text-center border border-white/10">
                            <div class="text-3xl font-bold text-white">95%</div>
                            <div class="text-white/60 text-xs mt-1">Success Rate</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 text-center border border-white/10">
                            <div class="text-3xl font-bold text-white">Free</div>
                            <div class="text-white/60 text-xs mt-1">To Download</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section id="features" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24 scroll-section">
        <div class="text-center mb-12 anim-fade-up">
            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-gray-100 mb-3">Why Choose Shikhbo?</h2>
            <p class="text-base text-gray-500 dark:text-gray-400 max-w-2xl mx-auto">Everything you need to prepare for your exams in one place.</p>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5 sm:gap-6 anim-stagger" id="featuresGrid">
            <?php
            $features = [
                ['fa-layer-group', 'Multiple Categories', 'Academic, job, and general knowledge exams organized by subject and difficulty level.'],
                ['fa-chart-line', 'Track Progress', 'Detailed analytics on your performance, strengths, and areas that need improvement.'],
                ['fa-bolt', 'Timed Practice', 'Simulate real exam conditions with timed tests and instant result feedback.'],
                ['fa-mobile-screen', 'Mobile First', 'Native Android app with offline support. Study anytime, anywhere.'],
                ['fa-users', 'Referral Program', 'Invite friends and earn rewards. Learning together is better.'],
                ['fa-shield-halved', 'Secure & Private', 'Your data is protected with enterprise-grade security and encryption.'],
            ];
            foreach ($features as $i => [$icon, $title, $desc]):
            ?>
            <div class="feature-card bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                <div class="w-11 h-11 bg-blue-50 dark:bg-blue-900/20 rounded-xl flex items-center justify-center mb-3">
                    <i class="fa-solid <?php echo $icon; ?> text-blue-600 dark:text-blue-400"></i>
                </div>
                <h3 class="font-bold text-gray-900 dark:text-gray-100 mb-1.5"><?php echo $title; ?></h3>
                <p class="text-gray-500 dark:text-gray-400 text-sm leading-relaxed"><?php echo $desc; ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Download CTA -->
    <section id="download" class="landing-gradient-solid relative overflow-hidden scroll-section">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20 text-center relative z-10">
            <div class="anim-fade-up">
            <img src="/image/app_logo.png" alt="Shikhbo" class="logo-img-lg mx-auto mb-5 logo-glow">
            <h2 class="text-3xl sm:text-4xl font-bold text-white mb-3">Ready to Get Started?</h2>
            <p class="text-base text-white/70 mb-8 max-w-2xl mx-auto">Download the Shikhbo Android app and start mastering your exams today.</p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <button onclick="openDownloadModal('apk')" class="download-btn inline-flex items-center justify-center px-7 py-3.5 bg-white text-blue-600 rounded-xl font-bold hover:bg-gray-50 transition-all shadow-xl text-sm cursor-pointer">
                    <i class="fa-brands fa-android text-xl mr-2.5"></i> Download APK
                </button>
                <button onclick="openDownloadModal('play')" class="download-btn inline-flex items-center justify-center px-7 py-3.5 bg-white/10 backdrop-blur-sm text-white rounded-xl font-bold hover:bg-white/20 transition-all border border-white/20 text-sm cursor-pointer">
                    <i class="fa-brands fa-google-play text-xl mr-2.5"></i> Get on Google Play
                </button>
            </div>
            </div>
        </div>
    </section>

    <!-- Privacy Policy -->
    <section id="privacy" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20 scroll-section">
        <div class="anim-fade-up max-w-4xl mx-auto">
            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-gray-100 mb-3">Privacy Policy</h2>
            <p class="text-gray-500 dark:text-gray-400 text-sm mb-8">Last updated: January 2026</p>
            <div class="prose prose-gray dark:prose-invert max-w-none space-y-4 text-gray-600 dark:text-gray-400 text-sm leading-relaxed">
                <p>Shikhbo respects your privacy. This Privacy Policy explains how we collect, use, and safeguard your information when you use our mobile application and website.</p>
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mt-6">Information We Collect</h3>
                <p>We collect information you provide when creating an account, including your name, email address, and academic interests. We also collect anonymized usage data to improve the learning experience.</p>
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mt-6">How We Use Your Data</h3>
                <p>Your data is used solely to personalize your learning journey, track progress, and improve our services. We never sell your personal information to third parties.</p>
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mt-6">Data Security</h3>
                <p>We implement industry-standard encryption and security measures to protect your data. All communications between the app and our servers are encrypted via HTTPS.</p>
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mt-6">Contact</h3>
                <p>For privacy-related inquiries, reach out to us at <a href="mailto:privacy@shikhbo.com" class="text-blue-600 dark:text-blue-400 hover:underline">privacy@shikhbo.com</a>.</p>
            </div>
        </div>
    </section>

    <!-- Terms of Service -->
    <section id="terms" class="bg-gray-50 dark:bg-gray-800/50 scroll-section">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20">
            <div class="anim-fade-up max-w-4xl mx-auto">
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-gray-100 mb-3">Terms of Service</h2>
                <p class="text-gray-500 dark:text-gray-400 text-sm mb-8">Last updated: January 2026</p>
                <div class="prose prose-gray dark:prose-invert max-w-none space-y-4 text-gray-600 dark:text-gray-400 text-sm leading-relaxed">
                    <p>By using Shikhbo, you agree to these terms. Please read them carefully before using our platform.</p>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mt-6">Account Responsibilities</h3>
                    <p>You are responsible for maintaining the confidentiality of your account credentials. Any activity under your account is your responsibility.</p>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mt-6">Acceptable Use</h3>
                    <p>You agree not to misuse the platform for unauthorized commercial purposes, distribute harmful content, or attempt to breach our security systems.</p>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mt-6">Intellectual Property</h3>
                    <p>All content, questions, and materials within Shikhbo are owned by Shikhbo and protected by copyright laws. Redistribution without permission is prohibited.</p>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mt-6">Limitation of Liability</h3>
                    <p>Shikhbo is provided "as is" without warranties. We are not liable for any damages arising from your use of the platform.</p>
                </div>
            </div>
        </div>
    </section>

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
                <button onclick="closeDownloadModal()" id="dlCancelBtn" class="flex-1 px-4 py-2.5 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-900 dark:bg-gray-950">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 text-center">
            <p class="text-gray-500 text-sm">&copy; <?php echo date('Y'); ?> Shikhbo. All rights reserved.</p>
        </div>
    </footer>
    <?php endif; ?>
</div>

<script>
(function() {
    // Navbar scroll
    const nav = document.getElementById('siteNav');
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

    // Mobile menu
    const menuToggle = document.getElementById('menuToggle');
    const mobileMenu = document.getElementById('mobileMenu');
    const mobileOverlay = document.getElementById('mobileOverlay');
    const mobileClose = document.getElementById('mobileClose');

    function openMenu() { mobileMenu.classList.add('open'); mobileOverlay.classList.add('open'); document.body.style.overflow = 'hidden'; }
    function closeMenu() { mobileMenu.classList.remove('open'); mobileOverlay.classList.remove('open'); document.body.style.overflow = ''; }

    if (menuToggle) menuToggle.addEventListener('click', openMenu);
    if (mobileClose) mobileClose.addEventListener('click', closeMenu);
    if (mobileOverlay) mobileOverlay.addEventListener('click', closeMenu);
    document.querySelectorAll('.mobile-nav-link').forEach(l => l.addEventListener('click', closeMenu));

    // Active nav link
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

    // Smooth scroll
    document.querySelectorAll('a[href^="#"]').forEach(a => {
        a.addEventListener('click', function(e) {
            const t = document.querySelector(this.getAttribute('href'));
            if (t) { e.preventDefault(); t.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
        });
    });

    // Intersection Observer
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('show');
                entry.target.querySelectorAll(':scope > *').forEach((c, i) => c.style.transitionDelay = (i * 0.08) + 's');
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
    document.querySelectorAll('.anim-fade-up, .anim-stagger').forEach(el => observer.observe(el));

    // Particles
    const container = document.getElementById('particles');
    if (container) {
        for (let i = 0; i < 25; i++) {
            const p = document.createElement('div');
            p.className = 'particle';
            const s = 2 + Math.random() * 4;
            p.style.cssText = `width:${s}px;height:${s}px;left:${Math.random()*100}%;top:${Math.random()*100}%;animation-duration:${8+Math.random()*12}s;animation-delay:${Math.random()*5}s`;
            container.appendChild(p);
        }
    }
})();

// ── Download Modal (global) ──
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
    let lastLoaded = 0;
    const startTime = Date.now();
    const speeds = [];

    // Phase 1: Preparing
    let phase = 0;
    const phaseInterval = setInterval(() => {
        if (dlCancel) { clearInterval(phaseInterval); return; }
        const dots = '.'.repeat((phase % 3) + 1);
        status.textContent = 'Preparing' + dots;
        phase++;
    }, 400);

    setTimeout(() => {
        clearInterval(phaseInterval);
        if (dlCancel) return;

        status.textContent = 'Downloading...';
        status.className = 'text-sm text-blue-600 dark:text-blue-400 font-medium dl-status-pulse';

        // Phase 2: Download simulation
        const dlInterval = setInterval(() => {
            if (dlCancel) {
                clearInterval(dlInterval);
                status.textContent = 'Cancelled';
                status.className = 'text-sm text-gray-500 dark:text-gray-400';
                speed.textContent = '0 MB/s';
                return;
            }

            const increment = 0.3 + Math.random() * 1.2;
            downloaded = Math.min(downloaded + increment, totalSize);
            const progress = (downloaded / totalSize) * 100;

            bar.style.width = progress + '%';
            pct.textContent = Math.round(progress) + '%';

            // Speed (moving average)
            const elapsed = (Date.now() - startTime) / 1000;
            const currentSpeed = downloaded / elapsed;
            speeds.push(currentSpeed);
            if (speeds.length > 10) speeds.shift();
            const avgSpeed = speeds.reduce((a, b) => a + b, 0) / speeds.length;
            speed.textContent = avgSpeed.toFixed(1) + ' MB/s';

            // Remaining
            const remaining = (totalSize - downloaded) / (avgSpeed || 1);
            if (remaining > 60) {
                const min = Math.floor(remaining / 60);
                const sec = Math.floor(remaining % 60);
                remain.textContent = min + 'm ' + sec + 's remaining';
            } else {
                remain.textContent = Math.max(1, Math.ceil(remaining)) + 's remaining';
            }

            if (progress >= 100) {
                clearInterval(dlInterval);
                finishDownload(bar, pct, status, speed, remain, actions);
            }
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

        actions.innerHTML = `
            <button onclick="closeDownloadModal()" class="flex-1 px-4 py-2.5 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Close</button>
            <a href="/api/download.php" class="flex-1 px-4 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700 transition-colors text-center">
                <i class="fa-solid fa-download mr-1.5"></i> Save File
            </a>
        `;
    }, 800);
}
</script>