<?php
/**
 * Shikhbo Admin Panel - Enhanced Main Router
 * Route all admin pages, active state, smooth transitions, keyboard shortcuts
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/security.php';
requireAdminAuth();

$admin = getCurrentAdmin();

$page = $_GET['page'] ?? 'dashboard';
$allowedPages = [
    'dashboard', 'students', 'admins', 'settings',
    'exams', 'questions', 'results', 'categories',
    'database', 'exam_attempt'
];

if (!in_array($page, $allowedPages)) {
    $page = 'dashboard';
}

$pageFile = __DIR__ . '/pages/' . $page . '.php';
if (!file_exists($pageFile)) {
    $page = 'dashboard';
    $pageFile = __DIR__ . '/pages/dashboard.php';
}

$pageTitles = [
    'dashboard'     => 'Admin Dashboard',
    'students'      => 'Students Management',
    'admins'        => 'Admin Management',
    'settings'      => 'System Settings',
    'exams'         => 'Exams Management',
    'questions'     => 'Question Bank',
    'results'       => 'Exam Results',
    'categories'    => 'Exam Categories',
    'database'      => 'Database Console',
    'exam_attempt'  => 'Exam Attempt',
];
$pageTitle = $pageTitles[$page] ?? 'Admin Panel';

// Navigation items (icon, label, page)
$navItems = [
    ['dashboard',    'fa-chart-pie',   'Dashboard'],
    ['categories',   'fa-layer-group', 'Categories'],
    ['exams',        'fa-file-alt',    'Exams'],
    ['questions',    'fa-database',    'Question Bank'],
    ['exam_attempt', 'fa-play',        'Exam Attempt'],
    ['students',     'fa-users',       'Students'],
    ['results',      'fa-chart-bar',   'Results'],
    ['admins',       'fa-user-gear',   'Admins'],
    ['database',     'fa-terminal',    'Database'],
    ['settings',     'fa-cog',         'Settings'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo sanitizeOutput($pageTitle); ?> — Shikhbo Admin</title>
    <meta name="robots" content="noindex, nofollow">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
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

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="/css/custom.css">
</head>
<body class="bg-gray-50 font-sans antialiased">

    <!-- Wrapper: Sidebar + Main Content -->
    <div class="flex h-screen overflow-hidden">

        <!-- ============ SIDEBAR ============ -->
        <aside id="sidebar" 
               class="sidebar fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-lg 
                      transform transition-transform duration-300 ease-in-out 
                      lg:translate-x-0 lg:static lg:inset-0 -translate-x-full">

            <!-- Sidebar Header -->
            <div class="flex items-center justify-between h-16 px-6 border-b border-gray-200">
                <a href="index.php" class="flex items-center space-x-2">
                    <div class="w-9 h-9 bg-gradient-to-br from-shikhbo-primary to-indigo-400 rounded-lg flex items-center justify-center">
                        <i class="fa-solid fa-graduation-cap text-lg text-white"></i>
                    </div>
                    <span class="text-xl font-bold text-gray-800">Shikhbo</span>
                </a>
                <button id="sidebarClose" class="lg:hidden text-gray-500 hover:text-gray-700 p-1 rounded-md hover:bg-gray-100">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>

            <!-- Navigation Menu -->
            <nav class="mt-4 px-3 flex-1 overflow-y-auto" style="max-height: calc(100vh - 140px);">
                <ul class="space-y-1">
                    <?php foreach ($navItems as [$navPage, $navIcon, $navLabel]): 
                        $isActive = ($page === $navPage);
                    ?>
                        <li>
                            <a href="index.php?page=<?php echo $navPage; ?>" 
                               class="nav-link flex items-center px-3 py-2.5 text-sm font-medium rounded-lg 
                                      transition-all duration-200 group
                                      <?php echo $isActive 
                                          ? 'bg-shikhbo-primary text-white shadow-md shadow-indigo-200' 
                                          : 'text-gray-600 hover:bg-gray-100 hover:text-shikhbo-primary'; ?>">
                                <span class="w-8 h-8 flex items-center justify-center rounded-lg 
                                             <?php echo $isActive ? 'bg-white/20' : 'bg-gray-100 group-hover:bg-indigo-50'; ?> 
                                             transition-colors mr-3">
                                    <i class="fa-solid <?php echo $navIcon; ?> text-sm"></i>
                                </span>
                                <?php echo $navLabel; ?>
                                <?php if ($isActive): ?>
                                    <span class="ml-auto w-1.5 h-6 bg-white rounded-full"></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>

            <!-- Sidebar Footer -->
            <div class="p-4 border-t border-gray-200">
                <div class="flex items-center space-x-3">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($admin['name']); ?>&background=4F46E5&color=fff&size=36" 
                         alt="<?php echo sanitizeOutput($admin['name']); ?>" class="w-9 h-9 rounded-full">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-700 truncate"><?php echo sanitizeOutput($admin['name']); ?></p>
                        <p class="text-xs text-gray-400 truncate"><?php echo sanitizeOutput($admin['email']); ?></p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- ============ MAIN CONTENT AREA ============ -->
        <div class="flex-1 flex flex-col overflow-hidden">

            <!-- Top Header Bar -->
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="flex items-center justify-between h-16 px-4 lg:px-8">
                    <!-- Mobile menu toggle -->
                    <button id="sidebarToggle" class="lg:hidden text-gray-600 hover:text-gray-800 p-2 rounded-md hover:bg-gray-100">
                        <i class="fa-solid fa-bars text-xl"></i>
                    </button>

                    <!-- Page Title -->
                    <div class="hidden sm:flex items-center space-x-2 ml-2">
                        <h2 class="text-lg font-semibold text-gray-700"><?php echo sanitizeOutput($pageTitle); ?></h2>
                    </div>

                    <!-- Right side actions -->
                    <div class="flex items-center space-x-2 ml-auto">
                        <!-- CSRF Token (for AJAX calls) -->
                        <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                        <!-- Page Loading Indicator -->
                        <div id="pageLoader" class="hidden mr-2">
                            <div class="w-5 h-5 border-2 border-shikhbo-primary border-t-transparent rounded-full animate-spin"></div>
                        </div>

                        <!-- Notifications -->
                        <button class="relative p-2 text-gray-500 hover:text-shikhbo-primary rounded-full hover:bg-gray-100 transition-colors">
                            <i class="fa-solid fa-bell text-lg"></i>
                            <span class="absolute top-1 right-1 w-4 h-4 bg-red-500 text-white text-[10px] rounded-full flex items-center justify-center font-medium">3</span>
                        </button>

                        <!-- Profile Dropdown -->
                        <div class="relative" id="profileDropdown">
                            <button id="profileButton" class="flex items-center space-x-2 p-1.5 rounded-lg hover:bg-gray-100 transition-colors">
                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($admin['name']); ?>&background=4F46E5&color=fff&size=32" 
                                     alt="<?php echo sanitizeOutput($admin['name']); ?>" class="w-8 h-8 rounded-full">
                                <span class="text-sm font-medium text-gray-700 hidden md:block"><?php echo sanitizeOutput($admin['name']); ?></span>
                                <i class="fa-solid fa-chevron-down text-xs text-gray-400 transition-transform duration-200" id="dropdownChevron"></i>
                            </button>
                            <div id="profileMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 hidden z-50 transform opacity-0 scale-95 transition-all duration-200 origin-top-right">
                                <div class="px-4 py-3 border-b border-gray-100">
                                    <p class="text-sm font-medium text-gray-800"><?php echo sanitizeOutput($admin['name']); ?></p>
                                    <p class="text-xs text-gray-500 truncate"><?php echo sanitizeOutput($admin['email']); ?></p>
                                </div>
                                <a href="index.php?page=settings" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Settings</a>
                                <a href="/pages/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 rounded-b-lg">Logout</a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content (Dynamic) -->
            <main id="mainContent" class="flex-1 overflow-y-auto bg-gray-50 p-4 lg:p-8 transition-opacity duration-200" style="scroll-behavior: smooth;">
                <?php include $pageFile; ?>
            </main>
        </div>
    </div>

    <!-- Backdrop overlay (mobile) -->
    <div id="sidebarOverlay" class="fixed inset-0 z-40 bg-black bg-opacity-50 hidden lg:hidden backdrop-blur-sm transition-opacity duration-300"></div>

    <!-- Toast Container -->
    <div id="toastContainer" class="fixed bottom-4 right-4 z-[100] flex flex-col-reverse space-y-reverse space-y-2"></div>

    <!-- Custom JavaScript -->
    <script src="/js/custom.js"></script>
    <script>
        // Page transition smoothness
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                document.getElementById('pageLoader').classList.remove('hidden');
                document.getElementById('mainContent').classList.add('opacity-50');
            });
        });
        window.addEventListener('pageshow', function() {
            document.getElementById('pageLoader').classList.add('hidden');
            document.getElementById('mainContent').classList.remove('opacity-50');
        });
    </script>
</body>
</html>