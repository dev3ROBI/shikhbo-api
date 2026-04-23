<?php
require_once 'connection.php';
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept-Language, X-App-Language');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Debug log function
function debug_log($message) {
    $log_file = 'email_signup_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

// Language detection function
function getClientLanguage() {
    $supported_languages = ['en', 'bn'];
    $default_language = 'en';
    
    // Check custom header first (from app)
    if (isset($_SERVER['HTTP_X_APP_LANGUAGE'])) {
        $app_lang = substr($_SERVER['HTTP_X_APP_LANGUAGE'], 0, 2);
        if (in_array($app_lang, $supported_languages)) {
            return $app_lang;
        }
    }
    
    // Check Accept-Language header
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $client_languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        foreach ($client_languages as $lang) {
            $lang = substr(trim($lang), 0, 2);
            if (in_array($lang, $supported_languages)) {
                return $lang;
            }
        }
    }
    
    return $default_language;
}

// Multi-language messages
function getMessage($key, $language = 'en') {
    $messages = [
        'en' => [
            'invalid_request_method' => "Invalid request method. Only POST allowed.",
            'no_input_data' => "No input data received",
            'invalid_json' => "Invalid JSON data",
            'name_required' => "Name is required",
            'email_required' => "Email is required",
            'password_required' => "Password is required",
            'name_length_invalid' => "Name must be between 2 and 50 characters",
            'name_invalid_chars' => "Name can only contain letters and spaces",
            'invalid_email_format' => "Invalid email format",
            'password_too_short' => "Password must be at least 8 characters long",
            'password_no_uppercase' => "Password must contain at least one uppercase letter",
            'password_no_lowercase' => "Password must contain at least one lowercase letter",
            'password_no_number' => "Password must contain at least one number",
            'password_no_special_char' => "Password must contain at least one special character",
            'email_already_registered' => "Email already registered",
            'invalid_referral_code' => "Invalid referral code",
            'registration_successful' => "Registration successful! You can now login.",
            'registration_failed' => "Registration failed",
            'database_error' => "Database error occurred",
            'server_error' => "Server error occurred"
        ],
        'bn' => [
            'invalid_request_method' => "অনুরোধ পদ্ধতি সঠিক নয়। শুধুমাত্র POST অনুরোধ অনুমোদিত।",
            'no_input_data' => "কোনো ডেটা প্রাপ্ত হয়নি",
            'invalid_json' => "JSON ডেটা সঠিক নয়",
            'name_required' => "নাম প্রয়োজন",
            'email_required' => "ইমেইল প্রয়োজন",
            'password_required' => "পাসওয়ার্ড প্রয়োজন",
            'name_length_invalid' => "নাম ২ থেকে ৫০ অক্ষরের মধ্যে হতে হবে",
            'name_invalid_chars' => "নামে শুধুমাত্র অক্ষর এবং স্পেস থাকতে পারে",
            'invalid_email_format' => "ইমেইল ফরমেট সঠিক নয়",
            'password_too_short' => "পাসওয়ার্ড কমপক্ষে ৮ অক্ষরের হতে হবে",
            'password_no_uppercase' => "পাসওয়ার্ডে কমপক্ষে একটি বড় হাতের অক্ষর থাকতে হবে",
            'password_no_lowercase' => "পাসওয়ার্ডে কমপক্ষে একটি ছোট হাতের অক্ষর থাকতে হবে",
            'password_no_number' => "পাসওয়ার্ডে কমপক্ষে একটি সংখ্যা থাকতে হবে",
            'password_no_special_char' => "পাসওয়ার্ডে কমপক্ষে একটি বিশেষ অক্ষর থাকতে হবে",
            'email_already_registered' => "ইমেইল ইতিমধ্যে নিবন্ধিত",
            'invalid_referral_code' => "রেফারেল কোড সঠিক নয়",
            'registration_successful' => "নিবন্ধন সফল! আপনি এখন লগইন করতে পারেন।",
            'registration_failed' => "নিবন্ধন ব্যর্থ হয়েছে",
            'database_error' => "ডাটাবেস ত্রুটি ঘটেছে",
            'server_error' => "সার্ভার ত্রুটি ঘটেছে"
        ]
    ];
    
    return $messages[$language][$key] ?? $messages['en'][$key] ?? "Unknown error";
}

