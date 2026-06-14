<?php
/**
 * GET COURSE CATEGORY CONTENT FOR APP
 * Uses course's category_id to fetch exam categories, subcategories,
 * exams with question counts, and user results
 * 
 * GET params: course_id, uid, season, u_state
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

// Get course category_id
$courseStmt = $conn->prepare("SELECT id, title, category_id FROM courses WHERE id = ? AND is_active = 1");
$courseStmt->bind_param('i', $courseId);
$courseStmt->execute();
$course = $courseStmt->get_result()->fetch_assoc();
$courseStmt->close();

if (!$course) {
    echo json_encode(['status' => 'error', 'message' => 'Course not found']);
    exit;
}

if (!$course['category_id']) {
    echo json_encode([
        'status' => 'success',
        'category' => null,
        'subcategories' => [],
        'exams' => [],
        'access' => 'unlimited'
    ]);
    exit;
}

// Get root category info
$catStmt = $conn->prepare("SELECT id, name, slug, icon, category_type FROM exam_categories WHERE id = ? AND is_active = 1");
$catStmt->bind_param('i', $course['category_id']);
$catStmt->execute();
$category = $catStmt->get_result()->fetch_assoc();
$catStmt->close();

if (!$category) {
    echo json_encode([
        'status' => 'success',
        'category' => null,
        'subcategories' => [],
        'exams' => [],
        'access' => 'unlimited'
    ]);
    exit;
}

// Helper: get exams with user results for a set of category IDs
function getExamsForCategoryIds($conn, $categoryIds, $uid) {
    if (empty($categoryIds)) return [];
    $ph = implode(',', array_fill(0, count($categoryIds), '?'));
    $types = str_repeat('i', count($categoryIds));
    $sql = "
        SELECT e.id, e.title, e.duration_minutes, e.total_marks, e.passing_percentage,
               e.is_free, e.status,
               (SELECT COUNT(*) FROM questions q WHERE q.exam_id = e.id) AS question_count,
               (SELECT MAX(er.percentage) FROM exam_results er WHERE er.exam_id = e.id AND er.user_id = ?) AS user_best_score,
               (SELECT COUNT(*) FROM exam_results er WHERE er.exam_id = e.id AND er.user_id = ?) AS user_attempt_count,
               (SELECT er.status FROM exam_results er WHERE er.exam_id = e.id AND er.user_id = ? ORDER BY er.id DESC LIMIT 1) AS user_last_status
        FROM exams e
        WHERE e.category_id IN ($ph) AND e.status = 'active'
        ORDER BY e.created_at DESC
    ";
    $stmt = $conn->prepare($sql);
    $allParams = array_merge([$uid, $uid, $uid], $categoryIds);
    $allTypes = str_repeat('i', 3) . $types;
    $stmt->bind_param($allTypes, ...$allParams);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $exams = [];
    foreach ($rows as $row) {
        $best = $row['user_best_score'] !== null ? round((float)$row['user_best_score'], 1) : null;
        $exams[] = [
            'id' => (int)$row['id'],
            'title' => $row['title'],
            'duration_minutes' => (int)$row['duration_minutes'],
            'total_marks' => (int)$row['total_marks'],
            'passing_percentage' => (int)$row['passing_percentage'],
            'is_free' => (int)$row['is_free'],
            'question_count' => (int)$row['question_count'],
            'user_best_score' => $best,
            'user_attempt_count' => (int)$row['user_attempt_count'],
            'user_last_status' => $row['user_last_status']
        ];
    }
    return $exams;
}

// Helper: recursively get all child category IDs
function getAllChildIds($conn, $parentId) {
    $ids = [(int)$parentId];
    $stmt = $conn->prepare("SELECT id FROM exam_categories WHERE parent_id = ? AND is_active = 1");
    $stmt->bind_param('i', $parentId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $childIds = getAllChildIds($conn, $row['id']);
        $ids = array_merge($ids, $childIds);
    }
    $stmt->close();
    return $ids;
}

// Get immediate child categories with exam counts
$subcatStmt = $conn->prepare("
    SELECT ec.id, ec.name, ec.slug, ec.icon,
           (SELECT COUNT(*) FROM exams e WHERE e.category_id = ec.id AND e.status = 'active') AS exam_count
    FROM exam_categories ec
    WHERE ec.parent_id = ? AND ec.is_active = 1
    ORDER BY ec.sort_order, ec.name
");
$subcatStmt->bind_param('i', $course['category_id']);
$subcatStmt->execute();
$subcats = $subcatStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$subcatStmt->close();

// Get all category IDs in the tree
$allCategoryIds = getAllChildIds($conn, $course['category_id']);

$subcategories = [];
$directExams = [];

if (count($subcats) > 0) {
    // Has subcategories: build subcategories with their own exams
    foreach ($subcats as $sc) {
        $scId = (int)$sc['id'];
        $scChildIds = getAllChildIds($conn, $scId);
        $exams = getExamsForCategoryIds($conn, $scChildIds, $uid);
        $subcategories[] = [
            'id' => $scId,
            'name' => $sc['name'],
            'slug' => $sc['slug'],
            'icon' => $sc['icon'],
            'exam_count' => (int)$sc['exam_count'],
            'exams' => $exams
        ];
    }
} else {
    // No subcategories: fetch exams directly under this category
    $directExams = getExamsForCategoryIds($conn, [$course['category_id']], $uid);
}

// Get total exam count across all categories
$totalPlaceholders = implode(',', array_fill(0, count($allCategoryIds), '?'));
$totalTypes = str_repeat('i', count($allCategoryIds));
$totalStmt = $conn->prepare("SELECT COUNT(*) AS c FROM exams WHERE category_id IN ($totalPlaceholders) AND status = 'active'");
$totalStmt->bind_param($totalTypes, ...$allCategoryIds);
$totalStmt->execute();
$totalExams = (int)$totalStmt->get_result()->fetch_assoc()['c'];
$totalStmt->close();

echo json_encode([
    'status' => 'success',
    'category' => [
        'id' => (int)$category['id'],
        'name' => $category['name'],
        'slug' => $category['slug'],
        'icon' => $category['icon'],
        'category_type' => $category['category_type'],
        'total_exams' => $totalExams
    ],
    'subcategories' => $subcategories,
    'exams' => $directExams,
    'access' => 'unlimited'
]);
