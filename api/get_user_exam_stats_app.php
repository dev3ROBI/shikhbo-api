<?php
/**
 * GET USER EXAM STATS FOR APP
 * Returns user exam history, total scores, attendance, and results
 * 
 * Usage: POST /api/get_user_exam_stats_app.php
 * Header: Authorization: Bearer <token>
 * Body: {"uid":1, "season":"...", "u_state":"1"}
 */
error_reporting(0);
ini_set('display_errors', 0);

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

try {
    require_once __DIR__ . '/../includes/app_security_validation.php';
    require_once __DIR__ . '/../api/config.php';

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['uid']) || !isset($input['season']) || !isset($input['u_state'])) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
        exit();
    }

    $uid = $input['uid'];
    $season = $input['season'];
    $u_state = $input['u_state'];

    // Validate security
    $security = requireAppSecurity($uid, $season, $u_state);

    $conn = getAppSecurityConn();
    $conn->set_charset('utf8mb4');

    $limit = isset($input['limit']) ? intval($input['limit']) : 10;
    $offset = isset($input['offset']) ? intval($input['offset']) : 0;
    $limit = max(1, min($limit, 50));
    $offset = max(0, $offset);

    // Overall stats - simplified queries
    $totalExamsTaken = 0;
    $totalScore = 0;
    $totalMarks = 0;
    $avgPercentage = 0;
    $passedCount = 0;
    $failedCount = 0;

    try {
        $result = $conn->query("SELECT COUNT(*) as c FROM exam_results WHERE user_id = $uid");
        if ($result) {
            $row = $result->fetch_assoc();
            $totalExamsTaken = (int)$row['c'];
        }
    } catch (Exception $e) {}

    try {
        $result = $conn->query("SELECT COALESCE(SUM(score), 0) as s FROM exam_results WHERE user_id = $uid");
        if ($result) {
            $row = $result->fetch_assoc();
            $totalScore = (int)$row['s'];
        }
    } catch (Exception $e) {}

    try {
        $result = $conn->query("SELECT COALESCE(SUM(total_marks), 0) as m FROM exam_results WHERE user_id = $uid");
        if ($result) {
            $row = $result->fetch_assoc();
            $totalMarks = (int)$row['m'];
        }
    } catch (Exception $e) {}

    try {
        $result = $conn->query("SELECT COALESCE(AVG(percentage), 0) as a FROM exam_results WHERE user_id = $uid");
        if ($result) {
            $row = $result->fetch_assoc();
            $avgPercentage = (float)$row['a'];
        }
    } catch (Exception $e) {}

    try {
        $result = $conn->query("SELECT COUNT(*) as c FROM exam_results WHERE user_id = $uid AND status = 'passed'");
        if ($result) {
            $row = $result->fetch_assoc();
            $passedCount = (int)$row['c'];
        }
    } catch (Exception $e) {}

    try {
        $result = $conn->query("SELECT COUNT(*) as c FROM exam_results WHERE user_id = $uid AND status = 'failed'");
        if ($result) {
            $row = $result->fetch_assoc();
            $failedCount = (int)$row['c'];
        }
    } catch (Exception $e) {}

    // Unique exams attempted
    $uniqueExams = 0;
    try {
        $result = $conn->query("SELECT COUNT(DISTINCT exam_id) as c FROM exam_results WHERE user_id = $uid");
        if ($result) {
            $row = $result->fetch_assoc();
            $uniqueExams = (int)$row['c'];
        }
    } catch (Exception $e) {}

    // Recent exam history
    $recentExams = [];
    try {
        // Direct query for debugging
        $sql = "SELECT r.id, r.exam_id, e.title as exam_title, e.category_id, c.name as category_name,
                r.score, r.total_marks, r.percentage, r.status, r.completed_at
        FROM exam_results r 
        JOIN exams e ON r.exam_id = e.id 
        LEFT JOIN categories c ON e.category_id = c.id 
        WHERE r.user_id = $uid
        ORDER BY r.completed_at DESC
        LIMIT $limit OFFSET $offset";
        
        $result = $conn->query($sql);
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
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
        }
    } catch (Exception $e) {
        // ignore
    }

    // Best performing exam
    $bestExam = null;
    try {
        $result = $conn->query("
            SELECT e.title, r.percentage 
            FROM exam_results r 
            JOIN exams e ON r.exam_id = e.id 
            WHERE r.user_id = $uid 
            ORDER BY r.percentage DESC 
            LIMIT 1
        ");
        if ($result && $row = $result->fetch_assoc()) {
            $bestExam = [
                'title' => $row['title'],
                'percentage' => round($row['percentage'], 1)
            ];
        }
    } catch (Exception $e) {}

    // Category-wise performance
    $categoryStats = [];
    try {
        $result = $conn->query("
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

        while ($row = $result->fetch_assoc()) {
            $categoryStats[] = [
                'category_id' => (int)$row['category_id'],
                'category_name' => $row['category_name'] ?? 'Uncategorized',
                'exams_taken' => (int)$row['exams_taken'],
                'avg_percentage' => round($row['avg_percentage'], 1),
                'passed' => (int)$row['passed'],
                'failed' => (int)$row['failed']
            ];
        }
    } catch (Exception $e) {}

    $passRate = $totalExamsTaken > 0 ? round(($passedCount / $totalExamsTaken) * 100, 1) : 0;

    echo json_encode([
        'status' => 'success',
        'stats' => [
            'total_exams_taken' => $totalExamsTaken,
            'unique_exams_attempted' => $uniqueExams,
            'total_score' => $totalScore,
            'total_marks' => $totalMarks,
            'overall_percentage' => round($avgPercentage, 1),
            'passed_count' => $passedCount,
            'failed_count' => $failedCount,
            'pass_rate' => $passRate,
            'best_exam' => $bestExam
        ],
        'recent_exams' => $recentExams,
        'category_stats' => $categoryStats,
        'access' => 'unlimited'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
