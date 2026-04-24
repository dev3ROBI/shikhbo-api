<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
$mysqli->set_charset('utf8mb4');

$input = json_decode(file_get_contents('php://input'), true);
$examId = intval($input['exam_id'] ?? 0);
$userId = intval($input['user_id'] ?? 0);   // Normally from session, here we take from request for testing
$answers = $input['answers'] ?? [];        // Array of {question_id, selected_option}

if (!$examId || !$userId || empty($answers)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
    exit;
}

// Fetch correct answers
$stmt = $mysqli->prepare("SELECT id, correct_answer, marks FROM questions WHERE exam_id = ?");
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

// Fetch exam info for pass criteria
$examInfo = $mysqli->query("SELECT total_marks, passing_percentage FROM exams WHERE id = $examId")->fetch_assoc();
$passingMarks = $examInfo ? ($examInfo['total_marks'] * $examInfo['passing_percentage'] / 100) : 40;
$status = $score >= $passingMarks ? 'passed' : 'failed';

// Save result
$stmt = $mysqli->prepare("INSERT INTO exam_results (user_id, exam_id, score, total_marks, percentage, status, started_at, completed_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
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
    'details' => $details
]);