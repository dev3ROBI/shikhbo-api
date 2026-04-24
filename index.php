<?php
/**
 * Shikhbo Admin Panel — Header + Sidebar (No horizontal nav)
 * Logo visibility: desktop = header logo, mobile = sidebar logo
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/security.php';
requireAdminAuth();

$admin = getCurrentAdmin();
$mysqli = getDBConnection();

// ── Fetch notifications & tickets ──
$notifCount = $mysqli->query("SELECT COUNT(*) AS c FROM login_attempts WHERE success=0 AND attempt_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch_assoc()['c'] ?? 0;
$recentFailed = $mysqli->query("SELECT email, ip_address, attempt_time FROM login_attempts WHERE success=0 ORDER BY attempt_time DESC LIMIT 8");

$ticketsTableExists = $mysqli->query("SHOW TABLES LIKE 'support_tickets'")->num_rows > 0;
$ticketCount = 0; $recentTickets = [];
if ($ticketsTableExists) {
    $ticketCount = $mysqli->query("SELECT COUNT(*) AS c FROM support_tickets WHERE status='open'")->fetch_assoc()['c'] ?? 0;
    $recentTickets = $mysqli->query("SELECT t.*, u.name AS user_name FROM support_tickets t LEFT JOIN users u ON t.user_id=u.id ORDER BY t.created_at DESC LIMIT 10");
} else {
    $mysqli->query("CREATE TABLE IF NOT EXISTS support_tickets ( id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED, subject VARCHAR(255), message TEXT, status ENUM('open','in_progress','closed') DEFAULT 'open', created_at DATETIME DEFAULT CURRENT_TIMESTAMP ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// Page routing
$page = $_GET['page'] ?? 'dashboard';
$allowedPages = ['dashboard','students','admins','settings','exams','questions','results','categories','database','exam_attempt'];
if (!in_array($page, $allowedPages)) $page = 'dashboard';
$pageFile = __DIR__ . '/pages/' . $page . '.php';
if (!file_exists($pageFile)) { $page = 'dashboard'; $pageFile = __DIR__ . '/pages/dashboard.php'; }
$pageTitles = ['dashboard'=>'Admin Dashboard','students'=>'Students Management','admins'=>'Admin Management','settings'=>'System Settings','exams'=>'Exams Management','questions'=>'Question Bank','results'=>'Exam Results','categories'=>'Exam Categories','database'=>'Database Console','exam_attempt'=>'Exam Attempt'];
$pageTitle = $pageTitles[$page] ?? 'Admin Panel';

$navItems = [
    ['dashboard',    'fa-chart-pie',     'Dashboard'],
    ['categories',   'fa-layer-group',   'Categories'],
    ['exams',        'fa-file-alt',      'Exams'],
    ['questions',    'fa-database',      'Question Bank'],
    ['exam_attempt', 'fa-play',          'Exam Attempt'],
    ['students',     'fa-users',         'Students'],
    ['results',      'fa-chart-bar',     'Results'],
    ['admins',       'fa-user-gear',     'Admins'],
    ['database',     'fa-terminal',      'Database'],
    ['settings',     'fa-cog',           'Settings'],
];
?>
<!DOCTYPE html>
<html lang="en" class="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo sanitizeOutput($pageTitle); ?> — Shikhbo</title>
    <meta name="robots" content="noindex, nofollow">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                colors: {
                    'shikhbo-primary': '#4F46E5',
                    'shikhbo-dark': '#1E293B',
                    'shikhbo-light': '#F8FAFC',
                }
            }
        }
    }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/custom.css">
</head>
<body class="bg-gray-50 dark:bg-gray-900 font-sans antialiased transition-colors duration-300">

<!-- ===== FULL WIDTH HEADER ===== -->
<header class="bg-gradient-to-r from-indigo-600 to-purple-600 dark:from-indigo-900 dark:to-purple-900 shadow-lg">
    <div class="flex items-center justify-between h-16 px-4 lg:px-8">
        <!-- Mobile menu toggle for sidebar -->
        <button id="sidebarToggle" class="lg:hidden text-white hover:text-gray-200 p-2 rounded-md flex-shrink-0">
            <i class="fa-solid fa-bars text-xl"></i>
        </button>

        <!-- Logo + Web Name (visible on desktop only) -->
        <div class="hidden lg:flex items-center space-x-3">
            <div class="w-10 h-10 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center shadow-lg">
                <i class="fa-solid fa-graduation-cap text-2xl text-white"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-white tracking-tight">Shikhbo</h1>
                <p class="text-xs text-white/70">Admin Panel</p>
            </div>
        </div>

        <!-- Right side actions -->
        <div class="flex items-center space-x-1 ml-auto">
            <input type="hidden" id="csrf_token" value="<?php echo generateCSRFToken(); ?>">

            <!-- Theme Toggle -->
            <button id="themeToggle" class="relative p-2 text-white/80 hover:text-white rounded-full hover:bg-white/10 transition-colors" title="Toggle dark mode">
                <i id="themeIcon" class="fa-solid fa-moon text-lg"></i>
            </button>

            <!-- Notifications -->
            <div class="relative" id="notifDropdown">
                <button id="notifButton" class="relative p-2 text-white/80 hover:text-white rounded-full hover:bg-white/10 transition-colors">
                    <i class="fa-solid fa-bell text-lg"></i>
                    <?php if ($notifCount > 0): ?><span class="absolute top-1 right-1 w-4 h-4 bg-red-500 text-white text-[10px] rounded-full flex items-center justify-center font-bold"><?php echo $notifCount > 99 ? '99+' : $notifCount; ?></span><?php endif; ?>
                </button>
                <div id="notifPanel" class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 hidden z-50 transform opacity-0 scale-95 transition-all duration-200 origin-top-right max-h-96 overflow-y-auto">
                    <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200"><i class="fa-solid fa-shield-halved text-red-500 mr-1"></i>Security Alerts</h3>
                        <span class="text-xs text-gray-400 dark:text-gray-500">Last 24h</span>
                    </div>
                    <div class="divide-y divide-gray-50 dark:divide-gray-700">
                        <?php if ($recentFailed && $recentFailed->num_rows > 0): ?>
                            <?php while ($nf = $recentFailed->fetch_assoc()): ?>
                                <div class="px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-700 text-xs">
                                    <div class="flex items-start space-x-2">
                                        <i class="fa-solid fa-circle-exclamation text-red-400 mt-0.5 flex-shrink-0"></i>
                                        <div class="min-w-0"><p class="text-gray-700 dark:text-gray-200 font-medium truncate"><?php echo sanitizeOutput($nf['email']); ?></p><p class="text-gray-400 dark:text-gray-500">IP: <?php echo sanitizeOutput($nf['ip_address']); ?> · <?php echo date('H:i', strtotime($nf['attempt_time'])); ?></p></div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="px-4 py-6 text-center text-xs text-gray-400 dark:text-gray-500"><i class="fa-solid fa-circle-check text-green-400 text-lg block mb-1"></i>No alerts</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Support Tickets -->
            <div class="relative" id="ticketDropdown">
                <button id="ticketButton" class="relative p-2 text-white/80 hover:text-white rounded-full hover:bg-white/10 transition-colors">
                    <i class="fa-solid fa-ticket text-lg"></i>
                    <?php if ($ticketCount > 0): ?><span class="absolute top-1 right-1 w-4 h-4 bg-orange-500 text-white text-[10px] rounded-full flex items-center justify-center font-bold"><?php echo $ticketCount; ?></span><?php endif; ?>
                </button>
                <div id="ticketPanel" class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 hidden z-50 transform opacity-0 scale-95 transition-all duration-200 origin-top-right max-h-96 overflow-y-auto">
                    <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200"><i class="fa-solid fa-life-ring text-orange-500 mr-1"></i>Support Tickets</h3>
                        <button onclick="openTicketModal()" class="text-xs text-shikhbo-primary hover:underline">+ New</button>
                    </div>
                    <div class="divide-y divide-gray-50 dark:divide-gray-700">
                        <?php if ($ticketsTableExists && $recentTickets && $recentTickets->num_rows > 0): ?>
                            <?php while ($tk = $recentTickets->fetch_assoc()): ?>
                                <div class="px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-700 text-xs">
                                    <p class="text-gray-700 dark:text-gray-200 font-medium truncate"><?php echo sanitizeOutput($tk['subject']); ?></p>
                                    <p class="text-gray-400 dark:text-gray-500"><?php echo sanitizeOutput($tk['user_name'] ?? 'Unknown'); ?> · <span class="px-1.5 py-0.5 rounded-full text-[10px] <?php echo $tk['status']==='open'?'bg-orange-100 text-orange-700':'bg-green-100 text-green-700'; ?>"><?php echo ucfirst(str_replace('_',' ',$tk['status'])); ?></span></p>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="px-4 py-6 text-center text-xs text-gray-400 dark:text-gray-500"><i class="fa-solid fa-inbox text-lg block mb-1"></i>No tickets</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Profile -->
            <div class="relative" id="profileDropdown">
                <button id="profileButton" class="flex items-center space-x-2 p-1.5 rounded-lg hover:bg-white/10 transition-colors">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($admin['name']); ?>&background=4F46E5&color=fff&size=32" alt="" class="w-8 h-8 rounded-full flex-shrink-0 border-2 border-white/30">
                    <span class="text-sm font-medium text-white hidden md:block truncate max-w-[80px]"><?php echo sanitizeOutput($admin['name']); ?></span>
                    <i class="fa-solid fa-chevron-down text-xs text-white/60 transition-transform duration-200 flex-shrink-0" id="dropdownChevron"></i>
                </button>
                <div id="profileMenu" class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 hidden z-50 transform opacity-0 scale-95 transition-all duration-200 origin-top-right">
                    <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700"><p class="text-sm font-medium text-gray-800 dark:text-gray-200"><?php echo sanitizeOutput($admin['name']); ?></p><p class="text-xs text-gray-500 dark:text-gray-400 truncate"><?php echo sanitizeOutput($admin['email']); ?></p></div>
                    <a href="index.php?page=settings" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">Settings</a>
                    <a href="/pages/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-b-lg">Logout</a>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- ===== MAIN LAYOUT: SIDEBAR + CONTENT ===== -->
<div class="flex flex-1 overflow-hidden" style="height: calc(100vh - 64px);">
    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar w-64 bg-white dark:bg-gray-900 shadow-lg flex-shrink-0 overflow-y-auto transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0 -translate-x-full fixed inset-y-0 left-0 z-50">
        <!-- Sidebar header: visible on mobile only (logo + close btn) -->
        <div class="lg:hidden flex items-center justify-between h-16 px-5 border-b border-gray-200 dark:border-gray-700">
            <a href="index.php" class="flex items-center space-x-2 flex-shrink-0">
                <div class="w-9 h-9 bg-gradient-to-br from-shikhbo-primary to-indigo-400 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="fa-solid fa-graduation-cap text-white text-sm"></i>
                </div>
                <span class="text-xl font-bold text-gray-800 dark:text-gray-100 whitespace-nowrap">Shikhbo</span>
            </a>
            <button id="sidebarClose" class="text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 p-1 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800"><i class="fa-solid fa-xmark text-xl"></i></button>
        </div>

        <!-- Navigation (visible always) -->
        <nav class="mt-3 px-2 flex-1 overflow-y-auto" style="max-height: calc(100vh - 140px);">
            <ul class="space-y-0.5">
                <?php foreach ($navItems as [$navPage, $navIcon, $navLabel]): $isActive = ($page === $navPage); ?>
                    <li>
                        <a href="index.php?page=<?php echo $navPage; ?>"
                           class="nav-link flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200
                                  <?php echo $isActive ? 'bg-shikhbo-primary text-white shadow-md shadow-indigo-200' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-shikhbo-primary'; ?>">
                            <span class="w-8 h-8 flex items-center justify-center rounded-lg flex-shrink-0
                                         <?php echo $isActive ? 'bg-white/20' : 'bg-gray-100 dark:bg-gray-800'; ?> mr-3">
                                <i class="fa-solid <?php echo $navIcon; ?> text-sm w-4 text-center"></i>
                            </span>
                            <span class="truncate"><?php echo $navLabel; ?></span>
                            <?php if ($isActive): ?><span class="ml-auto w-1.5 h-6 bg-white rounded-full flex-shrink-0"></span><?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <div class="p-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center space-x-3">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($admin['name']); ?>&background=4F46E5&color=fff&size=36" alt="" class="w-9 h-9 rounded-full flex-shrink-0">
                <div class="flex-1 min-w-0"><p class="text-sm font-medium text-gray-700 dark:text-gray-200 truncate"><?php echo sanitizeOutput($admin['name']); ?></p><p class="text-xs text-gray-400 dark:text-gray-500 truncate"><?php echo sanitizeOutput($admin['email']); ?></p></div>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main id="mainContent" class="flex-1 overflow-y-auto bg-gray-50 dark:bg-gray-900 p-4 lg:p-8 transition-opacity duration-200" style="scroll-behavior:smooth;">
        <?php include $pageFile; ?>
    </main>
</div>

<!-- Mobile overlay -->
<div id="sidebarOverlay" class="fixed inset-0 z-40 bg-black bg-opacity-50 hidden lg:hidden backdrop-blur-sm transition-opacity duration-300"></div>
<div id="toastContainer" class="fixed bottom-4 right-4 z-[100] flex flex-col-reverse space-y-reverse space-y-2"></div>

<!-- Ticket Modal -->
<div id="ticketModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
    <div class="absolute inset-0 bg-black bg-opacity-50" onclick="closeTicketModal()"></div>
    <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
        <div class="flex items-center justify-between mb-4"><h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">New Support Ticket</h3><button onclick="closeTicketModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"><i class="fa-solid fa-xmark text-xl"></i></button></div>
        <form id="ticketForm" class="space-y-4" onsubmit="submitTicket(event)">
            <?php echo getCSRFTokenField(); ?>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Subject</label><input type="text" id="ticketSubject" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg text-sm focus:ring-2 focus:ring-shikhbo-primary outline-none"></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Message</label><textarea id="ticketMessage" rows="3" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg text-sm focus:ring-2 focus:ring-shikhbo-primary outline-none"></textarea></div>
            <div class="flex justify-end space-x-3"><button type="button" onclick="closeTicketModal()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-700 dark:text-gray-300">Cancel</button><button type="submit" class="px-4 py-2 bg-shikhbo-primary text-white rounded-lg text-sm">Submit</button></div>
        </form>
    </div>
</div>
<script src="/js/custom.js"></script>
</body>
</html>