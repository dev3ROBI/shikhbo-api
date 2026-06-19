<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/app_security_validation.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$uid = null;
$userEmail = '';
$userName = '';
$userMobile = '';
$conn = null;

if (isLoggedIn()) {
    $user = getCurrentUser();
    $uid = $user['id'];
    $userName = $user['name'];
    $userEmail = $user['email'];
    $userMobile = $user['mobile'] ?? $user['phone'] ?? '01700000000';
    $conn = getDBConnection();
} else {
    $appUid = $input['uid'] ?? null;
    $season = $input['season'] ?? null;
    $u_state = $input['u_state'] ?? null;

    if ($appUid && $season && $u_state) {
        $security = requireAppSecurity($appUid, $season, $u_state);
        $uid = $appUid;
        $conn = getAppSecurityConn();

        $stmt = $conn->prepare("SELECT name, email, mobile, phone FROM users WHERE id = ?");
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $userData = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($userData) {
            $userName = $userData['name'];
            $userEmail = $userData['email'];
            $userMobile = $userData['mobile'] ?? $userData['phone'] ?? '01700000000';
        }
    }
}

if (!$uid) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized. Please login first.']);
    exit;
}

$courseId = intval($input['course_id'] ?? 0);
if ($courseId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'course_id required']);
    exit;
}

$courseStmt = $conn->prepare("SELECT id, title, price, is_free, is_active FROM courses WHERE id = ?");
$courseStmt->bind_param('i', $courseId);
$courseStmt->execute();
$course = $courseStmt->get_result()->fetch_assoc();
$courseStmt->close();

if (!$course || $course['is_active'] != 1) {
    echo json_encode(['status' => 'error', 'message' => 'Course not found or inactive']);
    exit;
}

if ($course['is_free'] == 1) {
    echo json_encode(['status' => 'error', 'message' => 'This is a free course. Please enroll directly.']);
    exit;
}

$enrollStmt = $conn->prepare("SELECT id, status FROM enrollments WHERE user_id = ? AND course_id = ?");
$enrollStmt->bind_param('ii', $uid, $courseId);
$enrollStmt->execute();
$enrollment = $enrollStmt->get_result()->fetch_assoc();
$enrollStmt->close();

if ($enrollment) {
    if ($enrollment['status'] === 'active') {
        echo json_encode(['status' => 'success', 'message' => 'Already enrolled', 'is_enrolled' => true]);
        exit;
    }
}

$transactionId = 'TXN-' . time() . '-' . rand(1000, 9999);
$amount = floatval($course['price']);
$currency = 'BDT';

$enrollmentId = null;
if ($enrollment) {
    $enrollmentId = $enrollment['id'];
    $updateEnroll = $conn->prepare("UPDATE enrollments SET status = 'pending_payment' WHERE id = ?");
    $updateEnroll->bind_param('i', $enrollmentId);
    $updateEnroll->execute();
    $updateEnroll->close();
} else {
    $insertEnroll = $conn->prepare("INSERT INTO enrollments (user_id, course_id, status) VALUES (?, ?, 'pending_payment')");
    $insertEnroll->bind_param('ii', $uid, $courseId);
    $insertEnroll->execute();
    $enrollmentId = $conn->insert_id;
    $insertEnroll->close();
}

$status = 'initiated';
$insertTxn = $conn->prepare("INSERT INTO transactions (user_id, course_id, enrollment_id, transaction_id, amount, currency, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
$insertTxn->bind_param('iiisdss', $uid, $courseId, $enrollmentId, $transactionId, $amount, $currency, $status);
$insertTxn->execute();
$insertTxn->close();

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$redirectVerifyUrl = $protocol . '://' . $host . '/api/piprapay_verify.php?transaction_id=' . urlencode($transactionId);

$payload = [
    'full_name' => $userName,
    'email_address' => $userEmail,
    'mobile_number' => $userMobile,
    'amount' => (string)$amount,
    'currency' => $currency,
    'metadata' => json_encode([
        'transaction_id' => $transactionId,
        'course_id' => $courseId,
        'user_id' => $uid
    ]),
    'return_url' => $redirectVerifyUrl,
    'webhook_url' => $protocol . '://' . $host . '/api/piprapay_webhook.php'
];

$ch = curl_init(PIPRAPAY_BASE_URL . '/checkout/redirect');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'MHS-PIPRAPAY-API-KEY: ' . PIPRAPAY_API_KEY,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($httpCode !== 200 || !$response) {
    $failStatus = 'failed';
    $updateTxn = $conn->prepare("UPDATE transactions SET status = ? WHERE transaction_id = ?");
    $updateTxn->bind_param('ss', $failStatus, $transactionId);
    $updateTxn->execute();
    $updateTxn->close();

    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to initialize payment with PipraPay',
        'http_code' => $httpCode,
        'debug' => $response,
        'curl_error' => $curlError
    ]);
    exit;
}

$resData = json_decode($response, true);

if (!empty($resData['pp_url'])) {
    $ppId = $resData['pp_id'] ?? '';
    $pendingStatus = 'pending';
    $updateTxn = $conn->prepare("UPDATE transactions SET piprapay_pp_id = ?, status = ?, gateway_response = ? WHERE transaction_id = ?");
    $gatewayJson = json_encode($resData);
    $updateTxn->bind_param('ssss', $ppId, $pendingStatus, $gatewayJson, $transactionId);
    $updateTxn->execute();
    $updateTxn->close();

    echo json_encode([
        'status' => 'success',
        'transaction_id' => $transactionId,
        'checkout_url' => $resData['pp_url']
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'PipraPay initialization returned invalid response',
        'response' => $resData
    ]);
}
