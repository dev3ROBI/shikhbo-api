<?php
$mysqli = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) { $error = 'CSRF validation failed.'; }
    else {
        $action = $_POST['action'] ?? '';
        if ($action === 'add_question' || $action === 'edit_question') {
            $examId = intval($_POST['exam_id']);
            $qtext = sanitize($_POST['question_text']);
            $a = sanitize($_POST['option_a']); $b = sanitize($_POST['option_b']);
            $c = sanitize($_POST['option_c']); $d = sanitize($_POST['option_d']);
            $correct = $_POST['correct_answer']; $marks = intval($_POST['marks']);
            if ($action === 'add_question') {
                $stmt = $mysqli->prepare("INSERT INTO questions (exam_id,question_text,option_a,option_b,option_c,option_d,correct_answer,marks) VALUES (?,?,?,?,?,?,?,?)");
                $stmt->bind_param('issssssi', $examId, $qtext, $a, $b, $c, $d, $correct, $marks);
            } else {
                $qid = intval($_POST['question_id']);
                $stmt = $mysqli->prepare("UPDATE questions SET exam_id=?,question_text=?,option_a=?,option_b=?,option_c=?,option_d=?,correct_answer=?,marks=? WHERE id=?");
                $stmt->bind_param('issssssii', $examId, $qtext, $a, $b, $c, $d, $correct, $marks, $qid);
            }
            $stmt->execute(); $stmt->close();
            $success = $action==='add_question'?'Question added successfully.':'Question updated successfully.';
        } elseif ($action === 'delete_question') {
            $stmt = $mysqli->prepare("DELETE FROM questions WHERE id=?");
            $stmt->bind_param('i', intval($_POST['question_id']));
            $stmt->execute(); $stmt->close();
            $success = 'Question deleted successfully.';
        }
    }
}

$examFilter = intval($_GET['exam_id'] ?? 0);
$search = sanitize($_GET['search'] ?? '');
$page_num = max(1, intval($_GET['p'] ?? 1));
$limit = 20; $offset = ($page_num-1)*$limit;

$where = "1=1"; $params = []; $types = '';
if ($examFilter) { $where .= " AND q.exam_id=?"; $params[] = $examFilter; $types .= 'i'; }
if ($search) { $where .= " AND q.question_text LIKE ?"; $params[] = "%{$search}%"; $types .= 's'; }

$countStmt = $mysqli->prepare("SELECT COUNT(*) as c FROM questions q WHERE $where");
if ($params) $countStmt->bind_param($types, ...$params);
$countStmt->execute(); $total = $countStmt->get_result()->fetch_assoc()['c']; $countStmt->close();
$totalPages = ceil($total/$limit);

$query = "SELECT q.*, e.title AS exam_title FROM questions q LEFT JOIN exams e ON q.exam_id=e.id WHERE $where ORDER BY q.id DESC LIMIT ? OFFSET ?";
$types .= 'ii'; $params[] = $limit; $params[] = $offset;
$stmt = $mysqli->prepare($query); $stmt->bind_param($types, ...$params); $stmt->execute();
$questions = $stmt->get_result(); $stmt->close();

$allCats = $mysqli->query("SELECT ec.*, COUNT(e.id) as exam_count FROM exam_categories ec LEFT JOIN exams e ON ec.id = e.category_id GROUP BY ec.id ORDER BY ec.parent_id, ec.sort_order, ec.id");
$catsById = [];
while ($c = $allCats->fetch_assoc()) { $catsById[$c['id']] = $c; }

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

