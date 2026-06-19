<?php
/**
 * Verify payment status (both manual checks and customer redirect landings)
 */
require_once __DIR__ . '/../includes/auth.php';

$transactionId = $_GET['transaction_id'] ?? '';
$conn = getDBConnection();

$txnStmt = $conn->prepare("SELECT id, user_id, course_id, enrollment_id, piprapay_pp_id, amount, status FROM transactions WHERE transaction_id = ?");
$txnStmt->bind_param('s', $transactionId);
$txnStmt->execute();
$txn = $txnStmt->get_result()->fetch_assoc();
$txnStmt->close();

$message = 'Checking payment status...';
$isSuccess = false;
$courseTitle = 'Course';

if ($txn) {
    // Fetch course title
    $courseStmt = $conn->prepare("SELECT title FROM courses WHERE id = ?");
    $courseStmt->bind_param('i', $txn['course_id']);
    $courseStmt->execute();
    $course = $courseStmt->get_result()->fetch_assoc();
    $courseStmt->close();
    if ($course) {
        $courseTitle = $course['title'];
    }

    $ppId = $txn['piprapay_pp_id'];
    $status = $txn['status'];

    if ($status === 'pending' || $status === 'initiated') {
        if (!empty($ppId)) {
            // Call verify API to check latest status
            $ch = curl_init(PIPRAPAY_BASE_URL . '/verify-payment');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['pp_id' => $ppId]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'mh-piprapay-api-key: ' . PIPRAPAY_API_KEY,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $resData = json_decode($response, true);
                $statusData = $resData['data'] ?? null;
                if ($statusData && isset($statusData['status'])) {
                    $paymentStatus = strtolower($statusData['status']);
                    $enrollmentId = $txn['enrollment_id'];
                    $courseId = $txn['course_id'];

                    if ($paymentStatus === 'completed') {
                        $conn->begin_transaction();
                        try {
                            // Update transaction
                            $updateTxn = $conn->prepare("UPDATE transactions SET status = 'completed', gateway_response = ?, completed_at = NOW() WHERE id = ?");
                            $gatewayJson = json_encode($resData);
                            $updateTxn->bind_param('si', $gatewayJson, $txn['id']);
                            $updateTxn->execute();
                            $updateTxn->close();

                            // Activate enrollment
                            $updateEnroll = $conn->prepare("UPDATE enrollments SET status = 'active', enrolled_at = NOW() WHERE id = ?");
                            $updateEnroll->bind_param('i', $enrollmentId);
                            $updateEnroll->execute();
                            $updateEnroll->close();

                            // Update Course enrollment counter
                            $conn->query("UPDATE courses SET total_enrolled = (SELECT COUNT(*) FROM enrollments WHERE course_id = $courseId AND status = 'active') WHERE id = $courseId");

                            $conn->commit();
                            $status = 'completed';
                        } catch (Exception $e) {
                            $conn->rollback();
                        }
                    } elseif ($paymentStatus === 'failed') {
                        $updateTxn = $conn->prepare("UPDATE transactions SET status = 'failed', gateway_response = ? WHERE id = ?");
                        $gatewayJson = json_encode($resData);
                        $updateTxn->bind_param('si', $gatewayJson, $txn['id']);
                        $updateTxn->execute();
                        $updateTxn->close();
                        $status = 'failed';
                    }
                }
            }
        }
    }

    if ($status === 'completed') {
        $isSuccess = true;
        $message = "Payment of ৳" . number_format($txn['amount'], 2) . " completed successfully! You are now enrolled.";
    } elseif ($status === 'failed') {
        $message = "Payment failed or was cancelled. Please try initiating enrollment again.";
    } else {
        $message = "Your payment is currently pending or processing.";
    }
} else {
    $message = "Transaction ID not found.";
}

// Check if request is JSON (e.g. from app check button)
if (isset($_GET['format']) && $_GET['format'] === 'json') {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $isSuccess ? 'success' : 'pending_or_failed',
        'message' => $message,
        'payment_status' => $txn ? $txn['status'] : 'not_found'
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Status — Shikhbo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" type="image/png" href="/image/app_logo.png">
</head>
<body class="bg-gray-50 dark:bg-gray-900 flex items-center justify-center min-h-screen p-4 font-sans">
    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl w-full max-w-md p-8 border border-gray-100 dark:border-gray-700 text-center">
        <div class="mb-6 flex justify-center">
            <?php if ($isSuccess): ?>
                <div class="w-20 h-20 bg-green-100 dark:bg-green-900/30 text-green-500 rounded-full flex items-center justify-center text-4xl shadow-md">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            <?php else: ?>
                <div class="w-20 h-20 bg-red-100 dark:bg-red-900/30 text-red-500 rounded-full flex items-center justify-center text-4xl shadow-md">
                    <i class="fa-solid fa-circle-xmark"></i>
                </div>
            <?php endif; ?>
        </div>

        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-2">
            <?php echo $isSuccess ? 'Payment Successful' : 'Payment Status'; ?>
        </h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 font-medium mb-4">
            <?php echo htmlspecialchars($courseTitle); ?>
        </p>

        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-2xl p-4 mb-6 text-sm text-gray-700 dark:text-gray-300 leading-relaxed border border-gray-100 dark:border-gray-700">
            <?php echo htmlspecialchars($message); ?>
        </div>

        <div class="flex flex-col gap-3">
            <?php if ($isSuccess): ?>
                <a href="/index.php" class="px-6 py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold transition shadow-lg text-sm">
                    Start Learning
                </a>
            <?php else: ?>
                <a href="/index.php#courses" class="px-6 py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold transition shadow-lg text-sm">
                    Retry Payment
                </a>
            <?php endif; ?>
            <a href="/index.php" class="px-6 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 text-gray-600 dark:text-gray-300 rounded-xl font-semibold transition text-sm">
                Back to Home
            </a>
        </div>
    </div>
</body>
</html>
