<style>
.course-card{transition:all .4s cubic-bezier(.34,1.56,.64,1);opacity:0;transform:translateY(30px)}
.course-card.show{opacity:1;transform:translateY(0)}
.course-card:hover{transform:translateY(-6px)}
.spinner{width:22px;height:22px;border:3px solid #E2E8F0;border-top-color:#4F46E5;border-radius:50%;animation:spin .6s linear infinite;display:inline-block;vertical-align:middle}
@keyframes spin{to{transform:rotate(360deg)}}
.skel{background:linear-gradient(90deg,#E2E8F0 25%,#F1F5F9 50%,#E2E8F0 75%);background-size:200% 100%;animation:shimmer 1.5s infinite;border-radius:.75rem}
.dark .skel{background:linear-gradient(90deg,#334155 25%,#475569 50%,#334155 75%);background-size:200% 100%}
@keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
</style>

<div class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-indigo-900 to-purple-900" style="padding-top:100px;padding-bottom:52px">
    <div class="absolute inset-0 opacity-10" style="background-image:radial-gradient(circle at 25% 50%,rgba(255,255,255,.1) 0%,transparent 50%),radial-gradient(circle at 75% 30%,rgba(129,140,248,.15) 0%,transparent 50%)"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        <div class="inline-flex items-center justify-center w-14 h-14 bg-white/10 backdrop-blur-sm rounded-2xl mb-4 hero-fade-in">
            <i class="fa-solid fa-graduation-cap text-2xl text-indigo-300"></i>
        </div>
        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white mb-3 hero-fade-in delay-1">Course Catalog</h1>
        <p class="text-indigo-200/80 text-sm sm:text-base max-w-xl mx-auto hero-fade-in delay-2">Explore our curated collection of courses designed to help you master every subject</p>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-6 mb-10">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-[0_4px_24px_rgba(0,0,0,.06)] border border-gray-100 dark:border-gray-700 p-4 sm:p-5 flex flex-wrap gap-3 items-center backdrop-blur-sm">
        <div class="relative flex-1 min-w-[200px]">
            <i class="fa-solid fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
            <input type="text" id="searchInput" placeholder="Search courses..." oninput="filterCourses()" class="w-full pl-10 pr-4 py-2.5 border-2 border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-xl text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all">
        </div>
        <select id="catFilter" onchange="filterCourses()" class="px-4 py-2.5 border-2 border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-xl text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 min-w-[130px] bg-white dark:bg-gray-700 cursor-pointer">
            <option value="">All Categories</option>
        </select>
        <select id="difficultyFilter" onchange="filterCourses()" class="px-4 py-2.5 border-2 border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-xl text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 min-w-[130px] bg-white dark:bg-gray-700 cursor-pointer">
            <option value="">All Levels</option>
            <option value="beginner">Beginner</option>
            <option value="intermediate">Intermediate</option>
            <option value="advanced">Advanced</option>
        </select>
        <select id="sortFilter" onchange="filterCourses()" class="px-4 py-2.5 border-2 border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-xl text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 min-w-[130px] bg-white dark:bg-gray-700 cursor-pointer">
            <option value="popular">Most Popular</option>
            <option value="newest">Newest</option>
            <option value="price-low">Price: Low to High</option>
            <option value="price-high">Price: High to Low</option>
        </select>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-16">
    <div id="enrolledSection" class="mb-12 hidden anim-fade-up">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center">
                <i class="fa-solid fa-circle-check text-emerald-500"></i>
            </div>
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-gray-100">My Enrolled Courses</h2>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5" id="enrolledGrid"></div>
        <hr class="my-10 border-gray-200 dark:border-gray-700">
    </div>

    <div class="flex items-center justify-between mb-6 anim-fade-up">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl flex items-center justify-center">
                <i class="fa-solid fa-layer-group text-indigo-500"></i>
            </div>
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-gray-100">All Courses</h2>
        </div>
        <span id="courseCount" class="text-xs text-gray-400 bg-gray-100 dark:bg-gray-800 px-3 py-1.5 rounded-full font-medium"></span>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5" id="courseGrid"></div>
</div>

<div id="skeletonGrid" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-16 hidden">
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
        <div class="bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow-sm border border-gray-100 dark:border-gray-700"><div class="skel h-40" style="border-radius:0"></div><div class="p-4 space-y-3"><div class="skel h-3 w-16"></div><div class="skel h-4 w-3/4"></div><div class="skel h-3 w-full"></div><div class="flex gap-4"><div class="skel h-3 w-12"></div><div class="skel h-3 w-12"></div></div></div></div>
        <div class="bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow-sm border border-gray-100 dark:border-gray-700"><div class="skel h-40" style="border-radius:0"></div><div class="p-4 space-y-3"><div class="skel h-3 w-16"></div><div class="skel h-4 w-3/4"></div><div class="skel h-3 w-full"></div><div class="flex gap-4"><div class="skel h-3 w-12"></div><div class="skel h-3 w-12"></div></div></div></div>
        <div class="bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow-sm border border-gray-100 dark:border-gray-700"><div class="skel h-40" style="border-radius:0"></div><div class="p-4 space-y-3"><div class="skel h-3 w-16"></div><div class="skel h-4 w-3/4"></div><div class="skel h-3 w-full"></div><div class="flex gap-4"><div class="skel h-3 w-12"></div><div class="skel h-3 w-12"></div></div></div></div>
    </div>
</div>

<script>
var _uid = <?php echo $loggedInUser ? intval($loggedInUser['id']) : 0; ?>;
var allCourses = [];

function showToast(msg, type) {
    type = type || 'info';
    var c = document.getElementById('toastContainer');
    if (!c) { c = document.createElement('div'); c.id = 'toastContainer'; c.className = 'fixed bottom-4 right-4 z-[100] flex flex-col-reverse gap-2'; document.body.appendChild(c); }
    var el = document.createElement('div');
    var bg = type === 'success' ? '#10B981' : type === 'error' ? '#EF4444' : '#4F46E5';
    el.style.cssText = 'padding:12px 20px;border-radius:10px;color:#fff;font-weight:600;font-size:.85rem;box-shadow:0 8px 24px rgba(0,0,0,.15);background:'+bg;
    el.textContent = msg;
    c.appendChild(el);
    setTimeout(function(){ el.style.opacity='0'; el.style.transition='opacity .3s'; setTimeout(function(){ el.remove(); },300); }, 2500);
}

function showSkeleton(on) {
    document.getElementById('skeletonGrid').classList.toggle('hidden', !on);
    document.getElementById('courseGrid').classList.toggle('hidden', on);
}

async function loadData() {
    showSkeleton(true);
    try {
        var res = await fetch('/api/get_courses_app.php');
        var data = await res.json();
        showSkeleton(false);
        if (data.status !== 'success') { showToast(data.message||'Failed to load','error'); return; }
        allCourses = data.courses || [];
        var catSel = document.getElementById('catFilter');
        var catSet = new Set();
        allCourses.forEach(function(c){ if (c.category_name) catSet.add(c.category_name); });
        [...catSet].sort().forEach(function(c){ var o = document.createElement('option'); o.value=c; o.textContent=c; catSel.appendChild(o); });
        filterCourses();
        if (_uid > 0) loadEnrolled();
    } catch(e) {
        showSkeleton(false);
        document.getElementById('courseGrid').innerHTML = '<div class="col-span-full text-center py-24 text-gray-400"><i class="fa-solid fa-wifi-slash text-4xl block mb-3"></i><p class="text-sm">Failed to load courses. Check your connection.</p></div>';
    }
}

async function loadEnrolled() {
    try {
        var res = await fetch('/api/get_courses_app.php?enrolled=1');
        var data = await res.json();
        var enrolled = data.courses || [];
        if (enrolled.length === 0) return;
        var grid = document.getElementById('enrolledGrid');
        document.getElementById('enrolledSection').classList.remove('hidden');
        grid.innerHTML = '';
        enrolled.forEach(function(c, i){
            var card = document.createElement('div');
            card.className = 'bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col transition-all duration-300 hover:shadow-md hover:-translate-y-1';
            card.innerHTML = '<div class="h-36 bg-gradient-to-br from-indigo-100 to-purple-100 dark:from-indigo-900/20 dark:to-purple-900/20 flex items-center justify-center text-3xl text-indigo-300 relative overflow-hidden">'+(c.cover_image?'<img src="'+c.cover_image+'" class="w-full h-full object-cover">':'<i class="fa-solid fa-graduation-cap"></i>')+'<div class="absolute top-3 right-3 bg-emerald-500 text-white text-[11px] px-2.5 py-1 rounded-full font-bold flex items-center gap-1 shadow shadow-emerald-300/30"><i class="fa-solid fa-check text-[10px]"></i> Enrolled</div></div><div class="p-4"><span class="text-[11px] font-bold tracking-wider text-indigo-600 dark:text-indigo-400 uppercase">'+(c.category_name||'General')+'</span><h3 class="font-bold text-gray-900 dark:text-gray-100 text-sm mt-1 line-clamp-1">'+(c.title||'')+'</h3><div class="flex items-center gap-4 text-xs text-gray-400 mt-3"><span><i class="fa-regular fa-clock mr-1"></i>'+(c.duration_hours||0)+'h</span><span><i class="fa-solid fa-signal mr-1"></i>'+(c.difficulty||'beginner')+'</span></div></div>';
            grid.appendChild(card);
            setTimeout(function(){ card.style.opacity='0'; card.style.transform='translateY(20px)'; requestAnimationFrame(function(){ card.style.transition='all .5s ease'; card.style.opacity='1'; card.style.transform='translateY(0)'; }); }, i*80);
        });
    } catch(e) {}
}

function filterCourses() {
    var search = document.getElementById('searchInput').value.toLowerCase();
    var cat = document.getElementById('catFilter').value;
    var diff = document.getElementById('difficultyFilter').value;
    var sort = document.getElementById('sortFilter').value;
    var filtered = allCourses.filter(function(c){
        if (search && !(c.title||'').toLowerCase().includes(search) && !(c.short_description||'').toLowerCase().includes(search)) return false;
        if (cat && c.category_name !== cat) return false;
        if (diff && c.difficulty !== diff) return false;
        return true;
    });
    if (sort === 'newest') filtered.sort(function(a,b){ return new Date(b.created_at||0) - new Date(a.created_at||0); });
    else if (sort === 'price-low') filtered.sort(function(a,b){ return parseFloat(a.price||0) - parseFloat(b.price||0); });
    else if (sort === 'price-high') filtered.sort(function(a,b){ return parseFloat(b.price||0) - parseFloat(a.price||0); });
    else filtered.sort(function(a,b){ return (b.total_enrolled||0) - (a.total_enrolled||0); });
    renderCourses(filtered);
}

function renderCourses(courses) {
    var grid = document.getElementById('courseGrid');
    document.getElementById('courseCount').textContent = courses.length + ' course' + (courses.length !== 1 ? 's' : '');
    if (courses.length === 0) {
        grid.innerHTML = '<div class="col-span-full text-center py-24 text-gray-400"><i class="fa-solid fa-search text-4xl block mb-3"></i><p class="text-base font-medium text-gray-500 dark:text-gray-300 mb-1">No courses found</p><p class="text-sm">Try adjusting your filters</p></div>';
        return;
    }
    grid.innerHTML = '';
    courses.forEach(function(c, i){
        var price = parseFloat(c.price||0);
        var isFree = c.is_free == 1 || price === 0;
        var enrolled = c.enrollment_status || '';
        var card = document.createElement('div');
        card.className = 'course-card bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col';
        card.style.boxShadow = '0 1px 3px rgba(0,0,0,.04)';
        card.addEventListener('mouseenter', function(){ this.style.boxShadow='0 20px 40px -12px rgba(79,70,229,.2)'; });
        card.addEventListener('mouseleave', function(){ this.style.boxShadow='0 1px 3px rgba(0,0,0,.04)'; });
        var badge = '';
        if (enrolled === 'active') badge = '<div class="absolute top-3 right-3 bg-emerald-500 text-white text-[11px] px-2.5 py-1 rounded-full font-bold flex items-center gap-1 shadow shadow-emerald-300/30"><i class="fa-solid fa-check text-[10px]"></i> Enrolled</div>';
        else badge = '<div class="absolute top-3 right-3 bg-gradient-to-r '+(isFree?'from-emerald-500 to-emerald-600':'from-indigo-500 to-purple-600')+' text-white px-3 py-1 rounded-full text-xs font-bold shadow-sm">'+(isFree?'FREE':'৳'+price)+'</div>';
        var btn = '';
        if (enrolled === 'active') {
            btn = '<button disabled class="w-full py-2.5 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-lg text-sm font-semibold flex items-center justify-center gap-2 cursor-default border border-emerald-200 dark:border-emerald-800"><i class="fa-solid fa-check-circle"></i> Enrolled</button>';
        } else if (enrolled === 'pending_payment') {
            btn = '<button onclick="checkoutCourse('+c.id+')" class="w-full py-2.5 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white rounded-lg text-sm font-semibold transition-all flex items-center justify-center gap-2 shadow-sm shadow-amber-200"><i class="fa-solid fa-clock"></i> Complete Payment</button>';
        } else if (isFree) {
            btn = '<button onclick="enrollFree('+c.id+')" class="w-full py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-lg text-sm font-semibold transition-all flex items-center justify-center gap-2 shadow-sm shadow-indigo-200"><i class="fa-solid fa-graduation-cap"></i> Enroll Free</button>';
        } else {
            btn = '<button onclick="checkoutCourse('+c.id+')" class="w-full py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-lg text-sm font-semibold transition-all flex items-center justify-center gap-2 shadow-sm shadow-indigo-200"><i class="fa-solid fa-cart-plus"></i> Buy ৳'+price+'</button>';
        }
        card.innerHTML = '<div class="h-40 bg-gradient-to-br from-indigo-100 to-purple-100 dark:from-indigo-900/20 dark:to-purple-900/20 flex items-center justify-center text-4xl text-indigo-300 relative overflow-hidden">'+(c.cover_image?'<img src="'+c.cover_image+'" class="w-full h-full object-cover">':'<i class="fa-solid fa-graduation-cap"></i>')+badge+'</div><div class="p-4 flex flex-col flex-1"><span class="text-[11px] font-bold tracking-wider text-indigo-600 dark:text-indigo-400 uppercase">'+(c.category_name||'General')+'</span><h3 class="font-bold text-gray-900 dark:text-gray-100 text-sm mt-1 mb-1.5 line-clamp-1">'+(c.title||'')+'</h3><p class="text-xs text-gray-500 dark:text-gray-400 mb-3 line-clamp-2 flex-1">'+(c.short_description||c.description||'')+'</p><div class="flex items-center gap-4 text-xs text-gray-400 mb-3"><span><i class="fa-regular fa-clock mr-1"></i>'+(c.duration_hours||0)+'h</span><span><i class="fa-solid fa-users mr-1"></i>'+(c.total_enrolled||0)+'</span><span><i class="fa-solid fa-signal mr-1"></i>'+(c.difficulty||'beginner')+'</span></div>'+btn+'</div>';
        grid.appendChild(card);
        setTimeout(function(){ card.classList.add('show'); }, i*60);
    });
}

function enrollFree(courseId) {
    showToast('Enrolling, please wait...', 'success');
    fetch('/api/enroll_course_app.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ course_id: courseId, action:'enroll', uid: _uid, season:'web', u_state:'1' })
    }).then(function(r){ return r.json(); }).then(function(d){
        if (d.status === 'success') { showToast('Enrolled successfully!', 'success'); setTimeout(function(){ location.reload(); }, 1500); }
        else { showToast(d.message || 'Enrollment failed', 'error'); }
    }).catch(function(){ showToast('Connection error during enrollment', 'error'); });
}

loadData();
</script>