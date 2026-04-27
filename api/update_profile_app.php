<?php
/**
 * UPDATE PROFILE FOR APP
 * Requires: uid, season, u_state in body
 * Accepts Bearer token in Authorization header
 * 
 * Usage: POST /api/update_profile_app.php
 * Header: Authorization: Bearer <token>
 * Body: {"name":"...", "email":"...", "profile_image":"...", "uid":1, "season":"...", "u_state":"1"}
 */
require_once __DIR__ . '/../includes/app_security_validation.php';
require_once __DIR__ . '/../api/config.php';

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
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

$uid = $input['uid'] ?? null;
$season = $input['season'] ?? null;
$u_state = $input['u_state'] ?? null;

// Validate security
$security = requireAppSecurity($uid, $season, $u_state);

$conn = getAppSecurityConn();
$conn->set_charset('utf8mb4');

// Get Bearer token from header
$token = getBearerToken();

// Verify token if provided
if ($token) {
    $tokenVerify = verifyToken($token, $uid);
    if (!$tokenVerify['valid']) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Invalid token"], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

try {
    require_once __DIR__ . '/../includes/app_security_validation.php';
    require_once __DIR__ . '/../api/config.php';

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['uid']) || !isset($input['season']) || !isset($input['u_state'])) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
        exit();
    }

    $uid = $input['uid'];
    $season = $input['season'];
    $u_state = $input['u_state'];

    // Validate security
    $security = requireAppSecurity($uid, $season, $u_state);

    $conn = getAppSecurityConn();
    $conn->set_charset('utf8mb4');

    // Get Bearer token from header
    $token = getBearerToken();

    // Verify token if provided
    if ($token) {
        $tokenVerify = verifyToken($token, $uid);
        if (!$tokenVerify['valid']) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Invalid token"], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $profileImage = trim($input['profile_image'] ?? '');

    if (empty($name) || empty($email)) {
        echo json_encode(["status" => "error", "message" => "Name and email are required"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["status" => "error", "message" => "Invalid email format"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!preg_match('/^[\p{L}\s.\'-]+$/u', $name) || mb_strlen($name) < 2 || mb_strlen($name) > 50) {
        echo json_encode(["status" => "error", "message" => "Name must be 2 to 50 characters"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, profile_image = ? WHERE id = ?");
    $stmt->bind_param("sssi", $name, $email, $profileImage, $uid);

    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "message" => "Profile updated successfully",
            "access" => "unlimited"
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Could not update profile"
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}