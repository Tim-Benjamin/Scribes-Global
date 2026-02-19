<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mailer.php';
require_once __DIR__ . '/../config/session.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    $_SESSION['error_message'] = 'Invalid verification link.';
    header('Location: ' . SITE_URL);
    exit;
}

$db = new Database();
$conn = $db->connect();

// Find user with this token
$stmt = $conn->prepare("SELECT id, first_name, email FROM users WHERE verification_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error_message'] = 'Invalid or expired verification link.';
    header('Location: ' . SITE_URL);
    exit;
}

// Verify email
$updateStmt = $conn->prepare("
    UPDATE users 
    SET email_verified = 1, verification_token = NULL 
    WHERE id = ?
");
$updateStmt->execute([$user['id']]);

// Send welcome email
$mailer = new Mailer();
$mailer->sendWelcomeEmail($user['email'], $user['first_name']);

// Auto-login user
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_email'] = $user['email'];

$_SESSION['success_message'] = 'Email verified successfully! Welcome to Scribes Global! 🎉';
header('Location: ' . SITE_URL . '/pages/dashboard');
exit;
?>