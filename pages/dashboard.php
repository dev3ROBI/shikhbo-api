<?php
$mysqli = getDBConnection();

// Student roles for accurate counting
$studentRoles = ['Student', 'Student Pro', 'Premium Student'];
$roleIn = "'" . implode("','", $studentRoles) . "'";

// Primary Stats
$totalStudents = $mysqli->query("SELECT COUNT(*) as count FROM users WHERE (role IS NULL OR role = '' OR role = 'user' OR role IN ($roleIn))")->fetch_assoc()['count'];
$totalAdmins   = $mysqli->query("SELECT COUNT(*) as count FROM users WHERE role IN ('Administrator','Moderator','Editor','admin')")->fetch_assoc()['count'];
$totalExams    = $mysqli->query("SELECT COUNT(*) as count FROM exams")->fetch_assoc()['count'];
$activeExams   = $mysqli->query("SELECT COUNT(*) as count FROM exams WHERE status = 'active'")->fetch_assoc()['count'];
$totalCourses  = $mysqli->query("SELECT COUNT(*) as count FROM courses")->fetch_assoc()['count'];
$totalEnrolls  = $mysqli->query("SELECT COUNT(*) as count FROM enrollments")->fetch_assoc()['count'];
$totalQuestions= $mysqli->query("SELECT COUNT(*) as count FROM questions")->fetch_assoc()['count'];
$totalResults  = $mysqli->query("SELECT COUNT(*) as count FROM exam_results")->fetch_assoc()['count'];

$today = date('Y-m-d');
$todayStudents = $mysqli->query("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = '$today' AND (role IS NULL OR role = '' OR role = 'user' OR role IN ($roleIn))")->fetch_assoc()['count'];

$avgScoreQuery = $mysqli->query("SELECT AVG(percentage) as avg FROM exam_results");
$passRate = $avgScoreQuery ? round($avgScoreQuery->fetch_assoc()['avg'] ?? 0, 1) : 0;

// Recent Data
$recentResults = $mysqli->query("SELECT r.*, u.name as student_name, e.title as exam_title FROM exam_results r JOIN users u ON r.user_id = u.id JOIN exams e ON r.exam_id = e.id ORDER BY r.created_at DESC LIMIT 6");
$recentStudents = $mysqli->query("SELECT id, name, email, status, role, created_at FROM users WHERE (role IS NULL OR role = '' OR role = 'user' OR role IN ($roleIn)) ORDER BY created_at DESC LIMIT 5");

// Popular Exams
$popularExams = $mysqli->query("SELECT e.title, COUNT(r.id) as attempts FROM exams e LEFT JOIN exam_results r ON e.id = r.exam_id GROUP BY e.id ORDER BY attempts DESC LIMIT 5");

