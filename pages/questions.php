<?php
$mysqli = getDBConnection();

// Handle form submissions (unchanged)
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
            $success = $action==='add_question'?'Question added.':'Question updated.';
        } elseif ($action === 'delete_question') {
            $stmt = $mysqli->prepare("DELETE FROM questions WHERE id=?");
            $stmt->bind_param('i', intval($_POST['question_id']));
            $stmt->execute(); $stmt->close();
            $success = 'Question deleted.';
        }
    }
}

// Filters
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

// === TREE DATA for Category Browser ===
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
        $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
        $hasChildren = !empty($cat['children']);
        $icon = $hasChildren ? 'fa-folder-open' : 'fa-folder';
        $typeColors = ['academic'=>'blue','job'=>'green','general'=>'purple','other'=>'gray'];
        $tc = $typeColors[$cat['category_type']] ?? 'gray';
        $html .= "<div class='tree-node' data-cat-id='{$cat['id']}' data-level='{$level}'>";
        $html .= "<div class='flex items-center py-2 px-2 rounded-lg hover:bg-gray-100 cursor-pointer transition-colors group' onclick='toggleCatNode(this, {$cat['id']})'>";
        $html .= "<span class='w-5 h-5 flex items-center justify-center mr-2 text-gray-400'>";
        $html .= $hasChildren ? "<i class='fa-solid fa-chevron-right text-[10px] transition-transform duration-200 chevron'></i>" : "<span class='w-3'></span>";
        $html .= "</span>";
        $html .= "<i class='fa-solid {$icon} text-{$tc}-400 mr-2 text-sm'></i>";
        $html .= "<span class='text-sm text-gray-700 flex-1'>" . sanitizeOutput($cat['name']) . "</span>";
        $html .= "<span class='text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full'>{$cat['exam_count']}</span>";
        $html .= "</div>";

        // Load exams for this category (AJAX loaded)
        $html .= "<div class='cat-exams hidden ml-7' id='cat-exams-{$cat['id']}'>";
        $html .= "<div class='text-xs text-gray-400 py-1'>Loading exams...</div>";
        $html .= "</div>";

        // Children container
        if ($hasChildren) {
            $html .= "<div class='cat-children hidden ml-5' id='cat-children-{$cat['id']}'>";
            $html .= renderCategoryTreeForFilter($cat['children'], $selectedExamId, $level + 1);
            $html .= "</div>";
        }
        $html .= "</div>";
    }
    return $html;
}

// Get flat list of exams for dropdown (fallback)
$exams = $mysqli->query("SELECT id, title FROM exams ORDER BY title");
$selectedExamTitle = $examFilter ? $mysqli->query("SELECT title FROM exams WHERE id=$examFilter")->fetch_assoc()['title'] : '';
?>

<?php if (isset($error)): ?><div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4"><?php echo sanitizeOutput($error); ?></div><?php endif; ?>
<?php if (isset($success)): ?><div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4"><?php echo sanitizeOutput($success); ?></div><?php endif; ?>

<!-- Header -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Question Bank</h1>
        <p class="text-gray-500 mt-1"><?php echo $total; ?> questions total<?php echo $selectedExamTitle ? ' • <span class=\"text-shikhbo-primary font-medium\">' . sanitizeOutput($selectedExamTitle) . '</span>' : ''; ?></p>
    </div>
    <button onclick="openQuestionModal()" class="px-4 py-2 bg-shikhbo-primary text-white rounded-lg text-sm hover:bg-indigo-700 transition-colors shadow-md shadow-indigo-200">
        <i class="fa-solid fa-plus mr-2"></i>Add Question
    </button>
</div>

