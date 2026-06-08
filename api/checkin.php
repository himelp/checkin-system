<?php
/**
 * Check-in API
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

// Check if user already has an active check-in
$stmt = $db->prepare("SELECT id FROM check_log WHERE user_id = ? AND status = 'active'");
$stmt->execute([$userId]);
if ($stmt->fetch()) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Already checked in']);
    exit;
}

// Insert check-in record
$now = date('Y-m-d H:i:s');
$date = date('Y-m-d');

$stmt = $db->prepare("INSERT INTO check_log (user_id, checkin_time, date, status, ip_address) VALUES (?, ?, ?, 'active', ?)");
$stmt->execute([$userId, $now, $date, $ip]);

$checkinId = $db->lastInsertId();

// Send to Google Sheets (non-blocking, failures logged but not thrown)
$sheetsData = [
    'row_id' => $checkinId,
    'name' => $_SESSION['name'],
    'username' => $_SESSION['username'],
    'date' => $date,
    'checkin_time' => $now,
    'ip' => $ip
];

sendToSheets('checkin', $sheetsData);

echo json_encode([
    'success' => true,
    'message' => t('success_checkin'),
    'checkin_time' => $now
]);
