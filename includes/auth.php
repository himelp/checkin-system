<?php
/**
 * Authentication Functions
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/lang.php';

/**
 * Start secure session
 */
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Lax');
        session_start();
    }
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    startSecureSession();
    
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // Check session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        return false;
    }
    
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Check if user has admin role
 * @return bool
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Generate CSRF token
 * @return string
 */
function generateCSRFToken() {
    startSecureSession();
    
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token
 * @return bool
 */
function verifyCSRFToken($token) {
    startSecureSession();
    
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check rate limiting
 * @param string $ip
 * @return bool True if allowed, false if rate limited
 */
function checkRateLimit($ip) {
    $db = getDB();
    if (!$db) return false;
    
    // Clean old attempts (older than 10 minutes)
    $stmt = $db->prepare("DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 10 MINUTE)");
    $stmt->execute();
    
    // Count recent attempts
    $stmt = $db->prepare("SELECT COUNT(*) as attempts FROM login_attempts WHERE ip_address = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)");
    $stmt->execute([$ip]);
    $result = $stmt->fetch();
    
    if ($result['attempts'] >= 5) {
        return false;
    }
    
    // Log attempt
    $stmt = $db->prepare("INSERT INTO login_attempts (ip_address) VALUES (?)");
    $stmt->execute([$ip]);
    
    return true;
}

/**
 * Login user
 * @param string $username
 * @param string $password
 * @return array ['success' => bool, 'message' => string]
 */
function loginUser($username, $password) {
    $db = getDB();
    if (!$db) {
        return ['success' => false, 'message' => t('error_db')];
    }
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    // Check rate limit
    if (!checkRateLimit($ip)) {
        return ['success' => false, 'message' => t('error_locked')];
    }
    
    // Get user
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND status = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => t('error_invalid')];
    }
    
    // Start session
    startSecureSession();
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['lang'] = $user['lang'];
    $_SESSION['last_activity'] = time();
    
    // Update last login
    $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    return ['success' => true, 'message' => t('success_login')];
}

/**
 * Logout user
 */
function logoutUser() {
    startSecureSession();
    session_unset();
    session_destroy();
}
