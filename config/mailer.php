<?php
/**
 * Email Mailer Class for Scribes Global
 * Uses PHPMailer with SMTPS (Port 465)
 */

// Load PHPMailer
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    die("PHPMailer not found. Run: composer require phpmailer/phpmailer");
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class Mailer {
    private $mail;
    private $debug = false;
    
    public function __construct($debug = false) {
        $this->debug = $debug;
        $this->mail = new PHPMailer(true);
        
        try {
            // Server settings - Using SMTPS (SSL on Port 465)
            if ($this->debug) {
                $this->mail->SMTPDebug = SMTP::DEBUG_SERVER;
            }
            
            $this->mail->isSMTP();
            $this->mail->Host       = SMTP_HOST;
            $this->mail->SMTPAuth   = true;
            $this->mail->Username   = SMTP_USERNAME;
            $this->mail->Password   = SMTP_PASSWORD;
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL instead of STARTTLS
            $this->mail->Port       = SMTP_PORT; // 465 instead of 587
            
            // Additional settings
            $this->mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            // Timeout settings
            $this->mail->Timeout = 30;
            
            // Default sender
            $this->mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            
            // Character set
            $this->mail->CharSet = 'UTF-8';
            $this->mail->isHTML(true);
            
        } catch (Exception $e) {
            error_log("Mailer initialization error: {$e->getMessage()}");
            if ($this->debug) {
                echo "Mailer Error: {$e->getMessage()}<br>";
            }
        }
    }
    
    /**
     * Send email
     */
    public function send($to, $subject, $body, $altBody = '', $recipientName = '') {
        try {
            // Clear previous recipients
            $this->mail->clearAddresses();
            $this->mail->clearAttachments();
            
            // Add recipient
            if ($recipientName) {
                $this->mail->addAddress($to, $recipientName);
            } else {
                $this->mail->addAddress($to);
            }
            
            // Set content
            $this->mail->Subject = $subject;
            $this->mail->Body    = $body;
            $this->mail->AltBody = $altBody ?: strip_tags($body);
            
            // Send
            $result = $this->mail->send();
            
            if ($result) {
                error_log("✅ Email sent successfully to: {$to}");
                if ($this->debug) {
                    echo "✅ Email sent to: {$to}<br>";
                }
            }
            
            return $result;
            
        } catch (Exception $e) {
            $errorMsg = "Email Error: {$this->mail->ErrorInfo} | Exception: {$e->getMessage()}";
            error_log($errorMsg);
            
            if ($this->debug) {
                echo "<pre style='color: red;'>{$errorMsg}</pre>";
            }
            
            return false;
        }
    }
    
    /**
     * Send verification email
     */
    public function sendVerificationEmail($email, $firstName, $token) {
        $verificationUrl = SITE_URL . '/auth/verify-email?token=' . $token;
        
        $subject = 'Verify Your Email - Scribes Global';
        $body = $this->getEmailTemplate('verification', [
            'first_name' => $firstName,
            'verification_url' => $verificationUrl
        ]);
        
        $sent = $this->send($email, $subject, $body, '', $firstName);
        
        if (!$sent) {
            error_log("❌ Failed to send verification email to: {$email}");
        }
        
        return $sent;
    }
    
    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail($email, $firstName, $token) {
        $resetUrl = SITE_URL . '/auth/reset-password?token=' . $token;
        
        $subject = 'Reset Your Password - Scribes Global';
        $body = $this->getEmailTemplate('password-reset', [
            'first_name' => $firstName,
            'reset_url' => $resetUrl
        ]);
        
        return $this->send($email, $subject, $body, '', $firstName);
    }
    
    /**
     * Send welcome email
     */
    public function sendWelcomeEmail($email, $firstName) {
        $subject = 'Welcome to Scribes Global! 🎉';
        $body = $this->getEmailTemplate('welcome', [
            'first_name' => $firstName
        ]);
        
        return $this->send($email, $subject, $body, '', $firstName);
    }
    
    /**
     * Send event registration confirmation
     */
    public function sendEventRegistrationEmail($email, $firstName, $eventData) {
        $subject = 'Event Registration Confirmed - ' . $eventData['title'];
        $body = $this->getEmailTemplate('event-registration', [
            'first_name' => $firstName,
            'event_title' => $eventData['title'],
            'event_date' => date('F j, Y', strtotime($eventData['start_date'])),
            'event_time' => date('g:i A', strtotime($eventData['start_date'])),
            'event_location' => $eventData['location'],
            'event_url' => SITE_URL . '/pages/events/details?id=' . $eventData['id']
        ]);
        
        return $this->send($email, $subject, $body, '', $firstName);
    }
    
    /**
     * Send event reminder (24 hours before)
     */
    public function sendEventReminderEmail($email, $firstName, $eventData) {
        $subject = 'Reminder: ' . $eventData['title'] . ' Tomorrow!';
        $body = $this->getEmailTemplate('event-reminder', [
            'first_name' => $firstName,
            'event_title' => $eventData['title'],
            'event_date' => date('F j, Y', strtotime($eventData['start_date'])),
            'event_time' => date('g:i A', strtotime($eventData['start_date'])),
            'event_location' => $eventData['location'],
            'event_url' => SITE_URL . '/pages/events/details?id=' . $eventData['id']
        ]);
        
        return $this->send($email, $subject, $body, '', $firstName);
    }
    
    /**
     * Send notification email
     */
    public function sendNotificationEmail($email, $firstName, $title, $message, $actionUrl = '', $actionText = '') {
        $subject = $title;
        $body = $this->getEmailTemplate('notification', [
            'first_name' => $firstName,
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'action_text' => $actionText
        ]);
        
        return $this->send($email, $subject, $body, '', $firstName);
    }
    
    /**
     * Get email template
     */
    private function getEmailTemplate($template, $data) {
        $templatePath = __DIR__ . '/../email-templates/' . $template . '.php';
        
        if (!file_exists($templatePath)) {
            error_log("Email template not found: {$templatePath}");
            return $this->getDefaultTemplate($data);
        }
        
        ob_start();
        extract($data);
        include $templatePath;
        return ob_get_clean();
    }
    
    /**
     * Default email template
     */
    private function getDefaultTemplate($data) {
        $firstName = $data['first_name'] ?? 'User';
        $verificationUrl = $data['verification_url'] ?? '#';
        
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
        </head>
        <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px;">
                <tr>
                    <td align="center">
                        <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 10px;">
                            <tr>
                                <td style="background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%); padding: 30px; text-align: center;">
                                    <h1 style="color: #ffffff; margin: 0;">Scribes Global</h1>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 40px 30px;">
                                    <h2>Verify Your Email</h2>
                                    <p>Hi ' . htmlspecialchars($firstName) . ',</p>
                                    <p>Welcome to Scribes Global! Please verify your email address:</p>
                                    <p><a href="' . $verificationUrl . '" style="display: inline-block; padding: 15px 30px; background: #6B46C1; color: white; text-decoration: none; border-radius: 5px;">Verify Email</a></p>
                                </td>
                            </tr>
                            <tr>
                                <td style="background-color: #f9f9f9; padding: 20px; text-align: center;">
                                    <p style="margin: 0; color: #666; font-size: 12px;">© ' . date('Y') . ' Scribes Global</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ';
    }
}
?>