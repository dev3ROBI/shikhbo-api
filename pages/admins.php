<?php
$mysqli = getDBConnection();

// Determine super admin (by email)
$superEmail = 'admin@shikhbo.com';

// Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'CSRF validation failed.';
    } else {
        $action = $_POST['action'] ?? '';
        $adminId = intval($_POST['admin_id'] ?? 0);

        if ($action === 'add_admin') {
            $name = sanitize($_POST['name']);
            $email = sanitize($_POST['email']);
            $password = password_hash($_POST['password'], PASSWORD_BCRYPT, ['cost' => 12]);
            $referral = 'ADMIN' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

            $stmt = $mysqli->prepare("INSERT INTO users (name, email, password, role, status, referral_code) VALUES (?, ?, ?, 'admin', 'active', ?)");
            $stmt->bind_param('ssss', $name, $email, $password, $referral);
            if ($stmt->execute()) {
                $success = "Admin added successfully.";
            } else {
                $error = "Error: " . $stmt->error;
            }
            $stmt->close();

        } elseif ($action === 'edit_admin' && $adminId > 0) {
            // Prevent editing super admin
            $check = $mysqli->query("SELECT email FROM users WHERE id = $adminId")->fetch_assoc();
            if ($check && $check['email'] === $superEmail) {
                $error = "Super Admin cannot be edited.";
            } else {
                $name = sanitize($_POST['name']);
                $email = sanitize($_POST['email']);
                $status = sanitize($_POST['status']);
                $stmt = $mysqli->prepare("UPDATE users SET name=?, email=?, status=? WHERE id=? AND role='admin'");
                $stmt->bind_param('sssi', $name, $email, $status, $adminId);
                if ($stmt->execute()) {
                    $success = "Admin updated.";
                } else {
                    $error = "Error: " . $stmt->error;
                }
                $stmt->close();
            }

        } elseif ($action === 'delete_admin' && $adminId > 0) {
            $check = $mysqli->query("SELECT email FROM users WHERE id = $adminId")->fetch_assoc();
            if ($check && $check['email'] === $superEmail) {
                $error = "Super Admin cannot be deleted.";
            } else {
                $stmt = $mysqli->prepare("DELETE FROM users WHERE id=? AND role='admin'");
                $stmt->bind_param('i', $adminId);
                if ($stmt->execute()) {
                    $success = "Admin deleted.";
                } else {
                    $error = "Error: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

$admins = $mysqli->query("SELECT id, name, email, status, last_login, created_at FROM users WHERE role='admin' ORDER BY created_at DESC");
?>

<?php if (isset($error)): ?>
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg mb-4 text-sm"><?php echo sanitizeOutput($error); ?></div>
<?php endif; ?>
<?php if (isset($success)): ?>
    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 px-4 py-3 rounded-lg mb-4 text-sm"><?php echo sanitizeOutput($success); ?></div>
<?php endif; ?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Admin Management</h1>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Manage system administrators</p>
    </div>
    <button onclick="openAddAdminModal()" class="px-4 py-2 bg-shikhbo-primary text-white rounded-lg text-sm hover:bg-indigo-700 transition-colors shadow-sm flex-shrink-0">
        <i class="fa-solid fa-plus mr-1.5"></i>Add Admin
    </button>
</div>

<!-- Admin Cards Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php while ($admin = $admins->fetch_assoc()): 
        $isSuper = ($admin['email'] === $superEmail);
        ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 flex flex-col">
            <div class="flex items-start justify-between mb-3">
                <div class="flex items-center space-x-3">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($admin['name']); ?>&background=4F46E5&color=fff&size=44" class="w-11 h-11 rounded-full border-2 border-white dark:border-gray-800 shadow">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100"><?php echo sanitizeOutput($admin['name']); ?></h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[180px]"><?php echo sanitizeOutput($admin['email']); ?></p>
                    </div>
                </div>
                <span class="px-2 py-0.5 text-xs font-medium rounded-full <?php echo $admin['status']==='active' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300'; ?>">
                    <?php echo ucfirst($admin['status']); ?>
                </span>
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1 mb-3 flex-1">
                <p><i class="fa-solid fa-clock mr-1"></i>Last login: <?php echo $admin['last_login'] ? date('M j, H:i', strtotime($admin['last_login'])) : 'Never'; ?></p>
                <p><i class="fa-solid fa-calendar mr-1"></i>Created: <?php echo date('M j, Y', strtotime($admin['created_at'])); ?></p>
            </div>
            <div class="flex items-center gap-2 mt-auto pt-2 border-t border-gray-100 dark:border-gray-700">
                <?php if ($isSuper): ?>
                    <span class="text-xs text-gray-400 dark:text-gray-500 italic flex items-center gap-1"><i class="fa-solid fa-shield-halved"></i> Super Admin</span>
                <?php else: ?>
                    <button onclick="editAdmin(<?php echo htmlspecialchars(json_encode($admin), ENT_QUOTES, 'UTF-8'); ?>)" class="text-xs text-shikhbo-primary hover:underline"><i class="fa-solid fa-pen-to-square mr-1"></i>Edit</button>
                    <button onclick="deleteAdmin(<?php echo $admin['id']; ?>)" class="text-xs text-red-600 hover:underline"><i class="fa-solid fa-trash mr-1"></i>Delete</button>
                <?php endif; ?>
            </div>
        </div>
    <?php endwhile; ?>
    <?php if ($admins->num_rows == 0): ?>
        <div class="col-span-full text-center py-12 text-gray-500 dark:text-gray-400">
            <i class="fa-solid fa-user-slash text-3xl mb-2 block"></i>
            <p>No admins found.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Add Admin Modal -->
<div id="addAdminModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
    <div class="absolute inset-0 bg-black bg-opacity-50" onclick="closeAddModal()"></div>
    <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Add New Admin</h3>
            <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"><i class="fa-solid fa-xmark text-xl"></i></button>
        </div>
        <form method="POST" class="space-y-4">
            <?php echo getCSRFTokenField(); ?>
            <input type="hidden" name="action" value="add_admin">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg text-sm focus:ring-2 focus:ring-shikhbo-primary outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                <input type="email" name="email" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg text-sm focus:ring-2 focus:ring-shikhbo-primary outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password</label>
                <input type="password" name="password" required minlength="8" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg text-sm focus:ring-2 focus:ring-shikhbo-primary outline-none">
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeAddModal()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-700 dark:text-gray-300">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-shikhbo-primary text-white rounded-lg text-sm">Create Admin</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Admin Modal -->
<div id="editAdminModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
    <div class="absolute inset-0 bg-black bg-opacity-50" onclick="closeEditModal()"></div>
    <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Edit Admin</h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"><i class="fa-solid fa-xmark text-xl"></i></button>
        </div>
        <form method="POST" class="space-y-4">
            <?php echo getCSRFTokenField(); ?>
            <input type="hidden" name="action" value="edit_admin">
            <input type="hidden" name="admin_id" id="edit-id">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                <input type="text" name="name" id="edit-name" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg text-sm focus:ring-2 focus:ring-shikhbo-primary outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                <input type="email" name="email" id="edit-email" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg text-sm focus:ring-2 focus:ring-shikhbo-primary outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                <select name="status" id="edit-status" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg text-sm">
                    <option value="active">Active</option>
                    <option value="suspended">Suspended</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-700 dark:text-gray-300">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-shikhbo-primary text-white rounded-lg text-sm">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<form id="delete-admin-form" method="POST" style="display:none;">
    <?php echo getCSRFTokenField(); ?>
    <input type="hidden" name="action" value="delete_admin">
    <input type="hidden" name="admin_id" id="delete-id">
</form>

<script>
function openAddAdminModal() { document.getElementById('addAdminModal').classList.remove('hidden'); }
function closeAddModal() { document.getElementById('addAdminModal').classList.add('hidden'); }
function editAdmin(admin) {
    document.getElementById('edit-id').value = admin.id;
    document.getElementById('edit-name').value = admin.name;
    document.getElementById('edit-email').value = admin.email;
    document.getElementById('edit-status').value = admin.status;
    document.getElementById('editAdminModal').classList.remove('hidden');
}
function closeEditModal() { document.getElementById('editAdminModal').classList.add('hidden'); }
function deleteAdmin(id) {
    if (confirm('Delete this admin?')) {
        document.getElementById('delete-id').value = id;
        document.getElementById('delete-admin-form').submit();
    }
}
</script>