// Referral system functions
function validateReferralCode($referral_code) {
    global $conn;
    
    if (empty($referral_code)) {
        return null;
    }
    
    $stmt = $conn->prepare("SELECT id, name, email FROM users WHERE referral_code = ? AND status = 'active'");
    if (!$stmt) {
        return null;
    }
    
    $stmt->bind_param("s", $referral_code);
    if (!$stmt->execute()) {
        return null;
    }
    
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

function logReferral($referrer_id, $new_user_id, $referral_code_used) {
    global $conn;
    
    $stmt = $conn->prepare("
        INSERT INTO referral_logs (referrer_id, new_user_id, referral_code_used, status, created_at) 
        VALUES (?, ?, ?, 'completed', NOW())
    ");
    
    if (!$stmt) {
        debug_log("Failed to prepare referral log statement: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("iis", $referrer_id, $new_user_id, $referral_code_used);
    
    if (!$stmt->execute()) {
        debug_log("Failed to execute referral log: " . $stmt->error);
        return false;
    }
    
    $referral_log_id = $stmt->insert_id;
    
    // Add referral reward
    addReferralReward($referrer_id, $referral_log_id, 5.00);
    
    return $referral_log_id;
}

function addReferralReward($user_id, $referral_log_id, $amount) {
    global $conn;
    
    $stmt = $conn->prepare("
        INSERT INTO referral_rewards (user_id, referral_log_id, reward_type, amount, status, created_at) 
        VALUES (?, ?, 'signup_bonus', ?, 'pending', NOW())
    ");
    
    if ($stmt) {
        $stmt->bind_param("iid", $user_id, $referral_log_id, $amount);
        return $stmt->execute();
    }
    
    return false;
}

function generateReferralCode() {
    global $conn;
    
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    
    do {
        $code = '';
        for ($i = 0; $i < 8; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }
        
        // Check if code already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE referral_code = ?");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $stmt->store_result();
    } while ($stmt->num_rows > 0);
    
    return $code;
}

function createUser($name, $email, $password, $device_id, $device_model, $os_version, $app_version, $ip, $referred_by = null) {
    global $conn;
    
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    $referral_code = generateReferralCode();

    $stmt = $conn->prepare("
        INSERT INTO users 
        (name, email, password, google_login, status, referral_code, referred_by, device_id, ip_address, device_model, os_version, app_version, created_at) 
        VALUES (?, ?, ?, 0, 'active', ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param(
        "ssssisssss",
        $name, $email, $hashed_password, $referral_code, $referred_by, $device_id, $ip, $device_model, $os_version, $app_version
    );

    if ($stmt->execute()) {
        debug_log("New user created with ID: " . $stmt->insert_id);
        return $stmt->insert_id;
    } else {
        debug_log("MySQL Error: " . $stmt->error);
        throw new Exception("Database execute failed: " . $stmt->error);
    }
}

function getUserReferralCode($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT referral_code FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['referral_code'];
        }
    }
    
    return null;
}

function checkEmailExists($email) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    if (!$stmt) {
        return false;
    }
    
    $stmt->bind_param("s", $email);
    if (!$stmt->execute()) {
        return false;
    }
    
    $stmt->store_result();
    return $stmt->num_rows > 0;
}

// Input validation functions
function validateName($name, $language) {
    if (empty($name)) {
        return getMessage('name_required', $language);
    }
    
    if (strlen($name) < 2 || strlen($name) > 50) {
        return getMessage('name_length_invalid', $language);
    }
    
    if (!preg_match('/^[\p{L}\s.\'-]+$/u', $name)) {
        return getMessage('name_invalid_chars', $language);
    }
    
    return null;
}

function validateEmail($email, $language) {
    if (empty($email)) {
        return getMessage('email_required', $language);
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return getMessage('invalid_email_format', $language);
    }
    
    return null;
}

function validatePassword($password, $language) {
    if (empty($password)) {
        return getMessage('password_required', $language);
    }
    
    if (strlen($password) < 8) {
        return getMessage('password_too_short', $language);
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        return getMessage('password_no_uppercase', $language);
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        return getMessage('password_no_lowercase', $language);
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        return getMessage('password_no_number', $language);
    }
    
    if (!preg_match('/[!@#$%^&*()\-_=+{};:,<.>]/', $password)) {
        return getMessage('password_no_special_char', $language);
    }
    
    return null;
}

// ========================
// MAIN REQUEST HANDLER
// ========================

debug_log("=== NEW EMAIL SIGNUP REQUEST ===");
debug_log("Server Time: " . date('Y-m-d H:i:s'));
debug_log("Remote IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown'));

// Detect client language
$client_language = getClientLanguage();
debug_log("Detected client language: " . $client_language);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception(getMessage('invalid_request_method', $client_language));
    }

    $input = file_get_contents('php://input');
    debug_log("Raw input length: " . strlen($input));

    if (empty($input)) {
        throw new Exception(getMessage('no_input_data', $client_language));
    }

    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception(getMessage('invalid_json', $client_language) . ": " . json_last_error_msg());
    }

    debug_log("Parsed JSON data successfully");

    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = trim($data['password'] ?? '');
    $referral_code = trim($data['referral_code'] ?? '');
    $device_info = $data['device_info'] ?? [];

    $device_id = $device_info['device_id'] ?? '';
    $device_model = $device_info['device_model'] ?? '';
    $os_version = $device_info['os_version'] ?? '';
    $app_version = $device_info['app_version'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'];

    debug_log("Extracted data: Name: $name, Email: $email, Referral Code: $referral_code");

    // Validate input fields
    $nameError = validateName($name, $client_language);
    if ($nameError) {
        throw new Exception($nameError);
    }

    $emailError = validateEmail($email, $client_language);
    if ($emailError) {
        throw new Exception($emailError);
    }

    $passwordError = validatePassword($password, $client_language);
    if ($passwordError) {
        throw new Exception($passwordError);
    }

    // Check if email already exists
    if (checkEmailExists($email)) {
        throw new Exception(getMessage('email_already_registered', $client_language));
    }

    // Validate referral code if provided
    $referrer_info = null;
    $referred_by = null;
    
    if (!empty($referral_code)) {
        $referrer_info = validateReferralCode($referral_code);
        if (!$referrer_info) {
            throw new Exception(getMessage('invalid_referral_code', $client_language));
        }
        $referred_by = $referrer_info['id'];
        debug_log("Valid referral code provided by: " . $referrer_info['name']);
    }

    // Create new user
    $user_id = createUser($name, $email, $password, $device_id, $device_model, $os_version, $app_version, $ip, $referred_by);

    if (!$user_id) {
        throw new Exception(getMessage('registration_failed', $client_language));
    }

    // Process referral if applicable
    $referral_message = null;
    if ($referred_by && $referrer_info) {
        $referral_log_id = logReferral($referred_by, $user_id, $referral_code);
        if ($referral_log_id) {
            $referral_message = "Referral applied successfully";
            debug_log("Referral logged successfully. Log ID: " . $referral_log_id);
        }
    }

    // Get user's referral code for response
    $user_referral_code = getUserReferralCode($user_id);

    $response = [
        "status" => "success",
        "message" => getMessage('registration_successful', $client_language),
        "user_id" => $user_id,
        "email" => $email,
        "name" => $name,
        "referral_code" => $user_referral_code,
        "language" => $client_language
    ];
    
    if ($referral_message) {
        $response["referral_message"] = $client_language === 'bn'
                ? "রেফারেল সফলভাবে প্রয়োগ হয়েছে"
                : $referral_message;
    }
    
    echo json_encode($response);
    debug_log("User registration successful - User ID: $user_id");

} catch (Exception $e) {
    $error_message = $e->getMessage();
    debug_log("ERROR: " . $error_message);
    http_response_code(400);
    echo json_encode([
        "status" => "error", 
        "message" => $error_message,
        "language" => $client_language
    ]);
}

debug_log("=== REQUEST COMPLETED ===\n");
?>
