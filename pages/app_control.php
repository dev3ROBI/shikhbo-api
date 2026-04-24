<?php
$mysqli = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token validation failed.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_settings') {
            $settings = [
                'app_notice' => sanitize($_POST['app_notice'] ?? ''),
                'support_email' => sanitize($_POST['support_email'] ?? ''),
                'highlight_course' => sanitize($_POST['highlight_course'] ?? ''),
                'latest_version' => sanitize($_POST['latest_version'] ?? ''),
                'maintenance_mode' => isset($_POST['maintenance_mode']) ? 'on' : 'off',
                'maintenance_title' => sanitize($_POST['maintenance_title'] ?? ''),
                'maintenance_message' => sanitize($_POST['maintenance_message'] ?? ''),
                'maintenance_eta' => sanitize($_POST['maintenance_eta'] ?? ''),
                'maintenance_break_time' => sanitize($_POST['maintenance_break_time'] ?? ''),
                'maintenance_details' => sanitize($_POST['maintenance_details'] ?? ''),
                'maintenance_status_note' => sanitize($_POST['maintenance_status_note'] ?? ''),
                'force_update' => isset($_POST['force_update']) ? '1' : '0',
                'update_url' => sanitize($_POST['update_url'] ?? ''),
            ];

            $stmt = $mysqli->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");

            $successCount = 0;
            foreach ($settings as $key => $value) {
                $stmt->bind_param('ss', $key, $value);
                if ($stmt->execute()) $successCount++;
            }
            $stmt->close();

            if ($successCount > 0) {
                $success = 'Settings saved successfully.';
            } else {
                $error = 'Failed to save settings.';
            }
        }
    }
}

$result = $mysqli->query("SELECT setting_key, setting_value FROM app_settings");
$settings = [];
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$defaults = [
    'app_notice' => 'Welcome to Shikhbo. Keep learning every day.',
    'support_email' => 'support@shikhbo.com',
    'maintenance_mode' => 'off',
    'maintenance_title' => 'We are improving Shikhbo',
    'maintenance_message' => 'The app is temporarily unavailable while we apply updates.',
    'maintenance_eta' => 'Back very soon',
    'maintenance_break_time' => '15-20 minutes',
    'maintenance_details' => 'Server upgrade, bug fixes, better performance.',
    'maintenance_status_note' => 'Live maintenance in progress',
    'latest_version' => '1.0.0',
    'highlight_course' => 'English for Beginners',
    'force_update' => '0',
    'update_url' => '',
];

foreach ($defaults as $key => $default) {
    if (!isset($settings[$key])) {
        $settings[$key] = $default;
    }
}
?>

<?php if (isset($error)): ?>
<div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg mb-4 flex items-center space-x-2">
    <i class="fa-solid fa-circle-exclamation"></i><span><?php echo sanitizeOutput($error); ?></span>
</div>
<?php endif; ?>
<?php if (isset($success)): ?>
<div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 px-4 py-3 rounded-lg mb-4 flex items-center space-x-2">
    <i class="fa-solid fa-circle-check"></i><span><?php echo sanitizeOutput($success); ?></span>
</div>
<?php endif; ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">App Control</h1>
    <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Manage app settings, maintenance mode, and updates</p>
</div>

