<?php
require_once __DIR__ . '/../includes/auth.php';

if (isAdminLoggedIn()) {
    header('Location: /index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token validation failed. Please refresh the page.';
    } else {
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $result = authenticateAdmin($email, $password);
        
        if ($result['status'] === 'success') {
            $redirect = $_SESSION['redirect_after_login'] ?? '/index.php';
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

if (isset($_GET['logout'])) {
    $success = 'You have been successfully logged out.';
}

if (isset($_GET['expired'])) {
    $error = 'Your session has expired. Please login again.';
}
?>
<!DOCTYPE html>
<html lang="en" class="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Shikhbo Panel</title>
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
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/custom.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .dark body {
            background: linear-gradient(135deg, #19172d 0%, #201f56 50%, #1e1b4b 100%);
        }
        .login-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.98);
        }
        .dark .login-card {
            background: rgba(30, 41, 59, 0.95);
        }
    </style>
</head>
<body class="font-sans antialiased flex items-center justify-center min-h-screen p-4">
    <div class="login-card w-full max-w-md rounded-2xl shadow-2xl p-8 dark:shadow-gray-900/50">
        <!-- Logo & Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-shikhbo-primary rounded-full mb-4">
                <i class="fa-solid fa-graduation-cap text-2xl text-white"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Shikhbo Admin</h1>
            <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Secure Admin Panel Access</p>
        </div>

        <!-- Messages -->
        <?php if ($error): ?>
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg mb-4 flex items-center space-x-2">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo sanitizeOutput($error); ?></span>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 px-4 py-3 rounded-lg mb-4 flex items-center space-x-2">
                <i class="fa-solid fa-circle-check"></i>
                <span><?php echo sanitizeOutput($success); ?></span>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" action="" id="loginForm" autocomplete="off">
            <?php echo getCSRFTokenField(); ?>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email Address</label>
                    <div class="relative">
                        <i class="fa-solid fa-envelope absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500"></i>
                        <input type="email" name="email" required
                               class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg text-sm focus:ring-2 focus:ring-shikhbo-primary focus:border-transparent outline-none transition-all"
                               placeholder="admin@shikhbo.com">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password</label>
                    <div class="relative">
                        <i class="fa-solid fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500"></i>
                        <input type="password" name="password" id="password" required
                               class="w-full pl-10 pr-12 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg text-sm focus:ring-2 focus:ring-shikhbo-primary focus:border-transparent outline-none transition-all"
                               placeholder="••••••••">
                        <button type="button" onclick="togglePassword()"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300">
                            <i class="fa-solid fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit"
                        class="w-full bg-shikhbo-primary text-white py-3 rounded-lg font-medium hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-all duration-200 flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    <span>Sign In</span>
                </button>
            </div>
        </form>

        <!-- Footer -->
        <div class="mt-6 text-center text-xs text-gray-400 dark:text-gray-500">
            <p>🔒 Secured with CSRF Protection & Rate Limiting</p>
            <p class="mt-1">Shikhbo API v1.0</p>
        </div>
    </div>

    <script>
        function togglePassword() {
            const pwd = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                pwd.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = this.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i><span>Signing in...</span>';
        });
    </script>
    <script src="/js/custom.js"></script>
</body>
</html>