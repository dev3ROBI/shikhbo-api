<?php
$mysqli = getDBConnection();

$totalStudents = $mysqli->query("SELECT COUNT(*) as count FROM users WHERE (role IS NULL OR role = '' OR role = 'user')")->fetch_assoc()['count'];
$totalAdmins   = $mysqli->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")->fetch_assoc()['count'];
$totalExams    = $mysqli->query("SELECT COUNT(*) as count FROM exams")->fetch_assoc()['count'] ?? 0;
$activeExams   = $mysqli->query("SELECT COUNT(*) as count FROM exams WHERE status='active'")->fetch_assoc()['count'] ?? 0;
$totalQuestions= $mysqli->query("SELECT COUNT(*) as count FROM questions")->fetch_assoc()['count'] ?? 0;
$totalResults  = $mysqli->query("SELECT COUNT(*) as count FROM exam_results")->fetch_assoc()['count'] ?? 0;
$passCount     = $mysqli->query("SELECT COUNT(*) as count FROM exam_results WHERE status='passed'")->fetch_assoc()['count'] ?? 0;
$todayStudents = $mysqli->query("SELECT COUNT(*) as count FROM users WHERE (role IS NULL OR role = '' OR role = 'user') AND DATE(created_at)=CURDATE()")->fetch_assoc()['count'];
$rootCategories = $mysqli->query("SELECT COUNT(*) as count FROM exam_categories WHERE parent_id IS NULL")->fetch_assoc()['count'];
$totalCategories= $mysqli->query("SELECT COUNT(*) as count FROM exam_categories")->fetch_assoc()['count'];
$passRate = $totalResults > 0 ? round(($passCount / $totalResults) * 100, 1) : 0;

