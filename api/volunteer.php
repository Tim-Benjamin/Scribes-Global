<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$db = new Database();
$conn = $db->connect();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'submit_application':
        handleSubmitApplication($conn);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function handleSubmitApplication($conn) {
    $opportunityId = $_POST['opportunity_id'] ?? null;
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $availability = trim($_POST['availability'] ?? '');
    $skills = trim($_POST['skills'] ?? '');
    $motivation = trim($_POST['motivation'] ?? '');
    $chapter = trim($_POST['chapter'] ?? '');
    
    // Validation
    if (empty($firstName) || empty($lastName) || empty($email) || empty($phone) || empty($availability) || empty($skills) || empty($motivation)) {
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO volunteer_applications (
                opportunity_id, user_id, first_name, last_name, email, phone,
                availability, skills, motivation, chapter, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");
        
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $opportunityIdValue = !empty($opportunityId) ? $opportunityId : null;
        
        $stmt->execute([
            $opportunityIdValue,
            $userId,
            $firstName,
            $lastName,
            $email,
            $phone,
            $availability,
            $skills,
            $motivation,
            $chapter
        ]);
        
        // Send confirmation email
        require_once __DIR__ . '/../config/mailer.php';
        $mailer = new Mailer();
        
        $emailContent = "
            <h2>Volunteer Application Received</h2>
            <p>Dear {$firstName},</p>
            <p>Thank you for your interest in volunteering with Scribes Global! We have received your application and will review it carefully.</p>
            <p>Our team will get back to you within 5-7 business days.</p>
            <p>Blessings,<br>The Scribes Global Team</p>
        ";
        
        $mailer->send($email, 'Volunteer Application Received - Scribes Global', $emailContent);
        
        echo json_encode([
            'success' => true,
            'message' => 'Application submitted successfully'
        ]);
        
    } catch (PDOException $e) {
        error_log("Volunteer application error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to submit application. Please try again.']);
    }
}
?>