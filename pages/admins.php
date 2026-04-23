<?php
$mysqli = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token validation failed.';
    } elseif ($_POST['action'] === 'add_admin') {
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        
        $stmt = $mysqli->prepare("INSERT INTO users (name, email, password, role, status, referral_code) VALUES (?, ?, ?, 'admin', 'active', ?)");
        $referral = 'ADMIN' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $stmt->bind_param('ssss', $name, $email, $password, $referral);
        
        if ($stmt->execute()) {
            $success = "Admin added successfully.";
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

$admins = $mysqli->query("SELECT id, name, email, status, last_login, created_at FROM users WHERE role = 'admin' ORDER BY created_at DESC");
?>

<?php if (isset($error)): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4"><?php echo sanitizeOutput($error); ?></div>
<?php endif; ?>
<?php if (isset($success)): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4"><?php echo sanitizeOutput($success); ?></div>
<?php endif; ?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Admin Management</h1>
        <p class="text-gray-500 mt-1">Manage system administrators</p>
    </div>
    <button onclick="document.getElementById('addAdminModal').classList.remove('hidden')"
            class="px-4 py-2 bg-shikhbo-primary text-white rounded-lg text-sm hover:bg-indigo-700">
        <i class="fa-solid fa-plus mr-2"></i>Add Admin
    </button>
</div>

<div class="bg-white rounded-xl shadow-md overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Login</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php while ($admin = $admins->fetch_assoc()): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center space-x-3">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($admin['name']); ?>&background=4F46E5&color=fff&size=32" 
                                 class="w-8 h-8 rounded-full">
                            <span class="text-sm font-medium text-gray-800"><?php echo sanitizeOutput($admin['name']); ?></span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo sanitizeOutput($admin['email']); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $admin['status'] === 'active' ? 'text-green-800 bg-green-100' : 'text-red-800 bg-red-100'; ?>">
                            <?php echo ucfirst($admin['status']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $admin['last_login'] ? date('d M, Y H:i', strtotime($admin['last_login'])) : 'Never'; ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('d M, Y', strtotime($admin['created_at'])); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Add Admin Modal -->
<div id="addAdminModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
    <div class="absolute inset-0 bg-black bg-opacity-50" onclick="document.getElementById('addAdminModal').classList.add('hidden')"></div>
    <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md p-6 mx-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Add New Admin</h3>
            <button onclick="document.getElementById('addAdminModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>
        <form method="POST" class="space-y-4">
            <?php echo getCSRFTokenField(); ?>
            <input type="hidden" name="action" value="add_admin">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-shikhbo-primary focus:border-transparent outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-shikhbo-primary focus:border-transparent outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" name="password" required minlength="8" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-shikhbo-primary focus:border-transparent outline-none">
            </div>
            <button type="submit" class="w-full bg-shikhbo-primary text-white py-2 rounded-lg font-medium hover:bg-indigo-700 transition-colors">
                Create Admin
            </button>
        </form>
    </div>
</div>