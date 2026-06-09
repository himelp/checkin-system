<?php
/**
 * Calendar Page
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
    <title><?php echo APP_NAME; ?> - <?php echo t('calendar'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
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
        .fc-daygrid-day {
            cursor: pointer;
        }
        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin: 0 1px;
        }
        .dot-green { background-color: #10b981; }
        .dot-yellow { background-color: #f59e0b; }
        .dot-red { background-color: #ef4444; }
        .dot-gray { background-color: #9ca3af; }
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
                <h2 class="text-2xl font-bold text-gray-800"><?php echo t('calendar'); ?></h2>
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

        <!-- Calendar -->
        <div id="calendarContainer" class="bg-white rounded-xl shadow-lg p-4 sm:p-6">
            <div id="calendar"></div>
        </div>

        <!-- Legend -->
        <div class="bg-white rounded-xl shadow-lg p-4 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-3"><?php echo t('legend'); ?></h3>
            <div class="flex flex-wrap gap-4">
                <div class="flex items-center gap-2">
                    <span class="dot dot-green"></span>
                    <span class="text-sm text-gray-600"><?php echo t('complete'); ?></span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="dot dot-yellow"></span>
                    <span class="text-sm text-gray-600"><?php echo t('checkin_only'); ?></span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="dot dot-red"></span>
                    <span class="text-sm text-gray-600"><?php echo t('absent'); ?></span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="dot dot-gray"></span>
                    <span class="text-sm text-gray-600"><?php echo t('future_weekend'); ?></span>
                </div>
            </div>
        </div>
    </main>

    <!-- Day Detail Modal -->
    <div id="dayModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 id="modalDate" class="text-xl font-bold text-gray-800"></h3>
                    <button id="closeModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div id="modalContent" class="space-y-4">
                    <!-- Content loaded dynamically -->
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer" class="fixed top-20 left-1/2 -translate-x-1/2 z-50"></div>

    <script>
        let calendar;
        let currentUserId = '<?php echo $isAdmin ? "" : $_SESSION['user_id']; ?>';

        // Initialize calendar
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            
            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth'
                },
                events: function(info, successCallback, failureCallback) {
                    fetchEvents(info.start, info.end, successCallback, failureCallback);
                },
                eventClick: function(info) {
                    showDayModal(info.event);
                },
                datesSet: function(info) {
                    // Reload events when month changes
                    calendar.refetchEvents();
                }
            });
            
            calendar.render();
            
            // Load users for admin
            <?php if ($isAdmin): ?>
            loadUsers();
            document.getElementById('userSelect').addEventListener('change', function() {
                currentUserId = this.value;
                calendar.refetchEvents();
            });
            <?php endif; ?>
            
            // Export PDF
            document.getElementById('exportPdfBtn').addEventListener('click', exportToPDF);
            
            // Close modal
            document.getElementById('closeModal').addEventListener('click', closeModal);
            document.getElementById('dayModal').addEventListener('click', function(e) {
                if (e.target === this) closeModal();
            });
        });

        async function fetchEvents(start, end, successCallback, failureCallback) {
            try {
                const year = start.getFullYear();
                const month = start.getMonth() + 1;
                
                let url = `api/calendar_data.php?year=${year}&month=${month}`;
                if (currentUserId) {
                    url += `&user_id=${currentUserId}`;
                }
                
                const response = await fetch(url);
                const data = await response.json();
                
                if (data.success) {
                    successCallback(data.events);
                } else {
                    failureCallback(data.message);
                }
            } catch (error) {
                failureCallback(error);
            }
        }

        async function showDayModal(event) {
            const dateStr = event.startStr;
            const dayDetail = event.extendedProps.day_detail;
            
            document.getElementById('modalDate').textContent = new Date(dateStr).toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            
            let content = '';
            
            if (!dayDetail || dayDetail.status === 'future' || dayDetail.status === 'weekend') {
                content = `
                    <div class="text-center py-8">
                        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <p class="text-gray-500">${dayDetail?.status === 'weekend' ? '<?php echo t('weekend'); ?>' : '<?php echo t('no_data'); ?>'}</p>
                    </div>
                `;
            } else {
                const statusColors = {
                    'complete': 'bg-green-100 text-green-800',
                    'checkin_only': 'bg-yellow-100 text-yellow-800',
                    'absent': 'bg-red-100 text-red-800'
                };
                const statusText = {
                    'complete': '<?php echo t('complete'); ?>',
                    'checkin_only': '<?php echo t('checkin_only'); ?>',
                    'absent': '<?php echo t('absent'); ?>'
                };
                
                content = `
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600"><?php echo t('status'); ?></span>
                            <span class="px-3 py-1 rounded-full text-sm font-medium ${statusColors[dayDetail.status] || 'bg-gray-100 text-gray-800'}">${statusText[dayDetail.status] || dayDetail.status}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600"><?php echo t('checkin'); ?></span>
                            <span class="font-medium">${dayDetail.checkin_time || '--:--'}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600"><?php echo t('checkout'); ?></span>
                            <span class="font-medium">${dayDetail.checkout_time || '--:--'}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600"><?php echo t('duration'); ?></span>
                            <span class="font-medium">${dayDetail.duration || '0h 0min'}</span>
                        </div>
                    </div>
                `;
            }
            
            document.getElementById('modalContent').innerHTML = content;
            document.getElementById('dayModal').classList.remove('hidden');
            document.getElementById('dayModal').classList.add('flex');
        }

        function closeModal() {
            document.getElementById('dayModal').classList.add('hidden');
            document.getElementById('dayModal').classList.remove('flex');
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
                const container = document.getElementById('calendarContainer');
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
                pdf.save(`calendar-${new Date().toISOString().split('T')[0]}.pdf`);
                
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
