<?php
require_once __DIR__ . '/../includes/auth.php';

$ref = $_GET['transaction_id'] ?? '';
$conn = getDBConnection();
$isOrder = strpos($ref, 'ORD-') === 0;

if ($isOrder) {
    $orderStmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ?");
    $orderStmt->bind_param('s', $ref);
    $orderStmt->execute();
    $order = $orderStmt->get_result()->fetch_assoc();
    $orderStmt->close();

    $message = 'Checking payment status...';
    $isSuccess = false;

    if ($order) {
        $ppId = $order['piprapay_pp_id'];
        $status = $order['status'];

        if (($status === 'pending') && !empty($ppId)) {
            $ch = curl_init(PIPRAPAY_BASE_URL . '/verify-payment');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['pp_id' => $ppId]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'MHS-PIPRAPAY-API-KEY: ' . PIPRAPAY_API_KEY,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $resData = json_decode($response, true);
                $paymentStatus = strtolower($resData['status'] ?? '');

                if ($paymentStatus === 'completed') {
                    $conn->begin_transaction();
                    try {
                        $updateOrderStmt = $conn->prepare("UPDATE orders SET status='completed', gateway_response=?, paid_at=NOW() WHERE order_id=?");
                        if ($updateOrderStmt) {
                            $gateway_response = json_encode($resData);
                            $updateOrderStmt->bind_param('ss', $gateway_response, $ref);
                            $updateOrderStmt->execute();
                            $updateOrderStmt->close();
                        }

                        $items = $conn->query("SELECT oi.*, cr.price, cr.id AS cid FROM order_items oi JOIN courses cr ON oi.course_id=cr.id WHERE oi.order_ref='$ref'");
                        while ($item = $items->fetch_assoc()) {
                            $eid = $item['enrollment_id'];
                            if ($eid) {
                                $updateEnrollStmt = $conn->prepare("UPDATE enrollments SET status='active', enrolled_at=NOW() WHERE id=?");
                                if ($updateEnrollStmt) {
                                    $updateEnrollStmt->bind_param('i', $eid);
                                    $updateEnrollStmt->execute();
                                    $updateEnrollStmt->close();
                                }
                            }
                            $conn->query("UPDATE courses SET total_enrolled=(SELECT COUNT(*) FROM enrollments WHERE course_id={$item['cid']} AND status='active') WHERE id={$item['cid']}");
                        }
                        $conn->commit();
                        $status = 'completed';
                    } catch (Exception $e) { $conn->rollback(); }
                } elseif ($paymentStatus === 'failed') {
                    $cancelStmt = $conn->prepare("UPDATE orders SET status='cancelled', gateway_response=? WHERE order_id=?");
                    if ($cancelStmt) {
                        $gateway_response = json_encode($resData);
                        $cancelStmt->bind_param('ss', $gateway_response, $ref);
                        $cancelStmt->execute();
                        $cancelStmt->close();
                    }
                    $status = 'cancelled';
                }
            }
        }

        if ($status === 'completed') { $isSuccess = true; $message = "Payment of ৳" . number_format($order['total_amount'],2) . " completed successfully!"; }
        elseif ($status === 'cancelled' || $status === 'refunded') { $message = "Payment was not completed. Please try again."; }
        else { $message = "Your payment is being processed."; }
    } else {
        $message = "Order not found.";
    }

    if (isset($_GET['format']) && $_GET['format'] === 'json') {
        header('Content-Type: application/json');
        echo json_encode(['status'=>$isSuccess?'success':'pending_or_failed','message'=>$message,'payment_status'=>$order['status']??'not_found']);
        exit;
    }
    ?>
    <!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Payment Status — Shikhbo</title><script src="https://cdn.tailwindcss.com"></script><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"><link rel="icon" type="image/png" href="/image/app_logo.png"></head>
    <body class="bg-gray-50 dark:bg-gray-900 flex items-center justify-center min-h-screen p-4">
    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl w-full max-w-md p-8 text-center">
        <div class="mb-6 flex justify-center">
            <?php if ($isSuccess): ?>
                <div class="w-20 h-20 bg-green-100 dark:bg-green-900/30 text-green-500 rounded-full flex items-center justify-center text-4xl"><i class="fa-solid fa-circle-check"></i></div>
            <?php else: ?>
                <div class="w-20 h-20 bg-red-100 dark:bg-red-900/30 text-red-500 rounded-full flex items-center justify-center text-4xl"><i class="fa-solid fa-circle-xmark"></i></div>
            <?php endif; ?>
        </div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-4"><?php echo $isSuccess?'Payment Successful':'Payment Status'; ?></h1>
        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-2xl p-4 mb-6 text-sm text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($message); ?></div>
        <div class="flex flex-col gap-3">
            <?php if ($isSuccess): ?>
                <a href="/index.php" class="px-6 py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold transition text-sm">Start Learning</a>
            <?php endif; ?>
            <a href="/" class="px-6 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 text-gray-600 dark:text-gray-300 rounded-xl font-semibold transition text-sm">Back to Home</a>
        </div>
    </div>
    </body></html>
    <?php
    exit;
}

