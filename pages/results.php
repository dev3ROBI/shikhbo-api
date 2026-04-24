<?php
$mysqli = getDBConnection();

// Filters
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

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div><h1 class="text-2xl font-bold text-gray-800">Exam Results</h1><p class="text-gray-500 text-sm mt-1"><?php echo $totalResults; ?> total attempts</p></div>
    <div class="flex flex-wrap items-center gap-2">
        <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-700 font-medium"><?php echo $passCount; ?> Passed</span>
        <span class="text-xs px-2 py-1 rounded-full bg-red-100 text-red-700 font-medium"><?php echo $failCount; ?> Failed</span>
        <span class="text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-700 font-medium">Avg <?php echo round($avgScore,1); ?>%</span>
    </div>
</div>

<!-- Search -->
<form method="GET" class="mb-4 flex flex-col sm:flex-row gap-2">
    <input type="hidden" name="page" value="results">
    <input type="text" name="search" value="<?php echo sanitizeOutput($search); ?>" placeholder="Student or exam..." class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-shikhbo-primary outline-none">
    <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg text-sm"><option value="">All</option><option value="passed" <?php echo $statusFilter==='passed'?'selected':''; ?>>Passed</option><option value="failed" <?php echo $statusFilter==='failed'?'selected':''; ?>>Failed</option></select>
    <button type="submit" class="px-4 py-2 bg-shikhbo-primary text-white rounded-lg text-sm">Filter</button>
    <?php if ($search||$statusFilter): ?><a href="index.php?page=results" class="px-4 py-2 border border-gray-300 rounded-lg text-sm inline-flex items-center">Clear</a><?php endif; ?>
</form>

<!-- Desktop Table (hidden on small screens) -->
<div class="hidden sm:block bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-4">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Exam</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Score</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">%</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th></tr></thead>
            <tbody class="divide-y divide-gray-200">
                <?php if ($results->num_rows > 0): ?>
                    <?php while ($r = $results->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-medium text-gray-800"><?php echo sanitizeOutput($r['student_name']); ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600"><?php echo sanitizeOutput($r['exam_title']); ?></td>
                            <td class="px-4 py-3 text-sm"><?php echo $r['score']; ?>/<?php echo $r['total_marks']; ?></td>
                            <td class="px-4 py-3 text-sm"><div class="flex items-center gap-2"><div class="w-16 bg-gray-200 rounded-full h-1.5"><div class="h-1.5 rounded-full <?php echo $r['status']==='passed'?'bg-green-500':'bg-red-500'; ?>" style="width:<?php echo $r['percentage']; ?>%"></div></div><span class="font-medium"><?php echo $r['percentage']; ?>%</span></div></td>
                            <td class="px-4 py-3"><span class="px-2 py-0.5 text-xs font-semibold rounded-full <?php echo $r['status']==='passed'?'text-green-800 bg-green-100':'text-red-800 bg-red-100'; ?>"><?php echo ucfirst($r['status']); ?></span></td>
                            <td class="px-4 py-3 text-xs text-gray-500"><?php echo date('M j, Y', strtotime($r['completed_at'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="px-6 py-12 text-center text-gray-500"><i class="fa-solid fa-chart-simple text-3xl mb-2 block"></i>No results yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Mobile Cards (visible on small screens) -->
<div class="sm:hidden space-y-3">
    <?php if ($results->num_rows > 0): ?>
        <?php $results->data_seek(0); while ($r = $results->fetch_assoc()): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <div class="flex items-start justify-between mb-2">
                    <div class="flex items-center space-x-3 min-w-0">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($r['student_name']); ?>&background=4F46E5&color=fff&size=40" class="w-10 h-10 rounded-full flex-shrink-0">
                        <div class="min-w-0"><p class="text-sm font-medium text-gray-800 truncate"><?php echo sanitizeOutput($r['student_name']); ?></p><p class="text-xs text-gray-400 truncate"><?php echo sanitizeOutput($r['exam_title']); ?></p></div>
                    </div>
                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full flex-shrink-0 <?php echo $r['status']==='passed'?'text-green-800 bg-green-100':'text-red-800 bg-red-100'; ?>"><?php echo ucfirst($r['status']); ?></span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600"><?php echo $r['score']; ?>/<?php echo $r['total_marks']; ?> · <?php echo $r['percentage']; ?>%</span>
                    <span class="text-xs text-gray-400"><?php echo date('M j, Y', strtotime($r['completed_at'])); ?></span>
                </div>
                <div class="mt-2 w-full bg-gray-200 rounded-full h-1.5"><div class="h-1.5 rounded-full <?php echo $r['status']==='passed'?'bg-green-500':'bg-red-500'; ?>" style="width:<?php echo $r['percentage']; ?>%"></div></div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="text-center py-12 text-gray-500"><i class="fa-solid fa-chart-simple text-3xl mb-2 block"></i>No results yet.</div>
    <?php endif; ?>
</div>

<?php if ($totalPages > 1): ?>
    <div class="mt-6 flex justify-center flex-wrap gap-1">
        <?php for ($i=1;$i<=$totalPages;$i++): ?>
            <a href="index.php?page=results&p=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $statusFilter; ?>" class="px-3 py-1 text-sm border rounded <?php echo $i===$page_num?'bg-shikhbo-primary text-white border-shikhbo-primary':'hover:bg-gray-100'; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
    </div>
<?php endif; ?>