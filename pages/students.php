<?php
$mysqli = getDBConnection();

// Handle actions
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
            $success = "Status updated.";
        } elseif ($action === 'delete') {
            $stmt = $mysqli->prepare("DELETE FROM users WHERE id=? AND (role IS NULL OR role='' OR role='user')");
            $stmt->bind_param('i', $studentId);
            $stmt->execute(); $stmt->close();
            $success = "Student deleted.";
        } elseif ($action === 'edit_student') {
            $name = sanitize($_POST['name']);
            $email = sanitize($_POST['email']);
            $status = sanitize($_POST['status']);
            $stmt = $mysqli->prepare("UPDATE users SET name=?, email=?, status=? WHERE id=? AND (role IS NULL OR role='' OR role='user')");
            $stmt->bind_param('sssi', $name, $email, $status, $studentId);
            $stmt->execute(); $stmt->close();
            $success = "Student updated.";
        }
    }
}

// Search & Pagination
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

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Students Management</h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1"><?php echo number_format($totalStudents); ?> students total</p>
    </div>
    <form method="GET" action="index.php" class="flex flex-wrap gap-2 items-center">
        <input type="hidden" name="page" value="students">
        <input type="text" name="search" value="<?php echo sanitizeOutput($search); ?>" placeholder="Search name/email..." class="px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg text-sm focus:ring-2 focus:ring-shikhbo-primary outline-none w-48">
        <select name="status" class="px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg text-sm">
            <option value="">All Status</option>
            <option value="active" <?php echo $statusFilter==='active'?'selected':''; ?>>Active</option>
            <option value="suspended" <?php echo $statusFilter==='suspended'?'selected':''; ?>>Suspended</option>
            <option value="inactive" <?php echo $statusFilter==='inactive'?'selected':''; ?>>Inactive</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-shikhbo-primary text-white rounded-lg text-sm"><i class="fa-solid fa-filter mr-1"></i>Filter</button>
        <?php if ($search || $statusFilter): ?>
            <a href="index.php?page=students" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-600 dark:text-gray-300">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Students Table -->
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-md dark:shadow-gray-900/20 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Student</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Email</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Auth</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Last Login</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <?php if ($students->num_rows > 0): ?>
                    <?php while ($stu = $students->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center space-x-3">
                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($stu['name']); ?>&background=4F46E5&color=fff&size=36" class="w-9 h-9 rounded-full">
                                    <div>
                                        <p class="text-sm font-medium text-gray-800 dark:text-gray-100"><?php echo sanitizeOutput($stu['name']); ?></p>
                                        <p class="text-xs text-gray-400 dark:text-gray-500">#<?php echo $stu['id']; ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300"><?php echo sanitizeOutput($stu['email']); ?></td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <?php if ($stu['google_login']): ?>
                                    <span class="px-2 py-1 text-xs bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-full"><i class="fa-brands fa-google mr-1"></i>Google</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 text-xs bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-full"><i class="fa-solid fa-envelope mr-1"></i>Email</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $stu['status']==='active'?'text-green-800 bg-green-100 dark:text-green-300 dark:bg-green-900/30':($stu['status']==='suspended'?'text-red-800 bg-red-100 dark:text-red-300 dark:bg-red-900/30':'text-yellow-800 bg-yellow-100 dark:text-yellow-300 dark:bg-yellow-900/30'); ?>">
                                    <?php echo ucfirst($stu['status']); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?php echo $stu['last_login'] ? date('M j, H:i', strtotime($stu['last_login'])) : 'Never'; ?></td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                <div class="flex items-center space-x-2">
                                    <button onclick="viewStudent(<?php echo htmlspecialchars(json_encode($stu), ENT_QUOTES, 'UTF-8'); ?>)" class="text-blue-600 hover:underline" title="View Details"><i class="fa-solid fa-eye"></i></button>
                                    <button onclick="editStudent(<?php echo htmlspecialchars(json_encode($stu), ENT_QUOTES, 'UTF-8'); ?>)" class="text-shikhbo-primary hover:underline" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>
                                    <button onclick="toggleStudentStatus(<?php echo $stu['id']; ?>,'<?php echo $stu['status']==='active'?'suspended':'active'; ?>')" class="text-yellow-600 hover:underline" title="<?php echo $stu['status']==='active'?'Suspend':'Activate'; ?>"><i class="fa-solid fa-<?php echo $stu['status']==='active'?'ban':'check'; ?>"></i></button>
                                    <button onclick="deleteStudent(<?php echo $stu['id']; ?>)" class="text-red-600 hover:underline" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400"><i class="fa-solid fa-user-slash text-3xl mb-2 block"></i>No students found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($totalPages > 1): ?>
        <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <p class="text-sm text-gray-600 dark:text-gray-400">Page <?php echo $page_num; ?> of <?php echo $totalPages; ?></p>
            <div class="flex space-x-1">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="index.php?page=students&p=<?php echo $i; ?><?php echo $search?'&search='.urlencode($search):''; ?><?php echo $statusFilter?'&status='.$statusFilter:''; ?>" class="px-3 py-1 text-sm border rounded <?php echo $i===$page_num?'bg-shikhbo-primary text-white border-shikhbo-primary':'hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-gray-300'; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- View Student Detail Modal -->
<div id="viewStudentModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
    <div class="absolute inset-0 bg-black bg-opacity-50" onclick="closeViewModal()"></div>
    <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-lg mx-4 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Student Details</h3>
            <button onclick="closeViewModal()" class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300"><i class="fa-solid fa-xmark text-xl"></i></button>
        </div>
        <div id="viewStudentContent" class="space-y-3"></div>
    </div>
</div>

<!-- Edit Student Modal -->
<div id="editStudentModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
    <div class="absolute inset-0 bg-black bg-opacity-50" onclick="closeEditModal()"></div>
    <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-lg mx-4 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Edit Student</h3>
            <button onclick="closeEditModal()" class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300"><i class="fa-solid fa-xmark text-xl"></i></button>
        </div>
        <form method="POST" class="space-y-4">
            <?php echo getCSRFTokenField(); ?>
            <input type="hidden" name="action" value="edit_student">
            <input type="hidden" name="student_id" id="editStudentId">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                <input type="text" name="name" id="editStudentName" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg text-sm focus:ring-2 focus:ring-shikhbo-primary outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                <input type="email" name="email" id="editStudentEmail" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg text-sm focus:ring-2 focus:ring-shikhbo-primary outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                <select name="status" id="editStudentStatus" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg text-sm">
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

<!-- Hidden action form -->
<form id="actionForm" method="POST" style="display:none;">
    <?php echo getCSRFTokenField(); ?>
    <input type="hidden" name="student_id" id="actionStudentId">
    <input type="hidden" name="action" id="actionType">
    <input type="hidden" name="new_status" id="actionStatus">
</form>

<script>
function viewStudent(stu) {
    document.getElementById('viewStudentContent').innerHTML = `
        <div class="flex items-center space-x-4 mb-4">
            <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(stu.name)}&background=4F46E5&color=fff&size=64" class="w-16 h-16 rounded-full">
            <div><p class="text-lg font-semibold text-gray-800 dark:text-gray-100">${stu.name}</p><p class="text-sm text-gray-500 dark:text-gray-400">#${stu.id}</p></div>
        </div>
        <div class="grid grid-cols-2 gap-3 text-sm">
            <div><span class="text-gray-500 dark:text-gray-400">Email:</span><p class="font-medium text-gray-800 dark:text-gray-100">${stu.email}</p></div>
            <div><span class="text-gray-500 dark:text-gray-400">Status:</span><p class="font-medium text-gray-800 dark:text-gray-100 capitalize">${stu.status}</p></div>
            <div><span class="text-gray-500 dark:text-gray-400">Referral:</span><p class="font-medium text-gray-800 dark:text-gray-100">${stu.referral_code || '—'}</p></div>
            <div><span class="text-gray-500 dark:text-gray-400">Auth:</span><p class="font-medium text-gray-800 dark:text-gray-100">${stu.google_login == 1 ? 'Google' : 'Email'}</p></div>
            <div><span class="text-gray-500 dark:text-gray-400">Device:</span><p class="font-medium text-gray-800 dark:text-gray-100">${stu.device_model || '—'}</p></div>
            <div><span class="text-gray-500 dark:text-gray-400">IP:</span><p class="font-medium text-gray-800 dark:text-gray-100">${stu.ip_address || '—'}</p></div>
            <div><span class="text-gray-500 dark:text-gray-400">Last Login:</span><p class="font-medium text-gray-800 dark:text-gray-100">${stu.last_login || 'Never'}</p></div>
            <div><span class="text-gray-500 dark:text-gray-400">Joined:</span><p class="font-medium text-gray-800 dark:text-gray-100">${stu.created_at}</p></div>
        </div>
    `;
    document.getElementById('viewStudentModal').classList.remove('hidden');
}
function closeViewModal() { document.getElementById('viewStudentModal').classList.add('hidden'); }
function editStudent(stu) {
    document.getElementById('editStudentId').value = stu.id;
    document.getElementById('editStudentName').value = stu.name;
    document.getElementById('editStudentEmail').value = stu.email;
    document.getElementById('editStudentStatus').value = stu.status;
    document.getElementById('editStudentModal').classList.remove('hidden');
}
function closeEditModal() { document.getElementById('editStudentModal').classList.add('hidden'); }
function toggleStudentStatus(id, newStatus) {
    if (confirm(`Change status to ${newStatus}?`)) {
        document.getElementById('actionStudentId').value = id;
        document.getElementById('actionType').value = 'toggle_status';
        document.getElementById('actionStatus').value = newStatus;
        document.getElementById('actionForm').submit();
    }
}
function deleteStudent(id) {
    if (confirm('Permanently delete this student? This cannot be undone.')) {
        document.getElementById('actionStudentId').value = id;
        document.getElementById('actionType').value = 'delete';
        document.getElementById('actionForm').submit();
    }
}
</script>