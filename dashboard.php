<?php
/**
 * Dashboard Page
 */

// Redirect to installer if not installed
if (!file_exists(__DIR__ . '/install/installed.lock')) {
    header('Location: install.php');
    exit;
}

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/config.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? DEFAULT_LANG; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - <?php echo t('dashboard'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .toast {
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from { transform: translateY(-100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
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
                <button id="langToggle" class="px-3 py-2 bg-gray-100 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-200 min-h-[44px] min-w-[44px]">
                    <?php echo ($_SESSION['lang'] ?? DEFAULT_LANG) === 'en' ? 'IT' : 'EN'; ?>
                </button>
                <a href="api/logout.php" class="px-3 py-2 bg-red-100 text-red-600 rounded-lg text-sm font-medium hover:bg-red-200 min-h-[44px] flex items-center">
                    <?php echo t('logout'); ?>
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto p-4 space-y-6 pb-24">
        <!-- Check-in/out Section -->
        <div id="checkinSection" class="bg-white rounded-xl shadow-lg p-6 sm:p-8 text-center">
            <!-- Initial state - will be updated by JS -->
            <div id="loadingState" class="py-8">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                <p class="mt-4 text-gray-500">Loading...</p>
            </div>

            <!-- Checked Out State -->
            <div id="checkedOutState" class="hidden">
                <p class="text-gray-600 mb-6"><?php echo t('welcome'); ?>, <?php echo htmlspecialchars($_SESSION['name']); ?>!</p>
                <button id="checkinBtn" 
                    class="w-full max-w-xs mx-auto py-6 px-8 bg-green-500 hover:bg-green-600 text-white text-xl font-bold rounded-xl shadow-lg transition duration-200 min-h-[88px] pulse-animation">
                    <?php echo t('checkin'); ?>
                </button>
            </div>

            <!-- Checked In State -->
            <div id="checkedInState" class="hidden">
                <p class="text-gray-600 mb-2"><?php echo t('currently_checkin'); ?></p>
                <div id="timer" class="text-4xl sm:text-5xl font-mono font-bold text-blue-600 mb-6">00:00:00</div>
                <button id="checkoutBtn" 
                    class="w-full max-w-xs mx-auto py-6 px-8 bg-red-500 hover:bg-red-600 text-white text-xl font-bold rounded-xl shadow-lg transition duration-200 min-h-[88px]">
                    <?php echo t('checkout'); ?>
                </button>
            </div>
        </div>

        <!-- Today Total Card -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-2"><?php echo t('today_total'); ?></h2>
            <p id="todayTotal" class="text-3xl font-bold text-blue-600">--</p>
        </div>

        <!-- History Table -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="p-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-700"><?php echo t('history'); ?></h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo t('date'); ?></th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo t('checkin'); ?></th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo t('checkout'); ?></th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo t('duration'); ?></th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo t('status_active'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="historyBody" class="divide-y divide-gray-200">
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500"><?php echo t('no_records'); ?></td>
                        </tr>
                    </tbody>
                </table>
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

    <script src="assets/app.js"></script>
    <script>
        let timerInterval = null;
        let currentCheckinTime = null;

        // Load initial status
        async function loadStatus() {
            try {
                const response = await fetch('api/status.php');
                const data = await response.json();
                
                document.getElementById('loadingState').classList.add('hidden');
                
                if (data.is_checkedin) {
                    showCheckedIn(data.checkin_time);
                } else {
                    showCheckedOut();
                }
                
                // Update today total
                const hours = Math.floor(data.today_total_minutes / 60);
                const minutes = data.today_total_minutes % 60;
                document.getElementById('todayTotal').textContent = `${hours}h ${minutes}min`;
                
                // Update history
                updateHistory(data.history);
                
            } catch (error) {
                console.error('Failed to load status:', error);
                showToast('Failed to load status', 'error');
            }
        }

        function showCheckedIn(checkinTime) {
            document.getElementById('checkedOutState').classList.add('hidden');
            document.getElementById('checkedInState').classList.remove('hidden');
            currentCheckinTime = checkinTime;
            startTimer(checkinTime);
        }

        function showCheckedOut() {
            document.getElementById('checkedInState').classList.add('hidden');
            document.getElementById('checkedOutState').classList.remove('hidden');
            if (timerInterval) {
                clearInterval(timerInterval);
                timerInterval = null;
            }
            document.getElementById('timer').textContent = '00:00:00';
        }

        function updateHistory(history) {
            const tbody = document.getElementById('historyBody');
            
            if (!history || history.length === 0) {
                tbody.innerHTML = `<tr><td colspan="5" class="px-4 py-8 text-center text-gray-500"><?php echo t('no_records'); ?></td></tr>`;
                return;
            }
            
            tbody.innerHTML = history.map(row => {
                const date = new Date(row.date).toLocaleDateString();
                const checkin = row.checkin_time ? row.checkin_time.split(' ')[1].substring(0, 5) : '--';
                const checkout = row.checkout_time ? row.checkout_time.split(' ')[1].substring(0, 5) : '--';
                const duration = row.duration_minutes ? formatDuration(row.duration_minutes) : '--';
                const statusClass = row.status === 'active' 
                    ? 'bg-green-100 text-green-800' 
                    : 'bg-gray-100 text-gray-800';
                const statusText = row.status === 'active' ? '<?php echo t('status_active'); ?>' : '<?php echo t('status_done'); ?>';
                
                return `
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-900">${date}</td>
                        <td class="px-4 py-3 text-sm text-gray-900">${checkin}</td>
                        <td class="px-4 py-3 text-sm text-gray-900">${checkout}</td>
                        <td class="px-4 py-3 text-sm text-gray-900">${duration}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs font-medium rounded-full ${statusClass}">${statusText}</span>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function formatDuration(minutes) {
            const h = Math.floor(minutes / 60);
            const m = minutes % 60;
            return h > 0 ? `${h}h ${m}min` : `${m}min`;
        }

        // Check-in button
        document.getElementById('checkinBtn').addEventListener('click', async function() {
            const btn = this;
            btn.disabled = true;
            btn.textContent = '...';
            
            try {
                const result = await postJSON('api/checkin.php', {});
                
                if (result.success) {
                    showToast(result.message, 'success');
                    showCheckedIn(result.checkin_time);
                    loadStatus(); // Refresh data
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                showToast('Network error', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = '<?php echo t('checkin'); ?>';
            }
        });

        // Check-out button
        document.getElementById('checkoutBtn').addEventListener('click', async function() {
            if (!confirm('<?php echo t('confirm_checkout'); ?>')) return;
            
            const btn = this;
            btn.disabled = true;
            btn.textContent = '...';
            
            try {
                const result = await postJSON('api/checkout.php', {});
                
                if (result.success) {
                    showToast(result.message, 'success');
                    showCheckedOut();
                    loadStatus(); // Refresh data
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                showToast('Network error', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = '<?php echo t('checkout'); ?>';
            }
        });

        // Language toggle
        document.getElementById('langToggle').addEventListener('click', async function() {
            try {
                const result = await postJSON('api/set_language.php', {});
                if (result.success) {
                    location.reload();
                }
            } catch (error) {
                showToast('Failed to change language', 'error');
            }
        });

        // Auto-refresh every 30 seconds
        setInterval(loadStatus, 30000);

        // Initial load
        loadStatus();
    </script>
</body>
</html>
