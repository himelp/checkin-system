<?php
/**
 * User Profile Page
 */
ob_start();

if (!file_exists(__DIR__ . '/install/installed.lock')) {
    header('Location: install.php');
    exit;
}

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/config.php';

secureHeaders();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');

if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$csrfToken = generateCSRFToken();
$currentTimezone = $_SESSION['timezone'] ?? 'UTC';
$currentLang = $_SESSION['lang'] ?? DEFAULT_LANG;

// Grouped timezones
$timezoneGroups = [
    'Europe' => [
        'Europe/Rome', 'Europe/London', 'Europe/Paris', 'Europe/Berlin',
    ],
    'Asia' => [
        'Asia/Dhaka', 'Asia/Kolkata', 'Asia/Dubai', 'Asia/Singapore',
    ],
    'Americas' => [
        'America/New_York', 'America/Chicago', 'America/Los_Angeles',
    ],
    'Pacific' => [
        'Australia/Sydney', 'Pacific/Auckland',
    ],
    'Other' => [
        'UTC',
    ],
];
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo APP_NAME; ?> - <?php echo t('profile'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .toast { animation: slideIn 0.3s ease-out; }
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
            <h1 class="text-xl font-bold text-blue-600"><?php echo APP_NAME; ?></h1>
            <div class="flex items-center gap-2 sm:gap-4">
                <span class="text-gray-600 text-sm hidden sm:inline"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                <a href="dashboard.php" class="px-3 py-2 bg-gray-100 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-200 min-h-[44px] flex items-center">
                    <?php echo t('dashboard'); ?>
                </a>
                <a href="api/logout.php" class="px-3 py-2 bg-red-100 text-red-600 rounded-lg text-sm font-medium hover:bg-red-200 min-h-[44px] flex items-center">
                    <?php echo t('logout'); ?>
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-2xl mx-auto p-4 space-y-6 pb-24">
        <div class="mb-2">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800"><?php echo t('profile'); ?></h1>
            <p class="text-gray-600 mt-1">Manage your account settings</p>
        </div>

        <!-- Change Password -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="p-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-700"><?php echo t('change_password'); ?></h2>
            </div>
            <div class="p-4 sm:p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><?php echo t('current_password'); ?></label>
                    <input type="password" id="currentPass" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 min-h-[44px]" placeholder="<?php echo t('current_password'); ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><?php echo t('new_password'); ?></label>
                    <input type="password" id="newPass" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 min-h-[44px]" placeholder="<?php echo t('new_password'); ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><?php echo t('confirm_password'); ?></label>
                    <input type="password" id="confirmPass" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 min-h-[44px]" placeholder="<?php echo t('confirm_password'); ?>">
                </div>
                <button id="savePasswordBtn" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition min-h-[44px]">
                    <?php echo t('change_password'); ?>
                </button>
            </div>
        </div>

        <!-- Preferences -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="p-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-700"><?php echo t('preferences'); ?></h2>
            </div>
            <div class="p-4 sm:p-6 space-y-4">
                <!-- Language -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><?php echo t('login'); ?> / <?php echo t('language') ?? 'Language'; ?></label>
                    <div class="flex gap-3">
                        <button type="button" data-lang="en" class="lang-btn flex-1 py-3 rounded-lg border-2 font-semibold transition min-h-[44px] <?php echo $currentLang === 'en' ? 'border-blue-600 bg-blue-50 text-blue-700' : 'border-gray-300 text-gray-600 hover:border-gray-400'; ?>">English</button>
                        <button type="button" data-lang="it" class="lang-btn flex-1 py-3 rounded-lg border-2 font-semibold transition min-h-[44px] <?php echo $currentLang === 'it' ? 'border-blue-600 bg-blue-50 text-blue-700' : 'border-gray-300 text-gray-600 hover:border-gray-400'; ?>">Italiano</button>
                    </div>
                    <input type="hidden" id="prefLang" value="<?php echo $currentLang; ?>">
                </div>

                <!-- Timezone -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><?php echo t('timezone'); ?></label>
                    <select id="prefTimezone" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 min-h-[44px]">
                        <?php foreach ($timezoneGroups as $group => $zones): ?>
                            <optgroup label="<?php echo $group; ?>">
                                <?php foreach ($zones as $tz): ?>
                                    <option value="<?php echo $tz; ?>" <?php echo $currentTimezone === $tz ? 'selected' : ''; ?>>
                                        <?php echo $tz; ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Current local time display -->
                <div class="bg-gray-50 rounded-lg p-3">
                    <p class="text-sm text-gray-500">Current local time:</p>
                    <p id="localTimeDisplay" class="text-lg font-mono font-semibold text-gray-800">
                        <?php echo date('Y-m-d H:i:s'); ?>
                    </p>
                </div>

                <button id="savePrefsBtn" class="w-full py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition min-h-[44px]">
                    <?php echo t('save_changes'); ?>
                </button>
            </div>
        </div>
    </main>

    <!-- Developer Footer -->
    <?php if (defined('SHOW_DEV_FOOTER') && SHOW_DEV_FOOTER): ?>
    <footer class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 py-2 px-4">
        <div class="max-w-4xl mx-auto flex items-center justify-center gap-2 text-xs text-gray-500">
            <span>Developed by</span>
            <a href="<?php echo DEV_WEBSITE; ?>" target="_blank" class="text-blue-600 hover:underline font-medium"><?php echo DEV_NAME; ?></a>
            <span class="text-gray-300">|</span>
            <span><?php echo DEV_COMPANY; ?></span>
        </div>
    </footer>
    <?php endif; ?>

    <!-- Toast Container -->
    <div id="toastContainer" class="fixed top-20 left-1/2 -translate-x-1/2 z-50"></div>

    <script>
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
            }, 3000);
        }

        async function postJSON(url, data) {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams(data).toString()
            });
            return response.json();
        }

        // Language buttons
        document.querySelectorAll('.lang-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const lang = this.dataset.lang;
                document.getElementById('prefLang').value = lang;
                document.querySelectorAll('.lang-btn').forEach(b => {
                    b.classList.remove('border-blue-600', 'bg-blue-50', 'text-blue-700');
                    b.classList.add('border-gray-300', 'text-gray-600');
                });
                this.classList.remove('border-gray-300', 'text-gray-600');
                this.classList.add('border-blue-600', 'bg-blue-50', 'text-blue-700');
            });
        });

        // Save password
        document.getElementById('savePasswordBtn').addEventListener('click', async function() {
            const btn = this;
            const currentPass = document.getElementById('currentPass').value;
            const newPass = document.getElementById('newPass').value;
            const confirmPass = document.getElementById('confirmPass').value;

            if (!currentPass || !newPass || !confirmPass) {
                showToast('All fields are required', 'error');
                return;
            }

            btn.disabled = true;
            btn.textContent = '...';

            try {
                const result = await postJSON('api/profile.php', {
                    action: 'update_password',
                    current_pass: currentPass,
                    new_pass: newPass,
                    confirm_pass: confirmPass
                });

                showToast(result.message, result.success ? 'success' : 'error');

                if (result.success) {
                    document.getElementById('currentPass').value = '';
                    document.getElementById('newPass').value = '';
                    document.getElementById('confirmPass').value = '';
                }
            } catch (error) {
                showToast('Network error', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = '<?php echo t('change_password'); ?>';
            }
        });

        // Save preferences
        document.getElementById('savePrefsBtn').addEventListener('click', async function() {
            const btn = this;
            const lang = document.getElementById('prefLang').value;
            const timezone = document.getElementById('prefTimezone').value;

            btn.disabled = true;
            btn.textContent = '...';

            try {
                const result = await postJSON('api/profile.php', {
                    action: 'update_preferences',
                    lang: lang,
                    timezone: timezone
                });

                showToast(result.message, result.success ? 'success' : 'error');

                if (result.success) {
                    // Update local time display
                    const now = new Date();
                    document.getElementById('localTimeDisplay').textContent = now.toLocaleString();
                }
            } catch (error) {
                showToast('Network error', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = '<?php echo t('save_changes'); ?>';
            }
        });

        // Update local time display every second
        setInterval(() => {
            const el = document.getElementById('localTimeDisplay');
            if (el) {
                const now = new Date();
                el.textContent = now.toLocaleString('sv-SE').replace('T', ' ').substring(0, 19);
            }
        }, 1000);
    </script>
</body>
</html>
