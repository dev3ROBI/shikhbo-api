<?php
/**
 * Shikhbo Admin Panel - Main Entry Point
 * Secure Router: All pages accessed through sidebar navigation
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/security.php';

// Require admin authentication for ALL admin pages
requireAdminAuth();

$admin = getCurrentAdmin();

// Determine which page to load
$page = $_GET['page'] ?? 'dashboard';
$allowedPages = ['dashboard', 'students', 'admins', 'settings', 'exams', 'questions', 'results', 'categories', 'database'];

if (!in_array($page, $allowedPages)) {
    $page = 'dashboard';
}

$pageFile = __DIR__ . '/pages/' . $page . '.php';
if (!file_exists($pageFile)) {
    $page = 'dashboard';
    $pageFile = __DIR__ . '/pages/dashboard.php';
}

// Page titles
$pageTitles = [
    'dashboard' => 'Admin Dashboard',
    'students' => 'Students Management',
    'admins' => 'Admin Management',
    'settings' => 'System Settings',
    'exams' => 'Exams Management',
    'questions' => 'Question Bank',
    'results' => 'Exam Results',
    'categories' => 'Exam Categories',
    'database' => 'Database Console'
];
$pageTitle = $pageTitles[$page] ?? 'Admin Panel';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo sanitizeOutput($pageTitle); ?> - Shikhbo Admin</title>
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
            class="sidebar fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-lg transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0 -translate-x-full">
            <!-- Sidebar Header -->
            <div class="flex items-center justify-between h-16 px-6 border-b border-gray-200">
                <a href="index.php" class="flex items-center space-x-2">
                    <i class="fa-solid fa-graduation-cap text-2xl text-shikhbo-primary"></i>
                    <span class="text-xl font-bold text-gray-800">Shikhbo</span>
                </a>
                <button id="sidebarClose" class="lg:hidden text-gray-500 hover:text-gray-700">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>

            <!-- Navigation Menu -->
            <nav class="mt-6 px-4">
                <ul class="space-y-2">
                    <li>
                        <a href="index.php?page=dashboard"
                            class="nav-link flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors
                           <?php echo $page === 'dashboard' ? 'bg-shikhbo-primary text-white shadow-md' : 'text-gray-600 hover:bg-gray-100 hover:text-shikhbo-primary'; ?>">
                            <i class="fa-solid fa-chart-pie w-5 h-5 mr-3"></i>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="index.php?page=categories"
                            class="nav-link flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors
                            <?php echo $page === 'categories' ? 'bg-shikhbo-primary text-white shadow-md' : 'text-gray-600 hover:bg-gray-100 hover:text-shikhbo-primary'; ?>">
                            <i class="fa-solid fa-layer-group w-5 h-5 mr-3"></i>
                            Categories
                        </a>
                    </li>
                    <li>
                        <a href="index.php?page=exams"
                            class="nav-link flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors
                           <?php echo $page === 'exams' ? 'bg-shikhbo-primary text-white shadow-md' : 'text-gray-600 hover:bg-gray-100 hover:text-shikhbo-primary'; ?>">
                            <i class="fa-solid fa-file-alt w-5 h-5 mr-3"></i>
                            Exams
                        </a>
                    </li>
                    <li>
                        <a href="index.php?page=questions"
                            class="nav-link flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors
                           <?php echo $page === 'questions' ? 'bg-shikhbo-primary text-white shadow-md' : 'text-gray-600 hover:bg-gray-100 hover:text-shikhbo-primary'; ?>">
                            <i class="fa-solid fa-database w-5 h-5 mr-3"></i>
                            Question Bank
                        </a>
                    </li>
                    <li>
                        <a href="index.php?page=students"
                            class="nav-link flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors
                           <?php echo $page === 'students' ? 'bg-shikhbo-primary text-white shadow-md' : 'text-gray-600 hover:bg-gray-100 hover:text-shikhbo-primary'; ?>">
                            <i class="fa-solid fa-users w-5 h-5 mr-3"></i>
                            Students
                        </a>
                    </li>
                    <li>
                        <a href="index.php?page=results"
                            class="nav-link flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors
                           <?php echo $page === 'results' ? 'bg-shikhbo-primary text-white shadow-md' : 'text-gray-600 hover:bg-gray-100 hover:text-shikhbo-primary'; ?>">
                            <i class="fa-solid fa-chart-bar w-5 h-5 mr-3"></i>
                            Results
                        </a>
                    </li>
                    <li>
                        <a href="index.php?page=admins"
                            class="nav-link flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors
                           <?php echo $page === 'admins' ? 'bg-shikhbo-primary text-white shadow-md' : 'text-gray-600 hover:bg-gray-100 hover:text-shikhbo-primary'; ?>">
                            <i class="fa-solid fa-user-gear w-5 h-5 mr-3"></i>
                            Admins
                        </a>
                    </li>
                    <li>
                        <a href="index.php?page=database"
                            class="nav-link flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors
                            <?php echo $page === 'database' ? 'bg-shikhbo-primary text-white shadow-md' : 'text-gray-600 hover:bg-gray-100 hover:text-shikhbo-primary'; ?>">
                            <i class="fa-solid fa-terminal w-5 h-5 mr-3"></i>
                            Database
                        </a>
                    </li>
                    <li>
                        <a href="index.php?page=settings"
                            class="nav-link flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors
                           <?php echo $page === 'settings' ? 'bg-shikhbo-primary text-white shadow-md' : 'text-gray-600 hover:bg-gray-100 hover:text-shikhbo-primary'; ?>">
                            <i class="fa-solid fa-cog w-5 h-5 mr-3"></i>
                            Settings
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- Sidebar Footer -->
            <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200">
                <div class="flex items-center space-x-3 text-sm text-gray-500">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>Shikhbo API v1.0</span>
                </div>
            </div>
        </aside>

        <!-- ============ MAIN CONTENT AREA ============ -->
        <div class="flex-1 flex flex-col overflow-hidden">

            <!-- Top Header Bar -->
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="flex items-center justify-between h-16 px-4 lg:px-8">
                    <!-- Mobile menu toggle -->
                    <button id="sidebarToggle" class="lg:hidden text-gray-600 hover:text-gray-800 p-2 rounded-md">
                        <i class="fa-solid fa-bars text-xl"></i>
                    </button>

                    <!-- Page Title -->
                    <div class="hidden sm:block ml-4">
                        <h2 class="text-lg font-semibold text-gray-700"><?php echo sanitizeOutput($pageTitle); ?></h2>
                    </div>

                    <!-- Right side actions -->
                    <div class="flex items-center space-x-3 ml-auto">
                        <!-- CSRF Token (for AJAX calls) -->
                        <input type="hidden" id="csrf_token" name="csrf_token"
                            value="<?php echo generateCSRFToken(); ?>">

                        <!-- Notifications -->
                        <button
                            class="relative p-2 text-gray-500 hover:text-shikhbo-primary rounded-full hover:bg-gray-100">
                            <i class="fa-solid fa-bell text-xl"></i>
                            <span
                                class="absolute top-1 right-1 w-4 h-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center">3</span>
                        </button>

                        <!-- Profile Dropdown -->
                        <div class="relative" id="profileDropdown">
                            <button id="profileButton"
                                class="flex items-center space-x-2 p-2 rounded-lg hover:bg-gray-100">
                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($admin['name']); ?>&background=4F46E5&color=fff"
                                    alt="<?php echo sanitizeOutput($admin['name']); ?>" class="w-8 h-8 rounded-full">
                                <span class="text-sm font-medium text-gray-700 hidden md:block">
                                    <?php echo sanitizeOutput($admin['name']); ?>
                                </span>
                                <i class="fa-solid fa-chevron-down text-xs text-gray-400"></i>
                            </button>
                            <div id="profileMenu"
                                class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg border border-gray-200 hidden z-50">
                                <div class="px-4 py-2 border-b border-gray-100">
                                    <p class="text-sm font-medium text-gray-800">
                                        <?php echo sanitizeOutput($admin['name']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo sanitizeOutput($admin['email']); ?></p>
                                </div>
                                <a href="index.php?page=settings"
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                                <hr class="my-1">
                                <a href="/pages/logout.php"
                                    class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">Logout</a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content (Dynamic) -->
            <main class="flex-1 overflow-y-auto bg-gray-50 p-4 lg:p-8">
                <?php include $pageFile; ?>
            </main>
        </div>
    </div>

    <!-- Backdrop overlay (mobile) -->
    <div id="sidebarOverlay" class="fixed inset-0 z-40 bg-black bg-opacity-50 hidden lg:hidden"></div>

    <!-- Custom JavaScript -->
    <script src="/js/custom.js"></script>
</body>

</html>