<?php
/**
 * Status API - Returns current check-in status
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/lang.php';

// Set content type
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => t('session_expired')]);
    exit;
}

$userId = $_SESSION['user_id'];
$today = date('Y-m-d');

$db = getDB();
if (!$db) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => t('error_db')]);
    exit;
}

// Check for active check-in
$stmt = $db->prepare("SELECT id, checkin_time FROM check_log WHERE user_id = ? AND status = 'active' ORDER BY checkin_time DESC LIMIT 1");
$stmt->execute([$userId]);
$activeCheckin = $stmt->fetch();

// Get today's total minutes
$stmt = $db->prepare("SELECT COALESCE(SUM(duration_minutes), 0) as total FROM check_log WHERE user_id = ? AND date = ? AND status = 'done'");
$stmt->execute([$userId, $today]);
$todayTotal = $stmt->fetch()['total'];

// If currently checked in, add current session duration
if ($activeCheckin) {
    $checkinTime = new DateTime($activeCheckin['checkin_time']);
    $now = new DateTime();
    $interval = $checkinTime->diff($now);
    $currentMinutes = ($interval->h * 60) + $interval->i;
    $todayTotal += $currentMinutes;
}

// Get last 7 days history
$stmt = $db->prepare("SELECT * FROM check_log WHERE user_id = ? ORDER BY date DESC, checkin_time DESC LIMIT 7");
$stmt->execute([$userId]);
$history = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'is_checkedin' => !empty($activeCheckin),
    'checkin_time' => $activeCheckin ? $activeCheckin['checkin_time'] : null,
    'today_total_minutes' => (int)$todayTotal,
    'history' => $history
]);
