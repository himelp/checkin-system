<?php
/**
 * Calendar Data API
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
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;

// If not admin, only show own data
if (!isAdmin()) {
    $user_id = $_SESSION['user_id'];
}

// Validate month and year
if ($month < 1 || $month > 12) {
    $month = date('m');
}
if ($year < 2000 || $year > 2100) {
    $year = date('Y');
}

try {
    $db = getDB();
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    // Get first and last day of month
    $firstDay = sprintf('%04d-%02d-01', $year, $month);
    $lastDay = date('Y-m-t', strtotime($firstDay));
    
    // Get current date for comparison
    $today = date('Y-m-d');
    
    // Build query
    $sql = "
        SELECT 
            DATE(checkin_time) as date,
            MIN(checkin_time) as checkin_time,
            MAX(checkout_time) as checkout_time,
            SUM(duration_minutes) as total_minutes,
            CASE 
                WHEN MAX(checkout_time) IS NOT NULL THEN 'complete'
                WHEN MAX(checkin_time) IS NOT NULL THEN 'checkin_only'
                ELSE 'absent'
            END as status
        FROM check_log 
        WHERE DATE(checkin_time) BETWEEN :first_day AND :last_day
    ";
    
    $params = [
        ':first_day' => $firstDay,
        ':last_day' => $lastDay
    ];
    
    if ($user_id) {
        $sql .= " AND user_id = :user_id";
        $params[':user_id'] = $user_id;
    }
    
    $sql .= " GROUP BY DATE(checkin_time)";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll();
    
    // Create events array
    $events = [];
    $dayDetails = [];
    
    // Process records
    foreach ($records as $record) {
        $date = $record['date'];
        $status = $record['status'];
        
        // Determine color based on status
        $colors = [
            'complete' => '#10b981',
            'checkin_only' => '#f59e0b',
            'absent' => '#ef4444'
        ];
        
        $color = $colors[$status] ?? '#9ca3af';
        
        // Format times
        $checkinTime = $record['checkin_time'] ? date('H:i', strtotime($record['checkin_time'])) : null;
        $checkoutTime = $record['checkout_time'] ? date('H:i', strtotime($record['checkout_time'])) : null;
        
        // Format duration
        $hours = floor($record['total_minutes'] / 60);
        $minutes = $record['total_minutes'] % 60;
        $duration = "{$hours}h {$minutes}min";
        
        $events[] = [
            'title' => $checkinTime ? "{$checkinTime}" : 'No check-in',
            'start' => $date,
            'color' => $color,
            'extendedProps' => [
                'day_detail' => [
                    'status' => $status,
                    'checkin_time' => $checkinTime,
                    'checkout_time' => $checkoutTime,
                    'duration' => $duration
                ]
            ]
        ];
        
        $dayDetails[$date] = [
            'status' => $status,
            'checkin_time' => $checkinTime,
            'checkout_time' => $checkoutTime,
            'duration' => $duration
        ];
    }
    
    // Add absent days (weekdays without records)
    $start = new DateTime($firstDay);
    $end = new DateTime($lastDay);
    $end->modify('+1 day');
    
    $interval = new DateInterval('P1D');
    $period = new DatePeriod($start, $interval, $end);
    
    foreach ($period as $date) {
        $dateStr = $date->format('Y-m-d');
        $dayOfWeek = $date->format('N'); // 1 (Monday) to 7 (Sunday)
        
        // Skip weekends
        if ($dayOfWeek >= 6) {
            if (!isset($dayDetails[$dateStr])) {
                $events[] = [
                    'title' => 'Weekend',
                    'start' => $dateStr,
                    'color' => '#9ca3af',
                    'extendedProps' => [
                        'day_detail' => [
                            'status' => 'weekend'
                        ]
                    ]
                ];
                $dayDetails[$dateStr] = ['status' => 'weekend'];
            }
            continue;
        }
        
        // Skip future dates
        if ($dateStr > $today) {
            if (!isset($dayDetails[$dateStr])) {
                $events[] = [
                    'title' => 'Future',
                    'start' => $dateStr,
                    'color' => '#9ca3af',
                    'extendedProps' => [
                        'day_detail' => [
                            'status' => 'future'
                        ]
                    ]
                ];
                $dayDetails[$dateStr] = ['status' => 'future'];
            }
            continue;
        }
        
        // Mark as absent if no record exists
        if (!isset($dayDetails[$dateStr])) {
            $events[] = [
                'title' => 'Absent',
                'start' => $dateStr,
                'color' => '#ef4444',
                'extendedProps' => [
                    'day_detail' => [
                        'status' => 'absent',
                        'checkin_time' => null,
                        'checkout_time' => null,
                        'duration' => '0h 0min'
                    ]
                ]
            ];
            $dayDetails[$dateStr] = [
                'status' => 'absent',
                'checkin_time' => null,
                'checkout_time' => null,
                'duration' => '0h 0min'
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'events' => $events,
        'day_details' => $dayDetails
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
