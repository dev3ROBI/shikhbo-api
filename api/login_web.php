<?php
/**
 * LOGIN FOR WEB/ADMIN PANEL
 * No security params required - uses session
 * 
 * Usage: POST /api/login_web.php
 * Body: {"email":"...", "password":"..."}
 */
require_once __DIR__ . '/../api/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

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

if (empty($email) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Email and password required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email format']);
    exit;
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
$conn->set_charset('utf8mb4');

// Check login attempts for rate limiting
if (!checkRateLimit($conn, $email)) {
    echo json_encode(['status' => 'error', 'message' => 'Too many login attempts. Please try again later.']);
    exit;
}

// Get user
$stmt = $conn->prepare("SELECT id, name, email, password, status, is_active FROM users WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    logLoginAttempt($conn, $email, 0);
    echo json_encode(['status' => 'error', 'message' => 'User not found']);
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

if ($user['status'] === 'suspended') {
    logLoginAttempt($conn, $email, 0);
    echo json_encode(['status' => 'error', 'message' => 'Account is suspended']);
    exit;
}

if ($user['status'] !== 'active' || $user['is_active'] != 1) {
    logLoginAttempt($conn, $email, 0);
    echo json_encode(['status' => 'error', 'message' => 'Account is not active']);
    exit;
}

if (empty($user['password']) || !password_verify($password, $user['password'])) {
    logLoginAttempt($conn, $email, 0);
    echo json_encode(['status' => 'error', 'message' => 'Invalid password']);
    exit;
}

// Generate token for web
$token = bin2hex(random_bytes(32));
$expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

$stmt = $conn->prepare("INSERT INTO user_tokens (user_id, token, expires_at, created_at) VALUES (?, ?, ?, NOW())");
$stmt->bind_param('iss', $user['id'], $token, $expiresAt);
$stmt->execute();
$stmt->close();

// Update last login
$updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
$updateStmt->bind_param('i', $user['id']);
$updateStmt->execute();
$updateStmt->close();

logLoginAttempt($conn, $email, 1);

echo json_encode([
    'status' => 'success',
    'message' => 'Login successful',
    'token' => $token,
    'user' => [
        'user_id' => (int)$user['id'],
        'name' => $user['name'],
        'email' => $user['email']
    ],
    'source' => 'web'
]);