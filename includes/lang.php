<?php
/**
 * Language Functions
 */

require_once __DIR__ . '/../config.php';

/**
 * Load language file
 * @param string $lang
 * @return array
 */
function loadLanguage($lang) {
    $file = __DIR__ . '/../lang/' . $lang . '.json';
    
    if (!file_exists($file)) {
        $file = __DIR__ . '/../lang/' . DEFAULT_LANG . '.json';
    }
    
    $json = file_get_contents($file);
    return json_decode($json, true) ?: [];
}

/**
 * Get translation
 * @param string $key
 * @param array $vars
 * @return string
 */
function t($key, $vars = []) {
    static $lang = null;
    
    if ($lang === null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $langCode = $_SESSION['lang'] ?? DEFAULT_LANG;
        
        // Auto-detect browser language if not set
        if (!isset($_SESSION['lang']) && isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $browserLangs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
            foreach ($browserLangs as $bl) {
                $bl = substr($bl, 0, 2);
                if (file_exists(__DIR__ . '/../lang/' . $bl . '.json')) {
                    $langCode = $bl;
                    break;
                }
            }
        }
        
        $lang = loadLanguage($langCode);
    }
    
    $text = $lang[$key] ?? $key;
    
    // Replace variables
    foreach ($vars as $k => $v) {
        $text = str_replace('{' . $k . '}', $v, $text);
    }
    
    return $text;
}
