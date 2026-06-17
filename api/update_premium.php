<?php
/**
 * UPDATE PREMIUM STATUS
 * After a successful Google Play purchase, the app calls this endpoint
 * to record the purchase and update the user's premium status + expiry.
 * 
 * Usage: POST /api/update_premium.php
 * Body: {"uid":1, "season":"...", "u_state":"1", "product_id":"premium_1m", "purchase_token":"...", "order_id":"...", "purchase_time":"2026-06-17 18:00:00"}
 */
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../includes/app_security_validation.php';
require_once __DIR__ . '/../api/config.php';

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
    exit();
}

try {
    $input = json_decode(file_get_contents('php://input'), true);

    $uid = $input['uid'] ?? null;
    $season = $input['season'] ?? null;
    $u_state = $input['u_state'] ?? null;

    if (!$uid || !$season || !$u_state) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
        exit();
    }

    $security = requireAppSecurity($uid, $season, $u_state);

    $conn = getAppSecurityConn();
    $conn->set_charset('utf8mb4');

    $productId = $input['product_id'] ?? '';
    $purchaseToken = $input['purchase_token'] ?? '';
    $orderId = $input['order_id'] ?? '';
    $purchaseTime = $input['purchase_time'] ?? '';

    if (!$productId || !$purchaseToken) {
        echo json_encode(['status' => 'error', 'message' => 'Missing product_id or purchase_token']);
        exit();
    }

    // Calculate expiry date based on product_id
    $expiryDate = null;
    switch ($productId) {
        case 'premium_1m':
            $expiryDate = date('Y-m-d H:i:s', strtotime('+1 month'));
            break;
        case 'premium_3m':
            $expiryDate = date('Y-m-d H:i:s', strtotime('+3 months'));
            break;
        case 'premium_6m':
            $expiryDate = date('Y-m-d H:i:s', strtotime('+6 months'));
            break;
        case 'premium_12m':
            $expiryDate = date('Y-m-d H:i:s', strtotime('+12 months'));
            break;
        case 'premium_lifetime':
            $expiryDate = null; // never expires
            break;
        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid product_id']);
            exit();
    }

    // Check for existing purchase with same token (idempotency)
    $checkStmt = $conn->prepare("SELECT id FROM premium_purchases WHERE purchase_token = ?");
    $checkStmt->bind_param('s', $purchaseToken);
    $checkStmt->execute();
    $existing = $checkStmt->get_result()->fetch_assoc();
    $checkStmt->close();

    if ($existing) {
        echo json_encode(['status' => 'success', 'message' => 'Purchase already recorded']);
        exit();
    }

    // Convert purchase_time to MySQL datetime
    $purchaseDateTime = $purchaseTime ? date('Y-m-d H:i:s', strtotime($purchaseTime)) : date('Y-m-d H:i:s');

    // Insert into premium_purchases
    $insertStmt = $conn->prepare("INSERT INTO premium_purchases (user_id, product_id, purchase_token, order_id, purchase_time, expiry_date) VALUES (?, ?, ?, ?, ?, ?)");
    $insertStmt->bind_param('isssss', $uid, $productId, $purchaseToken, $orderId, $purchaseDateTime, $expiryDate);
    $insertStmt->execute();
    $insertStmt->close();

    // Update user's premium status
    if ($expiryDate === null) {
        // Lifetime
        $updateStmt = $conn->prepare("UPDATE users SET is_premium = 1, premium_expiry_date = NULL, updated_at = NOW() WHERE id = ?");
        $updateStmt->bind_param('i', $uid);
    } else {
        // Check if user already has premium with a later expiry
        $userStmt = $conn->prepare("SELECT premium_expiry_date FROM users WHERE id = ?");
        $userStmt->bind_param('i', $uid);
        $userStmt->execute();
        $userRow = $userStmt->get_result()->fetch_assoc();
        $userStmt->close();

        if ($userRow && $userRow['premium_expiry_date'] && strtotime($userRow['premium_expiry_date']) > strtotime($expiryDate)) {
            // Existing expiry is later — keep it
            $finalExpiry = $userRow['premium_expiry_date'];
        } else {
            $finalExpiry = $expiryDate;
        }

        $updateStmt = $conn->prepare("UPDATE users SET is_premium = 1, premium_expiry_date = ?, updated_at = NOW() WHERE id = ?");
        $updateStmt->bind_param('si', $finalExpiry, $uid);
    }
    $updateStmt->execute();
    $updateStmt->close();

    echo json_encode([
        'status' => 'success',
        'message' => 'Premium activated successfully',
        'expiry_date' => $expiryDate,
        'is_premium' => true
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
