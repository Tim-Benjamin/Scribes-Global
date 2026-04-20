<?php
// Site Configuration
define('SITE_NAME', 'Scribes Global');
define('SITE_URL', 'http://localhost/scribes-global');
define('SITE_EMAIL', 'info@scribesglobal.com');

// Paths
define('BASE_PATH', $_SERVER['DOCUMENT_ROOT'] . '/scribes-global/');
define('ASSETS_PATH', SITE_URL . '/assets/');

// Session settings
define('SESSION_LIFETIME', 7 * 24 * 60 * 60); // 7 days
define('REMEMBER_ME_LIFETIME', 30 * 24 * 60 * 60); // 30 days

// Security
define('BCRYPT_COST', 12);
define('TOKEN_EXPIRY', 3600); // 1 hour for password reset

// Upload settings
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('UPLOAD_PATH', BASE_PATH . 'assets/images/uploads/');

// Google OAuth
define('GOOGLE_CLIENT_ID', 'your-google-client-id');
define('GOOGLE_CLIENT_SECRET', 'your-google-client-secret');
define('GOOGLE_REDIRECT_URI', SITE_URL . '/auth/google-oauth');

// Payment Gateway (Paystack for Ghana)
define('PAYSTACK_PUBLIC_KEY', 'your-paystack-public-key');
define('PAYSTACK_SECRET_KEY', 'your-paystack-secret-key');

// ============================================
// EMAIL SETTINGS - USING SMTPS (Port 465)
// ============================================
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 465); // Changed from 587 to 465
define('SMTP_SECURE', 'ssl'); // Using SSL instead of TLS
define('SMTP_USERNAME', 'minawood4321@gmail.com');
define('SMTP_PASSWORD', 'inxrifuxdmdkglww'); // Your App Password (no spaces!)
define('SMTP_FROM_EMAIL', 'minawood4321@gmail.com');
define('SMTP_FROM_NAME', 'Scribes Global');

// Timezone
date_default_timezone_set('Africa/Accra');

// Error reporting (disable in production)
if ($_SERVER['SERVER_NAME'] === 'localhost') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

define('SHOW_SPLASH_SCREEN', false);
?>