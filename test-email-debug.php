<?php
require_once __DIR__ . '/config/config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>PHPMailer SMTPS Debug Test (Port 465)</h1>";

// Check if PHPMailer exists
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    die("<p style='color: red;'>❌ Composer autoload not found. Run: composer require phpmailer/phpmailer</p>");
}

require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

echo "<p>✅ PHPMailer loaded successfully</p>";
echo "<p><strong>Using SMTPS (SSL) on Port 465</strong></p>";
echo "<p>SMTP Host: " . SMTP_HOST . "</p>";
echo "<p>SMTP Port: " . SMTP_PORT . "</p>";
echo "<p>SMTP Username: " . SMTP_USERNAME . "</p>";
echo "<hr>";

$mail = new PHPMailer(true);

try {
    // Server settings - SMTPS (SSL on Port 465)
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USERNAME;
    $mail->Password   = SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
    $mail->Port       = SMTP_PORT; // 465
    
    // SSL Options
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    
    // Recipients
    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    $mail->addAddress(SMTP_USERNAME); // Send to yourself
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = 'SMTPS Test Email - ' . date('Y-m-d H:i:s');
    $mail->Body    = '<h1>Test Email using SMTPS (Port 465)</h1><p>This is a test email from Scribes Global.</p><p>Sent at: ' . date('Y-m-d H:i:s') . '</p>';
    $mail->AltBody = 'This is a test email from Scribes Global using SMTPS.';
    
    echo "<h2>Attempting to send email...</h2><pre>";
    
    $mail->send();
    
    echo "</pre><hr>";
    echo "<h2 style='color: green;'>✅ EMAIL SENT SUCCESSFULLY!</h2>";
    echo "<p>Check your inbox (and spam folder): " . SMTP_USERNAME . "</p>";
    echo "<p><strong>SMTPS (Port 465) is working!</strong></p>";
    
} catch (Exception $e) {
    echo "</pre><hr>";
    echo "<h2 style='color: red;'>❌ Email could not be sent</h2>";
    echo "<p><strong>Error:</strong> {$mail->ErrorInfo}</p>";
    echo "<p><strong>Exception:</strong> {$e->getMessage()}</p>";
    echo "<hr>";
    echo "<h3>Troubleshooting:</h3>";
    echo "<ol>";
    echo "<li>Make sure 2-Step Verification is enabled on your Gmail account</li>";
    echo "<li>Generate a NEW App Password at: <a href='https://myaccount.google.com/apppasswords' target='_blank'>https://myaccount.google.com/apppasswords</a></li>";
    echo "<li>Copy the 16-character password WITHOUT spaces</li>";
    echo "<li>Update SMTP_PASSWORD in config/config.php</li>";
    echo "</ol>";
}
?>