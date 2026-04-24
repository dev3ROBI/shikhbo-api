<?php
$mysqli = getDBConnection();

// Handle actions (same CRUD as before)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) { $error = 'CSRF validation failed.'; }
    else {
        $action = $_POST['action'] ?? '';
        if (in_array($action, ['add_exam','edit_exam'])) {
            $title = sanitize($_POST['title']);
            $catId = intval($_POST['category_id']);
            $duration = intval($_POST['duration_minutes']);
            $marks = intval($_POST['total_marks']);
            $passing = floatval($_POST['passing_percentage']);
            $status = $_POST['status'];
            $desc = sanitize($_POST['description'] ?? '');
            $date = !empty($_POST['exam_date']) ? $_POST['exam_date'] : null;
            if ($action === 'add_exam') {
                $stmt = $mysqli->prepare("INSERT INTO exams (title,category_id,description,exam_date,duration_minutes,total_marks,passing_percentage,status) VALUES (?,?,?,?,?,?,?,?)");
                $stmt->bind_param('sisiddds', $title, $catId, $desc, $date, $duration, $marks, $passing, $status);
            } else {
                $eid = intval($_POST['exam_id']);
                $stmt = $mysqli->prepare("UPDATE exams SET title=?,category_id=?,description=?,exam_date=?,duration_minutes=?,total_marks=?,passing_percentage=?,status=? WHERE id=?");
                $stmt->bind_param('sisidddsi', $title, $catId, $desc, $date, $duration, $marks, $passing, $status, $eid);
            }
            $stmt->execute(); $stmt->close();
            $success = $action==='add_exam'?'Exam created.':'Exam updated.';
        } elseif ($action === 'delete_exam') {
            $stmt = $mysqli->prepare("DELETE FROM exams WHERE id=?");
            $stmt->bind_param('i', intval($_POST['exam_id']));
            $stmt->execute(); $stmt->close();
            $success = 'Exam deleted.';
        }
    }
}

// Fetch categories for dropdown (tree)
$cats = $mysqli->query("SELECT id, name, level, parent_id FROM exam_categories WHERE is_active=1 ORDER BY parent_id, sort_order, id");
$catTree = []; while ($c = $cats->fetch_assoc()) { $catTree[$c['id']] = $c; }
function catSelectOptions($tree, $parent=null, $level=0) {
    $h = '';
    foreach ($tree as $id => $cat) {
        if ($cat['parent_id'] == $parent) {
            $h .= "<option value='{$id}'>" . str_repeat('— ', $level) . sanitizeOutput($cat['name']) . "</option>";
            $h .= catSelectOptions($tree, $id, $level+1);
        }
    }
    return $h;
}

// Search & Pagination
$search = sanitize($_GET['search'] ?? '');
$statusFilter = sanitize($_GET['status'] ?? '');
$page_num = max(1, intval($_GET['p'] ?? 1));
$limit = 15; $offset = ($page_num-1)*$limit;

$where = "1=1"; $params = []; $types = '';
if ($search) { $where .= " AND e.title LIKE ?"; $params[] = "%{$search}%"; $types .= 's'; }
if ($statusFilter) { $where .= " AND e.status = ?"; $params[] = $statusFilter; $types .= 's'; }

$countStmt = $mysqli->prepare("SELECT COUNT(*) as c FROM exams e WHERE $where");
if ($params) $countStmt->bind_param($types, ...$params);
$countStmt->execute(); $totalExams = $countStmt->get_result()->fetch_assoc()['c']; $countStmt->close();
$totalPages = ceil($totalExams/$limit);

$query = "SELECT e.*, c.name AS cat_name FROM exams e LEFT JOIN exam_categories c ON e.category_id=c.id WHERE $where ORDER BY e.created_at DESC LIMIT ? OFFSET ?";
$types .= 'ii'; $params[] = $limit; $params[] = $offset;
$stmt = $mysqli->prepare($query); $stmt->bind_param($types, ...$params); $stmt->execute();
$exams = $stmt->get_result(); $stmt->close();

