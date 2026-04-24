<?php
$mysqli = getDBConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token validation failed.';
    } else {
        $action = $_POST['action'] ?? '';

        // ADD / EDIT Exam
        if (in_array($action, ['add_exam', 'edit_exam'])) {
            $title = sanitize($_POST['title']);
            $categoryId = intval($_POST['category_id']);
            $duration = intval($_POST['duration_minutes']);
            $totalMarks = intval($_POST['total_marks']);
            $passing = floatval($_POST['passing_percentage']);
            $status = $_POST['status'];
            $date = $_POST['exam_date'] ?? null;

            if ($action === 'add_exam') {
                $stmt = $mysqli->prepare("INSERT INTO exams (title, category_id, exam_date, duration_minutes, total_marks, passing_percentage, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('sisidds', $title, $categoryId, $date, $duration, $totalMarks, $passing, $status);
            } else {
                $examId = intval($_POST['exam_id']);
                $stmt = $mysqli->prepare("UPDATE exams SET title=?, category_id=?, exam_date=?, duration_minutes=?, total_marks=?, passing_percentage=?, status=? WHERE id=?");
                $stmt->bind_param('sisiddsi', $title, $categoryId, $date, $duration, $totalMarks, $passing, $status, $examId);
            }
            $stmt->execute();
            $stmt->close();
            $success = $action === 'add_exam' ? 'Exam created successfully.' : 'Exam updated successfully.';
        }

        // DELETE Exam
        if ($action === 'delete_exam') {
            $examId = intval($_POST['exam_id']);
            $stmt = $mysqli->prepare("DELETE FROM exams WHERE id = ?");
            $stmt->bind_param('i', $examId);
            $stmt->execute();
            $stmt->close();
            $success = 'Exam deleted.';
        }
    }
}

// Fetch categories for dropdown (tree view)
$categories = $mysqli->query("SELECT id, name, level, parent_id FROM exam_categories WHERE is_active = 1 ORDER BY parent_id, sort_order, id");
$catTree = [];
while ($c = $categories->fetch_assoc()) {
    $catTree[$c['id']] = $c;
}

// Build hierarchical view for select
function catOptions($tree, $parent = null, $level = 0) {
    $html = '';
    foreach ($tree as $id => $cat) {
        if ($cat['parent_id'] == $parent) {
            $indent = str_repeat('— ', $level);
            $html .= "<option value='{$id}'>{$indent}{$cat['name']}</option>";
            $html .= catOptions($tree, $id, $level + 1);
        }
    }
    return $html;
}

