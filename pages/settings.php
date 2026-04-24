<?php
$mysqli = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) { $error = 'Security token validation failed.'; }
    elseif ($_POST['action'] === 'change_password') {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        $stmt = $mysqli->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param('i', $_SESSION['admin_id']);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!password_verify($currentPassword, $user['password'])) { $error = 'Current password is incorrect.'; }
        elseif ($newPassword !== $confirmPassword) { $error = 'New passwords do not match.'; }
        elseif (strlen($newPassword) < 8) { $error = 'Password must be at least 8 characters.'; }
        else {
            $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $mysqli->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param('si', $hash, $_SESSION['admin_id']);
            $stmt->execute(); $stmt->close();
            $success = 'Password changed successfully.';
        }
    }
}
?>

<div class="page-content max-w-2xl">
    <div class="mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 dark:text-gray-100">Settings</h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1">Manage your account and preferences</p>
    </div>

    <?php if (isset($error)): ?>
    <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl flex items-center gap-3 alert-auto-dismiss">
        <i class="fa-solid fa-circle-exclamation text-red-500"></i><span class="text-red-700 dark:text-red-300"><?php echo sanitizeOutput($error); ?></span>
    </div>
    <?php endif; ?>
    <?php if (isset($success)): ?>
    <div class="mb-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl flex items-center gap-3 alert-auto-dismiss">
        <i class="fa-solid fa-circle-check text-green-500"></i><span class="text-green-700 dark:text-green-300"><?php echo sanitizeOutput($success); ?></span>
    </div>
    <?php endif; ?>

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-gray-100 dark:border-gray-700 overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                <i class="fa-solid fa-lock text-indigo-600 dark:text-indigo-400"></i>
            </div>
            <div>
                <h3 class="font-semibold text-gray-800 dark:text-gray-100">Change Password</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Update your admin password</p>
            </div>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <?php echo getCSRFTokenField(); ?>
            <input type="hidden" name="action" value="change_password">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Current Password</label>
                <input type="password" name="current_password" required class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">New Password</label>
                <input type="password" name="new_password" required minlength="8" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced" placeholder="Min. 8 characters">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Confirm New Password</label>
                <input type="password" name="confirm_password" required minlength="8" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced">
            </div>
            <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700 transition-colors flex items-center gap-2">
                <i class="fa-solid fa-key"></i>Update Password
            </button>
        </form>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                <i class="fa-solid fa-shield-check text-green-600 dark:text-green-400"></i>
            </div>
            <div>
                <h3 class="font-semibold text-gray-800 dark:text-gray-100">System Information</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Security and configuration details</p>
            </div>
        </div>
        <div class="p-6">
            <dl class="space-y-4">
                <div class="flex items-center justify-between py-2 border-b border-gray-50 dark:border-gray-700">
                    <div class="flex items-center gap-2">
                        <i class="fa-brands fa-php text-purple-500"></i>
                        <span class="text-sm text-gray-500 dark:text-gray-400">PHP Version</span>
                    </div>
                    <span class="text-sm font-medium text-gray-800 dark:text-gray-100"><?php echo phpversion(); ?></span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-gray-50 dark:border-gray-700">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-database text-blue-500"></i>
                        <span class="text-sm text-gray-500 dark:text-gray-400">Database</span>
                    </div>
                    <span class="text-sm font-medium text-gray-800 dark:text-gray-100">MySQL (Railway)</span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-gray-50 dark:border-gray-700">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-lock text-green-500"></i>
                        <span class="text-sm text-gray-500 dark:text-gray-400">Session Security</span>
                    </div>
                    <span class="text-sm font-medium text-green-600 dark:text-green-400">HTTPS + HttpOnly + SameSite Strict</span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-gray-50 dark:border-gray-700">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-shield-halved text-indigo-500"></i>
                        <span class="text-sm text-gray-500 dark:text-gray-400">CSRF Protection</span>
                    </div>
                    <span class="text-sm font-medium text-green-600 dark:text-green-400">Synchronized Token</span>
                </div>
                <div class="flex items-center justify-between py-2">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-clock text-yellow-500"></i>
                        <span class="text-sm text-gray-500 dark:text-gray-400">Rate Limiting</span>
                    </div>
                    <span class="text-sm font-medium text-green-600 dark:text-green-400">5 attempts / 15 min</span>
                </div>
            </dl>
        </div>
    </div>
</div>