<?php
/**
 * UPDATE PROFILE FOR APP
 * Requires: uid, season, u_state
 * 
 * Usage: POST /api/update_profile_app.php
 * Body: {"name":"...", "email":"...", "profile_image":"...", "uid":1, "season":"__", "u_state":"1"}
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

$input = json_decode(file_get_contents('php://input'), true);

$uid = $input['uid'] ?? null;
$season = $input['season'] ?? null;
$u_state = $input['u_state'] ?? null;

$security = requireAppSecurity($uid, $season, $u_state);

$token = trim($input['token'] ?? '');
$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$profileImage = trim($input['profile_image'] ?? '');

if (empty($token) || empty($name) || empty($email)) {
    echo json_encode(["status" => "error", "message" => "Token, name and email are required"], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["status" => "error", "message" => "Invalid email format"], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!preg_match('/^[\p{L}\s.\'-]+$/u', $name) || mb_strlen($name) < 2 || mb_strlen($name) > 50) {
    echo json_encode(["status" => "error", "message" => "Name must be 2 to 50 characters"], JSON_UNESCAPED_UNICODE);
    exit;
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
$conn->set_charset('utf8mb4');

$tokenStmt = $conn->prepare("SELECT user_id FROM user_tokens WHERE token = ? LIMIT 1");
$tokenStmt->bind_param("s", $token);
$tokenStmt->execute();
$tokenResult = $tokenStmt->get_result();
$tokenRow = $tokenResult->fetch_assoc();

if (!$tokenRow || (int) $tokenRow['user_id'] !== (int) $uid) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized request"], JSON_UNESCAPED_UNICODE);
    exit;
}

$checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$checkStmt->bind_param("si", $email, $uid);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
if ($checkResult->num_rows > 0) {
    echo json_encode(["status" => "error", "message" => "Email already registered"], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, profile_image = ?, updated_at = NOW() WHERE id = ?");
$stmt->bind_param("sssi", $name, $email, $profileImage, $uid);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Profile updated successfully",
        "user_info" => [
            'uid' => (int)$uid,
            'requests_remaining' => $security['remaining']
        ]
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Could not update profile"
    ], JSON_UNESCAPED_UNICODE);
}