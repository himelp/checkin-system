<?php
/**
 * Login Page
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/lang.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? DEFAULT_LANG; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - <?php echo t('login'); ?></title>
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
<body class="bg-gray-100 min-h-screen flex flex-col">
    <!-- Language Toggle -->
    <div class="absolute top-4 right-4">
        <button id="langToggle" class="px-4 py-2 bg-white rounded-lg shadow text-sm font-medium text-gray-700 hover:bg-gray-50 min-h-[44px] min-w-[44px]">
            <?php echo ($_SESSION['lang'] ?? DEFAULT_LANG) === 'en' ? 'IT' : 'EN'; ?>
        </button>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <!-- App Name -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-blue-600"><?php echo APP_NAME; ?></h1>
            </div>

            <!-- Login Card -->
            <div class="bg-white rounded-xl shadow-lg p-6 sm:p-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-6 text-center"><?php echo t('login'); ?></h2>
                
                <form id="loginForm" class="space-y-4">
                    <input type="hidden" name="csrf_token" id="csrfToken" value="<?php echo $csrfToken; ?>">
                    
                    <!-- Username -->
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-1"><?php echo t('username'); ?></label>
                        <input type="text" id="username" name="username" required 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 min-h-[44px]"
                            placeholder="<?php echo t('username'); ?>">
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1"><?php echo t('password'); ?></label>
                        <div class="relative">
                            <input type="password" id="password" name="password" required 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 pr-12 min-h-[44px]"
                                placeholder="<?php echo t('password'); ?>">
                            <button type="button" id="togglePassword" 
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 min-h-[44px] min-w-[44px] flex items-center justify-center">
                                <svg id="eyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg id="eyeOffIcon" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" id="submitBtn"
                        class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-200 min-h-[44px]">
                        <?php echo t('login'); ?>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer" class="fixed top-4 left-1/2 -translate-x-1/2 z-50"></div>

    <script src="assets/app.js"></script>
    <script>
        // Password toggle
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            const eyeOffIcon = document.getElementById('eyeOffIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.add('hidden');
                eyeOffIcon.classList.remove('hidden');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('hidden');
                eyeOffIcon.classList.add('hidden');
            }
        });

        // Login form submission
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = '...';
            
            const data = {
                username: document.getElementById('username').value,
                password: document.getElementById('password').value,
                csrf_token: document.getElementById('csrfToken').value
            };
            
            try {
                const result = await postJSON('api/login.php', data);
                
                if (result.success) {
                    showToast(result.message, 'success');
                    setTimeout(() => {
                        window.location.href = result.redirect;
                    }, 500);
                } else {
                    showToast(result.message, 'error');
                    // Update CSRF token if provided
                    if (result.csrf_token) {
                        document.getElementById('csrfToken').value = result.csrf_token;
                    }
                }
            } catch (error) {
                showToast('Network error', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
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
    </script>
</body>
</html>
