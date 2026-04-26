<?php
$mysqli = getDBConnection();
$examId = intval($_GET['exam_id'] ?? 0);

// ======================== CATEGORY BROWSER (no examId) ========================
if ($examId <= 0):
    $rootCats = $mysqli->query("SELECT id, name, slug, icon, category_type FROM exam_categories WHERE parent_id IS NULL AND is_active = 1 ORDER BY sort_order, id");
    $typeIcons = ['academic'=>'fa-graduation-cap','job'=>'fa-briefcase','general'=>'fa-book','other'=>'fa-folder'];
    $typeColors = ['academic'=>'text-blue-500','job'=>'text-emerald-500','general'=>'text-purple-500','other'=>'text-gray-500'];
    ?>
    <div class="max-w-5xl mx-auto">
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-shikhbo-primary to-indigo-400 rounded-2xl mb-4 shadow-lg">
                <i class="fa-solid fa-graduation-cap text-3xl text-white"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">Explore Exams</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-2">Browse categories to find the exam you want to attempt.</p>
        </div>

        <div id="categoryBrowser" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php while ($cat = $rootCats->fetch_assoc()): 
                $icon = $cat['icon'] ?? $typeIcons[$cat['category_type']] ?? 'fa-folder';
                $color = $typeColors[$cat['category_type']] ?? 'text-gray-500';
            ?>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md dark:shadow-gray-900/20 border border-gray-100 dark:border-gray-700 overflow-hidden category-card">
                    <div class="category-header flex items-center justify-between p-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                         onclick="toggleCategory(this, <?php echo $cat['id']; ?>)">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                <i class="fa-solid <?php echo $icon; ?> text-lg <?php echo $color; ?>"></i>
                            </div>
                            <div>
                                <span class="font-semibold text-gray-700 dark:text-gray-200"><?php echo sanitizeOutput($cat['name']); ?></span>
                                <p class="text-xs text-gray-400 dark:text-gray-500 capitalize"><?php echo $cat['category_type']; ?></p>
                            </div>
                        </div>
                        <i class="fa-solid fa-chevron-down text-gray-400 dark:text-gray-500 transition-transform duration-300 chevron"></i>
                    </div>
                    <div class="children-container hidden border-t border-gray-100 dark:border-gray-700" id="children-<?php echo $cat['id']; ?>">
                        <div class="p-3 bg-gray-50 dark:bg-gray-700 text-center text-sm text-gray-500 dark:text-gray-400">
                            <i class="fa-solid fa-spinner fa-spin mr-2"></i>Loading...
                        </div>
                    </div>
                    <div class="exams-container hidden border-t border-gray-100 dark:border-gray-700" id="exams-<?php echo $cat['id']; ?>"></div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <script>
    const typeIcons = <?php echo json_encode($typeIcons); ?>;
    const typeColors = <?php echo json_encode($typeColors); ?>;

    async function toggleCategory(header, catId) {
        const card = header.closest('.category-card');
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

        chevron.style.transform = 'rotate(180deg)';
        childrenDiv.classList.remove('hidden');
        childrenDiv.innerHTML = '<div class="p-4 text-center text-sm text-gray-500 dark:text-gray-400"><i class="fa-solid fa-spinner fa-spin mr-2"></i>Loading subcategories...</div>';

        try {
            const [catRes, examRes] = await Promise.all([
                fetch(`/api/get_categories_web.php?parent_id=${catId}`),
                fetch(`/api/get_exams_by_category_web.php?category_id=${catId}&direct=1`)
            ]);
            const catData = await catRes.json();
            const examData = await examRes.json();

            if (catData.status === 'success' && catData.categories.length > 0) {
                childrenDiv.innerHTML = catData.categories.map(c => {
                    const icon = c.icon || typeIcons[c.category_type] || 'fa-folder';
                    const color = typeColors[c.category_type] || 'text-gray-500';
                    return `
                    <div class="border-b border-gray-100 dark:border-gray-700 last:border-0">
                        <div class="flex items-center justify-between p-3 pl-8 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors subcategory-header"
                             onclick="event.stopPropagation(); toggleSubCategory(this, ${c.id})">
                            <span class="flex items-center gap-2">
                                <i class="fa-solid ${icon} text-xs ${color}"></i>
                                <span class="text-sm text-gray-700 dark:text-gray-200">${c.name}</span>
                            </span>
                            <i class="fa-solid fa-chevron-down text-xs text-gray-400 dark:text-gray-500 transition-transform duration-300 subchevron"></i>
                        </div>
                        <div class="subchildren-container hidden" id="children-${c.id}"></div>
                        <div class="subexams-container hidden" id="exams-${c.id}"></div>
                    </div>`;
                }).join('');
            } else {
                childrenDiv.innerHTML = '';
            }

            if (examData.status === 'success' && examData.exams.length > 0) {
                examsDiv.innerHTML = '<div class="p-3 space-y-2">' + examData.exams.map(e => `
                    <a href="index.php?page=exam_attempt&exam_id=${e.id}" class="flex items-center justify-between p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-900/30 transition border border-indigo-100 dark:border-indigo-900/30">
                        <div>
                            <p class="font-medium text-gray-700 dark:text-gray-200 text-sm">${e.title}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">${e.duration_minutes} min • ${e.total_marks} marks</p>
                        </div>
                        <i class="fa-solid fa-circle-play text-shikhbo-primary text-xl"></i>
                    </a>
                `).join('') + '</div>';
                examsDiv.classList.remove('hidden');
            }

            if (!catData.categories || catData.categories.length === 0) {
                childrenDiv.classList.add('hidden');
            }
        } catch (err) {
            childrenDiv.innerHTML = '<div class="p-4 text-center text-sm text-red-500"><i class="fa-solid fa-circle-exclamation mr-1"></i>Failed to load</div>';
        }
    }

    async function toggleSubCategory(header, catId) {
        const parentRow = header.parentElement;
        const childrenDiv = parentRow.querySelector('.subchildren-container');
        const examsDiv = parentRow.querySelector('.subexams-container');
        const chevron = header.querySelector('.subchevron');
        const isOpen = !childrenDiv.classList.contains('hidden');

        if (isOpen) {
            childrenDiv.classList.add('hidden');
            examsDiv.classList.add('hidden');
            chevron.style.transform = 'rotate(0deg)';
            return;
        }

        chevron.style.transform = 'rotate(180deg)';

        try {
            const [catRes, examRes] = await Promise.all([
                fetch(`/api/get_categories_web.php?parent_id=${catId}`),
                fetch(`/api/get_exams_by_category_web.php?category_id=${catId}&direct=1`)
            ]);
            const catData = await catRes.json();
            const examData = await examRes.json();

            if (catData.status === 'success' && catData.categories.length > 0) {
                childrenDiv.classList.remove('hidden');
                childrenDiv.innerHTML = catData.categories.map(c => `
                    <div class="flex items-center justify-between p-2 pl-14 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
                         onclick="event.stopPropagation(); toggleSubCategory(this, ${c.id})">
                        <span class="text-sm text-gray-600 dark:text-gray-300">${c.name}</span>
                        <i class="fa-solid fa-chevron-down text-xs text-gray-400 dark:text-gray-500 subchevron"></i>
                    </div>
                    <div class="subchildren-container hidden pl-6" id="children-${c.id}"></div>
                    <div class="subexams-container hidden" id="exams-${c.id}"></div>
                `).join('');
            }

            if (examData.status === 'success' && examData.exams.length > 0) {
                examsDiv.classList.remove('hidden');
                examsDiv.innerHTML = '<div class="p-3 pl-14 space-y-2">' + examData.exams.map(e => `
                    <a href="index.php?page=exam_attempt&exam_id=${e.id}" class="flex items-center justify-between p-2 bg-indigo-50 dark:bg-indigo-900/20 rounded hover:bg-indigo-100 dark:hover:bg-indigo-900/30 transition text-sm">
                        <span class="font-medium text-gray-700 dark:text-gray-200">${e.title}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">${e.duration_minutes} min</span>
                    </a>
                `).join('') + '</div>';
            }
        } catch (err) {
            childrenDiv.innerHTML = '<div class="p-2 pl-14 text-sm text-red-500">Failed to load</div>';
        }
    }
    </script>
    <?php
    return;
