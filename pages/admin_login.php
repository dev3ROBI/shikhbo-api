<?php
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) {
    $role = $_SESSION['user_role'] ?? '';
    $redirect = $_GET['redirect'] ?? '';
    if ($redirect && $redirect !== 'admin_login') {
        header('Location: /' . ltrim($redirect, '/'));
    } elseif (isAdminRole($role)) {
        header('Location: /index.php?page=dashboard');
    } else {
        header('Location: /index.php');
    }
    exit;
}

$error = '';
$success = '';

$redirectParam = isset($_GET['redirect']) ? trim($_GET['redirect']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token validation failed. Please refresh the page.';
    } elseif (!validateCaptcha($_POST['captcha'] ?? '')) {
        $error = 'Incorrect captcha answer. Please try again.';
    } else {
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $result = authenticateUser($email, $password);
        
        if ($result['status'] === 'success') {
            $redirect = $_POST['redirect'] ?: $redirectParam ?: ($_SESSION['redirect_after_login'] ?? '');
            unset($_SESSION['redirect_after_login']);

            if ($redirect && $redirect !== 'admin_login') {
                header('Location: /' . ltrim($redirect, '/'));
            } elseif (isAdminRole($result['role'])) {
                header('Location: /index.php?page=dashboard');
            } else {
                header('Location: /index.php');
            }
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

generateCaptcha();

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
    <title>Sign In — Shikhbo</title>
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
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <style>
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes gradientShift {
            0%   { background-position: 0% 50%; }
            50%  { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        body {
            background: linear-gradient(135deg, #0F172A 0%, #1E3A5F 40%, #312E81 100%);
            background-size: 200% 200%;
            animation: gradientShift 12s ease infinite;
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .login-card {
            animation: fadeInUp 0.6s ease forwards;
            backdrop-filter: blur(16px);
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(255,255,255,0.2);
        }
        .dark .login-card {
            background: rgba(30, 41, 59, 0.92);
            border-color: rgba(255,255,255,0.05);
        }
        .input-field {
            transition: all 0.2s ease;
        }
        .input-field:focus {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px -2px rgba(79, 70, 229, 0.2);
        }
        .g_id_signin > div {
            margin: 0 auto !important;
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
    <div class="w-full max-w-sm">
        <!-- Logo -->
        <div class="text-center mb-6 anim-fade-in">
            <img src="/image/app_logo.png" alt="Shikhbo" class="w-16 h-16 object-contain mx-auto mb-3" style="filter: drop-shadow(0 0 12px rgba(37,99,235,0.4));">
            <h1 class="text-2xl font-bold text-white">Shikhbo</h1>
            <p class="text-white/60 text-sm mt-0.5">Sign in to your account</p>
        </div>

        <!-- Card -->
        <div class="login-card rounded-2xl shadow-2xl p-6">
            <!-- Messages -->
            <?php if ($error): ?>
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-2.5 rounded-lg mb-4 flex items-center space-x-2 text-sm">
                    <i class="fa-solid fa-circle-exclamation flex-shrink-0"></i>
                    <span><?php echo sanitizeOutput($error); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 px-4 py-2.5 rounded-lg mb-4 flex items-center space-x-2 text-sm">
                    <i class="fa-solid fa-circle-check flex-shrink-0"></i>
                    <span><?php echo sanitizeOutput($success); ?></span>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="" id="loginForm" autocomplete="off" class="space-y-3.5">
                <?php echo getCSRFTokenField(); ?>
                <?php if ($redirectParam): ?>
                <input type="hidden" name="redirect" value="<?php echo sanitizeOutput($redirectParam); ?>">
                <?php endif; ?>

                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Email</label>
                    <div class="relative">
                        <i class="fa-solid fa-envelope absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500 text-sm"></i>
                        <input type="email" name="email" required
                               class="input-field w-full pl-9 pr-3 py-2.5 border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg text-sm focus:ring-2 focus:ring-shikhbo-primary focus:border-transparent outline-none"
                               placeholder="you@example.com">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Password</label>
                    <div class="relative">
                        <i class="fa-solid fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500 text-sm"></i>
                        <input type="password" name="password" id="password" required
                               class="input-field w-full pl-9 pr-10 py-2.5 border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg text-sm focus:ring-2 focus:ring-shikhbo-primary focus:border-transparent outline-none"
                               placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
                        <button type="button" onclick="togglePassword()"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-sm">
                            <i class="fa-solid fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                        Captcha: <span class="font-semibold text-shikhbo-primary"><?php echo getCaptchaQuestion(); ?></span>
                    </label>
                    <div class="relative">
                        <i class="fa-solid fa-shield-halved absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500 text-sm"></i>
                        <input type="number" name="captcha" required min="0" max="99"
                               class="input-field w-full pl-9 pr-3 py-2.5 border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg text-sm focus:ring-2 focus:ring-shikhbo-primary focus:border-transparent outline-none"
                               placeholder="Enter answer">
                    </div>
                </div>

                <button type="submit"
                        class="w-full bg-shikhbo-primary text-white py-2.5 rounded-lg font-medium hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-all duration-200 flex items-center justify-center space-x-2 text-sm">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    <span>Sign In</span>
                </button>
            </form>

            <!-- Divider -->
            <div class="flex items-center gap-3 my-4">
                <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700"></div>
                <span class="text-xs text-gray-400 dark:text-gray-500">or continue with</span>
                <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700"></div>
            </div>

            <!-- Google Sign-In -->
            <div id="g_id_onload"
                 data-client_id="151985259285-nvemiiq9gg5lh7ap27vcrv25jv930ddm.apps.googleusercontent.com"
                 data-context="signin"
                 data-ux_mode="popup"
                 data-callback="handleGoogleCredential"
                 data-auto_prompt="false">
            </div>
            <div class="flex justify-center">
                <div class="g_id_signin"
                     data-type="standard"
                     data-shape="pill"
                     data-theme="outline"
                     data-text="continue_with"
                     data-size="large"
                     data-logo_alignment="left"
                     data-width="280">
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-5 pt-4 border-t border-gray-100 dark:border-gray-700/50 text-center space-y-1.5">
                <a href="/index.php" class="text-xs text-gray-400 dark:text-gray-500 hover:text-shikhbo-primary transition-colors">
                    <i class="fa-solid fa-arrow-left mr-1"></i> Back to Home
                </a>
            </div>
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
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i><span class="ml-2">Signing in...</span>';
        });

        function handleGoogleCredential(response) {
            const overlay = document.createElement('div');
            overlay.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black/30 backdrop-blur-sm';
            overlay.innerHTML = '<div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-2xl"><div class="w-8 h-8 border-3 border-shikhbo-primary border-t-transparent rounded-full animate-spin mx-auto"></div><p class="text-sm text-gray-500 dark:text-gray-400 mt-3">Signing in with Google...</p></div>';
            document.body.appendChild(overlay);

            fetch('/api/google_login_web.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ credential: response.credential })
            })
            .then(r => r.text().then(text => {
                try {
                    const d = JSON.parse(text);
                    overlay.remove();
                    if (d.status === 'success') {
                        window.location.href = d.redirect;
                    } else {
                        alert(d.message || 'Google sign-in failed.');
                    }
                } catch(e) {
                    overlay.remove();
                    alert('Server error. Response: ' + text.substring(0, 200));
                }
            }))
            .catch(err => {
                overlay.remove();
                alert('Network error. Unable to reach server.');
            });
        }

        // Dark mode sync
        if (localStorage.getItem('shikhbo-theme') === 'dark' ||
            (!localStorage.getItem('shikhbo-theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</body>
</html>
