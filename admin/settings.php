<?php
/**
 * Admin Settings Page
 */

// Redirect to installer if not installed
if (!file_exists(__DIR__ . '/../install/installed.lock')) {
    header('Location: ../install.php');
    exit;
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../includes/sheets.php';
require_once __DIR__ . '/../config.php';

// Only admin can access
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../index.php');
    exit;
}

$csrfToken = generateCSRFToken();

// Get current values
$currentSheetsUrl = defined('GOOGLE_SCRIPT_WEBHOOK_URL') ? GOOGLE_SCRIPT_WEBHOOK_URL : '';
$currentAppName = defined('APP_NAME') ? APP_NAME : 'CheckTrack';
$currentTimeout = defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT / 60 : 30;
$currentLang = defined('DEFAULT_LANG') ? DEFAULT_LANG : 'en';

// Check HTTPS
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
           (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
           (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

// Check sheets connection status
$sheetsConnected = false;
if (!empty($currentSheetsUrl) && strpos($currentSheetsUrl, 'script.google.com') !== false && strpos($currentSheetsUrl, 'YOUR_SCRIPT_ID') === false) {
    $testResult = testSheetsConnection();
    $sheetsConnected = $testResult['success'] ?? false;
}

// Load Code.gs content for display
$codeGsPath = __DIR__ . '/../apps_script/Code.gs';
$codeGsContent = file_exists($codeGsPath) ? file_get_contents($codeGsPath) : '// Code.gs file not found';
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? DEFAULT_LANG; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .toast {
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from { transform: translateY(-100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .code-block {
            max-height: 400px;
            overflow-y: auto;
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
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Settings</h1>
                <p class="text-gray-600 mt-1">Manage your CheckTrack configuration</p>
            </div>

            <!-- Sheets Connection Status Banner -->
            <div class="mb-6 rounded-xl shadow-lg p-4 sm:p-6 <?php echo $sheetsConnected ? 'bg-green-50 border border-green-200' : 'bg-yellow-50 border border-yellow-200'; ?>">
                <div class="flex items-center gap-3">
                    <?php if ($sheetsConnected): ?>
                        <div class="flex-shrink-0 w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-green-800">Google Sheets Connected</h3>
                            <p class="text-sm text-green-600">Data is being synced to your Google Sheet</p>
                        </div>
                    <?php else: ?>
                        <div class="flex-shrink-0 w-10 h-10 bg-yellow-500 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-yellow-800">Google Sheets Not Connected</h3>
                            <p class="text-sm text-yellow-600">Follow the setup guide below to enable syncing</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tabs -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <!-- Tab Headers -->
                <div class="border-b border-gray-200">
                    <nav class="flex -mb-px overflow-x-auto">
                        <button onclick="switchTab('sheets')" id="tab-sheets" class="tab-btn flex-shrink-0 px-6 py-4 text-sm font-medium border-b-2 border-blue-500 text-blue-600">
                            <span class="flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Google Sheets
                            </span>
                        </button>
                        <button onclick="switchTab('general')" id="tab-general" class="tab-btn flex-shrink-0 px-6 py-4 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700">
                            <span class="flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                General
                            </span>
                        </button>
                        <button onclick="switchTab('security')" id="tab-security" class="tab-btn flex-shrink-0 px-6 py-4 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700">
                            <span class="flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                                Security
                            </span>
                        </button>
                    </nav>
                </div>

                <!-- Tab Contents -->
                <div class="p-4 sm:p-6 lg:p-8">
                    <!-- Google Sheets Tab -->
                    <div id="content-sheets" class="tab-content active">
                        <h2 class="text-xl font-bold text-gray-800 mb-6">Google Sheets Setup Guide</h2>
                        
                        <!-- Step 1 -->
                        <div class="mb-8 p-4 bg-blue-50 rounded-lg border border-blue-200">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold">1</div>
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-800 mb-2">Create a New Google Sheet</h3>
                                    <p class="text-gray-600 mb-3">Start by creating a new Google Spreadsheet where your check-in data will be stored.</p>
                                    <a href="https://sheets.new" target="_blank" rel="noopener noreferrer" 
                                       class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition min-h-[44px]">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                        </svg>
                                        Open New Google Sheet
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2 -->
                        <div class="mb-8 p-4 bg-blue-50 rounded-lg border border-blue-200">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold">2</div>
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-800 mb-2">Open Apps Script Editor</h3>
                                    <p class="text-gray-600 mb-3">In your Google Sheet, go to <strong>Extensions → Apps Script</strong>. This will open the script editor in a new tab.</p>
                                    <div class="bg-gray-100 rounded-lg p-3 text-sm text-gray-500 italic">
                                        📸 Screenshot: Look for "Extensions" in the menu bar, then click "Apps Script"
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3 -->
                        <div class="mb-8 p-4 bg-blue-50 rounded-lg border border-blue-200">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold">3</div>
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-800 mb-2">Paste the Code</h3>
                                    <p class="text-gray-600 mb-3">Delete any existing code in the editor and paste the following script:</p>
                                    
                                    <div class="relative">
                                        <pre class="code-block bg-gray-900 text-green-400 p-4 rounded-lg text-xs overflow-x-auto"><code id="codeContent"><?php echo htmlspecialchars($codeGsContent); ?></code></pre>
                                        <button onclick="copyCode()" class="absolute top-2 right-2 px-3 py-1 bg-gray-700 text-white rounded hover:bg-gray-600 text-sm flex items-center gap-1 min-h-[36px]">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                            </svg>
                                            Copy Code
                                        </button>
                                    </div>
                                    <p class="text-gray-500 text-sm mt-2">Click "Copy Code" then paste into the Apps Script editor</p>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4 -->
                        <div class="mb-8 p-4 bg-blue-50 rounded-lg border border-blue-200">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold">4</div>
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-800 mb-2">Deploy as Web App</h3>
                                    <p class="text-gray-600 mb-3">Click <strong>Deploy → New deployment</strong> in the Apps Script editor:</p>
                                    <ul class="list-disc list-inside text-gray-600 space-y-1 ml-2">
                                        <li>Click the gear icon next to "Select type" and choose <strong>Web app</strong></li>
                                        <li>Set <strong>Execute as</strong> to: <code class="bg-gray-200 px-1 rounded">Me</code></li>
                                        <li>Set <strong>Who has access</strong> to: <code class="bg-gray-200 px-1 rounded">Anyone</code></li>
                                        <li>Click <strong>Deploy</strong></li>
                                        <li>Copy the <strong>Web app URL</strong> that appears</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Step 5 -->
                        <div class="mb-8 p-4 bg-green-50 rounded-lg border border-green-200">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 w-8 h-8 bg-green-600 text-white rounded-full flex items-center justify-center font-bold">5</div>
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-800 mb-2">Paste Web App URL Here</h3>
                                    <p class="text-gray-600 mb-3">Enter the Web App URL you copied from the deployment:</p>
                                    
                                    <div class="space-y-3">
                                        <input type="url" id="sheetsUrl" 
                                               value="<?php echo htmlspecialchars($currentSheetsUrl); ?>"
                                               placeholder="https://script.google.com/macros/s/..."
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 min-h-[48px]">
                                        
                                        <button onclick="saveSheetsUrl()" id="saveSheetsBtn"
                                                class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium min-h-[48px] flex items-center justify-center gap-2">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                                            </svg>
                                            Save & Test Connection
                                        </button>
                                        
                                        <div id="sheetsTestResult" class="hidden p-3 rounded-lg">
                                            <!-- Result will be shown here -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- General Settings Tab -->
                    <div id="content-general" class="tab-content">
                        <h2 class="text-xl font-bold text-gray-800 mb-6">General Settings</h2>
                        
                        <form id="generalForm" class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Application Name</label>
                                <input type="text" id="appName" value="<?php echo htmlspecialchars($currentAppName); ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 min-h-[48px]">
                                <p class="text-sm text-gray-500 mt-1">This name appears in the navbar and page titles</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Session Timeout (minutes)</label>
                                <input type="number" id="sessionTimeout" value="<?php echo htmlspecialchars($currentTimeout); ?>"
                                       min="5" max="1440"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 min-h-[48px]">
                                <p class="text-sm text-gray-500 mt-1">How long users stay logged in (5-1440 minutes)</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Default Language</label>
                                <select id="defaultLang" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 min-h-[48px]">
                                    <option value="en" <?php echo $currentLang === 'en' ? 'selected' : ''; ?>>English</option>
                                    <option value="it" <?php echo $currentLang === 'it' ? 'selected' : ''; ?>>Italiano</option>
                                </select>
                                <p class="text-sm text-gray-500 mt-1">Default language for new users</p>
                            </div>
                            
                            <button type="button" onclick="saveGeneralSettings()" id="saveGeneralBtn"
                                    class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium min-h-[48px] flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Save General Settings
                            </button>
                            
                            <div id="generalResult" class="hidden p-3 rounded-lg">
                                <!-- Result will be shown here -->
                            </div>
                        </form>
                    </div>

                    <!-- Security Tab -->
                    <div id="content-security" class="tab-content">
                        <h2 class="text-xl font-bold text-gray-800 mb-6">Security Information</h2>
                        
                        <div class="space-y-6">
                            <!-- HTTPS Status -->
                            <div class="p-4 rounded-lg <?php echo $isHttps ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'; ?>">
                                <div class="flex items-center gap-3">
                                    <?php if ($isHttps): ?>
                                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                        </svg>
                                        <div>
                                            <h3 class="font-semibold text-green-800">HTTPS Enabled</h3>
                                            <p class="text-sm text-green-600">Your connection is secure</p>
                                        </div>
                                    <?php else: ?>
                                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                        </svg>
                                        <div>
                                            <h3 class="font-semibold text-red-800">HTTPS Not Detected</h3>
                                            <p class="text-sm text-red-600">Enable HTTPS for secure data transmission</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Session Info -->
                            <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <h3 class="font-semibold text-gray-800 mb-3">Session Configuration</h3>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Session Timeout:</span>
                                        <span class="font-medium"><?php echo SESSION_TIMEOUT / 60; ?> minutes</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Session Cookie:</span>
                                        <span class="font-medium">HttpOnly, Secure, SameSite=Lax</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Session Strict Mode:</span>
                                        <span class="font-medium text-green-600">Enabled</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Rate Limiting Info -->
                            <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <h3 class="font-semibold text-gray-800 mb-3">Rate Limiting</h3>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Max Login Attempts:</span>
                                        <span class="font-medium">5 attempts</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Lockout Period:</span>
                                        <span class="font-medium">10 minutes</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Tracked By:</span>
                                        <span class="font-medium">IP Address</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- CSRF Protection -->
                            <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <h3 class="font-semibold text-gray-800 mb-3">CSRF Protection</h3>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Token Length:</span>
                                        <span class="font-medium">64 characters (256-bit)</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Token Regeneration:</span>
                                        <span class="font-medium">On login</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Validation Method:</span>
                                        <span class="font-medium">hash_equals (timing-safe)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer" class="fixed top-20 left-1/2 -translate-x-1/2 z-50"></div>

    <script>
        // Tab switching
        function switchTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active state from all tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('border-blue-500', 'text-blue-600');
                btn.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Show selected tab content
            document.getElementById('content-' + tabName).classList.add('active');
            
            // Add active state to selected tab button
            const activeBtn = document.getElementById('tab-' + tabName);
            activeBtn.classList.remove('border-transparent', 'text-gray-500');
            activeBtn.classList.add('border-blue-500', 'text-blue-600');
        }

        // Copy code to clipboard
        function copyCode() {
            const code = document.getElementById('codeContent').textContent;
            navigator.clipboard.writeText(code).then(() => {
                showToast('Code copied to clipboard!', 'success');
            }).catch(() => {
                showToast('Failed to copy code', 'error');
            });
        }

        // Save Sheets URL
        async function saveSheetsUrl() {
            const url = document.getElementById('sheetsUrl').value.trim();
            const btn = document.getElementById('saveSheetsBtn');
            const resultDiv = document.getElementById('sheetsTestResult');
            
            if (!url) {
                showToast('Please enter a URL', 'error');
                return;
            }
            
            if (!url.includes('script.google.com')) {
                showToast('Invalid URL: Must be a Google Apps Script URL', 'error');
                return;
            }
            
            btn.disabled = true;
            btn.innerHTML = '<svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Saving...';
            
            try {
                const response = await fetch('api/save_settings.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': '<?php echo $csrfToken; ?>'
                    },
                    body: JSON.stringify({
                        action: 'sheets_url',
                        url: url
                    })
                });
                
                const data = await response.json();
                
                resultDiv.classList.remove('hidden');
                
                if (data.success) {
                    resultDiv.className = 'p-3 rounded-lg bg-green-100 border border-green-300';
                    resultDiv.innerHTML = `
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-green-800 font-medium">${data.message}</span>
                        </div>
                    `;
                    showToast('Settings saved successfully!', 'success');
                } else {
                    resultDiv.className = 'p-3 rounded-lg bg-red-100 border border-red-300';
                    resultDiv.innerHTML = `
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            <span class="text-red-800 font-medium">${data.message}</span>
                        </div>
                    `;
                    showToast(data.message, 'error');
                }
            } catch (error) {
                resultDiv.classList.remove('hidden');
                resultDiv.className = 'p-3 rounded-lg bg-red-100 border border-red-300';
                resultDiv.innerHTML = `
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        <span class="text-red-800 font-medium">Connection error</span>
                    </div>
                `;
                showToast('Failed to save settings', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = `
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                    </svg>
                    Save & Test Connection
                `;
            }
        }

        // Save General Settings
        async function saveGeneralSettings() {
            const appName = document.getElementById('appName').value.trim();
            const sessionTimeout = document.getElementById('sessionTimeout').value;
            const defaultLang = document.getElementById('defaultLang').value;
            const btn = document.getElementById('saveGeneralBtn');
            const resultDiv = document.getElementById('generalResult');
            
            if (!appName) {
                showToast('Please enter an application name', 'error');
                return;
            }
            
            if (sessionTimeout < 5 || sessionTimeout > 1440) {
                showToast('Session timeout must be between 5 and 1440 minutes', 'error');
                return;
            }
            
            btn.disabled = true;
            btn.innerHTML = '<svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Saving...';
            
            try {
                const response = await fetch('api/save_settings.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': '<?php echo $csrfToken; ?>'
                    },
                    body: JSON.stringify({
                        action: 'general',
                        app_name: appName,
                        session_timeout: sessionTimeout,
                        default_lang: defaultLang
                    })
                });
                
                const data = await response.json();
                
                resultDiv.classList.remove('hidden');
                
                if (data.success) {
                    resultDiv.className = 'p-3 rounded-lg bg-green-100 border border-green-300';
                    resultDiv.innerHTML = `
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-green-800 font-medium">${data.message}</span>
                        </div>
                    `;
                    showToast('Settings saved successfully!', 'success');
                } else {
                    resultDiv.className = 'p-3 rounded-lg bg-red-100 border border-red-300';
                    resultDiv.innerHTML = `
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            <span class="text-red-800 font-medium">${data.message}</span>
                        </div>
                    `;
                    showToast(data.message, 'error');
                }
            } catch (error) {
                resultDiv.classList.remove('hidden');
                resultDiv.className = 'p-3 rounded-lg bg-red-100 border border-red-300';
                resultDiv.innerHTML = `
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        <span class="text-red-800 font-medium">Connection error</span>
                    </div>
                `;
                showToast('Failed to save settings', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = `
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Save General Settings
                `;
            }
        }

        // Toast notification
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
