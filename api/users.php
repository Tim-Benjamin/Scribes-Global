<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = new Database();
$conn = $db->connect();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'update_profile':
        handleUpdateProfile($conn);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function handleUpdateProfile($conn) {
    $userId = $_SESSION['user_id'];
    
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $customTag = trim($_POST['custom_tag'] ?? '');
    $primaryRole = $_POST['primary_role'] ?? 'member';
    $chapterId = $_POST['chapter_id'] ?? null;
    $ministryTeam = $_POST['ministry_team'] ?? null;
    $showRole = isset($_POST['show_role']) ? 1 : 0;
    $showChapter = isset($_POST['show_chapter']) ? 1 : 0;
    $showTeam = isset($_POST['show_team']) ? 1 : 0;
    
    // Validate
    if (empty($firstName) || empty($lastName) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
        return;
    }
    
    // Check if email is taken by another user
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $checkStmt->execute([$email, $userId]);
    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email already in use']);
        return;
    }
    
    // Handle profile photo upload
    $profilePhoto = null;
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_photo'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WEBP allowed']);
            return;
        }
        
        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'File too large. Maximum 5MB allowed']);
            return;
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . $userId . '_' . time() . '.' . $extension;
        $uploadPath = UPLOAD_PATH . $filename;
        
        if (!is_dir(UPLOAD_PATH)) {
            mkdir(UPLOAD_PATH, 0777, true);
        }
        
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            $profilePhoto = $filename;
            
            // Delete old photo
            $oldPhotoStmt = $conn->prepare("SELECT profile_photo FROM users WHERE id = ?");
            $oldPhotoStmt->execute([$userId]);
            $oldPhoto = $oldPhotoStmt->fetch();
            
            if ($oldPhoto && $oldPhoto['profile_photo'] && file_exists(UPLOAD_PATH . $oldPhoto['profile_photo'])) {
                unlink(UPLOAD_PATH . $oldPhoto['profile_photo']);
            }
        }
    }
    
    // Update user
    if ($profilePhoto) {
        $stmt = $conn->prepare("
            UPDATE users SET
                first_name = ?,
                last_name = ?,
                email = ?,
                phone = ?,
                bio = ?,
                custom_tag = ?,
                primary_role = ?,
                chapter_id = ?,
                ministry_team = ?,
                show_role = ?,
                show_chapter = ?,
                show_team = ?,
                profile_photo = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $firstName, $lastName, $email, $phone, $bio, $customTag,
            $primaryRole, $chapterId, $ministryTeam, $showRole,
            $showChapter, $showTeam, $profilePhoto, $userId
        ]);
    } else {
        $stmt = $conn->prepare("
            UPDATE users SET
                first_name = ?,
                last_name = ?,
                email = ?,
                phone = ?,
                bio = ?,
                custom_tag = ?,
                primary_role = ?,
                chapter_id = ?,
                ministry_team = ?,
                show_role = ?,
                show_chapter = ?,
                show_team = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $firstName, $lastName, $email, $phone, $bio, $customTag,
            $primaryRole, $chapterId, $ministryTeam, $showRole,
            $showChapter, $showTeam, $userId
        ]);
    }
    
    // Log activity
    $logStmt = $conn->prepare("
        INSERT INTO activity_log (user_id, action, ip_address, user_agent)
        VALUES (?, 'profile_updated', ?, ?)
    ");
    $logStmt->execute([$userId, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? '']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully!'
    ]);

    
}
?>


<!-- // After successful update, calculate and update profile completion
$completionCalc = 0;
$completionChecks = [
    [$profilePhoto ? $profilePhoto : $oldPhoto['profile_photo'], 15],
    [$bio, 15],
    [$customTag, 15],
    [$primaryRole !== 'member', 15],
    [$chapterId, 10],
    [$ministryTeam, 10],
    [$phone, 10],
    // Email verified status remains unchanged during profile update
];

foreach ($completionChecks as $check) {
    if (!empty($check[0])) {
        $completionCalc += $check[1];
    }
}

// Add email verified points
$emailVerifiedStmt = $conn->prepare("SELECT email_verified FROM users WHERE id = ?");
$emailVerifiedStmt->execute([$userId]);
$emailVerified = $emailVerifiedStmt->fetchColumn();
if ($emailVerified) {
    $completionCalc += 10;
}

// Update completion
$completionUpdateStmt = $conn->prepare("UPDATE users SET profile_completion = ? WHERE id = ?");
$completionUpdateStmt->execute([$completionCalc, $userId]);

// Check if user just reached 100% and hasn't been awarded Active Member badge
if ($completionCalc >= 100) {
    $badgeCheckStmt = $conn->prepare("
        SELECT id FROM user_badges 
        WHERE user_id = ? AND badge_type = 'active'
    ");
    $badgeCheckStmt->execute([$userId]);
    
    if (!$badgeCheckStmt->fetch()) {
        // Award Active Member badge
        $badgeStmt = $conn->prepare("
            INSERT INTO user_badges (user_id, badge_type, auto_earned, awarded_at)
            VALUES (?, 'active', 1, NOW())
        ");
        $badgeStmt->execute([$userId]);
        
        // Send congratulations email
        require_once __DIR__ . '/../config/mailer.php';
        $mailer = new Mailer();
        $mailer->sendNotificationEmail(
            $email,
            $firstName,
            'Congratulations! You Earned the Active Member Badge! 🏆',
            '<p>Amazing work! You\'ve completed 100% of your profile and earned the <strong>Active Member Badge</strong>.</p>
            <p>This badge will be displayed on your profile and shows your commitment to the Scribes Global community.</p>
            <p>Keep up the great work!</p>',
            SITE_URL . '/pages/dashboard',
            'View Your Profile'
        );
    }
} -->