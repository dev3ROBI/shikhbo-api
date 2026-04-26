<?php
/**
 * Shikhbo App Security Validation
 * 
 * This function validates security parameters for all _app.php APIs
 * Required: uid (user ID), season (timestamp), u_state (active state)
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
// VALIDATE APP SECURITY
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
    
    // 2. Check season exists and not expired
    if (!$season) {
        return [
            'valid' => false,
            'message' => 'Missing season timestamp',
            'code' => 'SEASON_REQUIRED'
        ];
    }
    
    $seasonTimestamp = strtotime($season);
    if (!$seasonTimestamp || $seasonTimestamp < time()) {
        return [
            'valid' => false,
            'message' => 'Season expired. Please login again.',
            'code' => 'SEASON_EXPIRED'
        ];
    }
    
    // 3. Check user active state
    if (!$u_state || $u_state !== '1') {
        return [
            'valid' => false,
            'message' => 'User account is not active',
            'code' => 'USER_NOT_ACTIVE'
        ];
    }
    
    // 4. Verify user exists in database
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
    
    // 5. Check rate limit
    $rateCheck = checkAppRateLimit($conn, $uid);
    if (!$rateCheck['allowed']) {
        return [
            'valid' => false,
            'message' => 'Rate limit exceeded. Try again after 3 hours.',
            'code' => 'RATE_LIMIT_EXCEEDED',
            'season_expires' => $rateCheck['season_expires'] ?? null
        ];
    }
    
    return [
        'valid' => true,
        'user' => $user,
        'remaining' => $rateCheck['remaining'] ?? 100,
        'season_expires' => $rateCheck['season_expires'] ?? null
    ];
}

// =======================
// CHECK RATE LIMIT
// =======================
function checkAppRateLimit($conn, $userId) {
    $table = 'app_api_usage';
    $cutoff = date('Y-m-d H:i:s', strtotime('-3 hours'));
    
    // Create table if not exists
    $conn->query("CREATE TABLE IF NOT EXISTS `$table` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(50) NOT NULL,
        request_count INT DEFAULT 0,
        last_request TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        season_expires TIMESTAMP DEFAULT NULL,
        INDEX idx_user (user_id)
    )");
    
    // Get or create user record
    $stmt = $conn->prepare("SELECT * FROM `$table` WHERE user_id = ?");
    $stmt->bind_param('s', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $record = $result->fetch_assoc();
    $stmt->close();
    
    $maxRequests = 100;
    $seasonHours = 3;
    
    if (!$record) {
        $seasonExpires = date('Y-m-d H:i:s', strtotime('+' . $seasonHours . ' hours'));
        $stmt = $conn->prepare("INSERT INTO `$table` (user_id, request_count, season_expires) VALUES (?, 1, ?)");
        $stmt->bind_param('ss', $userId, $seasonExpires);
        $stmt->execute();
        $stmt->close();
        
        return [
            'allowed' => true,
            'remaining' => $maxRequests - 1,
            'season_expires' => $seasonExpires
        ];
    }
    
    // Clean old records
    $conn->query("DELETE FROM `$table` WHERE last_request < '$cutoff'");
    
    // Check season expired - reset if needed
    if ($record['season_expires'] && strtotime($record['season_expires']) < time()) {
        $seasonExpires = date('Y-m-d H:i:s', strtotime('+' . $seasonHours . ' hours'));
        $stmt = $conn->prepare("UPDATE `$table` SET request_count = 1, season_expires = ? WHERE user_id = ?");
        $stmt->bind_param('ss', $seasonExpires, $userId);
        $stmt->execute();
        $stmt->close();
        
        return [
            'allowed' => true,
            'remaining' => $maxRequests - 1,
            'season_expires' => $seasonExpires,
            'season_reset' => true
        ];
    }
    
    // Check rate limit
    if ($record['request_count'] >= $maxRequests) {
        return [
            'allowed' => false,
            'message' => 'Rate limit exceeded',
            'season_expires' => $record['season_expires']
        ];
    }
    
    // Increment counter
    $stmt = $conn->prepare("UPDATE `$table` SET request_count = request_count + 1, last_request = NOW() WHERE user_id = ?");
    $stmt->bind_param('s', $userId);
    $stmt->execute();
    $stmt->close();
    
    $remaining = $maxRequests - $record['request_count'] - 1;
    
    return [
        'allowed' => true,
        'remaining' => max(0, $remaining),
        'season_expires' => $record['season_expires']
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