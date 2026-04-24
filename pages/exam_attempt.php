<?php
$mysqli = getDBConnection();
$examId = intval($_GET['exam_id'] ?? 0);

// ========================  NO EXAM SELECTED: CATEGORY BROWSER ========================
if ($examId <= 0):
    // Fetch root categories
    $rootCats = $mysqli->query("SELECT id, name, slug, icon FROM exam_categories WHERE parent_id IS NULL AND is_active = 1 ORDER BY sort_order, id");
    ?>
    <div class="max-w-4xl mx-auto">
        <div class="text-center mb-8">
            <i class="fa-solid fa-layer-group text-5xl text-gray-300 mb-3"></i>
            <h1 class="text-2xl font-bold text-gray-800">Choose Your Exam</h1>
            <p class="text-gray-500 mt-1">Browse categories to find the exam you want to attempt.</p>
        </div>
        
        <div id="categoryBrowser" class="space-y-3">
            <?php while ($cat = $rootCats->fetch_assoc()): ?>
                <div class="bg-white rounded-xl shadow-md border border-gray-100">
                    <div class="category-item flex items-center justify-between p-4 cursor-pointer hover:bg-gray-50" 
                         data-cat-id="<?php echo $cat['id']; ?>" data-level="0"
                         onclick="toggleCategory(this, <?php echo $cat['id']; ?>)">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid <?php echo $cat['icon'] ?? 'fa-folder'; ?> text-gray-400"></i>
                            <span class="font-medium text-gray-700"><?php echo sanitizeOutput($cat['name']); ?></span>
                        </div>
                        <i class="fa-solid fa-chevron-right text-gray-400 transition-transform chevron"></i>
                    </div>
                    <div class="children-container hidden pl-8 border-t border-gray-100" id="children-<?php echo $cat['id']; ?>"></div>
                    <div class="exams-container hidden p-4 border-t border-gray-100 bg-gray-50" id="exams-<?php echo $cat['id']; ?>"></div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <script>
    // Category browser functions
    async function toggleCategory(header, catId) {
        const childrenDiv = document.getElementById('children-' + catId);
        const examsDiv = document.getElementById('exams-' + catId);
        const chevron = header.querySelector('.chevron');
        const isOpen = !childrenDiv.classList.contains('hidden');
        
        if (isOpen) {
            childrenDiv.classList.add('hidden');
            examsDiv.classList.add('hidden');
            chevron.style.transform = 'rotate(0deg)';
            return;
        }
        
        chevron.style.transform = 'rotate(90deg)';
        // Load children categories
        if (childrenDiv.innerHTML === '') {
            const res = await fetch(`/api/get_categories.php?parent_id=${catId}`);
            const data = await res.json();
            if (data.status === 'success' && data.categories.length > 0) {
                childrenDiv.innerHTML = data.categories.map(c => `
                    <div class="bg-white border border-gray-100 mt-1 rounded">
                        <div class="flex items-center justify-between p-3 cursor-pointer hover:bg-gray-50"
                             data-cat-id="${c.id}" data-level="${c.level}"
                             onclick="toggleCategory(this, ${c.id})">
                            <span>${c.name}</span>
                            <i class="fa-solid fa-chevron-right text-gray-400 transition-transform chevron"></i>
                        </div>
                        <div class="children-container hidden pl-6" id="children-${c.id}"></div>
                        <div class="exams-container hidden p-4 border-t bg-gray-50" id="exams-${c.id}"></div>
                    </div>
                `).join('');
                childrenDiv.classList.remove('hidden');
            }
        } else {
            childrenDiv.classList.toggle('hidden');
        }
        
        // Always load exams for this category
        if (examsDiv.innerHTML === '') {
            const resExams = await fetch(`/api/get_exams_by_category.php?category_id=${catId}`);
            const examData = await resExams.json();
            if (examData.status === 'success' && examData.exams.length > 0) {
                examsDiv.innerHTML = `<div class="space-y-2">` + examData.exams.map(e => `
                    <a href="index.php?page=exam_attempt&exam_id=${e.id}" class="flex items-center justify-between p-3 bg-white rounded-lg border hover:border-shikhbo-primary transition">
                        <div>
                            <p class="font-medium text-gray-700">${e.title}</p>
                            <p class="text-xs text-gray-500">${e.duration_minutes} min, ${e.total_marks} marks</p>
                        </div>
                        <i class="fa-solid fa-play text-shikhbo-primary"></i>
                    </a>
                `).join('') + `</div>`;
            } else {
                examsDiv.innerHTML = '<p class="text-sm text-gray-500">No exams in this category yet.</p>';
            }
        }
        examsDiv.classList.remove('hidden');
    }
    </script>
    <?php
    return;
