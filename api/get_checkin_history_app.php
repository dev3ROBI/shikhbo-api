<?php
/**
 * GET CHECK-IN HISTORY FOR APP
 * Requires: uid, season, u_state in body
 * Accepts Bearer token in Authorization header
 *
 * Usage: POST /api/get_checkin_history_app.php
 * Header: Authorization: Bearer <token>
 * Body: {"uid":1, "season":"...", "u_state":"1"}
 *
 * Response: list of all check-in dates with XP and streak
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

$security = requireAppSecurity($uid, $season, $u_state);

$conn = getAppSecurityConn();
$conn->set_charset('utf8mb4');

$token = getBearerToken();
if ($token) {
    $tokenVerify = verifyToken($token, $uid);
    if (!$tokenVerify['valid']) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Invalid token"], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

try {
    $month = isset($input['month']) ? (int)$input['month'] : 0;
    $year = isset($input['year']) ? (int)$input['year'] : 0;

    if ($month > 0 && $year > 0) {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));
        $stmt = $conn->prepare("SELECT checkin_date, xp_earned, streak FROM daily_checkins WHERE user_id = ? AND checkin_date >= ? AND checkin_date <= ? ORDER BY checkin_date DESC");
        $stmt->bind_param("iss", $uid, $startDate, $endDate);
    } else {
        $stmt = $conn->prepare("SELECT checkin_date, xp_earned, streak FROM daily_checkins WHERE user_id = ? ORDER BY checkin_date DESC");
        $stmt->bind_param("i", $uid);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $checkins = [];
    $totalXp = 0;
    while ($row = $result->fetch_assoc()) {
        $checkins[] = [
            'date' => $row['checkin_date'],
            'xp_earned' => (int)$row['xp_earned'],
            'streak' => (int)$row['streak']
        ];
        $totalXp += (int)$row['xp_earned'];
    }
    $stmt->close();

    // Get current streak from users table
    $streakStmt = $conn->prepare("SELECT streak FROM users WHERE id = ?");
    $streakStmt->bind_param("i", $uid);
    $streakStmt->execute();
    $streakResult = $streakStmt->get_result();
    $currentStreak = 0;
    if ($streakResult->num_rows > 0) {
        $currentStreak = (int)$streakResult->fetch_assoc()['streak'];
    }
    $streakStmt->close();

    echo json_encode([
        'status' => 'success',
        'data' => [
            'total_checkins' => count($checkins),
            'total_xp' => $totalXp,
            'current_streak' => $currentStreak,
            'checkins' => $checkins
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