function renderCategoryTreeForFilter($tree, $selectedExamId, $level = 0) {
    $html = '';
    foreach ($tree as $cat) {
        $hasChildren = !empty($cat['children']);
        $icon = $hasChildren ? 'fa-folder-open' : 'fa-folder';
        $typeColors = ['academic'=>'blue','job'=>'emerald','general'=>'purple','other'=>'gray'];
        $tc = $typeColors[$cat['category_type']] ?? 'gray';
        $html .= "<div class='tree-node'>";
        $html .= "<div class='tree-header flex items-center py-2 px-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition-all' onclick='toggleCatNode(this, {$cat['id']})'>";
        $html .= "<span class='w-6 h-6 flex items-center justify-center mr-2'>";
        $html .= $hasChildren ? "<i class='fa-solid fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500 transition-transform duration-200 chevron'></i>" : "<span class='w-2'></span>";
        $html .= "</span>";
        $html .= "<i class='fa-solid {$icon} text-{$tc}-500 dark:text-{$tc}-400 mr-2 text-sm'></i>";
        $html .= "<span class='flex-1 text-sm text-gray-700 dark:text-gray-200'>" . sanitizeOutput($cat['name']) . "</span>";
        $html .= "<span class='text-xs px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400'>{$cat['exam_count']}</span>";
        $html .= "</div>";
        $html .= "<div class='cat-exams hidden ml-8' id='cat-exams-{$cat['id']}'><div class='text-xs text-gray-400 dark:text-gray-500 py-2'>Loading...</div></div>";
        if ($hasChildren) {
            $html .= "<div class='cat-children hidden ml-6' id='cat-children-{$cat['id']}'>";
            $html .= renderCategoryTreeForFilter($cat['children'], $selectedExamId, $level + 1);
            $html .= "</div>";
        }
        $html .= "</div>";
    }
    return $html;
}

