<?php
/**
 * Admin Panel - Transactions Management Page
 */
$mysqli = getDBConnection();

// CSRF check if any POST action is taken
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token validation failed.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'verify_payment') {
            $txnId = sanitize($_POST['transaction_id']);
            // We call verify function by mimicking a request or calling it locally
            // To do it cleanly, let's trigger curl verify locally
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $verifyUrl = $protocol . '://' . $host . '/api/piprapay_verify.php?transaction_id=' . urlencode($txnId) . '&format=json';

            $ch = curl_init($verifyUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $resp = curl_exec($ch);
            curl_close($ch);

            if ($resp) {
                $respData = json_decode($resp, true);
                if (isset($respData['status']) && $respData['status'] === 'success') {
                    $success = "Transaction synced: Payment Completed! User enrollment activated.";
                } else {
                    $error = "Verification complete: " . ($respData['message'] ?? 'Transaction is not completed yet.');
                }
            } else {
                $error = "Failed to communicate with verification API.";
            }
        }
    }
}

// Stats queries
$totalRevenue = $mysqli->query("SELECT SUM(amount) AS total FROM transactions WHERE status = 'completed'")->fetch_assoc()['total'] ?? 0;
$completedCount = $mysqli->query("SELECT COUNT(*) AS total FROM transactions WHERE status = 'completed'")->fetch_assoc()['total'] ?? 0;
$pendingCount = $mysqli->query("SELECT COUNT(*) AS total FROM transactions WHERE status IN ('pending', 'initiated')")->fetch_assoc()['total'] ?? 0;
$failedCount = $mysqli->query("SELECT COUNT(*) AS total FROM transactions WHERE status = 'failed'")->fetch_assoc()['total'] ?? 0;

// Filters
$search = sanitize($_GET['search'] ?? '');
$statusFilter = sanitize($_GET['status'] ?? '');

$where = "1=1";
if ($search) {
    $where .= " AND (t.transaction_id LIKE '%$search%' OR u.email LIKE '%$search%' OR u.name LIKE '%$search%')";
}
if ($statusFilter) {
    $where .= " AND t.status = '$statusFilter'";
}

// Pagination
$limit = 15;
$page_num = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$offset = ($page_num - 1) * $limit;

$totalTxns = $mysqli->query("SELECT COUNT(*) as count FROM transactions t LEFT JOIN users u ON t.user_id = u.id WHERE $where")->fetch_assoc()['count'];
$totalPages = ceil($totalTxns / $limit);

$sql = "SELECT t.*, u.name AS user_name, u.email AS user_email, c.title AS course_title
        FROM transactions t
        LEFT JOIN users u ON t.user_id = u.id
        LEFT JOIN courses c ON t.course_id = c.id
        WHERE $where
        ORDER BY t.initiated_at DESC
        LIMIT ? OFFSET ?";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$transactions = $stmt->get_result();
$stmt->close();
?>

