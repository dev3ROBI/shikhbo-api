<?php
/**
 * GET CATEGORIES FOR APP
 * Requires: Bearer token in header
 * Returns unlimited access for authenticated users
 */
require_once __DIR__ . '/../includes/app_security_validation.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Cache-Control: public, max-age=300');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$token = getBearerToken();

$uid = $_GET['uid'] ?? null;
$season = $_GET['season'] ?? null;
$u_state = $_GET['u_state'] ?? null;

$security = requireAppSecurity($uid, $season, $u_state);

if ($token) {
    $tokenVerify = verifyToken($token, $uid);
}

$conn = getAppSecurityConn();

$result = $conn->query("
    SELECT ec.id, ec.name, ec.slug, ec.parent_id, ec.level, ec.category_type,
           ec.icon, ec.description,
           (SELECT COUNT(*) FROM exams e WHERE e.category_id = ec.id AND e.status = 'active') AS exam_count
    FROM exam_categories ec
    ORDER BY ec.parent_id, ec.sort_order, ec.id
");

$catsById = [];
while ($row = $result->fetch_assoc()) {
    $row['exam_count'] = (int)$row['exam_count'];
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
        'access' => 'unlimited'
    ]
]);
