<?php
/**
 * VERIFY COURSE PURCHASE VIA GOOGLE PLAY BILLING
 * Called after a successful Google Play purchase for a paid course.
 * POST params: uid, season, u_state, course_id, product_id, purchase_token, order_id, purchase_time
 */
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../includes/app_security_validation.php';
require_once __DIR__ . '/config.php';

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
    $courseId = intval($input['course_id'] ?? 0);
    $productId = $input['product_id'] ?? '';
    $purchaseToken = $input['purchase_token'] ?? '';
    $orderId = $input['order_id'] ?? '';
    $purchaseTime = $input['purchase_time'] ?? '';

    if (!$uid || !$season || !$u_state || !$courseId || !$purchaseToken) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
        exit();
    }

    $security = requireAppSecurity($uid, $season, $u_state);
    $conn = getAppSecurityConn();
    $conn->set_charset('utf8mb4');

    // Ensure course_purchases table exists
    $conn->query("CREATE TABLE IF NOT EXISTS course_purchases (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        course_id INT UNSIGNED NOT NULL,
        product_id VARCHAR(128) NOT NULL,
        purchase_token VARCHAR(512) NOT NULL,
        order_id VARCHAR(128) DEFAULT NULL,
        purchase_time DATETIME DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_token (purchase_token(255)),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
        INDEX idx_user (user_id),
        INDEX idx_course (course_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Verify course exists and is a paid active course
    $courseStmt = $conn->prepare("SELECT id, title, price, is_free, is_active FROM courses WHERE id = ?");
    $courseStmt->bind_param('i', $courseId);
    $courseStmt->execute();
    $course = $courseStmt->get_result()->fetch_assoc();
    $courseStmt->close();

    if (!$course || $course['is_active'] != 1) {
        echo json_encode(['status' => 'error', 'message' => 'Course not found or inactive']);
        exit();
    }

    if ($course['is_free'] == 1) {
        echo json_encode(['status' => 'error', 'message' => 'This course is free, please enroll directly']);
        exit();
    }

    // Check for existing purchase with same token (idempotency)
    $checkStmt = $conn->prepare("SELECT id FROM course_purchases WHERE purchase_token = ?");
    $checkStmt->bind_param('s', $purchaseToken);
    $checkStmt->execute();
    $existing = $checkStmt->get_result()->fetch_assoc();
    $checkStmt->close();

    if ($existing) {
        echo json_encode(['status' => 'success', 'message' => 'Purchase already recorded', 'is_enrolled' => true]);
        exit();
    }

    // Check user already has active enrollment
    $enrollCheck = $conn->prepare("SELECT id, status FROM enrollments WHERE user_id = ? AND course_id = ?");
    $enrollCheck->bind_param('ii', $uid, $courseId);
    $enrollCheck->execute();
    $enrollment = $enrollCheck->get_result()->fetch_assoc();
    $enrollCheck->close();

    if ($enrollment && $enrollment['status'] === 'active') {
        echo json_encode(['status' => 'success', 'message' => 'Already enrolled', 'is_enrolled' => true]);
        exit();
    }

    // Record the purchase
    $purchaseDateTime = $purchaseTime ? date('Y-m-d H:i:s', strtotime($purchaseTime)) : date('Y-m-d H:i:s');

    $insertPurchase = $conn->prepare("INSERT INTO course_purchases (user_id, course_id, product_id, purchase_token, order_id, purchase_time) VALUES (?, ?, ?, ?, ?, ?)");
    $insertPurchase->bind_param('iissss', $uid, $courseId, $productId, $purchaseToken, $orderId, $purchaseDateTime);
    $insertPurchase->execute();
    $insertPurchase->close();

    // Create or update enrollment to active
    if ($enrollment) {
        $updateEnroll = $conn->prepare("UPDATE enrollments SET status = 'active', enrolled_at = NOW(), completed_at = NULL WHERE id = ?");
        $updateEnroll->bind_param('i', $enrollment['id']);
        $updateEnroll->execute();
        $updateEnroll->close();
    } else {
        $insertEnroll = $conn->prepare("INSERT INTO enrollments (user_id, course_id, status) VALUES (?, ?, 'active')");
        $insertEnroll->bind_param('ii', $uid, $courseId);
        $insertEnroll->execute();
        $insertEnroll->close();
    }

    // Update enrollment count
    $conn->query("UPDATE courses SET total_enrolled = (SELECT COUNT(*) FROM enrollments WHERE course_id = $courseId AND status = 'active') WHERE id = $courseId");

    echo json_encode([
        'status' => 'success',
        'message' => 'Course purchased and enrolled successfully',
        'is_enrolled' => true,
        'course_id' => $courseId
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
