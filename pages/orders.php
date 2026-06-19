<style>
.tab-btn{padding:8px 22px;border:2px solid #E2E8F0;border-radius:10px;background:#fff;font-weight:600;font-size:.85rem;cursor:pointer;transition:all .2s}
.dark .tab-btn{background:#1E293B;border-color:#374151;color:#D1D5DB}
.tab-btn.active{background:#4F46E5;color:#fff;border-color:#4F46E5;box-shadow:0 4px 12px rgba(79,70,229,.25)}
.tab-btn:hover:not(.active){border-color:#4F46E5;color:#4F46E5}
.dark .tab-btn:hover:not(.active){border-color:#818CF8;color:#818CF8}
.order-card{transition:all .3s ease;opacity:0;transform:translateY(20px)}
.order-card.show{opacity:1;transform:translateY(0)}
.order-card:hover{box-shadow:0 8px 28px rgba(0,0,0,.08);transform:translateY(-2px)}
.spinner{width:24px;height:24px;border:3px solid #E2E8F0;border-top-color:#4F46E5;border-radius:50%;animation:spin .6s linear infinite;display:inline-block}
@keyframes spin{to{transform:rotate(360deg)}}
</style>

<div class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-indigo-900 to-purple-900" style="padding-top:100px;padding-bottom:52px">
    <div class="absolute inset-0 opacity-10" style="background-image:radial-gradient(circle at 75% 25%,rgba(255,255,255,.1) 0%,transparent 50%),radial-gradient(circle at 25% 75%,rgba(129,140,248,.15) 0%,transparent 50%)"></div>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        <div class="inline-flex items-center justify-center w-14 h-14 bg-white/10 backdrop-blur-sm rounded-2xl mb-4 hero-fade-in">
            <i class="fa-solid fa-receipt text-2xl text-indigo-300"></i>
        </div>
        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white mb-3 hero-fade-in delay-1">My Orders</h1>
        <p class="text-indigo-200/80 text-sm sm:text-base max-w-xl mx-auto hero-fade-in delay-2">Track your purchases, view payment status, and manage your enrollments</p>
    </div>
</div>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 -mt-5 mb-8">
    <div class="flex flex-wrap gap-2" id="orderTabs">
        <button class="tab-btn active" data-filter="">All</button>
        <button class="tab-btn" data-filter="completed">Completed</button>
        <button class="tab-btn" data-filter="pending">Pending</button>
        <button class="tab-btn" data-filter="cancelled">Cancelled</button>
    </div>
</div>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pb-16">
    <div id="ordersContainer" class="space-y-4"><div id="ordersSkeleton"></div></div>
    <div class="flex justify-center gap-2 mt-8" id="pagination"></div>
</div>

<script>
var currentPage = 1, currentFilter = '', totalPages = 1;

function skeletonHTML(n) {
    var h = '';
    for (var i=0; i<n; i++) {
        h += '<div class="bg-white dark:bg-gray-800 rounded-2xl p-5 sm:p-6 shadow-sm border border-gray-100 dark:border-gray-700"><div class="flex items-start justify-between mb-4"><div class="flex items-center gap-3"><div class="skel w-10 h-10 rounded-xl"></div><div><div class="skel h-4 w-36 mb-1.5"></div><div class="skel h-3 w-16"></div></div></div><div class="skel h-6 w-20 rounded-full"></div></div><div class="flex gap-2 mb-4"><div class="skel h-6 w-24 rounded-lg"></div><div class="skel h-6 w-20 rounded-lg"></div></div><div class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-700"><div class="skel h-7 w-20"></div><div class="skel h-4 w-28"></div></div></div>';
    }
    return h;
}
</script>
<style>.skel{background:linear-gradient(90deg,#E2E8F0 25%,#F1F5F9 50%,#E2E8F0 75%);background-size:200% 100%;animation:shimmer 1.5s infinite;border-radius:.5rem}.dark .skel{background:linear-gradient(90deg,#334155 25%,#475569 50%,#334155 75%);background-size:200% 100%}@keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}</style>
<script>
document.getElementById('ordersSkeleton').innerHTML = skeletonHTML(3);

document.querySelectorAll('#orderTabs .tab-btn').forEach(function(b){
    b.addEventListener('click', function(){
        document.querySelectorAll('#orderTabs .tab-btn').forEach(function(x){ x.classList.remove('active'); });
        b.classList.add('active');
        currentFilter = b.dataset.filter;
        currentPage = 1;
        loadOrders();
    });
});

async function loadOrders() {
    var c = document.getElementById('ordersContainer');
    c.innerHTML = '<div class="space-y-4">'+skeletonHTML(3)+'</div>';
    try {
        var res = await fetch('/api/orders_get.php?page='+currentPage+'&status='+currentFilter);
        var bodyText = await res.text();
        var data;
        try { data = JSON.parse(bodyText); } catch(e) { data = null; }
        if (!data || data.status !== 'success') {
            var msg = data ? (data.message || 'An unexpected error occurred') : bodyText;
            var icon = data ? 'fa-circle-exclamation' : 'fa-bug';
            c.innerHTML = '<div class="text-center py-24 text-gray-400"><i class="fa-solid '+icon+' text-4xl block mb-3"></i><p class="text-base font-medium text-gray-500 dark:text-gray-300 mb-1">'+(data?'Failed to load':'Server Error')+'</p><p class="text-sm max-w-md mx-auto break-words">'+(msg.length>200?msg.slice(0,200)+'...':msg)+'</p><p class="text-xs text-gray-400 mt-3">HTTP '+(res.status||'?')+'</p></div>';
            return;
        }
        totalPages = data.pages || 1;
        if (!data.orders || data.orders.length === 0) {
            var icon = currentFilter ? 'fa-filter-circle-xmark' : 'fa-receipt';
            var msg = currentFilter ? 'No <strong>'+currentFilter+'</strong> orders found' : 'No orders yet';
            c.innerHTML = '<div class="text-center py-24 text-gray-400"><i class="fa-solid '+icon+' text-5xl block mb-3"></i><h3 class="text-lg font-semibold text-gray-600 dark:text-gray-300 mb-1">'+msg+'</h3><p class="text-sm">'+(currentFilter?'Try a different filter':'Your purchases will appear here once you enroll in a course')+'</p></div>';
        } else {
            c.innerHTML = data.orders.map(renderOrder).join('');
            requestAnimationFrame(function(){
                var cards = c.querySelectorAll('.order-card');
                cards.forEach(function(el, i){
                    setTimeout(function(){ el.classList.add('show'); }, i*80);
                });
            });
        }
        renderPagination();
    } catch(e) {
        c.innerHTML = '<div class="text-center py-24 text-gray-400"><i class="fa-solid fa-wifi-slash text-4xl block mb-3"></i><p class="text-base font-medium text-gray-500 dark:text-gray-300 mb-1">Network Error</p><p class="text-sm">'+e.message+'</p></div>';
    }
}

function renderOrder(o) {
    var courses = (o.course_list && o.course_list.length) ? o.course_list.filter(Boolean).map(function(c){ return '<span class="inline-block bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 px-3 py-1 rounded-lg text-xs font-medium mr-1.5 mb-1">'+c+'</span>'; }).join('') : '<span class="text-gray-400 text-sm italic">Course</span>';
    var status = o.status || 'pending';
    var statusIcon = status === 'completed' ? 'fa-circle-check' : status === 'pending' ? 'fa-clock' : 'fa-circle-xmark';
    var statusBg = status === 'completed' ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800' : status === 'pending' ? 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800' : 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border-red-200 dark:border-red-800';
    var date = o.created_at ? new Date(o.created_at).toLocaleDateString('en-US', { year:'numeric', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' }) : '--';
    return '<div class="order-card bg-white dark:bg-gray-800 rounded-2xl p-5 sm:p-6 shadow-sm border border-gray-100 dark:border-gray-700">'+
        '<div class="flex items-start justify-between mb-4 flex-wrap gap-3">'+
            '<div class="flex items-center gap-3">'+
                '<div class="w-10 h-10 bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-xl flex items-center justify-center flex-shrink-0 shadow-sm"><i class="fa-solid fa-receipt text-indigo-500"></i></div>'+
                '<div><div class="font-bold text-gray-900 dark:text-gray-100 text-sm font-mono">'+(o.order_id||'N/A')+'</div><div class="text-xs text-gray-400 mt-0.5">'+(o.item_count||1)+' item'+(o.item_count>1?'s':'')+'</div></div>'+
            '</div>'+
            '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold capitalize border '+statusBg+'"><i class="fa-solid '+statusIcon+' text-[10px]"></i> '+status+'</span>'+
        '</div>'+
        '<div class="mb-4">'+courses+'</div>'+
        '<div class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-700">'+
            '<div class="text-xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">৳'+(parseFloat(o.total_amount||0).toFixed(2))+'</div>'+
            '<div class="text-xs text-gray-400 flex items-center gap-1.5"><i class="fa-regular fa-calendar"></i> '+date+'</div>'+
        '</div></div>';
}

function renderPagination() {
    var p = document.getElementById('pagination');
    if (totalPages <= 1) { p.innerHTML = ''; return; }
    var html = '<button '+(currentPage<=1?'disabled':'')+' onclick="goPage('+(currentPage-1)+')" class="px-3.5 py-1.5 border border-gray-200 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 '+(currentPage<=1?'opacity-40 cursor-not-allowed':'hover:bg-gray-50 dark:hover:bg-gray-700')+'"><i class="fa-solid fa-chevron-left"></i></button>';
    for (var i=1; i<=totalPages; i++) {
        html += '<button onclick="goPage('+i+')" class="px-3.5 py-1.5 border rounded-lg text-sm font-medium '+(i===currentPage?'bg-indigo-600 text-white border-indigo-600 shadow-sm shadow-indigo-200':'border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700')+'">'+i+'</button>';
    }
    html += '<button '+(currentPage>=totalPages?'disabled':'')+' onclick="goPage('+(currentPage+1)+')" class="px-3.5 py-1.5 border border-gray-200 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 '+(currentPage>=totalPages?'opacity-40 cursor-not-allowed':'hover:bg-gray-50 dark:hover:bg-gray-700')+'"><i class="fa-solid fa-chevron-right"></i></button>';
    p.innerHTML = html;
}

function goPage(p) { currentPage = p; loadOrders(); window.scrollTo({top:0,behavior:'smooth'}); }
loadOrders();
</script>