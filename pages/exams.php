<?php
$mysqli = getDBConnection();

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
            $date = (!empty($_POST['exam_date']) && strtotime($_POST['exam_date'])) ? $_POST['exam_date'] : null;
            if ($action === 'add_exam') {
                $stmt = $mysqli->prepare("INSERT INTO exams (title,category_id,description,exam_date,duration_minutes,total_marks,passing_percentage,status) VALUES (?,?,?,?,?,?,?,?)");
                $stmt->bind_param('sissidds', $title, $catId, $desc, $date, $duration, $marks, $passing, $status);
            } else {
                $eid = intval($_POST['exam_id']);
                $stmt = $mysqli->prepare("UPDATE exams SET title=?,category_id=?,description=?,exam_date=?,duration_minutes=?,total_marks=?,passing_percentage=?,status=? WHERE id=?");
                $stmt->bind_param('sissiddsi', $title, $catId, $desc, $date, $duration, $marks, $passing, $status, $eid);
            }
            $stmt->execute(); $stmt->close();
            $success = $action==='add_exam'?'Exam created successfully.':'Exam updated successfully.';
        } elseif ($action === 'delete_exam') {
            $stmt = $mysqli->prepare("DELETE FROM exams WHERE id=?"); $stmt->bind_param('i', intval($_POST['exam_id']));
            $stmt->execute(); $stmt->close(); $success = 'Exam deleted successfully.';
        }
    }
}

$allCats = $mysqli->query("SELECT ec.*, COUNT(e.id) as exam_count FROM exam_categories ec LEFT JOIN exams e ON ec.id=e.category_id GROUP BY ec.id ORDER BY ec.parent_id, ec.sort_order, ec.id");
$catsById = []; while ($c = $allCats->fetch_assoc()) { $catsById[$c['id']] = $c; }

$allExams = $mysqli->query("SELECT e.*, c.name AS cat_name FROM exams e LEFT JOIN exam_categories c ON e.category_id=c.id ORDER BY e.created_at DESC");
$examsByCat = [];
while ($ex = $allExams->fetch_assoc()) { $examsByCat[$ex['category_id'] ?? 0][] = $ex; }

function buildCatTree($cats, $parentId = null) {
    $tree = [];
    foreach ($cats as $id => $cat) {
        if ($cat['parent_id'] == $parentId) {
            $cat['children'] = buildCatTree($cats, $id);
            $tree[] = $cat;
        }
    }
    return $tree;
}
$catTree = buildCatTree($catsById);

