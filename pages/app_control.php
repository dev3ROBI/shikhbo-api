<?php
$mysqli = getDBConnection();

$result = $mysqli->query("SELECT setting_key, setting_value FROM app_settings");
$settings = [];
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$defaults = [
    'app_notice' => 'Welcome to Shikhbo. Keep learning every day.',
    'support_email' => 'support@shikhbo.com',
    'highlight_course' => 'English for Beginners',
    'maintenance_mode' => 'off',
    'maintenance_title' => 'We are improving Shikhbo',
    'maintenance_message' => 'The app is temporarily unavailable while we apply updates.',
    'maintenance_eta' => 'Back very soon',
    'maintenance_break_time' => '15-20 minutes',
    'maintenance_details' => 'Server upgrade, bug fixes, better performance.',
    'maintenance_status_note' => 'Live maintenance in progress',
    'latest_version' => '1.0.0',
    'force_update' => '0',
    'update_url' => ''
];

foreach ($defaults as $key => $default) {
    if (!isset($settings[$key])) {
        $settings[$key] = $default;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token validation failed.';
    } else {
        $settings_to_save = [
            'app_notice', 'support_email', 'highlight_course', 'latest_version',
            'maintenance_title', 'maintenance_message', 'maintenance_eta',
            'maintenance_break_time', 'maintenance_details', 'maintenance_status_note', 'update_url'
        ];
        
        $stmt = $mysqli->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        
        foreach ($settings_to_save as $key) {
            $value = sanitize($_POST[$key] ?? '');
            $stmt->bind_param('ss', $key, $value);
            $stmt->execute();
        }
        $stmt->close();
        
        $maintenanceMode = isset($_POST['maintenance_mode']) ? 'on' : 'off';
        $stmt2 = $mysqli->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES ('maintenance_mode', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt2->bind_param('s', $maintenanceMode);
        $stmt2->execute();
        $stmt2->close();
        
        $forceUpdate = isset($_POST['force_update']) ? '1' : '0';
        $stmt3 = $mysqli->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES ('force_update', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt3->bind_param('s', $forceUpdate);
        $stmt3->execute();
        $stmt3->close();
        
        $success = 'Settings saved successfully.';
        
        $result = $mysqli->query("SELECT setting_key, setting_value FROM app_settings");
        $settings = [];
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        foreach ($defaults as $key => $default) {
            if (!isset($settings[$key])) $settings[$key] = $default;
        }
    }
}
?>

<div class="page-content">
    <div class="mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 dark:text-gray-100">App Control</h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1">Manage app settings, maintenance mode, and updates</p>
    </div>

    <form method="POST" class="space-y-6">
        <?php echo getCSRFTokenField(); ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- App Notice -->
            <div class="card-hover bg-white dark:bg-gray-800 rounded-2xl shadow-md p-6 border border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                        <i class="fa-solid fa-bullhorn text-blue-600 dark:text-blue-400"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800 dark:text-gray-100">App Notice</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Displayed on app home screen</p>
                    </div>
                </div>
                <textarea name="app_notice" rows="3" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced"><?php echo sanitizeOutput($settings['app_notice'] ?? ''); ?></textarea>
            </div>

            <!-- Support & Highlights -->
            <div class="card-hover bg-white dark:bg-gray-800 rounded-2xl shadow-md p-6 border border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                        <i class="fa-solid fa-star text-purple-600 dark:text-purple-400"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800 dark:text-gray-100">Support & Highlights</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Contact and featured course</p>
                    </div>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Support Email</label>
                        <input type="email" name="support_email" value="<?php echo sanitizeOutput($settings['support_email'] ?? ''); ?>" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Highlight Course</label>
                        <input type="text" name="highlight_course" value="<?php echo sanitizeOutput($settings['highlight_course'] ?? ''); ?>" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced">
                    </div>
                </div>
            </div>
        </div>

        <!-- Maintenance Mode -->
        <div class="card-hover bg-white dark:bg-gray-800 rounded-2xl shadow-md p-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                        <i class="fa-solid fa-wrench text-red-600 dark:text-red-400"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800 dark:text-gray-100">Maintenance Mode</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Block app access with custom message</p>
                    </div>
                </div>
                <label class="relative cursor-pointer">
                    <input type="checkbox" name="maintenance_mode" id="maintenanceToggle" class="sr-only peer" <?php echo ($settings['maintenance_mode'] ?? 'off') === 'on' ? 'checked' : ''; ?>>
                    <div class="w-14 h-7 bg-gray-300 dark:bg-gray-600 rounded-full peer-checked:bg-red-500 transition-colors duration-300"></div>
                    <div class="absolute left-0.5 top-0.5 w-6 h-6 bg-white rounded-full shadow-md transition-transform duration-300 peer-checked:translate-x-7"></div>
                </label>
            </div>

            <div id="maintenanceSettings" class="<?php echo ($settings['maintenance_mode'] ?? 'off') === 'on' ? '' : 'hidden'; ?>">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Title</label>
                        <input type="text" name="maintenance_title" value="<?php echo sanitizeOutput($settings['maintenance_title'] ?? ''); ?>" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ETA</label>
                        <input type="text" name="maintenance_eta" value="<?php echo sanitizeOutput($settings['maintenance_eta'] ?? ''); ?>" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Break Time</label>
                        <input type="text" name="maintenance_break_time" value="<?php echo sanitizeOutput($settings['maintenance_break_time'] ?? ''); ?>" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Status Note</label>
                        <input type="text" name="maintenance_status_note" value="<?php echo sanitizeOutput($settings['maintenance_status_note'] ?? ''); ?>" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Message</label>
                        <textarea name="maintenance_message" rows="2" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced"><?php echo sanitizeOutput($settings['maintenance_message'] ?? ''); ?></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Details</label>
                        <textarea name="maintenance_details" rows="2" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced"><?php echo sanitizeOutput($settings['maintenance_details'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- App Update -->
        <div class="card-hover bg-white dark:bg-gray-800 rounded-2xl shadow-md p-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <i class="fa-solid fa-cloud-arrow-down text-green-600 dark:text-green-400"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-800 dark:text-gray-100">App Update</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Force users to update the app</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Latest Version</label>
                    <input type="text" name="latest_version" value="<?php echo sanitizeOutput($settings['latest_version'] ?? '1.0.0'); ?>" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Update URL</label>
                    <input type="url" name="update_url" value="<?php echo sanitizeOutput($settings['update_url'] ?? ''); ?>" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced">
                </div>
                <div class="md:col-span-2">
                    <label class="flex items-center justify-between cursor-pointer p-4 bg-gray-50 dark:bg-gray-700 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                        <div>
                            <span class="text-sm font-semibold text-gray-800 dark:text-gray-100">Force Update</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Users cannot use the app until they update</p>
                        </div>
                        <div class="relative">
                            <input type="checkbox" name="force_update" id="forceUpdateToggle" class="sr-only peer" <?php echo ($settings['force_update'] ?? '0') === '1' ? 'checked' : ''; ?>>
                            <div class="w-14 h-7 bg-gray-300 dark:bg-gray-600 rounded-full peer-checked:bg-green-500 transition-colors duration-300"></div>
                            <div class="absolute left-0.5 top-0.5 w-6 h-6 bg-white rounded-full shadow-md transition-transform duration-300 peer-checked:translate-x-7"></div>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-6 py-3 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700 transition-colors flex items-center gap-2 shadow-lg shadow-indigo-200 dark:shadow-indigo-900/30">
                <i class="fa-solid fa-floppy-disk"></i>Save All Settings
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('maintenanceToggle');
    const settings = document.getElementById('maintenanceSettings');
    
    if (toggle && settings) {
        toggle.addEventListener('change', function() {
            settings.classList.toggle('hidden', !this.checked);
        });
    }
});
</script>