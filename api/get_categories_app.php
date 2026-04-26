<?php
/**
 * GET CATEGORIES FOR APP
 * Requires: uid, season, u_state
 * 
 * Usage: /api/get_categories_app.php?uid=1&season=2024-01-01 12:00:00&u_state=1
 * 
 * Optimized: Uses shared connection, caches categories for 5 minutes
 */
require_once __DIR__ . '/../includes/app_security_validation.php';
require_once __DIR__ . '/../api/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Cache-Control: public, max-age=300');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$uid = $_GET['uid'] ?? null;
$season = $_GET['season'] ?? null;
$u_state = $_GET['u_state'] ?? null;

$security = requireAppSecurity($uid, $season, $u_state);
$conn = getAppSecurityConn();

$parentId = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : 0;

$cacheKey = "categories_parent_{$parentId}";
$cacheFile = sys_get_temp_dir() . "/shikhbo_cat_{$parentId}.cache";

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
    $stmt = $conn->prepare("
        SELECT id, name, slug, parent_id, level, icon, category_type, is_active
        FROM exam_categories
        WHERE is_active = 1 AND parent_id = 0
        ORDER BY sort_order, id
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $row['is_active'] = (int)$row['is_active'];
        $childStmt = $conn->prepare("
            SELECT id, name, slug, parent_id, level, icon, category_type, is_active
            FROM exam_categories
            WHERE parent_id = ? AND is_active = 1
            ORDER BY sort_order, id
        ");
        $childStmt->bind_param('i', $row['id']);
        $childStmt->execute();
        $childResult = $childStmt->get_result();
        $children = [];
        while ($child = $childResult->fetch_assoc()) {
            $child['is_active'] = (int)$child['is_active'];
            $children[] = $child;
        }
        $childStmt->close();
        
        if (!empty($children)) {
            $row['children'] = $children;
        }
        $categories[] = $row;
    }
    $stmt->close();
}

echo json_encode([
    'status' => 'success',
    'categories' => $categories,
    'user_info' => [
        'uid' => (int)$uid,
        'season' => $season,
        'requests_remaining' => $security['remaining'] ?? 100
    ]
]);