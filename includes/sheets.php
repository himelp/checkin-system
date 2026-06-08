<?php
/**
 * Google Sheets Integration Helper
 */

require_once __DIR__ . '/../config.php';

/**
 * Send data to Google Apps Script
 * 
 * @param string $action The action type (checkin, checkout, get_status)
 * @param array $data The data to send
 * @return array Response with success bool and data
 */
function sendToSheets($action, $data) {
    // Skip if webhook URL is not configured
    if (empty(GOOGLE_SCRIPT_WEBHOOK_URL) || 
        GOOGLE_SCRIPT_WEBHOOK_URL === 'https://script.google.com/macros/s/YOUR_SCRIPT_ID/exec') {
        return ['success' => false, 'message' => 'Webhook URL not configured', 'skipped' => true];
    }
    
    $payload = array_merge(['action' => $action], $data);
    
    // First attempt
    $result = sendWebhookRequest($payload);
    
    // Retry once on failure
    if (!$result['success'] && !isset($result['skipped'])) {
        error_log("Sheets API: First attempt failed, retrying... Error: " . ($result['message'] ?? 'Unknown'));
        $result = sendWebhookRequest($payload);
    }
    
    // Log final failure (but don't throw)
    if (!$result['success'] && !isset($result['skipped'])) {
        error_log("Sheets API: Failed to send data. Action: $action, Error: " . ($result['message'] ?? 'Unknown'));
    }
    
    return $result;
}

/**
 * Send webhook request via cURL
 * 
 * @param array $payload Data to send
 * @return array Response data
 */
function sendWebhookRequest($payload) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => GOOGLE_SCRIPT_WEBHOOK_URL,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $curlErrno = curl_errno($ch);
    curl_close($ch);
    
    // Check for cURL errors
    if ($curlErrno !== 0) {
        return [
            'success' => false,
            'message' => "cURL error ($curlErrno): $curlError"
        ];
    }
    
    // Check HTTP status
    if ($httpCode !== 200) {
        return [
            'success' => false,
            'message' => "HTTP error: $httpCode"
        ];
    }
    
    // Parse response
    $decoded = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'success' => false,
            'message' => 'Invalid JSON response: ' . json_last_error_msg()
        ];
    }
    
    return $decoded ?: ['success' => false, 'message' => 'Empty response'];
}

/**
 * Test connection to Google Apps Script
 * 
 * @return array Test result
 */
function testSheetsConnection() {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => GOOGLE_SCRIPT_WEBHOOK_URL . '?action=ping',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        return [
            'success' => false,
            'message' => "Connection failed: $curlError"
        ];
    }
    
    if ($httpCode !== 200) {
        return [
            'success' => false,
            'message' => "HTTP error: $httpCode"
        ];
    }
    
    $decoded = json_decode($response, true);
    
    return $decoded ?: [
        'success' => false,
        'message' => 'Invalid response'
    ];
}
