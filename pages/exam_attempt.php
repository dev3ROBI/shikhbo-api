<?php
$mysqli = getDBConnection();
$examId = intval($_GET['exam_id'] ?? 0);

if ($examId <= 0) {
    echo '<div class="text-center py-20 text-red-500"><i class="fa-solid fa-circle-exclamation text-4xl mb-3"></i><p class="text-xl">No exam selected.</p></div>';
    return;
}

// নিরাপদ ডাটাবেজ কুয়েরি
$stmt = $mysqli->prepare("SELECT * FROM exams WHERE id = ?");
$stmt->bind_param("i", $examId);
$stmt->execute();
$exam = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$exam) {
    echo '<div class="text-center py-20 text-red-500"><i class="fa-solid fa-exclamation-triangle text-4xl mb-3"></i><p class="text-xl">Exam not found.</p></div>';
    return;
}

// মিনিমাম কনফিগারেশন
$durationSec = $exam['duration_minutes'] * 60;
$totalMarks = $exam['total_marks'];
$passingPercent = $exam['passing_percentage'];
$perPage = 25; // প্রতি পৃষ্ঠায় প্রশ্নসংখ্যা
?>
<div class="mb-6">
    <div class="flex items-center gap-3 mb-2">
        <a href="index.php?page=exams" class="text-gray-500 hover:text-gray-700">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
        <h1 class="text-2xl font-bold text-gray-800"><?php echo sanitizeOutput($exam['title']); ?></h1>
    </div>
    <p class="text-gray-500">
        <i class="fa-regular fa-clock mr-1"></i> <?php echo $exam['duration_minutes']; ?> min &nbsp;
        <i class="fa-solid fa-star mr-1"></i> <?php echo $totalMarks; ?> marks &nbsp;
        <i class="fa-solid fa-chart-line mr-1"></i> Pass: <?php echo $passingPercent; ?>%
    </p>
</div>

<!-- টাইমার বক্স -->
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
<div id="questionsContainer" class="space-y-6"></div>

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
let questionsData = []; // all questions fetched at once
let answers = {}; // জমা রাখবে { question_id: selected_option }
const STORAGE_KEY = `exam_${examId}_answers`;

// লোকালস্টোরেজ থেকে পূর্বের উত্তর নিন
function loadAnswersFromStorage() {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored) {
        try {
            answers = JSON.parse(stored);
        } catch(e) {
            answers = {};
        }
    }
}
function saveAnswersToStorage() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(answers));
}

// প্রশ্ন লোড (একবারেই সব প্রশ্ন নিয়ে পেজিনেশন করব, র‍্যান্ডম অর্ডার)
async function loadAllQuestions() {
    const url = `/api/get_exam_questions.php?exam_id=${examId}&page=1&per_page=9999&seed=<?php echo time(); ?>`;
    const res = await fetch(url);
    const data = await res.json();
    if (data.status === 'success') {
        questionsData = data.questions;
        totalPages = Math.ceil(questionsData.length / perPage);
        showPage(currentPage);
        updatePagination();
    } else {
        document.getElementById('questionsContainer').innerHTML = '<p class="text-red-500">Could not load questions.</p>';
    }
}

// নির্দিষ্ট পৃষ্ঠা প্রদর্শন
function showPage(page) {
    currentPage = page;
    const start = (page - 1) * perPage;
    const end = start + perPage;
    const pageQuestions = questionsData.slice(start, end);

    const container = document.getElementById('questionsContainer');
    container.innerHTML = pageQuestions.map(q => `
        <div class="bg-white rounded-xl shadow-md p-5 border border-gray-100">
            <div class="flex items-start justify-between mb-3">
                <p class="text-gray-800 font-medium flex-1">${q.question_text}</p>
                <span class="text-sm text-gray-500 ml-2 bg-gray-100 px-2 py-1 rounded-full">${q.marks} mark${q.marks>1?'s':''}</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                ${['a','b','c','d'].map(opt => {
                    const optText = q['option_' + opt];
                    return `
                    <label class="flex items-center space-x-3 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors ${answers[q.id] === opt ? 'border-shikhbo-primary bg-indigo-50' : ''}" id="label_${q.id}_${opt}">
                        <input type="radio" name="q_${q.id}" value="${opt}" class="w-4 h-4 text-shikhbo-primary" onchange="selectAnswer(${q.id}, '${opt}')" ${answers[q.id] === opt ? 'checked' : ''}>
                        <span>${optText}</span>
                    </label>
                    `;
                }).join('')}
            </div>
        </div>
    `).join('');
    updatePagination();
}

