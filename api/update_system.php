<?php
/**
 * System Update API
 */
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/version.php';
require_once __DIR__ . '/../includes/security.php';

// Only allow admin users to update
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Verify CSRF token
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'check':
        $updateInfo = checkForUpdates();
        echo json_encode(['success' => true, 'data' => $updateInfo]);
        break;
        
    case 'download':
        // In a real implementation, you would download the update package
        echo json_encode([
            'success' => true, 
            'message' => 'Update download simulated',
            'download_url' => 'https://github.com/himelp/checkin-system/archive/refs/heads/master.zip'
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
