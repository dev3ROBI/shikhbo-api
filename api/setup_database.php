<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

// =======================
// CONNECT MYSQL SERVER
// =======================
$server = new mysqli(DB_HOST, DB_USER, DB_PASS, null, DB_PORT);

if ($server->connect_error) {
    die(json_encode([
        'status' => 'error',
        'message' => 'MySQL connection failed',
        'error' => $server->connect_error
    ]));
}

$server->set_charset('utf8mb4');

// =======================
// CREATE DATABASE
// =======================
$server->query("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$server->select_db(DB_NAME);

// =======================
// CREATE USERS TABLE (FULL)
// =======================
$server->query("
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(191) NOT NULL UNIQUE,
    password VARCHAR(255) NULL,
    google_login TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('active','inactive','suspended') DEFAULT 'active',
    referral_code VARCHAR(32) UNIQUE,
    referred_by INT UNSIGNED NULL,
    google_id VARCHAR(191) NULL,
    device_id VARCHAR(191) NULL,
    ip_address VARCHAR(64) NULL,
    device_model VARCHAR(191) NULL,
    os_version VARCHAR(64) NULL,
    app_version VARCHAR(64) NULL,
    profile_image VARCHAR(255) NULL,
    last_login DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// =======================
// AUTO ADD MISSING COLUMNS (MIGRATION)
// =======================
$columns = [
    "google_id VARCHAR(191) NULL",
    "device_id VARCHAR(191) NULL",
    "ip_address VARCHAR(64) NULL",
    "device_model VARCHAR(191) NULL",
    "os_version VARCHAR(64) NULL",
    "app_version VARCHAR(64) NULL",
    "profile_image VARCHAR(255) NULL",
    "last_login DATETIME NULL",
    "updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
];

foreach ($columns as $col) {
    $colName = explode(" ", $col)[0];
    $check = $server->query("SHOW COLUMNS FROM users LIKE '$colName'");
    
    if ($check->num_rows == 0) {
        $server->query("ALTER TABLE users ADD COLUMN $col");
    }
}

// =======================
// USER TOKENS TABLE
// =======================
$server->query("
CREATE TABLE IF NOT EXISTS user_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token VARCHAR(128) UNIQUE,
    expires_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// =======================
// USER IMAGES TABLE
// =======================
$server->query("
CREATE TABLE IF NOT EXISTS user_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    image_type VARCHAR(32) DEFAULT 'profile',
    image_path VARCHAR(255),
    image_url VARCHAR(255),
    is_primary TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// =======================
// REFERRAL TABLES
// =======================
$server->query("
CREATE TABLE IF NOT EXISTS referral_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    referrer_id INT UNSIGNED,
    new_user_id INT UNSIGNED,
    referral_code_used VARCHAR(32),
    status VARCHAR(32),
    reward_amount DECIMAL(10,2),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$server->query("
CREATE TABLE IF NOT EXISTS referral_rewards (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED,
    referral_log_id INT UNSIGNED,
    reward_type VARCHAR(64),
    amount DECIMAL(10,2),
    status VARCHAR(32),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// =======================
// RESPONSE
// =======================
echo json_encode([
    'status' => 'success',
    'message' => 'Database fully synced & ready 🚀',
    'database' => DB_NAME
], JSON_PRETTY_PRINT);
?>
