<?php
$mysqli = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token validation failed.';
    } elseif ($_POST['action'] === 'change_password') {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        $stmt = $mysqli->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param('i', $_SESSION['admin_id']);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!password_verify($currentPassword, $user['password'])) {
            $error = 'Current password is incorrect.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match.';
        } elseif (strlen($newPassword) < 8) {
            $error = 'Password must be at least 8 characters.';
        } else {
            $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $mysqli->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param('si', $hash, $_SESSION['admin_id']);
            $stmt->execute();
            $stmt->close();
            $success = 'Password changed successfully.';
        }
    }
}
?>

<div class="max-w-2xl">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">System Settings</h1>

    <?php if (isset($error)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4"><?php echo sanitizeOutput($error); ?></div>
    <?php endif; ?>
    <?php if (isset($success)): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4"><?php echo sanitizeOutput($success); ?></div>
    <?php endif; ?>

    <!-- Change Password -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Change Password</h3>
        <form method="POST" class="space-y-4">
            <?php echo getCSRFTokenField(); ?>
            <input type="hidden" name="action" value="change_password">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                <input type="password" name="current_password" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-shikhbo-primary focus:border-transparent outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                <input type="password" name="new_password" required minlength="8" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-shikhbo-primary focus:border-transparent outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                <input type="password" name="confirm_password" required minlength="8" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-shikhbo-primary focus:border-transparent outline-none">
            </div>
            <button type="submit" class="px-6 py-2 bg-shikhbo-primary text-white rounded-lg font-medium hover:bg-indigo-700 transition-colors">
                Update Password
            </button>
        </form>
    </div>

    <!-- System Info -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">System Information</h3>
        <dl class="space-y-3">
            <div class="flex justify-between">
                <dt class="text-sm text-gray-500">PHP Version:</dt>
                <dd class="text-sm font-medium text-gray-800"><?php echo phpversion(); ?></dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-sm text-gray-500">Database:</dt>
                <dd class="text-sm font-medium text-gray-800">MySQL (Railway)</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-sm text-gray-500">Session Security:</dt>
                <dd class="text-sm font-medium text-green-600">HTTPS + HttpOnly + SameSite Strict</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-sm text-gray-500">CSRF Protection:</dt>
                <dd class="text-sm font-medium text-green-600">Enabled (Synchronized Token)</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-sm text-gray-500">Rate Limiting:</dt>
                <dd class="text-sm font-medium text-green-600">5 attempts / 15 min</dd>
            </div>
        </dl>
    </div>
</div>