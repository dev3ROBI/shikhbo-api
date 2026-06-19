<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/app_security_validation.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status'=>'error','message'=>'Method not allowed']); exit;
}

$user = getCurrentUser();
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$uid = null; $conn = null; $userName = ''; $userEmail = ''; $userMobile = '';

if ($user) {
    $uid = $user['id'];
    $userName = $user['name'];
    $userEmail = $user['email'];
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT mobile, phone FROM users WHERE id = ?");
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $d = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $userMobile = $d['mobile'] ?? $d['phone'] ?? '01700000000';
} else {
    $appUid = $input['uid'] ?? null;
    $season = $input['season'] ?? null;
    $u_state = $input['u_state'] ?? null;
    if ($appUid && $season && $u_state) {
        requireAppSecurity($appUid, $season, $u_state);
        $uid = $appUid;
        $conn = getAppSecurityConn();
        $stmt = $conn->prepare("SELECT name, email, mobile, phone FROM users WHERE id = ?");
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $d = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($d) { $userName = $d['name']; $userEmail = $d['email']; $userMobile = $d['mobile'] ?? $d['phone'] ?? '01700000000'; }
    }
}

if (!$uid || !$conn) {
    http_response_code(401);
    echo json_encode(['status'=>'error','message'=>'Please login first']); exit;
}

$conn->begin_transaction();
try {
    $cartResult = $conn->query("
        SELECT c.course_id, cr.title, cr.price, cr.is_free
        FROM carts c JOIN courses cr ON c.course_id = cr.id AND cr.is_active = 1
        WHERE c.user_id = $uid
    ");

    $cartItems = [];
    $totalAmount = 0;
    while ($row = $cartResult->fetch_assoc()) {
        if ($row['is_free'] == 1) continue;
        $p = floatval($row['price']);
        $totalAmount += $p;
        $cartItems[] = $row;
    }

    if (empty($cartItems)) {
        echo json_encode(['status'=>'error','message'=>'Cart is empty']); $conn->rollback(); exit;
    }

    $orderRef = 'ORD-' . time() . '-' . rand(1000, 9999);
    $stmt = $conn->prepare("INSERT INTO orders (user_id, order_id, total_amount, status) VALUES (?, ?, ?, 'pending')");
    $stmt->bind_param('isd', $uid, $orderRef, $totalAmount);
    $stmt->execute();
    $stmt->close();

    foreach ($cartItems as $item) {
        $enrollStmt = $conn->prepare("SELECT id, status FROM enrollments WHERE user_id = ? AND course_id = ?");
        $enrollStmt->bind_param('ii', $uid, $item['course_id']);
        $enrollStmt->execute();
        $existing = $enrollStmt->get_result()->fetch_assoc();
        $enrollStmt->close();

        $enrollId = null;
        if ($existing) {
            $enrollId = $existing['id'];
            $stmt = $conn->prepare("UPDATE enrollments SET status = 'pending_payment' WHERE id = ?");
            $stmt->bind_param('i', $enrollId);
            $stmt->execute();
            $stmt->close();
        } else {
            $insertEnrollStmt = $conn->prepare("INSERT INTO enrollments (user_id, course_id, status) VALUES (?, ?, 'pending_payment')");
            if (!$insertEnrollStmt) {
                throw new Exception('Enrollment insert prepare failed: ' . $conn->error);
            }
            $insertEnrollStmt->bind_param('ii', $uid, $item['course_id']);
            $insertEnrollStmt->execute();
            $insertEnrollStmt->close();
            $enrollId = $conn->insert_id;
        }

        $txnId = 'TXN-' . time() . '-' . rand(1000, 9999);
        $stmt2 = $conn->prepare("INSERT INTO transactions (user_id, course_id, enrollment_id, transaction_id, amount, currency, status) VALUES (?, ?, ?, ?, ?, 'BDT', 'initiated')");
        $stmt2->bind_param('iiisd', $uid, $item['course_id'], $enrollId, $txnId, $item['price']);
        $stmt2->execute();
        $stmt2->close();

        $stmt3 = $conn->prepare("INSERT INTO order_items (order_ref, course_id, amount, enrollment_id) VALUES (?, ?, ?, ?)");
        $stmt3->bind_param('sidi', $orderRef, $item['course_id'], $item['price'], $enrollId);
        $stmt3->execute();
        $stmt3->close();
    }

    $deleteStmt = $conn->prepare("DELETE FROM carts WHERE user_id = ?");
    $deleteStmt->bind_param('i', $uid);
    $deleteStmt->execute();
    $deleteStmt->close();
    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status'=>'error','message'=>'Checkout failed: '.$e->getMessage()]); exit;
}

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];

$payload = [
    'full_name' => $userName,
    'email_address' => $userEmail,
    'mobile_number' => $userMobile,
    'amount' => (string)$totalAmount,
    'currency' => 'BDT',
    'metadata' => json_encode(['order_id' => $orderRef]),
    'return_url' => $protocol . '://' . $host . '/api/piprapay_verify.php?transaction_id=' . urlencode($orderRef),
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
curl_close($ch);

if ($httpCode !== 200 || !$response) {
    echo json_encode(['status'=>'error','message'=>'Payment gateway error','http_code'=>$httpCode,'debug'=>$response]); exit;
}

$resData = json_decode($response, true);
if (!empty($resData['pp_url'])) {
    $ppId = $resData['pp_id'] ?? '';
    $gatewayResponse = json_encode($resData);
    $stmt = $conn->prepare("UPDATE orders SET piprapay_pp_id = ?, gateway_response = ? WHERE order_id = ?");
    $stmt->bind_param('sss', $ppId, $gatewayResponse, $orderRef);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['status'=>'success','order_id'=>$orderRef,'checkout_url'=>$resData['pp_url']]);
} else {
    echo json_encode(['status'=>'error','message'=>'Invalid gateway response','response'=>$resData]);
}
