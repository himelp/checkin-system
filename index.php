<?php
/**
 * Login Page
 */
ob_start();

// Redirect to installer if not installed
if (!file_exists(__DIR__ . '/install/installed.lock')) {
    header('Location: install.php');
    exit;
}

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/config.php';

secureHeaders();
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$csrfToken = generateCSRFToken();
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? DEFAULT_LANG; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#2563eb">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="manifest" href="/manifest.json">
    <title><?php echo APP_NAME; ?> - <?php echo t('login'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-blue-600"><?php echo APP_NAME; ?></h1>
                <p class="text-gray-500 mt-2"><?php echo t('login_subtitle'); ?></p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg mb-6 text-sm">
                    <?php echo htmlspecialchars(t($error)); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="api/login.php" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo t('username'); ?></label>
                    <input type="text" name="username" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                        placeholder="<?php echo t('username'); ?>">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo t('password'); ?></label>
                    <input type="password" name="password" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                        placeholder="<?php echo t('password'); ?>">
                </div>

                <button type="submit"
                    class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-200">
                    <?php echo t('login'); ?>
                </button>
            </form>
        </div>

        <?php if (defined('SHOW_DEV_FOOTER') && SHOW_DEV_FOOTER): ?>
        <div class="mt-6 text-center text-sm text-gray-500">
            <p>Developed by <a href="<?php echo DEV_WEBSITE; ?>" target="_blank" class="text-blue-600 hover:underline"><?php echo DEV_NAME; ?></a></p>
            <p class="text-xs mt-1"><?php echo DEV_COMPANY; ?> | <a href="mailto:<?php echo DEV_EMAIL; ?>" class="hover:underline"><?php echo DEV_EMAIL; ?></a></p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
