<?php
$mysqli = getDBConnection();
$examId = intval($_GET['exam_id'] ?? 0);
if (!$examId) {
    echo '<div class="text-red-500">Invalid exam ID.</div>';
    return;
}
$exam = $mysqli->query("SELECT * FROM exams WHERE id = $examId")->fetch_assoc();
if (!$exam) {
    echo '<div class="text-red-500">Exam not found.</div>';
    return;
}
?>
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800"><?php echo sanitizeOutput($exam['title']); ?></h1>
    <p class="text-gray-500 mt-1">
        Duration: <?php echo $exam['duration_minutes']; ?> min |
        Total Marks: <?php echo $exam['total_marks']; ?> |
        Passing: <?php echo $exam['passing_percentage']; ?>%
    </p>
</div>

<!-- Timer -->
<div class="bg-white rounded-xl shadow-md p-4 mb-6 flex items-center justify-between">
    <span class="text-lg font-semibold text-gray-700">Time Left:</span>
    <span id="timer" class="text-2xl font-mono text-shikhbo-primary"></span>
</div>

<!-- Progress -->
<div class="bg-white rounded-xl shadow-md p-4 mb-6">
    <div class="flex justify-between text-sm text-gray-600 mb-1">
        <span id="pageInfo"></span>
        <span id="progressText"></span>
    </div>
    <div class="w-full bg-gray-200 rounded-full h-2">
        <div id="progressBar" class="bg-shikhbo-primary h-2 rounded-full" style="width: 0%"></div>
    </div>
</div>

<!-- Questions Container -->
<div id="questionsContainer" class="space-y-6"></div>

<!-- Navigation Buttons -->
<div class="mt-6 flex justify-between">
    <button id="prevBtn" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 disabled:opacity-50" disabled>Previous</button>
    <button id="nextBtn" class="px-6 py-2 bg-shikhbo-primary text-white rounded-lg hover:bg-indigo-700">Next</button>
    <button id="submitBtn" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 hidden">Submit Exam</button>
</div>

<script>
const examId = <?php echo $examId; ?>;
const duration = <?php echo $exam['duration_minutes']; ?> * 60;
let timer = duration;
let currentPage = 1;
let totalPages = 1;
let answers = {};

// Fetch exam questions
async function loadQuestions(page) {
    const url = `/api/get_exam_questions.php?exam_id=${examId}&page=${page}&per_page=25&seed=<?php echo time(); ?>`;
    const res = await fetch(url);
    const data = await res.json();
    totalPages = data.total_pages;
    renderQuestions(data.questions);
    updatePagination(data);
}

function renderQuestions(questions) {
    const container = document.getElementById('questionsContainer');
    container.innerHTML = questions.map(q => `
        <div class="bg-white rounded-xl shadow-md p-5 question-card">
            <div class="flex items-start justify-between">
                <p class="text-gray-800 font-medium flex-1">${q.question_text}</p>
                <span class="text-sm text-gray-500 ml-2">[${q.marks} marks]</span>
            </div>
            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                ${['a','b','c','d'].map(opt => `
                    <label class="flex items-center space-x-3 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors" id="label_${q.id}_${opt}">
                        <input type="radio" name="q_${q.id}" value="${opt}" class="w-4 h-4 text-shikhbo-primary" onchange="saveAnswer(${q.id}, '${opt}')">
                        <span>${q['option_' + opt]}</span>
                    </label>
                `).join('')}
            </div>
        </div>
    `).join('');
    // Reapply saved answers
    for (const [qid, opt] of Object.entries(answers)) {
        const radio = document.querySelector(`input[name="q_${qid}"][value="${opt}"]`);
        if (radio) radio.checked = true;
    }
}

function saveAnswer(qid, option) {
    answers[qid] = option;
}

function updatePagination(data) {
    document.getElementById('pageInfo').textContent = `Page ${data.page} of ${data.total_pages}`;
    document.getElementById('progressText').textContent = `${data.page}/${data.total_pages}`;
    document.getElementById('progressBar').style.width = `${(data.page / data.total_pages) * 100}%`;

    document.getElementById('prevBtn').disabled = data.page <= 1;
    document.getElementById('nextBtn').classList.toggle('hidden', data.page >= data.total_pages);
    document.getElementById('submitBtn').classList.toggle('hidden', data.page < data.total_pages);
}

// Timer
function startTimer() {
    const timerElem = document.getElementById('timer');
    const interval = setInterval(() => {
        if (timer <= 0) {
            clearInterval(interval);
            submitExam();
        }
        const mins = Math.floor(timer / 60);
        const secs = timer % 60;
        timerElem.textContent = `${mins}:${secs.toString().padStart(2, '0')}`;
        timer--;
    }, 1000);
}

async function submitExam() {
    // Gather all answers from all pages? We only have current page answers. We need to collect from local storage or send current.
    // For simplicity, we will fetch all pages and collect answers? Not good.
    // Best is to store all answers in a global object and fetch all questions IDs.
    // Since we paginate, we must track answers across pages. We'll keep a global answers object.
    // Also need to ensure unanswered are submitted as blank.
    const allQuestions = []; // We'll fetch all to get IDs
    const res = await fetch(`/api/get_exam_questions.php?exam_id=${examId}&page=1&per_page=9999&seed=<?php echo time(); ?>`);
    const data = await res.json();
    const answerArray = data.questions.map(q => ({
        question_id: q.id,
        selected_option: answers[q.id] || ''
    }));

    const submitRes = await fetch('/api/submit_exam.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            exam_id: examId,
            user_id: <?php echo $_SESSION['admin_id'] ?? 1; ?>,
            answers: answerArray
        })
    });
    const result = await submitRes.json();
    alert(`Score: ${result.score}/${result.total_marks} (${result.percentage}%) - ${result.exam_status}`);
    window.location.href = 'index.php?page=exams';
}

document.getElementById('prevBtn').addEventListener('click', () => {
    if (currentPage > 1) {
        currentPage--;
        loadQuestions(currentPage);
    }
});
document.getElementById('nextBtn').addEventListener('click', () => {
    if (currentPage < totalPages) {
        currentPage++;
        loadQuestions(currentPage);
    }
});
document.getElementById('submitBtn').addEventListener('click', submitExam);

// Init
loadQuestions(currentPage);
startTimer();
</script>