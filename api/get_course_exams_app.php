<?php
/**
 * GET COURSE EXAMS FOR APP
 * Returns exams for a given course along with user's attempt history
 * GET params: course_id (required), uid, season, u_state
 */
require_once __DIR__ . '/../includes/app_security_validation.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$token = getBearerToken();
$uid = $_GET['uid'] ?? null;
$season = $_GET['season'] ?? null;
$u_state = $_GET['u_state'] ?? null;
$security = requireAppSecurity($uid, $season, $u_state);

if ($token) {
    $tokenVerify = verifyToken($token, $uid);
}

$conn = getAppSecurityConn();

$courseId = intval($_GET['course_id'] ?? 0);
if ($courseId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'course_id required']);
    exit;
}

// Verify course
$courseCheck = $conn->prepare("SELECT id, title FROM courses WHERE id = ? AND is_active = 1");
$courseCheck->bind_param('i', $courseId);
$courseCheck->execute();
$course = $courseCheck->get_result()->fetch_assoc();
$courseCheck->close();

if (!$course) {
    echo json_encode(['status' => 'error', 'message' => 'Course not found']);
    exit;
}

// Get exams with user's best result
$sql = "SELECT e.id, e.title, e.duration_minutes, e.total_marks, e.passing_percentage,
               e.is_free, e.sort_order,
               (SELECT COUNT(*) FROM questions q WHERE q.exam_id = e.id) AS question_count,
               (SELECT MAX(er.percentage) FROM exam_results er WHERE er.exam_id = e.id AND er.user_id = ?) AS best_score,
               (SELECT COUNT(*) FROM exam_results er WHERE er.exam_id = e.id AND er.user_id = ?) AS attempt_count
        FROM exams e
        WHERE e.course_id = ? AND e.status = 'active'
        ORDER BY e.sort_order, e.title";

$stmt = $conn->prepare($sql);
$stmt->bind_param('iii', $uid, $uid, $courseId);
$stmt->execute();
$result = $stmt->get_result();

$exams = [];
while ($row = $result->fetch_assoc()) {
    $exams[] = [
        'id' => (int)$row['id'],
        'title' => $row['title'],
        'duration_minutes' => (int)$row['duration_minutes'],
        'total_marks' => (int)$row['total_marks'],
        'passing_percentage' => (int)$row['passing_percentage'],
        'is_free' => (int)$row['is_free'],
        'question_count' => (int)$row['question_count'],
        'best_score' => $row['best_score'] !== null ? round((float)$row['best_score'], 1) : null,
        'attempt_count' => (int)$row['attempt_count']
    ];
}
$stmt->close();

echo json_encode([
    'status' => 'success',
    'course_id' => $courseId,
    'course_title' => $course['title'],
    'exams' => $exams,
    'total_exams' => count($exams),
    'access' => 'unlimited'
]);
