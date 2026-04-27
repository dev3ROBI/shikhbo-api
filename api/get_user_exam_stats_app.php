<?php
/**
 * GET USER EXAM STATS FOR APP
 * Returns user exam history, total scores, attendance, and results
 * 
 * Usage: POST /api/get_user_exam_stats_app.php
 * Header: Authorization: Bearer <token>
 * Body: {"uid":1, "season":"...", "u_state":"1"}
 */
require_once __DIR__ . '/../includes/app_security_validation.php';
require_once __DIR__ . '/../api/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit();
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$uid = isset($data['uid']) ? (int)$data['uid'] : 0;
$season = $data['season'] ?? null;
$u_state = $data['u_state'] ?? null;

$security = requireAppSecurity($uid, $season, $u_state);
$token = getBearerToken();
$conn = getAppSecurityConn();

$limit = isset($data['limit']) ? intval($data['limit']) : 10;
$offset = isset($data['offset']) ? intval($data['offset']) : 0;
$limit = max(1, min($limit, 50));
$offset = max(0, $offset);

function fetchSingleValue(mysqli $conn, string $sql, int $uid, string $alias, string $types = 'i', array $extraParams = [])
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return 0;
    }

    $params = array_merge([$uid], $extraParams);
    $bindParams = [$types];
    foreach ($params as $index => $value) {
        $bindParams[] = &$params[$index];
    }
    call_user_func_array([$stmt, 'bind_param'], $bindParams);

    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $row[$alias] ?? 0;
}

// Overall stats
$totalExamsTaken = (int) fetchSingleValue(
    $conn,
    "SELECT COUNT(*) as c FROM exam_results WHERE user_id = ?",
    $uid,
    'c'
);
$totalScore = (int) fetchSingleValue(
    $conn,
    "SELECT COALESCE(SUM(score), 0) as s FROM exam_results WHERE user_id = ?",
    $uid,
    's'
);
$totalMarks = (int) fetchSingleValue(
    $conn,
    "SELECT COALESCE(SUM(total_marks), 0) as m FROM exam_results WHERE user_id = ?",
    $uid,
    'm'
);
$avgPercentage = (float) fetchSingleValue(
    $conn,
    "SELECT COALESCE(AVG(percentage), 0) as a FROM exam_results WHERE user_id = ?",
    $uid,
    'a'
);
$passedCount = (int) fetchSingleValue(
    $conn,
    "SELECT COUNT(*) as c FROM exam_results WHERE user_id = ? AND status = 'passed'",
    $uid,
    'c'
);
$failedCount = (int) fetchSingleValue(
    $conn,
    "SELECT COUNT(*) as c FROM exam_results WHERE user_id = ? AND status = 'failed'",
    $uid,
    'c'
);

// Unique exams attempted
$uniqueExams = (int) fetchSingleValue(
    $conn,
    "SELECT COUNT(DISTINCT exam_id) as c FROM exam_results WHERE user_id = ?",
    $uid,
    'c'
);

// Recent exam history
$recentQuery = $conn->prepare("
    SELECT r.*, e.title as exam_title, e.category_id, c.name as category_name
    FROM exam_results r 
    JOIN exams e ON r.exam_id = e.id 
    LEFT JOIN exam_categories c ON e.category_id = c.id 
    WHERE r.user_id = ?
    ORDER BY r.completed_at DESC
    LIMIT ? OFFSET ?
");
$recentQuery->bind_param("iii", $uid, $limit, $offset);
$recentQuery->execute();
$recentResults = $recentQuery->get_result();

$recentExams = [];
while ($row = $recentResults->fetch_assoc()) {
    $recentExams[] = [
        'id' => (int)$row['id'],
        'exam_id' => (int)$row['exam_id'],
        'exam_title' => $row['exam_title'],
        'category_id' => (int)$row['category_id'],
        'category_name' => $row['category_name'] ?? '',
        'score' => (int)$row['score'],
        'total_marks' => (int)$row['total_marks'],
        'percentage' => round($row['percentage'], 1),
        'status' => $row['status'],
        'completed_at' => $row['completed_at']
    ];
}
$recentQuery->close();

// Best performing exam
$bestExam = null;
$bestExamQuery = $conn->prepare("
    SELECT e.title, r.percentage
    FROM exam_results r
    JOIN exams e ON r.exam_id = e.id
    WHERE r.user_id = ?
    ORDER BY r.percentage DESC, r.completed_at DESC, r.id DESC
    LIMIT 1
");
if ($bestExamQuery) {
    $bestExamQuery->bind_param("i", $uid);
    $bestExamQuery->execute();
    $bestExamResult = $bestExamQuery->get_result();
    $bestExam = $bestExamResult ? $bestExamResult->fetch_assoc() : null;
    $bestExamQuery->close();
}

// Category-wise performance
$categoryStats = [];
$categoryStatsQuery = $conn->prepare("
    SELECT c.name as category_name, c.id as category_id,
           COUNT(r.id) as exams_taken,
           AVG(r.percentage) as avg_percentage,
           SUM(CASE WHEN r.status = 'passed' THEN 1 ELSE 0 END) as passed,
           SUM(CASE WHEN r.status = 'failed' THEN 1 ELSE 0 END) as failed
    FROM exam_results r
    JOIN exams e ON r.exam_id = e.id
    LEFT JOIN exam_categories c ON e.category_id = c.id
    WHERE r.user_id = ?
    GROUP BY c.id, c.name
    ORDER BY exams_taken DESC
    LIMIT 5
");
if ($categoryStatsQuery) {
    $categoryStatsQuery->bind_param("i", $uid);
    $categoryStatsQuery->execute();
    $categoryStatsResult = $categoryStatsQuery->get_result();

    while ($categoryStatsResult && $row = $categoryStatsResult->fetch_assoc()) {
        $categoryStats[] = [
            'category_id' => (int)($row['category_id'] ?? 0),
            'category_name' => $row['category_name'] ?? 'Uncategorized',
            'exams_taken' => (int)$row['exams_taken'],
            'avg_percentage' => round((float)$row['avg_percentage'], 1),
            'passed' => (int)$row['passed'],
            'failed' => (int)$row['failed']
        ];
    }

    $categoryStatsQuery->close();
}

echo json_encode([
    'status' => 'success',
    'stats' => [
        'total_exams_taken' => (int)$totalExamsTaken,
        'unique_exams_attempted' => (int)$uniqueExams,
        'total_score' => (int)$totalScore,
        'total_marks' => (int)$totalMarks,
        'overall_percentage' => round($avgPercentage, 1),
        'passed_count' => (int)$passedCount,
        'failed_count' => (int)$failedCount,
        'pass_rate' => $totalExamsTaken > 0 ? round(($passedCount / $totalExamsTaken) * 100, 1) : 0,
        'best_exam' => $bestExam ? [
            'title' => $bestExam['title'],
            'percentage' => round($bestExam['percentage'], 1)
        ] : null
    ],
    'recent_exams' => $recentExams,
    'category_stats' => $categoryStats,
    'access' => 'unlimited'
]);
