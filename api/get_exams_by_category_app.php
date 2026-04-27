<?php
/**
 * GET EXAMS BY CATEGORY FOR APP
 * Requires: uid, season, u_state
 * Accepts Bearer token in header
 * 
 * Usage: /api/get_exams_by_category_app.php?category_id=1&uid=1&season=...&u_state=1
 * Header: Authorization: Bearer <token>
 */
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

$uid = $_GET['uid'] ?? null;
$season = $_GET['season'] ?? null;
$u_state = $_GET['u_state'] ?? null;

$security = requireAppSecurity($uid, $season, $u_state);

$token = getBearerToken();
if ($token) {
    $tokenVerify = verifyToken($token, $uid);
}

$conn = getAppSecurityConn();

$categoryId = intval($_GET['category_id'] ?? 0);
$direct = isset($_GET['direct']) ? ($_GET['direct'] === '1' || $_GET['direct'] === 'true') : true;

if ($categoryId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'category_id required']);
    exit;
}

$childCategories = [];
$exams = [];

if (!$direct) {
    function getAllChildCategoryIds($conn, $parentId, &$ids = []) {
        $ids[] = $parentId;
        $stmt = $conn->prepare("SELECT id, name, icon FROM exam_categories WHERE parent_id = ? AND is_active = 1 ORDER BY sort_order, name");
        $stmt->bind_param('i', $parentId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $childCategories[] = $row;
            getAllChildCategoryIds($conn, $row['id'], $ids);
        }
        $stmt->close();
        return $ids;
    }

    $categoryIds = getAllChildCategoryIds($conn, $categoryId);

    foreach ($childCategories as &$cat) {
        $catId = $cat['id'];
        $countStmt = $conn->prepare("SELECT COUNT(*) as c FROM exams WHERE category_id = ? AND status = 'active'");
        $countStmt->bind_param('i', $catId);
        $countStmt->execute();
        $cat['exam_count'] = $countStmt->get_result()->fetch_assoc()['c'];
        $countStmt->close();
    }
    unset($cat);

    $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
    $types = str_repeat('i', count($categoryIds));

    $stmt = $conn->prepare("
        SELECT e.id, e.title, e.duration_minutes, e.total_marks, e.passing_percentage,
               e.status, e.is_free, c.name AS category_name, c.id AS category_id
        FROM exams e
        JOIN exam_categories c ON e.category_id = c.id
        WHERE e.category_id IN ($placeholders) AND e.status = 'active'
        ORDER BY c.sort_order, c.name, e.created_at DESC
    ");
    $stmt->bind_param($types, ...$categoryIds);
    $stmt->execute();
    $exams = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $stmt = $conn->prepare("SELECT id, name, icon FROM exam_categories WHERE parent_id = ? AND is_active = 1 ORDER BY sort_order, name");
    $stmt->bind_param('i', $categoryId);
    $stmt->execute();
    $result = $stmt->get_result();
    $childCategories = [];
    while ($row = $result->fetch_assoc()) {
        $catId = $row['id'];
        $countStmt = $conn->prepare("SELECT COUNT(*) as c FROM exams WHERE category_id = ? AND status = 'active'");
        $countStmt->bind_param('i', $catId);
        $countStmt->execute();
        $row['exam_count'] = $countStmt->get_result()->fetch_assoc()['c'];
        $countStmt->close();
        $childCategories[] = $row;
    }
    $stmt->close();

    $stmt = $conn->prepare("
        SELECT e.id, e.title, e.duration_minutes, e.total_marks, e.passing_percentage,
               e.status, e.is_free, c.name AS category_name, c.id AS category_id
        FROM exams e
        JOIN exam_categories c ON e.category_id = c.id
        WHERE e.category_id = ? AND e.status = 'active'
        ORDER BY e.created_at DESC
    ");
    $stmt->bind_param('i', $categoryId);
    $stmt->execute();
    $exams = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

echo json_encode([
    'status' => 'success',
    'has_children' => !empty($childCategories),
    'child_categories' => $childCategories,
    'exams' => $exams ?: [],
    'access' => 'unlimited'
]);