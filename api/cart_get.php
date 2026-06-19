<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json; charset=utf-8');

$user = getCurrentUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['status'=>'error','message'=>'Please login first']); exit;
}

$conn = getDBConnection();
$result = $conn->query("
    SELECT c.id AS cart_id, cr.id AS course_id, cr.title, cr.slug, cr.price, cr.is_free, 
           cr.cover_image, cr.duration_hours, cr.difficulty,
           cat.name AS category_name
    FROM carts c
    JOIN courses cr ON c.course_id = cr.id AND cr.is_active = 1
    LEFT JOIN exam_categories cat ON cr.category_id = cat.id
    WHERE c.user_id = {$user['id']}
    ORDER BY c.created_at DESC
");

$items = [];
$total = 0;
while ($row = $result->fetch_assoc()) {
    $price = floatval($row['price']);
    $total += $price;
    $items[] = $row;
}

echo json_encode([
    'status' => 'success',
    'count' => count($items),
    'total' => number_format($total, 2),
    'items' => $items
]);
