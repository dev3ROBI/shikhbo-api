<?php
$mysqli = getDBConnection();

// Real-time stats
$totalStudents = $mysqli->query("SELECT COUNT(*) as count FROM users WHERE (role IS NULL OR role = '' OR role = 'user')")->fetch_assoc()['count'];
$totalAdmins   = $mysqli->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")->fetch_assoc()['count'];
$totalExams    = $mysqli->query("SELECT COUNT(*) as count FROM exams")->fetch_assoc()['count'] ?? 0;
$activeExams   = $mysqli->query("SELECT COUNT(*) as count FROM exams WHERE status='active'")->fetch_assoc()['count'] ?? 0;
$totalQuestions= $mysqli->query("SELECT COUNT(*) as count FROM questions")->fetch_assoc()['count'] ?? 0;
$totalResults  = $mysqli->query("SELECT COUNT(*) as count FROM exam_results")->fetch_assoc()['count'] ?? 0;
$passCount     = $mysqli->query("SELECT COUNT(*) as count FROM exam_results WHERE status='passed'")->fetch_assoc()['count'] ?? 0;

// Today's new students
$todayStudents = $mysqli->query("SELECT COUNT(*) as count FROM users WHERE (role IS NULL OR role = '' OR role = 'user') AND DATE(created_at)=CURDATE()")->fetch_assoc()['count'];

