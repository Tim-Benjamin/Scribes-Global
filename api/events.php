<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

header('Content-Type: application/json');

$db = new Database();
$conn = $db->connect();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'register':
        handleEventRegistration($conn);
        break;
    case 'cancel_registration':
        handleCancelRegistration($conn);
        break;
    case 'rsvp':
        handleRSVP($conn);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function handleEventRegistration($conn) {
    $eventId = $_POST['event_id'] ?? 0;
    $useAccountInfo = isset($_POST['use_account_info']) ? true : false;
    
    // Get user info if using account
    if ($useAccountInfo && isLoggedIn()) {
        $userId = $_SESSION['user_id'];
        $userStmt = $conn->prepare("SELECT first_name, last_name, email, phone FROM users WHERE id = ?");
        $userStmt->execute([$userId]);
        $userData = $userStmt->fetch();
        
        $name = $userData['first_name'] . ' ' . $userData['last_name'];
        $email = $userData['email'];
        $phone = $userData['phone'] ?? '';
    } else {
        $userId = isLoggedIn() ? $_SESSION['user_id'] : null;
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
    }
    
    $chapter = trim($_POST['chapter'] ?? '');
    $dietaryNeeds = trim($_POST['dietary_needs'] ?? '');
    $additionalInfo = trim($_POST['additional_info'] ?? '');
    
    // Validation
    if (empty($eventId) || empty($name) || empty($email) || empty($phone)) {
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        return;
    }
    
    // Check if event exists and is available
    $eventStmt = $conn->prepare("
        SELECT *, 
               (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as registration_count
        FROM events e
        WHERE id = ?
    ");
    $eventStmt->execute([$eventId]);
    $event = $eventStmt->fetch();
    
    if (!$event) {
        echo json_encode(['success' => false, 'message' => 'Event not found']);
        return;
    }
    
    if ($event['status'] !== 'upcoming') {
        echo json_encode(['success' => false, 'message' => 'Event is not open for registration']);
        return;
    }
    
    if (!$event['registration_enabled']) {
        echo json_encode(['success' => false, 'message' => 'Registration is not enabled for this event']);
        return;
    }
    
    if ($event['registration_limit'] && $event['registration_count'] >= $event['registration_limit']) {
        echo json_encode(['success' => false, 'message' => 'Event is full']);
        return;
    }
    
    if (strtotime($event['start_date']) < time()) {
        echo json_encode(['success' => false, 'message' => 'Event has already started']);
        return;
    }
    
    // Check if already registered (by user_id or email)
    if ($userId) {
        $checkStmt = $conn->prepare("SELECT id FROM event_registrations WHERE event_id = ? AND user_id = ?");
        $checkStmt->execute([$eventId, $userId]);
        if ($checkStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'You are already registered for this event']);
            return;
        }
    }
    
    // Also check by email
    $checkEmailStmt = $conn->prepare("SELECT id FROM event_registrations WHERE event_id = ? AND email = ?");
    $checkEmailStmt->execute([$eventId, $email]);
    if ($checkEmailStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'This email is already registered for this event']);
        return;
    }
    
    // Insert registration
    $stmt = $conn->prepare("
        INSERT INTO event_registrations (
            event_id, user_id, name, email, phone, chapter, 
            dietary_needs, additional_info, registered_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    try {
        $stmt->execute([
            $eventId, $userId, $name, $email, $phone, 
            $chapter, $dietaryNeeds, $additionalInfo
        ]);
        
        // Send confirmation email
        require_once __DIR__ . '/../config/mailer.php';
        $mailer = new Mailer();
        
        $firstName = explode(' ', $name)[0];
        $eventData = [
            'id' => $event['id'],
            'title' => $event['title'],
            'start_date' => $event['start_date'],
            'location' => $event['location']
        ];
        
        $emailSent = $mailer->sendEventRegistrationEmail($email, $firstName, $eventData);
        
        if (!$emailSent) {
            error_log("Failed to send event registration confirmation to: {$email}");
        }
        
        // Log activity if user is logged in
        if ($userId) {
            $logStmt = $conn->prepare("
                INSERT INTO activity_log (user_id, action, entity_type, entity_id, ip_address, user_agent)
                VALUES (?, 'event_registered', 'event', ?, ?, ?)
            ");
            $logStmt->execute([
                $userId, 
                $eventId, 
                $_SERVER['REMOTE_ADDR'], 
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Registration successful! Check your email for confirmation.'
        ]);
        
    } catch (PDOException $e) {
        error_log("Event registration error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
    }
}

function handleCancelRegistration($conn) {
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Please login to cancel registration']);
        return;
    }
    
    $eventId = $_POST['event_id'] ?? 0;
    $userId = $_SESSION['user_id'];
    
    // Check if registration exists
    $checkStmt = $conn->prepare("
        SELECT id FROM event_registrations 
        WHERE event_id = ? AND user_id = ?
    ");
    $checkStmt->execute([$eventId, $userId]);
    $registration = $checkStmt->fetch();
    
    if (!$registration) {
        echo json_encode(['success' => false, 'message' => 'Registration not found']);
        return;
    }
    
    try {
        // Delete registration
        $deleteStmt = $conn->prepare("DELETE FROM event_registrations WHERE id = ?");
        $deleteStmt->execute([$registration['id']]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Registration cancelled successfully'
        ]);
        
    } catch (PDOException $e) {
        error_log("Cancel registration error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to cancel registration']);
    }
}

function handleRSVP($conn) {
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Please login to RSVP']);
        return;
    }
    
    $eventId = $_POST['event_id'] ?? 0;
    $response = $_POST['response'] ?? '';
    $userId = $_SESSION['user_id'];
    
    // Validation
    if (!in_array($response, ['yes', 'no', 'maybe'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid RSVP response']);
        return;
    }
    
    // Get event and user info
    $eventStmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
    $eventStmt->execute([$eventId]);
    $event = $eventStmt->fetch();
    
    if (!$event) {
        echo json_encode(['success' => false, 'message' => 'Event not found']);
        return;
    }
    
    if (!$event['rsvp_enabled']) {
        echo json_encode(['success' => false, 'message' => 'RSVP is not enabled for this event']);
        return;
    }
    
    $userStmt = $conn->prepare("SELECT first_name, last_name, email, phone FROM users WHERE id = ?");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch();
    
    try {
        // Check if RSVP already exists
        $checkStmt = $conn->prepare("SELECT id FROM event_rsvps WHERE event_id = ? AND user_id = ?");
        $checkStmt->execute([$eventId, $userId]);
        $existingRSVP = $checkStmt->fetch();
        
        if ($existingRSVP) {
            // Update existing RSVP
            $updateStmt = $conn->prepare("
                UPDATE event_rsvps 
                SET response = ?, responded_at = NOW() 
                WHERE id = ?
            ");
            $updateStmt->execute([$response, $existingRSVP['id']]);
        } else {
            // Insert new RSVP
            $insertStmt = $conn->prepare("
                INSERT INTO event_rsvps (
                    event_id, user_id, name, email, phone, response, responded_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $insertStmt->execute([
                $eventId,
                $userId,
                $user['first_name'] . ' ' . $user['last_name'],
                $user['email'],
                $user['phone'],
                $response
            ]);
        }
        
        // Log activity
        $logStmt = $conn->prepare("
            INSERT INTO activity_log (user_id, action, entity_type, entity_id, ip_address, user_agent)
            VALUES (?, 'event_rsvp', 'event', ?, ?, ?)
        ");
        $logStmt->execute([
            $userId, 
            $eventId, 
            $_SERVER['REMOTE_ADDR'], 
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'RSVP submitted successfully'
        ]);
        
    } catch (PDOException $e) {
        error_log("RSVP error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to submit RSVP']);
    }
}
?>