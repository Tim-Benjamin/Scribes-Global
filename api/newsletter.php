<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/mailer.php';
require_once __DIR__ . '/../includes/email-templates.php';

header('Content-Type: application/json');

$db = new Database();
$conn = $db->connect();

$action = $_GET['action'] ?? '';

// Debug: Log all requests
error_log("Newsletter API - Action: " . $action . ", GET: " . json_encode($_GET) . ", POST: " . json_encode($_POST));

switch ($action) {
    case 'subscribe':
        handleNewsletterSubscribe($conn);
        break;
    case 'confirm':
        handleNewsletterConfirm($conn);
        break;
    case 'unsubscribe':
        handleNewsletterUnsubscribe($conn);
        break;
    case 'remove_subscriber':
        handleRemoveSubscriber($conn);
        break;
    case 'get_stats':
        handleGetStats($conn);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

// Subscribe to newsletter
function handleNewsletterSubscribe($conn) {
    try {
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $name = trim($_POST['name'] ?? '');
        
        error_log("Newsletter Subscribe - Email: " . $email . ", Name: " . $name);
        
        if (!$email) {
            echo json_encode(['success' => false, 'message' => 'Please provide a valid email']);
            return;
        }
        
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Please provide your name']);
            return;
        }
        
        // Check if already subscribed
        $checkStmt = $conn->prepare("SELECT id, status FROM newsletter_subscribers WHERE email = ?");
        $checkStmt->execute([$email]);
        $existing = $checkStmt->fetch();
        
        error_log("Existing subscriber check: " . json_encode($existing));
        
        if ($existing) {
            if ($existing['status'] === 'active') {
                echo json_encode(['success' => false, 'message' => 'This email is already subscribed']);
                return;
            }
        }
        
        // Generate tokens
        $token = bin2hex(random_bytes(32));
        $unsubscribeToken = bin2hex(random_bytes(32));
        
        error_log("Generated tokens - Confirmation: " . $token . ", Unsubscribe: " . $unsubscribeToken);
        
        if ($existing) {
            // Update existing record
            $stmt = $conn->prepare("
                UPDATE newsletter_subscribers 
                SET name = ?, confirmation_token = ?, unsubscribe_token = ?, status = 'pending', updated_at = NOW()
                WHERE email = ?
            ");
            $result = $stmt->execute([$name, $token, $unsubscribeToken, $email]);
            error_log("Update result: " . ($result ? 'true' : 'false'));
            error_log("Update affected rows: " . $stmt->rowCount());
        } else {
            // Create new subscriber
            $stmt = $conn->prepare("
                INSERT INTO newsletter_subscribers (email, name, confirmation_token, unsubscribe_token, status)
                VALUES (?, ?, ?, ?, 'pending')
            ");
            $result = $stmt->execute([$email, $name, $token, $unsubscribeToken]);
            error_log("Insert result: " . ($result ? 'true' : 'false'));
            error_log("Insert ID: " . $conn->lastInsertId());
        }
        
        // Send confirmation email
        $mailer = new Mailer();
        $confirmLink = SITE_URL . '/api/newsletter.php?action=confirm&token=' . $token;
        $emailBody = getNewsletterConfirmationEmail($name, $confirmLink);
        
        error_log("Sending email to: " . $email . " with link: " . $confirmLink);
        
        $mailSent = $mailer->send(
            $email,
            'Confirm Your Newsletter Subscription - Scribes Global',
            $emailBody
        );
        
        error_log("Mail sent result: " . ($mailSent ? 'true' : 'false'));
        
        echo json_encode([
            'success' => true,
            'message' => 'Please check your email to confirm your subscription'
        ]);
        
    } catch (PDOException $e) {
        error_log("Newsletter subscribe PDO error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        error_log("Newsletter subscribe general error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

// ✅ FIXED: Confirm newsletter subscription
function handleNewsletterConfirm($conn) {
    $token = $_GET['token'] ?? '';
    
    error_log("===== CONFIRM REQUEST =====");
    error_log("Token received: " . $token);
    error_log("Token length: " . strlen($token));
    error_log("GET params: " . json_encode($_GET));
    
    if (empty($token)) {
        error_log("ERROR: No token provided");
        header('Location: ' . SITE_URL . '?error=invalid_token');
        exit;
    }
    
    try {
        // First, check what tokens exist in the database
        error_log("Checking database for token...");
        
        // Get all tokens to debug
        $allTokensStmt = $conn->query("SELECT id, email, confirmation_token FROM newsletter_subscribers WHERE confirmation_token IS NOT NULL LIMIT 5");
        $allTokens = $allTokensStmt->fetchAll();
        error_log("Tokens in DB: " . json_encode($allTokens));
        
        // Search for the token
        $stmt = $conn->prepare("SELECT id, email, name, confirmation_token FROM newsletter_subscribers WHERE confirmation_token = ?");
        $stmt->execute([$token]);
        $subscriber = $stmt->fetch();
        
        if ($subscriber) {
            error_log("✓ Subscriber found! ID: " . $subscriber['id'] . ", Email: " . $subscriber['email']);
        } else {
            error_log("✗ Subscriber NOT found with token: " . $token);
            error_log("Attempted comparison with " . count($allTokens) . " tokens in database");
            
            // Try searching without exact match to debug
            $likeStmt = $conn->prepare("SELECT id, email, confirmation_token FROM newsletter_subscribers WHERE confirmation_token LIKE ?");
            $likeStmt->execute(['%' . substr($token, 0, 10) . '%']);
            $likeResult = $likeStmt->fetch();
            if ($likeResult) {
                error_log("PARTIAL MATCH FOUND: " . json_encode($likeResult));
            }
            
            header('Location: ' . SITE_URL . '?error=invalid_token');
            exit;
        }
        
        // Update subscriber status
        error_log("Updating subscriber " . $subscriber['id'] . " status to 'active'");
        
        $updateStmt = $conn->prepare("
            UPDATE newsletter_subscribers 
            SET status = 'active', confirmation_date = NOW(), confirmation_token = NULL, updated_at = NOW()
            WHERE id = ?
        ");
        $updateResult = $updateStmt->execute([$subscriber['id']]);
        
        error_log("Update executed: " . ($updateResult ? 'true' : 'false'));
        error_log("Rows affected: " . $updateStmt->rowCount());
        
        // Verify the update
        $verifyStmt = $conn->prepare("SELECT status, confirmation_token FROM newsletter_subscribers WHERE id = ?");
        $verifyStmt->execute([$subscriber['id']]);
        $verified = $verifyStmt->fetch();
        
        error_log("After update - Status: " . $verified['status'] . ", Token: " . ($verified['confirmation_token'] ?? 'NULL'));
        error_log("✓ Confirmation successful!");
        
        header('Location: ' . SITE_URL . '?newsletter=confirmed');
        exit;
        
    } catch (PDOException $e) {
        error_log("✗ PDO Error: " . $e->getMessage());
        header('Location: ' . SITE_URL . '?error=confirmation_failed');
        exit;
    } catch (Exception $e) {
        error_log("✗ General Error: " . $e->getMessage());
        header('Location: ' . SITE_URL . '?error=confirmation_failed');
        exit;
    }
}

// Unsubscribe from newsletter
function handleNewsletterUnsubscribe($conn) {
    $token = $_GET['token'] ?? '';
    
    error_log("Unsubscribe newsletter - Token received: " . $token);
    
    if (empty($token)) {
        header('Location: ' . SITE_URL . '?error=invalid_token');
        exit;
    }
    
    try {
        $stmt = $conn->prepare("SELECT id FROM newsletter_subscribers WHERE unsubscribe_token = ? LIMIT 1");
        $stmt->execute([$token]);
        $subscriber = $stmt->fetch();
        
        if (!$subscriber) {
            header('Location: ' . SITE_URL . '?error=invalid_token');
            exit;
        }
        
        $updateStmt = $conn->prepare("
            UPDATE newsletter_subscribers 
            SET status = 'unsubscribed', updated_at = NOW()
            WHERE id = ?
        ");
        $updateStmt->execute([$subscriber['id']]);
        
        header('Location: ' . SITE_URL . '?newsletter=unsubscribed');
        exit;
        
    } catch (PDOException $e) {
        error_log("Newsletter unsubscribe error: " . $e->getMessage());
        header('Location: ' . SITE_URL . '?error=unsubscribe_failed');
        exit;
    }
}

// Remove subscriber (admin)
function handleRemoveSubscriber($conn) {
    if (!isAdmin()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $id = $_POST['id'] ?? 0;
    
    try {
        $stmt = $conn->prepare("DELETE FROM newsletter_subscribers WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true, 'message' => 'Subscriber removed']);
    } catch (PDOException $e) {
        error_log("Remove subscriber error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to remove subscriber']);
    }
}

// Get newsletter statistics
function handleGetStats($conn) {
    if (!isAdmin()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    try {
        $statsStmt = $conn->query("
            SELECT 
                (SELECT COUNT(*) FROM newsletter_subscribers WHERE status = 'active') as active_subscribers,
                (SELECT COUNT(*) FROM newsletter_subscribers WHERE status = 'pending') as pending_confirmations,
                (SELECT COUNT(*) FROM newsletter_subscribers WHERE status = 'unsubscribed') as unsubscribed,
                (SELECT COUNT(*) FROM newsletter_emails WHERE status = 'sent') as emails_sent
        ");
        $stats = $statsStmt->fetch();
        
        echo json_encode(['success' => true, 'data' => $stats]);
        
    } catch (PDOException $e) {
        error_log("Get stats error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to get stats']);
    }
}

// Send event notification to subscribers
function sendEventNotificationToSubscribers($conn, $eventId, $eventData) {
    try {
        $mailer = new Mailer();
        
        $stmt = $conn->prepare("
            SELECT id, email, name FROM newsletter_subscribers 
            WHERE status = 'active'
        ");
        $stmt->execute();
        $subscribers = $stmt->fetchAll();
        
        $sentCount = 0;
        
        foreach ($subscribers as $subscriber) {
            $emailBody = getEventNotificationEmail($subscriber['name'], $eventData);
            $subject = "🎉 New Event: " . htmlspecialchars($eventData['title']) . " - Scribes Global";
            
            if ($mailer->send($subscriber['email'], $subject, $emailBody, '', $subscriber['name'])) {
                $logStmt = $conn->prepare("
                    INSERT INTO newsletter_emails (event_id, subscriber_id, email, subject, status)
                    VALUES (?, ?, ?, ?, 'sent')
                ");
                $logStmt->execute([$eventId, $subscriber['id'], $subscriber['email'], $subject]);
                $sentCount++;
            }
        }
        
        return $sentCount;
        
    } catch (Exception $e) {
        error_log("Send event notification error: " . $e->getMessage());
        return 0;
    }
}

?>