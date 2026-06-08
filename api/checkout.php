<?php
/**
 * Check-out API
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../includes/sheets.php';
require_once __DIR__ . '/../config.php';

// Set content type
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => t('session_expired')]);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$userId = $_SESSION['user_id'];
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

$db = getDB();
if (!$db) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => t('error_db')]);
    exit;
}

// Find active check-in
$stmt = $db->prepare("SELECT * FROM check_log WHERE user_id = ? AND status = 'active' ORDER BY checkin_time DESC LIMIT 1");
$stmt->execute([$userId]);
$checkin = $stmt->fetch();

if (!$checkin) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No active check-in found']);
    exit;
}

// Calculate duration
$checkinTime = new DateTime($checkin['checkin_time']);
$checkoutTime = new DateTime();
$interval = $checkinTime->diff($checkoutTime);
$durationMinutes = ($interval->h * 60) + $interval->i;

// Format duration
$hours = floor($durationMinutes / 60);
$minutes = $durationMinutes % 60;
$formattedDuration = '';
if ($hours > 0) {
    $formattedDuration .= $hours . 'h ';
}
$formattedDuration .= $minutes . 'min';

// Update check-in record
$stmt = $db->prepare("UPDATE check_log SET checkout_time = NOW(), duration_minutes = ?, status = 'done', ip_address = ? WHERE id = ?");
$stmt->execute([$durationMinutes, $ip, $checkin['id']]);

// Send to Google Sheets (non-blocking, failures logged but not thrown)
$sheetsData = [
    'row_id' => $checkin['id'],
    'checkout_time' => $checkoutTime->format('Y-m-d H:i:s'),
    'duration_minutes' => $durationMinutes,
    'duration_formatted' => $formattedDuration
];

sendToSheets('checkout', $sheetsData);

echo json_encode([
    'success' => true,
    'message' => t('success_checkout'),
    'duration' => $formattedDuration,
    'duration_minutes' => $durationMinutes
]);
