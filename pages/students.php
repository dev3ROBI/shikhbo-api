<?php
$mysqli = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token validation failed.';
    } else {
        $action = $_POST['action'] ?? '';
        $studentId = intval($_POST['student_id'] ?? 0);

        if ($action === 'toggle_status') {
            $newStatus = $_POST['new_status'] === 'active' ? 'active' : 'suspended';
            $stmt = $mysqli->prepare("UPDATE users SET status=? WHERE id=? AND (role IS NULL OR role='' OR role='user')");
            $stmt->bind_param('si', $newStatus, $studentId);
            $stmt->execute(); $stmt->close();
            $success = "Status updated successfully.";
        } elseif ($action === 'delete') {
            $stmt = $mysqli->prepare("DELETE FROM users WHERE id=? AND (role IS NULL OR role='' OR role='user')");
            $stmt->bind_param('i', $studentId);
            $stmt->execute(); $stmt->close();
            $success = "Student deleted successfully.";
        } elseif ($action === 'edit_student') {
            $name = sanitize($_POST['name']);
            $email = sanitize($_POST['email']);
            $status = sanitize($_POST['status']);
            $stmt = $mysqli->prepare("UPDATE users SET name=?, email=?, status=? WHERE id=? AND (role IS NULL OR role='' OR role='user')");
            $stmt->bind_param('sssi', $name, $email, $status, $studentId);
            $stmt->execute(); $stmt->close();
            $success = "Student updated successfully.";
        }
    }
}

$search = sanitize($_GET['search'] ?? '');
$statusFilter = sanitize($_GET['status'] ?? '');
$page_num = max(1, intval($_GET['p'] ?? 1));
$limit = 15;
$offset = ($page_num - 1) * $limit;

$where = "WHERE (role IS NULL OR role = '' OR role = 'user')";
$params = []; $types = '';

if ($search) {
    $where .= " AND (name LIKE ? OR email LIKE ?)";
    $sp = "%{$search}%";
    $params = [$sp, $sp]; $types = 'ss';
}
if ($statusFilter) {
    $where .= " AND status = ?";
    $params[] = $statusFilter; $types .= 's';
}

$countStmt = $mysqli->prepare("SELECT COUNT(*) as total FROM users {$where}");
if ($params) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalStudents = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();
$totalPages = ceil($totalStudents / $limit);

$query = "SELECT id, name, email, google_login, status, referral_code, device_model, ip_address, last_login, created_at FROM users {$where} ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $mysqli->prepare($query);
if ($params) {
    $stmt->bind_param($types . 'ii', ...[...$params, $limit, $offset]);
} else {
    $stmt->bind_param('ii', $limit, $offset);
}
$stmt->execute();
$students = $stmt->get_result();
$stmt->close();
?>

