<?php
/**
 * Quick SQL Migration Fix
 * Run this to add missing columns to users table
 * 
 * Access: /api/migrate_users.php
 */

require_once __DIR__ . '/api/config.php';

header('Content-Type: application/json; charset=utf-8');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
$conn->set_charset('utf8mb4');

$migrations = [
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS language VARCHAR(10) DEFAULT 'en'",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS tagline VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS streak INT DEFAULT 0",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS member_since DATE DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS is_premium TINYINT(1) DEFAULT 0"
];

$results = [];
$allSuccess = true;

foreach ($migrations as $sql) {
    try {
        $conn->query($sql);
        $results[] = ['sql' => $sql, 'status' => 'success'];
    } catch (Exception $e) {
        $results[] = ['sql' => $sql, 'status' => 'error', 'message' => $e->getMessage()];
        $allSuccess = false;
    }
}

echo json_encode([
    'status' => $allSuccess ? 'success' : 'partial',
    'message' => 'User table migration completed',
    'results' => $results
]);