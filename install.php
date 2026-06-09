<?php
/**
 * CheckTrack Installer
 * Multi-step Installation Wizard
 */

// Check if already installed
if (file_exists(__DIR__ . '/install/installed.lock')) {
    header('Location: index.php');
    exit;
}

// Auto-detect URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$path = dirname($_SERVER['SCRIPT_NAME']);
$path = str_replace('/install', '', $path);
$autoUrl = $protocol . '://' . $host . ($path !== '/' ? $path : '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CheckTrack Installer</title>
    <link rel="stylesheet" href="install/style.css">
</head>
<body>
    <div class="installer-container">
        <!-- Header -->
        <div class="installer-header">
            <h1>📋 CheckTrack</h1>
            <p>Installation Wizard</p>
        </div>

        <!-- Progress Steps -->
        <div class="progress-steps">
            <div class="step-indicator">
                <div class="step-dot active" id="dot-1">1</div>
                <div class="step-line" id="line-1"></div>
                <div class="step-dot" id="dot-2">2</div>
                <div class="step-line" id="line-2"></div>
                <div class="step-dot" id="dot-3">3</div>
                <div class="step-line" id="line-3"></div>
                <div class="step-dot" id="dot-4">4</div>
                <div class="step-line" id="line-4"></div>
                <div class="step-dot" id="dot-5">5</div>
            </div>
        </div>

        <!-- Step Content -->
        <div class="step-content">
            <!-- Step 1: Welcome -->
            <div class="step active" id="step-1">
                <h2 class="step-title">Welcome to CheckTrack!</h2>
                <p class="step-description">Let's set up your check-in/check-out system. This wizard will guide you through the installation process.</p>
                
                <div class="form-group">
                    <label>Application Name <span class="required">*</span></label>
                    <input type="text" class="form-control" id="app_name" value="CheckTrack" placeholder="My Company Check-in System">
                </div>
                
                <div class="form-group">
                    <label>Application URL <span class="required">*</span></label>
                    <input type="url" class="form-control" id="app_url" value="<?php echo htmlspecialchars($autoUrl); ?>" placeholder="https://yourdomain.com">
                    <small style="color: #64748b; font-size: 12px;">Auto-detected from server</small>
                </div>
                
                <div class="form-group">
                    <label>Language</label>
                    <select class="form-control" id="lang">
                        <option value="en">English</option>
                        <option value="it">Italiano</option>
                    </select>
                </div>
                
                <div class="btn-group">
                    <button class="btn btn-primary" onclick="nextStep(1)">Continue →</button>
                </div>
            </div>

            <!-- Step 2: Database -->
            <div class="step" id="step-2">
                <h2 class="step-title">Database Configuration</h2>
                <p class="step-description">Enter your database connection details. The database must already exist.</p>
                
                <div id="db-message" class="message"></div>
                
                <div class="form-group">
                    <label>Database Host <span class="required">*</span></label>
                    <input type="text" class="form-control" id="db_host" value="localhost" placeholder="localhost">
                </div>
                
                <div class="form-group">
                    <label>Database Name <span class="required">*</span></label>
                    <input type="text" class="form-control" id="db_name" placeholder="checktrack_db">
                </div>
                
                <div class="form-group">
                    <label>Database Username <span class="required">*</span></label>
                    <input type="text" class="form-control" id="db_user" placeholder="root">
                </div>
                
                <div class="form-group">
                    <label>Database Password</label>
                    <input type="password" class="form-control" id="db_pass" placeholder="Leave empty if none">
                </div>
                
                <div class="btn-group">
                    <button class="btn btn-secondary" onclick="prevStep(2)">← Back</button>
                    <button class="btn btn-secondary" onclick="testConnection()" id="test-btn">Test Connection</button>
                    <button class="btn btn-primary" onclick="nextStep(2)" id="db-continue" disabled>Continue →</button>
                </div>
            </div>

            <!-- Step 3: Admin Account -->
            <div class="step" id="step-3">
                <h2 class="step-title">Admin Account</h2>
                <p class="step-description">Create the administrator account for managing the system.</p>
                
                <div class="form-group">
                    <label>Full Name <span class="required">*</span></label>
                    <input type="text" class="form-control" id="admin_name" placeholder="John Doe">
                </div>
                
                <div class="form-group">
                    <label>Username <span class="required">*</span></label>
                    <input type="text" class="form-control" id="admin_user" placeholder="admin">
                </div>
                
                <div class="form-group">
                    <label>Password <span class="required">*</span></label>
                    <input type="password" class="form-control" id="admin_pass" placeholder="Minimum 6 characters">
                </div>
                
                <div class="form-group">
                    <label>Confirm Password <span class="required">*</span></label>
                    <input type="password" class="form-control" id="admin_pass_confirm" placeholder="Re-enter password">
                </div>
                
                <div class="btn-group">
                    <button class="btn btn-secondary" onclick="prevStep(3)">← Back</button>
                    <button class="btn btn-primary" onclick="nextStep(3)">Continue →</button>
                </div>
            </div>

            <!-- Step 4: Developer Info -->
            <div class="step" id="step-4">
                <h2 class="step-title">Developer Information</h2>
                <p class="step-description">Credit the developer. This information can be shown in the footer.</p>
                
                <div class="form-group">
                    <label>Developer Name</label>
                    <input type="text" class="form-control" id="dev_name" value="Md Minhaz Bin Santo">
                </div>
                
                <div class="form-group">
                    <label>Company</label>
                    <input type="text" class="form-control" id="dev_company" value="Beenet IT Solutions">
                </div>
                
                <div class="form-group">
                    <label>Website</label>
                    <input type="url" class="form-control" id="dev_website" value="https://minhazbinsanto.com">
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" class="form-control" id="dev_email" value="contact@minhazbinsanto.com">
                </div>
                
                <div class="form-group">
                    <div class="toggle-group">
                        <label class="toggle">
                            <input type="checkbox" id="show_dev_footer" checked>
                            <span class="toggle-slider"></span>
                        </label>
                        <span class="toggle-label">Show developer credit in footer</span>
                    </div>
                </div>
                
                <div class="btn-group">
                    <button class="btn btn-secondary" onclick="prevStep(4)">← Back</button>
                    <button class="btn btn-primary" onclick="startInstallation()">Install Now →</button>
                </div>
            </div>

            <!-- Step 5: Installing -->
            <div class="step" id="step-5">
                <h2 class="step-title">Installing...</h2>
                <p class="step-description">Please wait while we set up your system.</p>
                
                <div class="progress-bar-container">
                    <div class="progress-bar" id="progress-bar"></div>
                </div>
                <p class="progress-text" id="progress-text">Preparing...</p>
                
                <div id="install-log" style="margin-top: 20px; font-size: 13px; color: #64748b;"></div>
            </div>

            <!-- Step 6: Success -->
            <div class="step" id="step-6">
                <div class="success-icon">✓</div>
                <h2 class="success-title">Installation Complete!</h2>
                <p class="success-message">CheckTrack has been successfully installed.</p>
                
                <div class="warning-box">
                    <p>⚠️ <strong>Important:</strong> For security, please delete the <code>install/</code> folder or rename it.</p>
                </div>
                
                <a href="index.php" class="btn btn-success btn-block">Go to Login →</a>
            </div>
        </div>
    </div>

    <script>
        let dbConnected = false;
        let currentStep = 1;
        let isInstalling = false;

        function updateProgress(step) {
            // Update dots
            for (let i = 1; i <= 5; i++) {
                const dot = document.getElementById(`dot-${i}`);
                const line = document.getElementById(`line-${i}`);
                
                if (i < step) {
                    dot.classList.add('completed');
                    dot.classList.remove('active');
                    dot.innerHTML = '✓';
                    if (line) line.classList.add('completed');
                } else if (i === step) {
                    dot.classList.add('active');
                    dot.classList.remove('completed');
                    dot.innerHTML = i;
                } else {
                    dot.classList.remove('active', 'completed');
                    dot.innerHTML = i;
                    if (line) line.classList.remove('completed');
                }
            }
        }

        function showStep(step) {
            document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
            document.getElementById(`step-${step}`).classList.add('active');
            updateProgress(step);
            currentStep = step;
        }

        function nextStep(from) {
            if (from === 1) {
                const appName = document.getElementById('app_name').value.trim();
                const appUrl = document.getElementById('app_url').value.trim();
                
                if (!appName) {
                    alert('Please enter an application name');
                    return;
                }
                if (!appUrl) {
                    alert('Please enter the application URL');
                    return;
                }
            }
            
            if (from === 2 && !dbConnected) {
                alert('Please test the database connection first');
                return;
            }
            
            if (from === 3) {
                const adminName = document.getElementById('admin_name').value.trim();
                const adminUser = document.getElementById('admin_user').value.trim();
                const adminPass = document.getElementById('admin_pass').value;
                const adminPassConfirm = document.getElementById('admin_pass_confirm').value;
                
                if (!adminName || !adminUser || !adminPass) {
                    alert('Please fill in all admin fields');
                    return;
                }
                
                if (adminPass.length < 6) {
                    alert('Password must be at least 6 characters');
                    return;
                }
                
                if (adminPass !== adminPassConfirm) {
                    alert('Passwords do not match');
                    return;
                }
            }
            
            showStep(from + 1);
        }

        function prevStep(from) {
            showStep(from - 1);
        }

        async function testConnection() {
            const btn = document.getElementById('test-btn');
            const message = document.getElementById('db-message');
            const continueBtn = document.getElementById('db-continue');
            
            btn.disabled = true;
            btn.textContent = 'Testing...';
            
            const formData = new FormData();
            formData.append('host', document.getElementById('db_host').value);
            formData.append('dbname', document.getElementById('db_name').value);
            formData.append('user', document.getElementById('db_user').value);
            formData.append('pass', document.getElementById('db_pass').value);
            
            try {
                const response = await fetch('install/test_db.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                message.classList.add('show');
                
                if (data.success) {
                    message.className = 'message show message-success';
                    message.innerHTML = '✓ ' + data.message;
                    dbConnected = true;
                    continueBtn.disabled = false;
                } else {
                    message.className = 'message show message-error';
                    message.innerHTML = '✗ ' + data.message;
                    dbConnected = false;
                    continueBtn.disabled = true;
                }
            } catch (error) {
                message.className = 'message show message-error';
                message.innerHTML = '✗ Connection test failed';
                dbConnected = false;
                continueBtn.disabled = true;
            }
            
            btn.disabled = false;
            btn.textContent = 'Test Connection';
        }

        async function startInstallation() {
            if (isInstalling) return;
            isInstalling = true;
            
            showStep(5);
            
            const progressBar = document.getElementById('progress-bar');
            const progressText = document.getElementById('progress-text');
            const installLog = document.getElementById('install-log');
            
            // Animate progress
            let progress = 0;
            const progressInterval = setInterval(() => {
                progress += Math.random() * 15;
                if (progress > 90) progress = 90;
                progressBar.style.width = progress + '%';
            }, 200);
            
            const formData = new FormData();
            formData.append('app_name', document.getElementById('app_name').value);
            formData.append('app_url', document.getElementById('app_url').value);
            formData.append('lang', document.getElementById('lang').value);
            formData.append('db_host', document.getElementById('db_host').value);
            formData.append('db_name', document.getElementById('db_name').value);
            formData.append('db_user', document.getElementById('db_user').value);
            formData.append('db_pass', document.getElementById('db_pass').value);
            formData.append('admin_name', document.getElementById('admin_name').value);
            formData.append('admin_user', document.getElementById('admin_user').value);
            formData.append('admin_pass', document.getElementById('admin_pass').value);
            formData.append('dev_name', document.getElementById('dev_name').value);
            formData.append('dev_company', document.getElementById('dev_company').value);
            formData.append('dev_website', document.getElementById('dev_website').value);
            formData.append('dev_email', document.getElementById('dev_email').value);
            formData.append('show_dev_footer', document.getElementById('show_dev_footer').checked ? '1' : '0');
            
            try {
                const response = await fetch('install/run_install.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                clearInterval(progressInterval);
                progressBar.style.width = '100%';
                
                if (data.success) {
                    progressText.textContent = 'Installation complete!';
                    
                    if (data.progress) {
                        installLog.innerHTML = data.progress.map(p => 
                            `<p>✓ ${p.message}</p>`
                        ).join('');
                    }
                    
                    setTimeout(() => {
                        showStep(6);
                    }, 1000);
                } else {
                    progressText.textContent = 'Installation failed';
                    installLog.innerHTML = `<p style="color: #ef4444;">✗ ${data.message}</p>`;
                }
            } catch (error) {
                clearInterval(progressInterval);
                progressText.textContent = 'Installation failed';
                installLog.innerHTML = `<p style="color: #ef4444;">✗ Network error</p>`;
            } finally {
                isInstalling = false;
            }
        }
    </script>
</body>
</html>