endif;

// ======================== EXAM LOADED ========================
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

// ক্যাটাগরি breadcrumb
function categoryBreadcrumb($mysqli, $catId) {
    $bread = [];
    while ($catId) {
        $stmt = $mysqli->prepare("SELECT id, name, parent_id FROM exam_categories WHERE id = ?");
        $stmt->bind_param("i", $catId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) break;
        array_unshift($bread, ['name' => $row['name']]);
        $catId = $row['parent_id'];
    }
    return $bread;
}
$breadcrumb = !empty($exam['cat_id']) ? categoryBreadcrumb($mysqli, $exam['cat_id']) : [];

$durationSec = $exam['duration_minutes'] * 60;
$totalMarks  = $exam['total_marks'];
$perPage     = 25;
?>
<div class="mb-6">
    <div class="flex items-center gap-3 mb-2">
        <a href="index.php?page=exam_attempt" class="text-gray-500 hover:text-gray-700">
            <i class="fa-solid fa-arrow-left"></i> Browse Exams
        </a>
        <h1 class="text-2xl font-bold text-gray-800"><?php echo sanitizeOutput($exam['title']); ?></h1>
    </div>
    <?php if ($breadcrumb): ?>
    <div class="text-sm text-gray-500 mb-1">
        <?php foreach ($breadcrumb as $i => $step): ?>
            <?php if ($i > 0): ?> / <?php endif; ?><?php echo sanitizeOutput($step['name']); ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <p class="text-gray-500">
        <i class="fa-regular fa-clock mr-1"></i> <?php echo $exam['duration_minutes']; ?> min &nbsp;
        <i class="fa-solid fa-star mr-1"></i> <?php echo $totalMarks; ?> marks &nbsp;
        <i class="fa-solid fa-chart-line mr-1"></i> Pass: <?php echo $exam['passing_percentage']; ?>%
    </p>
</div>

<!-- টাইমার + প্রগ্রেস -->
<div class="bg-white rounded-xl shadow-md p-4 mb-4 flex items-center justify-between">
    <span class="text-lg font-semibold text-gray-700">⏳ Time Left:</span>
    <span id="timer" class="text-2xl font-mono font-bold text-shikhbo-primary"></span>
</div>
<div class="bg-white rounded-xl shadow-md p-4 mb-6">
    <div class="flex justify-between text-sm text-gray-600 mb-1">
        <span id="pageInfo">Page 1 of 1</span>
        <span id="progressText">0%</span>
    </div>
    <div class="w-full bg-gray-200 rounded-full h-2.5">
        <div id="progressBar" class="bg-shikhbo-primary h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
    </div>
</div>

<!-- কুইক নেভিগেশন (প্রশ্ন নম্বর) -->
<div id="questionPalette" class="bg-white rounded-xl shadow-md p-4 mb-4 hidden">
    <h3 class="text-sm font-medium text-gray-700 mb-2">Question Palette</h3>
    <div id="paletteGrid" class="flex flex-wrap gap-2"></div>
</div>

<!-- প্রশ্নকন্টেইনার -->
<form id="examForm" class="space-y-6">
    <div id="questionsContainer"></div>

    <div class="mt-6 flex justify-between">
        <button type="button" id="prevBtn" class="px-6 py-2.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
            <i class="fa-solid fa-chevron-left mr-2"></i>Previous
        </button>
        <button type="button" id="nextBtn" class="px-6 py-2.5 bg-shikhbo-primary text-white rounded-lg hover:bg-indigo-700">
            Next<i class="fa-solid fa-chevron-right ml-2"></i>
        </button>
        <button type="button" id="submitBtn" class="px-6 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 hidden">
            <i class="fa-solid fa-paper-plane mr-2"></i>Submit Exam
        </button>
    </div>
