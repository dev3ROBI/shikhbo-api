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

$defaults = [
    'app_notice' => 'Welcome to Shikhbo. Keep learning every day.',
    'support_email' => 'support@shikhbo.com',
    'highlight_course' => 'English for Beginners',
    'maintenance_mode' => 'off',
    'maintenance_title' => 'We are improving Shikhbo',
    'maintenance_message' => 'The app is temporarily unavailable while we apply updates. Please check back shortly.',
    'maintenance_eta' => 'Back very soon',
    'maintenance_break_time' => '15-20 minutes',
    'maintenance_details' => 'Server upgrade, bug fixes, better performance and smoother sync.',
    'maintenance_status_note' => 'Live maintenance in progress',
    'latest_version' => '1.0.0',
    'force_update' => '0',
    'update_url' => ''
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
