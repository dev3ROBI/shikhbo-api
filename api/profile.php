<?php
/**
 * Get User Profile (Alias for get_user.php for backward compatibility)
 * 
 * POST /api/profile.php
 * Header: Authorization: Bearer <token>
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
    echo json_encode(['status' => 'error', 'message' => 'Invalid or expired token']);
    exit();
}

$stmt = $conn->prepare("SELECT id, name, email, profile_image, referral_code, login_method, language, tagline, streak, member_since, is_premium, status, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
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
        'member_since' => $user['member_since'] ?? date('Y-m-d', strtotime($user['created_at']))
    ]
]);