endif;

// ======================== EXAM ATTEMPT (with examId) ========================
$stmt = $mysqli->prepare("SELECT e.*, c.id AS cat_id, c.name AS cat_name FROM exams e LEFT JOIN exam_categories c ON e.category_id=c.id WHERE e.id=?");
$stmt->bind_param("i", $examId);
$stmt->execute();
$exam = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$exam) { echo '<div class="text-center py-20 text-red-500"><i class="fa-solid fa-exclamation-triangle text-4xl mb-3"></i><p class="text-xl">Exam not found.</p></div>'; return; }

function catBreadcrumb($mysqli, $catId) {
    $bread = [];
    while ($catId) {
        $s = $mysqli->prepare("SELECT id, name, parent_id FROM exam_categories WHERE id=?");
        $s->bind_param("i", $catId); $s->execute();
        $r = $s->get_result()->fetch_assoc(); $s->close();
        if (!$r) break;
        array_unshift($bread, ['name'=>$r['name']]);
        $catId = $r['parent_id'];
    }
    return $bread;
}
$breadcrumb = !empty($exam['cat_id']) ? catBreadcrumb($mysqli, $exam['cat_id']) : [];
$durationSec = $exam['duration_minutes'] * 60;
$totalMarks = $exam['total_marks'];
$perPage = 25;
?>
<div class="mb-6">
    <div class="flex items-center gap-3 mb-2">
        <a href="index.php?page=exam_attempt" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
            <i class="fa-solid fa-arrow-left mr-1"></i> Browse
        </a>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100"><?php echo sanitizeOutput($exam['title']); ?></h1>
    </div>
    <?php if ($breadcrumb): ?>
        <div class="flex flex-wrap items-center text-sm text-gray-400 dark:text-gray-500 mb-2">
            <?php foreach ($breadcrumb as $i => $step): ?>
                <?php if ($i > 0): ?><i class="fa-solid fa-angle-right mx-2 text-xs"></i><?php endif; ?>
                <span><?php echo sanitizeOutput($step['name']); ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <div class="flex flex-wrap items-center gap-3 text-gray-500 dark:text-gray-400 text-sm">
        <span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-full"><i class="fa-regular fa-clock mr-1"></i><?php echo $exam['duration_minutes']; ?> min</span>
        <span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-full"><i class="fa-solid fa-star mr-1"></i><?php echo $totalMarks; ?> marks</span>
        <span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-full"><i class="fa-solid fa-chart-line mr-1"></i>Pass: <?php echo $exam['passing_percentage']; ?>%</span>
    </div>
