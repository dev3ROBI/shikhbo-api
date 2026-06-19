<?php
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$receivedKey = $_SERVER['HTTP_MHS_PIPRAPAY_API_KEY'] ?? $_SERVER['HTTP_MH_PIPRAPAY_API_KEY'] ?? '';
if (empty($receivedKey) || $receivedKey !== PIPRAPAY_API_KEY) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized key']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$ppId = $input['pp_id'] ?? null;

if (empty($ppId)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'pp_id is required']);
    exit;
}

$conn = getDBConnection();

$txnCheck = $conn->prepare("SELECT id, status, enrollment_id, course_id FROM transactions WHERE piprapay_pp_id = ?");
$txnCheck->bind_param('s', $ppId);
$txnCheck->execute();
$txn = $txnCheck->get_result()->fetch_assoc();
$txnCheck->close();

if ($txn && $txn['status'] === 'completed') {
    echo json_encode(['status' => 'success', 'message' => 'Webhook already processed']);
    exit;
}

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

if ($httpCode !== 200 || !$response) {
    http_response_code(502);
    echo json_encode(['status' => 'error', 'message' => 'Failed to reach PipraPay verify API']);
    exit;
}

$resData = json_decode($response, true);
$statusData = $resData;

if (!$statusData || !isset($statusData['status'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid verify response structure']);
    exit;
}

$metadata = json_decode($statusData['metadata']['invoice_id'] ?? $statusData['metadata'] ?? '{}', true);
$transactionId = $metadata['transaction_id'] ?? null;

if (empty($transactionId)) {
    if ($txn) {
        $transactionId = $txn['id'];
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'No transaction found matching metadata']);
        exit;
    }
}

$stmt = $conn->prepare("SELECT id, enrollment_id, course_id, status FROM transactions WHERE transaction_id = ?");
$stmt->bind_param('s', $transactionId);
$stmt->execute();
$localTxn = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$localTxn) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Transaction not found locally']);
    exit;
}

$paymentStatus = strtolower($statusData['status']);
$enrollmentId = $localTxn['enrollment_id'];
$courseId = $localTxn['course_id'];

$conn->begin_transaction();

try {
    if ($paymentStatus === 'completed') {
        $updateTxn = $conn->prepare("UPDATE transactions SET status = 'completed', piprapay_pp_id = ?, gateway_response = ?, completed_at = NOW() WHERE id = ?");
        $gatewayJson = json_encode($resData);
        $updateTxn->bind_param('ssi', $ppId, $gatewayJson, $localTxn['id']);
        $updateTxn->execute();
        $updateTxn->close();

        $updateEnroll = $conn->prepare("UPDATE enrollments SET status = 'active', enrolled_at = NOW() WHERE id = ?");
        $updateEnroll->bind_param('i', $enrollmentId);
        $updateEnroll->execute();
        $updateEnroll->close();

        $conn->query("UPDATE courses SET total_enrolled = (SELECT COUNT(*) FROM enrollments WHERE course_id = $courseId AND status = 'active') WHERE id = $courseId");

    } elseif ($paymentStatus === 'failed') {
        $updateTxn = $conn->prepare("UPDATE transactions SET status = 'failed', piprapay_pp_id = ?, gateway_response = ? WHERE id = ?");
        $gatewayJson = json_encode($resData);
        $updateTxn->bind_param('ssi', $ppId, $gatewayJson, $localTxn['id']);
        $updateTxn->execute();
        $updateTxn->close();
    }

    $conn->commit();
    echo json_encode(['status' => 'success', 'payment_status' => $paymentStatus]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database transaction failed: ' . $e->getMessage()]);
}
