<?php
/**
 * Set Language API
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

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$userId = $_SESSION['user_id'];
$currentLang = $_SESSION['lang'] ?? DEFAULT_LANG;

// Toggle language
$newLang = ($currentLang === 'en') ? 'it' : 'en';

// Update session
$_SESSION['lang'] = $newLang;

// Update database
$db = getDB();
if ($db) {
    $stmt = $db->prepare("UPDATE users SET lang = ? WHERE id = ?");
    $stmt->execute([$newLang, $userId]);
}

echo json_encode([
    'success' => true,
    'lang' => $newLang
]);
