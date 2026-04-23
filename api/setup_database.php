<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// =======================
// CONNECT MYSQL SERVER
// =======================
$server = new mysqli(DB_HOST, DB_USER, DB_PASS, null, DB_PORT);

if ($server->connect_error) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'MySQL server connection failed',
        'error' => $server->connect_error
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$server->set_charset('utf8mb4');

// =======================
// CREATE DATABASE
// =======================
if (!$server->query("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
    die(json_encode([
        'status' => 'error',
        'message' => 'Database creation failed',
        'error' => $server->error
    ]));
}

$server->select_db(DB_NAME);

// =======================
// TABLE CREATION FUNCTION
// =======================
function runQuery($db, $sql) {
    if (!$db->query($sql)) {
        return $db->error;
    }
    return true;
}

// =======================
// TABLES
// =======================
$tables = [
    'users' => "CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(191) NOT NULL UNIQUE,
        password VARCHAR(255) NULL,
        google_login TINYINT(1) NOT NULL DEFAULT 0,
        status ENUM('active','inactive','suspended') DEFAULT 'active',
        referral_code VARCHAR(32) UNIQUE,
        referred_by INT UNSIGNED NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'user_tokens' => "CREATE TABLE IF NOT EXISTS user_tokens (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        token VARCHAR(128) UNIQUE,
        expires_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

$created = [];
$errors = [];

foreach ($tables as $name => $sql) {
    $result = runQuery($server, $sql);

    if ($result === true) {
        $created[] = $name;
    } else {
        $errors[$name] = $result;
    }
}

// =======================
// RESPONSE
// =======================
echo json_encode([
    'status' => empty($errors) ? 'success' : 'partial',
    'database' => DB_NAME,
    'created_tables' => $created,
    'errors' => $errors,
    'message' => empty($errors)
        ? 'Database setup completed successfully 🚀'
        : 'Some tables failed to create'
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

?>