</div>

<div class="bg-white dark:bg-gray-800 rounded-xl shadow-md dark:shadow-gray-900/20 p-4 mb-4 flex items-center justify-between">
    <span class="text-lg font-semibold text-gray-700 dark:text-gray-200"><i class="fa-regular fa-hourglass-half mr-2"></i>Time Left:</span>
    <span id="timer" class="text-2xl font-mono font-bold text-shikhbo-primary dark:text-indigo-400"></span>
</div>

<div class="bg-white dark:bg-gray-800 rounded-xl shadow-md dark:shadow-gray-900/20 p-4 mb-4">
    <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
        <span id="pageInfo">Page 1 of 1</span>
        <span id="progressText">0%</span>
    </div>
    <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2.5">
        <div id="progressBar" class="bg-shikhbo-primary dark:bg-indigo-400 h-2.5 rounded-full transition-all duration-300" style="width:0%"></div>
    </div>
</div>

<div id="questionPalette" class="bg-white dark:bg-gray-800 rounded-xl shadow-md dark:shadow-gray-900/20 p-4 mb-4 hidden">
    <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Question Palette</h3>
    <div id="paletteGrid" class="flex flex-wrap gap-1.5"></div>
    <div class="flex items-center gap-4 mt-3 text-xs text-gray-500 dark:text-gray-400">
        <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-green-100 dark:bg-green-900/30 border border-green-400"></span> Answered</span>
        <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600"></span> Not Answered</span>
    </div>
