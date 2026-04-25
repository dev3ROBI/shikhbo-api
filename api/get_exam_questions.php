<?php
/**
 * Get exam questions - APP API (Requires security params)
 * 
 * GET /api/get_exam_questions.php?exam_id=1&uid=1&season=__&u_state=1
 */
require_once __DIR__ . '/../includes/app_security.php';
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-App-Language');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Security parameters (required)
$uid = $_GET['uid'] ?? null;
$season = $_GET['season'] ?? null;
$u_state = $_GET['u_state'] ?? null;

if (!$uid || !$season || !$u_state) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Missing security parameters', 'code' => 'SECURITY_PARAMS_REQUIRED']);
    exit();
}

if ($u_state != '1') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'User is not active', 'code' => 'USER_NOT_ACTIVE']);
    exit();
}

$season_expires = strtotime($season);
if ($season_expires < time()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Season expired', 'code' => 'SEASON_EXPIRED']);
    exit();
}

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}
$mysqli->set_charset('utf8mb4');

$examId = intval($_GET['exam_id'] ?? 0);
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = intval($_GET['per_page'] ?? 25);
$seed = intval($_GET['seed'] ?? 0);

if (!$examId) {
    echo json_encode(['status' => 'error', 'message' => 'exam_id required']);
    exit;
}

$countStmt = $mysqli->prepare("SELECT COUNT(*) as total FROM questions WHERE exam_id = ?");
$countStmt->bind_param('i', $examId);
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

$totalPages = ceil($total / $perPage);
$offset = ($page - 1) * $perPage;
$order = $seed ? "ORDER BY RAND($seed)" : "ORDER BY RAND()";

$stmt = $mysqli->prepare("SELECT id, question_text, option_a, option_b, option_c, option_d, marks FROM questions WHERE exam_id = ? $order LIMIT ? OFFSET ?");
$stmt->bind_param('iii', $examId, $perPage, $offset);
$stmt->execute();
$result = $stmt->get_result();
$questions = [];
while ($row = $result->fetch_assoc()) {
    $questions[] = $row;
}
$stmt->close();

echo json_encode([
    'status' => 'success',
    'exam_id' => $examId,
    'total_questions' => $total,
    'page' => $page,
    'per_page' => $perPage,
    'total_pages' => $totalPages,
    'questions' => $questions,
    'security' => ['uid' => (int)$uid, 'season' => $season, 'user_active' => (bool)$u_state]
]);