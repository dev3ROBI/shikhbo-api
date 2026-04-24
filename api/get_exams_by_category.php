<?php
/**
 * Get exams by category (MySQL 5.x compatible)
 */
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

$mysqli->set_charset('utf8mb4');

$categoryId = intval($_GET['category_id'] ?? 0);

if ($categoryId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'category_id required']);
    exit;
}

// Recursive function to get all child category IDs
function getAllChildCategoryIds($mysqli, $parentId, &$ids = []) {
    $ids[] = $parentId;
    $stmt = $mysqli->prepare("SELECT id FROM exam_categories WHERE parent_id = ? AND is_active = 1");
    $stmt->bind_param('i', $parentId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        getAllChildCategoryIds($mysqli, $row['id'], $ids);
    }
    $stmt->close();
    return $ids;
}

$categoryIds = getAllChildCategoryIds($mysqli, $categoryId);
$placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
$types = str_repeat('i', count($categoryIds));

$query = "
    SELECT e.id, e.title, e.duration_minutes, e.total_marks, e.passing_percentage,
           e.status, c.name AS category_name
    FROM exams e
    INNER JOIN exam_categories c ON e.category_id = c.id
    WHERE e.category_id IN ($placeholders)
      AND e.status = 'active'
    ORDER BY e.created_at DESC
";

$stmt = $mysqli->prepare($query);
$stmt->bind_param($types, ...$categoryIds);
$stmt->execute();
$exams = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode([
    'status' => 'success',
    'exams' => $exams
], JSON_PRETTY_PRINT);