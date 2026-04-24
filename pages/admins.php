<?php
$mysqli = getDBConnection();

$superEmail = 'admin@shikhbo.com';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) { $error = 'CSRF validation failed.'; }
    else {
        $action = $_POST['action'] ?? '';
        $adminId = intval($_POST['admin_id'] ?? 0);

        if ($action === 'add_admin') {
            $name = sanitize($_POST['name']);
            $email = sanitize($_POST['email']);
            $password = password_hash($_POST['password'], PASSWORD_BCRYPT, ['cost' => 12]);
            $referral = 'ADMIN' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            $stmt = $mysqli->prepare("INSERT INTO users (name, email, password, role, status, referral_code) VALUES (?, ?, ?, 'admin', 'active', ?)");
            $stmt->bind_param('ssss', $name, $email, $password, $referral);
            if ($stmt->execute()) { $success = "Admin added successfully."; } else { $error = "Error: " . $stmt->error; }
            $stmt->close();
        } elseif ($action === 'edit_admin' && $adminId > 0) {
            $check = $mysqli->query("SELECT email FROM users WHERE id = $adminId")->fetch_assoc();
            if ($check && $check['email'] === $superEmail) { $error = "Super Admin cannot be edited."; }
            else {
                $name = sanitize($_POST['name']); $email = sanitize($_POST['email']); $status = sanitize($_POST['status']);
                $stmt = $mysqli->prepare("UPDATE users SET name=?, email=?, status=? WHERE id=? AND role='admin'");
                $stmt->bind_param('sssi', $name, $email, $status, $adminId);
                if ($stmt->execute()) { $success = "Admin updated successfully."; } else { $error = "Error: " . $stmt->error; }
                $stmt->close();
            }
        } elseif ($action === 'delete_admin' && $adminId > 0) {
            $check = $mysqli->query("SELECT email FROM users WHERE id = $adminId")->fetch_assoc();
            if ($check && $check['email'] === $superEmail) { $error = "Super Admin cannot be deleted."; }
            else {
                $stmt = $mysqli->prepare("DELETE FROM users WHERE id=? AND role='admin'");
                $stmt->bind_param('i', $adminId);
                if ($stmt->execute()) { $success = "Admin deleted successfully."; } else { $error = "Error: " . $stmt->error; }
                $stmt->close();
            }
        }
    }
}

$admins = $mysqli->query("SELECT id, name, email, status, last_login, created_at FROM users WHERE role='admin' ORDER BY created_at DESC");
?>

<div class="page-content">
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

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 dark:text-gray-100">Admins</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1"><?php echo $admins->num_rows; ?> administrators</p>
        </div>
        <button onclick="openAddAdminModal()" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700 transition-colors flex items-center gap-2 shadow-lg shadow-indigo-200 dark:shadow-indigo-900/30">
            <i class="fa-solid fa-plus"></i>Add Admin
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <?php while ($admin = $admins->fetch_assoc()): 
            $isSuper = ($admin['email'] === $superEmail);
        ?>
        <div class="card-hover bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-gray-100 dark:border-gray-700 p-6">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center gap-4">
                    <div class="relative">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($admin['name']); ?>&background=4F46E5&color=fff&size=48&bold=true" class="w-12 h-12 rounded-xl">
                        <?php if ($isSuper): ?>
                        <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-yellow-500 rounded-full flex items-center justify-center">
                            <i class="fa-solid fa-shield-halved text-white text-xs"></i>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800 dark:text-gray-100"><?php echo sanitizeOutput($admin['name']); ?></h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400"><?php echo sanitizeOutput($admin['email']); ?></p>
                    </div>
                </div>
                <span class="badge <?php echo $admin['status']==='active' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'; ?>">
                    <?php echo ucfirst($admin['status']); ?>
                </span>
            </div>
            <div class="space-y-2 text-sm text-gray-500 dark:text-gray-400 mb-4">
                <div class="flex items-center gap-2">
                    <i class="fa-regular fa-clock w-5"></i>
                    <span>Last login: <?php echo $admin['last_login'] ? date('M j, H:i', strtotime($admin['last_login'])) : 'Never'; ?></span>
                </div>
                <div class="flex items-center gap-2">
                    <i class="fa-regular fa-calendar w-5"></i>
                    <span>Created: <?php echo date('M j, Y', strtotime($admin['created_at'])); ?></span>
                </div>
            </div>
            <div class="flex items-center gap-2 pt-4 border-t border-gray-100 dark:border-gray-700">
                <?php if ($isSuper): ?>
                    <span class="text-xs text-yellow-600 dark:text-yellow-400 font-medium flex items-center gap-1">
                        <i class="fa-solid fa-shield-halved"></i>Super Admin
                    </span>
                <?php else: ?>
                    <button onclick="editAdmin(<?php echo htmlspecialchars(json_encode($admin), ENT_QUOTES, 'UTF-8'); ?>)" class="flex-1 px-3 py-2 text-sm text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors flex items-center justify-center gap-1">
                        <i class="fa-solid fa-pen-to-square"></i>Edit
                    </button>
                    <button onclick="deleteAdmin(<?php echo $admin['id']; ?>)" class="flex-1 px-3 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors flex items-center justify-center gap-1">
                        <i class="fa-solid fa-trash"></i>Delete
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endwhile; ?>
        <?php if ($admins->num_rows == 0): ?>
        <div class="col-span-full text-center py-16 bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-gray-100 dark:border-gray-700">
            <i class="fa-solid fa-user-slash text-4xl text-gray-300 dark:text-gray-600 mb-3 block"></i>
            <p class="text-gray-500 dark:text-gray-400">No admins found</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Admin Modal -->
<div id="addAdminModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 modal-backdrop" onclick="closeAddModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4 pointer-events-none">
        <div class="modal-content bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md pointer-events-auto">
            <div class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Add New Admin</h3>
                <button onclick="closeAddModal()" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <?php echo getCSRFTokenField(); ?>
                <input type="hidden" name="action" value="add_admin">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Name</label>
                    <input type="text" name="name" required class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
                    <input type="email" name="email" required class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Password</label>
                    <input type="password" name="password" required minlength="8" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced">
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="closeAddModal()" class="px-5 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700 transition-colors">Create Admin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Admin Modal -->
<div id="editAdminModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 modal-backdrop" onclick="closeEditModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4 pointer-events-none">
        <div class="modal-content bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md pointer-events-auto">
            <div class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Edit Admin</h3>
                <button onclick="closeEditModal()" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <?php echo getCSRFTokenField(); ?>
                <input type="hidden" name="action" value="edit_admin">
                <input type="hidden" name="admin_id" id="edit-id">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Name</label>
                    <input type="text" name="name" id="edit-name" required class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
                    <input type="email" name="email" id="edit-email" required class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Status</label>
                    <select name="status" id="edit-status" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="closeEditModal()" class="px-5 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700 transition-colors">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form id="delete-admin-form" method="POST" class="hidden"><?php echo getCSRFTokenField(); ?><input type="hidden" name="action" value="delete_admin"><input type="hidden" name="admin_id" id="delete-id"></form>

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
    confirmAction('Delete this admin?', () => {
        document.getElementById('delete-id').value = id;
        document.getElementById('delete-admin-form').submit();
    });
}
</script>