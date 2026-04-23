<?php
require_once 'connection.php';
require_once 'config.php';

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

function debug_log($message) {
    $log_file = 'email_login_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
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

function getMessage($key, $language = 'en') {
    $messages = [
        'en' => [
            'invalid_request_method' => "Invalid request method. Only POST allowed.",
            'no_input_data' => "No input data received",
            'invalid_json' => "Invalid JSON data",
            'email_required' => "Email is required",
            'password_required' => "Password is required",
            'invalid_email_format' => "Invalid email format",
            'user_not_found' => "User not found",
            'google_login_required' => "This email is registered with Google. Please use Google login.",
            'account_suspended' => "Account is suspended. Contact support.",
            'account_inactive' => "Account is inactive. Contact support.",
            'invalid_password' => "Invalid password",
            'too_many_attempts' => "Too many login attempts. Please try again later.",
            'token_generation_failed' => "Failed to generate authentication token",
            'login_successful' => "Login successful",
            'database_error' => "Database error occurred",
            'server_error' => "Server error occurred"
        ],
        'bn' => [
            'invalid_request_method' => "অনুরোধ পদ্ধতি সঠিক নয়। শুধুমাত্র POST অনুরোধ অনুমোদিত।",
            'no_input_data' => "কোনো ডেটা প্রাপ্ত হয়নি",
            'invalid_json' => "JSON ডেটা সঠিক নয়",
            'email_required' => "ইমেইল প্রয়োজন",
            'password_required' => "পাসওয়ার্ড প্রয়োজন",
            'invalid_email_format' => "ইমেইল ফরমেট সঠিক নয়",
            'user_not_found' => "ব্যবহারকারী খুঁজে পাওয়া যায়নি",
            'google_login_required' => "এই ইমেইলটি Google দিয়ে নিবন্ধিত। দয়া করে Google লগইন ব্যবহার করুন।",
            'account_suspended' => "অ্যাকাউন্ট স্থগিত করা হয়েছে। সাপোর্টে যোগাযোগ করুন।",
            'account_inactive' => "অ্যাকাউন্ট নিষ্ক্রিয়। সাপোর্টে যোগাযোগ করুন।",
            'invalid_password' => "পাসওয়ার্ড সঠিক নয়",
            'too_many_attempts' => "বহুবার লগইন চেষ্টা করা হয়েছে। কিছুক্ষণ পর আবার চেষ্টা করুন।",
            'token_generation_failed' => "অনুমোদন টোকেন তৈরি করতে ব্যর্থ",
            'login_successful' => "লগইন সফল",
            'database_error' => "ডাটাবেস ত্রুটি ঘটেছে",
            'server_error' => "সার্ভার ত্রুটি ঘটেছে"
        ]
    ];
    
    return $messages[$language][$key] ?? $messages['en'][$key] ?? "Unknown error";
}

function checkRateLimit($email, $ip) {
    global $conn;
    
    $time_window = date('Y-m-d H:i:s', strtotime('-15 minutes'));
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as attempt_count 
        FROM login_attempts 
        WHERE (email = ? OR ip_address = ?) AND attempt_time > ? AND success = 0
    ");
    
    if ($stmt) {
        $stmt->bind_param("sss", $email, $ip, $time_window);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row['attempt_count'] < 5;
    }
    
    return true;
}

function logLoginAttempt($email, $ip, $success) {
    global $conn;
    
    $stmt = $conn->prepare("
        INSERT INTO login_attempts (email, ip_address, success, attempt_time) 
        VALUES (?, ?, ?, NOW())
    ");
    
    if ($stmt) {
        $stmt->bind_param("ssi", $email, $ip, $success);
        $stmt->execute();
        $stmt->close();
    }
}

function getUserProfileImage($userId) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT image_url FROM user_images 
        WHERE user_id = ? AND image_type = 'profile' AND is_primary = 1 
        ORDER BY created_at DESC LIMIT 1
    ");
    
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stmt->close();
            return $row['image_url'];
        }
        $stmt->close();
    }
    
    $stmt2 = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
    if ($stmt2) {
        $stmt2->bind_param("i", $userId);
        $stmt2->execute();
        $result = $stmt2->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stmt2->close();
            return $row['profile_image'];
        }
        $stmt2->close();
    }
    
    return "";
}

