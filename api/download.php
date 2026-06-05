<?php
require_once __DIR__ . '/config.php';

// Prevent hotlinking — require a valid Referer from our own site
$allowedHosts = ['shikhbo.kesug.com', 'localhost', '127.0.0.1'];
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$refHost = parse_url($referer, PHP_URL_HOST);
$isValidRef = false;
foreach ($allowedHosts as $h) {
    if ($refHost === $h || str_ends_with($refHost, '.' . $h)) {
        $isValidRef = true;
        break;
    }
}
// Allow direct access in dev (optional — remove in production)
if (!$isValidRef && php_sapi_name() !== 'cli') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Direct access not allowed']);
    exit;
}

$filePath = __DIR__ . '/../downloads/shikhbo-v2.1.0.apk';

if (!file_exists($filePath)) {
    header('HTTP/1.1 404 Not Found');
    echo json_encode(['error' => 'File not found']);
    exit;
}

$fileName = basename($filePath);
$fileSize = filesize($filePath);

header('Content-Description: File Transfer');
header('Content-Type: application/vnd.android.package-archive');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Length: ' . $fileSize);
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

readfile($filePath);
exit;
