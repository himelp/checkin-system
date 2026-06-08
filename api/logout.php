<?php
/**
 * Logout API
 */

require_once __DIR__ . '/../includes/auth.php';

// Logout user
logoutUser();

// Redirect to login page
header('Location: ' . SITE_URL . '/login.php');
exit;
