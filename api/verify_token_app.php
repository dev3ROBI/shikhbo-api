<?php
/**
 * VERIFY TOKEN FOR APP
 * Accepts Bearer token in Authorization header
 * 
 * Usage: POST /api/verify_token_app.php
 * Header: Authorization: Bearer <token>
 * Body: {"uid": 1, "season": "...", "u_state": "1"}
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

// Get POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Get security parameters
$uid = $data['uid'] ?? null;
$season = $data['season'] ?? null;
$u_state = $data['u_state'] ?? null;

// Validate security
$security = requireAppSecurity($uid, $season, $u_state);

// Get Bearer token from header
$token = getBearerToken();

if (empty($token)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Token required in Authorization header']);
    exit();
}

// Database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
$conn->set_charset('utf8mb4');

// Verify token
$stmt = $conn->prepare("SELECT user_id, expires_at FROM user_tokens WHERE token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$tokenData = $result->fetch_assoc();
$stmt->close();

if (!$tokenData) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid token', 'valid' => false]);
    exit();
}

if (strtotime($tokenData['expires_at']) < time()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Token expired', 'valid' => false]);
    exit();
}

// Verify token belongs to this user
if ($tokenData['user_id'] != $uid) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Token does not match user', 'valid' => false]);
    exit();
}

// Get user details
$stmt = $conn->prepare("SELECT name, email, status FROM users WHERE id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

echo json_encode([
    'status' => 'success',
    'valid' => true,
    'user' => [
        'user_id' => (int)$uid,
        'name' => $user['name'],
        'email' => $user['email'],
        'status' => $user['status']
    ],
    'access' => 'unlimited'
]);