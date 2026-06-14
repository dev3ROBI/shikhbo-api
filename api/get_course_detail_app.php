<?php
/**
 * GET COURSE DETAIL FOR APP
 * Returns single course with full metadata and exam list
 * GET params: course_id (required)
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

// Build category tree exam count map
$catExamMap = [];
$catResult = $conn->query("
    SELECT ec.id, ec.parent_id,
           (SELECT COUNT(*) FROM exams e WHERE e.category_id = ec.id AND e.status = 'active') AS direct_count
    FROM exam_categories ec WHERE ec.is_active = 1
");
$catRows = [];
while ($r = $catResult->fetch_assoc()) {
    $r['direct_count'] = (int)$r['direct_count'];
    $r['total_count'] = (int)$r['direct_count'];
    $catRows[(int)$r['id']] = $r;
}
foreach ($catRows as $id => &$data) {
    $pid = $data['parent_id'];
    if ($pid && isset($catRows[$pid])) {
        $catRows[$pid]['total_count'] += $data['direct_count'];
    }
}
unset($data);
foreach ($catRows as $id => $data) {
    $catExamMap[$id] = $data['total_count'];
}

$stmt = $conn->prepare("
    SELECT c.*, cat.name AS category_name, cat.slug AS category_slug,
           (SELECT COUNT(*) FROM exams e WHERE e.course_id = c.id AND e.status = 'active') AS direct_exam_count,
           (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id AND e.status = 'active') AS enrolled_count
    FROM courses c
    LEFT JOIN exam_categories cat ON c.category_id = cat.id
    WHERE c.id = ? AND c.is_active = 1
");
$stmt->bind_param('i', $courseId);
$stmt->execute();
$result = $stmt->get_result();
$course = $result->fetch_assoc();
$stmt->close();

if (!$course) {
    echo json_encode(['status' => 'error', 'message' => 'Course not found']);
    exit;
}

$catId = (int)$course['category_id'];
$catExamCount = $catExamMap[$catId] ?? 0;
$directCount = (int)$course['direct_exam_count'];
$examCount = $catExamCount > 0 ? $catExamCount : $directCount;

$examStmt = $conn->prepare("
    SELECT e.id, e.title, e.duration_minutes, e.total_marks, e.passing_percentage,
           e.is_free, e.status,
           (SELECT COUNT(*) FROM questions q WHERE q.exam_id = e.id) AS question_count
    FROM exams e
    WHERE e.course_id = ? AND e.status = 'active'
    ORDER BY e.id
");
$examStmt->bind_param('i', $courseId);
$examStmt->execute();
$examsResult = $examStmt->get_result();
$exams = [];
while ($row = $examsResult->fetch_assoc()) {
    $exams[] = [
        'id' => (int)$row['id'],
        'title' => $row['title'],
        'duration_minutes' => (int)$row['duration_minutes'],
        'total_marks' => (int)$row['total_marks'],
        'passing_percentage' => (int)$row['passing_percentage'],
        'is_free' => (int)$row['is_free'],
        'question_count' => (int)$row['question_count']
    ];
}
$examStmt->close();

$isEnrolled = false;
if ($uid) {
    $enrollCheck = $conn->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ? AND status = 'active'");
    $enrollCheck->bind_param('ii', $uid, $courseId);
    $enrollCheck->execute();
    $isEnrolled = $enrollCheck->get_result()->num_rows > 0;
    $enrollCheck->close();
}

echo json_encode([
    'status' => 'success',
    'course' => [
        'id' => (int)$course['id'],
        'title' => $course['title'],
        'slug' => $course['slug'],
        'short_description' => $course['short_description'],
        'description' => $course['description'],
        'cover_image' => $course['cover_image'],
        'price' => (float)$course['price'],
        'is_free' => (int)$course['is_free'],
        'course_type' => $course['course_type'],
        'difficulty' => $course['difficulty'],
        'duration_hours' => (int)$course['duration_hours'],
        'exam_count' => $examCount,
        'enrolled_count' => (int)$course['enrolled_count'],
        'is_featured' => (int)$course['is_featured'],
        'category_name' => $course['category_name'],
        'category_slug' => $course['category_slug'],
        'created_at' => $course['created_at']
    ],
    'exams' => $exams,
    'is_enrolled' => $isEnrolled,
    'access' => 'unlimited'
]);
