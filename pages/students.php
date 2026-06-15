<?php
$mysqli = getDBConnection();

$studentRoles = ['Student', 'Student Pro', 'Premium Student'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token validation failed.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'edit_student') {
            $id = intval($_POST['student_id']);
            $name = sanitize($_POST['name']);
            $email = sanitize($_POST['email']);
            $role = sanitize($_POST['role'] ?? 'Student');
            $status = sanitize($_POST['status']);
            
            $stmt = $mysqli->prepare("UPDATE users SET name = ?, email = ?, role = ?, status = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $name, $email, $role, $status, $id);
            if ($stmt->execute()) {
                $success = "Student updated successfully.";
            } else {
                $error = "Error updating student.";
            }
            $stmt->close();
        } elseif ($action === 'delete') {
            $id = intval($_POST['student_id']);
            $stmt = $mysqli->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $success = "Student deleted successfully.";
            } else {
                $error = "Error deleting student.";
            }
            $stmt->close();
        } elseif ($action === 'toggle_status') {
            $id = intval($_POST['student_id']);
            $status = sanitize($_POST['status']);
            $stmt = $mysqli->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $id);
            $stmt->execute();
            $stmt->close();
            $success = "Student status updated.";
        }
    }
}

// Filters
$search = sanitize($_GET['search'] ?? '');
$statusFilter = sanitize($_GET['status'] ?? '');
$roleFilter = sanitize($_GET['role'] ?? '');

$roleIn = "'" . implode("','", $studentRoles) . "'";
$where = "(role IS NULL OR role = '' OR role = 'user' OR role IN ($roleIn))";

if ($search) {
    $where .= " AND (name LIKE '%$search%' OR email LIKE '%$search%')";
}
if ($statusFilter) {
    $where .= " AND status = '$statusFilter'";
}
if ($roleFilter) {
    $where .= " AND role = '$roleFilter'";
}

// Pagination
$limit = 15;
$page_num = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$offset = ($page_num - 1) * $limit;

$totalStudents = $mysqli->query("SELECT COUNT(*) as count FROM users WHERE $where")->fetch_assoc()['count'];
$totalPages = ceil($totalStudents / $limit);

