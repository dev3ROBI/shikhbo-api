<?php
/**
 * GET COURSES FOR APP
 * Returns course list with cover image, pricing, enrollment count
 * GET params: category_id (optional), is_featured (optional), search (optional), page (optional)
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

$categoryId = isset($_GET['category_id']) ? intval($_GET['category_id']) : null;
$isFeatured = isset($_GET['is_featured']) ? intval($_GET['is_featured']) : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : null;
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = min(50, max(1, intval($_GET['per_page'] ?? 20)));
$offset = ($page - 1) * $perPage;

$where = ["c.is_active = 1", "c.parent_course_id IS NULL"];
$params = [];
$types = "";

if ($categoryId) {
    $where[] = "c.category_id = ?";
    $params[] = $categoryId;
    $types .= "i";
}
if ($isFeatured === 1) {
    $where[] = "c.is_featured = 1";
}
if ($search) {
    $where[] = "(c.title LIKE ? OR c.short_description LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

$whereClause = implode(" AND ", $where);

$countSql = "SELECT COUNT(*) as total FROM courses c WHERE $whereClause";
$countStmt = $conn->prepare($countSql);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalCourses = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

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
// Propagate child counts up to parents
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

$sql = "SELECT c.id, c.title, c.slug, c.short_description, c.cover_image, c.price, c.is_free,
               c.difficulty, c.duration_hours, c.total_enrolled, c.is_featured, c.created_at,
               c.category_id,
               cat.name AS category_name, cat.slug AS category_slug,
               (SELECT COUNT(*) FROM exams e WHERE e.course_id = c.id AND e.status = 'active') AS direct_exam_count
        FROM courses c
        LEFT JOIN exam_categories cat ON c.category_id = cat.id
        WHERE $whereClause
        ORDER BY c.is_featured DESC, c.total_enrolled DESC, c.created_at DESC
        LIMIT ? OFFSET ?";

$params[] = $perPage;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$courses = [];
while ($row = $result->fetch_assoc()) {
    $catId = (int)$row['category_id'];
    $catExamCount = $catExamMap[$catId] ?? 0;
    $directCount = (int)$row['direct_exam_count'];
    // Use category tree count if available, otherwise fall back to direct count
    $examCount = $catExamCount > 0 ? $catExamCount : $directCount;

    $courses[] = [
        'id' => (int)$row['id'],
        'title' => $row['title'],
        'slug' => $row['slug'],
        'short_description' => $row['short_description'],
        'cover_image' => $row['cover_image'],
        'price' => (float)$row['price'],
        'is_free' => (int)$row['is_free'],
        'difficulty' => $row['difficulty'],
        'duration_hours' => (int)$row['duration_hours'],
        'total_enrolled' => (int)$row['total_enrolled'],
        'is_featured' => (int)$row['is_featured'],
        'exam_count' => $examCount,
        'category_name' => $row['category_name'],
        'category_slug' => $row['category_slug'],
        'created_at' => $row['created_at']
    ];
}
$stmt->close();

echo json_encode([
    'status' => 'success',
    'courses' => $courses,
    'total' => (int)$totalCourses,
    'page' => $page,
    'per_page' => $perPage,
    'total_pages' => max(1, ceil($totalCourses / $perPage)),
    'access' => 'unlimited'
]);