<!-- Two-Column Layout: Tree + Questions -->
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <!-- Left: Category Tree Filter -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl shadow-md p-4 sticky top-4" style="max-height: calc(100vh - 120px); overflow-y: auto;">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider"><i class="fa-solid fa-layer-group mr-2 text-gray-400"></i>Exam Filter</h3>
                <?php if ($examFilter): ?>
                    <a href="index.php?page=questions" class="text-xs text-red-500 hover:underline">Clear</a>
                <?php endif; ?>
            </div>

            <!-- Search within tree -->
            <div class="relative mb-3">
                <input type="text" id="treeSearch" placeholder="Filter categories..." 
                       class="w-full pl-8 pr-3 py-1.5 text-xs border border-gray-200 rounded-lg focus:ring-2 focus:ring-shikhbo-primary focus:border-transparent outline-none">
                <i class="fa-solid fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
            </div>

            <!-- Tree -->
            <div id="categoryTree" class="space-y-0.5 text-sm">
                <?php echo renderCategoryTreeForFilter($catTree, $examFilter); ?>
            </div>

            <!-- Quick Exam Select (fallback) -->
            <div class="mt-4 pt-3 border-t border-gray-100">
                <label class="text-xs text-gray-500 mb-1 block">Or select exam:</label>
                <form method="GET" action="index.php" id="quickExamForm">
                    <input type="hidden" name="page" value="questions">
                    <select name="exam_id" onchange="this.form.submit()" class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-lg">
                        <option value="">All Exams</option>
                        <?php foreach ($exams as $ex): ?>
                            <option value="<?php echo $ex['id']; ?>" <?php echo $examFilter==$ex['id']?'selected':''; ?>><?php echo sanitizeOutput($ex['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>
    </div>

    <!-- Right: Questions List -->
    <div class="lg:col-span-3">
        <!-- Search bar -->
        <form method="GET" class="mb-4 flex gap-2">
            <input type="hidden" name="page" value="questions">
            <?php if ($examFilter): ?><input type="hidden" name="exam_id" value="<?php echo $examFilter; ?>"><?php endif; ?>
            <input type="text" name="search" value="<?php echo sanitizeOutput($search); ?>" placeholder="Search questions..." class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-shikhbo-primary outline-none">
            <button type="submit" class="px-4 py-2 bg-shikhbo-primary text-white rounded-lg text-sm hover:bg-indigo-700 transition-colors">Search</button>
            <?php if ($search): ?><a href="index.php?page=questions<?php echo $examFilter?'&exam_id='.$examFilter:''; ?>" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50">Clear</a><?php endif; ?>
        </form>

        <!-- Questions List -->
        <div class="space-y-4">
            <?php if ($questions->num_rows > 0): ?>
                <?php $qNum = $offset + 1; while ($q = $questions->fetch_assoc()): ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition-all question-card">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-xs text-gray-400 font-mono bg-gray-100 px-2 py-0.5 rounded">Q<?php echo $qNum++; ?></span>
                                    <span class="text-xs text-gray-400"><?php echo sanitizeOutput($q['exam_title'] ?? 'Unassigned'); ?></span>
                                    <span class="text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full"><?php echo $q['marks']; ?> mark<?php echo $q['marks']>1?'s':''; ?></span>
                                </div>
                                <p class="text-gray-800 font-medium mt-2"><?php echo sanitizeOutput($q['question_text']); ?></p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mb-3">
                            <?php foreach(['a','b','c','d'] as $opt): ?>
                                <div class="text-sm px-3 py-2 rounded-lg <?php echo strtolower($q['correct_answer'])===$opt ? 'bg-green-50 border border-green-200 text-green-800 font-medium' : 'bg-gray-50 text-gray-600'; ?>">
                                    <span class="font-semibold mr-1"><?php echo strtoupper($opt); ?>.</span><?php echo sanitizeOutput($q['option_'.$opt]); ?>
                                    <?php if (strtolower($q['correct_answer'])===$opt): ?><i class="fa-solid fa-check text-green-600 ml-2"></i><?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="flex items-center space-x-3 text-sm">
                            <button onclick="editQuestion(<?php echo htmlspecialchars(json_encode($q), ENT_QUOTES, 'UTF-8'); ?>)" class="text-shikhbo-primary hover:underline"><i class="fa-solid fa-pen-to-square mr-1"></i>Edit</button>
                            <button onclick="deleteQuestion(<?php echo $q['id']; ?>)" class="text-red-600 hover:underline"><i class="fa-solid fa-trash mr-1"></i>Delete</button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-16 text-gray-500">
                    <i class="fa-solid fa-database text-4xl mb-3 block"></i>
                    <p class="text-lg">No questions found.</p>
                    <p class="text-sm mt-1">Add your first question using the button above.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="mt-6 flex justify-center space-x-1">
                <?php for ($i=1;$i<=$totalPages;$i++): ?>
                    <a href="index.php?page=questions&p=<?php echo $i; ?>&exam_id=<?php echo $examFilter; ?>&search=<?php echo urlencode($search); ?>" class="px-3 py-1 text-sm border rounded <?php echo $i===$page_num?'bg-shikhbo-primary text-white border-shikhbo-primary':'hover:bg-gray-100'; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add/Edit Question Modal (same structure as before) -->
<div id="questionModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
    <div class="absolute inset-0 bg-black bg-opacity-50" onclick="closeQuestionModal()"></div>
    <div class="relative bg-white rounded-xl shadow-xl w-full max-w-2xl mx-4 p-6 max-h-screen overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold" id="qModalTitle">Add Question</h3>
            <button onclick="closeQuestionModal()" class="text-gray-400 hover:text-gray-600"><i class="fa-solid fa-xmark text-xl"></i></button>
        </div>
        <form method="POST" id="questionForm">
            <?php echo getCSRFTokenField(); ?>
            <input type="hidden" name="action" id="qAction" value="add_question">
            <input type="hidden" name="question_id" id="qId">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Exam</label>
                    <select name="exam_id" id="qExam" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="">Select</option>
                        <?php foreach ($exams as $ex): ?>
                            <option value="<?php echo $ex['id']; ?>"><?php echo sanitizeOutput($ex['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Marks</label>
                    <input type="number" name="marks" id="qMarks" value="1" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Question</label>
                    <textarea name="question_text" id="qText" rows="3" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></textarea>
                </div>
                <?php foreach(['a','b','c','d'] as $opt): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Option <?php echo strtoupper($opt); ?></label>
                        <input type="text" name="option_<?php echo $opt; ?>" id="qOpt<?php echo strtoupper($opt); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                <?php endforeach; ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Correct Answer</label>
                    <select name="correct_answer" id="qCorrect" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="a">A</option><option value="b">B</option><option value="c">C</option><option value="d">D</option>
                    </select>
                </div>
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" onclick="closeQuestionModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-shikhbo-primary text-white rounded-lg text-sm">Save</button>
            </div>
        </form>
    </div>
</div>

<form id="deleteQForm" method="POST" style="display:none;">
    <?php echo getCSRFTokenField(); ?>
    <input type="hidden" name="action" value="delete_question">
    <input type="hidden" name="question_id" id="deleteQId">
</form>

<script>
// === Category Tree Toggle ===
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
    
// Load exams for this category via AJAX
if (examsDiv && examsDiv.querySelector('.text-xs.text-gray-400')) {
    fetch(`/api/get_exams_by_category.php?category_id=${catId}&direct=1`)
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success' && data.exams.length > 0) {
                examsDiv.innerHTML = data.exams.map(e => `
                    <a href="index.php?page=questions&exam_id=${e.id}" class="block text-xs py-1.5 px-2 rounded hover:bg-indigo-50 text-gray-600 hover:text-shikhbo-primary transition-colors">
                        <i class="fa-solid fa-file-alt mr-1 text-gray-400"></i>${e.title}
                    </a>
                `).join('');
            } else {
                examsDiv.innerHTML = '<div class="text-xs text-gray-400 py-1">No exams</div>';
            }
            examsDiv.classList.remove('hidden');
        });
} else if (examsDiv) {
    examsDiv.classList.toggle('hidden');
}
}

// Tree search filter
document.getElementById('treeSearch')?.addEventListener('input', function() {
    const term = this.value.toLowerCase();
    document.querySelectorAll('#categoryTree .tree-node').forEach(node => {
        const text = node.textContent.toLowerCase();
        node.style.display = term === '' || text.includes(term) ? '' : 'none';
    });
});

// Question modal functions (unchanged)
function openQuestionModal(q=null){
    document.getElementById('questionModal').classList.remove('hidden');
    if(q){
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
    }else{
        document.getElementById('qModalTitle').textContent='Add Question';
        document.getElementById('qAction').value='add_question';
        document.getElementById('qId').value='';
        document.getElementById('questionForm').reset();
    }
}
function closeQuestionModal(){document.getElementById('questionModal').classList.add('hidden');}
function editQuestion(q){openQuestionModal(q);}
function deleteQuestion(id){
    if(confirm('Delete this question?')){
        document.getElementById('deleteQId').value=id;
        document.getElementById('deleteQForm').submit();
    }
}
</script>