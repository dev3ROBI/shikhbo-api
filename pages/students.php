<?php
/**
 * Students Management Page
 * Full CRUD with MySQL integration
 */
$mysqli = getDBConnection();

// Handle status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token validation failed.';
    } else {
        $studentId = intval($_POST['student_id']);
        
        if ($_POST['action'] === 'toggle_status') {
            $newStatus = $_POST['new_status'] === 'active' ? 'active' : 'suspended';
            $stmt = $mysqli->prepare("UPDATE users SET status = ? WHERE id = ? AND (role IS NULL OR role = '' OR role = 'user')");
            $stmt->bind_param('si', $newStatus, $studentId);
            $stmt->execute();
            $stmt->close();
            $success = "Student status updated successfully.";
        } elseif ($_POST['action'] === 'delete') {
            $stmt = $mysqli->prepare("DELETE FROM users WHERE id = ? AND (role IS NULL OR role = '' OR role = 'user')");
            $stmt->bind_param('i', $studentId);
            $stmt->execute();
            $stmt->close();
            $success = "Student deleted successfully.";
        }
    }
}

// Search & Pagination
$search = sanitize($_GET['search'] ?? '');
$page_num = max(1, intval($_GET['p'] ?? 1));
$limit = 15;
$offset = ($page_num - 1) * $limit;

$where = "WHERE (role IS NULL OR role = '' OR role = 'user')";
$params = [];
$types = '';

if ($search) {
    $where .= " AND (name LIKE ? OR email LIKE ?)";
    $searchParam = "%{$search}%";
    $params = [$searchParam, $searchParam];
    $types = 'ss';
}

$countStmt = $mysqli->prepare("SELECT COUNT(*) as total FROM users {$where}");
if ($params) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalStudents = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

$totalPages = ceil($totalStudents / $limit);

$query = "SELECT id, name, email, google_login, status, referral_code, device_model, 
          ip_address, last_login, created_at 
          FROM users {$where} ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $mysqli->prepare($query);
if ($params) {
    $stmt->bind_param($types . 'ii', ...array_merge($params, [$limit, $offset]));
} else {
    $stmt->bind_param('ii', $limit, $offset);
}
$stmt->execute();
$students = $stmt->get_result();
$stmt->close();
?>

<?php if (isset($error)): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4"><?php echo sanitizeOutput($error); ?></div>
<?php endif; ?>
<?php if (isset($success)): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4"><?php echo sanitizeOutput($success); ?></div>
<?php endif; ?>

<!-- Header & Search -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Students Management</h1>
        <p class="text-gray-500 mt-1">Total: <?php echo number_format($totalStudents); ?> students</p>
    </div>
    <form method="GET" action="index.php" class="flex items-center space-x-2">
        <input type="hidden" name="page" value="students">
        <input type="text" name="search" value="<?php echo sanitizeOutput($search); ?>" 
               placeholder="Search by name or email..."
               class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-shikhbo-primary focus:border-transparent outline-none">
        <button type="submit" class="px-4 py-2 bg-shikhbo-primary text-white rounded-lg text-sm hover:bg-indigo-700">
            <i class="fa-solid fa-search"></i>
        </button>
        <?php if ($search): ?>
            <a href="index.php?page=students" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50">
                Clear
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- Students Table -->
<div class="bg-white rounded-xl shadow-md overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Login Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Login</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if ($students->num_rows > 0): ?>
                    <?php while ($student = $students->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">#<?php echo $student['id']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center space-x-3">
                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($student['name']); ?>&background=4F46E5&color=fff&size=32" 
                                         class="w-8 h-8 rounded-full">
                                    <span class="text-sm font-medium text-gray-800"><?php echo sanitizeOutput($student['name']); ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                <?php echo sanitizeOutput($student['email']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php if ($student['google_login']): ?>
                                    <span class="px-2 py-1 text-xs bg-red-50 text-red-700 rounded-full">Google</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 text-xs bg-blue-50 text-blue-700 rounded-full">Email</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                    <?php echo $student['status'] === 'active' ? 'text-green-800 bg-green-100' : 
                                        ($student['status'] === 'suspended' ? 'text-red-800 bg-red-100' : 'text-yellow-800 bg-yellow-100'); ?>">
                                    <?php echo ucfirst($student['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $student['last_login'] ? date('d M, Y H:i', strtotime($student['last_login'])) : 'Never'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                                <button onclick="toggleStudentStatus(<?php echo $student['id']; ?>, '<?php echo $student['status'] === 'active' ? 'suspended' : 'active'; ?>')"
                                        class="text-shikhbo-primary hover:underline">
                                    <?php echo $student['status'] === 'active' ? 'Suspend' : 'Activate'; ?>
                                </button>
                                <button onclick="deleteStudent(<?php echo $student['id']; ?>)"
                                        class="text-red-600 hover:underline">Delete</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                            <i class="fa-solid fa-user-slash text-3xl mb-2 block"></i>
                            No students found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="px-6 py-3 border-t border-gray-200 flex items-center justify-between">
            <p class="text-sm text-gray-600">Showing page <?php echo $page_num; ?> of <?php echo $totalPages; ?></p>
            <div class="flex space-x-1">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="index.php?page=students&p=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"
                       class="px-3 py-1 text-sm border rounded hover:bg-gray-100 <?php echo $i === $page_num ? 'bg-shikhbo-primary text-white border-shikhbo-primary' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Hidden forms for actions -->
<form id="actionForm" method="POST" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    <input type="hidden" name="student_id" id="actionStudentId">
    <input type="hidden" name="action" id="actionType">
    <input type="hidden" name="new_status" id="actionStatus">
</form>

<script>
    function toggleStudentStatus(id, newStatus) {
        if (confirm('Are you sure you want to ' + newStatus + ' this student?')) {
            document.getElementById('actionStudentId').value = id;
            document.getElementById('actionType').value = 'toggle_status';
            document.getElementById('actionStatus').value = newStatus;
            document.getElementById('actionForm').submit();
        }
    }
    function deleteStudent(id) {
        if (confirm('Are you sure you want to permanently delete this student? This action cannot be undone.')) {
            document.getElementById('actionStudentId').value = id;
            document.getElementById('actionType').value = 'delete';
            document.getElementById('actionForm').submit();
        }
    }
</script>