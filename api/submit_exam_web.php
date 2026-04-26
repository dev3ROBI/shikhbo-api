<?php
/**
 * SUBMIT EXAM FOR WEB/ADMIN PANEL
 * No security required - for web and admin panel
 * 
 * Usage: POST /api/submit_exam_web.php
 * Body: {"exam_id":1, "answers":[]}
 */
require_once __DIR__ . '/../api/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

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

$examId = intval($input['exam_id'] ?? 0);
$userId = intval($input['user_id'] ?? 0);
$answers = $input['answers'] ?? [];

if (!$examId || !$userId || empty($answers)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
    exit;
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
$conn->set_charset('utf8mb4');

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
    'source' => 'web'
]);