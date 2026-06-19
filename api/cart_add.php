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
if ($courseId <= 0) {
    echo json_encode(['status'=>'error','message'=>'course_id required']); exit;
}

$conn = getDBConnection();
$stmt = $conn->prepare("SELECT id, is_free FROM courses WHERE id = ? AND is_active = 1");
$stmt->bind_param('i', $courseId);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$course) {
    echo json_encode(['status'=>'error','message'=>'Course not found']); exit;
}

$stmt = $conn->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ? AND status = 'active'");
$stmt->bind_param('ii', $user['id'], $courseId);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
    $stmt->close();
    echo json_encode(['status'=>'error','message'=>'Already enrolled']); exit;
}
$stmt->close();

$stmt = $conn->prepare("INSERT IGNORE INTO carts (user_id, course_id) VALUES (?, ?)");
$stmt->bind_param('ii', $user['id'], $courseId);
$stmt->execute();
$inserted = $stmt->affected_rows;
$stmt->close();

echo json_encode([
    'status' => $inserted > 0 ? 'success' : 'info',
    'message' => $inserted > 0 ? 'Added to cart' : 'Already in cart'
]);
