<?php
/**
 * Application Configuration
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');

// Google Apps Script Webhook URL
define('GOOGLE_SCRIPT_WEBHOOK_URL', 'https://script.google.com/macros/s/YOUR_SCRIPT_ID/exec');

// Site URL
define('SITE_URL', 'https://yourdomain.com');

// Session timeout in seconds (30 minutes)
define('SESSION_TIMEOUT', 1800);

// Application name
define('APP_NAME', 'CheckTrack');

// Default language
define('DEFAULT_LANG', 'en');

// Default Timezone
define('DEFAULT_TIMEZONE', 'UTC');
date_default_timezone_set(DEFAULT_TIMEZONE);

// Developer Information
define('DEV_NAME', 'Md Minhaz Bin Santo');
define('DEV_COMPANY', 'Beenet IT Solutions');
define('DEV_WEBSITE', 'https://minhazbinsanto.com');
define('DEV_EMAIL', 'contact@minhazbinsanto.com');
define('SHOW_DEV_FOOTER', true);

// Google Sheets Webhook Secret
define('SHEETS_SECRET', 'checktrack-secret-2026');

// Version Management
define('VERSION_CHECK_URL', 'https://raw.githubusercontent.com/himelp/checkin-system/master/version.txt');
define('GITHUB_REPO_URL', 'https://github.com/himelp/checkin-system');
define('UPDATE_DOWNLOAD_URL', 'https://github.com/himelp/checkin-system/archive/refs/heads/master.zip');
?>
