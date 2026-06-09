<?php
/**
 * API endpoint to check for updates
 */
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../includes/version.php';

try {
    $updateInfo = checkForUpdates();
    
    if (isset($updateInfo['error'])) {
        echo json_encode([
            'success' => false,
            'message' => $updateInfo['error']
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'data' => $updateInfo
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error checking for updates: ' . $e->getMessage()
    ]);
}
?>
