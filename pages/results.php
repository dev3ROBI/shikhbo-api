<?php
$mysqli = getDBConnection();

$search = sanitize($_GET['search'] ?? '');
$statusFilter = sanitize($_GET['status'] ?? '');
$page_num = max(1, intval($_GET['p'] ?? 1));
$limit = 20; $offset = ($page_num-1)*$limit;

$where = "1=1"; $params = []; $types = '';
if ($search) { $where .= " AND (u.name LIKE ? OR e.title LIKE ?)"; $sp = "%{$search}%"; $params = [$sp, $sp]; $types = 'ss'; }
if ($statusFilter) { $where .= " AND r.status = ?"; $params[] = $statusFilter; $types .= 's'; }

$countStmt = $mysqli->prepare("SELECT COUNT(*) as c FROM exam_results r JOIN users u ON r.user_id=u.id JOIN exams e ON r.exam_id=e.id WHERE $where");
if ($params) $countStmt->bind_param($types, ...$params);
$countStmt->execute(); $totalResults = $countStmt->get_result()->fetch_assoc()['c']; $countStmt->close();
$totalPages = ceil($totalResults/$limit);

$query = "SELECT r.*, u.name AS student_name, e.title AS exam_title FROM exam_results r JOIN users u ON r.user_id=u.id JOIN exams e ON r.exam_id=e.id WHERE $where ORDER BY r.completed_at DESC LIMIT ? OFFSET ?";
$types .= 'ii'; $params[] = $limit; $params[] = $offset;
$stmt = $mysqli->prepare($query); $stmt->bind_param($types, ...$params); $stmt->execute();
$results = $stmt->get_result(); $stmt->close();

$passCount = $mysqli->query("SELECT COUNT(*) as c FROM exam_results WHERE status='passed'")->fetch_assoc()['c'];
$failCount = $mysqli->query("SELECT COUNT(*) as c FROM exam_results WHERE status='failed'")->fetch_assoc()['c'];
$avgScore = $mysqli->query("SELECT AVG(percentage) as a FROM exam_results")->fetch_assoc()['a'] ?? 0;
?>

<div class="page-content">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 dark:text-gray-100">Results</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1"><?php echo number_format($totalResults); ?> total attempts</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="px-3 py-1.5 text-xs font-medium rounded-lg bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                <i class="fa-solid fa-check mr-1"></i><?php echo number_format($passCount); ?> Passed
            </span>
            <span class="px-3 py-1.5 text-xs font-medium rounded-lg bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                <i class="fa-solid fa-xmark mr-1"></i><?php echo number_format($failCount); ?> Failed
            </span>
            <span class="px-3 py-1.5 text-xs font-medium rounded-lg bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400">
                <i class="fa-solid fa-chart-simple mr-1"></i>Avg <?php echo round($avgScore,1); ?>%
            </span>
        </div>
    </div>

    <!-- Search -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-4 mb-6 border border-gray-100 dark:border-gray-700">
        <form method="GET" class="flex flex-col sm:flex-row gap-3">
            <input type="hidden" name="page" value="results">
            <div class="relative flex-1">
                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" name="search" value="<?php echo sanitizeOutput($search); ?>" placeholder="Search by student or exam..." class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced">
            </div>
            <select name="status" class="px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                <option value="">All Results</option>
                <option value="passed" <?php echo $statusFilter==='passed'?'selected':''; ?>>Passed</option>
                <option value="failed" <?php echo $statusFilter==='failed'?'selected':''; ?>>Failed</option>
            </select>
            <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700 transition-colors">Filter</button>
            <?php if ($search||$statusFilter): ?>
            <a href="index.php?page=results" class="px-5 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Desktop Table -->
    <div class="hidden sm:block bg-white dark:bg-gray-800 rounded-2xl shadow-md overflow-hidden border border-gray-100 dark:border-gray-700">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="table-header">
                    <tr>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Student</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider hide-mobile">Exam</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Score</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider hide-mobile">Progress</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider hide-mobile">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                    <?php if ($results->num_rows > 0): ?>
                        <?php while ($r = $results->fetch_assoc()): ?>
                        <tr class="table-row">
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($r['student_name']); ?>&background=4F46E5&color=fff&size=40&bold=true" class="w-10 h-10 rounded-xl">
                                    <span class="font-medium text-gray-800 dark:text-gray-100"><?php echo sanitizeOutput($r['student_name']); ?></span>
                                </div>
                            </td>
                            <td class="px-4 py-4 hide-mobile">
                                <span class="text-sm text-gray-600 dark:text-gray-300"><?php echo sanitizeOutput($r['exam_title']); ?></span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-100"><?php echo $r['score']; ?>/<?php echo $r['total_marks']; ?></span>
                                    <span class="text-xs text-gray-400"><?php echo round($r['percentage']); ?>%</span>
                                </div>
                            </td>
                            <td class="px-4 py-4 hide-mobile">
                                <div class="flex items-center gap-2 w-32">
                                    <div class="flex-1 bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                        <div class="h-2 rounded-full <?php echo $r['status']==='passed'?'bg-green-500':'bg-red-500'; ?>" style="width:<?php echo $r['percentage']; ?>%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <?php if ($r['status']==='passed'): ?>
                                    <span class="badge bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                        <i class="fa-solid fa-check mr-1"></i>Passed
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                        <i class="fa-solid fa-xmark mr-1"></i>Failed
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 hide-mobile">
                                <span class="text-sm text-gray-500 dark:text-gray-400"><?php echo date('M j, Y', strtotime($r['completed_at'])); ?></span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center">
                                <i class="fa-solid fa-chart-simple text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                                <p class="text-gray-500 dark:text-gray-400">No results found</p>
                            </div>
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Mobile Cards -->
    <div class="sm:hidden space-y-3">
        <?php if ($results->num_rows > 0): ?>
            <?php $results->data_seek(0); while ($r = $results->fetch_assoc()): ?>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-gray-100 dark:border-gray-700 p-4">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($r['student_name']); ?>&background=4F46E5&color=fff&size=40&bold=true" class="w-10 h-10 rounded-xl">
                        <div>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100"><?php echo sanitizeOutput($r['student_name']); ?></p>
                            <p class="text-xs text-gray-400 dark:text-gray-500"><?php echo sanitizeOutput($r['exam_title']); ?></p>
                        </div>
                    </div>
                    <span class="badge <?php echo $r['status']==='passed'?'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400':'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'; ?>">
                        <?php echo ucfirst($r['status']); ?>
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-300"><?php echo $r['score']; ?>/<?php echo $r['total_marks']; ?> (<?php echo round($r['percentage']); ?>%)</span>
                    <span class="text-xs text-gray-400 dark:text-gray-500"><?php echo date('M j, Y', strtotime($r['completed_at'])); ?></span>
                </div>
                <div class="mt-2 w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                    <div class="h-2 rounded-full <?php echo $r['status']==='passed'?'bg-green-500':'bg-red-500'; ?>" style="width:<?php echo $r['percentage']; ?>%"></div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
        <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-gray-100 dark:border-gray-700">
            <i class="fa-solid fa-chart-simple text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
            <p class="text-gray-500 dark:text-gray-400">No results found</p>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="mt-6 flex justify-center">
        <div class="flex items-center gap-1">
            <?php for ($i=1;$i<=$totalPages;$i++): ?>
            <a href="index.php?page=results&p=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $statusFilter; ?>" 
               class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-medium transition-colors <?php echo $i===$page_num?'bg-indigo-600 text-white':'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</div>