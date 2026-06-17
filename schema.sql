-- ============================================================
-- Shikhbo API - Database Schema
-- Database: if0_40799763_shikhbo
-- Engine: MySQL (InnoDB, utf8mb4)
-- ============================================================

CREATE DATABASE IF NOT EXISTS `if0_40799763_shikhbo`
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `if0_40799763_shikhbo`;

-- ============================================================
-- USERS
-- Core user table for both app users and admins.
-- role = 'admin' distinguishes admin accounts.
-- ============================================================
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
    is_active TINYINT(1) DEFAULT 1,
    language VARCHAR(10) DEFAULT 'en',
    tagline VARCHAR(255) DEFAULT NULL,
    streak INT DEFAULT 0,
    member_since DATE DEFAULT NULL,
    is_premium TINYINT(1) DEFAULT 0,
    premium_expiry_date DATETIME DEFAULT NULL,
    role VARCHAR(32) DEFAULT NULL,
    last_login DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- USER TOKENS
-- Bearer tokens for mobile/web API authentication.
-- ============================================================
CREATE TABLE IF NOT EXISTS user_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token VARCHAR(128) UNIQUE,
    expires_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- USER IMAGES
-- Profile images uploaded by users.
-- ============================================================
CREATE TABLE IF NOT EXISTS user_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    image_type VARCHAR(32) DEFAULT 'profile',
    image_path VARCHAR(255),
    image_url VARCHAR(255),
    is_primary TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- REFERRAL LOGS
-- Tracks referrals made by users.
-- ============================================================
CREATE TABLE IF NOT EXISTS referral_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    referrer_id INT UNSIGNED,
    new_user_id INT UNSIGNED,
    referral_code_used VARCHAR(32),
    status VARCHAR(32),
    reward_amount DECIMAL(10,2),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- REFERRAL REWARDS
-- Rewards earned from referrals.
-- ============================================================
CREATE TABLE IF NOT EXISTS referral_rewards (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED,
    referral_log_id INT UNSIGNED,
    reward_type VARCHAR(64),
    amount DECIMAL(10,2),
    status VARCHAR(32),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- LOGIN ATTEMPTS
-- Used for rate limiting failed login attempts.
-- ============================================================
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- APP SETTINGS
-- Key-value store for app-level configuration
-- (maintenance mode, force update, notices, etc.)
-- ============================================================
CREATE TABLE IF NOT EXISTS app_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SUBJECTS
-- Exam subjects (e.g., Mathematics, English, Science).
-- ============================================================
CREATE TABLE IF NOT EXISTS subjects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- EXAM CATEGORIES (Multi-Level Hierarchy)
-- Hierarchical categories (Academic > University > Science).
-- Supports unlimited nesting via parent_id self-reference.
-- ============================================================
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- EXAMS
-- Exam definitions linked to a subject and category.
-- ============================================================
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- QUESTIONS
-- Questions linked to an exam, with 4 options (A-D).
-- ============================================================
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
    explanation TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- EXAM RESULTS
-- Stores each user's attempt results for an exam.
-- ============================================================
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- EXAM ANSWERS
-- Stores each user's per-question answers for an exam attempt.
-- ============================================================
CREATE TABLE IF NOT EXISTS exam_answers (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- PREMIUM PURCHASES
-- Tracks all premium plan purchases (Google Play).
-- ============================================================
CREATE TABLE IF NOT EXISTS premium_purchases (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- DEFAULT DATA
-- ============================================================

-- Default root categories
INSERT INTO exam_categories (name, slug, parent_id, level, category_type, icon)
SELECT * FROM (
    SELECT 'Academic' AS name, 'academic' AS slug, NULL AS parent_id, 1 AS level, 'academic' AS category_type, 'fa-graduation-cap' AS icon
    UNION ALL
    SELECT 'Job', 'job', NULL, 1, 'job', 'fa-briefcase'
    UNION ALL
    SELECT 'General', 'general', NULL, 1, 'general', 'fa-book'
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM exam_categories WHERE parent_id IS NULL LIMIT 1);

-- Note: The default admin account is created automatically by
-- running api/setup_database.php (password: Admin@123#Secure).
-- To create manually, use PHP's password_hash() for the bcrypt hash.
