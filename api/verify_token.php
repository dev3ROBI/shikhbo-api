<?php
/**
 * Verify Authentication Token
 * 
 * POST /api/verify_token.php
 * Header: Authorization: Bearer <token>
 * Body: {"token": "..."}
 * 
 * Security: Checks logged user + active user + season time + rate limit
 */
require_once 'connection.php';
require_once 'config.php';
require_once __DIR__ . '/../includes/app_security.php';

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

$clientType = getApiClientType();
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$token = $data['token'] ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
$token = str_replace('Bearer ', '', $token);

if (empty($token)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Token required']);
    exit();
}

// Use app_security module for verification
$securityCheck = verifyUserSecurity($conn, $token, $clientType);

if (!$securityCheck['success']) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error', 
        'message' => $securityCheck['message'],
        'code' => $securityCheck['code'] ?? 'UNAUTHORIZED'
    ]);
    exit();
}

// Get user details
$user_id = $securityCheck['user_id'];
$stmt = $conn->prepare("SELECT name, email, status, is_active FROM users WHERE id = ?");
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
        'status' => $user['status'],
        'is_active' => (bool)$user['is_active']
    ],
    'rate_info' => [
        'remaining' => $securityCheck['remaining'],
        'season_expires' => $securityCheck['season_expires']
    ]
]);