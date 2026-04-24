<?php
$mysqli = getDBConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token validation failed.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add_question' || $action === 'edit_question') {
            $examId = intval($_POST['exam_id']);
            $qtext = sanitize($_POST['question_text']);
            $a = sanitize($_POST['option_a']);
            $b = sanitize($_POST['option_b']);
            $c = sanitize($_POST['option_c']);
            $d = sanitize($_POST['option_d']);
            $correct = $_POST['correct_answer'];
            $marks = intval($_POST['marks']);

            if ($action === 'add_question') {
                $stmt = $mysqli->prepare("INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_answer, marks) VALUES (?,?,?,?,?,?,?,?)");
                $stmt->bind_param('issssssi', $examId, $qtext, $a, $b, $c, $d, $correct, $marks);
            } else {
                $qid = intval($_POST['question_id']);
                $stmt = $mysqli->prepare("UPDATE questions SET exam_id=?, question_text=?, option_a=?, option_b=?, option_c=?, option_d=?, correct_answer=?, marks=? WHERE id=?");
                $stmt->bind_param('issssssii', $examId, $qtext, $a, $b, $c, $d, $correct, $marks, $qid);
            }
            $stmt->execute();
            $stmt->close();
            $success = $action === 'add_question' ? 'Question added.' : 'Question updated.';
        }

        if ($action === 'delete_question') {
            $qid = intval($_POST['question_id']);
            $stmt = $mysqli->prepare("DELETE FROM questions WHERE id = ?");
            $stmt->bind_param('i', $qid);
            $stmt->execute();
            $stmt->close();
            $success = 'Question deleted.';
        }
    }
}

