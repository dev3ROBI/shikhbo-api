<?php
/**
 * Get exam categories - APP API (Requires security params)
 * 
 * GET /api/get_categories.php?uid=1&season=2024-01-01 12:00:00&u_state=1
 * GET /api/get_categories.php?parent_id=5&uid=1&season=__&u_state=1
 */
require_once __DIR__ . '/../includes/app_security.php';
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-App-Language');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Security parameters (required)
$uid = $_GET['uid'] ?? null;
$season = $_GET['season'] ?? null;
$u_state = $_GET['u_state'] ?? null;

// If no security params, reject request
if (!$uid || !$season || !$u_state) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Missing security parameters',
        'code' => 'SECURITY_PARAMS_REQUIRED'
    ]);
    exit();
}

// Verify user is active
if ($u_state != '1') {
    http_response_code(403);
    echo json_encode([
        'status' => 'error', 
        'message' => 'User is not active',
        'code' => 'USER_NOT_ACTIVE'
    ]);
    exit();
}

// Verify season hasn't expired
$season_expires = strtotime($season);
if ($season_expires < time()) {
    http_response_code(403);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Season expired. Login again.',
        'code' => 'SEASON_EXPIRED'
    ]);
    exit();
}

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

$mysqli->set_charset('utf8mb4');

$parentId = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : 0;

if ($parentId > 0) {
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
} else {
    $result = $mysqli->query("
        SELECT id, name, slug, parent_id, level, icon, category_type
        FROM exam_categories
        WHERE is_active = 1 AND parent_id = 0
        ORDER BY sort_order, id
    ");
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $childStmt = $mysqli->prepare("
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
    'security' => [
        'uid' => (int)$uid,
        'season' => $season,
        'user_active' => (bool)$u_state
    ]
]);