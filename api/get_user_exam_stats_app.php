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

$uid = $data['uid'] ?? null;
$season = $data['season'] ?? null;
$u_state = $data['u_state'] ?? null;

$security = requireAppSecurity($uid, $season, $u_state);
$token = getBearerToken();
$conn = getAppSecurityConn();

$limit = isset($data['limit']) ? intval($data['limit']) : 10;
$offset = isset($data['offset']) ? intval($data['offset']) : 0;

// Overall stats
$totalExamsTaken = $conn->query("SELECT COUNT(*) as c FROM exam_results WHERE user_id = $uid")->fetch_assoc()['c'];
$totalScore = $conn->query("SELECT COALESCE(SUM(score), 0) as s FROM exam_results WHERE user_id = $uid")->fetch_assoc()['s'];
$totalMarks = $conn->query("SELECT COALESCE(SUM(total_marks), 0) as m FROM exam_results WHERE user_id = $uid")->fetch_assoc()['m'];
$avgPercentage = $conn->query("SELECT COALESCE(AVG(percentage), 0) as a FROM exam_results WHERE user_id = $uid")->fetch_assoc()['a'];
$passedCount = $conn->query("SELECT COUNT(*) as c FROM exam_results WHERE user_id = $uid AND status = 'passed'")->fetch_assoc()['c'];
$failedCount = $conn->query("SELECT COUNT(*) as c FROM exam_results WHERE user_id = $uid AND status = 'failed'")->fetch_assoc()['c'];

// Unique exams attempted
$uniqueExams = $conn->query("SELECT COUNT(DISTINCT exam_id) as c FROM exam_results WHERE user_id = $uid")->fetch_assoc()['c'];

// Recent exam history
$recentQuery = $conn->prepare("
    SELECT r.*, e.title as exam_title, e.category_id, c.name as category_name,
           ROW_NUMBER() OVER (PARTITION BY r.exam_id ORDER BY r.completed_at DESC) as rn
    FROM exam_results r 
    JOIN exams e ON r.exam_id = e.id 
    LEFT JOIN categories c ON e.category_id = c.id 
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
$bestExam = $conn->query("
    SELECT e.title, r.percentage 
    FROM exam_results r 
    JOIN exams e ON r.exam_id = e.id 
    WHERE r.user_id = $uid 
    ORDER BY r.percentage DESC 
    LIMIT 1
")->fetch_assoc();

// Category-wise performance
$categoryStatsQuery = $conn->query("
    SELECT c.name as category_name, c.id as category_id,
           COUNT(r.id) as exams_taken,
           AVG(r.percentage) as avg_percentage,
           SUM(CASE WHEN r.status = 'passed' THEN 1 ELSE 0 END) as passed,
           SUM(CASE WHEN r.status = 'failed' THEN 1 ELSE 0 END) as failed
    FROM exam_results r
    JOIN exams e ON r.exam_id = e.id
    LEFT JOIN categories c ON e.category_id = c.id
    WHERE r.user_id = $uid
    GROUP BY c.id, c.name
    ORDER BY exams_taken DESC
    LIMIT 5
");

$categoryStats = [];
while ($row = $categoryStatsQuery->fetch_assoc()) {
    $categoryStats[] = [
        'category_id' => (int)$row['category_id'],
        'category_name' => $row['category_name'] ?? 'Uncategorized',
        'exams_taken' => (int)$row['exams_taken'],
        'avg_percentage' => round($row['avg_percentage'], 1),
        'passed' => (int)$row['passed'],
        'failed' => (int)$row['failed']
    ];
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