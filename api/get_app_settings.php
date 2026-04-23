<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed'
    ]);
    exit;
}

$conn->query("
    CREATE TABLE IF NOT EXISTS app_settings (
        setting_key VARCHAR(100) PRIMARY KEY,
        setting_value TEXT NULL,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$defaults = [
    'app_notice' => 'Welcome to Shikhbo. Keep learning every day.',
    'support_email' => 'support@shikhbo.com',
    'maintenance_mode' => 'off',
    'maintenance_title' => 'We are improving Shikhbo',
    'maintenance_message' => 'The app is temporarily unavailable while we apply updates. Please check back shortly.',
    'maintenance_eta' => 'Back very soon',
    'maintenance_end_at' => '',
    'maintenance_break_time' => '15-20 minutes',
    'maintenance_details' => 'Server upgrade, bug fixes, better performance and smoother sync.',
    'maintenance_status_note' => 'Live maintenance in progress',
    'latest_version' => '1.0.0',
    'highlight_course' => 'English for Beginners'
];

$result = $conn->query("SELECT setting_key, setting_value FROM app_settings");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $defaults[$row['setting_key']] = $row['setting_value'];
    }
}

echo json_encode([
    'status' => 'success',
    'server_time' => date('c'),
    'data' => $defaults
], JSON_UNESCAPED_UNICODE);