function renderExamTree($tree, $examsByCat, $level = 0) {
    $html = '';
    $typeColors = ['academic'=>'blue','job'=>'emerald','general'=>'purple','other'=>'gray'];
    foreach ($tree as $cat) {
        $tc = $typeColors[$cat['category_type']] ?? 'gray';
        $hasChildren = !empty($cat['children']);
        $catExams = $examsByCat[$cat['id']] ?? [];
        $totalExamsInCat = count($catExams);

        $html .= "<div class='tree-node'>";
        $html .= "<div class='tree-header flex items-center py-3 px-4 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition-all' onclick='toggleExamTreeNode(this, {$cat['id']})'>";
        $html .= "<span class='w-8 h-8 flex items-center justify-center mr-3'>";
        $html .= ($hasChildren || $totalExamsInCat > 0) ? "<i class='fa-solid fa-chevron-right text-xs text-gray-400 dark:text-gray-500 transition-transform duration-200 chevron'></i>" : "<span class='w-2'></span>";
        $html .= "</span>";
        $html .= "<div class='w-9 h-9 rounded-lg bg-{$tc}-100 dark:bg-{$tc}-900/30 flex items-center justify-center mr-3'>";
        $html .= "<i class='fa-solid fa-folder text-{$tc}-600 dark:text-{$tc}-400 text-sm'></i>";
        $html .= "</div>";
        $html .= "<span class='flex-1 text-sm font-medium text-gray-700 dark:text-gray-200'>" . sanitizeOutput($cat['name']) . "</span>";
        $html .= "<span class='px-2.5 py-1 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'>{$totalExamsInCat} exams</span>";
        $html .= "</div>";

        $html .= "<div class='cat-exams hidden ml-12' id='cat-exams-{$cat['id']}'>";
        if ($totalExamsInCat > 0) {
            foreach ($catExams as $exam) {
                $statusColors = ['active'=>'green','draft'=>'yellow','completed'=>'gray'];
                $sc = $statusColors[$exam['status']] ?? 'gray';
                $html .= "<div class='mb-3 ml-4 border-l-2 border-{$sc}-200 dark:border-{$sc}-700 pl-4 py-3'>";
                $html .= "<div class='flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2'>";
                $html .= "<div class='min-w-0'><p class='text-sm font-semibold text-gray-800 dark:text-gray-100'>" . sanitizeOutput($exam['title']) . "</p>";
                if ($exam['description']) $html .= "<p class='text-xs text-gray-500 dark:text-gray-400 mt-1'>" . sanitizeOutput(substr($exam['description'],0,80)) . "</p>";
                $html .= "</div>";
                $html .= "<div class='flex items-center gap-3'>";
                $html .= "<div class='flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400'>";
                $html .= "<span><i class='fa-regular fa-clock mr-1'></i>{$exam['duration_minutes']}m</span>";
                $html .= "<span><i class='fa-solid fa-star mr-1'></i>{$exam['total_marks']}pts</span>";
                $html .= "</div>";
                $html .= "<span class='badge bg-{$sc}-100 text-{$sc}-700 dark:bg-{$sc}-900/30 dark:text-{$sc}-400'>" . ucfirst($exam['status']) . "</span>";
                $html .= "</div></div>";
                $html .= "<div class='flex items-center gap-3 mt-3'>";
                $html .= "<a href='index.php?page=exam_attempt&exam_id={$exam['id']}' class='text-xs text-green-600 dark:text-green-400 hover:underline'><i class='fa-solid fa-play mr-1'></i>Attempt</a>";
                $html .= "<a href='index.php?page=questions&exam_id={$exam['id']}' class='text-xs text-blue-600 dark:text-blue-400 hover:underline'><i class='fa-solid fa-list-check mr-1'></i>Questions</a>";
                $html .= "<button onclick='event.stopPropagation(); editExam(" . htmlspecialchars(json_encode($exam), ENT_QUOTES, 'UTF-8') . ")' class='text-xs text-indigo-600 dark:text-indigo-400 hover:underline'><i class='fa-solid fa-pen-to-square mr-1'></i>Edit</button>";
                $html .= "<button onclick='event.stopPropagation(); deleteExam({$exam['id']})' class='text-xs text-red-600 dark:text-red-400 hover:underline'><i class='fa-solid fa-trash mr-1'></i>Delete</button>";
                $html .= "</div></div>";
            }
        } else {
            $html .= "<div class='text-xs text-gray-400 dark:text-gray-500 py-3 pl-4'>No exams in this category</div>";
        }
        $html .= "</div>";

        if ($hasChildren) {
            $html .= "<div class='cat-children hidden ml-8' id='cat-children-{$cat['id']}'>";
            $html .= renderExamTree($cat['children'], $examsByCat, $level+1);
            $html .= "</div>";
        }
        $html .= "</div>";
    }
    return $html;
}

$totalExams = $mysqli->query("SELECT COUNT(*) as c FROM exams")->fetch_assoc()['c'];
$activeCount = $mysqli->query("SELECT COUNT(*) as c FROM exams WHERE status='active'")->fetch_assoc()['c'];
$draftCount = $mysqli->query("SELECT COUNT(*) as c FROM exams WHERE status='draft'")->fetch_assoc()['c'];
$completedCount = $mysqli->query("SELECT COUNT(*) as c FROM exams WHERE status='completed'")->fetch_assoc()['c'];

function buildCategoryPaths($catsById) {
    $paths = [];
    $cache = [];
    $getPath = function($id) use ($catsById, &$cache, &$getPath) {
        if (isset($cache[$id])) return $cache[$id];
        if (!isset($catsById[$id])) return '';
        $parentId = $catsById[$id]['parent_id'];
        $name = $catsById[$id]['name'];
        if ($parentId && $parentId != $id) {
            $parentPath = $getPath($parentId);
            $cache[$id] = ($parentPath ? $parentPath . ' → ' : '') . $name;
        } else {
            $cache[$id] = $name;
        }
        return $cache[$id];
    };
    foreach ($catsById as $id => $cat) { $paths[$id] = $getPath($id); }
    return $paths;
}
$categoryPaths = buildCategoryPaths($catsById);