// Stats
$activeCount = $mysqli->query("SELECT COUNT(*) as c FROM exams WHERE status='active'")->fetch_assoc()['c'];
$draftCount = $mysqli->query("SELECT COUNT(*) as c FROM exams WHERE status='draft'")->fetch_assoc()['c'];
$completedCount = $mysqli->query("SELECT COUNT(*) as c FROM exams WHERE status='completed'")->fetch_assoc()['c'];
?>

<?php if (isset($error)): ?><div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4"><?php echo sanitizeOutput($error); ?></div><?php endif; ?>
<?php if (isset($success)): ?><div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4"><?php echo sanitizeOutput($success); ?></div><?php endif; ?>

<!-- Header + Stats -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Exams Management</h1>
        <p class="text-gray-500 mt-1"><?php echo $totalExams; ?> exams total</p>
    </div>
    <div class="flex items-center gap-3">
        <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-700"><strong><?php echo $activeCount; ?></strong> Active</span>
        <span class="text-xs px-2 py-1 rounded-full bg-yellow-100 text-yellow-700"><strong><?php echo $draftCount; ?></strong> Draft</span>
        <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-700"><strong><?php echo $completedCount; ?></strong> Done</span>
        <button onclick="openExamModal()" class="px-4 py-2 bg-shikhbo-primary text-white rounded-lg text-sm hover:bg-indigo-700">
            <i class="fa-solid fa-plus mr-2"></i>Create Exam
        </button>
    </div>
</div>

<!-- Filters -->
<form method="GET" class="mb-6 flex flex-wrap gap-2 items-center">
    <input type="hidden" name="page" value="exams">
    <input type="text" name="search" value="<?php echo sanitizeOutput($search); ?>" placeholder="Search exams..." class="px-3 py-2 border border-gray-300 rounded-lg text-sm w-48">
    <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
        <option value="">All Status</option>
        <option value="active" <?php echo $statusFilter==='active'?'selected':''; ?>>Active</option>
        <option value="draft" <?php echo $statusFilter==='draft'?'selected':''; ?>>Draft</option>
        <option value="completed" <?php echo $statusFilter==='completed'?'selected':''; ?>>Completed</option>
    </select>
    <button type="submit" class="px-4 py-2 bg-shikhbo-primary text-white rounded-lg text-sm">Filter</button>
    <?php if ($search||$statusFilter): ?><a href="index.php?page=exams" class="px-4 py-2 border border-gray-300 rounded-lg text-sm">Clear</a><?php endif; ?>
</form>

<!-- Exams Table -->
<div class="bg-white rounded-xl shadow-md overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Exam</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Marks</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            <?php if ($exams->num_rows > 0): ?>
                <?php while ($exam = $exams->fetch_assoc()): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <p class="text-sm font-medium text-gray-800"><?php echo sanitizeOutput($exam['title']); ?></p>
                            <?php if ($exam['description']): ?><p class="text-xs text-gray-400 truncate max-w-xs"><?php echo sanitizeOutput(substr($exam['description'],0,60)); ?></p><?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600"><?php echo sanitizeOutput($exam['cat_name'] ?? '—'); ?></td>
                        <td class="px-4 py-3 text-sm text-gray-500"><?php echo $exam['exam_date'] ? date('M j, Y', strtotime($exam['exam_date'])) : '—'; ?></td>
                        <td class="px-4 py-3 text-sm"><?php echo $exam['duration_minutes']; ?> min</td>
                        <td class="px-4 py-3 text-sm font-medium"><?php echo $exam['total_marks']; ?></td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $exam['status']==='active'?'text-green-800 bg-green-100':($exam['status']==='completed'?'text-red-800 bg-red-100':'text-yellow-800 bg-yellow-100'); ?>"><?php echo ucfirst($exam['status']); ?></span>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <div class="flex items-center space-x-2">
                                <a href="index.php?page=exam_attempt&exam_id=<?php echo $exam['id']; ?>" class="text-green-600 hover:underline" title="Attempt"><i class="fa-solid fa-play"></i></a>
                                <a href="index.php?page=questions&exam_id=<?php echo $exam['id']; ?>" class="text-blue-600 hover:underline" title="Questions"><i class="fa-solid fa-list-check"></i></a>
                                <button onclick="editExam(<?php echo htmlspecialchars(json_encode($exam), ENT_QUOTES, 'UTF-8'); ?>)" class="text-shikhbo-primary hover:underline" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>
                                <button onclick="deleteExam(<?php echo $exam['id']; ?>)" class="text-red-600 hover:underline" title="Delete"><i class="fa-solid fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7" class="px-6 py-12 text-center text-gray-500"><i class="fa-solid fa-file-circle-exclamation text-3xl mb-2 block"></i>No exams yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php if ($totalPages > 1): ?>
        <div class="px-6 py-3 border-t flex justify-between text-sm">
            <span>Page <?php echo $page_num; ?> of <?php echo $totalPages; ?></span>
            <div class="flex space-x-1">
                <?php for ($i=1;$i<=$totalPages;$i++): ?>
                    <a href="index.php?page=exams&p=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $statusFilter; ?>" class="px-3 py-1 border rounded <?php echo $i===$page_num?'bg-shikhbo-primary text-white':'hover:bg-gray-100'; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal for Add/Edit (same as before, with description field added) -->
