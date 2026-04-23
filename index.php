<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shikhbo Admin Panel - Online Exam System</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Tailwind configuration (optional)
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

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/custom.css">
</head>
<body class="bg-gray-50 font-sans antialiased">

    <!-- ============================================ -->
    <!-- Wrapper: Sidebar + Main Content              -->
    <!-- ============================================ -->
    <div class="flex h-screen overflow-hidden">

        <!-- ============ SIDEBAR ============ -->
        <aside id="sidebar" class="sidebar fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-lg transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0 -translate-x-full">
            <!-- Sidebar Header / Logo -->
            <div class="flex items-center justify-between h-16 px-6 border-b border-gray-200">
                <a href="index.php" class="flex items-center space-x-2">
                    <i class="fa-solid fa-graduation-cap text-2xl text-shikhbo-primary"></i>
                    <span class="text-xl font-bold text-gray-800">Shikhbo</span>
                </a>
                <!-- Close button (mobile) -->
                <button id="sidebarClose" class="lg:hidden text-gray-500 hover:text-gray-700">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>

            <!-- Navigation Menu -->
            <nav class="mt-6 px-4">
                <ul class="space-y-2">
                    <!-- Dashboard (Active) -->
                    <li>
                        <a href="index.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg bg-shikhbo-primary text-white shadow-md">
                            <i class="fa-solid fa-chart-pie w-5 h-5 mr-3"></i>
                            Dashboard
                        </a>
                    </li>
                    <!-- Exams Management -->
                    <li>
                        <a href="#" class="flex items-center px-4 py-3 text-sm font-medium text-gray-600 rounded-lg hover:bg-gray-100 hover:text-shikhbo-primary transition-colors">
                            <i class="fa-solid fa-file-alt w-5 h-5 mr-3"></i>
                            Exams
                        </a>
                    </li>
                    <!-- Question Bank -->
                    <li>
                        <a href="#" class="flex items-center px-4 py-3 text-sm font-medium text-gray-600 rounded-lg hover:bg-gray-100 hover:text-shikhbo-primary transition-colors">
                            <i class="fa-solid fa-database w-5 h-5 mr-3"></i>
                            Question Bank
                        </a>
                    </li>
                    <!-- Students -->
                    <li>
                        <a href="#" class="flex items-center px-4 py-3 text-sm font-medium text-gray-600 rounded-lg hover:bg-gray-100 hover:text-shikhbo-primary transition-colors">
                            <i class="fa-solid fa-users w-5 h-5 mr-3"></i>
                            Students
                        </a>
                    </li>
                    <!-- Results -->
                    <li>
                        <a href="#" class="flex items-center px-4 py-3 text-sm font-medium text-gray-600 rounded-lg hover:bg-gray-100 hover:text-shikhbo-primary transition-colors">
                            <i class="fa-solid fa-chart-bar w-5 h-5 mr-3"></i>
                            Results
                        </a>
                    </li>
                    <!-- Users (Admin/Instructors) -->
                    <li>
                        <a href="#" class="flex items-center px-4 py-3 text-sm font-medium text-gray-600 rounded-lg hover:bg-gray-100 hover:text-shikhbo-primary transition-colors">
                            <i class="fa-solid fa-user-gear w-5 h-5 mr-3"></i>
                            Admins
                        </a>
                    </li>
                    <!-- Settings -->
                    <li>
                        <a href="#" class="flex items-center px-4 py-3 text-sm font-medium text-gray-600 rounded-lg hover:bg-gray-100 hover:text-shikhbo-primary transition-colors">
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

                    <!-- Search Bar -->
                    <div class="hidden sm:flex items-center space-x-2 flex-1 max-w-md ml-4">
                        <div class="relative w-full">
                            <input type="text" placeholder="Search exams, students..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-shikhbo-primary focus:border-transparent">
                            <i class="fa-solid fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        </div>
                    </div>

                    <!-- Right side actions -->
                    <div class="flex items-center space-x-3 ml-auto">
                        <!-- Notifications -->
                        <button class="relative p-2 text-gray-500 hover:text-shikhbo-primary rounded-full hover:bg-gray-100">
                            <i class="fa-solid fa-bell text-xl"></i>
                            <span class="absolute top-1 right-1 w-4 h-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center">3</span>
                        </button>
                        <!-- Profile Dropdown -->
                        <div class="relative" id="profileDropdown">
                            <button id="profileButton" class="flex items-center space-x-2 p-2 rounded-lg hover:bg-gray-100">
                                <img src="https://ui-avatars.com/api/?name=Admin&background=4F46E5&color=fff" alt="Admin" class="w-8 h-8 rounded-full">
                                <span class="text-sm font-medium text-gray-700 hidden md:block">Admin</span>
                                <i class="fa-solid fa-chevron-down text-xs text-gray-400"></i>
                            </button>
                            <!-- Dropdown Menu (hidden by default) -->
                            <div id="profileMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg border border-gray-200 hidden">
                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                                <hr class="my-1">
                                <a href="#" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">Logout</a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto bg-gray-50 p-4 lg:p-8">

                <!-- Page Header -->
                <div class="mb-8">
                    <h1 class="text-2xl font-bold text-gray-800">Admin Dashboard</h1>
                    <p class="text-gray-500 mt-1">Welcome back! Here's what's happening today.</p>
                </div>

                <!-- Dashboard Stats Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Total Exams -->
                    <div class="bg-white rounded-xl shadow-md p-6 transition-all hover:shadow-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Exams</p>
                                <p class="text-2xl font-bold text-gray-800 mt-1">42</p>
                            </div>
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fa-solid fa-file-alt text-blue-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-4 text-sm text-green-600 flex items-center">
                            <i class="fa-solid fa-arrow-up mr-1"></i>
                            <span>12% from last month</span>
                        </div>
                    </div>

                    <!-- Total Students -->
                    <div class="bg-white rounded-xl shadow-md p-6 transition-all hover:shadow-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Students</p>
                                <p class="text-2xl font-bold text-gray-800 mt-1">1,245</p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fa-solid fa-users text-green-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-4 text-sm text-green-600 flex items-center">
                            <i class="fa-solid fa-arrow-up mr-1"></i>
                            <span>8% from last month</span>
                        </div>
                    </div>

                    <!-- Questions -->
                    <div class="bg-white rounded-xl shadow-md p-6 transition-all hover:shadow-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Questions</p>
                                <p class="text-2xl font-bold text-gray-800 mt-1">3,210</p>
                            </div>
                            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                                <i class="fa-solid fa-database text-purple-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-4 text-sm text-green-600 flex items-center">
                            <i class="fa-solid fa-arrow-up mr-1"></i>
                            <span>25% from last month</span>
                        </div>
                    </div>

                    <!-- Results Pending -->
                    <div class="bg-white rounded-xl shadow-md p-6 transition-all hover:shadow-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Pending Results</p>
                                <p class="text-2xl font-bold text-gray-800 mt-1">18</p>
                            </div>
                            <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                                <i class="fa-solid fa-clock text-yellow-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-4 text-sm text-red-600 flex items-center">
                            <i class="fa-solid fa-exclamation-triangle mr-1"></i>
                            <span>Needs attention</span>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities Table -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-800">Recent Exams</h3>
                        <a href="#" class="text-sm text-shikhbo-primary hover:underline">View All</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Exam Title</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <!-- Row 1 -->
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800">Midterm Mathematics</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">Mathematics</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">15 Jan, 2026</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded-full">Active</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <button class="text-shikhbo-primary hover:underline mr-3">Edit</button>
                                        <button class="text-red-600 hover:underline">Delete</button>
                                    </td>
                                </tr>
                                <!-- Row 2 -->
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800">Physics Final</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">Physics</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">20 Jan, 2026</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold text-yellow-800 bg-yellow-100 rounded-full">Pending</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <button class="text-shikhbo-primary hover:underline mr-3">Edit</button>
                                        <button class="text-red-600 hover:underline">Delete</button>
                                    </td>
                                </tr>
                                <!-- Row 3 -->
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800">Chemistry Quiz 4</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">Chemistry</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">25 Jan, 2026</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold text-red-800 bg-red-100 rounded-full">Completed</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <button class="text-shikhbo-primary hover:underline mr-3">Edit</button>
                                        <button class="text-red-600 hover:underline">Delete</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Pagination placeholder -->
                    <div class="px-6 py-3 border-t border-gray-200 flex items-center justify-between">
                        <p class="text-sm text-gray-600">Showing 1 to 3 of 42 entries</p>
                        <div class="flex space-x-1">
                            <button class="px-3 py-1 text-sm border rounded hover:bg-gray-100">Previous</button>
                            <button class="px-3 py-1 text-sm border bg-shikhbo-primary text-white rounded">1</button>
                            <button class="px-3 py-1 text-sm border rounded hover:bg-gray-100">2</button>
                            <button class="px-3 py-1 text-sm border rounded hover:bg-gray-100">3</button>
                            <button class="px-3 py-1 text-sm border rounded hover:bg-gray-100">Next</button>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Backdrop overlay (mobile) -->
    <div id="sidebarOverlay" class="fixed inset-0 z-40 bg-black bg-opacity-50 hidden lg:hidden"></div>

    <!-- Custom JavaScript -->
    <script src="js/custom.js"></script>
</body>
</html>