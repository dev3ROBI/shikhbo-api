<?php
/**
 * ENROLL / UNENROLL COURSE FOR APP
 * POST params: course_id, action (enroll or unenroll)
 */
require_once __DIR__ . '/../includes/app_security_validation.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$uid = $input['uid'] ?? $_POST['uid'] ?? null;
$season = $input['season'] ?? $_POST['season'] ?? null;
$u_state = $input['u_state'] ?? $_POST['u_state'] ?? null;
$security = requireAppSecurity($uid, $season, $u_state);

$token = getBearerToken();
if ($token) {
    $tokenVerify = verifyToken($token, $uid);
}

$conn = getAppSecurityConn();

$courseId = intval($input['course_id'] ?? $_POST['course_id'] ?? 0);
$action = $input['action'] ?? $_POST['action'] ?? 'enroll';

if ($courseId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'course_id required']);
    exit;
}

// Verify course exists and is active
$courseCheck = $conn->prepare("SELECT id, is_free FROM courses WHERE id = ? AND is_active = 1");
$courseCheck->bind_param('i', $courseId);
$courseCheck->execute();
$course = $courseCheck->get_result()->fetch_assoc();
$courseCheck->close();

if (!$course) {
    echo json_encode(['status' => 'error', 'message' => 'Course not found or inactive']);
    exit;
}

if ($action === 'enroll') {
    // Check if already enrolled
    $existing = $conn->prepare("SELECT id, status FROM enrollments WHERE user_id = ? AND course_id = ?");
    $existing->bind_param('ii', $uid, $courseId);
    $existing->execute();
    $enrollment = $existing->get_result()->fetch_assoc();
    $existing->close();

    if ($course['is_free'] == 0) {
        if ($enrollment && $enrollment['status'] === 'active') {
            echo json_encode(['status' => 'success', 'message' => 'Already enrolled', 'is_enrolled' => true]);
            exit;
        }
        // Paid course requires payment flow
        echo json_encode([
            'status' => 'payment_required',
            'message' => 'Payment required for this course',
            'course_id' => $courseId,
            'is_enrolled' => false
        ]);
        exit;
    }

    if ($enrollment) {
        if ($enrollment['status'] === 'active') {
            echo json_encode(['status' => 'success', 'message' => 'Already enrolled', 'is_enrolled' => true]);
            exit;
        }
        // Re-activate dropped/completed enrollment
        $reactivate = $conn->prepare("UPDATE enrollments SET status = 'active', enrolled_at = NOW(), completed_at = NULL WHERE id = ?");
        $reactivate->bind_param('i', $enrollment['id']);
        $reactivate->execute();
        $reactivate->close();
    } else {
        $insert = $conn->prepare("INSERT INTO enrollments (user_id, course_id) VALUES (?, ?)");
        $insert->bind_param('ii', $uid, $courseId);
        $insert->execute();
        $insert->close();
    }

    // Update enrollment count
    $conn->query("UPDATE courses SET total_enrolled = (SELECT COUNT(*) FROM enrollments WHERE course_id = $courseId AND status = 'active') WHERE id = $courseId");

    echo json_encode(['status' => 'success', 'message' => 'Enrolled successfully', 'is_enrolled' => true, 'access' => 'unlimited']);

} elseif ($action === 'unenroll') {
    $stmt = $conn->prepare("UPDATE enrollments SET status = 'dropped' WHERE user_id = ? AND course_id = ? AND status = 'active'");
    $stmt->bind_param('ii', $uid, $courseId);
    $stmt->execute();
    $stmt->close();

    $conn->query("UPDATE courses SET total_enrolled = (SELECT COUNT(*) FROM enrollments WHERE course_id = $courseId AND status = 'active') WHERE id = $courseId");

    echo json_encode(['status' => 'success', 'message' => 'Unenrolled', 'is_enrolled' => false, 'access' => 'unlimited']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