</div>

<form id="examForm" class="space-y-6"><div id="questionsContainer"></div></form>

<div class="mt-6 flex justify-between">
    <button id="prevBtn" class="px-6 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
        <i class="fa-solid fa-chevron-left mr-2"></i>Previous
    </button>
    <button id="nextBtn" class="px-6 py-2.5 bg-shikhbo-primary text-white rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600">
        Next<i class="fa-solid fa-chevron-right ml-2"></i>
    </button>
    <button id="submitBtn" class="px-6 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 dark:hover:bg-red-500 hidden">
        <i class="fa-solid fa-paper-plane mr-2"></i>Submit Exam
    </button>
</div>

<script>
const examId = <?php echo $examId; ?>;
const totalDuration = <?php echo $durationSec; ?>;
const perPage = <?php echo $perPage; ?>;
let timer = totalDuration;
let currentPage = 1, totalPages = 1;
let questionsData = [];
let answers = {};
const STORAGE_KEY = `exam_${examId}_answers`;

function loadAnswersFromStorage(){ const s=localStorage.getItem(STORAGE_KEY); if(s) try{answers=JSON.parse(s);}catch(e){answers={};} }
function saveAnswersToStorage(){ localStorage.setItem(STORAGE_KEY, JSON.stringify(answers)); }
loadAnswersFromStorage();

async function loadAllQuestions(){
    const container = document.getElementById('questionsContainer');
    container.innerHTML = '<div class="text-center py-16"><div class="inline-block w-12 h-12 border-4 border-shikhbo-primary dark:border-indigo-400 border-t-transparent rounded-full animate-spin mb-4"></div><p class="text-gray-500 dark:text-gray-400">Loading questions...</p></div>';
    try {
        const res = await fetch(`/api/get_exam_questions_web.php?exam_id=${examId}&page=1&per_page=9999&seed=${Date.now()}`);
        const data = await res.json();
        if(data.status==='success' && Array.isArray(data.questions)){
            questionsData = data.questions;
            totalPages = Math.ceil(questionsData.length/perPage);
            if(questionsData.length===0){
                container.innerHTML = '<div class="text-center py-16 text-gray-500 dark:text-gray-400"><i class="fa-solid fa-circle-question text-4xl mb-3 block"></i><p>No questions available.</p></div>';
                document.getElementById('prevBtn').style.display='none';
                document.getElementById('nextBtn').style.display='none';
                document.getElementById('submitBtn').style.display='none';
                return;
            }
            showPage(currentPage);
            updatePagination();
            buildPalette();
        } else {
            container.innerHTML = '<div class="text-center py-16 text-red-500 dark:text-red-400"><i class="fa-solid fa-triangle-exclamation text-4xl mb-3 block"></i><p>Failed to load questions.</p></div>';
        }
    } catch(e) {
        container.innerHTML = '<div class="text-center py-16 text-red-500 dark:text-red-400"><i class="fa-solid fa-plug-circle-xmark text-4xl mb-3 block"></i><p>Network error.</p></div>';
    }
}

function showPage(page){
    currentPage = page;
    const start = (page-1)*perPage;
    const pageQuestions = questionsData.slice(start, start+perPage);
    let html = '';
    pageQuestions.forEach((q, idx) => {
        const qNum = start + idx + 1;
        const selected = answers[q.id] || '';
        html += `
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm dark:shadow-gray-900/20 border border-gray-100 dark:border-gray-700 p-5" id="q-card-${q.id}">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <span class="text-xs text-gray-400 dark:text-gray-500 font-mono bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">Q${qNum}</span>
                    <p class="text-gray-800 dark:text-gray-100 font-medium mt-2">${q.question_text}</p>
                </div>
                <span class="text-xs text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded-full">${q.marks} mark${q.marks>1?'s':''}</span>
            </div>
            <div class="mt-4 space-y-2">
                ${['a','b','c','d'].map(opt => `
                    <label class="flex items-center space-x-3 p-3 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors ${selected===opt?'border-shikhbo-primary dark:border-indigo-400 bg-indigo-50 dark:bg-indigo-900/20':''}">
                        <input type="radio" name="q_${q.id}" value="${opt}" class="w-4 h-4 text-shikhbo-primary dark:text-indigo-400" onchange="selectAnswer(${q.id},'${opt}')" ${selected===opt?'checked':''}>
                        <span class="text-sm text-gray-700 dark:text-gray-200">${q['option_'+opt]}</span>
                    </label>
                `).join('')}
            </div>
        </div>`;
    });
    document.getElementById('questionsContainer').innerHTML = html;
    updatePagination();
    window.scrollTo({top:0, behavior:'smooth'});
}

