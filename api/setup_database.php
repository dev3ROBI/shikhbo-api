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
    "is_active TINYINT(1) DEFAULT 1",
    "language VARCHAR(10) DEFAULT 'en'",
    "tagline VARCHAR(255) DEFAULT NULL",
    "streak INT DEFAULT 0",
    "member_since DATE DEFAULT NULL",
    "is_premium TINYINT(1) DEFAULT 0",
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
// APP SETTINGS TABLE
// =======================
$server->query("
CREATE TABLE IF NOT EXISTS app_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// =======================
// NEW: EXAM CATEGORIES (Multi-Level)
// =======================
$server->query("
CREATE TABLE IF NOT EXISTS exam_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    parent_id INT UNSIGNED DEFAULT NULL,
    level TINYINT UNSIGNED NOT NULL DEFAULT 1,
    category_type ENUM('academic','job','general','other') DEFAULT 'academic',
    icon VARCHAR(64) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES exam_categories(id) ON DELETE CASCADE,
    INDEX idx_parent (parent_id),
    INDEX idx_slug (slug),
    INDEX idx_type (category_type),
    INDEX idx_level (level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// =======================
// SEED DEFAULT ROOT CATEGORIES
// =======================
$rootCheck = $server->query("SELECT id FROM exam_categories WHERE parent_id IS NULL LIMIT 1");
if ($rootCheck->num_rows === 0) {
    $server->query("
        INSERT INTO exam_categories (name, slug, parent_id, level, category_type, icon) VALUES
        ('Academic', 'academic', NULL, 1, 'academic', 'fa-graduation-cap'),
        ('Job', 'job', NULL, 1, 'job', 'fa-briefcase'),
        ('General', 'general', NULL, 1, 'general', 'fa-book')
    ");
}

// =======================
// MIGRATE EXAMS TABLE (add category_id)
// =======================
$checkExamCol = $server->query("SHOW COLUMNS FROM exams LIKE 'category_id'");
if ($checkExamCol->num_rows == 0) {
    // Add category_id and adjust
    $server->query("ALTER TABLE exams 
        ADD COLUMN category_id INT UNSIGNED NULL AFTER subject_id,
        ADD COLUMN description TEXT NULL AFTER title,
        ADD COLUMN is_free TINYINT(1) DEFAULT 1 AFTER passing_percentage,
        ADD COLUMN price DECIMAL(10,2) DEFAULT 0.00 AFTER is_free
    ");
    // Add foreign key if possible (skip if table has data incompatible)
    // We'll do a soft addition, actual relation can be applied later
}

// =======================
// EXAMS TABLE (if not exists)
// =======================
$server->query("
CREATE TABLE IF NOT EXISTS exams (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    subject_id INT UNSIGNED NULL,
    category_id INT UNSIGNED NULL,
    description TEXT NULL,
    exam_date DATETIME NULL,
    duration_minutes INT DEFAULT 60,
    total_marks INT DEFAULT 100,
    passing_percentage DECIMAL(5,2) DEFAULT 40.00,
    is_free TINYINT(1) DEFAULT 1,
    price DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('draft','active','completed') DEFAULT 'draft',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// =======================
// QUESTIONS TABLE
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
// EXAM RESULTS TABLE
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
// SUBJECTS TABLE (if not exists)
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
// DEFAULT ADMIN CREATION
// =======================
$adminCheck = $server->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
if ($adminCheck->num_rows == 0) {
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
    'message' => 'Database fully synced & ready 🚀 (Multi-level categories included)',
    'database' => DB_NAME,
    'default_admin' => 'admin@shikhbo.com / Admin@123#Secure (change immediately)',
    'tables_created' => ['users','user_tokens','user_images','referral_logs','referral_rewards','login_attempts','app_settings','subjects','exam_categories','exams','questions','exam_results']
], JSON_PRETTY_PRINT);