</form>

<script>
const examId = <?php echo $examId; ?>;
const totalDuration = <?php echo $durationSec; ?>;
const perPage = <?php echo $perPage; ?>;
let timer = totalDuration;
let currentPage = 1, totalPages = 1;
let questionsData = [];
let answers = {};
const STORAGE_KEY = `exam_${examId}_answers`;

function loadAnswersFromStorage() {
    const s = localStorage.getItem(STORAGE_KEY);
    if (s) try { answers = JSON.parse(s); } catch(e){ answers = {}; }
}
function saveAnswersToStorage() { localStorage.setItem(STORAGE_KEY, JSON.stringify(answers)); }
loadAnswersFromStorage();

// লোড সব প্রশ্ন
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
            buildPalette();
        } else {
            container.innerHTML = '<div class="text-center py-10 text-red-500"><i class="fa-solid fa-triangle-exclamation text-3xl mb-2"></i><p>Failed to load questions.</p><p class="text-xs mt-2">'+JSON.stringify(data)+'</p></div>';
        }
    } catch(e) {
        container.innerHTML = '<div class="text-center py-10 text-red-500"><i class="fa-solid fa-plug-circle-xmark text-3xl mb-2"></i><p>Network error.</p><p class="text-xs">'+e.message+'</p></div>';
    }
}

function showPage(page) {
    currentPage = page;
    const start = (page-1)*perPage;
    const pageQuestions = questionsData.slice(start, start+perPage);
    let html = '';
    pageQuestions.forEach((q, idx) => {
        const qNum = start + idx + 1;
        const selected = answers[q.id] || '';
        html += `
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 question-card" id="q-card-${q.id}">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <span class="text-xs text-gray-400 font-mono">Q${qNum}</span>
                    <p class="text-gray-800 font-medium">${q.question_text}</p>
                </div>
                <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-full">${q.marks} mark${q.marks>1?'s':''}</span>
            </div>
            <div class="mt-4 space-y-2">
                ${['a','b','c','d'].map(opt => `
                    <label class="flex items-center space-x-3 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors ${selected===opt ? 'border-shikhbo-primary bg-indigo-50' : ''}">
                        <input type="radio" name="q_${q.id}" value="${opt}" class="w-4 h-4 text-shikhbo-primary" onchange="selectAnswer(${q.id}, '${opt}')" ${selected===opt ? 'checked' : ''}>
                        <span>${q['option_'+opt]}</span>
                    </label>
                `).join('')}
            </div>
        </div>`;
    });
    document.getElementById('questionsContainer').innerHTML = html;
    updatePagination();
}

function selectAnswer(qid, option) {
    answers[qid] = option;
    saveAnswersToStorage();
    // হাইলাইট আপডেট
    document.querySelectorAll(`#q-card-${qid} label`).forEach(l => {
        l.classList.remove('border-shikhbo-primary','bg-indigo-50');
        if (l.querySelector('input').value === option) l.classList.add('border-shikhbo-primary','bg-indigo-50');
    });
    buildPalette();
}

function updatePagination() {
    document.getElementById('pageInfo').textContent = `Page ${currentPage} of ${totalPages}`;
    const progress = Math.round((currentPage/totalPages)*100);
    document.getElementById('progressText').textContent = progress+'%';
    document.getElementById('progressBar').style.width = progress+'%';
    document.getElementById('prevBtn').disabled = (currentPage <= 1);
    document.getElementById('nextBtn').classList.toggle('hidden', currentPage >= totalPages);
    document.getElementById('submitBtn').classList.toggle('hidden', currentPage < totalPages);
}

