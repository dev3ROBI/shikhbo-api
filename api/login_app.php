<?php
/**
 * LOGIN FOR APP
 * Returns user data with security params for subsequent API calls
 * 
 * Usage: POST /api/login_app.php
 * Body: {"email":"...", "password":"...", "device_info":{...}}
 */
require_once __DIR__ . '/../api/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-App-Language');

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

if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data']);
    exit;
}

$email = trim($data['email'] ?? '');
$password = trim($data['password'] ?? '');
$deviceInfo = $data['device_info'] ?? [];

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Email and password required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid email format']);
    exit;
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
$conn->set_charset('utf8mb4');

// Check rate limit
$ip = $_SERVER['REMOTE_ADDR'];
$timeWindow = date('Y-m-d H:i:s', strtotime('-15 minutes'));

$stmt = $conn->prepare("SELECT COUNT(*) as attempt_count FROM login_attempts WHERE (email = ? OR ip_address = ?) AND attempt_time > ? AND success = 0");
$stmt->bind_param('sss', $email, $ip, $timeWindow);
$stmt->execute();
$attemptCount = $stmt->get_result()->fetch_assoc()['attempt_count'];
$stmt->close();

if ($attemptCount >= 5) {
    echo json_encode(['status' => 'error', 'message' => 'Too many login attempts. Please try again later.']);
    exit;
}

// Get user
$stmt = $conn->prepare("SELECT id, name, email, password, status, is_active, profile_image FROM users WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    echo json_encode(['status' => 'error', 'message' => 'User not found']);
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

if ($user['status'] === 'suspended') {
    echo json_encode(['status' => 'error', 'message' => 'Account is suspended']);
    exit;
}

if ($user['status'] !== 'active' || $user['is_active'] != 1) {
    echo json_encode(['status' => 'error', 'message' => 'Account is not active']);
    exit;
}

if (empty($user['password']) || !password_verify($password, $user['password'])) {
    $stmt = $conn->prepare("INSERT INTO login_attempts (email, ip_address, success, attempt_time) VALUES (?, ?, 0, NOW())");
    $stmt->bind_param('ss', $email, $ip);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['status' => 'error', 'message' => 'Invalid password']);
    exit;
}

// Generate token
$token = bin2hex(random_bytes(32));
$expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

$stmt = $conn->prepare("INSERT INTO user_tokens (user_id, token, expires_at, created_at) VALUES (?, ?, ?, NOW())");
$stmt->bind_param('iss', $user['id'], $token, $expiresAt);
$stmt->execute();
$stmt->close();

// Generate season (expires in 3 hours)
$season = date('Y-m-d H:i:s', strtotime('+3 hours'));

// Get actual user active status from database
$statusStmt = $conn->prepare("SELECT status, is_active FROM users WHERE id = ?");
$statusStmt->bind_param('i', $user['id']);
$statusStmt->execute();
$statusResult = $statusStmt->get_result();
$statusRow = $statusResult->fetch_assoc();
$statusStmt->close();
$userActive = ($statusRow && $statusRow['status'] === 'active' && $statusRow['is_active'] == 1) ? 1 : 0;

// Update last login
$deviceId = $deviceInfo['device_id'] ?? '';
$deviceModel = $deviceInfo['device_model'] ?? '';
$osVersion = $deviceInfo['os_version'] ?? '';
$appVersion = $deviceInfo['app_version'] ?? '';

$updateStmt = $conn->prepare("UPDATE users SET last_login = NOW(), device_id = ?, device_model = ?, os_version = ?, app_version = ? WHERE id = ?");
$updateStmt->bind_param('ssssi', $deviceId, $deviceModel, $osVersion, $appVersion, $user['id']);
$updateStmt->execute();
$updateStmt->close();

// Log successful attempt
$stmt = $conn->prepare("INSERT INTO login_attempts (email, ip_address, success, attempt_time) VALUES (?, ?, 1, NOW())");
$stmt->bind_param('ss', $email, $ip);
$stmt->execute();
$stmt->close();

// Get referral code
$referralCode = '';
$refStmt = $conn->prepare("SELECT referral_code FROM users WHERE id = ?");
$refStmt->bind_param('i', $user['id']);
$refStmt->execute();
$refResult = $refStmt->get_result();
if ($refRow = $refResult->fetch_assoc()) {
    $referralCode = $refRow['referral_code'] ?? '';
}
$refStmt->close();

echo json_encode([
    'status' => 'success',
    'message' => 'Login successful',
    'token' => $token,
    'user_id' => (int)$user['id'],
    'name' => $user['name'],
    'email' => $user['email'],
    'profile_image' => $user['profile_image'] ?? '',
    'referral_code' => $referralCode ?: '',
    'login_method' => 'email',
    'user_data' => [
        'user_id' => (int)$user['id'],
        'user_season' => $season,
        'user_active' => $userActive
    ],
    'source' => 'app'
]);