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
    "updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
    "role VARCHAR(32) DEFAULT NULL"
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
// LOGIN ATTEMPTS TABLE
// =======================
$server->query("
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    attempt_time DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_ip (ip_address),
    INDEX idx_attempt_time (attempt_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// =======================
// subjects
// =======================
$server->query("
CREATE TABLE IF NOT EXISTS subjects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// =======================
// exams
// =======================
$server->query("
CREATE TABLE IF NOT EXISTS exams (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    subject_id INT UNSIGNED NULL,
    exam_date DATETIME NULL,
    duration_minutes INT DEFAULT 60,
    total_marks INT DEFAULT 100,
    passing_percentage DECIMAL(5,2) DEFAULT 40.00,
    status ENUM('draft','active','completed') DEFAULT 'draft',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// =======================
// questions
// =======================
$server->query("
CREATE TABLE IF NOT EXISTS questions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exam_id INT UNSIGNED NULL,
    subject_id INT UNSIGNED NULL,
    question_text TEXT NOT NULL,
    option_a VARCHAR(255),
    option_b VARCHAR(255),
    option_c VARCHAR(255),
    option_d VARCHAR(255),
    correct_answer CHAR(1) NOT NULL,
    marks INT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// =======================
// exam_results
// =======================
$server->query("
CREATE TABLE IF NOT EXISTS exam_results (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    exam_id INT UNSIGNED NOT NULL,
    score INT DEFAULT 0,
    total_marks INT DEFAULT 100,
    percentage DECIMAL(5,2) DEFAULT 0.00,
    status ENUM('pending','passed','failed') DEFAULT 'pending',
    started_at DATETIME NULL,
    completed_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// =======================
// DEFAULT ADMIN CREATION (only if no admin exists)
// =======================
$adminCheck = $server->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
if ($adminCheck->num_rows == 0) {
    // No admin yet → create default
    $name = 'Super Admin';
    $email = 'admin@shikhbo.com';
    $password = password_hash('Admin@123#Secure', PASSWORD_BCRYPT, ['cost' => 12]);
    $referral = 'ADMIN' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

    $stmt = $server->prepare("INSERT INTO users (name, email, password, role, status, referral_code) VALUES (?, ?, ?, 'admin', 'active', ?)");
    $stmt->bind_param('ssss', $name, $email, $password, $referral);
    $stmt->execute();
    $stmt->close();
}

// =======================
// RESPONSE
// =======================
echo json_encode([
    'status' => 'success',
    'message' => 'Database fully synced & ready 🚀 (Admin panel tables included)',
    'database' => DB_NAME,
    'default_admin' => 'admin@shikhbo.com / Admin@123#Secure (change immediately)'
], JSON_PRETTY_PRINT);
?>