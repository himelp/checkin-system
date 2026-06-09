<?php
/**
 * Create User API
 */

session_start();

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php';

header('Content-Type: application/json');

// Check admin (use 'role' not 'user_role')
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Admin access required.']);
    exit;
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

$action = sanitizeInput($input['action'] ?? 'add');

// Handle edit action
if ($action === 'edit') {
    $userId = intval($input['user_id'] ?? 0);
    $name = sanitizeInput($input['name'] ?? '');
    $role = sanitizeInput($input['role'] ?? 'user');

    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Name is required']);
        exit;
    }

    if (!in_array($role, ['user', 'admin'])) {
        $role = 'user';
    }

    $db = getDB();
    if (!$db) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit;
    }

    $stmt = $db->prepare("UPDATE users SET name = ?, role = ? WHERE id = ?");
    try {
        $stmt->execute([$name, $role, $userId]);
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update user']);
    }
    exit;
}

// Handle add action (default)
try {
    $username = sanitizeInput($input['username'] ?? '');
    $password = $input['password'] ?? '';
    $name = sanitizeInput($input['name'] ?? '');
    $role = sanitizeInput($input['role'] ?? 'user');

    // Validation
    if (empty($username) || empty($password) || empty($name)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Username, password, and name are required']);
        exit;
    }

    if (strlen($username) < 3 || strlen($username) > 100) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Username must be 3-100 characters']);
        exit;
    }

    if (strlen($password) < 6) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
        exit;
    }

    if (!in_array($role, ['user', 'admin'])) {
        $role = 'user';
    }

    $db = getDB();
    if (!$db) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit;
    }

    // Check username exists
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Username already exists']);
        exit;
    }

    // Create user
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $defaultLang = defined('DEFAULT_LANG') ? DEFAULT_LANG : 'en';
    $defaultTimezone = defined('DEFAULT_TIMEZONE') ? DEFAULT_TIMEZONE : 'UTC';

    $stmt = $db->prepare("INSERT INTO users (name, username, password, role, lang, timezone, status) VALUES (?, ?, ?, ?, ?, ?, 1)");
    $stmt->execute([$name, $username, $hashedPassword, $role, $defaultLang, $defaultTimezone]);
    $newUserId = $db->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'User created successfully',
        'user_id' => $newUserId
    ]);
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Username already exists']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create user']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred']);
}