function updateUserLogin($user_id, $device_id, $device_model, $os_version, $app_version, $ip) {
    global $conn;
    
    $stmt = $conn->prepare("
        UPDATE users SET 
        last_login = NOW(), 
        device_id = ?, 
        ip_address = ?, 
        device_model = ?, 
        os_version = ?, 
        app_version = ?,
        updated_at = NOW()
        WHERE id = ?
    ");
    
    if ($stmt) {
        $stmt->bind_param("sssssi", $device_id, $ip, $device_model, $os_version, $app_version, $user_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    return false;
}

function generateAuthToken($user_id) {
    global $conn;
    
    $token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));

    $stmt = $conn->prepare("
        INSERT INTO user_tokens (user_id, token, expires_at, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    
    if ($stmt) {
        $stmt->bind_param("iss", $user_id, $token, $expires_at);
        if ($stmt->execute()) {
            debug_log("Auth token generated for user: $user_id");
            $stmt->close();
            return $token;
        }
        $stmt->close();
    }
    
    debug_log("Failed to generate auth token for user: $user_id");
    return false;
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
            $stmt->close();
            return $row['referral_code'];
        }
        $stmt->close();
    }
    
    return "";
}

// ========================
// MAIN REQUEST HANDLER
// ========================

debug_log("=== NEW EMAIL LOGIN REQUEST ===");
debug_log("Server Time: " . date('Y-m-d H:i:s'));
debug_log("Remote IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown'));

$client_language = getClientLanguage();
debug_log("Detected client language: " . $client_language);

// Check database connection
if ($conn->connect_error) {
    debug_log("Database connection failed: " . $conn->connect_error);
    http_response_code(500);
    echo json_encode([
        "status" => "error", 
        "message" => getMessage('database_error', 'en')
    ]);
    exit;
}

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

    $email = trim($data['email'] ?? '');
    $password = trim($data['password'] ?? '');
    $device_info = $data['device_info'] ?? [];

    $device_id = $device_info['device_id'] ?? '';
    $device_model = $device_info['device_model'] ?? '';
    $os_version = $device_info['os_version'] ?? '';
    $app_version = $device_info['app_version'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'];

    debug_log("Extracted data: Email: $email");

    // Check rate limiting
    if (!checkRateLimit($email, $ip)) {
        throw new Exception(getMessage('too_many_attempts', $client_language));
    }

    // Validate input
    if (empty($email)) {
        throw new Exception(getMessage('email_required', $client_language));
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception(getMessage('invalid_email_format', $client_language));
    }

    if (empty($password)) {
        throw new Exception(getMessage('password_required', $client_language));
    }

    // Check if user exists
    $stmt = $conn->prepare("SELECT id, name, email, password, status, google_login FROM users WHERE email = ?");
    if (!$stmt) {
        throw new Exception(getMessage('database_error', $client_language) . ": " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    if (!$stmt->execute()) {
        throw new Exception(getMessage('database_error', $client_language) . ": " . $stmt->error);
    }

    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        logLoginAttempt($email, $ip, 0);
        throw new Exception(getMessage('user_not_found', $client_language));
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    debug_log("User found - ID: {$user['id']}, Google Login: {$user['google_login']}, Status: {$user['status']}");

    // Check if user registered with Google
    if ($user['google_login'] == 1) {
        logLoginAttempt($email, $ip, 0);
        throw new Exception(getMessage('google_login_required', $client_language));
    }

    // Check account status
    if ($user['status'] === 'suspended') {
        logLoginAttempt($email, $ip, 0);
        throw new Exception(getMessage('account_suspended', $client_language));
    }
    
    if ($user['status'] === 'inactive') {
        logLoginAttempt($email, $ip, 0);
        throw new Exception(getMessage('account_inactive', $client_language));
    }

    // Verify password
    if (empty($user['password']) || !password_verify($password, $user['password'])) {
        logLoginAttempt($email, $ip, 0);
        throw new Exception(getMessage('invalid_password', $client_language));
    }

    debug_log("Password verified successfully for user: {$user['id']}");

    // Update last login and device info
    updateUserLogin($user['id'], $device_id, $device_model, $os_version, $app_version, $ip);

    // Generate authentication token
    $token = generateAuthToken($user['id']);

    if (!$token) {
        throw new Exception(getMessage('token_generation_failed', $client_language));
    }

    // Get user data
    $profile_image_url = getUserProfileImage($user['id']);
    $referral_code = getUserReferralCode($user['id']);

    // Log successful attempt
    logLoginAttempt($email, $ip, 1);

    $response = [
        "status" => "success",
        "message" => getMessage('login_successful', $client_language),
        "token" => $token,
        "user_id" => (int)$user['id'],
        "name" => $user['name'],
        "email" => $user['email'],
        "profile_image" => $profile_image_url ?: "",
        "referral_code" => $referral_code ?: "",
        "login_method" => "email"
    ];

    echo json_encode($response);
    debug_log("User login successful - User ID: {$user['id']}, Token generated");

} catch (Exception $e) {
    $error_message = $e->getMessage();
    debug_log("ERROR: " . $error_message);
    http_response_code(400);
    echo json_encode([
        "status" => "error", 
        "message" => $error_message
    ]);
}

debug_log("=== REQUEST COMPLETED ===\n");
?>
