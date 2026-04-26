<?php
/**
 * Shikhbo App Security Validation
 * 
 * This function validates security parameters for all _app.php APIs
 * Uses Bearer token for authentication - users get unlimited access
 */

// Single shared connection
$app_security_conn = null;

function getAppSecurityConn() {
    global $app_security_conn;
    if ($app_security_conn === null) {
        require_once __DIR__ . '/../api/config.php';
        $app_security_conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        $app_security_conn->set_charset('utf8mb4');
    }
    return $app_security_conn;
}

// =======================
// VALIDATE APP SECURITY (Token-based - unlimited access)
// =======================
function validateAppSecurity($uid, $season, $u_state) {
    $conn = getAppSecurityConn();
    
    if ($conn->connect_error) {
        return [
            'valid' => false,
            'message' => 'Database connection failed',
            'code' => 'DB_ERROR'
        ];
    }
    
    // 1. Check uid exists
    if (!$uid || $uid === null || $uid === '') {
        return [
            'valid' => false,
            'message' => 'Missing user ID (uid)',
            'code' => 'UID_REQUIRED'
        ];
    }
    
    // 2. Check user active state
    if (!$u_state || $u_state !== '1') {
        return [
            'valid' => false,
            'message' => 'User account is not active',
            'code' => 'USER_NOT_ACTIVE'
        ];
    }
    
    // 3. Verify user exists in database
    $stmt = $conn->prepare("SELECT id, name, email, status, is_active FROM users WHERE id = ?");
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if (!$user) {
        return [
            'valid' => false,
            'message' => 'User not found',
            'code' => 'USER_NOT_FOUND'
        ];
    }
    
    if ($user['status'] !== 'active' || $user['is_active'] != 1) {
        return [
            'valid' => false,
            'message' => 'User account is inactive or suspended',
            'code' => 'USER_INACTIVE'
        ];
    }
    
    // User is valid - unlimited access
    return [
        'valid' => true,
        'user' => $user,
        'remaining' => 'unlimited',
        'unlimited_access' => true
    ];
}

// =======================
// GET BEARER TOKEN FROM HEADER
// =======================
function getBearerToken() {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
    
    if (empty($authHeader)) {
        return null;
    }
    
    // Remove "Bearer " prefix if present
    if (stripos($authHeader, 'Bearer ') === 0) {
        return trim(substr($authHeader, 7));
    }
    
    return trim($authHeader);
}

// =======================
// VERIFY TOKEN (For APIs that need explicit token validation)
// =======================
function verifyToken($token, $userId = null) {
    if (empty($token)) {
        return [
            'valid' => false,
            'message' => 'Token required',
            'code' => 'TOKEN_REQUIRED'
        ];
    }
    
    $conn = getAppSecurityConn();
    
    // Clean up expired tokens periodically (1% chance)
    if (rand(1, 100) === 1) {
        $conn->query("DELETE FROM user_tokens WHERE expires_at < NOW()");
    }
    
    // Verify token exists and not expired
    $stmt = $conn->prepare("SELECT user_id, expires_at FROM user_tokens WHERE token = ?");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $tokenData = $result->fetch_assoc();
    $stmt->close();
    
    if (!$tokenData) {
        return [
            'valid' => false,
            'message' => 'Invalid token',
            'code' => 'INVALID_TOKEN'
        ];
    }
    
    if (strtotime($tokenData['expires_at']) < time()) {
        return [
            'valid' => false,
            'message' => 'Token expired',
            'code' => 'TOKEN_EXPIRED'
        ];
    }
    
    // If userId provided, verify it matches
    if ($userId !== null && $tokenData['user_id'] != $userId) {
        return [
            'valid' => false,
            'message' => 'Token does not match user',
            'code' => 'TOKEN_MISMATCH'
        ];
    }
    
    return [
        'valid' => true,
        'user_id' => (int)$tokenData['user_id'],
        'expires_at' => $tokenData['expires_at']
    ];
}

// =======================
// REQUIRE APP SECURITY
// =======================
function requireAppSecurity($uid, $season, $u_state) {
    $result = validateAppSecurity($uid, $season, $u_state);
    
    if (!$result['valid']) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'message' => $result['message'],
            'code' => $result['code']
        ]);
        exit();
    }
    
    return $result;
}