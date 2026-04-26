<?php
/**
 * GET CATEGORIES FOR WEB/ADMIN PANEL
 */
require_once __DIR__ . '/../api/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
$conn->set_charset('utf8mb4');

$result = $conn->query("SELECT id, name, slug, parent_id, level, category_type FROM exam_categories ORDER BY parent_id, sort_order, id");
$categories = [];
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

echo json_encode([
    'status' => 'success',
    'categories' => $categories,
    'count' => count($categories),
    'source' => 'web'
]);