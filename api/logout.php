<?php
// logout.php
require_once 'connection.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $token = trim($input['token'] ?? '');

    if (empty($token)) {
        echo json_encode(["status" => "error", "message" => "Token is required"]);
        exit;
    }

    try {
        // Delete the token from database
        $stmt = $conn->prepare("DELETE FROM user_tokens WHERE token = ?");
        $stmt->bind_param("s", $token);
        
        if ($stmt->execute()) {
            echo json_encode([
                "status" => "success", 
                "message" => "Logged out successfully"
            ]);
        } else {
            echo json_encode([
                "status" => "success", 
                "message" => "Logged out locally"
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            "status" => "success", 
            "message" => "Logged out (server error ignored)"
        ]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
}
?>
