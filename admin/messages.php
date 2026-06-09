<?php
/**
 * Admin Messages Page
 */
ob_start();

if (!file_exists(__DIR__ . '/../install/installed.lock')) {
    header('Location: ../install.php');
    exit;
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../config.php';

secureHeaders();

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../index.php');
    exit;
}

$csrfToken = generateCSRFToken();

// Get users for dropdown
$db = getDB();
$users = [];
if ($db) {
    $stmt = $db->query("SELECT id, name, username FROM users WHERE status = 1 ORDER BY name");
    $users = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? DEFAULT_LANG; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo APP_NAME; ?> - <?php echo t('messages'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .toast { animation: slideIn 0.3s ease-out; }
        @keyframes slideIn {
            from { transform: translateY(-100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .modal { animation: fadeIn 0.2s ease-out; }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <?php include __DIR__ . '/../includes/admin_nav.php'; ?>

    <!-- Main Content -->
    <div class="lg:ml-64">
        <main class="p-4 sm:p-6 lg:p-8 pb-24 lg:pb-8">
            <div class="mb-6">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800"><?php echo t('messages'); ?></h1>
                <p class="text-gray-600 mt-1">Manage internal communications</p>
            </div>

            <!-- Tabs -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="flex border-b">
                    <button onclick="switchTab('inbox')" id="tabInbox" class="flex-1 py-4 px-6 text-sm font-medium text-blue-600 border-b-2 border-blue-600">
                        <?php echo t('inbox'); ?>
                    </button>
                    <button onclick="switchTab('sent')" id="tabSent" class="flex-1 py-4 px-6 text-sm font-medium text-gray-500 hover:text-gray-700">
                        <?php echo t('sent'); ?>
                    </button>
                    <button onclick="switchTab('compose')" id="tabCompose" class="flex-1 py-4 px-6 text-sm font-medium text-gray-500 hover:text-gray-700">
                        <?php echo t('compose'); ?>
                    </button>
                </div>

                <!-- Inbox -->
                <div id="inboxContent" class="divide-y divide-gray-200">
                    <div class="p-8 text-center text-gray-500"><?php echo t('no_messages'); ?></div>
                </div>

                <!-- Sent -->
                <div id="sentContent" class="divide-y divide-gray-200 hidden">
                    <div class="p-8 text-center text-gray-500"><?php echo t('no_messages'); ?></div>
                </div>

                <!-- Compose -->
                <div id="composeContent" class="hidden p-4 sm:p-6">
                    <form id="composeForm" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"><?php echo t('to'); ?> *</label>
                            <select id="composeReceiver" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 min-h-[44px]">
                                <option value=""><?php echo t('select_user'); ?></option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['name']); ?> (<?php echo htmlspecialchars($user['username']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"><?php echo t('subject'); ?> *</label>
                            <input type="text" id="composeSubject" required maxlength="255" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 min-h-[44px]" placeholder="<?php echo t('subject'); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"><?php echo t('message_body'); ?> *</label>
                            <textarea id="composeBody" required rows="6" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="<?php echo t('message_body'); ?>"></textarea>
                        </div>
                        <button type="submit" id="sendBtn" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition min-h-[44px]">
                            <?php echo t('send_message'); ?>
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <!-- View Message Modal -->
    <div id="viewModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
        <div class="modal bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="p-4 border-b flex items-center justify-between">
                <h2 id="viewSubject" class="text-lg font-semibold text-gray-800 truncate pr-4"></h2>
                <button onclick="closeViewModal()" class="text-gray-400 hover:text-gray-600 p-1">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-4">
                <div class="flex items-center gap-4 mb-4 text-sm text-gray-500">
                    <span id="viewFrom"></span>
                    <span id="viewDate"></span>
                </div>
                <div id="viewBody" class="text-gray-700 whitespace-pre-wrap"></div>
            </div>
            <div class="p-4 border-t flex gap-3">
                <button id="replyBtn" onclick="replyToMessage()" class="flex-1 py-2 bg-blue-100 text-blue-700 font-medium rounded-lg hover:bg-blue-200 transition min-h-[44px]">
                    <?php echo t('reply'); ?>
                </button>
                <button onclick="deleteMessage()" class="flex-1 py-2 bg-red-100 text-red-700 font-medium rounded-lg hover:bg-red-200 transition min-h-[44px]">
                    <?php echo t('delete'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer" class="fixed top-20 left-1/2 -translate-x-1/2 z-50"></div>

    <script>
        let currentTab = 'inbox';
        let currentMessageId = null;
        let inboxMessages = [];
        let sentMessages = [];

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            loadInbox();
            loadSent();
        });

        // Tab switching
        function switchTab(tab) {
            currentTab = tab;
            ['inbox', 'sent', 'compose'].forEach(t => {
                const tabEl = document.getElementById('tab' + t.charAt(0).toUpperCase() + t.slice(1));
                const contentEl = document.getElementById(t + 'Content');
                if (t === tab) {
                    tabEl.className = 'flex-1 py-4 px-6 text-sm font-medium text-blue-600 border-b-2 border-blue-600';
                    contentEl.classList.remove('hidden');
                } else {
                    tabEl.className = 'flex-1 py-4 px-6 text-sm font-medium text-gray-500 hover:text-gray-700';
                    contentEl.classList.add('hidden');
                }
            });
        }

        // Load inbox
        async function loadInbox() {
            try {
                const response = await fetch('../api/messages.php?action=inbox');
                const data = await response.json();
                inboxMessages = data.messages || [];
                renderInbox();
            } catch (error) {
                console.error('Failed to load inbox:', error);
            }
        }

        // Load sent
        async function loadSent() {
            try {
                const response = await fetch('../api/messages.php?action=sent');
                const data = await response.json();
                sentMessages = data.messages || [];
                renderSent();
            } catch (error) {
                console.error('Failed to load sent:', error);
            }
        }

        // Render inbox
        function renderInbox() {
            const container = document.getElementById('inboxContent');
            if (inboxMessages.length === 0) {
                container.innerHTML = `<div class="p-8 text-center text-gray-500"><?php echo t('no_messages'); ?></div>`;
                return;
            }
            container.innerHTML = inboxMessages.map(msg => `
                <div onclick="viewMessage(${msg.id})" class="p-4 hover:bg-gray-50 cursor-pointer transition ${msg.is_read ? '' : 'bg-blue-50'}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                ${msg.is_read ? '' : '<span class="w-2 h-2 bg-blue-600 rounded-full flex-shrink-0"></span>'}
                                <span class="font-medium text-gray-900 truncate ${msg.is_read ? '' : 'font-bold'}">${escapeHtml(msg.subject)}</span>
                            </div>
                            <p class="text-sm text-gray-500 mt-1"><?php echo t('from'); ?>: ${escapeHtml(msg.sender_name)}</p>
                        </div>
                        <span class="text-xs text-gray-400 flex-shrink-0">${formatDate(msg.created_at)}</span>
                    </div>
                </div>
            `).join('');
        }

        // Render sent
        function renderSent() {
            const container = document.getElementById('sentContent');
            if (sentMessages.length === 0) {
                container.innerHTML = `<div class="p-8 text-center text-gray-500"><?php echo t('no_messages'); ?></div>`;
                return;
            }
            container.innerHTML = sentMessages.map(msg => `
                <div onclick="viewSentMessage(${msg.id})" class="p-4 hover:bg-gray-50 cursor-pointer transition">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <span class="font-medium text-gray-900 truncate">${escapeHtml(msg.subject)}</span>
                            <p class="text-sm text-gray-500 mt-1"><?php echo t('to'); ?>: ${escapeHtml(msg.receiver_name)}</p>
                        </div>
                        <span class="text-xs text-gray-400 flex-shrink-0">${formatDate(msg.created_at)}</span>
                    </div>
                </div>
            `).join('');
        }

        // View message (inbox)
        async function viewMessage(id) {
            try {
                const response = await fetch(`../api/messages.php?action=message&id=${id}`);
                const data = await response.json();
                if (data.success) {
                    currentMessageId = id;
                    document.getElementById('viewSubject').textContent = data.message.subject;
                    document.getElementById('viewFrom').textContent = '<?php echo t('from'); ?>: ' + data.message.sender_name;
                    document.getElementById('viewDate').textContent = data.message.created_at;
                    document.getElementById('viewBody').textContent = data.message.body;
                    document.getElementById('replyBtn').dataset.senderId = data.message.sender_id;
                    document.getElementById('replyBtn').dataset.subject = data.message.subject;
                    openModal('viewModal');
                    loadInbox();
                }
            } catch (error) {
                showToast('Failed to load message', 'error');
            }
        }

        // View sent message
        async function viewSentMessage(id) {
            try {
                const response = await fetch(`../api/messages.php?action=message&id=${id}`);
                const data = await response.json();
                if (data.success) {
                    currentMessageId = id;
                    document.getElementById('viewSubject').textContent = data.message.subject;
                    document.getElementById('viewFrom').textContent = '<?php echo t('to'); ?>: ' + data.message.receiver_name;
                    document.getElementById('viewDate').textContent = data.message.created_at;
                    document.getElementById('viewBody').textContent = data.message.body;
                    document.getElementById('replyBtn').classList.add('hidden');
                    openModal('viewModal');
                }
            } catch (error) {
                showToast('Failed to load message', 'error');
            }
        }

        // Reply to message
        function replyToMessage() {
            const senderId = document.getElementById('replyBtn').dataset.senderId;
            const subject = document.getElementById('replyBtn').dataset.subject;
            closeViewModal();
            switchTab('compose');
            document.getElementById('composeReceiver').value = senderId;
            document.getElementById('composeSubject').value = 'Re: ' + subject;
        }

        // Delete message
        async function deleteMessage() {
            if (!confirm('<?php echo t('delete'); ?> this message?')) return;
            try {
                const response = await fetch('../api/messages.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=delete&message_id=${currentMessageId}`
                });
                const data = await response.json();
                showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    closeViewModal();
                    loadInbox();
                    loadSent();
                }
            } catch (error) {
                showToast('Failed to delete message', 'error');
            }
        }

        // Compose form
        document.getElementById('composeForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('sendBtn');
            btn.disabled = true;
            btn.textContent = '...';

            try {
                const response = await fetch('../api/messages.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=send&receiver_id=${encodeURIComponent(document.getElementById('composeReceiver').value)}&subject=${encodeURIComponent(document.getElementById('composeSubject').value)}&body=${encodeURIComponent(document.getElementById('composeBody').value)}`
                });
                const data = await response.json();
                showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    loadSent();
                    document.getElementById('composeForm').reset();
                    switchTab('sent');
                }
            } catch (error) {
                showToast('Failed to send message', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = '<?php echo t('send_message'); ?>';
            }
        });

        // Modal functions
        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
            document.getElementById(id).classList.add('flex');
        }

        function closeViewModal() {
            document.getElementById('viewModal').classList.add('hidden');
            document.getElementById('viewModal').classList.remove('flex');
            document.getElementById('replyBtn').classList.remove('hidden');
        }

        // Utility functions
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatDate(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
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

        // Close modal on outside click
        document.getElementById('viewModal').addEventListener('click', function(e) {
            if (e.target === this) closeViewModal();
        });
    </script>
</body>
</html>