<div class="page-content p-4 sm:p-6 lg:p-8">
    <?php if ($error): ?>
    <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl flex items-center gap-3">
        <i class="fa-solid fa-circle-exclamation text-red-500"></i>
        <span class="text-red-700 dark:text-red-300 text-sm"><?php echo sanitizeOutput($error); ?></span>
    </div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="mb-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl flex items-center gap-3">
        <i class="fa-solid fa-circle-check text-green-500"></i>
        <span class="text-green-700 dark:text-green-300 text-sm"><?php echo sanitizeOutput($success); ?></span>
    </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 flex items-center gap-4 shadow-sm">
            <div class="w-12 h-12 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 rounded-xl flex items-center justify-center text-xl">
                <i class="fa-solid fa-money-bill-wave"></i>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium">Total Revenue</p>
                <h3 class="text-xl font-bold text-gray-800 dark:text-gray-100 mt-0.5">৳<?php echo number_format($totalRevenue, 2); ?></h3>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 flex items-center gap-4 shadow-sm">
            <div class="w-12 h-12 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 rounded-xl flex items-center justify-center text-xl">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium">Completed</p>
                <h3 class="text-xl font-bold text-gray-800 dark:text-gray-100 mt-0.5"><?php echo number_format($completedCount); ?></h3>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 flex items-center gap-4 shadow-sm">
            <div class="w-12 h-12 bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 rounded-xl flex items-center justify-center text-xl">
                <i class="fa-solid fa-hourglass-half"></i>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium">Pending/Initiated</p>
                <h3 class="text-xl font-bold text-gray-800 dark:text-gray-100 mt-0.5"><?php echo number_format($pendingCount); ?></h3>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 flex items-center gap-4 shadow-sm">
            <div class="w-12 h-12 bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 rounded-xl flex items-center justify-center text-xl">
                <i class="fa-solid fa-circle-xmark"></i>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium">Failed</p>
                <h3 class="text-xl font-bold text-gray-800 dark:text-gray-100 mt-0.5"><?php echo number_format($failedCount); ?></h3>
            </div>
        </div>
    </div>

    <!-- Header & Search -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden mb-6">
        <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-gray-800 dark:text-gray-100">Transaction History</h1>
                <p class="text-xs text-gray-400 mt-0.5">Track and verify user payments processed via PipraPay</p>
            </div>

            <form method="GET" action="index.php" class="flex flex-wrap items-center gap-3">
                <input type="hidden" name="page" value="transactions">
                <div class="relative min-w-[200px]">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input type="text" name="search" value="<?php echo sanitizeOutput($search); ?>" placeholder="Search ID or Email..." class="w-full pl-9 pr-4 py-2 bg-gray-50 border border-gray-200 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div>
                    <select name="status" onchange="this.form.submit()" class="px-3 py-2 bg-gray-50 border border-gray-200 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">All Statuses</option>
                        <option value="initiated" <?php echo $statusFilter==='initiated'?'selected':''; ?>>Initiated</option>
                        <option value="pending" <?php echo $statusFilter==='pending'?'selected':''; ?>>Pending</option>
                        <option value="completed" <?php echo $statusFilter==='completed'?'selected':''; ?>>Completed</option>
                        <option value="failed" <?php echo $statusFilter==='failed'?'selected':''; ?>>Failed</option>
                        <option value="refunded" <?php echo $statusFilter==='refunded'?'selected':''; ?>>Refunded</option>
                    </select>
                </div>
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold transition">Search</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700/50 text-gray-400 text-xs font-bold uppercase tracking-wider border-b border-gray-100 dark:border-gray-700">
                        <th class="px-6 py-4">Transaction ID</th>
                        <th class="px-6 py-4">User</th>
                        <th class="px-6 py-4">Course</th>
                        <th class="px-6 py-4">Amount</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Initiated At</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-700 text-sm text-gray-600 dark:text-gray-300">
                    <?php if ($transactions->num_rows === 0): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500">
                            <i class="fa-solid fa-receipt text-3xl mb-3"></i>
                            <p>No transactions found matching the filter criteria.</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php while ($row = $transactions->fetch_assoc()): 
                        $statusClass = 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
                        if ($row['status'] === 'completed') $statusClass = 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400';
                        elseif ($row['status'] === 'pending') $statusClass = 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400';
                        elseif ($row['status'] === 'failed') $statusClass = 'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400';
                        elseif ($row['status'] === 'initiated') $statusClass = 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-400';
                    ?>
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/20 transition-colors">
                        <td class="px-6 py-4 font-mono text-xs font-semibold text-gray-800 dark:text-gray-200"><?php echo sanitizeOutput($row['transaction_id']); ?></td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900 dark:text-gray-100"><?php echo sanitizeOutput($row['user_name']); ?></div>
                            <div class="text-xs text-gray-400 mt-0.5"><?php echo sanitizeOutput($row['user_email']); ?></div>
                        </td>
                        <td class="px-6 py-4 font-medium text-gray-800 dark:text-gray-200"><?php echo sanitizeOutput($row['course_title'] ?: 'Deleted Course'); ?></td>
                        <td class="px-6 py-4 font-bold text-gray-900 dark:text-gray-100">৳<?php echo number_format($row['amount'], 2); ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold <?php echo $statusClass; ?>">
                                <?php echo ucfirst($row['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-xs text-gray-400"><?php echo date('M d, Y H:i', strtotime($row['initiated_at'])); ?></td>
                        <td class="px-6 py-4 text-right flex items-center justify-end gap-2">
                            <?php if (in_array($row['status'], ['pending', 'initiated'])): ?>
                            <form method="POST" action="" class="inline">
                                <?php echo getCSRFTokenField(); ?>
                                <input type="hidden" name="action" value="verify_payment">
                                <input type="hidden" name="transaction_id" value="<?php echo sanitizeOutput($row['transaction_id']); ?>">
                                <button type="submit" class="px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:hover:bg-indigo-900/50 dark:text-indigo-400 rounded-lg text-xs font-bold transition flex items-center gap-1.5" title="Verify status with PipraPay">
                                    <i class="fa-solid fa-rotate"></i> Verify
                                </button>
                            </form>
                            <?php endif; ?>
                            <?php if ($row['gateway_response']): ?>
                            <button onclick="viewGatewayResponse(<?php echo htmlspecialchars(json_encode($row['gateway_response'])); ?>)" class="px-2.5 py-1.5 bg-gray-50 hover:bg-gray-100 text-gray-600 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-300 rounded-lg text-xs font-bold transition">
                                Response
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/20 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <span class="text-xs text-gray-400">Page <?php echo $page_num; ?> of <?php echo $totalPages; ?> &middot; <?php echo $totalTxns; ?> rows</span>
            <div class="flex gap-2">
                <?php if ($page_num > 1): ?>
                <a href="index.php?page=transactions&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&p=<?php echo $page_num - 1; ?>" class="px-3 py-1 bg-white border dark:bg-gray-800 dark:border-gray-750 text-xs rounded-lg hover:bg-gray-50 transition">Prev</a>
                <?php endif; ?>
                <?php if ($page_num < $totalPages): ?>
                <a href="index.php?page=transactions&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&p=<?php echo $page_num + 1; ?>" class="px-3 py-1 bg-white border dark:bg-gray-800 dark:border-gray-750 text-xs rounded-lg hover:bg-gray-50 transition">Next</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal for Gateway Response -->
<div id="responseModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeResponseModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4 pointer-events-none">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-xl overflow-hidden pointer-events-auto flex flex-col max-h-[85vh]">
            <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between flex-shrink-0">
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Gateway Payload Details</h3>
                <button onclick="closeResponseModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-2"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <div class="p-6 overflow-y-auto flex-1 font-mono text-xs bg-gray-900 text-indigo-300 custom-scrollbar rounded-b-2xl">
                <pre id="jsonPayload" class="whitespace-pre-wrap"></pre>
            </div>
        </div>
    </div>
</div>

<script>
function viewGatewayResponse(payload) {
    try {
        const parsed = typeof payload === 'string' ? JSON.parse(payload) : payload;
        document.getElementById('jsonPayload').textContent = JSON.stringify(parsed, null, 2);
    } catch(e) {
        document.getElementById('jsonPayload').textContent = payload;
    }
    const modal = document.getElementById('responseModal');
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closeResponseModal() {
    const modal = document.getElementById('responseModal');
    modal.classList.add('hidden');
    document.body.style.overflow = '';
}
</script>
