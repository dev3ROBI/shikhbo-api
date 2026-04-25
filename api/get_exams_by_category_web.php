<?php
/**
 * GET EXAMS BY CATEGORY FOR WEB/ADMIN PANEL
 * No security required - for web and admin panel
 * 
 * Usage: /api/get_exams_by_category_web.php?category_id=1
 */
require_once __DIR__ . '/../api/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
$conn->set_charset('utf8mb4');

$categoryId = intval($_GET['category_id'] ?? 0);
$direct = isset($_GET['direct']) ? ($_GET['direct'] === '1' || $_GET['direct'] === 'true') : true;

if ($categoryId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'category_id required']);
    exit;
}

if ($direct) {
    $stmt = $conn->prepare("
        SELECT e.id, e.title, e.duration_minutes, e.total_marks, e.passing_percentage,
               e.status, e.is_free, c.name AS category_name
        FROM exams e
        JOIN exam_categories c ON e.category_id = c.id
        WHERE e.category_id = ? AND e.status = 'active'
        ORDER BY e.created_at DESC
    ");
    $stmt->bind_param('i', $categoryId);
    $stmt->execute();
    $exams = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    function getAllChildCategoryIds($conn, $parentId, &$ids = []) {
        $ids[] = $parentId;
        $stmt = $conn->prepare("SELECT id FROM exam_categories WHERE parent_id = ? AND is_active = 1");
        $stmt->bind_param('i', $parentId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            getAllChildCategoryIds($conn, $row['id'], $ids);
        }
        $stmt->close();
        return $ids;
    }

    $categoryIds = getAllChildCategoryIds($conn, $categoryId);
    $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
    $types = str_repeat('i', count($categoryIds));

    $stmt = $conn->prepare("
        SELECT e.id, e.title, e.duration_minutes, e.total_marks, e.passing_percentage,
               e.status, e.is_free, c.name AS category_name
        FROM exams e
        JOIN exam_categories c ON e.category_id = c.id
        WHERE e.category_id IN ($placeholders) AND e.status = 'active'
        ORDER BY e.created_at DESC
    ");
    $stmt->bind_param($types, ...$categoryIds);
    $stmt->execute();
    $exams = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

echo json_encode([
    'status' => 'success',
    'exams' => $exams ?: [],
    'source' => 'web'
]);