// উত্তর সিলেক্ট
function selectAnswer(qid, option) {
    answers[qid] = option;
    saveAnswersToStorage();
    // হাইলাইটিং আপডেট
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

// টাইমার
function startTimer() {
    const timerElem = document.getElementById('timer');
    const interval = setInterval(() => {
        if (timer <= 0) {
            clearInterval(interval);
            submitExam();
            return;
        }
        const mins = Math.floor(timer / 60);
        const secs = timer % 60;
        timerElem.textContent = `${mins}:${secs.toString().padStart(2, '0')}`;
        if (timer < 300) {
            timerElem.classList.add('text-red-500');
            timerElem.classList.remove('text-shikhbo-primary');
        }
        timer--;
    }, 1000);
}

// কনফার্মেশন মোডাল
function showConfirmModal(message, onConfirm) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 z-50 flex items-center justify-center';
    modal.innerHTML = `
        <div class="absolute inset-0 bg-black bg-opacity-50"></div>
        <div class="relative bg-white rounded-xl shadow-xl p-6 max-w-md mx-4 text-center">
            <p class="text-gray-800 mb-4">${message}</p>
            <div class="flex justify-center gap-3">
                <button class="px-4 py-2 bg-gray-200 rounded-lg cancel-btn">Cancel</button>
                <button class="px-4 py-2 bg-shikhbo-primary text-white rounded-lg confirm-btn">Confirm</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    modal.querySelector('.cancel-btn').addEventListener('click', () => modal.remove());
    modal.querySelector('.confirm-btn').addEventListener('click', () => {
        modal.remove();
        onConfirm();
    });
}

// জমা দেওয়া
async function submitExam() {
    // উত্তরগুলো সঠিক ফরমেটে তৈরি
    const answerArray = questionsData.map(q => ({
        question_id: q.id,
        selected_option: answers[q.id] || ''   // উত্তর না দিলে খালি
    }));

    const payload = {
        exam_id: examId,
        user_id: <?php echo $_SESSION['admin_id'] ?? 1; ?>,
        answers: answerArray
    };

    try {
        const res = await fetch('/api/submit_exam.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        });
        const result = await res.json();
        if (result.status === 'success') {
            localStorage.removeItem(STORAGE_KEY); // clear stored answers
            alert(`✅ Exam submitted!\nScore: ${result.score}/${result.total_marks} (${result.percentage}%)\nStatus: ${result.exam_status}`);
            window.location.href = 'index.php?page=results';
        } else {
            alert('Submission error: ' + (result.message || 'Unknown'));
        }
    } catch(e) {
        alert('Network error. Please try again.');
    }
}

// ইভেন্ট লিসেনার
document.getElementById('prevBtn').addEventListener('click', () => {
    if (currentPage > 1) showPage(currentPage - 1);
});
document.getElementById('nextBtn').addEventListener('click', () => {
    if (currentPage < totalPages) showPage(currentPage + 1);
});
document.getElementById('submitBtn').addEventListener('click', () => {
    const unanswered = questionsData.length - Object.keys(answers).length;
    let msg = 'Are you sure you want to submit?';
    if (unanswered > 0) msg += `\n⚠️ You have ${unanswered} unanswered question(s).`;
    showConfirmModal(msg, submitExam);
});

// সময় শেষে স্বয়ংক্রিয় জমা
startTimer();
loadAnswersFromStorage();
loadAllQuestions();
</script>