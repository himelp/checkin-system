<?php
/**
 * Test Google Sheets Integration
 * Only accessible to admin users
 */

// Redirect to installer if not installed
if (!file_exists(__DIR__ . '/install/installed.lock')) {
    header('Location: install.php');
    exit;
}

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/sheets.php';
require_once __DIR__ . '/config.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Only admin can access
if (!isAdmin()) {
    http_response_code(403);
    die('Access denied');
}

$message = '';
$messageType = '';
$response = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'test_connection') {
        $response = testSheetsConnection();
        $message = $response['success'] ? 'Connection successful!' : 'Connection failed: ' . ($response['message'] ?? 'Unknown error');
        $messageType = $response['success'] ? 'success' : 'error';
    } elseif ($action === 'test_checkin') {
        $testData = [
            'row_id' => rand(1000, 9999),
            'name' => 'Test User',
            'username' => 'testuser',
            'date' => date('Y-m-d'),
            'checkin_time' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ];
        $response = sendToSheets('checkin', $testData);
        $message = $response['success'] ? 'Test checkin sent successfully!' : 'Failed: ' . ($response['message'] ?? 'Unknown error');
        $messageType = $response['success'] ? 'success' : 'error';
    } elseif ($action === 'test_checkout') {
        $testData = [
            'row_id' => $_POST['row_id'] ?? '1',
            'checkout_time' => date('Y-m-d H:i:s'),
            'duration_minutes' => 480,
            'duration_formatted' => '8h 0min'
        ];
        $response = sendToSheets('checkout', $testData);
        $message = $response['success'] ? 'Test checkout sent successfully!' : 'Failed: ' . ($response['message'] ?? 'Unknown error');
        $messageType = $response['success'] ? 'success' : 'error';
    }
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? DEFAULT_LANG; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Test Sheets Integration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .toast {
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from { transform: translateY(-100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Navbar -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
            <h1 class="text-xl font-bold text-blue-600"><?php echo APP_NAME; ?></h1>
            <div class="flex items-center gap-2 sm:gap-4">
                <a href="dashboard.php" class="px-3 py-2 bg-gray-100 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-200 min-h-[44px] flex items-center">
                    <?php echo t('dashboard'); ?>
                </a>
                <span class="text-gray-600 text-sm hidden sm:inline"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                <a href="api/logout.php" class="px-3 py-2 bg-red-100 text-red-600 rounded-lg text-sm font-medium hover:bg-red-200 min-h-[44px] flex items-center">
                    <?php echo t('logout'); ?>
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto p-4 space-y-6 pb-24">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow-lg p-4 sm:p-6">
            <h2 class="text-2xl font-bold text-gray-800">Test Google Sheets Integration</h2>
            <p class="text-gray-600 mt-2">Test the connection and data sync with Google Apps Script</p>
        </div>

        <!-- Webhook URL Status -->
        <div class="bg-white rounded-xl shadow-lg p-4 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-3">Configuration</h3>
            <div class="space-y-2">
                <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                    <span class="text-gray-600 font-medium">Webhook URL:</span>
                    <code class="bg-gray-100 px-3 py-1 rounded text-sm break-all">
                        <?php echo htmlspecialchars(GOOGLE_SCRIPT_WEBHOOK_URL); ?>
                    </code>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-gray-600 font-medium">Status:</span>
                    <?php if (empty(GOOGLE_SCRIPT_WEBHOOK_URL) || GOOGLE_SCRIPT_WEBHOOK_URL === 'https://script.google.com/macros/s/YOUR_SCRIPT_ID/exec'): ?>
                        <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-medium">Not Configured</span>
                    <?php else: ?>
                        <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">Configured</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Test Actions -->
        <div class="bg-white rounded-xl shadow-lg p-4 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Test Actions</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Test Connection -->
                <form method="POST" class="space-y-3">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="action" value="test_connection">
                    <button type="submit" class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium min-h-[48px] flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        Test Connection
                    </button>
                    <p class="text-sm text-gray-500 text-center">Ping the Apps Script to verify connectivity</p>
                </form>

                <!-- Test Checkin -->
                <form method="POST" class="space-y-3">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="action" value="test_checkin">
                    <button type="submit" class="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium min-h-[48px] flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                        </svg>
                        Send Test Check-in
                    </button>
                    <p class="text-sm text-gray-500 text-center">Add a test row to the sheet</p>
                </form>

                <!-- Test Checkout -->
                <form method="POST" class="space-y-3 sm:col-span-2">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="action" value="test_checkout">
                    <div class="flex flex-col sm:flex-row gap-3">
                        <input type="text" name="row_id" placeholder="Row ID to update (e.g., 1234)" 
                            class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 min-h-[48px]">
                        <button type="submit" class="px-6 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700 font-medium min-h-[48px] flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                            Send Test Check-out
                        </button>
                    </div>
                    <p class="text-sm text-gray-500 text-center">Update a row with checkout data</p>
                </form>
            </div>
        </div>

        <!-- Response Display -->
        <?php if ($response): ?>
        <div class="bg-white rounded-xl shadow-lg p-4 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-3">Response</h3>
            <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
                <pre class="text-green-400 text-sm"><code><?php echo htmlspecialchars(json_encode($response, JSON_PRETTY_PRINT)); ?></code></pre>
            </div>
        </div>
        <?php endif; ?>

        <!-- Instructions -->
        <div class="bg-white rounded-xl shadow-lg p-4 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-3">Setup Instructions</h3>
            <ol class="list-decimal list-inside space-y-2 text-gray-600">
                <li>Create a new Google Spreadsheet</li>
                <li>Go to Extensions → Apps Script</li>
                <li>Paste the Code.gs content and save</li>
                <li>Deploy → New deployment → Web app</li>
                <li>Set "Execute as" to Me and "Who has access" to Anyone</li>
                <li>Copy the Web App URL and update config.php</li>
                <li>Run "Test Connection" above to verify</li>
            </ol>
        </div>
    </main>

    <!-- Toast Container -->
    <div id="toastContainer" class="fixed top-20 left-1/2 -translate-x-1/2 z-50"></div>

    <?php if ($message): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            showToast('<?php echo addslashes($message); ?>', '<?php echo $messageType; ?>');
        });
        
        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            if (!container) return;
            
            const toast = document.createElement('div');
            const bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';
            
            toast.className = `toast ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg mb-3 max-w-sm text-center`;
            toast.textContent = message;
            
            container.appendChild(toast);
            
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.3s ease-out';
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        }
    </script>
    <?php endif; ?>
</body>
</html>
