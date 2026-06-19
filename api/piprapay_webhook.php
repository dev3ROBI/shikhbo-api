<?php
/**
 * Handle payment notification webhooks from PipraPay
 */
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// 1. Verify PipraPay webhook API Key header
$receivedKey = $_SERVER['HTTP_MH_PIPRAPAY_API_KEY'] ?? $_SERVER['REDIRECT_HTTP_MH_PIPRAPAY_API_KEY'] ?? '';
if (empty($receivedKey) || $receivedKey !== PIPRAPAY_API_KEY) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized key']);
    exit;
}

// 2. Parse payload
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$ppId = $input['pp_id'] ?? null;

if (empty($ppId)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'pp_id is required']);
    exit;
}

$conn = getDBConnection();

// Avoid duplicate processing by checking if this pp_id is already completed
$txnCheck = $conn->prepare("SELECT id, status, enrollment_id, course_id FROM transactions WHERE piprapay_pp_id = ?");
$txnCheck->bind_param('s', $ppId);
$txnCheck->execute();
$txn = $txnCheck->get_result()->fetch_assoc();
$txnCheck->close();

if ($txn && $txn['status'] === 'completed') {
    echo json_encode(['status' => 'success', 'message' => 'Webhook already processed']);
    exit;
}

// 3. Query PipraPay API verify-payments to fetch official payment status (highly secure)
$ch = curl_init(PIPRAPAY_BASE_URL . '/api/verify-payments');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['pp_id' => $ppId]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'mh-piprapay-api-key: ' . PIPRAPAY_API_KEY,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$response) {
    http_response_code(502);
    echo json_encode(['status' => 'error', 'message' => 'Failed to reach PipraPay verify API']);
    exit;
}

$resData = json_decode($response, true);
$statusData = $resData['data'] ?? null;

if (!$statusData || !isset($statusData['status'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid verify response structure']);
    exit;
}

// Parse metadata
$metadata = json_decode($statusData['metadata'] ?? '{}', true);
$transactionId = $metadata['transaction_id'] ?? null;

if (empty($transactionId)) {
    // If metadata doesn't contain it, try checking if we already matched the transaction row by pp_id
    if ($txn) {
        $transactionId = $txn['id']; // We will use DB status update instead
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'No transaction found matching metadata']);
        exit;
    }
}

// Fetch local transaction row
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

$paymentStatus = strtolower($statusData['status']); // completed, failed, pending etc.
$enrollmentId = $localTxn['enrollment_id'];
$courseId = $localTxn['course_id'];

$conn->begin_transaction();

try {
    if ($paymentStatus === 'completed') {
        // Update Transaction
        $updateTxn = $conn->prepare("UPDATE transactions SET status = 'completed', piprapay_pp_id = ?, gateway_response = ?, completed_at = NOW() WHERE id = ?");
        $gatewayJson = json_encode($resData);
        $updateTxn->bind_param('ssi', $ppId, $gatewayJson, $localTxn['id']);
        $updateTxn->execute();
        $updateTxn->close();

        // Activate enrollment
        $updateEnroll = $conn->prepare("UPDATE enrollments SET status = 'active', enrolled_at = NOW() WHERE id = ?");
        $updateEnroll->bind_param('i', $enrollmentId);
        $updateEnroll->execute();
        $updateEnroll->close();

        // Update Course enrollment counter
        $conn->query("UPDATE courses SET total_enrolled = (SELECT COUNT(*) FROM enrollments WHERE course_id = $courseId AND status = 'active') WHERE id = $courseId");

    } elseif ($paymentStatus === 'failed') {
        // Update Transaction
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
