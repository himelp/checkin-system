<?php
/**
 * Login API
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/lang.php';

// Set content type
header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get input
// Accept both JSON and HTML form POST
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true);
    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';
    $csrfToken = $input['csrf_token'] ?? '';
} else {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';
}

// Verify CSRF token
if (!verifyCSRFToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

// Validate input
if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => t('error_invalid')]);
    exit;
}

// Attempt login
$result = loginUser($username, $password);

if ($result['success']) {
    echo json_encode([
        'success' => true,
        'message' => $result['message'],
        'redirect' => SITE_URL . '/dashboard.php'
    ]);
} else {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => $result['message']]);
}
