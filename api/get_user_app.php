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

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$uid = $data['uid'] ?? null;
$season = $data['season'] ?? null;
$u_state = $data['u_state'] ?? null;

// Validate security
$security = requireAppSecurity($uid, $season, $u_state);

// Get Bearer token from header (optional - security params already validate user)
$token = getBearerToken();

$conn = getAppSecurityConn();

// If token provided, verify it
if ($token) {
    $tokenVerify = verifyToken($token, $uid);
    if (!$tokenVerify['valid']) {
        // Token invalid but user is valid via security params
    }
}

// Get user profile
$stmt = $conn->prepare("SELECT id, name, email, profile_image, referral_code, login_method, language, tagline, streak, member_since, is_premium, status, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
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
]);