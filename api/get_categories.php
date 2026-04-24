<?php
/**
 * Get exam categories (tree or children)
 * 
 * GET /api/get_categories.php
 * GET /api/get_categories.php?parent_id=5
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

$parentId = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : null;

if ($parentId !== null) {
    // নির্দিষ্ট parent-এর children দিন
    $stmt = $mysqli->prepare("
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

    echo json_encode([
        'status' => 'success',
        'categories' => $categories
    ], JSON_PRETTY_PRINT);
} else {
    // সম্পূর্ণ tree দিন (mobile app-এর জন্য)
    $result = $mysqli->query("
        SELECT id, name, slug, parent_id, level, icon, category_type
        FROM exam_categories
        WHERE is_active = 1
        ORDER BY parent_id, sort_order, id
    ");

    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }

    function buildTree(array $elements, $parentId = null) {
        $branch = [];
        foreach ($elements as $element) {
            if ($element['parent_id'] == $parentId) {
                $children = buildTree($elements, $element['id']);
                if ($children) {
                    $element['children'] = $children;
                }
                $branch[] = $element;
            }
        }
        return $branch;
    }

    $tree = buildTree($categories);

    echo json_encode([
        'status' => 'success',
        'categories' => $tree
    ], JSON_PRETTY_PRINT);
}