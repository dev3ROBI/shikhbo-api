<?php
$loggedInUser = isLoggedIn() ? getCurrentUser() : null;
$isAdminUser = $loggedInUser && isAdminRole($loggedInUser['role']);
$pageTitle = $pageTitle ?? 'Shikhbo - Master Every Exam with Confidence';
$currentPage = $page ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="Practice with thousands of questions, track your progress, and ace your exams with Shikhbo.">
    <link rel="icon" type="image/png" href="/image/app_logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                colors: {
                    'shikhbo-primary': '#4F46E5',
                    'shikhbo-dark': '#1E293B',
                }
            }
        }
    }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
@keyframes fadeInUp{from{opacity:0;transform:translateY(40px)}to{opacity:1;transform:translateY(0)}}
@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-14px)}}
@keyframes gradientShift{0%{background-position:0% 50%}50%{background-position:100% 50%}100%{background-position:0% 50%}}
.anim-fade-up{opacity:0;transform:translateY(40px);transition:opacity .7s ease,transform .7s ease}
.anim-fade-up.show{opacity:1;transform:translateY(0)}
.anim-stagger>*{opacity:0;transform:translateY(30px);transition:opacity .5s ease,transform .5s ease}
.anim-stagger.show>*{opacity:1;transform:translateY(0)}
.delay-1{transition-delay:.1s}.delay-2{transition-delay:.2s}.delay-3{transition-delay:.3s}.delay-4{transition-delay:.4s}
.hero-fade-in{opacity:0;animation:fadeInUp .8s ease forwards}
.hero-fade-in.delay-1{animation-delay:.1s}.hero-fade-in.delay-2{animation-delay:.3s}.hero-fade-in.delay-3{animation-delay:.5s}.hero-fade-in.delay-4{animation-delay:.7s}
.feature-card{transition:all .4s cubic-bezier(.34,1.56,.64,1);border:1px solid rgba(0,0,0,.04)}
.feature-card:hover{transform:translateY(-8px) scale(1.02);box-shadow:0 24px 48px -12px rgba(37,99,235,.15)}
.dl-modal{position:fixed;inset:0;z-index:100;display:flex;align-items:center;justify-content:center;padding:1rem;opacity:0;pointer-events:none;transition:opacity .4s ease}
.dl-modal.open{opacity:1;pointer-events:auto}
.dl-modal .dl-backdrop{position:absolute;inset:0;background:rgba(0,0,0,.5);backdrop-filter:blur(6px)}
.dl-modal .dl-panel{position:relative;background:#fff;border-radius:1.25rem;width:100%;max-width:400px;padding:2rem;box-shadow:0 25px 60px rgba(0,0,0,.2);transform:translateY(30px) scale(.95);transition:transform .4s cubic-bezier(.34,1.56,.64,1)}
.dark .dl-modal .dl-panel{background:#1E293B}
.dl-modal.open .dl-panel{transform:translateY(0) scale(1)}
.dl-progress-track{width:100%;height:6px;background:#E5E7EB;border-radius:3px;overflow:hidden}
.dark .dl-progress-track{background:#374151}
.dl-progress-bar{height:100%;width:0%;background:linear-gradient(90deg,#2563EB,#3B82F6);border-radius:3px;transition:width .3s ease}
.dl-speed{font-variant-numeric:tabular-nums}
@keyframes dlPulse{0%,100%{opacity:1}50%{opacity:.5}}
.dl-status-pulse{animation:dlPulse 1s ease-in-out infinite}
.modal-overlay{position:fixed;inset:0;z-index:100;display:flex;align-items:center;justify-content:center;padding:1rem;pointer-events:none}
.modal-overlay .modal-backdrop{position:absolute;inset:0;background:rgba(0,0,0,0);backdrop-filter:blur(0px);-webkit-backdrop-filter:blur(0px);transition:background .3s ease,backdrop-filter .3s ease}
.modal-overlay.open{pointer-events:auto}
.modal-overlay.open .modal-backdrop{background:rgba(0,0,0,.5);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px)}
.modal-overlay .modal-panel{position:relative;background:#fff;border-radius:1.25rem;width:100%;max-width:380px;padding:2rem;box-shadow:0 32px 64px rgba(0,0,0,.2);opacity:0;transform:translateY(30px) scale(.92);transition:opacity .4s ease,transform .4s cubic-bezier(.34,1.56,.64,1);transition-delay:.15s}
.dark .modal-overlay .modal-panel{background:#1E293B}
.modal-overlay.open .modal-panel{opacity:1;transform:translateY(0) scale(1)}
.download-btn{transition:all .3s cubic-bezier(.34,1.56,.64,1)}
.download-btn:hover{transform:translateY(-3px) scale(1.03);box-shadow:0 12px 30px -8px rgba(37,99,235,.4)}
.download-btn:active{transform:scale(.97)}
.admin-section{background:linear-gradient(135deg,#1E293B 0%,#334155 100%)}
.admin-link-card{transition:all .25s cubic-bezier(.34,1.56,.64,1)}
.admin-link-card:hover{transform:translateY(-4px) scale(1.04);box-shadow:0 12px 28px -8px rgba(79,70,229,.35)}
.particles{position:absolute;inset:0;overflow:hidden;pointer-events:none}
.particle{position:absolute;width:4px;height:4px;background:rgba(255,255,255,.12);border-radius:50%;animation:float linear infinite}
.landing-scroll::-webkit-scrollbar{width:6px}
.landing-scroll::-webkit-scrollbar-track{background:transparent}
.landing-scroll::-webkit-scrollbar-thumb{background:rgba(37,99,235,.3);border-radius:3px}
.logo-glow{filter:drop-shadow(0 0 10px rgba(37,99,235,.4))}
.logo-img{width:36px;height:36px;object-fit:contain}
.logo-img-sm{width:28px;height:28px;object-fit:contain}
.logo-img-lg{width:72px;height:72px;object-fit:contain}
.logo-img-xl{width:56px;height:56px;object-fit:contain}
.site-nav{position:fixed;top:0;left:0;right:0;z-index:50;transition:background .4s ease,box-shadow .4s ease,padding .3s ease,backdrop-filter .4s ease;padding-top:1rem;padding-bottom:1rem}
.site-nav.scrolled{background:rgba(255,255,255,.9);backdrop-filter:blur(14px);box-shadow:0 1px 3px rgba(0,0,0,.05);padding-top:.5rem;padding-bottom:.5rem}
.dark .site-nav.scrolled{background:rgba(15,23,42,.9)}
.site-nav .nav-link{position:relative;color:rgba(255,255,255,.8);font-size:.875rem;font-weight:500;padding:.5rem 1rem;border-radius:.5rem;transition:color .3s ease,background .3s ease}
.site-nav .nav-link:hover{color:#fff;background:rgba(255,255,255,.08)}
.site-nav .nav-link.active,.site-nav .nav-link.active:hover{background:transparent;color:rgba(255,255,255,.8)}
.site-nav .nav-link::after{content:'';position:absolute;bottom:2px;left:50%;width:0;height:2px;background:#60A5FA;border-radius:2px;transform:translateX(-50%);transition:width .3s ease}
.site-nav .nav-link:hover::after,.site-nav .nav-link.active::after{width:60%}
.site-nav.scrolled .nav-link{color:#4B5563}
.dark .site-nav.scrolled .nav-link{color:#D1D5DB}
.site-nav.scrolled .nav-link:hover{color:#2563EB}
.site-nav.scrolled .nav-link.active,.site-nav.scrolled .nav-link.active:hover{color:#4B5563}
.dark .site-nav.scrolled .nav-link.active,.dark .site-nav.scrolled .nav-link.active:hover{color:#D1D5DB}
.site-nav.scrolled .nav-link::after{background:#2563EB}
.site-nav.scrolled .nav-brand-text{color:#1F2937}
.dark .site-nav.scrolled .nav-brand-text{color:#F3F4F6}
.site-nav.scrolled .nav-user-text{color:#1F2937}
.dark .site-nav.scrolled .nav-user-text{color:#F3F4F6}
.site-nav.scrolled .login-btn-nav{background:#2563EB;border-color:#2563EB;color:#fff}
.site-nav.scrolled .login-btn-nav:hover{background:#1D4ED8}
.site-nav.scrolled #menuToggle{color:#1F2937}
.dark .site-nav.scrolled #menuToggle{color:#F3F4F6}
.login-btn-nav{transition:all .3s ease}
.mobile-menu{position:fixed;top:0;right:-100%;width:280px;height:100vh;background:#fff;z-index:60;transition:right .35s cubic-bezier(.4,0,.2,1);box-shadow:-8px 0 30px rgba(0,0,0,.08);display:flex;flex-direction:column}
.dark .mobile-menu{background:#0F172A}
.mobile-menu.open{right:0}
.mobile-overlay{position:fixed;inset:0;background:rgba(0,0,0,.3);z-index:55;opacity:0;pointer-events:none;transition:opacity .35s ease;backdrop-filter:blur(4px)}
.mobile-overlay.open{opacity:1;pointer-events:auto}
.scroll-section{scroll-margin-top:80px}
</style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 font-sans antialiased">
<div class="min-h-screen bg-white dark:bg-gray-900 landing-scroll">

<div class="mobile-overlay" id="mobileOverlay"></div>

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
        <a href="/" class="mobile-nav-link flex items-center px-3 py-2.5 text-gray-600 dark:text-gray-400 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 font-medium transition-colors text-sm">
            <i class="fa-solid fa-house w-6 text-blue-500 text-xs"></i> Home
        </a>
        <?php if ($loggedInUser): ?>
        <a href="/index.php?page=browse" class="mobile-nav-link flex items-center px-3 py-2.5 text-gray-600 dark:text-gray-400 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 font-medium transition-colors text-sm">
            <i class="fa-solid fa-graduation-cap w-6 text-blue-500 text-xs"></i> Courses
        </a>
        <a href="/index.php?page=orders" class="mobile-nav-link flex items-center px-3 py-2.5 text-gray-600 dark:text-gray-400 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 font-medium transition-colors text-sm">
            <i class="fa-solid fa-receipt w-6 text-purple-500 text-xs"></i> Orders
        </a>
        <?php endif; ?>
        <?php if ($isAdminUser): ?>
        <a href="/index.php?page=dashboard" class="mobile-nav-link flex items-center px-3 py-2.5 text-gray-600 dark:text-gray-400 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 font-medium transition-colors text-sm">
            <i class="fa-solid fa-gauge-high w-6 text-indigo-500 text-xs"></i> Dashboard
        </a>
        <?php endif; ?>
        <a href="/#download" class="mobile-nav-link flex items-center px-3 py-2.5 text-gray-600 dark:text-gray-400 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 font-medium transition-colors text-sm">
            <i class="fa-solid fa-download w-6 text-blue-500 text-xs"></i> Download
        </a>
        <a href="/#privacy" class="mobile-nav-link flex items-center px-3 py-2.5 text-gray-600 dark:text-gray-400 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 font-medium transition-colors text-sm">
            <i class="fa-solid fa-shield-halved w-6 text-blue-500 text-xs"></i> Privacy
        </a>
        <a href="/#terms" class="mobile-nav-link flex items-center px-3 py-2.5 text-gray-600 dark:text-gray-400 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 font-medium transition-colors text-sm">
            <i class="fa-solid fa-file-lines w-6 text-blue-500 text-xs"></i> Terms
        </a>
        <div class="pt-3 mt-3 border-t border-gray-100 dark:border-gray-800">
            <?php if ($loggedInUser): ?>
            <div class="flex items-center justify-between px-3 py-2">
                <div class="flex items-center space-x-2.5">
                    <img src="<?php echo $loggedInUser['profile_image'] ?? '/api/uploads/profiles/profile.png'; ?>" alt="" class="w-7 h-7 rounded-full object-cover" onerror="this.src='/api/uploads/profiles/profile.png'">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200"><?php echo htmlspecialchars($loggedInUser['name']); ?></span>
                </div>
                <a href="/pages/logout.php" onclick="openLogoutModal();return false;" class="text-red-400 hover:text-red-500 transition-colors text-sm" title="Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
            </div>
            <?php else: ?>
            <a href="/pages/admin_login.php" class="flex items-center justify-center px-4 py-2.5 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors text-sm">
                <i class="fa-solid fa-lock mr-2"></i> Login
            </a>
            <?php endif; ?>
        </div>
    </nav>
</div>

<nav class="site-nav" id="siteNav">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between">
            <a href="/" class="flex items-center space-x-2.5 hero-fade-in">
                <img src="/image/app_logo.png" alt="Shikhbo" class="logo-img logo-glow">
                <span class="text-lg font-bold text-white nav-brand-text">Shikhbo</span>
            </a>
            <div class="hidden md:flex items-center hero-fade-in delay-1">
                <a href="/" class="nav-link <?php echo $currentPage === '' ? 'active' : ''; ?>">Home</a>
                <?php if ($loggedInUser): ?>
                <a href="/index.php?page=browse" class="nav-link <?php echo $currentPage === 'browse' ? 'active' : ''; ?>">Courses</a>
                <a href="/index.php?page=orders" class="nav-link <?php echo $currentPage === 'orders' ? 'active' : ''; ?>">Orders</a>
                <?php endif; ?>
                <?php if ($isAdminUser): ?>
                <a href="/index.php?page=dashboard" class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
                <?php endif; ?>
                <a href="/#download" class="nav-link">Download</a>
                <a href="/#privacy" class="nav-link">Privacy</a>
                <a href="/#terms" class="nav-link">Terms</a>
            </div>
            <div class="flex items-center hero-fade-in delay-1">
                <?php if ($loggedInUser): ?>
                <div class="hidden sm:flex items-center space-x-2.5 px-3 py-1.5">
                    <img src="<?php echo $loggedInUser['profile_image'] ?? '/api/uploads/profiles/profile.png'; ?>" alt="" class="w-7 h-7 rounded-full border-2 border-white/30 object-cover" onerror="this.src='/api/uploads/profiles/profile.png'">
                    <span class="text-sm font-medium text-white nav-user-text"><?php echo htmlspecialchars($loggedInUser['name']); ?></span>
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
