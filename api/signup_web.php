<?php
/**
 * SIGNUP FOR WEB/ADMIN PANEL
 * No security params required (user not logged in yet)
 * 
 * Usage: POST /api/signup_web.php
 * Body: {"name":"...", "email":"...", "password":"..."}
 */
require_once __DIR__ . '/../api/config.php';

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

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
$conn->set_charset('utf8mb4');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        throw new Exception('Invalid JSON data');
    }

    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = trim($input['password'] ?? '');

    if (empty($name) || empty($email) || empty($password)) {
        throw new Exception('Name, email and password are required');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        throw new Exception('Email already registered');
    }
    $checkStmt->close();

    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    $referral = 'REF' . substr(md5(uniqid()), 0, 8);
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    $stmt = $conn->prepare("INSERT INTO users (name, email, password, google_login, status, referral_code, device_id, ip_address, created_at) VALUES (?, ?, ?, 0, 'active', ?, '', ?, NOW())");
    $stmt->bind_param("sssss", $name, $email, $hashed_password, $referral, $ip);
    
    if (!$stmt->execute()) {
        throw new Exception('Registration failed');
    }
    
    $user_id = $stmt->insert_id;
    $stmt->close();

    echo json_encode([
        'status' => 'success',
        'message' => 'Registration successful',
        'user_id' => $user_id,
        'email' => $email,
        'name' => $name,
        'referral_code' => $referral,
        'source' => 'web'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}