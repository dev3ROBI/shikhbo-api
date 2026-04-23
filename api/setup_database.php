<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$server = new mysqli(DB_HOST, DB_USER, DB_PASS);
if ($server->connect_error) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Could not connect to MySQL server',
        'details' => $server->connect_error
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

$server->set_charset('utf8mb4');
$server->query("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$server->select_db(DB_NAME);

$queries = [
    'users' => "
        CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(191) NOT NULL UNIQUE,
            password VARCHAR(255) NULL,
            google_login TINYINT(1) NOT NULL DEFAULT 0,
            status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
            referral_code VARCHAR(32) NULL UNIQUE,
            referred_by INT UNSIGNED NULL,
            google_id VARCHAR(191) NULL,
            device_id VARCHAR(191) NULL,
            ip_address VARCHAR(64) NULL,
            device_model VARCHAR(191) NULL,
            os_version VARCHAR(64) NULL,
            app_version VARCHAR(64) NULL,
            profile_image VARCHAR(255) NULL,
            last_login DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_users_email (email),
            INDEX idx_users_referral_code (referral_code),
            CONSTRAINT fk_users_referred_by FOREIGN KEY (referred_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    'user_tokens' => "
        CREATE TABLE IF NOT EXISTS user_tokens (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            token VARCHAR(128) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_tokens_user_id (user_id),
            INDEX idx_user_tokens_expires_at (expires_at),
            CONSTRAINT fk_user_tokens_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    'login_attempts' => "
        CREATE TABLE IF NOT EXISTS login_attempts (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(191) NOT NULL,
            ip_address VARCHAR(64) NOT NULL,
            success TINYINT(1) NOT NULL DEFAULT 0,
            attempt_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_login_attempts_email (email),
            INDEX idx_login_attempts_ip (ip_address),
            INDEX idx_login_attempts_time (attempt_time)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    'user_images' => "
        CREATE TABLE IF NOT EXISTS user_images (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            image_type VARCHAR(32) NOT NULL DEFAULT 'profile',
            image_path VARCHAR(255) NULL,
            image_url VARCHAR(255) NULL,
            is_primary TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_images_user_id (user_id),
            CONSTRAINT fk_user_images_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    'referral_logs' => "
        CREATE TABLE IF NOT EXISTS referral_logs (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            referrer_id INT UNSIGNED NOT NULL,
            new_user_id INT UNSIGNED NOT NULL,
            referral_code_used VARCHAR(32) NOT NULL,
            status VARCHAR(32) NOT NULL DEFAULT 'completed',
            reward_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_referral_logs_referrer (referrer_id),
            INDEX idx_referral_logs_new_user (new_user_id),
            CONSTRAINT fk_referral_logs_referrer FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_referral_logs_new_user FOREIGN KEY (new_user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    'referral_rewards' => "
        CREATE TABLE IF NOT EXISTS referral_rewards (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            referral_log_id INT UNSIGNED NOT NULL,
            reward_type VARCHAR(64) NOT NULL,
            amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            status VARCHAR(32) NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            credited_at DATETIME NULL,
            INDEX idx_referral_rewards_user (user_id),
            INDEX idx_referral_rewards_log (referral_log_id),
            CONSTRAINT fk_referral_rewards_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_referral_rewards_log FOREIGN KEY (referral_log_id) REFERENCES referral_logs(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    "
];

$created = [];
$errors = [];

foreach ($queries as $table => $sql) {
    if ($server->query($sql)) {
        $created[] = $table;
    } else {
        $errors[$table] = $server->error;
    }
}

echo json_encode([
    'status' => empty($errors) ? 'success' : 'partial',
    'database' => DB_NAME,
    'tables_processed' => $created,
    'errors' => $errors,
    'message' => empty($errors)
            ? 'Database and auth tables are ready for app testing.'
            : 'Some tables could not be created. Check the errors field.'
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