$recentResults = $mysqli->query("SELECT r.id, u.name AS student_name, e.title AS exam_title, r.score, r.total_marks, r.percentage, r.status, r.completed_at FROM exam_results r JOIN users u ON r.user_id = u.id JOIN exams e ON r.exam_id = e.id ORDER BY r.completed_at DESC LIMIT 5");
$recentStudents = $mysqli->query("SELECT id, name, email, status, created_at FROM users WHERE (role IS NULL OR role = '' OR role = 'user') ORDER BY created_at DESC LIMIT 5");
?>
<div class="page-content">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 dark:text-gray-100">Admin Dashboard</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Welcome back, <?php echo sanitizeOutput($_SESSION['admin_name']); ?>!</p>
        </div>
        <div class="flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
            <span class="hidden sm:inline-flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <i class="fa-solid fa-calendar-day text-indigo-500"></i>
                <span><?php echo date('l, F j, Y'); ?></span>
            </span>
            <span class="inline-flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <i class="fa-solid fa-clock text-indigo-500"></i>
                <span id="liveClock"><?php echo date('H:i'); ?></span>
            </span>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
        <!-- Total Students -->
        <div class="stat-card card-hover bg-white dark:bg-gray-800 rounded-2xl shadow-md p-5 border border-gray-100 dark:border-gray-700">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Students</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-gray-100 mt-2"><?php echo number_format($totalStudents); ?></p>
                    <div class="flex items-center gap-2 mt-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                            <i class="fa-solid fa-arrow-up mr-1"></i><?php echo $todayStudents; ?> today
                        </span>
                    </div>
                </div>
                <div class="stat-icon bg-gradient-to-br from-green-400 to-green-600">
                    <i class="fa-solid fa-users text-white text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Active Exams -->
        <div class="stat-card card-hover bg-white dark:bg-gray-800 rounded-2xl shadow-md p-5 border border-gray-100 dark:border-gray-700">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Exams</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-gray-100 mt-2"><?php echo number_format($activeExams); ?></p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">of <?php echo number_format($totalExams); ?> total</p>
                </div>
                <div class="stat-icon bg-gradient-to-br from-blue-400 to-blue-600">
                    <i class="fa-solid fa-file-alt text-white text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Questions -->
        <div class="stat-card card-hover bg-white dark:bg-gray-800 rounded-2xl shadow-md p-5 border border-gray-100 dark:border-gray-700">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Question Bank</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-gray-100 mt-2"><?php echo number_format($totalQuestions); ?></p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-2"><?php echo number_format($totalCategories); ?> categories</p>
                </div>
                <div class="stat-icon bg-gradient-to-br from-purple-400 to-purple-600">
                    <i class="fa-solid fa-database text-white text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Pass Rate -->
        <div class="stat-card card-hover bg-white dark:bg-gray-800 rounded-2xl shadow-md p-5 border border-gray-100 dark:border-gray-700">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pass Rate</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-gray-100 mt-2"><?php echo $passRate; ?>%</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-2"><?php echo number_format($totalResults); ?> attempts</p>
                </div>
                <div class="stat-icon bg-gradient-to-br from-indigo-400 to-indigo-600">
                    <i class="fa-solid fa-chart-line text-white text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-4 mb-8 border border-gray-100 dark:border-gray-700">
        <div class="flex items-center gap-2 mb-4">
            <i class="fa-solid fa-bolt text-yellow-500"></i>
            <h3 class="font-semibold text-gray-800 dark:text-gray-100">Quick Actions</h3>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-2">
            <a href="index.php?page=exams" class="quick-action">
                <div class="w-12 h-12 mx-auto mb-2 rounded-xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-plus-circle text-blue-500 text-lg"></i>
                </div>
                <span class="text-xs font-medium text-gray-600 dark:text-gray-300">New Exam</span>
            </a>
            <a href="index.php?page=questions" class="quick-action">
                <div class="w-12 h-12 mx-auto mb-2 rounded-xl bg-green-50 dark:bg-green-900/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-circle-plus text-green-500 text-lg"></i>
                </div>
                <span class="text-xs font-medium text-gray-600 dark:text-gray-300">Add Question</span>
            </a>
            <a href="index.php?page=categories" class="quick-action">
                <div class="w-12 h-12 mx-auto mb-2 rounded-xl bg-purple-50 dark:bg-purple-900/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-layer-group text-purple-500 text-lg"></i>
                </div>
                <span class="text-xs font-medium text-gray-600 dark:text-gray-300">Categories</span>
            </a>
            <a href="index.php?page=students" class="quick-action">
                <div class="w-12 h-12 mx-auto mb-2 rounded-xl bg-orange-50 dark:bg-orange-900/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-user-plus text-orange-500 text-lg"></i>
                </div>
                <span class="text-xs font-medium text-gray-600 dark:text-gray-300">Students</span>
            </a>
            <a href="index.php?page=results" class="quick-action">
                <div class="w-12 h-12 mx-auto mb-2 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-chart-bar text-indigo-500 text-lg"></i>
                </div>
                <span class="text-xs font-medium text-gray-600 dark:text-gray-300">Results</span>
            </a>
            <a href="index.php?page=admins" class="quick-action">
                <div class="w-12 h-12 mx-auto mb-2 rounded-xl bg-yellow-50 dark:bg-yellow-900/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-user-gear text-yellow-500 text-lg"></i>
                </div>
                <span class="text-xs font-medium text-gray-600 dark:text-gray-300">Admins</span>
            </a>
            <a href="index.php?page=database" class="quick-action">
                <div class="w-12 h-12 mx-auto mb-2 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-terminal text-gray-600 dark:text-gray-300 text-lg"></i>
                </div>
                <span class="text-xs font-medium text-gray-600 dark:text-gray-300">Database</span>
            </a>
            <a href="index.php?page=settings" class="quick-action">
                <div class="w-12 h-12 mx-auto mb-2 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-gear text-gray-600 dark:text-gray-300 text-lg"></i>
                </div>
                <span class="text-xs font-medium text-gray-600 dark:text-gray-300">Settings</span>
            </a>
        </div>
    </div>

    <!-- Two Column Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Results -->
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl shadow-md overflow-hidden border border-gray-100 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center">
                        <i class="fa-solid fa-chart-line text-indigo-500 text-sm"></i>
                    </div>
                    <h3 class="font-semibold text-gray-800 dark:text-gray-100">Recent Exam Results</h3>
                </div>
                <a href="index.php?page=results" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline font-medium">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="table-header">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Student</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider hide-mobile">Exam</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Score</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                        <?php if ($recentResults && $recentResults->num_rows > 0): ?>
                            <?php while ($r = $recentResults->fetch_assoc()): ?>
                                <tr class="table-row">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($r['student_name']); ?>&background=4F46E5&color=fff&size=40&bold=true" class="w-9 h-9 rounded-full">
                                            <span class="font-medium text-gray-800 dark:text-gray-100"><?php echo sanitizeOutput($r['student_name']); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 hide-mobile">
                                        <span class="text-sm text-gray-600 dark:text-gray-300"><?php echo sanitizeOutput($r['exam_title']); ?></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <span class="font-semibold text-gray-800 dark:text-gray-100"><?php echo $r['score']; ?>/<?php echo $r['total_marks']; ?></span>
                                            <span class="text-xs text-gray-400"><?php echo round($r['percentage']); ?>%</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($r['status'] === 'passed'): ?>
                                            <span class="badge bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                                <i class="fa-solid fa-check mr-1"></i>Passed
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                                <i class="fa-solid fa-xmark mr-1"></i>Failed
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <i class="fa-solid fa-clipboard-list text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                                    <p class="text-gray-500 dark:text-gray-400">No results yet</p>
                                </div>
                            </td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Students -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md overflow-hidden border border-gray-100 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-green-50 dark:bg-green-900/30 flex items-center justify-center">
                        <i class="fa-solid fa-users text-green-500 text-sm"></i>
                    </div>
                    <h3 class="font-semibold text-gray-800 dark:text-gray-100">New Students</h3>
                </div>
                <a href="index.php?page=students" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline font-medium">View All</a>
            </div>
            <div class="divide-y divide-gray-50 dark:divide-gray-700">
                <?php if ($recentStudents && $recentStudents->num_rows > 0): ?>
                    <?php while ($stu = $recentStudents->fetch_assoc()): ?>
                        <div class="px-6 py-4 flex items-center gap-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($stu['name']); ?>&background=4F46E5&color=fff&size=48&bold=true" class="w-11 h-11 rounded-xl">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate"><?php echo sanitizeOutput($stu['name']); ?></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate"><?php echo sanitizeOutput($stu['email']); ?></p>
                            </div>
                            <div class="text-right">
                                <span class="badge <?php echo $stu['status'] === 'active' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'; ?>">
                                    <?php echo ucfirst($stu['status']); ?>
                                </span>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1"><?php echo date('M j', strtotime($stu['created_at'])); ?></p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="px-6 py-12 text-center">
                        <i class="fa-solid fa-user-slash text-4xl text-gray-300 dark:text-gray-600 mb-3 block"></i>
                        <p class="text-gray-500 dark:text-gray-400">No students yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.quick-action {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 12px 8px;
    border-radius: 12px;
    transition: all 0.2s ease;
}
.quick-action:hover {
    background: rgba(79, 70, 229, 0.05);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function updateClock() {
        const clock = document.getElementById('liveClock');
        if (clock) {
            const now = new Date();
            clock.textContent = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        }
    }
    updateClock();
    setInterval(updateClock, 1000);
});
</script>