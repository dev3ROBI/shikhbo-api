<?php
// Disable error output to prevent JSON corruption
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/config.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Verify credential
$input = json_decode(file_get_contents('php://input'), true);
$credential = $input['credential'] ?? '';

if (!$credential) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No credential provided.']);
    exit;
}

// Verify with Google
$tokenInfo = @file_get_contents("https://oauth2.googleapis.com/tokeninfo?id_token=" . urlencode($credential));
if (!$tokenInfo) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid Google token.']);
    exit;
}

$data = json_decode($tokenInfo, true);
if (!$data || empty($data['aud'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Token verification failed.']);
    exit;
}

$expectedAudiences = [
    '151985259285-nvemiiq9gg5lh7ap27vcrv25jv930ddm.apps.googleusercontent.com',
    '151985259285-9vp42do9jbkl0gv5rv25hhi3u74t7sp9.apps.googleusercontent.com'
];
if (!in_array($data['aud'], $expectedAudiences)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid audience.']);
    exit;
}

$googleId = $data['sub'];
$email = $data['email'];
$name = $data['name'] ?? explode('@', $email)[0];

// Helper: admin-level roles
function isAdminRole($role) {
    return in_array($role, ['Administrator', 'Moderator', 'Editor']);
}

// Get client info
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

// Connect to database
$mysqli = mysqli_init();
@$mysqli->real_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
    exit;
}
$mysqli->set_charset('utf8mb4');

// Find or create user
$stmt = $mysqli->prepare("SELECT id, name, email, role, status FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if ($user) {
    if ($user['status'] !== 'active') {
        $mysqli->close();
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Account is suspended.']);
        exit;
    }
    $userId = $user['id'];
    $stmt = $mysqli->prepare("UPDATE users SET google_login = 1, google_id = ?, ip_address = ?, device_model = ?, last_login = NOW(), updated_at = NOW() WHERE id = ?");
    $stmt->bind_param('sssi', $googleId, $ip, $userAgent, $userId);
    $stmt->execute();
    $stmt->close();
} else {
    $role = 'Member';
    $referralCode = 'WEB' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    $stmt = $mysqli->prepare("INSERT INTO users (name, email, google_login, google_id, role, status, referral_code, ip_address, device_model, created_at, updated_at, last_login) VALUES (?, ?, 1, ?, ?, 'active', ?, ?, ?, NOW(), NOW(), NOW())");
    $stmt->bind_param('sssssss', $name, $email, $googleId, $role, $referralCode, $ip, $userAgent);
    $stmt->execute();
    $userId = $stmt->insert_id;
    $stmt->close();
    $user = ['id' => $userId, 'name' => $name, 'email' => $email, 'role' => $role];
}

$mysqli->close();

// Set session
$_SESSION['user_id'] = $userId;
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_role'] = $user['role'];
$_SESSION['user_last_activity'] = time();

if (isAdminRole($user['role'])) {
    $_SESSION['admin_id'] = $userId;
    $_SESSION['admin_name'] = $user['name'];
    $_SESSION['admin_email'] = $user['email'];
    $_SESSION['admin_role'] = $user['role'];
    $_SESSION['admin_last_activity'] = time();
}

$redirect = '/index.php';
if (isAdminRole($user['role'])) {
    $redirect = '/index.php?page=dashboard';
}

echo json_encode([
    'status' => 'success',
    'message' => 'Login successful.',
    'redirect' => $redirect
]);
