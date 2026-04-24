<?php
$mysqli = getDBConnection();

// ── Handle CRUD (unchanged logic, bind‑param fix included) ──
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
            $success = $action==='add_exam'?'Exam created.':'Exam updated.';
        } elseif ($action === 'delete_exam') {
            $stmt = $mysqli->prepare("DELETE FROM exams WHERE id=?"); $stmt->bind_param('i', intval($_POST['exam_id']));
            $stmt->execute(); $stmt->close(); $success = 'Exam deleted.';
        }
    }
}

// ── Build Category Tree with nested exams ──
$allCats = $mysqli->query("SELECT ec.*, COUNT(e.id) as exam_count FROM exam_categories ec LEFT JOIN exams e ON ec.id=e.category_id GROUP BY ec.id ORDER BY ec.parent_id, ec.sort_order, ec.id");
$catsById = []; while ($c = $allCats->fetch_assoc()) { $catsById[$c['id']] = $c; }

// Fetch exams grouped by category
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
    $typeColors = ['academic'=>'blue','job'=>'green','general'=>'purple','other'=>'gray'];
    foreach ($tree as $cat) {
        $tc = $typeColors[$cat['category_type']] ?? 'gray';
        $hasChildren = !empty($cat['children']);
        $catExams = $examsByCat[$cat['id']] ?? [];
        $totalExamsInCat = count($catExams);

        $html .= "<div class='tree-node' data-cat-id='{$cat['id']}'>";
        $html .= "<div class='flex items-center py-2.5 px-3 rounded-lg hover:bg-gray-100 cursor-pointer transition-colors group' onclick='toggleExamTreeNode(this, {$cat['id']})'>";
        $html .= "<span class='w-5 h-5 flex items-center justify-center mr-2 text-gray-400 flex-shrink-0'>";
        $html .= ($hasChildren || $totalExamsInCat > 0) ? "<i class='fa-solid fa-chevron-right text-[10px] transition-transform duration-200 chevron'></i>" : "<span class='w-3'></span>";
        $html .= "</span>";
        $html .= "<i class='fa-solid fa-folder-tree text-{$tc}-400 mr-2 text-sm flex-shrink-0'></i>";
        $html .= "<span class='text-sm text-gray-700 flex-1 min-w-0 truncate'>" . sanitizeOutput($cat['name']) . "</span>";
        $html .= "<span class='text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full flex-shrink-0 ml-2'>{$totalExamsInCat}</span>";
        $html .= "</div>";

        // Exams under this category
        $html .= "<div class='cat-exams hidden ml-7' id='cat-exams-{$cat['id']}'>";
        if ($totalExamsInCat > 0) {
            foreach ($catExams as $exam) {
                $statusColors = ['active'=>'green','draft'=>'yellow','completed'=>'gray'];
                $sc = $statusColors[$exam['status']] ?? 'gray';
                $html .= "<div class='ml-3 border-l-2 border-gray-200 pl-3 py-1.5 hover:bg-gray-50 rounded-r-lg transition-colors group/exam'>";
                $html .= "<div class='flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1'>";
                $html .= "<div class='min-w-0 flex-1'><p class='text-sm font-medium text-gray-800 truncate'>" . sanitizeOutput($exam['title']) . "</p>";
                if ($exam['description']) $html .= "<p class='text-xs text-gray-400 truncate'>" . sanitizeOutput(substr($exam['description'],0,80)) . "</p>";
                $html .= "</div>";
                $html .= "<div class='flex items-center gap-2 flex-shrink-0'>";
                $html .= "<span class='text-xs text-gray-500'>{$exam['duration_minutes']}m · {$exam['total_marks']}pts</span>";
                $html .= "<span class='px-1.5 py-0.5 text-[10px] font-semibold rounded-full bg-{$sc}-100 text-{$sc}-700'>" . ucfirst($exam['status']) . "</span>";
                $html .= "</div></div>";
                $html .= "<div class='flex items-center gap-2 mt-1 text-xs'>";
                $html .= "<a href='index.php?page=exam_attempt&exam_id={$exam['id']}' class='text-green-600 hover:underline'><i class='fa-solid fa-play mr-0.5'></i>Attempt</a>";
                $html .= "<a href='index.php?page=questions&exam_id={$exam['id']}' class='text-blue-600 hover:underline'><i class='fa-solid fa-list-check mr-0.5'></i>Questions</a>";
                $html .= "<button onclick='editExam(" . htmlspecialchars(json_encode($exam), ENT_QUOTES, 'UTF-8') . ")' class='text-shikhbo-primary hover:underline'><i class='fa-solid fa-pen-to-square mr-0.5'></i>Edit</button>";
                $html .= "<button onclick='deleteExam({$exam['id']})' class='text-red-600 hover:underline'><i class='fa-solid fa-trash mr-0.5'></i>Delete</button>";
                $html .= "</div></div>";
            }
        } else {
            $html .= "<div class='text-xs text-gray-400 py-2 pl-3'>No exams in this category</div>";
        }
        $html .= "</div>";

        // Children
        if ($hasChildren) {
            $html .= "<div class='cat-children hidden ml-5' id='cat-children-{$cat['id']}'>";
            $html .= renderExamTree($cat['children'], $examsByCat, $level+1);
            $html .= "</div>";
        }
        $html .= "</div>";
    }
    return $html;
}

