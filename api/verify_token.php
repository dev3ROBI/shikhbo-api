<?php
/**
 * Verify Authentication Token
 * 
 * POST /api/verify_token.php
 * Header: Authorization: Bearer <token>
 * Body: {"token": "..."}
 */
require_once 'connection.php';
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept-Language, X-App-Language');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit();
}

function verifyToken($token) {
    global $conn;
    $stmt = $conn->prepare("SELECT user_id FROM user_tokens WHERE token = ? AND expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stmt->close();
        return $row['user_id'];
    }
    $stmt->close();
    return null;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$token = $data['token'] ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
$token = str_replace('Bearer ', '', $token);

if (empty($token)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Token required']);
    exit();
}

$user_id = verifyToken($token);
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid or expired token', 'valid' => false]);
    exit();
}

$stmt = $conn->prepare("SELECT name, email, status FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

echo json_encode([
    'status' => 'success',
    'valid' => true,
    'user' => [
        'user_id' => (int)$user_id,
        'name' => $user['name'],
        'email' => $user['email'],
        'status' => $user['status']
    ]
]);