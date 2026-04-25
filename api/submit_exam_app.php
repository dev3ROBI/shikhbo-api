<?php
/**
 * SUBMIT EXAM FOR APP
 * Requires: uid, season, u_state
 * 
 * Usage: POST /api/submit_exam_app.php
 * Body: {"exam_id":1, "answers":[], "uid":1, "season":"__", "u_state":"1"}
 */
require_once __DIR__ . '/../includes/app_security_validation.php';
require_once __DIR__ . '/../api/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

// Get security parameters
$uid = $input['uid'] ?? null;
$season = $input['season'] ?? null;
$u_state = $input['u_state'] ?? null;

// Validate security
$security = requireAppSecurity($uid, $season, $u_state);

// Database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
$conn->set_charset('utf8mb4');

$examId = intval($input['exam_id'] ?? 0);
$userId = intval($input['user_id'] ?? $uid);
$answers = $input['answers'] ?? [];

if (!$examId || empty($answers)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing exam_id or answers']);
    exit;
}

// Fetch correct answers
$stmt = $conn->prepare("SELECT id, correct_answer, marks FROM questions WHERE exam_id = ?");
$stmt->bind_param('i', $examId);
$stmt->execute();
$result = $stmt->get_result();
$questions = [];
while ($row = $result->fetch_assoc()) {
    $questions[$row['id']] = $row;
}
$stmt->close();

$score = 0;
$totalMarks = 0;
$details = [];

foreach ($answers as $ans) {
    $qid = intval($ans['question_id'] ?? 0);
    $selected = $ans['selected_option'] ?? '';
    if (isset($questions[$qid])) {
        $correct = $questions[$qid]['correct_answer'];
        $marks = $questions[$qid]['marks'];
        $totalMarks += $marks;
        $isCorrect = strtolower($selected) === strtolower($correct);
        if ($isCorrect) $score += $marks;
        $details[] = [
            'question_id' => $qid,
            'selected' => $selected,
            'correct' => $correct,
            'is_correct' => $isCorrect,
            'marks_obtained' => $isCorrect ? $marks : 0
        ];
    }
}

$examInfo = $conn->query("SELECT total_marks, passing_percentage FROM exams WHERE id = $examId")->fetch_assoc();
$passingMarks = $examInfo ? ($examInfo['total_marks'] * $examInfo['passing_percentage'] / 100) : 40;
$status = $score >= $passingMarks ? 'passed' : 'failed';

$stmt = $conn->prepare("INSERT INTO exam_results (user_id, exam_id, score, total_marks, percentage, status, started_at, completed_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
$percentage = ($totalMarks > 0) ? ($score / $totalMarks) * 100 : 0;
$stmt->bind_param('iiidds', $userId, $examId, $score, $totalMarks, $percentage, $status);
$stmt->execute();
$stmt->close();

echo json_encode([
    'status' => 'success',
    'score' => $score,
    'total_marks' => $totalMarks,
    'percentage' => round($percentage, 2),
    'exam_status' => $status,
    'details' => $details,
    'user_info' => [
        'uid' => (int)$uid,
        'requests_remaining' => $security['remaining']
    ]
]);