// Search & Pagination
$search = sanitize($_GET['search'] ?? '');
$page = max(1, intval($_GET['p'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

$where = "1=1";
$params = [];
$types = '';
if ($search) {
    $where .= " AND e.title LIKE ?";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $types .= 's';
}

$countStmt = $mysqli->prepare("SELECT COUNT(*) as total FROM exams e WHERE {$where}");
if ($params) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalExams = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();
$totalPages = ceil($totalExams / $limit);

$query = "SELECT e.*, c.name AS category_name FROM exams e LEFT JOIN exam_categories c ON e.category_id = c.id WHERE {$where} ORDER BY e.created_at DESC LIMIT ? OFFSET ?";
$types .= 'ii';
$params[] = $limit;
$params[] = $offset;
$stmt = $mysqli->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$exams = $stmt->get_result();
$stmt->close();
?>

<?php if (isset($error)): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4"><?php echo sanitizeOutput($error); ?></div>
<?php endif; ?>
<?php if (isset($success)): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4"><?php echo sanitizeOutput($success); ?></div>
<?php endif; ?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Exams Management</h1>
        <p class="text-gray-500 mt-1">Total: <?php echo $totalExams; ?> exams</p>
    </div>
    <div class="flex space-x-2">
        <button onclick="openExamModal()" class="px-4 py-2 bg-shikhbo-primary text-white rounded-lg text-sm hover:bg-indigo-700">
            <i class="fa-solid fa-plus mr-2"></i>Create Exam
        </button>
    </div>
</div>

<!-- Search -->
<form method="GET" class="mb-6 flex space-x-2">
    <input type="hidden" name="page" value="exams">
    <input type="text" name="search" value="<?php echo sanitizeOutput($search); ?>" placeholder="Search exams..." class="px-4 py-2 border border-gray-300 rounded-lg text-sm w-full max-w-md">
    <button type="submit" class="px-4 py-2 bg-shikhbo-primary text-white rounded-lg text-sm">Search</button>
    <?php if ($search): ?>
        <a href="index.php?page=exams" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50">Clear</a>
    <?php endif; ?>
</form>

<!-- Exams Table -->
<div class="bg-white rounded-xl shadow-md overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Marks</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if ($exams->num_rows > 0): ?>
                <?php while ($exam = $exams->fetch_assoc()): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800"><?php echo sanitizeOutput($exam['title']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo sanitizeOutput($exam['category_name'] ?? '—'); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo $exam['exam_date'] ? date('d M, Y', strtotime($exam['exam_date'])) : '—'; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo $exam['duration_minutes']; ?> min</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo $exam['total_marks']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $exam['status'] === 'active' ? 'text-green-800 bg-green-100' : ($exam['status'] === 'completed' ? 'text-red-800 bg-red-100' : 'text-yellow-800 bg-yellow-100'); ?>">
                                <?php echo ucfirst($exam['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                            <button onclick="editExam(<?php echo htmlspecialchars(json_encode($exam)); ?>)" class="text-shikhbo-primary hover:underline">Edit</button>
                            <button onclick="deleteExam(<?php echo $exam['id']; ?>)" class="text-red-600 hover:underline">Delete</button>
                            <a href="index.php?page=exam_attempt&exam_id=<?php echo $exam['id']; ?>" class="text-green-600 hover:underline">Attempt</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7" class="px-6 py-8 text-center text-gray-500">No exams found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php if ($totalPages > 1): ?>
        <div class="px-6 py-3 border-t flex justify-between items-center text-sm">
            <span>Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
            <div class="flex space-x-1">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="index.php?page=exams&p=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" class="px-3 py-1 border rounded <?php echo $i === $page ? 'bg-shikhbo-primary text-white' : 'hover:bg-gray-100'; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal for Add/Edit Exam -->
<div id="examModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
    <div class="absolute inset-0 bg-black bg-opacity-50" onclick="closeExamModal()"></div>
    <div class="relative bg-white rounded-xl shadow-xl w-full max-w-lg mx-4 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold" id="examModalTitle">Create Exam</h3>
            <button onclick="closeExamModal()" class="text-gray-400 hover:text-gray-600"><i class="fa-solid fa-xmark text-xl"></i></button>
        </div>
        <form method="POST" id="examForm">
            <?php echo getCSRFTokenField(); ?>
            <input type="hidden" name="action" id="examAction" value="add_exam">
            <input type="hidden" name="exam_id" id="examId">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                    <input type="text" name="title" id="examTitle" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-shikhbo-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select name="category_id" id="examCategory" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="">— Select Category —</option>
                        <?php echo catOptions($catTree); ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Duration (minutes)</label>
                    <input type="number" name="duration_minutes" id="examDuration" value="60" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Total Marks</label>
                    <input type="number" name="total_marks" id="examMarks" value="100" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Passing %</label>
                    <input type="number" step="0.01" name="passing_percentage" id="examPassing" value="40" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="examStatus" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="draft">Draft</option>
                        <option value="active" selected>Active</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Exam Date</label>
                    <input type="datetime-local" name="exam_date" id="examDate" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" onclick="closeExamModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-shikhbo-primary text-white rounded-lg text-sm">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openExamModal(exam = null) {
    document.getElementById('examModal').classList.remove('hidden');
    if (exam) {
        document.getElementById('examModalTitle').textContent = 'Edit Exam';
        document.getElementById('examAction').value = 'edit_exam';
        document.getElementById('examId').value = exam.id;
        document.getElementById('examTitle').value = exam.title;
        document.getElementById('examCategory').value = exam.category_id || '';
        document.getElementById('examDuration').value = exam.duration_minutes;
        document.getElementById('examMarks').value = exam.total_marks;
        document.getElementById('examPassing').value = exam.passing_percentage;
        document.getElementById('examStatus').value = exam.status;
        document.getElementById('examDate').value = exam.exam_date ? exam.exam_date.replace(' ', 'T') : '';
    } else {
        document.getElementById('examModalTitle').textContent = 'Create Exam';
        document.getElementById('examAction').value = 'add_exam';
        document.getElementById('examId').value = '';
        document.getElementById('examForm').reset();
    }
}
function closeExamModal() {
    document.getElementById('examModal').classList.add('hidden');
}
function editExam(exam) {
    openExamModal(exam);
}
function deleteExam(id) {
    if (confirm('Delete this exam permanently?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input type="hidden" name="action" value="delete_exam"><input type="hidden" name="exam_id" value="${id}"><?php echo getCSRFTokenField(); ?>`;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>