<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status'=>'error','message'=>'Method not allowed']); exit;
}

$user = getCurrentUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['status'=>'error','message'=>'Please login first']); exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$courseId = intval($input['course_id'] ?? 0);

$conn = getDBConnection();
if ($courseId > 0) {
    $stmt = $conn->prepare("DELETE FROM carts WHERE user_id = ? AND course_id = ?");
    $stmt->bind_param('ii', $user['id'], $courseId);
    $stmt->execute();
    $stmt->close();
} else {
    $conn->prepare("DELETE FROM carts WHERE user_id = ?")->execute([$user['id']]);
}

echo json_encode(['status'=>'success','message'=>'Cart updated']);
