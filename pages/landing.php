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

    /* ── Access Denied Modal ── */
    .ad-modal {
        position: fixed; inset: 0; z-index: 100;
        display: flex; align-items: center; justify-content: center;
        padding: 1rem;
        pointer-events: none;
    }
    .ad-modal .ad-backdrop {
        position: absolute; inset: 0;
        background: rgba(0,0,0,0);
        backdrop-filter: blur(0px);
        -webkit-backdrop-filter: blur(0px);
        transition: background 0.3s ease, backdrop-filter 0.3s ease;
    }
    .ad-modal.open { pointer-events: auto; }
    .ad-modal.open .ad-backdrop {
        background: rgba(0,0,0,0.5);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
    }
    .ad-modal .ad-panel {
        position: relative;
        background: #fff; border-radius: 1.25rem;
        width: 100%; max-width: 380px;
        padding: 2rem;
        box-shadow: 0 32px 64px rgba(0,0,0,0.2);
        opacity: 0;
        transform: translateY(30px) scale(0.92);
        transition: opacity 0.4s ease, transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        transition-delay: 0.15s;
    }
    .dark .ad-modal .ad-panel { background: #1E293B; }
    .ad-modal.open .ad-panel {
        opacity: 1;
        transform: translateY(0) scale(1);
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
    .site-nav.scrolled .nav-user-text {
        color: #1F2937;
    }
    .dark .site-nav.scrolled .nav-user-text {
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
    .site-nav.scrolled #menuToggle {
        color: #1F2937;
    }
    .dark .site-nav.scrolled #menuToggle {
        color: #F3F4F6;
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
<?php
$loggedInUser = isLoggedIn() ? getCurrentUser() : null;
$isAdminUser = $loggedInUser && isAdminRole($loggedInUser['role']);
$isMemberUser = $loggedInUser && !$isAdminUser;
?>



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
            <a href="#courses" class="mobile-nav-link flex items-center px-3 py-2.5 text-gray-600 dark:text-gray-400 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 font-medium transition-colors text-sm">
                <i class="fa-solid fa-graduation-cap w-6 text-blue-500 text-xs"></i> Courses
            </a>
            <a href="#features" class="mobile-nav-link flex items-center px-3 py-2.5 text-gray-600 dark:text-gray-400 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 font-medium transition-colors text-sm">
                <i class="fa-solid fa-star w-6 text-blue-500 text-xs"></i> Features
            </a>
            <?php if ($isAdminUser): ?>
            <a href="/index.php?page=dashboard" class="mobile-nav-link flex items-center px-3 py-2.5 text-gray-600 dark:text-gray-400 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 font-medium transition-colors text-sm">
                <i class="fa-solid fa-gauge-high w-6 text-indigo-500 text-xs"></i> Dashboard
            </a>
            <?php endif; ?>
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
                <?php if ($loggedInUser): ?>
                <div class="flex items-center justify-between px-3 py-2">
                    <div class="flex items-center space-x-2.5">
                        <img src="<?php echo $loggedInUser['profile_image'] ?? '/api/uploads/profiles/profile.png'; ?>" alt="" class="w-7 h-7 rounded-full object-cover" onerror="this.src='/api/uploads/profiles/profile.png'">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200"><?php echo sanitizeOutput($loggedInUser['name']); ?></span>
                    </div>
                    <a href="/pages/logout.php" onclick="openLogoutModal();return false;" class="text-gray-400 hover:text-red-500 transition-colors text-sm" title="Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
                </div>
                <?php else: ?>
                <a href="/pages/admin_login.php" class="flex items-center justify-center px-4 py-2.5 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors text-sm">
                    <i class="fa-solid fa-lock mr-2"></i> Login
                </a>
                <?php endif; ?>
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
                    <a href="#courses" class="nav-link">Courses</a>
                    <a href="#features" class="nav-link">Features</a>
                    <?php if ($isAdminUser): ?>
                    <a href="/index.php?page=dashboard" class="nav-link">Dashboard</a>
                    <?php endif; ?>
                    <a href="#download" class="nav-link">Download</a>
                    <a href="#privacy" class="nav-link">Privacy</a>
                    <a href="#terms" class="nav-link">Terms</a>
                </div>
                <div class="flex items-center hero-fade-in delay-1">
                    <?php if ($loggedInUser): ?>
                    <div class="hidden sm:flex items-center space-x-2.5 px-3 py-1.5">
                        <img src="<?php echo $loggedInUser['profile_image'] ?? '/api/uploads/profiles/profile.png'; ?>" alt="" class="w-7 h-7 rounded-full border-2 border-white/30 object-cover" onerror="this.src='/api/uploads/profiles/profile.png'">
                        <span class="text-sm font-medium text-white nav-user-text"><?php echo sanitizeOutput($loggedInUser['name']); ?></span>
                        <a href="/pages/logout.php" onclick="openLogoutModal();return false;" class="ml-1 text-white/60 hover:text-white transition-colors text-sm" title="Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
                    </div>
                    <?php else: ?>
                    <a href="/pages/admin_login.php" class="login-btn-nav hidden sm:inline-flex items-center px-4 py-2 bg-white/10 hover:bg-white/20 text-white text-sm font-medium rounded-lg transition-all border border-white/20">
                        <i class="fa-solid fa-lock mr-2"></i> Login
                    </a>
                    <?php endif; ?>
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

    <!-- Courses Section -->
    <section id="courses" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24 scroll-section">
        <div class="text-center mb-12 anim-fade-up">
            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-gray-100 mb-3">Explore Our Courses</h2>
            <p class="text-base text-gray-500 dark:text-gray-400 max-w-2xl mx-auto">Enhance your knowledge with our premium courses designed by expert instructors.</p>
        </div>

        <?php
        $conn = getDBConnection();
        $userId = $loggedInUser ? intval($loggedInUser['id']) : 0;
        $coursesQuery = "
            SELECT c.*, cat.name AS category_name,
                   e.status AS enrollment_status,
                   t.transaction_id AS pending_transaction_id
            FROM courses c
            LEFT JOIN exam_categories cat ON c.category_id = cat.id
            LEFT JOIN enrollments e ON e.course_id = c.id AND e.user_id = $userId
            LEFT JOIN transactions t ON t.id = (SELECT MAX(id) FROM transactions WHERE enrollment_id = e.id)
            WHERE c.is_active = 1 AND c.parent_course_id IS NULL
            ORDER BY c.is_featured DESC, c.total_enrolled DESC
        ";
        $coursesResult = $conn->query($coursesQuery);
        $landingCourses = [];
        if ($coursesResult) {
            while ($r = $coursesResult->fetch_assoc()) {
                $landingCourses[] = $r;
            }
        }
        ?>

        <?php if (empty($landingCourses)): ?>
            <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                <i class="fa-solid fa-graduation-cap text-4xl mb-3"></i>
                <p>No courses available at the moment. Check back soon!</p>
            </div>
        <?php else: ?>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
                <?php foreach ($landingCourses as $course): 
                    $isEnrolled = ($course['enrollment_status'] === 'active');
                    $isPending = ($course['enrollment_status'] === 'pending_payment');
                    $isFree = intval($course['is_free']);
                    $price = floatval($course['price']);
                    $coverImage = $course['cover_image'] ?: 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=600&auto=format&fit=crop&q=60';
                ?>
                <div class="bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-md border border-gray-100 dark:border-gray-700 hover:shadow-xl transition-all duration-300 flex flex-col h-full transform hover:-translate-y-1">
                    <div class="relative h-48 overflow-hidden bg-gray-100">
                        <img src="<?php echo htmlspecialchars($coverImage); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>" class="w-full h-full object-cover">
                        <div class="absolute top-4 right-4 bg-white/95 dark:bg-gray-900/95 backdrop-blur px-3 py-1.5 rounded-xl font-bold text-sm shadow-md <?php echo $isFree ? 'text-green-600' : 'text-indigo-600'; ?>">
                            <?php echo $isFree ? 'FREE' : '৳' . number_format($price, 0); ?>
                        </div>
                    </div>
                    <div class="p-6 flex-1 flex flex-col justify-between">
                        <div>
                            <span class="text-xs font-bold tracking-wider text-blue-600 uppercase mb-2 block"><?php echo htmlspecialchars($course['category_name'] ?: 'General'); ?></span>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2 line-clamp-1"><?php echo htmlspecialchars($course['title']); ?></h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4 line-clamp-2"><?php echo htmlspecialchars($course['short_description']); ?></p>
                        </div>
                        <div>
                            <div class="flex items-center justify-between text-xs text-gray-400 mb-4 border-t border-gray-100 dark:border-gray-700 pt-4">
                                <span><i class="fa-solid fa-user-group mr-1.5 text-blue-500"></i><?php echo number_format($course['total_enrolled']); ?> Enrolled</span>
                                <span><i class="fa-solid fa-clock mr-1.5 text-blue-500"></i><?php echo $course['duration_hours'] ?: 'Self-paced'; ?> hrs</span>
                            </div>
                            
                            <?php if (!$loggedInUser): ?>
                                <a href="/pages/admin_login.php?redirect=index.php" class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold transition flex items-center justify-center gap-2 text-sm shadow-lg shadow-indigo-100 dark:shadow-none">
                                    <i class="fa-solid fa-lock"></i> Login to Enroll
                                </a>
                            <?php elseif ($isEnrolled): ?>
                                <button class="w-full py-3 px-4 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-xl font-bold transition flex items-center justify-center gap-2 text-sm cursor-default" disabled>
                                    <i class="fa-solid fa-circle-check"></i> Already Enrolled
                                </button>
                            <?php elseif ($isPending): ?>
                                <button onclick="checkoutCourse(<?php echo $course['id']; ?>)" class="w-full py-3 px-4 bg-amber-500 hover:bg-amber-600 text-white rounded-xl font-bold transition flex items-center justify-center gap-2 text-sm shadow-lg shadow-amber-100 dark:shadow-none">
                                    <i class="fa-solid fa-wallet"></i> Complete Payment
                                </button>
                            <?php else: ?>
                                <button onclick="enrollCourse(<?php echo $course['id']; ?>, <?php echo $isFree; ?>)" class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold transition flex items-center justify-center gap-2 text-sm shadow-lg shadow-indigo-100 dark:shadow-none">
                                    <i class="fa-solid fa-graduation-cap"></i> <?php echo $isFree ? 'Enroll Now' : 'Buy Course'; ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
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

    <!-- Access Denied Modal -->
    <div class="ad-modal" id="accessDeniedModal">
        <div class="ad-backdrop" onclick="closeAccessDenied()"></div>
        <div class="ad-panel">
            <div class="text-center">
                <div style="width:64px;height:64px;margin:0 auto 1rem;background:#FEE2E2;border-radius:50%;display:flex;align-items:center;justify-content:center;">
                    <svg style="width:32px;height:32px;" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Access Restricted</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Your account has <strong>Member</strong> permissions.</p>
            </div>
            <div class="mt-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4 text-left text-xs text-gray-600 dark:text-gray-400 space-y-2 leading-relaxed">
                <p><span class="text-red-500">&cross;</span> Admin panel &amp; dashboard access</p>
                <p><span class="text-red-500">&cross;</span> Student &amp; exam management</p>
                <p><span class="text-red-500">&cross;</span> System &amp; app settings</p>
                <p class="pt-2 border-t border-gray-200 dark:border-gray-700"><span class="text-emerald-500">&check;</span> Practice exams &amp; learning content is available in the app</p>
            </div>
            <div class="mt-5 flex gap-3">
                <button onclick="closeAccessDenied()" class="flex-1 px-4 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700 transition-colors">Got it</button>
            </div>
        </div>
    </div>

    <!-- Logout Confirm Modal -->
    <div class="ad-modal" id="logoutModal">
        <div class="ad-backdrop" onclick="closeLogoutModal()"></div>
        <div class="ad-panel">
            <div class="text-center">
                <div style="width:64px;height:64px;margin:0 auto 1rem;background:#FEE2E2;border-radius:50%;display:flex;align-items:center;justify-content:center;">
                    <svg style="width:32px;height:32px;" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Logout</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Are you sure you want to logout?</p>
            </div>
            <div class="mt-5 flex gap-3">
                <button onclick="closeLogoutModal()" class="flex-1 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl text-sm font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">Cancel</button>
                <a href="/pages/logout.php" id="logoutConfirmBtn" class="flex-1 px-4 py-2.5 bg-red-600 text-white rounded-xl text-sm font-medium hover:bg-red-700 transition-colors text-center">Logout</a>
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
</div>

<script>
(function() {
    // Navbar scroll
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

    // Mobile menu
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

// ── Access Denied Modal (blur first, then slide) ──
function openAccessDenied() {
    const m = document.getElementById('accessDeniedModal');
    if (!m) return;
    document.body.style.overflow = 'hidden';
    // Phase 1: show backdrop with blur transition
    m.classList.add('open');
    // Phase 2: panel slides up (handled by CSS transition-delay)
}
function closeAccessDenied() {
    const m = document.getElementById('accessDeniedModal');
    if (!m) return;
    m.classList.remove('open');
    document.body.style.overflow = '';
}

// ── Logout Confirm Modal ──
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
// Toast Alert System for Landing Page
function showToast(title, message, type = 'success') {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'fixed bottom-4 right-4 z-[100] flex flex-col-reverse space-y-reverse space-y-2';
        document.body.appendChild(container);
    }
    
    const toast = document.createElement('div');
    const colorClass = type === 'success' ? 'bg-emerald-500 text-white' : 'bg-red-500 text-white';
    const iconClass = type === 'success' ? 'fa-circle-check' : 'fa-circle-xmark';
    
    toast.className = `${colorClass} px-5 py-3.5 rounded-xl shadow-lg flex items-center gap-3 text-sm font-semibold transition-all duration-300 transform translate-y-4 opacity-0`;
    toast.innerHTML = `<i class="fa-solid ${iconClass} text-lg"></i> <div><span class="block font-bold">${title}</span><span class="text-xs opacity-90">${message}</span></div>`;
    
    container.appendChild(toast);
    
    // Trigger animation
    setTimeout(() => {
        toast.classList.remove('translate-y-4', 'opacity-0');
    }, 10);
    
    // Auto dismiss
    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-y-2');
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

function enrollCourse(courseId, isFree) {
    if (isFree) {
        showToast('Enrolling', 'Please wait...', 'success');
        fetch('/api/enroll_course_app.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                course_id: courseId,
                action: 'enroll',
                uid: <?php echo $userId ? $userId : 0; ?>,
                season: 'web',
                u_state: '1'
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                showToast('Success', 'Enrolled successfully! Ready to start.', 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showToast('Error', data.message || 'Enrollment failed', 'error');
            }
        })
        .catch(err => {
            showToast('Error', 'An error occurred during enrollment', 'error');
        });
    } else {
        checkoutCourse(courseId);
    }
}

function checkoutCourse(courseId) {
    showToast('Redirecting', 'Preparing payment gateway...', 'success');
    fetch('/api/piprapay_initiate.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            course_id: courseId
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success' && data.checkout_url) {
            window.location.href = data.checkout_url;
        } else {
            showToast('Error', data.message || 'Payment initiation failed', 'error');
        }
    })
    .catch(err => {
        showToast('Error', 'An error occurred starting payment checkout', 'error');
    });
}

<?php if ($isMemberUser): ?>
document.addEventListener('DOMContentLoaded', function() { setTimeout(openAccessDenied, 500); });
<?php endif; ?>
</script>