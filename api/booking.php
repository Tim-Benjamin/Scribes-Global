<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$db = new Database();
$conn = $db->connect();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'submit_booking':
        handleSubmitBooking($conn);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function handleSubmitBooking($conn) {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $organization = trim($_POST['organization'] ?? '');
    $eventName = trim($_POST['event_name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $date = $_POST['date'] ?? '';
    $audience = trim($_POST['audience'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validation
    if (empty($firstName) || empty($email) || empty($phone) || empty($organization) || empty($eventName) || empty($location) || empty($date)) {
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        return;
    }
    
    // Check if date is in the future
    if (strtotime($date) < strtotime(date('Y-m-d'))) {
        echo json_encode(['success' => false, 'message' => 'Event date must be in the future']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO booking_invitations (
                user_id, name, email, phone, organization, 
                event_type, event_date, venue, audience_size, 
                additional_details, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");
        
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $fullName = $firstName . (!empty($lastName) ? ' ' . $lastName : '');
        
        $stmt->execute([
            $userId,
            $fullName,
            $email,
            $phone,
            $organization,
            $eventName,
            $date,
            $location,
            $audience,
            $message
        ]);
        
        // Send confirmation email
        require_once __DIR__ . '/../config/mailer.php';
        $mailer = new Mailer();
        
        $emailContent = "
            <h2>Booking Request Received</h2>
            <p>Dear {$firstName},</p>
            <p>Thank you for your booking request. We have received your information and will get back to you within 48 hours.</p>
            
            <h3>Your Booking Details:</h3>
            <ul>
                <li><strong>Event Name:</strong> {$eventName}</li>
                <li><strong>Organization:</strong> {$organization}</li>
                <li><strong>Date:</strong> " . date('F j, Y', strtotime($date)) . "</li>
                <li><strong>Location:</strong> {$location}</li>
            </ul>
            
            <p>If you have any urgent questions, please contact us at:</p>
            <ul>
                <li><strong>Phone:</strong> 0546296188 / 020 931 5447</li>
                <li><strong>Email:</strong> info@scribesglobal.com</li>
            </ul>
            
            <p>Blessings,<br>The Scribes Global Team</p>
        ";
        
        $mailer->send($email, 'Booking Request Received - Scribes Global', $emailContent);
        
        // Send notification to admin
        $adminEmail = 'info@scribesglobal.com';
        $adminContent = "
            <h2>New Booking Request</h2>
            
            <h3>Contact Information:</h3>
            <ul>
                <li><strong>Name:</strong> {$fullName}</li>
                <li><strong>Email:</strong> {$email}</li>
                <li><strong>Phone:</strong> {$phone}</li>
                <li><strong>Organization:</strong> {$organization}</li>
            </ul>
            
            <h3>Event Details:</h3>
            <ul>
                <li><strong>Event Name:</strong> {$eventName}</li>
                <li><strong>Date:</strong> " . date('F j, Y', strtotime($date)) . "</li>
                <li><strong>Location:</strong> {$location}</li>
                <li><strong>Audience:</strong> {$audience}</li>
            </ul>
            
            <h3>Message:</h3>
            <p>" . nl2br(htmlspecialchars($message)) . "</p>
        ";
        
        $mailer->send($adminEmail, 'New Booking Request - ' . $eventName, $adminContent);
        
        echo json_encode([
            'success' => true,
            'message' => 'Booking request submitted successfully'
        ]);
        
    } catch (PDOException $e) {
        error_log("Booking submission error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to submit booking request. Please try again.']);
    }
}
?>