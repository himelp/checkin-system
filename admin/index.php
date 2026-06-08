<?php
/**
 * Admin Dashboard Page
 */
ob_start();

// Redirect to installer if not installed
if (!file_exists(__DIR__ . '/../install/installed.lock')) {
    header('Location: ../install.php');
    exit;
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../config.php';

secureHeaders();

// Only admin can access
if (!isLoggedIn() || !isAdmin()) {
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
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo APP_NAME; ?> - Admin Dashboard</title>
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
    <div class="lg:ml-64">
        <main class="p-4 sm:p-6 lg:p-8">
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Admin Dashboard</h1>
                <p class="text-gray-600 mt-1">Overview of CheckTrack activity</p>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <!-- Total Users -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Total Users</p>
                            <p id="statTotalUsers" class="text-3xl font-bold text-blue-600 mt-1">--</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Checked In Now -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Checked In NOW</p>
                            <p id="statCurrentCheckins" class="text-3xl font-bold text-green-600 mt-1">--</p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Today Hours -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Today Total Hours</p>
                            <p id="statTodayHours" class="text-3xl font-bold text-purple-600 mt-1">--</p>
                        </div>
                        <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Month Hours -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Month Total Hours</p>
                            <p id="statMonthHours" class="text-3xl font-bold text-orange-600 mt-1">--</p>
                        </div>
                        <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                <a href="logs.php" class="bg-white rounded-xl shadow-lg p-4 flex items-center gap-4 hover:bg-gray-50 transition">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800">View Logs</h3>
                        <p class="text-sm text-gray-500">Check-in/out history</p>
                    </div>
                </a>
                <a href="users.php" class="bg-white rounded-xl shadow-lg p-4 flex items-center gap-4 hover:bg-gray-50 transition">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800">Manage Users</h3>
                        <p class="text-sm text-gray-500">Add, edit, remove users</p>
                    </div>
                </a>
                <a href="settings.php" class="bg-white rounded-xl shadow-lg p-4 flex items-center gap-4 hover:bg-gray-50 transition">
                    <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800">Settings</h3>
                        <p class="text-sm text-gray-500">Configure application</p>
                    </div>
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Live Status Table -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="p-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-700">Currently Checked In</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Check-in Time</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                                </tr>
                            </thead>
                            <tbody id="activeListBody" class="divide-y divide-gray-200">
                                <tr>
                                    <td colspan="3" class="px-4 py-8 text-center text-gray-500">No active check-ins</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="p-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-700">Recent Activity</h2>
                    </div>
                    <div class="overflow-x-auto max-h-96 overflow-y-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 sticky top-0">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                </tr>
                            </thead>
                            <tbody id="recentActivityBody" class="divide-y divide-gray-200">
                                <tr>
                                    <td colspan="3" class="px-4 py-8 text-center text-gray-500">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer" class="fixed top-20 left-1/2 -translate-x-1/2 z-50"></div>

    <script>
        // Load stats
        async function loadStats() {
            try {
                const response = await fetch('api/stats.php');
                const data = await response.json();
                
                if (data.success) {
                    // Update stat cards
                    document.getElementById('statTotalUsers').textContent = data.total_users;
                    document.getElementById('statCurrentCheckins').textContent = data.current_checkins;
                    
                    // Format hours
                    const todayHours = Math.floor(data.today_hours / 60);
                    const todayMins = data.today_hours % 60;
                    document.getElementById('statTodayHours').textContent = `${todayHours}h ${todayMins}m`;
                    
                    const monthHours = Math.floor(data.month_hours / 60);
                    const monthMins = data.month_hours % 60;
                    document.getElementById('statMonthHours').textContent = `${monthHours}h ${monthMins}m`;
                    
                    // Update active list
                    const activeListBody = document.getElementById('activeListBody');
                    if (data.active_list && data.active_list.length > 0) {
                        activeListBody.innerHTML = data.active_list.map(item => `
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900">${escapeHtml(item.name)}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">${item.checkin_time.split(' ')[1].substring(0, 5)}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">${item.duration_so_far}</td>
                            </tr>
                        `).join('');
                    } else {
                        activeListBody.innerHTML = `<tr><td colspan="3" class="px-4 py-8 text-center text-gray-500">No active check-ins</td></tr>`;
                    }
                    
                    // Update recent activity
                    const recentActivityBody = document.getElementById('recentActivityBody');
                    if (data.recent_activity && data.recent_activity.length > 0) {
                        recentActivityBody.innerHTML = data.recent_activity.map(item => {
                            const actionColor = item.action === 'checkin' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                            const actionText = item.action === 'checkin' ? 'Check In' : 'Check Out';
                            const time = item.time.split(' ')[1].substring(0, 5);
                            
                            return `
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-900">${escapeHtml(item.name)}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full ${actionColor}">${actionText}</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500">${time}</td>
                                </tr>
                            `;
                        }).join('');
                    } else {
                        recentActivityBody.innerHTML = `<tr><td colspan="3" class="px-4 py-8 text-center text-gray-500">No recent activity</td></tr>`;
                    }
                }
            } catch (error) {
                console.error('Failed to load stats:', error);
                showToast('Failed to load stats', 'error');
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

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

        // Auto-refresh every 30 seconds
        setInterval(loadStats, 30000);

        // Initial load
        loadStats();
    </script>
</body>
</html>
