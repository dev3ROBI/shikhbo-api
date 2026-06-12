<?php
/**
 * DAILY CHECK-IN FOR APP
 * Requires: uid, season, u_state in body
 * Accepts Bearer token in Authorization header
 * Uses SERVER DATE to prevent time cheating
 * 
 * Usage: POST /api/daily_checkin_app.php
 * Header: Authorization: Bearer <token>
 * Body: {"uid":1, "season":"...", "u_state":"1"}
 * 
 * Response:
 *   - success: check-in claimed
 *   - already_checked: already checked in today
 *   - error: something wrong
 */

require_once __DIR__ . '/../includes/app_security_validation.php';
require_once __DIR__ . '/../api/config.php';


header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['uid']) || !isset($input['season']) || !isset($input['u_state'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
    exit();
}

$uid = (int)$input['uid'];
$season = $input['season'];
$u_state = $input['u_state'];

// Validate security
$security = requireAppSecurity($uid, $season, $u_state);

$conn = getAppSecurityConn();
$conn->set_charset('utf8mb4');

// Get Bearer token from header
$token = getBearerToken();

// Verify token if provided
if ($token) {
    $tokenVerify = verifyToken($token, $uid);
    if (!$tokenVerify['valid']) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Invalid token"], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

try {
    // Use SERVER date for check-in to prevent phone time cheating
    $serverDate = date('Y-m-d');
    $xpReward = 50;

    // Check if already checked in today (server-side)
    $checkStmt = $conn->prepare("SELECT id, streak FROM daily_checkins WHERE user_id = ? AND checkin_date = ?");
    $checkStmt->bind_param("is", $uid, $serverDate);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        echo json_encode([
            'status' => 'already_checked',
            'message' => 'Already checked in today',
            'data' => [
                'checkin_date' => $serverDate,
                'xp_earned' => 0,
                'already_checked' => true
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
    $checkStmt->close();

    // Calculate streak - get last check-in date
    $lastStmt = $conn->prepare("SELECT checkin_date, streak FROM daily_checkins WHERE user_id = ? ORDER BY checkin_date DESC LIMIT 1");
    $lastStmt->bind_param("i", $uid);
    $lastStmt->execute();
    $lastResult = $lastStmt->get_result();
    $newStreak = 1;

    if ($lastResult->num_rows > 0) {
        $lastRow = $lastResult->fetch_assoc();
        $lastDate = $lastRow['checkin_date'];
        $lastStreak = (int)$lastRow['streak'];

        // Calculate difference in days using server dates
        $lastTimestamp = strtotime($lastDate);
        $todayTimestamp = strtotime($serverDate);
        $diffDays = ($todayTimestamp - $lastTimestamp) / (60 * 60 * 24);

        if ($diffDays == 1) {
            // Consecutive day - increase streak
            $newStreak = $lastStreak + 1;
        } elseif ($diffDays == 0) {
            // Same day - should not happen as we already checked
            $newStreak = $lastStreak;
        } else {
            // Gap in days - reset streak
            $newStreak = 1;
        }
    }
    $lastStmt->close();

    // Insert check-in record
    $insertStmt = $conn->prepare("INSERT INTO daily_checkins (user_id, checkin_date, xp_earned, streak) VALUES (?, ?, ?, ?)");
    $insertStmt->bind_param("isii", $uid, $serverDate, $xpReward, $newStreak);
    
    if ($insertStmt->execute()) {
        // Update streak in users table
        $updateUserStmt = $conn->prepare("UPDATE users SET streak = ? WHERE id = ?");
        $updateUserStmt->bind_param("ii", $newStreak, $uid);
        $updateUserStmt->execute();
        $updateUserStmt->close();

        echo json_encode([
            'status' => 'success',
            'message' => 'Check-in successful',
            'data' => [
                'checkin_date' => $serverDate,
                'xp_earned' => $xpReward,
                'streak' => $newStreak,
                'already_checked' => false
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to save check-in'
        ], JSON_UNESCAPED_UNICODE);
    }
    $insertStmt->close();

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
