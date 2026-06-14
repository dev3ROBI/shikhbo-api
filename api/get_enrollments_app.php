<?php
/**
 * GET USER ENROLLMENTS FOR APP
 * Returns user's enrolled courses with progress
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

$status = $_GET['status'] ?? 'active';

$statusFilter = $status === 'all' ? "" : "AND e.status = ?";
$params = [(int)$uid];
$types = "i";

if ($status !== 'all') {
    $params[] = $status;
    $types .= "s";
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

$sql = "SELECT e.id AS enrollment_id, e.enrolled_at, e.progress, e.status AS enrollment_status,
               c.id AS course_id, c.title, c.slug, c.short_description, c.cover_image,
               c.price, c.is_free, c.difficulty, c.duration_hours, c.total_enrolled, c.category_id,
               cat.name AS category_name,
               (SELECT COUNT(*) FROM exams ex WHERE ex.course_id = c.id AND ex.status = 'active') AS direct_exam_count
        FROM enrollments e
        JOIN courses c ON e.course_id = c.id
        LEFT JOIN exam_categories cat ON c.category_id = cat.id
        WHERE e.user_id = ? $statusFilter
        ORDER BY e.enrolled_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$enrollments = [];
while ($row = $result->fetch_assoc()) {
    $catId = (int)$row['category_id'];
    $catExamCount = $catExamMap[$catId] ?? 0;
    $directCount = (int)$row['direct_exam_count'];
    $examCount = $catExamCount > 0 ? $catExamCount : $directCount;

    $enrollments[] = [
        'enrollment_id' => (int)$row['enrollment_id'],
        'enrolled_at' => $row['enrolled_at'],
        'progress' => (float)$row['progress'],
        'enrollment_status' => $row['enrollment_status'],
        'course' => [
            'id' => (int)$row['course_id'],
            'title' => $row['title'],
            'slug' => $row['slug'],
            'short_description' => $row['short_description'],
            'cover_image' => $row['cover_image'],
            'price' => (float)$row['price'],
            'is_free' => (int)$row['is_free'],
            'difficulty' => $row['difficulty'],
            'duration_hours' => (int)$row['duration_hours'],
            'exam_count' => $examCount,
            'total_enrolled' => (int)$row['total_enrolled'],
            'category_name' => $row['category_name']
        ]
    ];
}
$stmt->close();

echo json_encode([
    'status' => 'success',
    'enrollments' => $enrollments,
    'total' => count($enrollments),
    'access' => 'unlimited'
]);