<div class="page-content">
    <?php if (isset($error)): ?>
    <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl flex items-center gap-3 alert-auto-dismiss">
        <i class="fa-solid fa-circle-exclamation text-red-500"></i>
        <span class="text-red-700 dark:text-red-300"><?php echo sanitizeOutput($error); ?></span>
    </div>
    <?php endif; ?>
    <?php if (isset($success)): ?>
    <div class="mb-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl flex items-center gap-3 alert-auto-dismiss">
        <i class="fa-solid fa-circle-check text-green-500"></i>
        <span class="text-green-700 dark:text-green-300"><?php echo sanitizeOutput($success); ?></span>
    </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 dark:text-gray-100">Students</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1"><?php echo number_format($totalStudents); ?> students total</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-3 py-1.5 text-xs font-medium rounded-lg bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                <i class="fa-solid fa-check mr-1"></i><?php echo $mysqli->query("SELECT COUNT(*) as c FROM users WHERE status='active' AND (role IS NULL OR role='' OR role='user')")->fetch_assoc()['c']; ?> Active
            </span>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-4 mb-6 border border-gray-100 dark:border-gray-700">
        <form method="GET" action="index.php" class="flex flex-col sm:flex-row gap-3">
            <input type="hidden" name="page" value="students">
            <div class="relative flex-1">
                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" name="search" value="<?php echo sanitizeOutput($search); ?>" placeholder="Search by name or email..." class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced">
            </div>
            <select name="status" class="px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                <option value="">All Status</option>
                <option value="active" <?php echo $statusFilter==='active'?'selected':''; ?>>Active</option>
                <option value="suspended" <?php echo $statusFilter==='suspended'?'selected':''; ?>>Suspended</option>
                <option value="inactive" <?php echo $statusFilter==='inactive'?'selected':''; ?>>Inactive</option>
            </select>
            <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700 transition-colors flex items-center gap-2">
                <i class="fa-solid fa-filter"></i>Filter
            </button>
            <?php if ($search || $statusFilter): ?>
            <a href="index.php?page=students" class="px-5 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center gap-2">
                <i class="fa-solid fa-xmark"></i>Clear
            </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Students Table -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md overflow-hidden border border-gray-100 dark:border-gray-700">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="table-header">
                    <tr>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Student</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider hide-mobile">Auth</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider hide-mobile">Status</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider hide-mobile">Last Login</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                    <?php if ($students->num_rows > 0): ?>
                        <?php while ($stu = $students->fetch_assoc()): ?>
                            <tr class="table-row">
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-3">
                                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($stu['name']); ?>&background=4F46E5&color=fff&size=44&bold=true" class="w-11 h-11 rounded-xl">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100"><?php echo sanitizeOutput($stu['name']); ?></p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo sanitizeOutput($stu['email']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 hide-mobile">
                                    <?php if ($stu['google_login']): ?>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                            <i class="fa-brands fa-google mr-1"></i>Google
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                            <i class="fa-solid fa-envelope mr-1"></i>Email
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-4 hide-mobile">
                                    <span class="badge <?php echo $stu['status']==='active'?'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400':($stu['status']==='suspended'?'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400':'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'); ?>">
                                        <?php echo ucfirst($stu['status']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4 hide-mobile">
                                    <span class="text-sm text-gray-500 dark:text-gray-400"><?php echo $stu['last_login'] ? date('M j, H:i', strtotime($stu['last_login'])) : '<span class="text-gray-400">Never</span>'; ?></span>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-1">
                                        <button onclick="viewStudent(<?php echo htmlspecialchars(json_encode($stu), ENT_QUOTES, 'UTF-8'); ?>)" class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors" title="View">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                        <button onclick="editStudent(<?php echo htmlspecialchars(json_encode($stu), ENT_QUOTES, 'UTF-8'); ?>)" class="p-2 text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors" title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <button onclick="toggleStudentStatus(<?php echo $stu['id']; ?>,'<?php echo $stu['status']==='active'?'suspended':'active'; ?>')" class="p-2 text-yellow-600 hover:bg-yellow-50 dark:hover:bg-yellow-900/30 rounded-lg transition-colors" title="<?php echo $stu['status']==='active'?'Suspend':'Activate'; ?>">
                                            <i class="fa-solid fa-<?php echo $stu['status']==='active'?'ban':'check'; ?>"></i>
                                        </button>
                                        <button onclick="deleteStudent(<?php echo $stu['id']; ?>)" class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors" title="Delete">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center">
                                <i class="fa-solid fa-users-slash text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                                <p class="text-gray-500 dark:text-gray-400 text-lg">No students found</p>
                                <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Try adjusting your search or filter</p>
                            </div>
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPages > 1): ?>
        <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <p class="text-sm text-gray-500 dark:text-gray-400">Page <?php echo $page_num; ?> of <?php echo $totalPages; ?></p>
            <div class="flex items-center gap-1">
                <?php for ($i = 1; $i <= min($totalPages, 5); $i++): ?>
                    <a href="index.php?page=students&p=<?php echo $i; ?><?php echo $search?'&search='.urlencode($search):''; ?><?php echo $statusFilter?'&status='.$statusFilter:''; ?>" 
                       class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-medium transition-colors <?php echo $i===$page_num?'bg-indigo-600 text-white':'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                <?php if ($totalPages > 5): ?>
                    <span class="px-2 text-gray-400">...</span>
                    <a href="index.php?page=students&p=<?php echo $totalPages; ?>" class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <?php echo $totalPages; ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- View Modal -->
<div id="viewStudentModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 modal-backdrop" onclick="closeViewModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4 pointer-events-none">
        <div class="modal-content bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg pointer-events-auto">
            <div class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Student Details</h3>
                <button onclick="closeViewModal()" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            <div id="viewStudentContent" class="p-6"></div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editStudentModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 modal-backdrop" onclick="closeEditModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4 pointer-events-none">
        <div class="modal-content bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg pointer-events-auto">
            <div class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Edit Student</h3>
                <button onclick="closeEditModal()" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <?php echo getCSRFTokenField(); ?>
                <input type="hidden" name="action" value="edit_student">
                <input type="hidden" name="student_id" id="editStudentId">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Name</label>
                    <input type="text" name="name" id="editStudentName" required class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
                    <input type="email" name="email" id="editStudentEmail" required class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Status</label>
                    <select name="status" id="editStudentStatus" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
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

<!-- Hidden Action Form -->
<form id="actionForm" method="POST" class="hidden">
    <?php echo getCSRFTokenField(); ?>
    <input type="hidden" name="student_id" id="actionStudentId">
    <input type="hidden" name="action" id="actionType">
    <input type="hidden" name="new_status" id="actionStatus">
</form>

<script>
function viewStudent(stu) {
    document.getElementById('viewStudentContent').innerHTML = `
        <div class="flex items-center gap-4 mb-6">
            <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(stu.name)}&background=4F46E5&color=fff&size=64&bold=true" class="w-16 h-16 rounded-2xl">
            <div>
                <p class="text-lg font-semibold text-gray-800 dark:text-gray-100">${stu.name}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">#${stu.id}</p>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Email</p>
                <p class="text-sm font-medium text-gray-800 dark:text-gray-100">${stu.email}</p>
            </div>
            <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Status</p>
                <p class="text-sm font-medium text-gray-800 dark:text-gray-100 capitalize">${stu.status}</p>
            </div>
            <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Auth Method</p>
                <p class="text-sm font-medium text-gray-800 dark:text-gray-100">${stu.google_login == 1 ? 'Google' : 'Email'}</p>
            </div>
            <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Referral Code</p>
                <p class="text-sm font-medium text-gray-800 dark:text-gray-100">${stu.referral_code || '—'}</p>
            </div>
            <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Device</p>
                <p class="text-sm font-medium text-gray-800 dark:text-gray-100">${stu.device_model || '—'}</p>
            </div>
            <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">IP Address</p>
                <p class="text-sm font-medium text-gray-800 dark:text-gray-100">${stu.ip_address || '—'}</p>
            </div>
            <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Last Login</p>
                <p class="text-sm font-medium text-gray-800 dark:text-gray-100">${stu.last_login || 'Never'}</p>
            </div>
            <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Joined</p>
                <p class="text-sm font-medium text-gray-800 dark:text-gray-100">${stu.created_at}</p>
            </div>
        </div>
    `;
    document.getElementById('viewStudentModal').classList.remove('hidden');
}

function closeViewModal() {
    document.getElementById('viewStudentModal').classList.add('hidden');
}

function editStudent(stu) {
    document.getElementById('editStudentId').value = stu.id;
    document.getElementById('editStudentName').value = stu.name;
    document.getElementById('editStudentEmail').value = stu.email;
    document.getElementById('editStudentStatus').value = stu.status;
    document.getElementById('editStudentModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editStudentModal').classList.add('hidden');
}

function toggleStudentStatus(id, newStatus) {
    confirmAction(`Change status to ${newStatus}?`, () => {
        document.getElementById('actionStudentId').value = id;
        document.getElementById('actionType').value = 'toggle_status';
        document.getElementById('actionStatus').value = newStatus;
        document.getElementById('actionForm').submit();
    });
}

function deleteStudent(id) {
    confirmAction('Permanently delete this student? This cannot be undone.', () => {
        document.getElementById('actionStudentId').value = id;
        document.getElementById('actionType').value = 'delete';
        document.getElementById('actionForm').submit();
    });
}
</script>