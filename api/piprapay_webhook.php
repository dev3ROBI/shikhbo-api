<?php
require_once __DIR__ . '/config.php';

$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput, true);

file_put_contents(__DIR__ . '/webhook_debug.log', date('Y-m-d H:i:s') . ' — ' . $rawInput . PHP_EOL, FILE_APPEND);

$ppId = $payload['pp_id'] ?? '';
$status = strtolower($payload['status'] ?? '');
$metadata = $payload['metadata'] ?? '';

$orderRef = null;
if ($metadata) {
    $metaData = is_string($metadata) ? json_decode($metadata, true) : $metadata;
    $orderRef = $metaData['order_id'] ?? null;
}

if (!$orderRef && $ppId) {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    if ($conn->connect_error) {
        http_response_code(500);
        exit;
    }
    $stmt = $conn->prepare("SELECT order_id FROM orders WHERE piprapay_pp_id = ? LIMIT 1");
    $stmt->bind_param('s', $ppId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $orderRef = $row['order_id'] ?? null;
    $conn->close();
}

if (!$orderRef) {
    http_response_code(200);
    echo 'OK';
    exit;
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
if ($conn->connect_error) {
    http_response_code(500);
    exit;
}

$stmt = $conn->prepare("SELECT status FROM orders WHERE order_id = ?");
$stmt->bind_param('s', $orderRef);
$stmt->execute();
$orderRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$orderRow || $orderRow['status'] === 'completed') {
    $conn->close();
    http_response_code(200);
    echo 'OK';
    exit;
}

if ($status === 'completed') {
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE orders SET status='completed', piprapay_pp_id=?, gateway_response=?, paid_at=NOW() WHERE order_id=?");
        $stmt->bind_param('sss', $ppId, $rawInput, $orderRef);
        $stmt->execute();

        $items = $conn->query("SELECT oi.*, cr.id AS cid FROM order_items oi JOIN courses cr ON oi.course_id=cr.id WHERE oi.order_ref='" . $conn->real_escape_string($orderRef) . "'");
        while ($item = $items->fetch_assoc()) {
            $eid = $item['enrollment_id'];
            if ($eid) {
                $stmt2 = $conn->prepare("UPDATE enrollments SET status='active', enrolled_at=NOW() WHERE id=?");
                if ($stmt2) {
                    $stmt2->bind_param('i', $eid);
                    $stmt2->execute();
                    $stmt2->close();
                }
            }
            $conn->query("UPDATE courses SET total_enrolled=(SELECT COUNT(*) FROM enrollments WHERE course_id={$item['cid']} AND status='active') WHERE id={$item['cid']}");
        }
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        file_put_contents(__DIR__ . '/webhook_debug.log', date('Y-m-d H:i:s') . ' — ERROR: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
    }
} elseif ($status === 'failed' || $status === 'cancelled') {
    $stmt3 = $conn->prepare("UPDATE orders SET status='cancelled', gateway_response=? WHERE order_id=?");
    if ($stmt3) {
        $stmt3->bind_param('ss', $rawInput, $orderRef);
        $stmt3->execute();
        $stmt3->close();
    }
}

$conn->close();
http_response_code(200);
echo 'OK';
