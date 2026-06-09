<?php
/**
 * Admin Updater Page
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/version.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../config.php';

secureHeaders();

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../index.php');
    exit;
}

$csrfToken = generateCSRFToken();
$currentVersion = getCurrentVersion();
$updateInfo = checkForUpdates($currentVersion);
$hasUpdate = isset($updateInfo['update_available']) && $updateInfo['update_available'];
$error = isset($updateInfo['error']) ? $updateInfo['error'] : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Updater - <?php echo APP_NAME; ?></title>
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
        <div class="max-w-4xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="../dashboard.php" class="text-blue-600 hover:text-blue-800">
                    &larr; Back to Dashboard
                </a>
                <h1 class="text-xl font-bold text-blue-600">System Updater</h1>
            </div>
            <div class="text-sm text-gray-500">
                v<?php echo htmlspecialchars($currentVersion); ?>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto p-4 space-y-6">
        <!-- Error Message -->
        <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <h2 class="text-lg font-semibold text-red-800 mb-2">Error</h2>
            <p class="text-red-700"><?php echo htmlspecialchars($error); ?></p>
        </div>
        <?php endif; ?>

        <!-- Current Version Card -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Current Version</h2>
            <div class="flex items-center gap-4">
                <div class="text-3xl font-bold text-blue-600">v<?php echo htmlspecialchars($currentVersion); ?></div>
                <?php if (!$hasUpdate && !$error): ?>
                    <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                        Up to Date
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Update Status -->
        <?php if ($hasUpdate): ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
            <h2 class="text-lg font-semibold text-yellow-800 mb-2">Update Available!</h2>
            <div class="text-yellow-700 mb-4 space-y-1">
                <p>Current Version: <strong><?php echo htmlspecialchars($updateInfo['current_version']); ?></strong></p>
                <p>Latest Version: <strong><?php echo htmlspecialchars($updateInfo['latest_version']); ?></strong></p>
            </div>
            <div class="flex gap-3">
                <button id="checkUpdateBtn" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">
                    Check Again
                </button>
                <button id="downloadUpdateBtn" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition">
                    Download Update
                </button>
                <a href="<?php echo GITHUB_REPO_URL; ?>" target="_blank" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition">
                    View on GitHub
                </a>
            </div>
        </div>
        <?php elseif (!$error): ?>
        <div class="bg-green-50 border border-green-200 rounded-lg p-6">
            <h2 class="text-lg font-semibold text-green-800">System Up to Date</h2>
            <p class="text-green-700 mt-2">You are using the latest version of <?php echo APP_NAME; ?>.</p>
            <button id="checkUpdateBtn" class="mt-4 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">
                Check Again
            </button>
        </div>
        <?php endif; ?>

        <!-- Update Instructions -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">How to Update</h2>
            <ol class="list-decimal list-inside space-y-2 text-gray-700">
                <li>Click the <strong>"Download Update"</strong> button above</li>
                <li>Download the ZIP file from GitHub</li>
                <li>Extract the files on your computer</li>
                <li>Upload the extracted files to your server (overwrite existing files)</li>
                <li>Run the database update if needed (check the README)</li>
                <li>Clear your browser cache and refresh</li>
            </ol>
        </div>

        <!-- Update History -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Update History</h2>
            <div class="space-y-3">
                <div class="flex justify-between items-center p-3 bg-blue-50 rounded border-l-4 border-blue-500">
                    <div>
                        <span class="font-medium">Version 1.1.1</span>
                        <p class="text-sm text-gray-500">Current version</p>
                    </div>
                    <span class="text-sm text-blue-600 font-medium">Current</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <div>
                        <span class="font-medium">Version 1.1.0</span>
                        <p class="text-sm text-gray-500">Bug fixes and improvements</p>
                    </div>
                    <span class="text-sm text-gray-500">Previous</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <div>
                        <span class="font-medium">Version 1.0.0</span>
                        <p class="text-sm text-gray-500">Initial release</p>
                    </div>
                    <span class="text-sm text-gray-500">Initial</span>
                </div>
            </div>
        </div>

        <!-- System Info -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">System Information</h2>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">Application:</span>
                    <span class="font-medium"><?php echo APP_NAME; ?></span>
                </div>
                <div>
                    <span class="text-gray-500">Version:</span>
                    <span class="font-medium">v<?php echo htmlspecialchars($currentVersion); ?></span>
                </div>
                <div>
                    <span class="text-gray-500">PHP Version:</span>
                    <span class="font-medium"><?php echo phpversion(); ?></span>
                </div>
                <div>
                    <span class="text-gray-500">Server:</span>
                    <span class="font-medium"><?php echo htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'); ?></span>
                </div>
            </div>
        </div>
    </main>

    <!-- Toast Container -->
    <div id="toastContainer" class="fixed top-20 left-1/2 -translate-x-1/2 z-50"></div>

    <!-- CSRF Token -->
    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

    <script src="../assets/app.js"></script>
    <script>
        // Toast notification function
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
                toast.style.transition = 'opacity 0.3s';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Check for updates
        document.getElementById('checkUpdateBtn')?.addEventListener('click', async function() {
            const btn = this;
            btn.disabled = true;
            btn.textContent = 'Checking...';

            try {
                const response = await fetch('../api/check_version.php');
                const data = await response.json();

                if (data.success) {
                    if (data.data.update_available) {
                        showToast('Update available: v' + data.data.latest_version, 'success');
                        location.reload();
                    } else {
                        showToast('You are using the latest version', 'success');
                    }
                } else {
                    showToast(data.message || 'Failed to check for updates', 'error');
                }
            } catch (error) {
                showToast('Network error: ' + error.message, 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Check Again';
            }
        });

        // Download update
        document.getElementById('downloadUpdateBtn')?.addEventListener('click', function() {
            window.open('<?php echo UPDATE_DOWNLOAD_URL; ?>', '_blank');
            showToast('Download started', 'success');
        });
    </script>
</body>
</html>
