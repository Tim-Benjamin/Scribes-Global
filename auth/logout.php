<?php
session_start();
require_once __DIR__ . '/../config/config.php';

// Destroy session
session_unset();
session_destroy();

// Delete remember me cookie if exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Redirect to homepage
$_SESSION['success_message'] = 'You have been logged out successfully.';
header('Location: ' . SITE_URL);
exit;
?>