function getAllCategoryOptions($catsById, $paths) {
    $visited = [];
    $options = '';
    $addChildren = function($parentId) use ($catsById, $paths, &$addChildren, &$visited) {
        $html = '';
        foreach ($catsById as $id => $cat) {
            if ($cat['parent_id'] == $parentId) {
                $visited[$id] = true;
                $display = isset($paths[$id]) ? sanitizeOutput($paths[$id]) : sanitizeOutput($cat['name']);
                $html .= "<option value='{$id}'>" . $display . "</option>";
                $html .= $addChildren($id);
            }
        }
        return $html;
    };
    $options = $addChildren(null);
    foreach ($catsById as $id => $cat) {
        if (!isset($visited[$id])) {
            $display = isset($paths[$id]) ? sanitizeOutput($paths[$id]) : sanitizeOutput($cat['name']);
            if ($cat['parent_id'] !== null) $display = '⚠️ ' . $display;
            $options .= "<option value='{$id}'>" . $display . "</option>";
            $options .= $addChildren($id);
        }
    }
    return $options;
}
$catSelectHTML = getAllCategoryOptions($catsById, $categoryPaths);
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
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 dark:text-gray-100">Exams</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1"><?php echo number_format($totalExams); ?> exams total</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="px-3 py-1.5 text-xs font-medium rounded-lg bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400"><?php echo $activeCount; ?> Active</span>
            <span class="px-3 py-1.5 text-xs font-medium rounded-lg bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400"><?php echo $draftCount; ?> Draft</span>
            <span class="px-3 py-1.5 text-xs font-medium rounded-lg bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300"><?php echo $completedCount; ?> Done</span>
            <button onclick="openExamModal()" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700 transition-colors flex items-center gap-2 shadow-lg shadow-indigo-200 dark:shadow-indigo-900/30">
                <i class="fa-solid fa-plus"></i>Create Exam
            </button>
        </div>
    </div>

    <!-- Search -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-4 mb-6 border border-gray-100 dark:border-gray-700">
        <div class="relative">
            <i class="fa-solid fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input type="text" id="examTreeSearch" placeholder="Search exams or categories..." class="w-full pl-11 pr-4 py-3 text-sm border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced">
        </div>
    </div>

    <!-- Tree View -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-gray-100 dark:border-gray-700 overflow-hidden" id="examTree">
        <?php if (empty($catTree)): ?>
        <div class="p-12 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center">
                <i class="fa-solid fa-folder-open text-2xl text-indigo-500"></i>
            </div>
            <p class="text-gray-500 dark:text-gray-400">No categories found. Create categories first.</p>
            <a href="index.php?page=categories" class="inline-flex items-center gap-2 mt-4 text-indigo-600 dark:text-indigo-400 hover:underline">
                <i class="fa-solid fa-layer-group"></i>Manage Categories
            </a>
        </div>
        <?php else: ?>
        <div class="p-4 space-y-2">
            <?php echo renderExamTree($catTree, $examsByCat); ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Exam Modal -->
<div id="examModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 modal-backdrop" onclick="closeExamModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4 pointer-events-none">
        <div class="modal-content bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-xl pointer-events-auto max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-gray-700 sticky top-0 bg-white dark:bg-gray-800 rounded-t-2xl">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100" id="examModalTitle">Create Exam</h3>
                <button onclick="closeExamModal()" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            <form method="POST" id="examForm" class="p-6 space-y-4">
                <?php echo getCSRFTokenField(); ?>
                <input type="hidden" name="action" id="examAction" value="add_exam">
                <input type="hidden" name="exam_id" id="examId">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Title</label>
                    <input type="text" name="title" id="examTitle" required class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Category</label>
                        <select name="category_id" id="examCategory" required class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="">Select category</option>
                            <?php echo $catSelectHTML; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Status</label>
                        <select name="status" id="examStatus" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="draft">Draft</option>
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Duration (min)</label>
                        <input type="number" name="duration_minutes" id="examDuration" value="60" required class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Total Marks</label>
                        <input type="number" name="total_marks" id="examMarks" value="100" required class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Passing %</label>
                        <input type="number" step="0.01" name="passing_percentage" id="examPassing" value="40" required class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Exam Date</label>
                    <input type="datetime-local" name="exam_date" id="examDate" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Description</label>
                    <textarea name="description" id="examDesc" rows="2" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced"></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="closeExamModal()" class="px-5 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700 transition-colors">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form id="deleteExamForm" method="POST" class="hidden"><?php echo getCSRFTokenField(); ?><input type="hidden" name="action" value="delete_exam"><input type="hidden" name="exam_id" id="deleteExamId"></form>

<script>
function toggleExamTreeNode(header, catId) {
    const childrenDiv = document.getElementById('cat-children-' + catId);
    const examsDiv = document.getElementById('cat-exams-' + catId);
    const chevron = header.querySelector('.chevron');
    if (childrenDiv) childrenDiv.classList.toggle('hidden');
    if (examsDiv) examsDiv.classList.toggle('hidden');
    if (chevron) {
        const isOpen = (examsDiv && !examsDiv.classList.contains('hidden')) || (childrenDiv && !childrenDiv.classList.contains('hidden'));
        chevron.style.transform = isOpen ? 'rotate(90deg)' : 'rotate(0deg)';
    }
}

document.getElementById('examTreeSearch')?.addEventListener('input', function() {
    const term = this.value.toLowerCase();
    document.querySelectorAll('#examTree .tree-node').forEach(node => {
        node.style.display = term === '' || node.textContent.toLowerCase().includes(term) ? '' : 'none';
    });
});

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
    confirmAction('Delete this exam? This cannot be undone.', () => {
        document.getElementById('deleteExamId').value = id;
        document.getElementById('deleteExamForm').submit();
    });
}
</script>