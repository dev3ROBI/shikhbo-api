<?php
$mysqli = getDBConnection();
$examId = intval($_GET['exam_id'] ?? 0);

// ------------------- কোনো পরীক্ষা নির্বাচন করা হয়নি -------------------
if ($examId <= 0) {
    // … আগের মতোই – এখানে যদি exam list দেখাতে চান তবে সেটার কোড বসাতে পারেন,
    // অথবা নিচের মতো একটি সুন্দর পেইজ রাখতে পারেন।
    echo '<div class="text-center py-20 text-red-500"><i class="fa-solid fa-circle-exclamation text-4xl mb-3"></i><p class="text-xl">No exam selected.</p></div>';
    return;
}

// ------------------- পরীক্ষা ও তার ক্যাটাগরি তথ্য -------------------
$stmt = $mysqli->prepare("
    SELECT e.*, c.id AS cat_id, c.name AS cat_name
    FROM exams e
    LEFT JOIN exam_categories c ON e.category_id = c.id
    WHERE e.id = ?
");
$stmt->bind_param("i", $examId);
$stmt->execute();
$exam = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$exam) {
    echo '<div class="text-center py-20 text-red-500"><i class="fa-solid fa-exclamation-triangle text-4xl mb-3"></i><p class="text-xl">Exam not found.</p></div>';
    return;
}

// ক্যাটাগরি breadcrumb তৈরি
function categoryBreadcrumb($mysqli, $catId) {
    $bread = [];
    while ($catId) {
        $stmt = $mysqli->prepare("SELECT id, name, parent_id FROM exam_categories WHERE id = ?");
        $stmt->bind_param("i", $catId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) break;
        array_unshift($bread, ['id' => $row['id'], 'name' => $row['name']]);
        $catId = $row['parent_id'];
    }
    return $bread;
}

$breadcrumb = [];
if (!empty($exam['cat_id'])) {
    $breadcrumb = categoryBreadcrumb($mysqli, $exam['cat_id']);
}

$durationSec = $exam['duration_minutes'] * 60;
$totalMarks  = $exam['total_marks'];
$passingPercent = $exam['passing_percentage'];
$perPage     = 25; // প্রতি পৃষ্ঠায় প্রশ্ন সংখ্যা
?>
<div class="mb-6">
    <!-- ব্যাক বাটন ও শিরোনাম -->
    <div class="flex items-center gap-3 mb-2">
        <a href="index.php?page=exams" class="text-gray-500 hover:text-gray-700">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
        <h1 class="text-2xl font-bold text-gray-800"><?php echo sanitizeOutput($exam['title']); ?></h1>
    </div>

    <!-- ক্যাটাগরি breadcrumb -->
    <?php if (!empty($breadcrumb)): ?>
    <div class="flex items-center space-x-1 text-sm text-gray-500 mb-2">
        <i class="fa-solid fa-layer-group mr-1"></i>
        <?php foreach ($breadcrumb as $i => $crumb): ?>
            <?php if ($i > 0): ?><span class="mx-1">/</span><?php endif; ?>
            <span><?php echo sanitizeOutput($crumb['name']); ?></span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- পরীক্ষার মেটা -->
    <p class="text-gray-500">
        <i class="fa-regular fa-clock mr-1"></i> <?php echo $exam['duration_minutes']; ?> min &nbsp;
        <i class="fa-solid fa-star mr-1"></i> <?php echo $totalMarks; ?> marks &nbsp;
        <i class="fa-solid fa-chart-line mr-1"></i> Pass: <?php echo $passingPercent; ?>%
    </p>
</div>

<!-- টাইমার -->
<div class="bg-white rounded-xl shadow-md p-4 mb-6 flex items-center justify-between">
    <span class="text-lg font-semibold text-gray-700">⏳ Time Left:</span>
    <span id="timer" class="text-2xl font-mono font-bold text-shikhbo-primary"></span>
</div>

<!-- প্রগ্রেস বার -->
<div class="bg-white rounded-xl shadow-md p-4 mb-6">
    <div class="flex justify-between text-sm text-gray-600 mb-1">
        <span id="pageInfo">Page 1 of 1</span>
        <span id="progressText">0%</span>
    </div>
    <div class="w-full bg-gray-200 rounded-full h-2.5">
        <div id="progressBar" class="bg-shikhbo-primary h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
    </div>
</div>

<!-- প্রশ্নকন্টেইনার -->
<div id="questionsContainer" class="space-y-6">
    <div class="text-center py-10 text-gray-500">
        <i class="fa-solid fa-spinner fa-spin text-3xl mb-2"></i>
        <p>Loading questions...</p>
    </div>
</div>

<!-- নেভিগেশন -->
<div class="mt-6 flex justify-between">
    <button id="prevBtn" class="px-6 py-2.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
        <i class="fa-solid fa-chevron-left mr-2"></i>Previous
    </button>
    <button id="nextBtn" class="px-6 py-2.5 bg-shikhbo-primary text-white rounded-lg hover:bg-indigo-700">
        Next<i class="fa-solid fa-chevron-right ml-2"></i>
    </button>
    <button id="submitBtn" class="px-6 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 hidden">
        <i class="fa-solid fa-paper-plane mr-2"></i>Submit Exam
    </button>