// প্রশ্ন নম্বর প্যালেট
function buildPalette() {
    const palette = document.getElementById('questionPalette');
    const grid = document.getElementById('paletteGrid');
    if (!questionsData.length) { palette.classList.add('hidden'); return; }
    palette.classList.remove('hidden');
    let btns = '';
    for (let i = 0; i < questionsData.length; i++) {
        const qid = questionsData[i].id;
        const answered = answers[qid] ? true : false;
        const activePage = Math.floor(i/perPage)+1;
        btns += `<button type="button" 
            class="w-9 h-9 rounded-full text-xs font-medium border transition
            ${answered ? 'bg-green-100 border-green-400 text-green-800' : 'bg-gray-100 border-gray-200 text-gray-600'}
            ${activePage === currentPage ? 'ring-2 ring-shikhbo-primary' : ''}"
            onclick="goToQuestion(${i})" title="Q${i+1}">${i+1}</button>`;
    }
    grid.innerHTML = btns;
}
function goToQuestion(index) {
    const page = Math.floor(index/perPage)+1;
    if (page !== currentPage) {
        currentPage = page;
        showPage(currentPage);
    }
    // scroll to the question
    const qid = questionsData[index].id;
    setTimeout(() => {
        document.getElementById('q-card-'+qid)?.scrollIntoView({behavior:'smooth',block:'center'});
    }, 100);
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
        const mins = Math.floor(timer/60);
        const secs = timer%60;
        timerElem.textContent = `${mins}:${secs.toString().padStart(2,'0')}`;
        if (timer < 300) timerElem.classList.add('text-red-500');
        else timerElem.classList.remove('text-red-500');
        timer--;
    }, 1000);
}

// কনফার্মেশন মোডাল
function showConfirmModal(msg, cb) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 z-50 flex items-center justify-center';
    modal.innerHTML = `
        <div class="absolute inset-0 bg-black bg-opacity-50"></div>
        <div class="relative bg-white rounded-xl shadow-xl p-6 max-w-md mx-4 text-center">
            <p class="text-gray-800 mb-4">${msg.replace(/\n/g,'<br>')}</p>
            <div class="flex justify-center gap-3">
                <button class="px-4 py-2 bg-gray-200 rounded-lg cancel-btn">Cancel</button>
                <button class="px-4 py-2 bg-shikhbo-primary text-white rounded-lg confirm-btn">Confirm</button>
            </div>
        </div>`;
    document.body.appendChild(modal);
    modal.querySelector('.cancel-btn').addEventListener('click', ()=>modal.remove());
    modal.querySelector('.confirm-btn').addEventListener('click', ()=>{ modal.remove(); cb(); });
}

// জমা দেওয়া
async function submitExam() {
    const answerArray = questionsData.map(q => ({
        question_id: q.id,
        selected_option: answers[q.id] || ''
    }));
    const payload = {
        exam_id: examId,
        user_id: <?php echo $_SESSION['admin_id'] ?? 1; ?>,
        answers: answerArray
    };
    try {
        const res = await fetch('/api/submit_exam.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify(payload)
        });
        const result = await res.json();
        if (result.status === 'success') {
            localStorage.removeItem(STORAGE_KEY);
            alert(`✅ Exam submitted!\nScore: ${result.score}/${result.total_marks} (${result.percentage}%)\nStatus: ${result.exam_status}`);
            window.location.href = 'index.php?page=results';
        } else {
            alert('Submission error: '+(result.message||'Unknown'));
        }
    } catch(e) {
        alert('Network error. Please try again.');
    }
}

// ইভেন্ট
document.getElementById('prevBtn').addEventListener('click', ()=>{
    if (currentPage>1) showPage(currentPage-1);
});
document.getElementById('nextBtn').addEventListener('click', ()=>{
    if (currentPage<totalPages) showPage(currentPage+1);
});
document.getElementById('submitBtn').addEventListener('click', ()=>{
    const unanswered = questionsData.length - Object.keys(answers).filter(k => answers[k]!=='').length;
    let msg = 'Are you sure you want to submit?';
    if (unanswered>0) msg += `\n⚠️ You have ${unanswered} unanswered question(s).`;
    showConfirmModal(msg, submitExam);
});

// Start
startTimer();
loadAllQuestions();
</script>