$exams = $mysqli->query("SELECT id, title FROM exams ORDER BY title");
$selectedExamTitle = $examFilter ? $mysqli->query("SELECT title FROM exams WHERE id=$examFilter")->fetch_assoc()['title'] : '';
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
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 dark:text-gray-100">Question Bank</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">
                <?php echo number_format($total); ?> questions
                <?php echo $selectedExamTitle ? '<span class="text-indigo-600 dark:text-indigo-400">• ' . sanitizeOutput($selectedExamTitle) . '</span>' : ''; ?>
            </p>
        </div>
        <button onclick="openQuestionModal()" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700 transition-colors flex items-center gap-2 shadow-lg shadow-indigo-200 dark:shadow-indigo-900/30">
            <i class="fa-solid fa-plus"></i>Add Question
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Category Tree Filter -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-gray-100 dark:border-gray-700 p-4 sticky top-4" style="max-height: calc(100vh - 120px); overflow-y: auto;">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 flex items-center gap-2">
                        <i class="fa-solid fa-layer-group text-gray-400"></i>Exam Filter
                    </h3>
                    <?php if ($examFilter): ?>
                    <a href="index.php?page=questions" class="text-xs text-red-500 hover:underline">Clear</a>
                    <?php endif; ?>
                </div>
                <div class="relative mb-4">
                    <input type="text" id="treeSearch" placeholder="Filter categories..." class="w-full pl-9 pr-3 py-2 text-xs border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                </div>
                <div id="categoryTree" class="space-y-1">
                    <?php echo renderCategoryTreeForFilter($catTree, $examFilter); ?>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <label class="text-xs text-gray-500 dark:text-gray-400 mb-2 block">Quick Select:</label>
                    <form method="GET" action="index.php">
                        <input type="hidden" name="page" value="questions">
                        <select name="exam_id" onchange="this.form.submit()" class="w-full px-3 py-2 text-xs border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg">
                            <option value="">All Exams</option>
                            <?php foreach ($exams as $ex): ?>
                            <option value="<?php echo $ex['id']; ?>" <?php echo $examFilter==$ex['id']?'selected':''; ?>><?php echo sanitizeOutput($ex['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </div>
        </div>

        <!-- Questions List -->
        <div class="lg:col-span-3">
            <!-- Search -->
            <form method="GET" class="mb-4 flex gap-2">
                <input type="hidden" name="page" value="questions">
                <?php if ($examFilter): ?><input type="hidden" name="exam_id" value="<?php echo $examFilter; ?>"><?php endif; ?>
                <div class="relative flex-1">
                    <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" name="search" value="<?php echo sanitizeOutput($search); ?>" placeholder="Search questions..." class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced">
                </div>
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700 transition-colors">Search</button>
                <?php if ($search): ?><a href="index.php?page=questions<?php echo $examFilter?'&exam_id='.$examFilter:''; ?>" class="px-5 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Clear</a><?php endif; ?>
            </form>

            <!-- Questions Cards -->
            <div class="space-y-4">
                <?php if ($questions->num_rows > 0): ?>
                    <?php $qNum = $offset + 1; while ($q = $questions->fetch_assoc()): ?>
                    <div class="question-card bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <span class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-xs font-bold text-indigo-600 dark:text-indigo-400">Q<?php echo $qNum++; ?></span>
                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400"><?php echo sanitizeOutput($q['exam_title'] ?? 'Unassigned'); ?></span>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5"><?php echo $q['marks']; ?> mark<?php echo $q['marks']>1?'s':''; ?></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button onclick="editQuestion(<?php echo htmlspecialchars(json_encode($q), ENT_QUOTES, 'UTF-8'); ?>)" class="p-2 text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors" title="Edit">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <button onclick="deleteQuestion(<?php echo $q['id']; ?>)" class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors" title="Delete">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <p class="text-gray-800 dark:text-gray-100 font-medium mb-4"><?php echo sanitizeOutput($q['question_text']); ?></p>
                        <div class="grid grid-cols-2 gap-2">
                            <?php foreach(['a','b','c','d'] as $opt): ?>
                            <div class="text-sm px-4 py-2.5 rounded-xl <?php echo strtolower($q['correct_answer'])===$opt ? 'bg-green-100 dark:bg-green-900/30 border-2 border-green-300 dark:border-green-700 text-green-800 dark:text-green-300 font-semibold' : 'bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300'; ?>">
                                <span class="font-bold mr-2"><?php echo strtoupper($opt); ?>.</span><?php echo sanitizeOutput($q['option_'.$opt]); ?>
                                <?php if (strtolower($q['correct_answer'])===$opt): ?><i class="fa-solid fa-check ml-2 text-green-600 dark:text-green-400"></i><?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                <div class="text-center py-16 bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-gray-100 dark:border-gray-700">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                        <i class="fa-solid fa-database text-2xl text-gray-400 dark:text-gray-500"></i>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400 text-lg">No questions found</p>
                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">Add your first question using the button above</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="mt-6 flex justify-center">
                <div class="flex items-center gap-1">
                    <?php for ($i=1;$i<=$totalPages;$i++): ?>
                    <a href="index.php?page=questions&p=<?php echo $i; ?>&exam_id=<?php echo $examFilter; ?>&search=<?php echo urlencode($search); ?>" 
                       class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-medium transition-colors <?php echo $i===$page_num?'bg-indigo-600 text-white':'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Question Modal -->
<div id="questionModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 modal-backdrop" onclick="closeQuestionModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4 pointer-events-none">
        <div class="modal-content bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl pointer-events-auto max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-gray-700 sticky top-0 bg-white dark:bg-gray-800 rounded-t-2xl">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100" id="qModalTitle">Add Question</h3>
                <button onclick="closeQuestionModal()" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            <form method="POST" id="questionForm" class="p-6 space-y-4">
                <?php echo getCSRFTokenField(); ?>
                <input type="hidden" name="action" id="qAction" value="add_question">
                <input type="hidden" name="question_id" id="qId">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Exam</label>
                        <select name="exam_id" id="qExam" required class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="">Select exam</option>
                            <?php foreach ($exams as $ex): ?>
                            <option value="<?php echo $ex['id']; ?>"><?php echo sanitizeOutput($ex['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Marks</label>
                        <input type="number" name="marks" id="qMarks" value="1" required class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Question</label>
                    <textarea name="question_text" id="qText" rows="3" required class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced" placeholder="Enter question text..."></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <?php foreach(['a','b','c','d'] as $opt): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Option <?php echo strtoupper($opt); ?></label>
                        <input type="text" name="option_<?php echo $opt; ?>" id="qOpt<?php echo strtoupper($opt); ?>" required class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced">
                    </div>
                    <?php endforeach; ?>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Correct Answer</label>
                    <select name="correct_answer" id="qCorrect" required class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="a">A</option><option value="b">B</option><option value="c">C</option><option value="d">D</option>
                    </select>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="closeQuestionModal()" class="px-5 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700 transition-colors">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form id="deleteQForm" method="POST" class="hidden"><?php echo getCSRFTokenField(); ?><input type="hidden" name="action" value="delete_question"><input type="hidden" name="question_id" id="deleteQId"></form>

<script>
function toggleCatNode(header, catId) {
    const childrenDiv = document.getElementById('cat-children-' + catId);
    const examsDiv = document.getElementById('cat-exams-' + catId);
    const chevron = header.querySelector('.chevron');
    if (childrenDiv && !childrenDiv.classList.contains('hidden')) {
        childrenDiv.classList.add('hidden');
        if (chevron) chevron.style.transform = 'rotate(0deg)';
    } else if (childrenDiv) {
        childrenDiv.classList.remove('hidden');
        if (chevron) chevron.style.transform = 'rotate(90deg)';
    }
    if (examsDiv && examsDiv.querySelector('.text-xs.text-gray-400, .text-xs.dark\\:text-gray-500')) {
        fetch(`/api/get_exams_by_category_web.php?category_id=${catId}&direct=1`).then(r => r.json()).then(data => {
            if (data.status === 'success' && data.exams.length > 0) {
                examsDiv.innerHTML = data.exams.map(e => `<a href="index.php?page=questions&exam_id=${e.id}" class="block text-xs py-2 px-3 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/30 text-gray-600 dark:text-gray-300 transition-colors"><i class="fa-solid fa-file-alt mr-2 text-gray-400"></i>${e.title}</a>`).join('');
            } else {
                examsDiv.innerHTML = '<div class="text-xs text-gray-400 dark:text-gray-500 py-2">No exams</div>';
            }
            examsDiv.classList.remove('hidden');
        });
    } else if (examsDiv) {
        examsDiv.classList.toggle('hidden');
    }
}

document.getElementById('treeSearch')?.addEventListener('input', function() {
    const term = this.value.toLowerCase();
    document.querySelectorAll('#categoryTree .tree-node').forEach(node => {
        node.style.display = term === '' || node.textContent.toLowerCase().includes(term) ? '' : 'none';
    });
});

function openQuestionModal(q=null) {
    document.getElementById('questionModal').classList.remove('hidden');
    if (q) {
        document.getElementById('qModalTitle').textContent='Edit Question';
        document.getElementById('qAction').value='edit_question';
        document.getElementById('qId').value=q.id;
        document.getElementById('qExam').value=q.exam_id;
        document.getElementById('qText').value=q.question_text;
        document.getElementById('qOptA').value=q.option_a;
        document.getElementById('qOptB').value=q.option_b;
        document.getElementById('qOptC').value=q.option_c;
        document.getElementById('qOptD').value=q.option_d;
        document.getElementById('qCorrect').value=q.correct_answer.toLowerCase();
        document.getElementById('qMarks').value=q.marks;
    } else {
        document.getElementById('qModalTitle').textContent='Add Question';
        document.getElementById('qAction').value='add_question';
        document.getElementById('qId').value='';
        document.getElementById('questionForm').reset();
    }
}
function closeQuestionModal(){document.getElementById('questionModal').classList.add('hidden');}
function editQuestion(q){openQuestionModal(q);}
function deleteQuestion(id){
    confirmAction('Delete this question?', () => {
        document.getElementById('deleteQId').value = id;
        document.getElementById('deleteQForm').submit();
    });
}
</script>