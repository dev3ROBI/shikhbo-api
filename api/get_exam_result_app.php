<?php
/**
 * GET EXAM RESULT FOR APP
 * Returns full exam result details including questions and user answers.
 * Requires: uid, season, u_state in GET params
 * Accepts Bearer token in Authorization header
 * 
 * Usage: GET /api/get_exam_result_app.php?exam_result_id=1&uid=1&season=...&u_state=1
 */
error_reporting(0);
ini_set('display_errors', 0);

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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit();
}

try {
    $uid = $_GET['uid'] ?? null;
    $season = $_GET['season'] ?? null;
    $u_state = $_GET['u_state'] ?? null;

    if (!$uid || !$season || !$u_state) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
        exit();
    }

    $security = requireAppSecurity($uid, $season, $u_state);

    $token = getBearerToken();
    if ($token) {
        $tokenVerify = verifyToken($token, $uid);
    }

    $conn = getAppSecurityConn();
    $conn->set_charset('utf8mb4');

    $examResultId = intval($_GET['exam_result_id'] ?? 0);

    if (!$examResultId) {
        echo json_encode(['status' => 'error', 'message' => 'Missing exam_result_id']);
        exit();
    }

    // Fetch exam result
    $resultStmt = $conn->prepare("
        SELECT er.id, er.exam_id, er.user_id, er.score, er.total_marks, 
               er.percentage, er.status, er.completed_at, e.title as exam_title
        FROM exam_results er
        JOIN exams e ON er.exam_id = e.id
        WHERE er.id = ? AND er.user_id = ?
    ");
    $resultStmt->bind_param('ii', $examResultId, $uid);
    $resultStmt->execute();
    $examResult = $resultStmt->get_result()->fetch_assoc();
    $resultStmt->close();

    if (!$examResult) {
        echo json_encode(['status' => 'error', 'message' => 'Exam result not found']);
        exit();
    }

    $examId = $examResult['exam_id'];

    // Fetch questions with correct answers (for review, all correct answers are exposed)
    $questionsStmt = $conn->prepare("
        SELECT id, question_text, option_a, option_b, option_c, option_d, 
               correct_answer, marks, explanation
        FROM questions 
        WHERE exam_id = ?
        ORDER BY id ASC
    ");
    $questionsStmt->bind_param('i', $examId);
    $questionsStmt->execute();
    $questions = $questionsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $questionsStmt->close();

    // Fetch user answers for this result
    $answersStmt = $conn->prepare("
        SELECT question_id, selected_option, is_correct, marks_obtained
        FROM exam_answers
        WHERE exam_result_id = ?
    ");
    $answersStmt->bind_param('i', $examResultId);
    $answersStmt->execute();
    $userAnswers = $answersStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $answersStmt->close();

    // Build details map
    $detailsMap = [];
    foreach ($userAnswers as $ans) {
        $detailsMap[] = [
            'question_id' => intval($ans['question_id']),
            'selected' => $ans['selected_option'],
            'correct' => '', // will be filled from questions
            'is_correct' => (bool)$ans['is_correct'],
            'marks_obtained' => intval($ans['marks_obtained'])
        ];
    }

    // Fill correct answers into details from questions
    $questionMap = [];
    foreach ($questions as $q) {
        $questionMap[$q['id']] = $q['correct_answer'];
    }
    for ($i = 0; $i < count($detailsMap); $i++) {
        $qid = $detailsMap[$i]['question_id'];
        if (isset($questionMap[$qid])) {
            $detailsMap[$i]['correct'] = $questionMap[$qid];
        }
    }

    echo json_encode([
        'status' => 'success',
        'exam_id' => intval($examId),
        'score' => intval($examResult['score']),
        'total_marks' => intval($examResult['total_marks']),
        'percentage' => floatval($examResult['percentage']),
        'exam_status' => $examResult['status'],
        'completed_at' => $examResult['completed_at'],
        'questions' => $questions,
        'details' => $detailsMap,
        'access' => 'unlimited'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