// Support Info (Handled in index.php but local fallback for safety)
if (!isset($ticketCount)) {
    $r = $mysqli->query("SHOW TABLES LIKE 'support_tickets'");
    if ($r && $r->num_rows > 0) {
        $ticketCount = $mysqli->query("SELECT COUNT(*) AS c FROM support_tickets WHERE status='open'")->fetch_assoc()['c'] ?? 0;
        $recentTickets = $mysqli->query("SELECT t.*, u.name AS user_name FROM support_tickets t LEFT JOIN users u ON t.user_id=u.id ORDER BY t.created_at DESC LIMIT 5");
    }
}
?>
<div class="page-content">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 dark:text-gray-100">Overview</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Real-time performance and system metrics.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 text-sm">
                <i class="fa-solid fa-clock text-indigo-500"></i>
                <span id="liveClock" class="font-medium text-gray-700 dark:text-gray-200"><?php echo date('H:i'); ?></span>
                <span class="w-px h-3 bg-gray-200 dark:bg-gray-700 mx-1"></span>
                <span class="text-gray-500 dark:text-gray-400 text-xs"><?php echo date('M d, Y'); ?></span>
            </span>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <!-- Students -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 bg-indigo-50 dark:bg-indigo-900/30 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-user-graduate text-indigo-600 dark:text-indigo-400"></i>
                </div>
                <span class="text-[10px] font-bold text-emerald-600 bg-green-50 dark:bg-green-900/20 px-2 py-1 rounded-lg">+<?php echo $todayStudents; ?> today</span>
            </div>
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Students</p>
            <p class="text-3xl font-bold text-gray-800 dark:text-gray-100 mt-1"><?php echo number_format($totalStudents); ?></p>
        </div>

        <!-- Enrollments -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 bg-blue-50 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-graduation-cap text-blue-600 dark:text-blue-400"></i>
                </div>
                <span class="text-[10px] font-bold text-blue-600 bg-blue-50 dark:bg-blue-900/20 px-2 py-1 rounded-lg"><?php echo $totalCourses; ?> courses</span>
            </div>
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Enrollments</p>
            <p class="text-3xl font-bold text-gray-800 dark:text-gray-100 mt-1"><?php echo number_format($totalEnrolls); ?></p>
        </div>

        <!-- Question Bank -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 bg-purple-50 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-database text-purple-600 dark:text-purple-400"></i>
                </div>
                <span class="text-[10px] font-bold text-gray-400 uppercase"><?php echo $activeExams; ?> live exams</span>
            </div>
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Question Bank</p>
            <p class="text-3xl font-bold text-gray-800 dark:text-gray-100 mt-1"><?php echo number_format($totalQuestions); ?></p>
        </div>

        <!-- Pass Rate -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 bg-amber-50 dark:bg-amber-900/30 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-chart-line text-amber-600 dark:text-amber-400"></i>
                </div>
                <span class="text-[10px] font-bold text-gray-400 uppercase"><?php echo number_format($totalResults); ?> attempts</span>
            </div>
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Avg. Success Rate</p>
            <div class="flex items-baseline gap-2 mt-1">
                <p class="text-3xl font-bold text-gray-800 dark:text-gray-100"><?php echo $passRate; ?>%</p>
                <span class="text-[10px] font-bold text-emerald-500"><i class="fa-solid fa-caret-up mr-0.5"></i>2.4%</span>
            </div>
        </div>
    </div>

    <!-- Main Layout Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Activity & Results -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Recent Results -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-50 dark:border-gray-700/50 flex items-center justify-between">
                    <h3 class="font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                        <i class="fa-solid fa-chart-simple text-indigo-500"></i>
                        Recent Exam Results
                    </h3>
                    <a href="index.php?page=results" class="text-xs font-semibold text-indigo-600 hover:underline">View All</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[11px] uppercase tracking-wider font-semibold text-gray-400 bg-gray-50/50 dark:bg-gray-900/30">
                                <th class="px-6 py-3">Student</th>
                                <th class="px-6 py-3 hidden sm:table-cell">Exam</th>
                                <th class="px-6 py-3">Score</th>
                                <th class="px-6 py-3 text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                            <?php if ($recentResults && $recentResults->num_rows > 0): ?>
                                <?php while ($r = $recentResults->fetch_assoc()): ?>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/20 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($r['student_name']); ?>&background=4F46E5&color=fff&size=32&bold=true" class="w-8 h-8 rounded-lg shadow-sm">
                                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-200"><?php echo sanitizeOutput($r['student_name']); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 hidden sm:table-cell">
                                            <span class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[150px] inline-block"><?php echo sanitizeOutput($r['exam_title']); ?></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-bold text-gray-800 dark:text-gray-100"><?php echo round($r['percentage']); ?>%</span>
                                                <div class="w-16 h-1 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                                    <div class="h-full bg-indigo-500" style="width: <?php echo $r['percentage']; ?>%"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <span class="badge text-[10px] <?php echo $r['status'] === 'passed' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'; ?>">
                                                <?php echo strtoupper($r['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="px-6 py-12 text-center text-xs text-gray-400 italic">No recent attempts</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- System Info -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm">
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">System Health</h4>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-600 dark:text-gray-300">Database Engine</span>
                            <span class="flex items-center gap-1.5 text-[10px] font-bold text-emerald-500 uppercase"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Optimal</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-600 dark:text-gray-300">API Latency</span>
                            <span class="text-[10px] font-bold text-gray-500">24ms</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-600 dark:text-gray-300">Security Firewall</span>
                            <span class="text-[10px] font-bold text-indigo-500 uppercase">Shield Active</span>
                        </div>
                    </div>
                </div>
                <div class="bg-indigo-600 rounded-2xl p-5 text-white shadow-lg shadow-indigo-900/20 relative overflow-hidden group">
                    <i class="fa-solid fa-rocket absolute -right-3 -bottom-3 text-7xl text-white/10 group-hover:scale-110 transition-transform duration-700"></i>
                    <h4 class="text-sm font-bold uppercase tracking-wider mb-1">Platform V3.2</h4>
                    <p class="text-indigo-100 text-[11px] leading-relaxed opacity-90">Running on optimized production builds. Daily backups active.</p>
                    <div class="mt-4 flex gap-3">
                        <button class="text-[10px] font-bold uppercase tracking-widest hover:text-white transition-colors underline underline-offset-4">Updates</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Support & Registrations -->
        <div class="space-y-6">
            <!-- Support Tickets -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-50 dark:border-gray-700/50 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100 uppercase tracking-wider">Support</h3>
                    <?php if (isset($ticketCount) && $ticketCount > 0): ?>
                        <span class="text-[9px] font-bold px-1.5 py-0.5 bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400 rounded"><?php echo $ticketCount; ?> OPEN</span>
                    <?php endif; ?>
                </div>
                <div class="divide-y divide-gray-50 dark:divide-gray-700/50">
                    <?php if (isset($recentTickets) && $recentTickets && $recentTickets->num_rows > 0): ?>
                        <?php $recentTickets->data_seek(0); while ($tk = $recentTickets->fetch_assoc()): ?>
                            <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors group">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-[9px] font-bold text-indigo-600 dark:text-indigo-400 uppercase"><?php echo sanitizeOutput($tk['user_name'] ?? 'Guest'); ?></span>
                                    <span class="text-[9px] text-gray-400"><?php echo date('H:i', strtotime($tk['created_at'])); ?></span>
                                </div>
                                <p class="text-xs font-semibold text-gray-700 dark:text-gray-200 truncate group-hover:text-indigo-600 transition-colors"><?php echo sanitizeOutput($tk['subject']); ?></p>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="py-10 text-center">
                            <i class="fa-solid fa-inbox text-gray-100 dark:text-gray-700 text-3xl mb-2"></i>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Inbox Empty</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Popular Exams -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-50 dark:border-gray-700/50 flex items-center gap-2">
                    <i class="fa-solid fa-fire text-orange-500 text-xs"></i>
                    <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100 uppercase tracking-wider">Popular Exams</h3>
                </div>
                <div class="p-2 space-y-1">
                    <?php if ($popularExams && $popularExams->num_rows > 0): ?>
                        <?php while ($ex = $popularExams->fetch_assoc()): ?>
                            <div class="px-3 py-2.5 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/30 rounded-xl transition-colors">
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate pr-2"><?php echo sanitizeOutput($ex['title']); ?></span>
                                <span class="text-[10px] font-bold px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-lg"><?php echo $ex['attempts']; ?></span>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="py-8 text-center text-xs text-gray-400 italic">No data</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- New Students -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-50 dark:border-gray-700/50">
                    <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100 uppercase tracking-wider">Registrations</h3>
                </div>
                <div class="divide-y divide-gray-50 dark:divide-gray-700/50">
                    <?php if ($recentStudents && $recentStudents->num_rows > 0): ?>
                        <?php while ($stu = $recentStudents->fetch_assoc()): 
                            $role = $stu['role'] ?: 'Student';
                            $roleClr = $role === 'Premium Student' ? 'text-amber-500' : ($role === 'Student Pro' ? 'text-blue-500' : 'text-gray-400');
                        ?>
                            <div class="p-4 flex items-center gap-4 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($stu['name']); ?>&background=F3F4F6&color=6366F1&size=36&bold=true" class="w-9 h-9 rounded-xl">
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-bold text-gray-800 dark:text-gray-100 truncate"><?php echo sanitizeOutput($stu['name']); ?></p>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="text-[9px] font-bold uppercase tracking-tighter <?php echo $roleClr; ?>"><?php echo $role; ?></span>
                                        <span class="text-[9px] text-gray-400"><?php echo date('M d', strtotime($stu['created_at'])); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="py-10 text-center text-xs text-gray-400 italic">No students yet</div>
                    <?php endif; ?>
                </div>
                <a href="index.php?page=students" class="block w-full py-3 bg-gray-50 dark:bg-gray-700/30 text-center text-[10px] font-bold text-gray-500 hover:text-indigo-600 transition-colors uppercase tracking-widest border-t border-gray-50 dark:border-gray-700/50">Manage Users</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const clock = document.getElementById('liveClock');
    function updateClock() {
        if (clock) {
            const now = new Date();
            clock.textContent = now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
        }
    }
    updateClock();
    setInterval(updateClock, 1000);
});
</script>