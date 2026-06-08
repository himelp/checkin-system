<?php
/**
 * Admin Logs Page
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/lang.php';

// Redirect if not admin
if (!isAdmin()) {
    header('Location: ../index.php');
    exit;
}

$csrfToken = generateCSRFToken();

// Get users for filter dropdown
$db = getDB();
$users = [];
if ($db) {
    $stmt = $db->query("SELECT id, name FROM users ORDER BY name");
    $users = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? DEFAULT_LANG; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - <?php echo t('admin'); ?> <?php echo t('history'); ?></title>
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
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <h1 class="text-2xl font-bold text-gray-800"><?php echo t('admin'); ?> <?php echo t('history'); ?></h1>
                <div class="flex gap-2">
                    <button id="exportCsvBtn" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 min-h-[44px]">
                        📊 Export CSV
                    </button>
                    <button id="exportPdfBtn" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 min-h-[44px]">
                        📄 Export PDF
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl shadow p-4">
                <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                        <input type="date" id="dateFrom" class="w-full px-3 py-2 border rounded-lg min-h-[44px]">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                        <input type="date" id="dateTo" class="w-full px-3 py-2 border rounded-lg min-h-[44px]">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">User</label>
                        <select id="userFilter" class="w-full px-3 py-2 border rounded-lg min-h-[44px]">
                            <option value="">All Users</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="statusFilter" class="w-full px-3 py-2 border rounded-lg min-h-[44px]">
                            <option value="">All</option>
                            <option value="active">Active</option>
                            <option value="done">Done</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button id="applyFilters" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 min-h-[44px]">
                            Apply Filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- Results Table -->
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Check-in</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Check-out</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP</th>
                            </tr>
                        </thead>
                        <tbody id="logsBody" class="divide-y divide-gray-200">
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-gray-500">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div id="pagination" class="p-4 border-t border-gray-200 flex items-center justify-between">
                    <span id="showingInfo" class="text-sm text-gray-500"></span>
                    <div id="pageButtons" class="flex gap-2"></div>
                </div>
            </div>
        </div>
    </main>

    <!-- Toast Container -->
    <div id="toastContainer" class="fixed top-4 right-4 z-50"></div>

    <script src="../assets/app.js"></script>
    <script>
        let currentPage = 1;
        const perPage = 20;

        // Load logs
        async function loadLogs(page = 1) {
            currentPage = page;
            
            const params = new URLSearchParams({
                page: page,
                per_page: perPage,
                date_from: document.getElementById('dateFrom').value,
                date_to: document.getElementById('dateTo').value,
                user_id: document.getElementById('userFilter').value,
                status: document.getElementById('statusFilter').value
            });
            
            try {
                const response = await fetch(`api/logs.php?${params}`);
                const data = await response.json();
                
                if (data.success) {
                    updateLogsTable(data.logs);
                    updatePagination(data.total, data.page, data.per_page);
                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
                console.error('Failed to load logs:', error);
                showToast('Failed to load logs', 'error');
            }
        }

        function updateLogsTable(logs) {
            const tbody = document.getElementById('logsBody');
            
            if (!logs || logs.length === 0) {
                tbody.innerHTML = `<tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">No records found</td></tr>`;
                return;
            }
            
            tbody.innerHTML = logs.map(log => {
                const statusClass = log.status === 'active' 
                    ? 'bg-green-100 text-green-800' 
                    : 'bg-gray-100 text-gray-800';
                const statusText = log.status === 'active' ? 'Active' : 'Done';
                
                return `
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-500">${log.id}</td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">${escapeHtml(log.name)}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">${log.date}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">${log.checkin_time || '--'}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">${log.checkout_time || '--'}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">${log.duration || '--'}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs font-medium rounded-full ${statusClass}">${statusText}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">${log.ip_address || '--'}</td>
                    </tr>
                `;
            }).join('');
        }

        function updatePagination(total, page, perPage) {
            const totalPages = Math.ceil(total / perPage);
            const start = (page - 1) * perPage + 1;
            const end = Math.min(page * perPage, total);
            
            document.getElementById('showingInfo').textContent = `Showing ${start}-${end} of ${total} records`;
            
            const pageButtons = document.getElementById('pageButtons');
            let buttons = '';
            
            if (page > 1) {
                buttons += `<button onclick="loadLogs(${page - 1})" class="px-3 py-1 border rounded hover:bg-gray-100">←</button>`;
            }
            
            for (let i = Math.max(1, page - 2); i <= Math.min(totalPages, page + 2); i++) {
                const active = i === page ? 'bg-blue-600 text-white' : 'hover:bg-gray-100';
                buttons += `<button onclick="loadLogs(${i})" class="px-3 py-1 border rounded ${active}">${i}</button>`;
            }
            
            if (page < totalPages) {
                buttons += `<button onclick="loadLogs(${page + 1})" class="px-3 py-1 border rounded hover:bg-gray-100">→</button>`;
            }
            
            pageButtons.innerHTML = buttons;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Export functions
        function getFilterParams() {
            return {
                date_from: document.getElementById('dateFrom').value,
                date_to: document.getElementById('dateTo').value,
                user_id: document.getElementById('userFilter').value,
                status: document.getElementById('statusFilter').value
            };
        }

        document.getElementById('exportCsvBtn').addEventListener('click', function() {
            const params = new URLSearchParams({...getFilterParams(), format: 'csv'});
            window.location.href = `export.php?${params}`;
        });

        document.getElementById('exportPdfBtn').addEventListener('click', function() {
            const params = new URLSearchParams({...getFilterParams(), format: 'pdf'});
            window.location.href = `export.php?${params}`;
        });

        document.getElementById('applyFilters').addEventListener('click', function() {
            loadLogs(1);
        });

        // Initial load
        loadLogs();
    </script>
</body>
</html>
