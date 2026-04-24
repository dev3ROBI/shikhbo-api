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

$exams = $mysqli->query("SELECT id, title FROM exams ORDER BY title");
$selectedExamTitle = $examFilter ? $mysqli->query("SELECT title FROM exams WHERE id=$examFilter")->fetch_assoc()['title'] : '';
?>

<?php if (isset($error)): ?><div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4"><?php echo sanitizeOutput($error); ?></div><?php endif; ?>
<?php if (isset($success)): ?><div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4"><?php echo sanitizeOutput($success); ?></div><?php endif; ?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Question Bank</h1>
        <p class="text-gray-500 mt-1"><?php echo $total; ?> questions total<?php echo $selectedExamTitle ? ' • '.sanitizeOutput($selectedExamTitle) : ''; ?></p>
    </div>
    <button onclick="openQuestionModal()" class="px-4 py-2 bg-shikhbo-primary text-white rounded-lg text-sm hover:bg-indigo-700">
        <i class="fa-solid fa-plus mr-2"></i>Add Question
    </button>
</div>

<!-- Filters -->
<form method="GET" class="mb-6 flex flex-wrap gap-2 items-end">
    <input type="hidden" name="page" value="questions">
    <div>
        <label class="block text-xs font-medium text-gray-500 mb-1">Exam</label>
        <select name="exam_id" class="px-3 py-2 border border-gray-300 rounded-lg text-sm" onchange="this.form.submit()">
            <option value="">All Exams</option>
            <?php foreach ($exams as $ex): ?>
                <option value="<?php echo $ex['id']; ?>" <?php echo $examFilter==$ex['id']?'selected':''; ?>><?php echo sanitizeOutput($ex['title']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
        <input type="text" name="search" value="<?php echo sanitizeOutput($search); ?>" placeholder="Question text..." class="px-3 py-2 border border-gray-300 rounded-lg text-sm w-48">
    </div>
    <button type="submit" class="px-4 py-2 bg-shikhbo-primary text-white rounded-lg text-sm">Filter</button>
    <a href="index.php?page=questions" class="px-4 py-2 border border-gray-300 rounded-lg text-sm">Reset</a>
</form>

<!-- Questions Grid -->
<div class="space-y-4">
    <?php if ($questions->num_rows > 0): ?>
        <?php $qNum = $offset + 1; while ($q = $questions->fetch_assoc()): ?>
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-5 hover:shadow-lg transition-all">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex-1">
                        <span class="text-xs text-gray-400 font-mono">Q<?php echo $qNum++; ?></span>
                        <span class="text-xs text-gray-400 ml-3"><?php echo sanitizeOutput($q['exam_title'] ?? 'Unassigned'); ?></span>
                        <p class="text-gray-800 font-medium mt-1"><?php echo sanitizeOutput($q['question_text']); ?></p>
                    </div>
                    <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-full ml-2"><?php echo $q['marks']; ?> mark<?php echo $q['marks']>1?'s':''; ?></span>
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

<?php if ($totalPages > 1): ?>
    <div class="mt-6 flex justify-center space-x-1">
        <?php for ($i=1;$i<=$totalPages;$i++): ?>
            <a href="index.php?page=questions&p=<?php echo $i; ?>&exam_id=<?php echo $examFilter; ?>&search=<?php echo urlencode($search); ?>" class="px-3 py-1 text-sm border rounded <?php echo $i===$page_num?'bg-shikhbo-primary text-white':'hover:bg-gray-100'; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
    </div>
<?php endif; ?>

<!-- Add/Edit Modal (same structure as before) -->
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
                <?php foreach(['a','b','c','d'] as $i => $opt): ?>
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