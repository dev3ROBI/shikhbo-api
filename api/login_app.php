<?php
/**
 * App Login API - For Android App
 * Requires: user_id, user_season, user_active
 * 
 * POST /api/login_app.php
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

function getMessage($key, $language = 'en') {
    $messages = [
        'en' => ['login_successful' => "Login successful"],
        'bn' => ['login_successful' => "লগইন সফল"]
    ];
    return $messages[$language][$key] ?? $messages['en'][$key];
}

function getClientLanguage() {
    $supported_languages = ['en', 'bn'];
    $default_language = 'en';
    if (isset($_SERVER['HTTP_X_APP_LANGUAGE'])) {
        $app_lang = substr($_SERVER['HTTP_X_APP_LANGUAGE'], 0, 2);
        if (in_array($app_lang, $supported_languages)) {
            return $app_lang;
        }
    }
    return $default_language;
}

$client_language = getClientLanguage();
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data']);
    exit();
}

$email = trim($data['email'] ?? '');
$password = trim($data['password'] ?? '');
$user_id = $data['user_id'] ?? null;
$user_season = $data['user_season'] ?? null;
$user_active = $data['user_active'] ?? null;
$device_info = $data['device_info'] ?? [];

$device_id = $device_info['device_id'] ?? '';
$device_model = $device_info['device_model'] ?? '';
$os_version = $device_info['os_version'] ?? '';
$app_version = $device_info['app_version'] ?? '';
$ip = $_SERVER['REMOTE_ADDR'];

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Email and password required']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid email format']);
    exit();
}

$stmt = $conn->prepare("SELECT id, name, email, password, status FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'User not found']);
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

if ($user['status'] === 'suspended') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Account is suspended']);
    exit();
}

if (empty($user['password']) || !password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid password']);
    exit();
}

$token = bin2hex(random_bytes(32));
$expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));

$stmt = $conn->prepare("INSERT INTO user_tokens (user_id, token, expires_at, created_at) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("iss", $user['id'], $token, $expires_at);
$stmt->execute();
$stmt->close();

$updateStmt = $conn->prepare("UPDATE users SET last_login = NOW(), device_id = ?, ip_address = ?, device_model = ?, os_version = ?, app_version = ? WHERE id = ?");
$updateStmt->bind_param("sssssi", $device_id, $ip, $device_model, $os_version, $app_version, $user['id']);
$updateStmt->execute();
$updateStmt->close();

$profile_image = $user['profile_image'] ?? '';
$referral_code = '';

$refStmt = $conn->prepare("SELECT referral_code FROM users WHERE id = ?");
$refStmt->bind_param("i", $user['id']);
$refStmt->execute();
$refResult = $refStmt->get_result();
if ($refRow = $refResult->fetch_assoc()) {
    $referral_code = $refRow['referral_code'] ?? '';
}
$refStmt->close();

$season = date('Y-m-d H:i:s', strtotime('+3 hours'));

$response = [
    "status" => "success",
    "message" => getMessage('login_successful', $client_language),
    "token" => $token,
    "user_id" => (int)$user['id'],
    "name" => $user['name'],
    "email" => $user['email'],
    "profile_image" => $profile_image ?: "",
    "referral_code" => $referral_code ?: "",
    "login_method" => "email",
    "user_data" => [
        "user_id" => (int)$user['id'],
        "user_season" => $season,
        "user_active" => true
    ]
];

echo json_encode($response);