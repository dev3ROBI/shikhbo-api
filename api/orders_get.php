<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json; charset=utf-8');

$user = getCurrentUser();
if (!$user) { http_response_code(401); echo json_encode(['status'=>'error','message'=>'Login required']); exit; }

$conn = getDBConnection();

// Auto-create tables if they don't exist
$conn->query("CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    order_id VARCHAR(64) NOT NULL UNIQUE,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending','completed','cancelled','refunded') DEFAULT 'pending',
    payment_method VARCHAR(32) DEFAULT 'piprapay',
    piprapay_pp_id VARCHAR(128) DEFAULT NULL,
    gateway_response JSON DEFAULT NULL,
    paid_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_ref VARCHAR(64) NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    enrollment_id INT UNSIGNED DEFAULT NULL,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE SET NULL,
    INDEX idx_order (order_ref),
    INDEX idx_course (course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS carts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_cart (user_id, course_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;
$statusFilter = $_GET['status'] ?? '';
$uid = intval($user['id']);

$where = "o.user_id = $uid";
if ($statusFilter) $where .= " AND o.status = '" . $conn->real_escape_string($statusFilter) . "'";

$countResult = $conn->query("SELECT COUNT(*) AS total FROM orders o WHERE $where");
if (!$countResult) {
    echo json_encode(['status'=>'error','message'=>'Database error: '.$conn->error]); exit;
}
$totalOrders = $countResult->fetch_assoc()['total'];

$result = $conn->query("
    SELECT o.id, o.order_id, o.total_amount, o.status, o.payment_method, o.created_at, o.paid_at,
           GROUP_CONCAT(DISTINCT cr.title SEPARATOR '||') AS course_titles,
           COUNT(DISTINCT oi.id) AS item_count
    FROM orders o
    LEFT JOIN order_items oi ON o.order_id = oi.order_ref
    LEFT JOIN courses cr ON oi.course_id = cr.id
    WHERE $where
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT $limit OFFSET $offset
");

if (!$result) {
    echo json_encode(['status'=>'error','message'=>'Query failed: '.$conn->error]); exit;
}

$orders = [];
while ($row = $result->fetch_assoc()) {
    $titles = !empty($row['course_titles']) ? explode('||', $row['course_titles']) : [];
    $row['course_list'] = $titles;
    unset($row['course_titles']);
    $orders[] = $row;
}

echo json_encode([
    'status' => 'success',
    'orders' => $orders,
    'total' => (int)$totalOrders,
    'page' => $page,
    'pages' => max(1, ceil($totalOrders / $limit))
]);
