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

$logs = [];

function logAction($type, $target, $action, $status = 'success', $details = '') {
    global $logs;
    $logs[] = [
        'type' => $type,     // TABLE, COLUMN, DATABASE, DATA
        'target' => $target, // table name or column name
        'action' => $action, // CREATE, ALTER, INSERT, UPDATE
        'status' => $status, // success, info, warning, error
        'details' => $details,
        'time' => date('H:i:s')
    ];
}

// =======================
// CREATE DATABASE
// =======================
if ($server->query("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
    logAction('DATABASE', DB_NAME, 'CREATE', 'success', 'Database verified/created');
}
$server->select_db(DB_NAME);

// =======================
// TABLES CONFIG
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    'user_tokens' => "CREATE TABLE IF NOT EXISTS user_tokens (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        token VARCHAR(128) UNIQUE,
        expires_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'user_images' => "CREATE TABLE IF NOT EXISTS user_images (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        image_type VARCHAR(32) DEFAULT 'profile',
        image_path VARCHAR(255),
        image_url VARCHAR(255),
        is_primary TINYINT(1) DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'referral_logs' => "CREATE TABLE IF NOT EXISTS referral_logs (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        referrer_id INT UNSIGNED,
        new_user_id INT UNSIGNED,
        referral_code_used VARCHAR(32),
        status VARCHAR(32),
        reward_amount DECIMAL(10,2),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'referral_rewards' => "CREATE TABLE IF NOT EXISTS referral_rewards (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED,
        referral_log_id INT UNSIGNED,
        reward_type VARCHAR(64),
        amount DECIMAL(10,2),
        status VARCHAR(32),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'login_attempts' => "CREATE TABLE IF NOT EXISTS login_attempts (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        success TINYINT(1) NOT NULL DEFAULT 0,
        attempt_time DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_ip (ip_address),
        INDEX idx_attempt_time (attempt_time)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'app_settings' => "CREATE TABLE IF NOT EXISTS app_settings (
        setting_key VARCHAR(100) PRIMARY KEY,
        setting_value TEXT NULL,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'exam_categories' => "CREATE TABLE IF NOT EXISTS exam_categories (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'exams' => "CREATE TABLE IF NOT EXISTS exams (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        subject_id INT UNSIGNED NULL,
        category_id INT UNSIGNED NULL,
        course_id INT UNSIGNED DEFAULT NULL,
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'questions' => "CREATE TABLE IF NOT EXISTS questions (
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
        explanation TEXT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
        FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'exam_answers' => "CREATE TABLE IF NOT EXISTS exam_answers (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        exam_result_id INT UNSIGNED NOT NULL,
        question_id INT UNSIGNED NOT NULL,
        selected_option CHAR(1) DEFAULT NULL,
        is_correct TINYINT(1) DEFAULT 0,
        marks_obtained INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (exam_result_id) REFERENCES exam_results(id) ON DELETE CASCADE,
        FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
        INDEX idx_exam_result (exam_result_id),
        INDEX idx_question (question_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'exam_results' => "CREATE TABLE IF NOT EXISTS exam_results (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'daily_checkins' => "CREATE TABLE IF NOT EXISTS daily_checkins (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        checkin_date DATE NOT NULL,
        xp_earned INT DEFAULT 50,
        streak INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_checkin (user_id, checkin_date),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_date (user_id, checkin_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'subjects' => "CREATE TABLE IF NOT EXISTS subjects (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'courses' => "CREATE TABLE IF NOT EXISTS courses (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        short_description VARCHAR(255) DEFAULT NULL,
        description TEXT DEFAULT NULL,
        cover_image VARCHAR(255) DEFAULT NULL,
        price DECIMAL(10,2) DEFAULT 0.00,
        is_free TINYINT(1) DEFAULT 1,
        category_id INT UNSIGNED DEFAULT NULL,
        parent_course_id INT UNSIGNED DEFAULT NULL,
        course_type ENUM('academic','skill','other') DEFAULT 'skill',
        difficulty ENUM('beginner','intermediate','advanced') DEFAULT 'beginner',
        duration_hours INT DEFAULT 0,
        total_enrolled INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        is_featured TINYINT(1) DEFAULT 0,
        sort_order INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES exam_categories(id) ON DELETE SET NULL,
        FOREIGN KEY (parent_course_id) REFERENCES courses(id) ON DELETE CASCADE,
        INDEX idx_category (category_id),
        INDEX idx_active (is_active),
        INDEX idx_featured (is_featured)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'premium_purchases' => "CREATE TABLE IF NOT EXISTS premium_purchases (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        product_id VARCHAR(64) NOT NULL,
        purchase_token VARCHAR(512) NOT NULL,
        order_id VARCHAR(128) DEFAULT NULL,
        purchase_time DATETIME DEFAULT NULL,
        expiry_date DATETIME DEFAULT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user (user_id),
        INDEX idx_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'enrollments' => "CREATE TABLE IF NOT EXISTS enrollments (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        course_id INT UNSIGNED NOT NULL,
        enrolled_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        completed_at DATETIME DEFAULT NULL,
        progress DECIMAL(5,2) DEFAULT 0.00,
        status ENUM('active','completed','dropped','pending_payment') DEFAULT 'active',
        UNIQUE KEY unique_enrollment (user_id, course_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
        INDEX idx_user (user_id),
        INDEX idx_course (course_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'transactions' => "CREATE TABLE IF NOT EXISTS transactions (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        course_id INT UNSIGNED NOT NULL,
        enrollment_id INT UNSIGNED DEFAULT NULL,
        transaction_id VARCHAR(64) NOT NULL UNIQUE,
        piprapay_pp_id VARCHAR(128) DEFAULT NULL,
        amount DECIMAL(10,2) NOT NULL,
        currency VARCHAR(10) DEFAULT 'BDT',
        status ENUM('initiated','pending','completed','failed','refunded') DEFAULT 'initiated',
        gateway_response JSON DEFAULT NULL,
        initiated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        completed_at DATETIME DEFAULT NULL,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
        FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE SET NULL,
        INDEX idx_user (user_id),
        INDEX idx_course (course_id),
        INDEX idx_status (status),
        INDEX idx_txn_id (transaction_id),
        INDEX idx_pp_id (piprapay_pp_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

foreach ($tables as $name => $sql) {
    $check = $server->query("SHOW TABLES LIKE '$name'");
    if ($check->num_rows == 0) {
        if ($server->query($sql)) {
            logAction('TABLE', $name, 'CREATE', 'success', "Table '$name' created successfully");
        } else {
            logAction('TABLE', $name, 'CREATE', 'error', $server->error);
        }
    } else {
        logAction('TABLE', $name, 'CREATE', 'info', "Table '$name' already exists");
    }
}

// Alter enrollments table status column to include pending_payment if not already present
$checkEnrollStatus = $server->query("SHOW COLUMNS FROM enrollments LIKE 'status'");
if ($checkEnrollStatus && $checkEnrollStatus->num_rows > 0) {
    $row = $checkEnrollStatus->fetch_assoc();
    if (strpos($row['Type'], 'pending_payment') === false) {
        if ($server->query("ALTER TABLE enrollments MODIFY status ENUM('active','completed','dropped','pending_payment') DEFAULT 'active'")) {
            logAction('COLUMN', 'enrollments.status', 'ALTER', 'success', "Status enum updated to include pending_payment");
        } else {
            logAction('COLUMN', 'enrollments.status', 'ALTER', 'error', $server->error);
        }
    }
}


// =======================
// MIGRATIONS (COLUMNS)
// =======================
$migrations = [
    'users' => [
        "is_active TINYINT(1) DEFAULT 1",
        "language VARCHAR(10) DEFAULT 'en'",
        "tagline VARCHAR(255) DEFAULT NULL",
        "streak INT DEFAULT 0",
        "member_since DATE DEFAULT NULL",
        "is_premium TINYINT(1) DEFAULT 0",
        "role VARCHAR(32) DEFAULT NULL",
        "total_xp INT DEFAULT 0",
        "premium_expiry_date DATETIME DEFAULT NULL"
    ],
    'exams' => [
        "category_id INT UNSIGNED NULL AFTER subject_id",
        "description TEXT NULL AFTER title",
        "is_free TINYINT(1) DEFAULT 1 AFTER passing_percentage",
        "price DECIMAL(10,2) DEFAULT 0.00 AFTER is_free",
        "course_id INT UNSIGNED DEFAULT NULL AFTER category_id"
    ],
    'questions' => [
        "explanation TEXT NULL AFTER marks"
    ]
];

foreach ($migrations as $table => $cols) {
    foreach ($cols as $col) {
        $colName = explode(" ", $col)[0];
        $check = $server->query("SHOW COLUMNS FROM `$table` LIKE '$colName'");
        if ($check && $check->num_rows == 0) {
            if ($server->query("ALTER TABLE `$table` ADD COLUMN $col")) {
                logAction('COLUMN', "$table.$colName", 'ALTER', 'success', "Column added to '$table'");
            } else {
                logAction('COLUMN', "$table.$colName", 'ALTER', 'error', $server->error);
            }
        } else {
            logAction('COLUMN', "$table.$colName", 'ALTER', 'info', "Column verified");
        }
    }
}

// =======================
// SEED DEFAULT DATA
// =======================
$rootCheck = $server->query("SELECT id FROM exam_categories WHERE parent_id IS NULL LIMIT 1");
if ($rootCheck && $rootCheck->num_rows === 0) {
    $server->query("INSERT INTO exam_categories (name, slug, parent_id, level, category_type, icon) VALUES
        ('Academic', 'academic', NULL, 1, 'academic', 'fa-graduation-cap'),
        ('Job', 'job', NULL, 1, 'job', 'fa-briefcase'),
        ('General', 'general', NULL, 1, 'general', 'fa-book')");
    logAction('DATA', 'exam_categories', 'INSERT', 'success', 'Default root categories seeded');
}

// Default Admin
$adminCheck = $server->query("SELECT id FROM users WHERE role IN ('Administrator','admin') LIMIT 1");
if ($adminCheck && $adminCheck->num_rows == 0) {
    $name = 'Super Admin';
    $email = 'admin@shikhbo.com';
    $password = password_hash('Admin@123#Secure', PASSWORD_BCRYPT, ['cost' => 12]);
    $referral = 'ADMIN' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    $stmt = $server->prepare("INSERT INTO users (name, email, password, role, status, referral_code) VALUES (?, ?, ?, 'Administrator', 'active', ?)");
    $stmt->bind_param('ssss', $name, $email, $password, $referral);
    if ($stmt->execute()) {
        logAction('DATA', 'users', 'INSERT', 'success', "Default admin created: $email");
    }
    $stmt->close();
}

// =======================
// RESPONSE
// =======================
echo json_encode([
    'status' => 'success',
    'message' => 'Database fully synced & ready 🚀',
    'database' => DB_NAME,
    'logs' => $logs,
    'summary' => [
        'tables' => count($tables),
        'total_logs' => count($logs)
    ]
], JSON_PRETTY_PRINT);
