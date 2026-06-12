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

$sql = "SELECT e.id AS enrollment_id, e.enrolled_at, e.progress, e.status AS enrollment_status,
               c.id AS course_id, c.title, c.slug, c.short_description, c.cover_image,
               c.price, c.is_free, c.difficulty, c.duration_hours, c.total_enrolled,
               cat.name AS category_name,
               (SELECT COUNT(*) FROM exams ex WHERE ex.course_id = c.id AND ex.status = 'active') AS exam_count
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
            'exam_count' => (int)$row['exam_count'],
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
