<?php
/**
 * GET CATEGORIES FOR APP
 * Requires: uid, season, u_state
 */
require_once __DIR__ . '/../includes/app_security_validation.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
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

$result = $conn->query("SELECT id, name, slug, parent_id, level, category_type FROM exam_categories ORDER BY parent_id, sort_order, id");

$catsById = [];
while ($row = $result->fetch_assoc()) {
    $catsById[$row['id']] = $row;
}

$rootCategories = [];
foreach ($catsById as $id => $cat) {
    if ($cat['parent_id'] == '' || $cat['parent_id'] == null) {
        $rootCategories[] = $cat;
    }
}

foreach ($rootCategories as $i => $root) {
    $children = [];
    foreach ($catsById as $id => $cat) {
        if ($cat['parent_id'] == $root['id']) {
            $children[] = $cat;
        }
    }
    if (!empty($children)) {
        $rootCategories[$i]['children'] = $children;
    }
}

echo json_encode([
    'status' => 'success',
    'categories' => $rootCategories,
    'user_info' => [
        'uid' => (int)$uid,
        'season' => $season,
        'requests_remaining' => $security['remaining'] ?? 100
    ]
]);