</div>

<script>
const examId = <?php echo $examId; ?>;
const totalDuration = <?php echo $durationSec; ?>;
const perPage = <?php echo $perPage; ?>;
let timer = totalDuration;
let currentPage = 1;
let totalPages = 1;
let questionsData = [];
let answers = {};
const STORAGE_KEY = `exam_${examId}_answers`;

// লোকালস্টোরেজ থেকে উত্তর নেওয়া
function loadAnswersFromStorage() {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored) {
        try { answers = JSON.parse(stored); } catch(e) { answers = {}; }
    }
}
function saveAnswersToStorage() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(answers));
}

// প্রশ্ন লোড (একবারেই সব)
async function loadAllQuestions() {
    const container = document.getElementById('questionsContainer');
    container.innerHTML = '<div class="text-center py-10 text-gray-500"><i class="fa-solid fa-spinner fa-spin text-3xl mb-2"></i><p>Loading questions...</p></div>';
    try {
        const url = `/api/get_exam_questions.php?exam_id=${examId}&page=1&per_page=9999&seed=${Date.now()}`;
        const res = await fetch(url);
        const data = await res.json();
        if (data.status === 'success' && Array.isArray(data.questions)) {
            questionsData = data.questions;
            totalPages = Math.ceil(questionsData.length / perPage);
            if (questionsData.length === 0) {
                container.innerHTML = '<div class="text-center py-10 text-gray-500"><i class="fa-solid fa-circle-question text-3xl mb-2"></i><p>No questions available for this exam.</p></div>';
                document.getElementById('prevBtn').style.display = 'none';
                document.getElementById('nextBtn').style.display = 'none';
                document.getElementById('submitBtn').style.display = 'none';
                return;
            }
            showPage(currentPage);
            updatePagination();
        } else {
            container.innerHTML = '<div class="text-center py-10 text-red-500"><i class="fa-solid fa-triangle-exclamation text-3xl mb-2"></i><p>Failed to load questions. (API error)</p><p class="text-xs mt-2">Response: '+JSON.stringify(data)+'</p></div>';
        }
    } catch (error) {
        container.innerHTML = '<div class="text-center py-10 text-red-500"><i class="fa-solid fa-plug-circle-xmark text-3xl mb-2"></i><p>Network error. Could not reach API.</p><p class="text-xs mt-2">'+error.message+'</p></div>';
    }
}

// পৃষ্ঠা প্রদর্শন
function showPage(page) {
    currentPage = page;
    const start = (page - 1) * perPage;
    const pageQuestions = questionsData.slice(start, start + perPage);
    const container = document.getElementById('questionsContainer');
    container.innerHTML = pageQuestions.map(q => `
        <div class="bg-white rounded-xl shadow-md p-5 border border-gray-100">
            <div class="flex items-start justify-between mb-3">
                <p class="text-gray-800 font-medium flex-1">${q.question_text}</p>
                <span class="text-sm text-gray-500 ml-2 bg-gray-100 px-2 py-1 rounded-full">${q.marks} mark${q.marks>1?'s':''}</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                ${['a','b','c','d'].map(opt => `
                    <label class="flex items-center space-x-3 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors ${answers[q.id] === opt ? 'border-shikhbo-primary bg-indigo-50' : ''}" id="label_${q.id}_${opt}">
                        <input type="radio" name="q_${q.id}" value="${opt}" class="w-4 h-4 text-shikhbo-primary" onchange="selectAnswer(${q.id}, '${opt}')" ${answers[q.id] === opt ? 'checked' : ''}>
                        <span>${q['option_' + opt]}</span>
                    </label>
                `).join('')}
            </div>
        </div>
    `).join('');
    updatePagination();
}

function selectAnswer(qid, option) {
    answers[qid] = option;
    saveAnswersToStorage();
    ['a','b','c','d'].forEach(opt => {
        const label = document.getElementById(`label_${qid}_${opt}`);
        if (label) {
            label.classList.toggle('border-shikhbo-primary', opt === option);
            label.classList.toggle('bg-indigo-50', opt === option);
        }
    });
}

function updatePagination() {
    document.getElementById('pageInfo').textContent = `Page ${currentPage} of ${totalPages}`;
    const progress = Math.round((currentPage / totalPages) * 100);
    document.getElementById('progressText').textContent = `${progress}%`;
    document.getElementById('progressBar').style.width = `${progress}%`;
    document.getElementById('prevBtn').disabled = (currentPage <= 1);
    document.getElementById('nextBtn').classList.toggle('hidden', currentPage >= totalPages);
    document.getElementById('submitBtn').classList.toggle('hidden', currentPage < totalPages);
}

// … (timer, submit, modal – same as before, omitted for brevity, use the previous versions) …
</script>