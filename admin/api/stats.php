<?php
/**
 * Admin Stats API
 */

session_start();

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/lang.php';

header('Content-Type: application/json');

// Check if user is admin
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$db = getDB();
if (!$db) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => t('error_db')]);
    exit;
}

try {
    // Total users
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $totalUsers = $stmt->fetch()['count'];

    // Current checkins - active sessions without checkout
    $stmt = $db->query("SELECT COUNT(*) as count FROM check_log WHERE status = 'active'");
    $currentCheckins = $stmt->fetch()['count'];

    // Today hours
    $today = date('Y-m-d');
    $stmt = $db->prepare("SELECT COALESCE(SUM(duration_minutes), 0) as total FROM check_log WHERE date = ? AND status = 'done'");
    $stmt->execute([$today]);
    $todayHours = $stmt->fetch()['total'];

    // Add current active sessions to today hours
    $stmt = $db->prepare("SELECT checkin_time FROM check_log WHERE date = ? AND status = 'active'");
    $stmt->execute([$today]);
    $activeSessions = $stmt->fetchAll();
    $now = new DateTime();
    foreach ($activeSessions as $session) {
        $checkinTime = new DateTime($session['checkin_time']);
        $interval = $checkinTime->diff($now);
        $todayHours += ($interval->h * 60) + $interval->i;
    }

    // Month hours
    $monthStart = date('Y-m-01');
    $stmt = $db->prepare("SELECT COALESCE(SUM(duration_minutes), 0) as total FROM check_log WHERE date >= ? AND status = 'done'");
    $stmt->execute([$monthStart]);
    $monthHours = $stmt->fetch()['total'];

    // Add current active sessions to month hours
    $stmt = $db->prepare("SELECT checkin_time FROM check_log WHERE date >= ? AND status = 'active'");
    $stmt->execute([$monthStart]);
    $activeSessions = $stmt->fetchAll();
    foreach ($activeSessions as $session) {
        $checkinTime = new DateTime($session['checkin_time']);
        $interval = $checkinTime->diff($now);
        $monthHours += ($interval->h * 60) + $interval->i;
    }

    // Active list with duration
    $stmt = $db->query("SELECT u.name, u.id as user_id, cl.checkin_time FROM check_log cl JOIN users u ON cl.user_id = u.id WHERE cl.status = 'active' ORDER BY cl.checkin_time");
    $activeList = $stmt->fetchAll();
    foreach ($activeList as &$item) {
        $checkinTime = new DateTime($item['checkin_time']);
        $interval = $checkinTime->diff($now);
        $item['duration_so_far'] = $interval->h . 'h ' . $interval->i . 'm';
    }

    // Recent activity (last 20)
    $stmt = $db->query("SELECT u.name, 
        CASE WHEN cl.checkout_time IS NULL THEN 'checkin' ELSE 'checkout' END as action,
        COALESCE(cl.checkout_time, cl.checkin_time) as time
        FROM check_log cl 
        JOIN users u ON cl.user_id = u.id 
        ORDER BY COALESCE(cl.checkout_time, cl.checkin_time) DESC 
        LIMIT 20");
    $recentActivity = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'total_users' => (int)$totalUsers,
        'current_checkins' => (int)$currentCheckins,
        'today_hours' => (int)$todayHours,
        'month_hours' => (int)$monthHours,
        'active_list' => $activeList,
        'recent_activity' => $recentActivity
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
