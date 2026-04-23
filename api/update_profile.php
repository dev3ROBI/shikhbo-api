<?php
require_once 'connection.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$userId = $input['user_id'] ?? $_POST['user_id'] ?? null;
$token = trim($input['token'] ?? $_POST['token'] ?? '');
$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$profileImage = trim($input['profile_image'] ?? '');

if (empty($userId) || empty($token) || empty($name) || empty($email)) {
    echo json_encode(["status" => "error", "message" => "User ID, token, name and email are required"], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["status" => "error", "message" => "Invalid email format"], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!preg_match('/^[\p{L}\s.\'-]+$/u', $name) || mb_strlen($name) < 2 || mb_strlen($name) > 50) {
    echo json_encode(["status" => "error", "message" => "Name must be 2 to 50 characters and contain only letters"], JSON_UNESCAPED_UNICODE);
    exit;
}

$tokenStmt = $conn->prepare("
    SELECT user_id
    FROM user_tokens
    WHERE token = ?
    LIMIT 1
");
$tokenStmt->bind_param("s", $token);
$tokenStmt->execute();
$tokenResult = $tokenStmt->get_result();
$tokenRow = $tokenResult->fetch_assoc();

if (!$tokenRow || (int) $tokenRow['user_id'] !== (int) $userId) {
    echo json_encode(["status" => "error", "message" => "Unauthorized request"], JSON_UNESCAPED_UNICODE);
    exit;
}

$checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$checkStmt->bind_param("si", $email, $userId);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
if ($checkResult->num_rows > 0) {
    echo json_encode(["status" => "error", "message" => "Email already registered"], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, profile_image = ?, updated_at = NOW() WHERE id = ?");
$stmt->bind_param("sssi", $name, $email, $profileImage, $userId);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Profile updated successfully"
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Could not update profile"
    ], JSON_UNESCAPED_UNICODE);
}
