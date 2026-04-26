<?php
/**
 * LOGOUT FOR APP
 * Requires: uid, season, u_state
 * 
 * Usage: POST /api/logout_app.php
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

$input = json_decode(file_get_contents('php://input'), true);

$uid = $input['uid'] ?? null;
$season = $input['season'] ?? null;
$u_state = $input['u_state'] ?? null;

$security = requireAppSecurity($uid, $season, $u_state);

$token = trim($input['token'] ?? '');

if (empty($token)) {
    echo json_encode(["status" => "error", "message" => "Token is required"]);
    exit;
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
$conn->set_charset('utf8mb4');

$stmt = $conn->prepare("DELETE FROM user_tokens WHERE token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$stmt->close();

echo json_encode([
    "status" => "success",
    "message" => "Logged out successfully"
]);