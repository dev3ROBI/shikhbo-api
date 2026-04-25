<?php
/**
 * VERIFY TOKEN FOR APP
 * Requires: uid, season, u_state
 * 
 * Usage: POST /api/verify_token_app.php
 * Body: {"token":"...", "uid":1, "season":"__", "u_state":"1"}
 */
require_once __DIR__ . '/../includes/app_security_validation.php';
require_once __DIR__ . '/../api/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

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

$token = $data['token'] ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
$token = str_replace('Bearer ', '', $token);

if (empty($token)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Token required']);
    exit();
}

// Database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
$conn->set_charset('utf8mb4');

// Verify token
$stmt = $conn->prepare("SELECT user_id FROM user_tokens WHERE token = ? AND expires_at > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$userId = null;
if ($row = $result->fetch_assoc()) {
    $userId = $row['user_id'];
}
$stmt->close();

if (!$userId) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid or expired token', 'valid' => false]);
    exit();
}

// Get user details
$stmt = $conn->prepare("SELECT name, email, status FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

echo json_encode([
    'status' => 'success',
    'valid' => true,
    'user' => [
        'user_id' => (int)$userId,
        'name' => $user['name'],
        'email' => $user['email'],
        'status' => $user['status']
    ],
    'user_info' => [
        'uid' => (int)$uid,
        'requests_remaining' => $security['remaining']
    ]
]);