// Stats
$totalExams = $mysqli->query("SELECT COUNT(*) as c FROM exams")->fetch_assoc()['c'];
$activeCount = $mysqli->query("SELECT COUNT(*) as c FROM exams WHERE status='active'")->fetch_assoc()['c'];
$draftCount = $mysqli->query("SELECT COUNT(*) as c FROM exams WHERE status='draft'")->fetch_assoc()['c'];
$completedCount = $mysqli->query("SELECT COUNT(*) as c FROM exams WHERE status='completed'")->fetch_assoc()['c'];

$catSelectHTML = '';
function catSelectOptions($tree, $parent=null, $level=0) {
    $h = '';
    foreach ($tree as $id => $cat) {
        if ($cat['parent_id'] == $parent) {
            $h .= "<option value='{$id}'>" . str_repeat('— ', $level) . sanitizeOutput($cat['name']) . "</option>";
            if (!empty($cat['children'])) $h .= catSelectOptions($cat['children'], $id, $level+1);
        }
    }
    return $h;
}
$catSelectHTML = catSelectOptions($catTree);
?>

<?php if (isset($error)): ?><div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4 text-sm"><?php echo sanitizeOutput($error); ?></div><?php endif; ?>
<?php if (isset($success)): ?><div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4 text-sm"><?php echo sanitizeOutput($success); ?></div><?php endif; ?>

<!-- Header -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Exams Management</h1>
        <p class="text-gray-500 text-sm mt-1"><?php echo $totalExams; ?> exams total</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-700 font-medium"><?php echo $activeCount; ?> Active</span>
        <span class="text-xs px-2 py-1 rounded-full bg-yellow-100 text-yellow-700 font-medium"><?php echo $draftCount; ?> Draft</span>
        <span class="text-xs px-2 py-1 rounded-full bg-gray-200 text-gray-700 font-medium"><?php echo $completedCount; ?> Done</span>
        <button onclick="openExamModal()" class="px-4 py-2 bg-shikhbo-primary text-white rounded-lg text-sm hover:bg-indigo-700 transition-colors shadow-sm flex-shrink-0">
            <i class="fa-solid fa-plus mr-1.5"></i>Create Exam
        </button>
    </div>
</div>

<!-- Tree Search -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-3 mb-4">
    <div class="relative">
        <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
        <input type="text" id="examTreeSearch" placeholder="Search exams or categories..." class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-shikhbo-primary focus:border-transparent outline-none">
    </div>
</div>

<!-- Tree View -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4" id="examTree">
    <?php echo renderExamTree($catTree, $examsByCat); ?>
</div>

<!-- Modal for Add/Edit -->
<div id="examModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
    <div class="absolute inset-0 bg-black bg-opacity-50" onclick="closeExamModal()"></div>
    <div class="relative bg-white rounded-xl shadow-xl w-full max-w-xl mx-4 p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4"><h3 class="text-lg font-semibold" id="examModalTitle">Create Exam</h3><button onclick="closeExamModal()" class="text-gray-400 hover:text-gray-600"><i class="fa-solid fa-xmark text-xl"></i></button></div>
        <form method="POST" id="examForm">
            <?php echo getCSRFTokenField(); ?>
            <input type="hidden" name="action" id="examAction" value="add_exam"><input type="hidden" name="exam_id" id="examId">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Title</label><input type="text" name="title" id="examTitle" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Category</label><select name="category_id" id="examCategory" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"><option value="">— Select —</option><?php echo $catSelectHTML; ?></select></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Status</label><select name="status" id="examStatus" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"><option value="draft">Draft</option><option value="active" selected>Active</option><option value="completed">Completed</option></select></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Duration (min)</label><input type="number" name="duration_minutes" id="examDuration" value="60" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Total Marks</label><input type="number" name="total_marks" id="examMarks" value="100" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Passing %</label><input type="number" step="0.01" name="passing_percentage" id="examPassing" value="40" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Exam Date</label><input type="datetime-local" name="exam_date" id="examDate" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></div>
                <div class="sm:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Description</label><textarea name="description" id="examDesc" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></textarea></div>
            </div>
            <div class="mt-6 flex justify-end space-x-3"><button type="button" onclick="closeExamModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm">Cancel</button><button type="submit" class="px-4 py-2 bg-shikhbo-primary text-white rounded-lg text-sm">Save</button></div>
        </form>
    </div>
</div>

<form id="deleteExamForm" method="POST" style="display:none;"><?php echo getCSRFTokenField(); ?><input type="hidden" name="action" value="delete_exam"><input type="hidden" name="exam_id" id="deleteExamId"></form>

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
        document.getElementById('examAction').value='edit_exam'; document.getElementById('examId').value=exam.id;
        document.getElementById('examTitle').value=exam.title; document.getElementById('examCategory').value=exam.category_id||'';
        document.getElementById('examDuration').value=exam.duration_minutes; document.getElementById('examMarks').value=exam.total_marks;
        document.getElementById('examPassing').value=exam.passing_percentage; document.getElementById('examStatus').value=exam.status;
        document.getElementById('examDate').value=exam.exam_date?exam.exam_date.replace(' ','T'):'';
        document.getElementById('examDesc').value=exam.description||'';
    } else {
        document.getElementById('examModalTitle').textContent='Create Exam'; document.getElementById('examAction').value='add_exam';
        document.getElementById('examId').value=''; document.getElementById('examForm').reset();
    }
}
function closeExamModal(){document.getElementById('examModal').classList.add('hidden');}
function editExam(e){openExamModal(e);}
function deleteExam(id){if(confirm('Delete this exam?')){document.getElementById('deleteExamId').value=id;document.getElementById('deleteExamForm').submit();}}
</script>