$stmt = $mysqli->prepare("SELECT * FROM users WHERE $where ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $limit, $offset);
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
                <i class="fa-solid fa-check mr-1"></i><?php echo $mysqli->query("SELECT COUNT(*) as c FROM users WHERE status='active' AND $where")->fetch_assoc()['c']; ?> Active
            </span>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-4 mb-6 border border-gray-100 dark:border-gray-700">
        <form method="GET" action="index.php" class="flex flex-col lg:flex-row gap-3">
            <input type="hidden" name="page" value="students">
            <div class="relative flex-1">
                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" name="search" value="<?php echo sanitizeOutput($search); ?>" placeholder="Search by name or email..." class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced">
            </div>
            <div class="flex flex-wrap gap-2">
                <select name="role" class="px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    <option value="">All Roles</option>
                    <?php foreach($studentRoles as $r): ?>
                    <option value="<?php echo $r; ?>" <?php echo $roleFilter===$r?'selected':''; ?>><?php echo $r; ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="status" class="px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $statusFilter==='active'?'selected':''; ?>>Active</option>
                    <option value="suspended" <?php echo $statusFilter==='suspended'?'selected':''; ?>>Suspended</option>
                    <option value="inactive" <?php echo $statusFilter==='inactive'?'selected':''; ?>>Inactive</option>
                </select>
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700 transition-colors flex items-center gap-2">
                    <i class="fa-solid fa-filter"></i>Filter
                </button>
                <?php if ($search || $statusFilter || $roleFilter): ?>
                <a href="index.php?page=students" class="px-5 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center gap-2">
                    <i class="fa-solid fa-xmark"></i>Clear
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Students Table -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md overflow-hidden border border-gray-100 dark:border-gray-700">
        <div class="overflow-x-auto table-wrapper">
            <table class="w-full">
                <thead class="table-header">
                    <tr>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Student</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider hide-mobile">Role</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider hide-mobile">Auth</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider hide-mobile">Status</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider hide-mobile">Last Login</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                    <?php if ($students->num_rows > 0): ?>
                        <?php while ($stu = $students->fetch_assoc()): 
                            $role = $stu['role'] ?: 'Student';
                            $roleCls = 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300';
                            if ($role === 'Student Pro') $roleCls = 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
                            if ($role === 'Premium Student') $roleCls = 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
                        ?>
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
                                    <span class="badge <?php echo $roleCls; ?>"><?php echo $role; ?></span>
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
                        <tr><td colspan="6" class="px-6 py-16 text-center">
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
                    <a href="index.php?page=students&p=<?php echo $i; ?><?php echo $search?'&search='.urlencode($search):''; ?><?php echo $statusFilter?'&status='.$statusFilter:''; ?><?php echo $roleFilter?'&role='.$roleFilter:''; ?>" 
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

<div id="viewStudentModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 modal-backdrop" onclick="closeViewModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4 pointer-events-none">
        <div class="modal-content w-full max-w-lg pointer-events-auto">
            <div class="modal-header flex items-center justify-between sticky top-0 z-10">
                <h3 class="text-lg font-semibold">Student Profile</h3>
                <button onclick="closeViewModal()" class="p-2 rounded-lg transition-all"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <div id="viewStudentContent" class="modal-body-scroll p-6"></div>
        </div>
    </div>
</div>

<div id="editStudentModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 modal-backdrop" onclick="closeEditModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4 pointer-events-none">
        <div class="modal-content w-full max-w-md pointer-events-auto">
            <div class="modal-header flex items-center justify-between sticky top-0 z-10">
                <h3 class="text-lg font-semibold">Edit Student</h3>
                <button onclick="closeEditModal()" class="p-2 rounded-lg transition-all"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <form method="POST" class="modal-body-scroll space-y-4">
                <?php echo getCSRFTokenField(); ?>
                <input type="hidden" name="action" value="edit_student">
                <input type="hidden" name="student_id" id="editStudentId">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                    <input type="text" name="name" id="editStudentName" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                    <input type="email" name="email" id="editStudentEmail" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Role</label>
                    <select name="role" id="editStudentRole" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <?php foreach($studentRoles as $r): ?>
                        <option value="<?php echo $r; ?>"><?php echo $r; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                    <select name="status" id="editStudentStatus" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" onclick="closeEditModal()" class="btn-cancel">Cancel</button>
                    <button type="submit" class="btn-save">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form id="actionForm" method="POST" class="hidden">
    <?php echo getCSRFTokenField(); ?>
    <input type="hidden" name="action" id="actionType">
    <input type="hidden" name="student_id" id="actionStudentId">
    <input type="hidden" name="status" id="actionStatus">
</form>

<script>
function viewStudent(s) {
    const c = document.getElementById('viewStudentContent');
    const role = s.role || 'Student';
    c.innerHTML = `
        <div class="flex items-center gap-4 mb-6">
            <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(s.name)}&background=4F46E5&color=fff&size=64&bold=true" class="w-16 h-16 rounded-xl">
            <div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">${s.name}</h2>
                <p class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest">${role}</p>
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"><p class="text-gray-400 text-xs uppercase mb-1">Email</p><p class="font-medium text-gray-800 dark:text-gray-100 truncate">${s.email}</p></div>
            <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"><p class="text-gray-400 text-xs uppercase mb-1">Status</p><p class="font-medium text-gray-800 dark:text-gray-100 capitalize">${s.status}</p></div>
            <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"><p class="text-gray-400 text-xs uppercase mb-1">Auth Type</p><p class="font-medium text-gray-800 dark:text-gray-100">${s.google_login == 1 ? 'Google' : 'Email'}</p></div>
            <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"><p class="text-gray-400 text-xs uppercase mb-1">Joined</p><p class="font-medium text-gray-800 dark:text-gray-100">${new Date(s.created_at).toLocaleDateString('en-US', {month:'long', day:'numeric', year:'numeric'})}</p></div>
            <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"><p class="text-gray-400 text-xs uppercase mb-1">Last Login</p><p class="font-medium text-gray-800 dark:text-gray-100">${s.last_login ? s.last_login : 'Never'}</p></div>
            <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"><p class="text-gray-400 text-xs uppercase mb-1">Device</p><p class="font-medium text-gray-800 dark:text-gray-100">${s.device_model || 'Unknown'}</p></div>
        </div>
    `;
    document.getElementById('viewStudentModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function editStudent(s) {
    document.getElementById('editStudentId').value = s.id;
    document.getElementById('editStudentName').value = s.name;
    document.getElementById('editStudentEmail').value = s.email;
    document.getElementById('editStudentRole').value = s.role || 'Student';
    document.getElementById('editStudentStatus').value = s.status;
    document.getElementById('editStudentModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeViewModal() { document.getElementById('viewStudentModal').classList.add('hidden'); document.body.style.overflow = ''; }
function closeEditModal() { document.getElementById('editStudentModal').classList.add('hidden'); document.body.style.overflow = ''; }

function toggleStudentStatus(id, newStatus) {
    document.getElementById('actionStudentId').value = id;
    document.getElementById('actionStatus').value = newStatus;
    document.getElementById('actionType').value = 'toggle_status';
    document.getElementById('actionForm').submit();
}

function deleteStudent(id) {
    confirmAction('Delete this student? This cannot be undone.', () => {
        document.getElementById('actionStudentId').value = id;
        document.getElementById('actionType').value = 'delete';
        document.getElementById('actionForm').submit();
    });
}
</script>