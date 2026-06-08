<?php
/**
 * Analytics Data API
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get parameters
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-30 days'));
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;

// If not admin, only show own data
if (!isAdmin()) {
    $user_id = $_SESSION['user_id'];
}

// Validate dates
if (!strtotime($date_from) || !strtotime($date_to)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
    exit;
}

try {
    $db = getDB();
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    // Build base query conditions
    $conditions = "WHERE DATE(checkin_time) BETWEEN :date_from AND :date_to";
    $params = [
        ':date_from' => $date_from,
        ':date_to' => $date_to
    ];
    
    if ($user_id) {
        $conditions .= " AND user_id = :user_id";
        $params[':user_id'] = $user_id;
    }
    
    // Get daily hours
    $sql = "
        SELECT 
            DATE(checkin_time) as date,
            SUM(duration_minutes) as total_minutes
        FROM attendance 
        $conditions
        GROUP BY DATE(checkin_time)
        ORDER BY date ASC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $dailyRecords = $stmt->fetchAll();
    
    // Format daily hours
    $dailyHours = [];
    foreach ($dailyRecords as $record) {
        $hours = round($record['total_minutes'] / 60, 1);
        $dailyHours[] = [
            'date' => date('M d', strtotime($record['date'])),
            'hours' => $hours
        ];
    }
    
    // Get total stats
    $sql = "
        SELECT 
            COALESCE(SUM(duration_minutes), 0) as total_minutes,
            COUNT(DISTINCT DATE(checkin_time)) as days_present,
            MAX(duration_minutes) as longest_session_minutes
        FROM attendance 
        $conditions
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $totalStats = $stmt->fetch();
    
    // Calculate weekdays in range (excluding weekends)
    $start = new DateTime($date_from);
    $end = new DateTime($date_to);
    $end->modify('+1 day');
    
    $interval = new DateInterval('P1D');
    $period = new DatePeriod($start, $interval, $end);
    
    $weekdays = 0;
    foreach ($period as $date) {
        $dayOfWeek = $date->format('N');
        if ($dayOfWeek < 6) { // Monday to Friday
            $weekdays++;
        }
    }
    
    $daysAbsent = max(0, $weekdays - $totalStats['days_present']);
    $totalHours = round($totalStats['total_minutes'] / 60, 1);
    $avgDaily = $totalStats['days_present'] > 0 ? round($totalHours / $totalStats['days_present'], 1) : 0;
    $longestSession = round($totalStats['longest_session_minutes'] / 60, 1);
    
    // Get attendance rate
    $attendance = [
        'present' => $totalStats['days_present'],
        'absent' => $daysAbsent
    ];
    
    // Get weekly comparison
    $thisWeekStart = date('Y-m-d', strtotime('monday this week'));
    $thisWeekEnd = date('Y-m-d', strtotime('sunday this week'));
    $lastWeekStart = date('Y-m-d', strtotime('monday last week'));
    $lastWeekEnd = date('Y-m-d', strtotime('sunday last week'));
    
    // This week hours
    $sql = "
        SELECT COALESCE(SUM(duration_minutes), 0) as total_minutes
        FROM attendance 
        WHERE DATE(checkin_time) BETWEEN :this_week_start AND :this_week_end
    ";
    
    $weeklyParams = [
        ':this_week_start' => $thisWeekStart,
        ':this_week_end' => $thisWeekEnd
    ];
    
    if ($user_id) {
        $sql .= " AND user_id = :user_id";
        $weeklyParams[':user_id'] = $user_id;
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($weeklyParams);
    $thisWeekResult = $stmt->fetch();
    $thisWeekHours = round($thisWeekResult['total_minutes'] / 60, 1);
    
    // Last week hours
    $sql = "
        SELECT COALESCE(SUM(duration_minutes), 0) as total_minutes
        FROM attendance 
        WHERE DATE(checkin_time) BETWEEN :last_week_start AND :last_week_end
    ";
    
    $weeklyParams = [
        ':last_week_start' => $lastWeekStart,
        ':last_week_end' => $lastWeekEnd
    ];
    
    if ($user_id) {
        $sql .= " AND user_id = :user_id";
        $weeklyParams[':user_id'] = $user_id;
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($weeklyParams);
    $lastWeekResult = $stmt->fetch();
    $lastWeekHours = round($lastWeekResult['total_minutes'] / 60, 1);
    
    $weeklyComparison = [
        'this_week' => $thisWeekHours,
        'last_week' => $lastWeekHours
    ];
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_hours' => $totalHours,
            'avg_daily' => $avgDaily,
            'days_present' => $totalStats['days_present'],
            'days_absent' => $daysAbsent,
            'longest_session' => $longestSession
        ],
        'daily_hours' => $dailyHours,
        'attendance' => $attendance,
        'weekly_comparison' => $weeklyComparison
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
