<?php
// api/create_user.php

header('Content-Type: application/json');
session_start(); // Ensure session is started for authentication checks

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php'; // For sanitizeInput

// --- Authorization Check ---
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Only admins can create users.']);
    exit();
}

// --- Input Handling ---
$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input.']);
    exit();
}

$username = sanitizeInput($input['username'] ?? '');
$password = $input['password'] ?? ''; // Don't sanitize password before hashing
$fullName = sanitizeInput($input['full_name'] ?? '');
$role = sanitizeInput($input['role'] ?? 'employee'); // Default to employee

// --- Validation ---
if (empty($username) || empty($password) || empty($fullName)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Username, password, and full name are required.']);
    exit();
}

// Basic password strength (example)
if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long.']);
    exit();
}

// Validate role (only allow specific roles)
$allowedRoles = ['employee', 'admin'];
if (!in_array($role, $allowedRoles)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid role specified.']);
    exit();
}

// --- User Creation ---
try {
    $newUserId = createUser($username, $password, $fullName, $role);
    echo json_encode(['success' => true, 'message' => 'User created successfully.', 'user_id' => $newUserId]);
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    // Log the detailed error for debugging
    error_log("User creation failed: " . $e->getMessage());
    // Provide a generic error message to the client
    echo json_encode(['success' => false, 'message' => 'Failed to create user. Please try again later.']);
}
