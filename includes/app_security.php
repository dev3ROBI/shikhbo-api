<?php
/**
 * Shikhbo API - App Security Module
 * Rate Limiting, Session Control, Client Detection
 */

require_once __DIR__ . '/../api/config.php';

// =======================
// RATE LIMITING CONFIG
// =======================
define('API_RATE_LIMIT', 100);        // Max requests per window
define('API_RATE_WINDOW', 10800);      // 3 hours in seconds
define('API_SEASON_TIME', 3);         // Hours the API call remains valid

// =======================
// DETECT API CLIENT
// =======================
function getApiClientType() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    if (stripos($userAgent, 'Shikhbo-App') !== false) {
        return 'app';
    } elseif (stripos($userAgent, 'Mozilla') !== false && isset($_SERVER['HTTP_ACCEPT'])) {
        return 'web';
    }
    
    return 'unknown';
}

// =======================
// RATE LIMIT CHECK
// =======================
function checkApiRateLimit($mysqli, $userId, $clientType = 'app') {
    if (!$userId) {
        return ['allowed' => false, 'message' => 'User identification required'];
    }
    
    $table = $clientType === 'app' ? 'app_api_usage' : 'web_api_usage';
    $cutoff = date('Y-m-d H:i:s', time() - API_RATE_WINDOW);
    
    // Check if table exists, create if not
    $mysqli->query("CREATE TABLE IF NOT EXISTS `$table` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(50) NOT NULL,
        request_count INT DEFAULT 0,
        last_request TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        season_expires TIMESTAMP DEFAULT NULL,
        INDEX idx_user (user_id),
        INDEX idx_last (last_request)
    )");
    
    // Get or create user record
    $stmt = $mysqli->prepare("SELECT * FROM `$table` WHERE user_id = ? LIMIT 1");
    $stmt->bind_param('s', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $record = $result->fetch_assoc();
    $stmt->close();
    
    if (!$record) {
        $seasonExpires = date('Y-m-d H:i:s', time() + (API_SEASON_TIME * 3600));
        $stmt = $mysqli->prepare("INSERT INTO `$table` (user_id, request_count, season_expires) VALUES (?, 1, ?)");
        $stmt->bind_param('ss', $userId, $seasonExpires);
        $stmt->execute();
        $stmt->close();
        
        return ['allowed' => true, 'remaining' => API_RATE_LIMIT - 1, 'season_expires' => $seasonExpires];
    }
    
    // Clean old records
    $mysqli->query("DELETE FROM `$table` WHERE last_request < '$cutoff'");
    
    // Check season time expired
    if ($record['season_expires'] && strtotime($record['season_expires']) < time()) {
        // Reset for new season
        $seasonExpires = date('Y-m-d H:i:s', time() + (API_SEASON_TIME * 3600));
        $stmt = $mysqli->prepare("UPDATE `$table` SET request_count = 1, season_expires = ? WHERE user_id = ?");
        $stmt->bind_param('ss', $seasonExpires, $userId);
        $stmt->execute();
        $stmt->close();
        
        return ['allowed' => true, 'remaining' => API_RATE_LIMIT - 1, 'season_expires' => $seasonExpires, 'season_reset' => true];
    }
    
    // Check rate limit
    if ($record['request_count'] >= API_RATE_LIMIT) {
        return [
            'allowed' => false, 
            'message' => 'Rate limit exceeded. Try again after ' . API_SEASON_TIME . ' hours.',
            'season_expires' => $record['season_expires']
        ];
    }
    
    // Increment counter
    $stmt = $mysqli->prepare("UPDATE `$table` SET request_count = request_count + 1, last_request = NOW() WHERE user_id = ?");
    $stmt->bind_param('s', $userId);
    $stmt->execute();
    $stmt->close();
    
    $remaining = API_RATE_LIMIT - $record['request_count'] - 1;
    
    return [
        'allowed' => true, 
        'remaining' => $remaining,
        'season_expires' => $record['season_expires']
    ];
}

// =======================
// LOGGED USER CHECK
// =======================
function isUserLoggedIn($mysqli, $token) {
    if (empty($token)) {
        return ['logged_in' => false, 'message' => 'Authentication token required'];
    }
    
    // Verify JWT token
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return ['logged_in' => false, 'message' => 'Invalid token format'];
    }
    
    $payload = json_decode(base64_decode($parts[1]), true);
    if (!$payload || !isset($payload['user_id']) || !isset($payload['exp'])) {
        return ['logged_in' => false, 'message' => 'Invalid token payload'];
    }
    
    // Check token expiration (24 hours)
    if ($payload['exp'] < time()) {
        return ['logged_in' => false, 'message' => 'Token expired. Please login again.'];
    }
    
    return [
        'logged_in' => true,
        'user_id' => $payload['user_id'],
        'email' => $payload['email'] ?? ''
    ];
}

// =======================
// ACTIVE USER CHECK
// =======================
function isUserActive($mysqli, $userId) {
    if (empty($userId)) {
        return ['active' => false, 'message' => 'User ID required'];
    }
    
    $stmt = $mysqli->prepare("SELECT id, status, is_active FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('s', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if (!$user) {
        return ['active' => false, 'message' => 'User not found'];
    }
    
    if ($user['status'] !== 'active' || $user['is_active'] != 1) {
        return ['active' => false, 'message' => 'Account is not active'];
    }
    
    return ['active' => true, 'user' => $user];
}

// =======================
// VERIFY USER SECURITY
// =======================
function verifyUserSecurity($mysqli, $token, $clientType = 'app') {
    $response = ['success' => false];
    
    // Check logged in
    $loginCheck = isUserLoggedIn($mysqli, $token);
    if (!$loginCheck['logged_in']) {
        $response['message'] = $loginCheck['message'];
        $response['code'] = 'NOT_LOGGED_IN';
        return $response;
    }
    $response['user_id'] = $loginCheck['user_id'];
    
    // Check active
    $activeCheck = isUserActive($mysqli, $loginCheck['user_id']);
    if (!$activeCheck['active']) {
        $response['message'] = $activeCheck['message'];
        $response['code'] = 'USER_NOT_ACTIVE';
        return $response;
    }
    
    // Check rate limit
    $rateCheck = checkApiRateLimit($mysqli, $loginCheck['user_id'], $clientType);
    if (!$rateCheck['allowed']) {
        $response['message'] = $rateCheck['message'];
        $response['code'] = 'RATE_LIMIT_EXCEEDED';
        $response['season_expires'] = $rateCheck['season_expires'] ?? null;
        return $response;
    }
    
    $response['success'] = true;
    $response['remaining'] = $rateCheck['remaining'];
    $response['season_expires'] = $rateCheck['season_expires'] ?? null;
    
    return $response;
}

// =======================
// API RESPONSE HELPERS
// =======================
function apiSuccess($data = [], $message = 'Success') {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

function apiError($message = 'Error', $code = 'ERROR') {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $message,
        'code' => $code
    ]);
    exit;
}

function apiUnauthorized($message = 'Unauthorized') {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => $message,
        'code' => 'UNAUTHORIZED'
    ]);
    exit;
}