<form method="POST" class="space-y-6">
    <?php echo getCSRFTokenField(); ?>
    <input type="hidden" name="action" value="update_settings">

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- App Notice -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
            <div class="flex items-center space-x-3 mb-4">
                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                    <i class="fa-solid fa-bullhorn text-blue-600 dark:text-blue-400"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-800 dark:text-gray-100">App Notice</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Displayed on app home screen</p>
                </div>
            </div>
            <textarea name="app_notice" rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg text-sm focus:ring-2 focus:ring-shikhbo-primary outline-none" placeholder="Enter notice message..."><?php echo sanitizeOutput($settings['app_notice'] ?? ''); ?></textarea>
        </div>

        <!-- Support & Highlights -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
            <div class="flex items-center space-x-3 mb-4">
                <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                    <i class="fa-solid fa-star text-purple-600 dark:text-purple-400"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-800 dark:text-gray-100">Support & Highlights</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Contact and featured course</p>
                </div>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Support Email</label>
                    <input type="email" name="support_email" value="<?php echo sanitizeOutput($settings['support_email'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg text-sm focus:ring-2 focus:ring-shikhbo-primary outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Highlight Course</label>
                    <input type="text" name="highlight_course" value="<?php echo sanitizeOutput($settings['highlight_course'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg text-sm focus:ring-2 focus:ring-shikhbo-primary outline-none">
                </div>
            </div>
        </div>
    </div>

    <!-- Maintenance Mode -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 <?php echo ($settings['maintenance_mode'] ?? 'off') === 'on' ? 'bg-red-100 dark:bg-red-900/30' : 'bg-gray-100 dark:bg-gray-700'; ?> rounded-lg flex items-center justify-center transition-colors">
                    <i class="fa-solid fa-wrench <?php echo ($settings['maintenance_mode'] ?? 'off') === 'on' ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400'; ?>"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-800 dark:text-gray-100">Maintenance Mode</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Block app access with custom message</p>
                </div>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="maintenance_mode" class="sr-only peer" <?php echo ($settings['maintenance_mode'] ?? 'off') === 'on' ? 'checked' : ''; ?>>
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-red-300 dark:peer-focus:ring-red-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-red-600"></div>
                <span class="ml-3 text-sm font-medium <?php echo ($settings['maintenance_mode'] ?? 'off') === 'on' ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400'; ?>"><?php echo ($settings['maintenance_mode'] ?? 'off') === 'on' ? 'Enabled' : 'Disabled'; ?></span>
            </label>
        </div>

        <div id="maintenanceSettings" class="<?php echo ($settings['maintenance_mode'] ?? 'off') === 'on' ? '' : 'hidden'; ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title</label>
                    <input type="text" name="maintenance_title" value="<?php echo sanitizeOutput($settings['maintenance_title'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg text-sm focus:ring-2 focus:ring-shikhbo-primary outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ETA</label>
                    <input type="text" name="maintenance_eta" value="<?php echo sanitizeOutput($settings['maintenance_eta'] ?? ''); ?>" placeholder="e.g., Back very soon" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg text-sm focus:ring-2 focus:ring-shikhbo-primary outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Break Time</label>
                    <input type="text" name="maintenance_break_time" value="<?php echo sanitizeOutput($settings['maintenance_break_time'] ?? ''); ?>" placeholder="e.g., 15-20 minutes" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg text-sm focus:ring-2 focus:ring-shikhbo-primary outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status Note</label>
                    <input type="text" name="maintenance_status_note" value="<?php echo sanitizeOutput($settings['maintenance_status_note'] ?? ''); ?>" placeholder="e.g., Live maintenance in progress" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg text-sm focus:ring-2 focus:ring-shikhbo-primary outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Message</label>
                    <textarea name="maintenance_message" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg text-sm focus:ring-2 focus:ring-shikhbo-primary outline-none"><?php echo sanitizeOutput($settings['maintenance_message'] ?? ''); ?></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Details</label>
                    <textarea name="maintenance_details" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg text-sm focus:ring-2 focus:ring-shikhbo-primary outline-none" placeholder="What's being updated..."><?php echo sanitizeOutput($settings['maintenance_details'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- App Update -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
        <div class="flex items-center space-x-3 mb-4">
            <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                <i class="fa-solid fa-cloud-arrow-down text-green-600 dark:text-green-400"></i>
            </div>
            <div>
                <h3 class="font-semibold text-gray-800 dark:text-gray-100">App Update</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Force users to update the app</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Latest Version</label>
                <input type="text" name="latest_version" value="<?php echo sanitizeOutput($settings['latest_version'] ?? '1.0.0'); ?>" placeholder="e.g., 1.2.0" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg text-sm focus:ring-2 focus:ring-shikhbo-primary outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Update URL</label>
                <input type="url" name="update_url" value="<?php echo sanitizeOutput($settings['update_url'] ?? ''); ?>" placeholder="https://play.google.com/..." class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg text-sm focus:ring-2 focus:ring-shikhbo-primary outline-none">
            </div>
            <div class="md:col-span-2">
                <label class="flex items-center space-x-3 cursor-pointer">
                    <input type="checkbox" name="force_update" class="sr-only peer" <?php echo ($settings['force_update'] ?? '0') === '1' ? 'checked' : ''; ?>>
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-green-600"></div>
                    <div>
                        <span class="text-sm font-medium text-gray-800 dark:text-gray-100">Force Update</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Users cannot use the app until they update</p>
                    </div>
                </label>
            </div>
        </div>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="px-6 py-2.5 bg-shikhbo-primary text-white rounded-lg font-medium hover:bg-indigo-700 transition-colors shadow-md shadow-indigo-200 dark:shadow-indigo-900/30 flex items-center space-x-2">
            <i class="fa-solid fa-floppy-disk"></i>
            <span>Save All Settings</span>
        </button>
    </div>
</form>

<script>
const toggle = document.querySelector('input[name="maintenance_mode"]');
const settings = document.getElementById('maintenanceSettings');
const statusLabel = toggle.closest('label').nextElementSibling;

toggle.addEventListener('change', function() {
    if (this.checked) {
        settings.classList.remove('hidden');
        this.closest('label').querySelector('div').classList.add('bg-red-600');
        this.closest('label').querySelector('div').classList.remove('bg-gray-200');
        statusLabel.textContent = 'Enabled';
        statusLabel.classList.remove('text-gray-500');
        statusLabel.classList.add('text-red-600');
    } else {
        settings.classList.add('hidden');
        this.closest('label').querySelector('div').classList.remove('bg-red-600');
        this.closest('label').querySelector('div').classList.add('bg-gray-200');
        statusLabel.textContent = 'Disabled';
        statusLabel.classList.add('text-gray-500');
        statusLabel.classList.remove('text-red-600');
    }
});
</script>