// Exam filter
$examFilter = intval($_GET['exam_id'] ?? 0);
$search = sanitize($_GET['search'] ?? '');
$page = max(1, intval($_GET['p'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$where = "1=1";
$params = [];
$types = '';
if ($examFilter) {
    $where .= " AND q.exam_id = ?";
    $params[] = $examFilter;
    $types .= 'i';
}
if ($search) {
    $where .= " AND q.question_text LIKE ?";
    $params[] = "%{$search}%";
    $types .= 's';
}

$countStmt = $mysqli->prepare("SELECT COUNT(*) as total FROM questions q WHERE {$where}");
if ($params) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();
$totalPages = ceil($total / $limit);

$query = "SELECT q.*, e.title AS exam_title FROM questions q LEFT JOIN exams e ON q.exam_id = e.id WHERE {$where} ORDER BY q.id DESC LIMIT ? OFFSET ?";
$types .= 'ii';
$params[] = $limit;
$params[] = $offset;
$stmt = $mysqli->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$questions = $stmt->get_result();
$stmt->close();

// Get exams for filter dropdown
$exams = $mysqli->query("SELECT id, title FROM exams ORDER BY title");
?>

<?php if (isset($error)): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4"><?php echo sanitizeOutput($error); ?></div>
<?php endif; ?>
<?php if (isset($success)): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4"><?php echo sanitizeOutput($success); ?></div>
<?php endif; ?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Question Bank</h1>
    <button onclick="openQuestionModal()" class="px-4 py-2 bg-shikhbo-primary text-white rounded-lg text-sm hover:bg-indigo-700">
        <i class="fa-solid fa-plus mr-2"></i>Add Question
    </button>
</div>

<!-- Filters -->
<form method="GET" class="mb-6 flex flex-wrap gap-3 items-end">
    <input type="hidden" name="page" value="questions">
    <div>
        <label class="block text-xs font-medium text-gray-500 mb-1">Exam</label>
        <select name="exam_id" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <option value="">All Exams</option>
            <?php foreach ($exams as $exam): ?>
                <option value="<?php echo $exam['id']; ?>" <?php echo $examFilter == $exam['id'] ? 'selected' : ''; ?>><?php echo sanitizeOutput($exam['title']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
        <input type="text" name="search" value="<?php echo sanitizeOutput($search); ?>" placeholder="Text..." class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
    </div>
    <button type="submit" class="px-4 py-2 bg-shikhbo-primary text-white rounded-lg text-sm">Filter</button>
    <a href="index.php?page=questions" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50">Reset</a>
</form>

<!-- Table -->
<div class="bg-white rounded-xl shadow-md overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Exam</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Question</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Options</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Correct</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Marks</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if ($questions->num_rows > 0): ?>
                <?php while ($q = $questions->fetch_assoc()): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm text-gray-600"><?php echo sanitizeOutput($q['exam_title'] ?? '—'); ?></td>
                        <td class="px-6 py-4 text-sm text-gray-800 max-w-xs truncate"><?php echo sanitizeOutput($q['question_text']); ?></td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            A) <?php echo sanitizeOutput($q['option_a']); ?><br>
                            B) <?php echo sanitizeOutput($q['option_b']); ?><br>
                            C) <?php echo sanitizeOutput($q['option_c']); ?><br>
                            D) <?php echo sanitizeOutput($q['option_d']); ?>
                        </td>
                        <td class="px-6 py-4 text-sm font-semibold text-green-600"><?php echo $q['correct_answer']; ?></td>
                        <td class="px-6 py-4 text-sm"><?php echo $q['marks']; ?></td>
                        <td class="px-6 py-4 text-sm space-x-2">
                            <button onclick="editQuestion(<?php echo htmlspecialchars(json_encode($q)); ?>)" class="text-shikhbo-primary hover:underline">Edit</button>
                            <button onclick="deleteQuestion(<?php echo $q['id']; ?>)" class="text-red-600 hover:underline">Delete</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">No questions found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php if ($totalPages > 1): ?>
        <div class="px-6 py-3 border-t flex justify-between text-sm">
            <span>Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
            <div class="flex space-x-1">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="index.php?page=questions&p=<?php echo $i; ?>&exam_id=<?php echo $examFilter; ?>&search=<?php echo urlencode($search); ?>" class="px-3 py-1 border rounded <?php echo $i === $page ? 'bg-shikhbo-primary text-white' : 'hover:bg-gray-100'; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Add/Edit Question Modal -->
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
                        <option value="">Select Exam</option>
                        <?php foreach ($exams as $exam): ?>
                            <option value="<?php echo $exam['id']; ?>"><?php echo sanitizeOutput($exam['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Marks</label>
                    <input type="number" name="marks" id="qMarks" value="1" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Question Text</label>
                    <textarea name="question_text" id="qText" rows="3" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Option A</label>
                    <input type="text" name="option_a" id="qA" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Option B</label>
                    <input type="text" name="option_b" id="qB" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Option C</label>
                    <input type="text" name="option_c" id="qC" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Option D</label>
                    <input type="text" name="option_d" id="qD" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
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

<script>
function openQuestionModal(q = null) {
    document.getElementById('questionModal').classList.remove('hidden');
    if (q) {
        document.getElementById('qModalTitle').textContent = 'Edit Question';
        document.getElementById('qAction').value = 'edit_question';
        document.getElementById('qId').value = q.id;
        document.getElementById('qExam').value = q.exam_id;
        document.getElementById('qText').value = q.question_text;
        document.getElementById('qA').value = q.option_a;
        document.getElementById('qB').value = q.option_b;
        document.getElementById('qC').value = q.option_c;
        document.getElementById('qD').value = q.option_d;
        document.getElementById('qCorrect').value = q.correct_answer.toLowerCase();
        document.getElementById('qMarks').value = q.marks;
    } else {
        document.getElementById('qModalTitle').textContent = 'Add Question';
        document.getElementById('qAction').value = 'add_question';
        document.getElementById('qId').value = '';
        document.getElementById('questionForm').reset();
    }
}
function closeQuestionModal() { document.getElementById('questionModal').classList.add('hidden'); }
function editQuestion(q) { openQuestionModal(q); }
function deleteQuestion(id) {
    if (confirm('Delete this question?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input type="hidden" name="action" value="delete_question"><input type="hidden" name="question_id" value="${id}"><?php echo getCSRFTokenField(); ?>`;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>