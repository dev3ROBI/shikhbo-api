<?php
/**
 * GET USER PROFILE FOR APP
 * Requires: uid, season, u_state in body
 * Accepts Bearer token in Authorization header
 * 
 * Usage: POST /api/get_user_app.php
 * Header: Authorization: Bearer <token>
 * Body: {"uid":1, "season":"...", "u_state":"1"}
 */
error_reporting(0);
ini_set('display_errors', 0);

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
    require_once __DIR__ . '/../includes/app_security_validation.php';
    require_once __DIR__ . '/../api/config.php';

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['uid']) || !isset($input['season']) || !isset($input['u_state'])) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
        exit();
    }

    $uid = $input['uid'];
    $season = $input['season'];
    $u_state = $input['u_state'];

    // Validate security
    $security = requireAppSecurity($uid, $season, $u_state);

    $conn = getAppSecurityConn();
    $conn->set_charset('utf8mb4');

    // Get user profile
    $stmt = $conn->prepare("SELECT id, name, email, profile_image, referral_code, login_method, language, tagline, streak, member_since, is_premium, status, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
        exit();
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    echo json_encode([
        'status' => 'success',
        'user' => [
            'user_id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'profile_image' => $user['profile_image'] ?? '',
            'referral_code' => $user['referral_code'] ?? '',
            'login_method' => $user['login_method'] ?? 'email',
            'tagline' => $user['tagline'] ?? '',
            'streak' => (int)$user['streak'],
            'member_since' => $user['member_since'] ?? date('Y-m-d', strtotime($user['created_at'])),
            'is_premium' => (bool)$user['is_premium']
        ],
        'access' => 'unlimited'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}