function selectAnswer(qid, option){
    answers[qid] = option;
    saveAnswersToStorage();
    document.querySelectorAll(`#q-card-${qid} label`).forEach(l => {
        l.classList.remove('border-shikhbo-primary','dark:border-indigo-400','bg-indigo-50','dark:bg-indigo-900/20');
        if(l.querySelector('input').value===option) {
            l.classList.add('border-shikhbo-primary','dark:border-indigo-400','bg-indigo-50','dark:bg-indigo-900/20');
        }
    });
    buildPalette();
}

function updatePagination(){
    document.getElementById('pageInfo').textContent = `Page ${currentPage} of ${totalPages}`;
    const progress = Math.round((currentPage/totalPages)*100);
    document.getElementById('progressText').textContent = progress+'%';
    document.getElementById('progressBar').style.width = progress+'%';
    document.getElementById('prevBtn').disabled = (currentPage <= 1);
    document.getElementById('nextBtn').classList.toggle('hidden', currentPage >= totalPages);
    document.getElementById('submitBtn').classList.toggle('hidden', currentPage < totalPages);
}

function buildPalette(){
    const palette = document.getElementById('questionPalette');
    const grid = document.getElementById('paletteGrid');
    if(!questionsData.length){ palette.classList.add('hidden'); return; }
    palette.classList.remove('hidden');
    let btns = '';
    for(let i=0; i<questionsData.length; i++){
        const qid = questionsData[i].id;
        const answered = answers[qid] ? true : false;
        const activePage = Math.floor(i/perPage)+1;
        btns += `<button type="button" class="w-8 h-8 rounded-full text-xs font-medium border transition ${
            answered ? 'bg-green-100 dark:bg-green-900/30 border-green-400 dark:border-green-600 text-green-800 dark:text-green-300' : 
            'bg-gray-100 dark:bg-gray-700 border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300'
        } ${activePage===currentPage?'ring-2 ring-shikhbo-primary dark:ring-indigo-400':''}" onclick="goToQuestion(${i})" title="Q${i+1}">${i+1}</button>`;
    }
    grid.innerHTML = btns;
}

function goToQuestion(index){
    const page = Math.floor(index/perPage)+1;
    if(page !== currentPage) { currentPage = page; showPage(currentPage); }
    setTimeout(()=>{ document.getElementById('q-card-'+questionsData[index].id)?.scrollIntoView({behavior:'smooth',block:'center'}); }, 100);
}

function startTimer(){
    const timerElem = document.getElementById('timer');
    const interval = setInterval(()=>{
        if(timer <= 0){ clearInterval(interval); submitExam(); return; }
        const mins = Math.floor(timer/60);
        const secs = timer%60;
        timerElem.textContent = `${mins}:${secs.toString().padStart(2,'0')}`;
        if(timer < 300) { timerElem.classList.add('text-red-500','dark:text-red-400'); timerElem.classList.remove('text-shikhbo-primary','dark:text-indigo-400'); }
        else { timerElem.classList.remove('text-red-500','dark:text-red-400'); timerElem.classList.add('text-shikhbo-primary','dark:text-indigo-400'); }
        timer--;
    }, 1000);
}

