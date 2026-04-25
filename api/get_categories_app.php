<?php
/**
 * Get Categories for APP - Requires user_id, user_season, user_active
 * 
 * GET /api/get_categories_app.php?uid=1&season=2024-01-01 12:00:00&u_state=1
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

$uid = $_GET['uid'] ?? null;
$season = $_GET['season'] ?? null;
$u_state = $_GET['u_state'] ?? null;

if (!$uid || !$season || !$u_state) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters: uid, season, u_state']);
    exit();
}

if ($u_state != '1') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'User is not active']);
    exit();
}

$season_expires = strtotime($season);
if ($season_expires < time()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Season expired. Login again.']);
    exit();
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit();
}
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
        WHERE is_active = 1
        ORDER BY parent_id, sort_order, id
    ");
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

echo json_encode([
    'status' => 'success',
    'categories' => $categories,
    'user_info' => [
        'uid' => (int)$uid,
        'season' => $season,
        'user_active' => (bool)$u_state
    ]
]);