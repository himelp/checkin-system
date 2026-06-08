<?php
/**
 * Save Settings API
 * Handles auto-saving settings to config.php
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$action = $input['action'];
$configFile = __DIR__ . '/../config.php';
$backupFile = __DIR__ . '/../config.php.bak';

try {
    // Read current config file
    $configContent = file_get_contents($configFile);
    if ($configContent === false) {
        throw new Exception('Failed to read config file');
    }
    
    // Create backup before modifying
    if (!copy($configFile, $backupFile)) {
        throw new Exception('Failed to create backup');
    }
    
    switch ($action) {
        case 'sheets_url':
            handleSheetsUrl($configContent, $input);
            break;
        case 'general':
            handleGeneralSettings($configContent, $input);
            break;
        default:
            throw new Exception('Unknown action: ' . $action);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Handle sheets URL update
 */
function handleSheetsUrl(&$configContent, $input) {
    if (!isset($input['url']) || empty($input['url'])) {
        throw new Exception('URL is required');
    }
    
    $url = trim($input['url']);
    
    // Validate URL format
    if (strpos($url, 'script.google.com') === false) {
        throw new Exception('Invalid URL: Must be a Google Apps Script URL');
    }
    
    // Validate URL is properly formed
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        throw new Exception('Invalid URL format');
    }
    
    // Escape single quotes for PHP string
    $url = str_replace("'", "\\'", $url);
    
    // Replace GOOGLE_SCRIPT_WEBHOOK_URL definition
    $pattern = "/define\s*\(\s*['\"]GOOGLE_SCRIPT_WEBHOOK_URL['\"]\s*,\s*['\"].*?['\"]\s*\)\s*;/";
    $replacement = "define('GOOGLE_SCRIPT_WEBHOOK_URL', '" . $url . "');";
    
    $newContent = preg_replace($pattern, $replacement, $configContent);
    
    if ($newContent === null || $newContent === $configContent) {
        throw new Exception('Failed to update GOOGLE_SCRIPT_WEBHOOK_URL');
    }
    
    $configContent = $newContent;
    
    // Write updated config
    if (file_put_contents(__DIR__ . '/../config.php', $configContent) === false) {
        // Restore backup on failure
        copy(__DIR__ . '/../config.php.bak', __DIR__ . '/../config.php');
        throw new Exception('Failed to write config file');
    }
    
    // Test the connection
    require_once __DIR__ . '/../includes/sheets.php';
    $testResult = testSheetsConnection();
    
    if ($testResult['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Google Sheets URL saved and connection verified!',
            'connection' => 'connected'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'URL saved but connection test failed. Please verify your deployment settings.',
            'connection' => 'failed',
            'error' => $testResult['message'] ?? 'Unknown error'
        ]);
    }
}

/**
 * Handle general settings update
 */
function handleGeneralSettings(&$configContent, $input) {
    $updates = [];
    
    // Update APP_NAME
    if (isset($input['app_name']) && !empty(trim($input['app_name']))) {
        $appName = trim($input['app_name']);
        $appName = str_replace("'", "\\'", $appName);
        
        $pattern = "/define\s*\(\s*['\"]APP_NAME['\"]\s*,\s*['\"].*?['\"]\s*\)\s*;/";
        $replacement = "define('APP_NAME', '" . $appName . "');";
        
        $newContent = preg_replace($pattern, $replacement, $configContent);
        if ($newContent !== null && $newContent !== $configContent) {
            $configContent = $newContent;
            $updates[] = 'APP_NAME';
        }
    }
    
    // Update SESSION_TIMEOUT
    if (isset($input['session_timeout'])) {
        $timeout = intval($input['session_timeout']);
        if ($timeout < 5) $timeout = 5;
        if ($timeout > 1440) $timeout = 1440;
        $timeoutSeconds = $timeout * 60;
        
        $pattern = "/define\s*\(\s*['\"]SESSION_TIMEOUT['\"]\s*,\s*\d+\s*\)\s*;/";
        $replacement = "define('SESSION_TIMEOUT', " . $timeoutSeconds . ");";
        
        $newContent = preg_replace($pattern, $replacement, $configContent);
        if ($newContent !== null && $newContent !== $configContent) {
            $configContent = $newContent;
            $updates[] = 'SESSION_TIMEOUT';
        }
    }
    
    // Update DEFAULT_LANG
    if (isset($input['default_lang']) && in_array($input['default_lang'], ['en', 'it'])) {
        $lang = $input['default_lang'];
        
        $pattern = "/define\s*\(\s*['\"]DEFAULT_LANG['\"]\s*,\s*['\"].*?['\"]\s*\)\s*;/";
        $replacement = "define('DEFAULT_LANG', '" . $lang . "');";
        
        $newContent = preg_replace($pattern, $replacement, $configContent);
        if ($newContent !== null && $newContent !== $configContent) {
            $configContent = $newContent;
            $updates[] = 'DEFAULT_LANG';
        }
    }
    
    if (empty($updates)) {
        throw new Exception('No valid settings to update');
    }
    
    // Write updated config
    if (file_put_contents(__DIR__ . '/../config.php', $configContent) === false) {
        // Restore backup on failure
        copy(__DIR__ . '/../config.php.bak', __DIR__ . '/../config.php');
        throw new Exception('Failed to write config file');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Settings updated: ' . implode(', ', $updates),
        'updated' => $updates
    ]);
}
