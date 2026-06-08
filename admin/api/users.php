<?php
/**
 * Admin Users API
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/lang.php';

// Set content type
header('Content-Type: application/json');

// Check if user is admin
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

$db = getDB();
if (!$db) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => t('error_db')]);
    exit;
}

switch ($action) {
    case 'add':
        $name = trim($input['name'] ?? '');
        $username = trim($input['username'] ?? '');
        $password = $input['password'] ?? '';
        $role = $input['role'] ?? 'user';
        
        // Validate
        if (empty($name) || empty($username) || empty($password)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            exit;
        }
        
        if (!in_array($role, ['admin', 'user'])) {
            $role = 'user';
        }
        
        // Check if username exists
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Username already exists']);
            exit;
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
        // Insert user
        $stmt = $db->prepare("INSERT INTO users (name, username, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $username, $hashedPassword, $role]);
        
        echo json_encode(['success' => true, 'message' => 'User added successfully']);
        break;
        
    case 'edit':
        $userId = (int)($input['user_id'] ?? 0);
        $name = trim($input['name'] ?? '');
        $role = $input['role'] ?? 'user';
        
        if (empty($name)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Name is required']);
            exit;
        }
        
        if (!in_array($role, ['admin', 'user'])) {
            $role = 'user';
        }
        
        $stmt = $db->prepare("UPDATE users SET name = ?, role = ? WHERE id = ?");
        $stmt->execute([$name, $role, $userId]);
        
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
        break;
        
    case 'disable':
        $userId = (int)($input['user_id'] ?? 0);
        
        // Cannot disable own account
        if ($userId == $_SESSION['user_id']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Cannot disable your own account']);
            exit;
        }
        
        $stmt = $db->prepare("UPDATE users SET status = 0 WHERE id = ?");
        $stmt->execute([$userId]);
        
        echo json_encode(['success' => true, 'message' => 'User disabled']);
        break;
        
    case 'enable':
        $userId = (int)($input['user_id'] ?? 0);
        
        $stmt = $db->prepare("UPDATE users SET status = 1 WHERE id = ?");
        $stmt->execute([$userId]);
        
        echo json_encode(['success' => true, 'message' => 'User enabled']);
        break;
        
    case 'reset_password':
        $userId = (int)($input['user_id'] ?? 0);
        $password = $input['password'] ?? '';
        
        if (empty($password)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Password is required']);
            exit;
        }
        
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $userId]);
        
        echo json_encode(['success' => true, 'message' => 'Password reset successfully']);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
