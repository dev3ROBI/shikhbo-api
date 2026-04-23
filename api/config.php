<?php

// =======================
// STATIC DATABASE CONFIG
// =======================
define('DB_HOST', 'mysql-production-556a.up.railway.app');
define('DB_NAME', 'shikhbo');
define('DB_USER', 'robi');
define('DB_PASS', 'Nafia2Naoshin');
define('DB_PORT', 3306);

// =======================
// MYSQL CONNECTION
// =======================
$conn = new mysqli(
    DB_HOST,
    DB_USER,
    DB_PASS,
    DB_NAME,
    DB_PORT
);

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode([
        "status" => "error",
        "message" => "DB Connection failed",
        "error" => $conn->connect_error
    ]));
}

$conn->set_charset("utf8mb4");

// =======================
// GOOGLE OAUTH
// =======================
define('GOOGLE_CLIENT_ID_WEB', '151985259285-nvemiiq9gg5lh7ap27vcrv25jv930ddm.apps.googleusercontent.com');
define('GOOGLE_CLIENT_ID_ANDROID', '151985259285-9vp42do9jbkl0gv5rv25hhi3u74t7sp9.apps.googleusercontent.com');

// =======================
// JWT SECRET
// =======================
define('JWT_SECRET', '?j=EaT(6LHCV]C=B6[E_EaA4HD)D7e2zRQ7S|XPxFRR[FFEXmo`;~uA>CJ_3MCA');

// =======================
// CORS
// =======================
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// =======================
// ERROR REPORTING
// =======================
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
