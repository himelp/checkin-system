<?php
/**
 * Analytics Page
 */

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

// Redirect if not logged in
if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$csrfToken = generateCSRFToken();
$isAdmin = isAdmin();
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? DEFAULT_LANG; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - <?php echo t('analytics'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
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
    <main class="max-w-7xl mx-auto p-4 space-y-6 pb-24">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow-lg p-4 sm:p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <h2 class="text-2xl font-bold text-gray-800"><?php echo t('analytics'); ?></h2>
                <div class="flex flex-col sm:flex-row gap-3">
                    <?php if ($isAdmin): ?>
                    <select id="userSelect" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 min-h-[44px]">
                        <option value=""><?php echo t('select_user'); ?></option>
                    </select>
                    <?php endif; ?>
                    <button id="exportPdfBtn" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium min-h-[44px] flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <?php echo t('export_pdf'); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-lg p-4 sm:p-6">
            <div class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1"><?php echo t('date_range'); ?></label>
                    <div class="flex gap-2">
                        <input type="date" id="dateFrom" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 min-h-[44px]">
                        <input type="date" id="dateTo" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 min-h-[44px]">
                    </div>
                </div>
                <div class="flex gap-2 items-end">
                    <button id="filter7Days" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium min-h-[44px]">
                        7 <?php echo t('days'); ?>
                    </button>
                    <button id="filter30Days" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium min-h-[44px]">
                        30 <?php echo t('days'); ?>
                    </button>
                    <button id="filterThisMonth" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium min-h-[44px]">
                        <?php echo t('this_month'); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div id="statsContainer" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
            <div class="bg-white rounded-xl shadow-lg p-4 sm:p-6">
                <p class="text-sm text-gray-500 mb-1"><?php echo t('total_hours'); ?></p>
                <p id="statTotalHours" class="text-2xl sm:text-3xl font-bold text-blue-600">--</p>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-4 sm:p-6">
                <p class="text-sm text-gray-500 mb-1"><?php echo t('avg_daily'); ?></p>
                <p id="statAvgDaily" class="text-2xl sm:text-3xl font-bold text-green-600">--</p>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-4 sm:p-6">
                <p class="text-sm text-gray-500 mb-1"><?php echo t('days_present'); ?></p>
                <p id="statDaysPresent" class="text-2xl sm:text-3xl font-bold text-purple-600">--</p>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-4 sm:p-6">
                <p class="text-sm text-gray-500 mb-1"><?php echo t('days_absent'); ?></p>
                <p id="statDaysAbsent" class="text-2xl sm:text-3xl font-bold text-red-600">--</p>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-4 sm:p-6 col-span-2 sm:col-span-1">
                <p class="text-sm text-gray-500 mb-1"><?php echo t('longest_session'); ?></p>
                <p id="statLongestSession" class="text-2xl sm:text-3xl font-bold text-orange-600">--</p>
            </div>
        </div>

        <!-- Charts -->
        <div id="chartsContainer" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Daily Hours Chart -->
            <div class="bg-white rounded-xl shadow-lg p-4 sm:p-6">
                <h3 class="text-lg font-semibold text-gray-700 mb-4"><?php echo t('daily_hours'); ?></h3>
                <div class="relative h-64">
                    <canvas id="dailyHoursChart"></canvas>
                </div>
            </div>

            <!-- Attendance Donut Chart -->
            <div class="bg-white rounded-xl shadow-lg p-4 sm:p-6">
                <h3 class="text-lg font-semibold text-gray-700 mb-4"><?php echo t('attendance_rate'); ?></h3>
                <div class="relative h-64">
                    <canvas id="attendanceChart"></canvas>
                </div>
            </div>

            <!-- Weekly Comparison Chart -->
            <div class="bg-white rounded-xl shadow-lg p-4 sm:p-6 lg:col-span-2">
                <h3 class="text-lg font-semibold text-gray-700 mb-4"><?php echo t('weekly_comparison'); ?></h3>
                <div class="relative h-64">
                    <canvas id="weeklyChart"></canvas>
                </div>
            </div>
        </div>
    </main>

    <!-- Toast Container -->
    <div id="toastContainer" class="fixed top-20 left-1/2 -translate-x-1/2 z-50"></div>

    <script>
        let dailyHoursChart, attendanceChart, weeklyChart;
        let currentUserId = '<?php echo $isAdmin ? "" : $_SESSION['user_id']; ?>';
        let currentDateFrom, currentDateTo;

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Set default date range (last 30 days)
            const today = new Date();
            const thirtyDaysAgo = new Date(today);
            thirtyDaysAgo.setDate(today.getDate() - 30);
            
            document.getElementById('dateFrom').value = formatDate(thirtyDaysAgo);
            document.getElementById('dateTo').value = formatDate(today);
            
            currentDateFrom = document.getElementById('dateFrom').value;
            currentDateTo = document.getElementById('dateTo').value;
            
            // Initialize charts
            initCharts();
            
            // Load initial data
            loadAnalytics();
            
            // Load users for admin
            <?php if ($isAdmin): ?>
            loadUsers();
            document.getElementById('userSelect').addEventListener('change', function() {
                currentUserId = this.value;
                loadAnalytics();
            });
            <?php endif; ?>
            
            // Filter buttons
            document.getElementById('filter7Days').addEventListener('click', function() {
                setDateRange(7);
            });
            
            document.getElementById('filter30Days').addEventListener('click', function() {
                setDateRange(30);
            });
            
            document.getElementById('filterThisMonth').addEventListener('click', function() {
                const today = new Date();
                const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                document.getElementById('dateFrom').value = formatDate(firstDay);
                document.getElementById('dateTo').value = formatDate(today);
                currentDateFrom = document.getElementById('dateFrom').value;
                currentDateTo = document.getElementById('dateTo').value;
                loadAnalytics();
            });
            
            // Date inputs
            document.getElementById('dateFrom').addEventListener('change', function() {
                currentDateFrom = this.value;
                loadAnalytics();
            });
            
            document.getElementById('dateTo').addEventListener('change', function() {
                currentDateTo = this.value;
                loadAnalytics();
            });
            
            // Export PDF
            document.getElementById('exportPdfBtn').addEventListener('click', exportToPDF);
        });

        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        function setDateRange(days) {
            const today = new Date();
            const pastDate = new Date(today);
            pastDate.setDate(today.getDate() - days);
            
            document.getElementById('dateFrom').value = formatDate(pastDate);
            document.getElementById('dateTo').value = formatDate(today);
            currentDateFrom = document.getElementById('dateFrom').value;
            currentDateTo = document.getElementById('dateTo').value;
            loadAnalytics();
        }

        function initCharts() {
            // Daily Hours Chart
            const dailyCtx = document.getElementById('dailyHoursChart').getContext('2d');
            dailyHoursChart = new Chart(dailyCtx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: '<?php echo t('hours'); ?>',
                        data: [],
                        backgroundColor: 'rgba(59, 130, 246, 0.8)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: '<?php echo t('hours'); ?>'
                            }
                        }
                    }
                }
            });

            // Attendance Donut Chart
            const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
            attendanceChart = new Chart(attendanceCtx, {
                type: 'doughnut',
                data: {
                    labels: ['<?php echo t('present'); ?>', '<?php echo t('absent'); ?>'],
                    datasets: [{
                        data: [0, 0],
                        backgroundColor: [
                            'rgba(16, 185, 129, 0.8)',
                            'rgba(239, 68, 68, 0.8)'
                        ],
                        borderColor: [
                            'rgba(16, 185, 129, 1)',
                            'rgba(239, 68, 68, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // Weekly Comparison Chart
            const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
            weeklyChart = new Chart(weeklyCtx, {
                type: 'bar',
                data: {
                    labels: ['<?php echo t('this_week'); ?>', '<?php echo t('last_week'); ?>'],
                    datasets: [{
                        label: '<?php echo t('hours'); ?>',
                        data: [0, 0],
                        backgroundColor: [
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(156, 163, 175, 0.8)'
                        ],
                        borderColor: [
                            'rgba(59, 130, 246, 1)',
                            'rgba(156, 163, 175, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: '<?php echo t('hours'); ?>'
                            }
                        }
                    }
                }
            });
        }

        async function loadAnalytics() {
            try {
                let url = `api/analytics_data.php?date_from=${currentDateFrom}&date_to=${currentDateTo}`;
                if (currentUserId) {
                    url += `&user_id=${currentUserId}`;
                }
                
                const response = await fetch(url);
                const data = await response.json();
                
                if (data.success) {
                    updateStats(data.stats);
                    updateCharts(data);
                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
                console.error('Failed to load analytics:', error);
                showToast('Failed to load analytics data', 'error');
            }
        }

        function updateStats(stats) {
            document.getElementById('statTotalHours').textContent = stats.total_hours + 'h';
            document.getElementById('statAvgDaily').textContent = stats.avg_daily + 'h';
            document.getElementById('statDaysPresent').textContent = stats.days_present;
            document.getElementById('statDaysAbsent').textContent = stats.days_absent;
            document.getElementById('statLongestSession').textContent = stats.longest_session + 'h';
        }

        function updateCharts(data) {
            // Update Daily Hours Chart
            dailyHoursChart.data.labels = data.daily_hours.map(d => d.date);
            dailyHoursChart.data.datasets[0].data = data.daily_hours.map(d => d.hours);
            dailyHoursChart.update();

            // Update Attendance Chart
            attendanceChart.data.datasets[0].data = [data.attendance.present, data.attendance.absent];
            attendanceChart.update();

            // Update Weekly Chart
            weeklyChart.data.datasets[0].data = [data.weekly_comparison.this_week, data.weekly_comparison.last_week];
            weeklyChart.update();
        }

        async function loadUsers() {
            try {
                const response = await fetch('api/users_list.php');
                const data = await response.json();
                
                if (data.success) {
                    const select = document.getElementById('userSelect');
                    data.users.forEach(user => {
                        const option = document.createElement('option');
                        option.value = user.id;
                        option.textContent = user.name;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Failed to load users:', error);
            }
        }

        async function exportToPDF() {
            const btn = document.getElementById('exportPdfBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Generating...';
            btn.disabled = true;
            
            try {
                const container = document.getElementById('chartsContainer');
                const canvas = await html2canvas(container, {
                    scale: 2,
                    useCORS: true,
                    logging: false
                });
                
                const imgData = canvas.toDataURL('image/png');
                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF('landscape');
                
                const imgWidth = 280;
                const imgHeight = (canvas.height * imgWidth) / canvas.width;
                
                pdf.addImage(imgData, 'PNG', 10, 10, imgWidth, imgHeight);
                pdf.save(`analytics-${currentDateFrom}-to-${currentDateTo}.pdf`);
                
                showToast('PDF exported successfully', 'success');
            } catch (error) {
                console.error('PDF export failed:', error);
                showToast('Failed to export PDF', 'error');
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
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
    </script>
</body>
</html>
