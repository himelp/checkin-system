<?php
/**
 * Admin Dashboard
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/lang.php';

// Redirect if not admin
if (!isAdmin()) {
    header('Location: ../index.php');
    exit;
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? DEFAULT_LANG; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - <?php echo t('admin'); ?> <?php echo t('dashboard'); ?></title>
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
    <?php include __DIR__ . '/../includes/admin_nav.php'; ?>

    <!-- Main Content -->
    <main class="lg:ml-64 pb-20 lg:pb-8">
        <div class="max-w-6xl mx-auto p-4 space-y-6">
            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-800"><?php echo t('admin'); ?> <?php echo t('dashboard'); ?></h1>
                <span class="text-sm text-gray-500"><?php echo date('d/m/Y H:i'); ?></span>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Users -->
                <div class="bg-white rounded-xl shadow p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <span class="text-2xl">👥</span>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Total Users</p>
                            <p id="totalUsers" class="text-2xl font-bold text-gray-800">--</p>
                        </div>
                    </div>
                </div>

                <!-- Checked In Now -->
                <div class="bg-white rounded-xl shadow p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <span class="text-2xl">✅</span>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Checked In Now</p>
                            <p id="currentCheckins" class="text-2xl font-bold text-green-600">--</p>
                        </div>
                    </div>
                </div>

                <!-- Today Hours -->
                <div class="bg-white rounded-xl shadow p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <span class="text-2xl">⏱️</span>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Today Hours</p>
                            <p id="todayHours" class="text-2xl font-bold text-purple-600">--</p>
                        </div>
                    </div>
                </div>

                <!-- Month Hours -->
                <div class="bg-white rounded-xl shadow p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                            <span class="text-2xl">📅</span>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Month Hours</p>
                            <p id="monthHours" class="text-2xl font-bold text-orange-600">--</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Live Status Table -->
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <div class="p-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-700">Currently Checked In</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Check-in Time</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                            </tr>
                        </thead>
                        <tbody id="activeList" class="divide-y divide-gray-200">
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-gray-500">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <div class="p-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-700">Recent Activity</h2>
                </div>
                <div id="recentActivity" class="divide-y divide-gray-200 max-h-96 overflow-y-auto">
                    <div class="p-4 text-center text-gray-500">Loading...</div>
                </div>
            </div>
        </div>
    </main>

    <!-- Toast Container -->
    <div id="toastContainer" class="fixed top-4 right-4 z-50"></div>

    <script src="../assets/app.js"></script>
    <script>
        // Load stats
        async function loadStats() {
            try {
                const response = await fetch('api/stats.php');
                const data = await response.json();
                
                if (data.success) {
                    // Update stat cards
                    document.getElementById('totalUsers').textContent = data.total_users;
                    document.getElementById('currentCheckins').textContent = data.current_checkins;
                    document.getElementById('todayHours').textContent = formatHours(data.today_hours);
                    document.getElementById('monthHours').textContent = formatHours(data.month_hours);
                    
                    // Update active list
                    updateActiveList(data.active_list);
                    
                    // Update recent activity
                    updateRecentActivity(data.recent_activity);
                }
            } catch (error) {
                console.error('Failed to load stats:', error);
                showToast('Failed to load stats', 'error');
            }
        }

        function formatHours(minutes) {
            const h = Math.floor(minutes / 60);
            const m = minutes % 60;
            return h > 0 ? `${h}h ${m}m` : `${m}m`;
        }

        function updateActiveList(list) {
            const tbody = document.getElementById('activeList');
            
            if (!list || list.length === 0) {
                tbody.innerHTML = `<tr><td colspan="3" class="px-4 py-8 text-center text-gray-500">No one is currently checked in</td></tr>`;
                return;
            }
            
            tbody.innerHTML = list.map(item => `
                <tr>
                    <td class="px-4 py-3 text-sm font-medium text-gray-900">${escapeHtml(item.name)}</td>
                    <td class="px-4 py-3 text-sm text-gray-500">${item.checkin_time}</td>
                    <td class="px-4 py-3 text-sm text-blue-600 font-mono">${item.duration_so_far}</td>
                </tr>
            `).join('');
        }

        function updateRecentActivity(list) {
            const container = document.getElementById('recentActivity');
            
            if (!list || list.length === 0) {
                container.innerHTML = `<div class="p-4 text-center text-gray-500">No recent activity</div>`;
                return;
            }
            
            container.innerHTML = list.map(item => {
                const icon = item.action === 'checkin' ? '🟢' : '🔴';
                const actionText = item.action === 'checkin' ? 'checked in' : 'checked out';
                return `
                    <div class="p-4 flex items-center gap-3">
                        <span class="text-xl">${icon}</span>
                        <div class="flex-1">
                            <p class="text-sm text-gray-800"><strong>${escapeHtml(item.name)}</strong> ${actionText}</p>
                            <p class="text-xs text-gray-500">${item.time}</p>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Auto-refresh every 30 seconds
        setInterval(loadStats, 30000);

        // Initial load
        loadStats();
    </script>
</body>
</html>
