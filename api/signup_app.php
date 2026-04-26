<?php
/**
 * SIGNUP FOR APP
 * Returns user data without security params (since user is not logged in yet)
 * 
 * Usage: POST /api/signup_app.php
 * Body: {"name":"...", "email":"...", "password":"...", "device_info":{...}}
 */
require_once __DIR__ . '/../api/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept-Language, X-App-Language');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

function getClientLanguage() {
    $supported = ['en', 'bn'];
    $default = 'en';
    if (isset($_SERVER['HTTP_X_APP_LANGUAGE'])) {
        $lang = substr($_SERVER['HTTP_X_APP_LANGUAGE'], 0, 2);
        if (in_array($lang, $supported)) return $lang;
    }
    return $default;
}

function getMessage($key, $lang = 'en') {
    $msg = [
        'en' => [
            'registration_successful' => "Registration successful! You can now login.",
            'email_already_registered' => "Email already registered",
            'invalid_referral_code' => "Invalid referral code"
        ],
        'bn' => [
            'registration_successful' => "নিবন্ধন সফল! আপনি এখন লগইন করতে পারেন।",
            'email_already_registered' => "ইমেইল ইতিমধ্যে নিবন্ধিত",
            'invalid_referral_code' => "রেফারেল কোড সঠিক নয়"
        ]
    ];
    return $msg[$lang][$key] ?? $msg['en'][$key];
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
$conn->set_charset('utf8mb4');
$lang = getClientLanguage();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method");
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        throw new Exception("Invalid JSON data");
    }

    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = trim($input['password'] ?? '');
    $referral_code = trim($input['referral_code'] ?? '');
    $device_info = $input['device_info'] ?? [];

    if (empty($name) || empty($email) || empty($password)) {
        throw new Exception("Name, email and password are required");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }

    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        throw new Exception(getMessage('email_already_registered', $lang));
    }
    $checkStmt->close();

    $referred_by = null;
    if (!empty($referral_code)) {
        $refStmt = $conn->prepare("SELECT id FROM users WHERE referral_code = ? AND status = 'active'");
        $refStmt->bind_param("s", $referral_code);
        $refStmt->execute();
        $refResult = $refStmt->get_result();
        if ($refRow = $refResult->fetch_assoc()) {
            $referred_by = $refRow['id'];
        }
        $refStmt->close();
    }

    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    $referral = 'REF' . substr(md5(uniqid()), 0, 8);
    $device_id = $device_info['device_id'] ?? '';
    $device_model = $device_info['device_model'] ?? '';
    $os_version = $device_info['os_version'] ?? '';
    $app_version = $device_info['app_version'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    $stmt = $conn->prepare("INSERT INTO users (name, email, password, google_login, status, referral_code, referred_by, device_id, ip_address, device_model, os_version, app_version, created_at) VALUES (?, ?, ?, 0, 'active', ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssssissssss", $name, $email, $hashed_password, $referral, $referred_by, $device_id, $ip, $device_model, $os_version, $app_version);
    
    if (!$stmt->execute()) {
        throw new Exception("Registration failed");
    }
    
    $user_id = $stmt->insert_id;
    $stmt->close();

    echo json_encode([
        "status" => "success",
        "message" => getMessage('registration_successful', $lang),
        "user_id" => $user_id,
        "email" => $email,
        "name" => $name,
        "referral_code" => $referral
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}