function showConfirmModal(msg, cb){
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 z-50 flex items-center justify-center';
    modal.innerHTML = `<div class="absolute inset-0 bg-black bg-opacity-50"></div><div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6 max-w-md mx-4 text-center"><p class="text-gray-800 dark:text-gray-100 mb-4">${msg.replace(/\n/g,'<br>')}</p><div class="flex justify-center gap-3"><button class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg cancel-btn">Cancel</button><button class="px-4 py-2 bg-shikhbo-primary text-white rounded-lg confirm-btn">Confirm</button></div></div>`;
    document.body.appendChild(modal);
    modal.querySelector('.cancel-btn').addEventListener('click', ()=>modal.remove());
    modal.querySelector('.confirm-btn').addEventListener('click', ()=>{ modal.remove(); cb(); });
}

function showResultModal(result){
    const isPassed = result.exam_status === 'PASSED';
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 z-50 flex items-center justify-center p-4';
    modal.innerHTML = `
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
        <div class="bg-gradient-to-r ${isPassed ? 'from-green-500 to-emerald-600' : 'from-orange-500 to-red-500'} p-6 text-center">
            <div class="w-20 h-20 mx-auto bg-white/20 rounded-full flex items-center justify-center mb-3">
                <i class="fa-solid ${isPassed ? 'fa-trophy' : 'fa-face-frown'} text-4xl text-white"></i>
            </div>
            <h2 class="text-2xl font-bold text-white">${isPassed ? 'Congratulations!' : 'Keep Trying!'}</h2>
            <p class="text-white/80 text-sm mt-1">Exam Completed</p>
        </div>
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-gray-50 dark:bg-gray-700/30 rounded-xl p-4 text-center">
                    <p class="text-2xl font-bold text-shikhbo-primary dark:text-indigo-400">${result.score}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Score</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700/30 rounded-xl p-4 text-center">
                    <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">${result.total_marks}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Total Marks</p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-gray-50 dark:bg-gray-700/30 rounded-xl p-4 text-center">
                    <p class="text-2xl font-bold ${result.percentage >= result.passing_percentage ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'}">${result.percentage}%</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Percentage</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700/30 rounded-xl p-4 text-center">
                    <p class="text-2xl font-bold ${isPassed ? 'text-green-600 dark:text-green-400' : 'text-orange-600 dark:text-orange-400'}">${result.percentage >= result.passing_percentage ? 'PASSED' : 'FAILED'}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Status</p>
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <a href="index.php?page=exam_attempt" class="flex-1 px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl text-center font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                    <i class="fa-solid fa-arrow-rotate-right mr-2"></i>Try Again
                </a>
                <a href="index.php?page=results" class="flex-1 px-4 py-3 bg-shikhbo-primary text-white rounded-xl text-center font-medium hover:bg-indigo-700 dark:hover:bg-indigo-600 transition">
                    <i class="fa-solid fa-chart-bar mr-2"></i>View Results
                </a>
            </div>
        </div>
    </div>`;
    document.body.appendChild(modal);
}

async function submitExam(){
    const answerArray = questionsData.map(q=>({question_id: q.id, selected_option: answers[q.id]||''}));
    try {
        const res = await fetch('/api/submit_exam_web.php', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({exam_id:examId,user_id:<?php echo $_SESSION['admin_id']??1; ?>,answers:answerArray})});
        const result = await res.json();
        if(result.status==='success'){
            localStorage.removeItem(STORAGE_KEY);
            showResultModal(result);
        } else { showToast('Submission error: '+(result.message||'Unknown'), 'error'); }
    } catch(e) { alert('Network error.'); }
}

document.getElementById('prevBtn').addEventListener('click', ()=>{ if(currentPage>1) showPage(currentPage-1); });
document.getElementById('nextBtn').addEventListener('click', ()=>{ if(currentPage<totalPages) showPage(currentPage+1); });
document.getElementById('submitBtn').addEventListener('click', ()=>{
    const unanswered = questionsData.length - Object.keys(answers).filter(k=>answers[k]!=='').length;
    let msg = 'Are you sure you want to submit?';
    if(unanswered>0) msg += `\n⚠️ You have ${unanswered} unanswered question(s).`;
    showConfirmModal(msg, submitExam);
});

startTimer();
loadAllQuestions();
</script>