$txnStmt = $conn->prepare("SELECT id, user_id, course_id, enrollment_id, piprapay_pp_id, amount, status FROM transactions WHERE transaction_id = ?");
$txnStmt->bind_param('s', $ref);
$txnStmt->execute();
$txn = $txnStmt->get_result()->fetch_assoc();
$txnStmt->close();

$message = 'Checking payment status...';
$isSuccess = false;
$courseTitle = 'Course';

if ($txn) {
    $courseStmt = $conn->prepare("SELECT title FROM courses WHERE id = ?");
    $courseStmt->bind_param('i', $txn['course_id']);
    $courseStmt->execute();
    $course = $courseStmt->get_result()->fetch_assoc();
    $courseStmt->close();
    if ($course) $courseTitle = $course['title'];

    $ppId = $txn['piprapay_pp_id'];
    $status = $txn['status'];

    if (($status === 'pending' || $status === 'initiated') && !empty($ppId)) {
        $ch = curl_init(PIPRAPAY_BASE_URL . '/verify-payment');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['pp_id' => $ppId]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'MHS-PIPRAPAY-API-KEY: ' . PIPRAPAY_API_KEY,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $resData = json_decode($response, true);
            $paymentStatus = strtolower($resData['status'] ?? '');
            $enrollmentId = $txn['enrollment_id'];
            $courseId = $txn['course_id'];

            if ($paymentStatus === 'completed') {
                $conn->begin_transaction();
                try {
                    $updateTxn = $conn->prepare("UPDATE transactions SET status='completed', gateway_response=?, completed_at=NOW() WHERE id=?");
                    if ($updateTxn) {
                        $gateway_response = json_encode($resData);
                        $updateTxn->bind_param('si', $gateway_response, $txn['id']);
                        $updateTxn->execute(); $updateTxn->close();
                    }

                    $updateEnroll = $conn->prepare("UPDATE enrollments SET status='active', enrolled_at=NOW() WHERE id=?");
                    $updateEnroll->bind_param('i', $enrollmentId);
                    $updateEnroll->execute(); $updateEnroll->close();

                    $conn->query("UPDATE courses SET total_enrolled=(SELECT COUNT(*) FROM enrollments WHERE course_id=$courseId AND status='active') WHERE id=$courseId");
                    $conn->commit();
                    $status = 'completed';
                } catch (Exception $e) { $conn->rollback(); }
            } elseif ($paymentStatus === 'failed') {
                $updateTxn = $conn->prepare("UPDATE transactions SET status='failed', gateway_response=? WHERE id=?");
                if ($updateTxn) {
                    $gateway_response = json_encode($resData);
                    $updateTxn->bind_param('si', $gateway_response, $txn['id']);
                    $updateTxn->execute(); $updateTxn->close();
                }
                $status = 'failed';
            }
        }
    }

    if ($status === 'completed') { $isSuccess = true; $message = "Payment of ৳" . number_format($txn['amount'],2) . " completed successfully! You are now enrolled."; }
    elseif ($status === 'failed') { $message = "Payment failed or was cancelled. Please try again."; }
    else { $message = "Your payment is currently pending or processing."; }
} else { $message = "Transaction ID not found."; }

if (isset($_GET['format']) && $_GET['format'] === 'json') {
    header('Content-Type: application/json');
    echo json_encode(['status'=>$isSuccess?'success':'pending_or_failed','message'=>$message,'payment_status'=>$txn?$txn['status']:'not_found']);
    exit;
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Payment Status — Shikhbo</title><script src="https://cdn.tailwindcss.com"></script><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"><link rel="icon" type="image/png" href="/image/app_logo.png"></head>
<body class="bg-gray-50 dark:bg-gray-900 flex items-center justify-center min-h-screen p-4">
<div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl w-full max-w-md p-8 text-center">
    <div class="mb-6 flex justify-center">
        <?php if ($isSuccess): ?>
            <div class="w-20 h-20 bg-green-100 dark:bg-green-900/30 text-green-500 rounded-full flex items-center justify-center text-4xl"><i class="fa-solid fa-circle-check"></i></div>
        <?php else: ?>
            <div class="w-20 h-20 bg-red-100 dark:bg-red-900/30 text-red-500 rounded-full flex items-center justify-center text-4xl"><i class="fa-solid fa-circle-xmark"></i></div>
        <?php endif; ?>
    </div>
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-2"><?php echo $isSuccess?'Payment Successful':'Payment Status'; ?></h1>
    <p class="text-sm text-gray-500 dark:text-gray-400 font-medium mb-4"><?php echo htmlspecialchars($courseTitle); ?></p>
    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-2xl p-4 mb-6 text-sm text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($message); ?></div>
    <div class="flex flex-col gap-3">
        <?php if ($isSuccess): ?>
            <a href="/" class="px-6 py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold transition text-sm">Start Learning</a>
        <?php else: ?>
            <a href="/#courses" class="px-6 py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold transition text-sm">Retry Payment</a>
        <?php endif; ?>
        <a href="/" class="px-6 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 text-gray-600 dark:text-gray-300 rounded-xl font-semibold transition text-sm">Back to Home</a>
    </div>
</div>
</body></html>
