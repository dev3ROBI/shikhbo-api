<?php
/**
 * GET CATEGORIES FOR WEB/ADMIN PANEL
 * No security required - for web and admin panel
 * 
 * Usage: /api/get_categories_web.php
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

$parentId = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : 0;

if ($parentId > 0) {
    $stmt = $conn->prepare("
        SELECT id, name, slug, parent_id, level, icon, category_type
        FROM exam_categories
        WHERE parent_id = ? AND is_active = 1
        ORDER BY sort_order, id
    ");
    $stmt->bind_param('i', $parentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    $stmt->close();
} else {
    $result = $conn->query("
        SELECT id, name, slug, parent_id, level, icon, category_type
        FROM exam_categories
        WHERE is_active = 1 AND parent_id = 0
        ORDER BY sort_order, id
    ");
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $childStmt = $conn->prepare("
            SELECT id, name, slug, parent_id, level, icon, category_type
            FROM exam_categories
            WHERE parent_id = ? AND is_active = 1
            ORDER BY sort_order, id
        ");
        $childStmt->bind_param('i', $row['id']);
        $childStmt->execute();
        $childResult = $childStmt->get_result();
        $children = [];
        while ($child = $childResult->fetch_assoc()) {
            $children[] = $child;
        }
        $childStmt->close();
        
        if (!empty($children)) {
            $row['children'] = $children;
        }
        $categories[] = $row;
    }
}

echo json_encode([
    'status' => 'success',
    'categories' => $categories,
    'source' => 'web'
]);