<div id="examModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
    <div class="absolute inset-0 bg-black bg-opacity-50" onclick="closeExamModal()"></div>
    <div class="relative bg-white rounded-xl shadow-xl w-full max-w-xl mx-4 p-6 max-h-screen overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold" id="examModalTitle">Create Exam</h3>
            <button onclick="closeExamModal()" class="text-gray-400 hover:text-gray-600"><i class="fa-solid fa-xmark text-xl"></i></button>
        </div>
        <form method="POST" id="examForm">
            <?php echo getCSRFTokenField(); ?>
            <input type="hidden" name="action" id="examAction" value="add_exam">
            <input type="hidden" name="exam_id" id="examId">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                    <input type="text" name="title" id="examTitle" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select name="category_id" id="examCategory" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="">— Select —</option>
                        <?php echo catSelectOptions($catTree); ?>
                    </select>
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
                    <label class="block text-sm font-medium text-gray-700 mb-1">Duration (min)</label>
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
                    <label class="block text-sm font-medium text-gray-700 mb-1">Exam Date</label>
                    <input type="datetime-local" name="exam_date" id="examDate" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" id="examDesc" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></textarea>
                </div>
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" onclick="closeExamModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-shikhbo-primary text-white rounded-lg text-sm">Save</button>
            </div>
        </form>
    </div>
</div>

<form id="deleteExamForm" method="POST" style="display:none;">
    <?php echo getCSRFTokenField(); ?>
    <input type="hidden" name="action" value="delete_exam">
    <input type="hidden" name="exam_id" id="deleteExamId">
</form>

<script>
function openExamModal(exam=null) {
    document.getElementById('examModal').classList.remove('hidden');
    if (exam) {
        document.getElementById('examModalTitle').textContent='Edit Exam';
        document.getElementById('examAction').value='edit_exam';
        document.getElementById('examId').value=exam.id;
        document.getElementById('examTitle').value=exam.title;
        document.getElementById('examCategory').value=exam.category_id||'';
        document.getElementById('examDuration').value=exam.duration_minutes;
        document.getElementById('examMarks').value=exam.total_marks;
        document.getElementById('examPassing').value=exam.passing_percentage;
        document.getElementById('examStatus').value=exam.status;
        document.getElementById('examDate').value=exam.exam_date?exam.exam_date.replace(' ','T'):'';
        document.getElementById('examDesc').value=exam.description||'';
    } else {
        document.getElementById('examModalTitle').textContent='Create Exam';
        document.getElementById('examAction').value='add_exam';
        document.getElementById('examId').value='';
        document.getElementById('examForm').reset();
    }
}
function closeExamModal(){document.getElementById('examModal').classList.add('hidden');}
function editExam(e){openExamModal(e);}
function deleteExam(id){
    if(confirm('Delete this exam? This will also delete all associated questions.')){
        document.getElementById('deleteExamId').value=id;
        document.getElementById('deleteExamForm').submit();
    }
}
</script>