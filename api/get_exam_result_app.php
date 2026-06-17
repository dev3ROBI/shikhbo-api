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

    // Determine explanation access: 
    // - Paid course (is_free=0): user must be enrolled
    // - Free course / standalone exam: user must be premium
    $canViewExplanations = false;
    $isPremiumUser = false;

    // Check user's premium status
    $userStmt = $conn->prepare("SELECT is_premium FROM users WHERE id = ?");
    $userStmt->bind_param('i', $uid);
    $userStmt->execute();
    $userRow = $userStmt->get_result()->fetch_assoc();
    $userStmt->close();
    if ($userRow) {
        $isPremiumUser = (bool)$userRow['is_premium'];
    }

    // Get exam's course info
    $courseStmt = $conn->prepare("
        SELECT e.course_id, e.is_free as exam_is_free, 
               c.is_free as course_is_free, c.id as cid
        FROM exams e
        LEFT JOIN courses c ON e.course_id = c.id
        WHERE e.id = ?
    ");
    $courseStmt->bind_param('i', $examId);
    $courseStmt->execute();
    $courseRow = $courseStmt->get_result()->fetch_assoc();
    $courseStmt->close();

    if ($courseRow && $courseRow['cid']) {
        // Exam belongs to a course
        if ((int)$courseRow['course_is_free'] === 1) {
            // Free course → needs premium
            $canViewExplanations = $isPremiumUser;
        } else {
            // Paid course → needs enrollment
            $enrollStmt = $conn->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ? AND status = 'active'");
            $enrollStmt->bind_param('ii', $uid, $courseRow['cid']);
            $enrollStmt->execute();
            $enrolled = $enrollStmt->get_result()->fetch_assoc();
            $enrollStmt->close();
            $canViewExplanations = (bool)$enrolled;
        }
    } else {
        // Standalone exam (no course) → use exam's is_free
        if ($courseRow && (int)$courseRow['exam_is_free'] === 1) {
            $canViewExplanations = $isPremiumUser;
        } else {
            // Paid standalone exam → needs premium as proxy
            $canViewExplanations = $isPremiumUser;
        }
    }

    // Fetch questions with user answers via LEFT JOIN
    // This ensures ALL questions are returned, even those the user didn't answer
    $questionsStmt = $conn->prepare("
        SELECT q.id, q.question_text, q.option_a, q.option_b, q.option_c, q.option_d, 
               q.correct_answer, q.marks, q.explanation,
               ea.selected_option, ea.is_correct, ea.marks_obtained
        FROM questions q
        LEFT JOIN exam_answers ea ON ea.question_id = q.id AND ea.exam_result_id = ?
        WHERE q.exam_id = ?
        ORDER BY q.id ASC
    ");
    $questionsStmt->bind_param('ii', $examResultId, $examId);
    $questionsStmt->execute();
    $rows = $questionsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $questionsStmt->close();

    // Build questions array and details array from the joined result
    $questions = [];
    $details = [];

    foreach ($rows as $row) {
        $questions[] = [
            'id' => intval($row['id']),
            'question_text' => $row['question_text'],
            'option_a' => $row['option_a'],
            'option_b' => $row['option_b'],
            'option_c' => $row['option_c'],
            'option_d' => $row['option_d'],
            'correct_answer' => $row['correct_answer'],
            'marks' => intval($row['marks']),
            'explanation' => $row['explanation']
        ];

        $details[] = [
            'question_id' => intval($row['id']),
            'selected' => $row['selected_option'] ?? '',
            'correct' => $row['correct_answer'],
            'is_correct' => $row['selected_option'] !== null ? (bool)$row['is_correct'] : false,
            'marks_obtained' => $row['selected_option'] !== null ? intval($row['marks_obtained']) : 0
        ];
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
        'details' => $details,
        'can_view_explanations' => $canViewExplanations,
        'is_premium' => $isPremiumUser,
        'access' => 'unlimited'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
