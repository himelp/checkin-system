<?php
/**
 * Database Connection Test
 */

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get POST data
$host = trim($_POST['host'] ?? 'localhost');
$dbname = trim($_POST['dbname'] ?? '');
$user = trim($_POST['user'] ?? '');
$pass = $_POST['pass'] ?? '';

// Validate inputs
if (empty($dbname)) {
    echo json_encode(['success' => false, 'message' => 'Database name is required']);
    exit;
}

if (empty($user)) {
    echo json_encode(['success' => false, 'message' => 'Database username is required']);
    exit;
}

// Try to connect
try {
    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 5
    ];
    
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Check if we can run queries
    $pdo->query('SELECT 1');
    
    echo json_encode(['success' => true, 'message' => 'Database connection successful!']);
    
} catch (PDOException $e) {
    $error = $e->getMessage();
    
    // Provide user-friendly error messages
    if (strpos($error, 'Access denied') !== false) {
        $message = 'Access denied. Please check your username and password.';
    } elseif (strpos($error, 'Unknown database') !== false) {
        $message = 'Database not found. Please create the database first.';
    } elseif (strpos($error, 'Connection refused') !== false || strpos($error, 'No such host') !== false) {
        $message = 'Cannot connect to database server. Please check the host.';
    } else {
        $message = 'Connection failed: ' . $error;
    }
    
    echo json_encode(['success' => false, 'message' => $message]);
}
