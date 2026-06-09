<?php
/**
 * Admin Updater Page
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/version.php';
require_once __DIR__ . '/../includes/security.php';

secureHeaders();

if (!isLoggedIn() || !isAdmin()) {
    header('Location: index.php');
    exit;
}

$currentVersion = getCurrentVersion();
$updateInfo = checkForUpdates($currentVersion);
$hasUpdate = isset($updateInfo['update_available']) && $updateInfo['update_available'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Updater - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-4xl mx-auto p-4">
        <h1 class="text-2xl font-bold mb-6">System Updater</h1>
        
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Current Version</h2>
            <p class="text-gray-600">Version: <?php echo $currentVersion; ?></p>
        </div>

        <?php if ($hasUpdate): ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-6">
            <h2 class="text-lg font-semibold text-yellow-800 mb-2">Update Available!</h2>
            <p class="text-yellow-700 mb-4">
                Current: <?php echo $updateInfo['current_version']; ?><br>
                Latest: <?php echo $updateInfo['latest_version']; ?>
            </p>
            <button id="checkUpdateBtn" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                Check for Updates
            </button>
        </div>
        <?php else: ?>
        <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-6">
            <h2 class="text-lg font-semibold text-green-800">System Up to Date</h2>
            <p class="text-green-700">You are using the latest version.</p>
        </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Update History</h2>
            <div class="space-y-2">
                <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                    <span>Version 1.1.1</span>
                    <span class="text-sm text-gray-500">Current</span>
                </div>
                <!-- Add more version history as needed -->
            </div>
        </div>
    </div>

    <script src="../assets/app.js"></script>
</body>
</html>