// Recent activity (last 10 actions from results + new users)
$recentResults = $mysqli->query("
    SELECT r.id, u.name AS student_name, e.title AS exam_title, r.score, r.total_marks, r.percentage, r.status, r.completed_at
    FROM exam_results r JOIN users u ON r.user_id = u.id JOIN exams e ON r.exam_id = e.id
    ORDER BY r.completed_at DESC LIMIT 5
");

$recentStudents = $mysqli->query("
    SELECT id, name, email, status, created_at FROM users
    WHERE (role IS NULL OR role = '' OR role = 'user')
    ORDER BY created_at DESC LIMIT 5
");

// Category stats
$rootCategories = $mysqli->query("SELECT COUNT(*) as count FROM exam_categories WHERE parent_id IS NULL")->fetch_assoc()['count'];
$totalCategories= $mysqli->query("SELECT COUNT(*) as count FROM exam_categories")->fetch_assoc()['count'];

// Pass rate
$passRate = $totalResults > 0 ? round(($passCount / $totalResults) * 100, 1) : 0;
?>
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Admin Dashboard</h1>
            <p class="text-gray-500 mt-1">Welcome back, <?php echo sanitizeOutput($_SESSION['admin_name']); ?>!</p>
        </div>
        <div class="flex items-center space-x-2 text-sm text-gray-500">
            <i class="fa-solid fa-calendar-day"></i>
            <span><?php echo date('l, F j, Y'); ?></span>
        </div>
    </div>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    <!-- Total Students -->
    <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-green-500 transition-all hover:shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Students</p>
                <p class="text-3xl font-bold text-gray-800 mt-1"><?php echo number_format($totalStudents); ?></p>
                <p class="text-xs text-green-600 mt-1">+<?php echo $todayStudents; ?> today</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                <i class="fa-solid fa-users text-green-600 text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Active Exams -->
    <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-blue-500 transition-all hover:shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Active Exams</p>
                <p class="text-3xl font-bold text-gray-800 mt-1"><?php echo number_format($activeExams); ?></p>
                <p class="text-xs text-gray-500 mt-1">of <?php echo number_format($totalExams); ?> total</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                <i class="fa-solid fa-file-alt text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Questions -->
    <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-purple-500 transition-all hover:shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Question Bank</p>
                <p class="text-3xl font-bold text-gray-800 mt-1"><?php echo number_format($totalQuestions); ?></p>
                <p class="text-xs text-gray-500 mt-1"><?php echo $totalCategories; ?> categories</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                <i class="fa-solid fa-database text-purple-600 text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Pass Rate -->
    <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-indigo-500 transition-all hover:shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Pass Rate</p>
                <p class="text-3xl font-bold text-gray-800 mt-1"><?php echo $passRate; ?>%</p>
                <p class="text-xs text-gray-500 mt-1"><?php echo number_format($totalResults); ?> attempts</p>
            </div>
            <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
                <i class="fa-solid fa-chart-line text-indigo-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3 mb-8">
    <a href="index.php?page=exams" class="bg-white rounded-xl shadow-sm p-3 text-center hover:shadow-md transition-all border border-gray-100 hover:border-shikhbo-primary">
        <i class="fa-solid fa-plus-circle text-shikhbo-primary text-xl mb-1 block"></i>
        <span class="text-xs font-medium text-gray-600">New Exam</span>
    </a>
    <a href="index.php?page=questions" class="bg-white rounded-xl shadow-sm p-3 text-center hover:shadow-md transition-all border border-gray-100 hover:border-shikhbo-primary">
        <i class="fa-solid fa-circle-plus text-green-600 text-xl mb-1 block"></i>
        <span class="text-xs font-medium text-gray-600">Add Question</span>
    </a>
    <a href="index.php?page=categories" class="bg-white rounded-xl shadow-sm p-3 text-center hover:shadow-md transition-all border border-gray-100 hover:border-shikhbo-primary">
        <i class="fa-solid fa-layer-group text-purple-600 text-xl mb-1 block"></i>
        <span class="text-xs font-medium text-gray-600">Categories</span>
    </a>
    <a href="index.php?page=students" class="bg-white rounded-xl shadow-sm p-3 text-center hover:shadow-md transition-all border border-gray-100 hover:border-shikhbo-primary">
        <i class="fa-solid fa-user-plus text-orange-600 text-xl mb-1 block"></i>
        <span class="text-xs font-medium text-gray-600">Students</span>
    </a>
    <a href="index.php?page=results" class="bg-white rounded-xl shadow-sm p-3 text-center hover:shadow-md transition-all border border-gray-100 hover:border-shikhbo-primary">
        <i class="fa-solid fa-chart-bar text-blue-600 text-xl mb-1 block"></i>
        <span class="text-xs font-medium text-gray-600">Results</span>
    </a>
    <a href="index.php?page=admins" class="bg-white rounded-xl shadow-sm p-3 text-center hover:shadow-md transition-all border border-gray-100 hover:border-shikhbo-primary">
        <i class="fa-solid fa-user-gear text-yellow-600 text-xl mb-1 block"></i>
        <span class="text-xs font-medium text-gray-600">Admins</span>
    </a>
    <a href="index.php?page=database" class="bg-white rounded-xl shadow-sm p-3 text-center hover:shadow-md transition-all border border-gray-100 hover:border-shikhbo-primary">
        <i class="fa-solid fa-terminal text-gray-600 text-xl mb-1 block"></i>
        <span class="text-xs font-medium text-gray-600">Database</span>
    </a>
    <a href="index.php?page=settings" class="bg-white rounded-xl shadow-sm p-3 text-center hover:shadow-md transition-all border border-gray-100 hover:border-shikhbo-primary">
        <i class="fa-solid fa-gear text-gray-600 text-xl mb-1 block"></i>
        <span class="text-xs font-medium text-gray-600">Settings</span>
    </a>
</div>

<!-- Two Column Layout -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Recent Results -->
    <div class="lg:col-span-2 bg-white rounded-xl shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800">Recent Exam Results</h3>
            <a href="index.php?page=results" class="text-sm text-shikhbo-primary hover:underline">View All</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Exam</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Score</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if ($recentResults && $recentResults->num_rows > 0): ?>
                        <?php while ($r = $recentResults->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800">
                                    <?php echo sanitizeOutput($r['student_name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <?php echo sanitizeOutput($r['exam_title']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="font-medium"><?php echo $r['score']; ?>/<?php echo $r['total_marks']; ?></span>
                                    <span class="text-gray-400 text-xs ml-1">(<?php echo $r['percentage']; ?>%)</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $r['status']==='passed' ? 'text-green-800 bg-green-100' : 'text-red-800 bg-red-100'; ?>">
                                        <?php echo ucfirst($r['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">No results yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Students -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800">New Students</h3>
            <a href="index.php?page=students" class="text-sm text-shikhbo-primary hover:underline">View All</a>
        </div>
        <div class="divide-y divide-gray-200">
            <?php if ($recentStudents && $recentStudents->num_rows > 0): ?>
                <?php while ($stu = $recentStudents->fetch_assoc()): ?>
                    <div class="px-6 py-3 flex items-center space-x-3 hover:bg-gray-50">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($stu['name']); ?>&background=4F46E5&color=fff&size=40" class="w-10 h-10 rounded-full">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate"><?php echo sanitizeOutput($stu['name']); ?></p>
                            <p class="text-xs text-gray-500 truncate"><?php echo sanitizeOutput($stu['email']); ?></p>
                        </div>
                        <span class="text-xs text-gray-400"><?php echo date('M j', strtotime($stu['created_at'])); ?></span>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="px-6 py-8 text-center text-gray-500">No students yet.</div>
            <?php endif; ?>
        </div>
    </div>
</div>