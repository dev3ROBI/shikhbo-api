<?php
/**
 * GET USER PROFILE FOR APP
 * Requires: uid, season, u_state in body
 * Accepts Bearer token in Authorization header
 * 
 * Usage: POST /api/get_user_app.php
 * Header: Authorization: Bearer <token>
 * Body: {"uid":1, "season":"...", "u_state":"1"}
 */
error_reporting(0);
ini_set('display_errors', 0);

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

try {
    require_once __DIR__ . '/../includes/app_security_validation.php';
    require_once __DIR__ . '/../api/config.php';

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['uid']) || !isset($input['season']) || !isset($input['u_state'])) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
        exit();
    }

    $uid = $input['uid'];
    $season = $input['season'];
    $u_state = $input['u_state'];

    // Validate security
    $security = requireAppSecurity($uid, $season, $u_state);

    $conn = getAppSecurityConn();
    $conn->set_charset('utf8mb4');

    // Get user profile
    $stmt = $conn->prepare("SELECT id, name, email, profile_image, referral_code, language, tagline, streak, member_since, is_premium, status, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
        exit();
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    $memberSince = $user['member_since'];
    if (empty($memberSince) && !empty($user['created_at'])) {
        $memberSince = date('Y-m-d', strtotime($user['created_at']));
    }

    // Get today's check-in status (server date)
    $serverDate = date('Y-m-d');
    $checkedInToday = false;
    $checkinStmt = $conn->prepare("SELECT id FROM daily_checkins WHERE user_id = ? AND checkin_date = ?");
    $checkinStmt->bind_param("is", $uid, $serverDate);
    $checkinStmt->execute();
    $checkinResult = $checkinStmt->get_result();
    if ($checkinResult->num_rows > 0) {
        $checkedInToday = true;
    }
    $checkinStmt->close();

    // Get total check-in count
    $totalCheckins = 0;
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM daily_checkins WHERE user_id = ?");
    $countStmt->bind_param("i", $uid);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    if ($countResult->num_rows > 0) {
        $totalCheckins = (int)$countResult->fetch_assoc()['total'];
    }
    $countStmt->close();

    // Get total XP from check-ins
    $totalXp = 0;
    $xpStmt = $conn->prepare("SELECT COALESCE(SUM(xp_earned), 0) as total_xp FROM daily_checkins WHERE user_id = ?");
    $xpStmt->bind_param("i", $uid);
    $xpStmt->execute();
    $xpResult = $xpStmt->get_result();
    if ($xpResult->num_rows > 0) {
        $totalXp = (int)$xpResult->fetch_assoc()['total_xp'];
    }
    $xpStmt->close();

    // Get last 7 days of check-ins for weekly view
    $weekCheckins = [];
    $weekStmt = $conn->prepare("SELECT checkin_date, xp_earned FROM daily_checkins WHERE user_id = ? AND checkin_date >= DATE_SUB(?, INTERVAL 7 DAY) ORDER BY checkin_date ASC");
    $weekStmt->bind_param("is", $uid, $serverDate);
    $weekStmt->execute();
    $weekResult = $weekStmt->get_result();
    while ($row = $weekResult->fetch_assoc()) {
        $weekCheckins[] = $row['checkin_date'];
    }
    $weekStmt->close();

    echo json_encode([
        'status' => 'success',
        'user' => [
            'user_id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'profile_image' => $user['profile_image'] ?? '',
            'referral_code' => $user['referral_code'] ?? '',
            'language' => $user['language'] ?? 'en',
            'tagline' => $user['tagline'] ?? '',
            'streak' => (int)$user['streak'],
            'member_since' => $memberSince ?? date('Y-m-d'),
            'is_premium' => (bool)$user['is_premium']
        ],
        'checkin' => [
            'checked_in_today' => $checkedInToday,
            'total_checkins' => $totalCheckins,
            'total_xp' => $totalXp,
            'week_checkins' => $weekCheckins
        ],
        'access' => 'unlimited'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}