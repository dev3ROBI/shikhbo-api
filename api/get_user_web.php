<?php
/**
 * GET USER PROFILE FOR WEB/ADMIN PANEL
 * No security required - for web and admin panel
 * 
 * Usage: POST /api/get_user_web.php
 * Body: {"user_id":1}
 */
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

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$userId = intval($data['user_id'] ?? 0);

if (!$userId) {
    echo json_encode(['status' => 'error', 'message' => 'user_id required']);
    exit();
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
$conn->set_charset('utf8mb4');

$stmt = $conn->prepare("SELECT id, name, email, profile_image, referral_code, login_method, language, tagline, streak, member_since, is_premium, status, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
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
        'language' => $user['language'] ?? 'en',
        'tagline' => $user['tagline'] ?? '',
        'streak' => (int)$user['streak'],
        'member_since' => $user['member_since'] ?? date('Y-m-d', strtotime($user['created_at'])),
        'is_premium' => (bool)$user['is_premium']
    ],
    'source' => 'web'
]);