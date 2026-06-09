<?php
/**
 * Force Checkout API — Admin only
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/lang.php';
require_once __DIR__ . '/../../includes/sheets.php';
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

// Check admin
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;

}

$targetUserId = intval($_POST['user_id'] ?? 0);

if ($targetUserId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

$db = getDB();
if (!$db) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => t('error_db')]);
    exit;
}

// Get target user's timezone
$stmt = $db->prepare("SELECT id, name, username, timezone FROM users WHERE id = ?");
$stmt->execute([$targetUserId]);
$targetUser = $stmt->fetch();

if (!$targetUser) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

// Set user's timezone for accurate time calculation
$userTimezone = $targetUser['timezone'] ?? 'UTC';
date_default_timezone_set($userTimezone);

// Find active check-in
$stmt = $db->prepare("SELECT * FROM check_log WHERE user_id = ? AND status = 'active' ORDER BY checkin_time DESC LIMIT 1");
$stmt->execute([$targetUserId]);
$checkin = $stmt->fetch();

if (!$checkin) {
    echo json_encode(['success' => false, 'message' => 'No active check-in found for this user']);
    exit;
}

// Calculate duration
$checkinTime = new DateTime($checkin['checkin_time']);
$checkoutTime = new DateTime();
$interval = $checkinTime->diff($checkoutTime);
$durationMinutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;

// Format duration
$hours = floor($durationMinutes / 60);
$minutes = $durationMinutes % 60;
$formattedDuration = '';
if ($hours > 0) {
    $formattedDuration .= $hours . 'h ';
}
$formattedDuration .= $minutes . 'min';

// Update check-in record
$stmt = $db->prepare("UPDATE check_log SET checkout_time = NOW(), duration_minutes = ?, status = 'done' WHERE id = ?");
$stmt->execute([$durationMinutes, $checkin['id']]);

// Send to Google Sheets
$sheetsData = [
    'action' => 'checkout',
    'row_id' => $checkin['id'],
    'user_id' => $targetUserId,
    'name' => $targetUser['name'],
    'checkin_time' => $checkin['checkin_time'],
    'checkout_time' => $checkoutTime->format('Y-m-d H:i:s'),
    'duration_minutes' => $durationMinutes,
    'duration_formatted' => $formattedDuration,
    'date' => $checkin['date'],
    'secret' => defined('SHEETS_SECRET') ? SHEETS_SECRET : 'checktrack-secret-2026'
];

sendToSheets('checkout', $sheetsData);

echo json_encode([
    'success' => true,
    'message' => 'Force checkout successful for ' . $targetUser['name'],
    'duration' => $formattedDuration,
    'duration_minutes' => $durationMinutes
]);
