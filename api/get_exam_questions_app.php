<?php
/**
 * GET EXAM QUESTIONS FOR APP
 * Requires: uid, season, u_state
 * Accepts Bearer token in header
 * 
 * Usage: /api/get_exam_questions_app.php?exam_id=1&uid=1&season=...&u_state=1
 * Header: Authorization: Bearer <token>
 */
require_once __DIR__ . '/../includes/app_security_validation.php';
require_once __DIR__ . '/../api/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$uid = $_GET['uid'] ?? null;
$season = $_GET['season'] ?? null;
$u_state = $_GET['u_state'] ?? null;

// Validate security
$security = requireAppSecurity($uid, $season, $u_state);

// Optional Bearer token
$token = getBearerToken();
if ($token) {
    $tokenVerify = verifyToken($token, $uid);
}

$conn = getAppSecurityConn();

$examId = intval($_GET['exam_id'] ?? 0);
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = intval($_GET['per_page'] ?? 25);
$seed = intval($_GET['seed'] ?? 0);

if (!$examId) {
    echo json_encode(['status' => 'error', 'message' => 'exam_id required']);
    exit;
}

$countStmt = $conn->prepare("SELECT COUNT(*) as total FROM questions WHERE exam_id = ?");
$countStmt->bind_param('i', $examId);
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

$totalPages = ceil($total / $perPage);
$offset = ($page - 1) * $perPage;
$order = $seed ? "ORDER BY RAND($seed)" : "ORDER BY RAND()";

$stmt = $conn->prepare("SELECT id, question_text, option_a, option_b, option_c, option_d, marks FROM questions WHERE exam_id = ? $order LIMIT ? OFFSET ?");
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
    'access' => 'unlimited'
]);