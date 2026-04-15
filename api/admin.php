<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

header('Content-Type: application/json');

// Check if user is admin
if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Access denied. Admin privileges required.']);
    exit;
}

$db = new Database();
$conn = $db->connect();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'approve_content':
        handleApproveContent($conn);
        break;
    case 'reject_content':
        handleRejectContent($conn);
        break;
    case 'update_user_role':
        handleUpdateUserRole($conn);
        break;
    case 'update_user_status':
        handleUpdateUserStatus($conn);
        break;
    case 'award_badge':
        handleAwardBadge($conn);
        break;
    case 'remove_badge':
        handleRemoveBadge($conn);
        break;
    case 'delete_event':
        handleDeleteEvent($conn);
        break;
    case 'delete_post':
        handleDeletePost($conn);
        break;
    case 'delete_user':
        handleDeleteUser($conn);
        break;
    case 'send_notification':
        handleSendNotification($conn);
        break;
    case 'get_event_registrations':
        handleGetEventRegistrations($conn);
        break;
    case 'export_registrations':
        handleExportRegistrations($conn);
        break;
    case 'create_event':
        handleCreateEvent($conn);
        break;
    case 'get_event_media':
        handleGetEventMedia($conn);
        break;
    case 'upload_event_gallery':
        handleUploadEventGallery($conn);
        break;
    case 'delete_event_gallery_image':
        handleDeleteEventGalleryImage($conn);
        break;
    case 'add_event_video':
        handleAddEventVideo($conn);
        break;
    case 'delete_event_video':
        handleDeleteEventVideo($conn);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function handleApproveContent($conn)
{
    $data = json_decode(file_get_contents('php://input'), true);
    $type = $data['type'] ?? '';
    $id = $data['id'] ?? 0;

    try {
        if ($type === 'media') {
            $stmt = $conn->prepare("UPDATE media_content SET status = 'approved' WHERE id = ?");
            $stmt->execute([$id]);

            // Get media info and send notification
            $mediaStmt = $conn->prepare("SELECT mc.*, u.email, u.first_name FROM media_content mc JOIN users u ON mc.user_id = u.id WHERE mc.id = ?");
            $mediaStmt->execute([$id]);
            $media = $mediaStmt->fetch();

            if ($media) {
                require_once __DIR__ . '/../config/mailer.php';
                $mailer = new Mailer();
                $mailer->sendNotificationEmail(
                    $media['email'],
                    $media['first_name'],
                    'Your Media Has Been Approved! 🎉',
                    '<p>Great news! Your submission "<strong>' . htmlspecialchars($media['title']) . '</strong>" has been approved and is now live on Scribes Global.</p>
                    <p>Thank you for sharing your creative work with the community!</p>',
                    SITE_URL . '/pages/media',
                    'View Media'
                );
            }
        } elseif ($type === 'prayer') {
            $stmt = $conn->prepare("UPDATE prayer_requests SET status = 'approved' WHERE id = ?");
            $stmt->execute([$id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid content type']);
            return;
        }

        echo json_encode(['success' => true, 'message' => 'Content approved successfully']);
    } catch (PDOException $e) {
        error_log("Approve content error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to approve content']);
    }
}

function handleRejectContent($conn)
{
    $data = json_decode(file_get_contents('php://input'), true);
    $type = $data['type'] ?? '';
    $id = $data['id'] ?? 0;

    try {
        if ($type === 'media') {
            $stmt = $conn->prepare("UPDATE media_content SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$id]);
        } elseif ($type === 'prayer') {
            $stmt = $conn->prepare("DELETE FROM prayer_requests WHERE id = ?");
            $stmt->execute([$id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid content type']);
            return;
        }

        echo json_encode(['success' => true, 'message' => 'Content rejected successfully']);
    } catch (PDOException $e) {
        error_log("Reject content error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to reject content']);
    }
}

function handleUpdateUserRole($conn)
{
    $userId = $_POST['user_id'] ?? 0;
    $role = $_POST['role'] ?? '';

    $validRoles = ['super_admin', 'administrator', 'editor', 'ministry_leader', 'member'];

    if (!in_array($role, $validRoles)) {
        echo json_encode(['success' => false, 'message' => 'Invalid role']);
        return;
    }

    try {
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$role, $userId]);

        echo json_encode(['success' => true, 'message' => 'User role updated successfully']);
    } catch (PDOException $e) {
        error_log("Update user role error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to update user role']);
    }
}

function handleUpdateUserStatus($conn)
{
    $userId = $_POST['user_id'] ?? 0;
    $status = $_POST['status'] ?? '';

    $validStatuses = ['active', 'suspended', 'banned'];

    if (!in_array($status, $validStatuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        return;
    }

    try {
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$status, $userId]);

        echo json_encode(['success' => true, 'message' => 'User status updated successfully']);
    } catch (PDOException $e) {
        error_log("Update user status error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to update user status']);
    }
}

function handleAwardBadge($conn)
{
    $userId = $_POST['user_id'] ?? 0;
    $badgeType = $_POST['badge_type'] ?? '';
    $adminId = $_SESSION['user_id'];

    $validBadges = ['verified', 'founder', 'ministry_leader', 'featured', 'certified', 'active'];

    if (!in_array($badgeType, $validBadges)) {
        echo json_encode(['success' => false, 'message' => 'Invalid badge type']);
        return;
    }

    try {
        // Check if badge already exists
        $checkStmt = $conn->prepare("SELECT id FROM user_badges WHERE user_id = ? AND badge_type = ?");
        $checkStmt->execute([$userId, $badgeType]);

        if ($checkStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'User already has this badge']);
            return;
        }

        // Award badge
        $stmt = $conn->prepare("INSERT INTO user_badges (user_id, badge_type, awarded_by, awarded_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$userId, $badgeType, $adminId]);

        // Send notification
        $userStmt = $conn->prepare("SELECT email, first_name FROM users WHERE id = ?");
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch();

        if ($user) {
            require_once __DIR__ . '/../config/mailer.php';
            $mailer = new Mailer();

            $badgeNames = [
                'verified' => 'Verified Artist Badge',
                'founder' => 'Founder Badge',
                'ministry_leader' => 'Ministry Leader Badge',
                'featured' => 'Featured Creator Badge',
                'certified' => 'Certified Graduate Badge',
                'active' => 'Active Member Badge'
            ];

            $mailer->sendNotificationEmail(
                $user['email'],
                $user['first_name'],
                'You\'ve Earned a New Badge! 🏆',
                '<p>Congratulations! You\'ve been awarded the <strong>' . $badgeNames[$badgeType] . '</strong>.</p>
                <p>This badge will be displayed on your profile and shows your commitment to the Scribes Global community.</p>',
                SITE_URL . '/pages/dashboard/profile',
                'View Your Profile'
            );
        }

        echo json_encode(['success' => true, 'message' => 'Badge awarded successfully']);
    } catch (PDOException $e) {
        error_log("Award badge error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to award badge']);
    }
}

function handleRemoveBadge($conn)
{
    $userId = $_POST['user_id'] ?? 0;
    $badgeType = $_POST['badge_type'] ?? '';

    try {
        $stmt = $conn->prepare("DELETE FROM user_badges WHERE user_id = ? AND badge_type = ?");
        $stmt->execute([$userId, $badgeType]);

        echo json_encode(['success' => true, 'message' => 'Badge removed successfully']);
    } catch (PDOException $e) {
        error_log("Remove badge error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to remove badge']);
    }
}

function handleDeleteEvent($conn)
{
    $eventId = $_POST['event_id'] ?? 0;

    try {
        // Delete registrations first
        $deleteRegStmt = $conn->prepare("DELETE FROM event_registrations WHERE event_id = ?");
        $deleteRegStmt->execute([$eventId]);

        // Delete event
        $deleteStmt = $conn->prepare("DELETE FROM events WHERE id = ?");
        $deleteStmt->execute([$eventId]);

        echo json_encode(['success' => true, 'message' => 'Event deleted successfully']);
    } catch (PDOException $e) {
        error_log("Delete event error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to delete event']);
    }
}

function handleDeletePost($conn)
{
    $postId = $_POST['post_id'] ?? 0;

    try {
        // Delete comments first
        $deleteCommentsStmt = $conn->prepare("DELETE FROM blog_comments WHERE post_id = ?");
        $deleteCommentsStmt->execute([$postId]);

        // Delete likes
        $deleteLikesStmt = $conn->prepare("DELETE FROM blog_likes WHERE post_id = ?");
        $deleteLikesStmt->execute([$postId]);

        // Delete post
        $deleteStmt = $conn->prepare("DELETE FROM blog_posts WHERE id = ?");
        $deleteStmt->execute([$postId]);

        echo json_encode(['success' => true, 'message' => 'Post deleted successfully']);
    } catch (PDOException $e) {
        error_log("Delete post error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to delete post']);
    }
}

function handleDeleteUser($conn)
{
    $userId = $_POST['user_id'] ?? 0;
    $adminId = $_SESSION['user_id'];

    // Prevent self-deletion
    if ($userId == $adminId) {
        echo json_encode(['success' => false, 'message' => 'You cannot delete your own account']);
        return;
    }

    try {
        // Delete user (cascade will handle related records)
        $deleteStmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $deleteStmt->execute([$userId]);

        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    } catch (PDOException $e) {
        error_log("Delete user error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
    }
}

function handleSendNotification($conn)
{
    $recipients = $_POST['recipients'] ?? 'all'; // all, role, chapter, specific
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';

    if (empty($subject) || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Subject and message are required']);
        return;
    }

    try {
        // Get recipients based on filter
        $query = "SELECT email, first_name FROM users WHERE status = 'active'";

        if ($recipients === 'role') {
            $role = $_POST['role'] ?? '';
            $query .= " AND role = ?";
            $params = [$role];
        } elseif ($recipients === 'chapter') {
            $chapterId = $_POST['chapter_id'] ?? 0;
            $query .= " AND chapter_id = ?";
            $params = [$chapterId];
        } elseif ($recipients === 'specific') {
            $userIds = $_POST['user_ids'] ?? [];
            $placeholders = str_repeat('?,', count($userIds) - 1) . '?';
            $query .= " AND id IN ($placeholders)";
            $params = $userIds;
        } else {
            $params = [];
        }

        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $users = $stmt->fetchAll();

        // Send emails
        require_once __DIR__ . '/../config/mailer.php';
        $mailer = new Mailer();

        $sent = 0;
        foreach ($users as $user) {
            if ($mailer->sendNotificationEmail($user['email'], $user['first_name'], $subject, $message)) {
                $sent++;
            }

            // Small delay to avoid rate limiting
            usleep(100000); // 0.1 second
        }

        echo json_encode([
            'success' => true,
            'message' => "Notification sent to {$sent} user(s)"
        ]);
    } catch (PDOException $e) {
        error_log("Send notification error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to send notification']);
    }
}

// Add these functions to api/admin.php

function handleGetEventRegistrations($conn)
{
    $eventId = $_GET['event_id'] ?? 0;

    try {
        // Get event details
        $eventStmt = $conn->prepare("SELECT id, title, registration_limit FROM events WHERE id = ?");
        $eventStmt->execute([$eventId]);
        $event = $eventStmt->fetch();

        if (!$event) {
            echo json_encode(['success' => false, 'message' => 'Event not found']);
            return;
        }

        // Get registrations
        $regStmt = $conn->prepare("
            SELECT * FROM event_registrations 
            WHERE event_id = ? 
            ORDER BY registered_at DESC
        ");
        $regStmt->execute([$eventId]);
        $registrations = $regStmt->fetchAll();

        echo json_encode([
            'success' => true,
            'event' => $event,
            'registrations' => $registrations
        ]);
    } catch (PDOException $e) {
        error_log("Get event registrations error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to fetch registrations']);
    }
}

function handleExportRegistrations($conn)
{
    $eventId = $_GET['event_id'] ?? 0;

    try {
        // Get event and registrations
        $eventStmt = $conn->prepare("SELECT title FROM events WHERE id = ?");
        $eventStmt->execute([$eventId]);
        $event = $eventStmt->fetch();

        $regStmt = $conn->prepare("SELECT * FROM event_registrations WHERE event_id = ? ORDER BY registered_at DESC");
        $regStmt->execute([$eventId]);
        $registrations = $regStmt->fetchAll();

        // Create CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . sanitize_filename($event['title']) . '_registrations.csv"');

        $output = fopen('php://output', 'w');

        // Headers
        fputcsv($output, ['Name', 'Email', 'Phone', 'Chapter', 'Dietary Needs', 'Registered At', 'Attendance Confirmed']);

        // Data
        foreach ($registrations as $reg) {
            fputcsv($output, [
                $reg['name'],
                $reg['email'],
                $reg['phone'],
                $reg['chapter'],
                $reg['dietary_needs'],
                $reg['registered_at'],
                $reg['attendance_confirmed'] ? 'Yes' : 'No'
            ]);
        }

        fclose($output);
        exit;
    } catch (PDOException $e) {
        error_log("Export registrations error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to export registrations']);
    }
}

function sanitize_filename($filename)
{
    return preg_replace('/[^A-Za-z0-9_\-]/', '_', $filename);
}


function handleCreateEvent($conn)
{
    error_log("Create event started");

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $eventType = $_POST['event_type'] ?? '';
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? null;
    $location = trim($_POST['location'] ?? '');
    $latitude = $_POST['latitude'] ?? null;
    $longitude = $_POST['longitude'] ?? null;
    $virtualLink = trim($_POST['virtual_link'] ?? '');
    $chapterId = $_POST['chapter_id'] ?? null;
    $registrationEnabled = isset($_POST['registration_enabled']) ? 1 : 0;
    $registrationLimit = $_POST['registration_limit'] ?? null;
    $rsvpEnabled = isset($_POST['rsvp_enabled']) ? 1 : 0;
    $status = $_POST['status'] ?? 'upcoming';
    $featured = isset($_POST['featured']) ? 1 : 0;
    $notifyUsers = isset($_POST['notify_users']) ? 1 : 0;
    $userId = $_SESSION['user_id'];

    // Validation
    if (empty($title)) {
        echo json_encode(['success' => false, 'message' => 'Title is required']);
        return;
    }

    if (empty($description)) {
        echo json_encode(['success' => false, 'message' => 'Description is required']);
        return;
    }

    if (empty($eventType)) {
        echo json_encode(['success' => false, 'message' => 'Event type is required']);
        return;
    }

    if (empty($startDate)) {
        echo json_encode(['success' => false, 'message' => 'Start date is required']);
        return;
    }

    if (empty($location)) {
        echo json_encode(['success' => false, 'message' => 'Location is required']);
        return;
    }

    // Generate slug
    $slug = generateSlug($title);

    // Check for duplicate slug
    try {
        $checkStmt = $conn->prepare("SELECT id FROM events WHERE slug = ?");
        $checkStmt->execute([$slug]);
        if ($checkStmt->fetch()) {
            $slug = $slug . '-' . time();
        }
    } catch (PDOException $e) {
        error_log("Slug check error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        return;
    }

    // Handle hero image upload
    $heroImage = null;
    if (isset($_FILES['hero_image']) && $_FILES['hero_image']['error'] === UPLOAD_ERR_OK) {
        $heroImage = uploadEventImage($_FILES['hero_image']);
        if (!$heroImage) {
            echo json_encode(['success' => false, 'message' => 'Failed to upload hero image']);
            return;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Hero image is required']);
        return;
    }

    // Handle gallery images
    $galleryImages = [];
    if (isset($_FILES['gallery_images'])) {
        foreach ($_FILES['gallery_images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['gallery_images']['error'][$key] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $_FILES['gallery_images']['name'][$key],
                    'type' => $_FILES['gallery_images']['type'][$key],
                    'tmp_name' => $tmp_name,
                    'error' => $_FILES['gallery_images']['error'][$key],
                    'size' => $_FILES['gallery_images']['size'][$key]
                ];
                $uploadedImage = uploadEventImage($file);
                if ($uploadedImage) {
                    $galleryImages[] = $uploadedImage;
                }
            }
        }
    }
    $galleryJson = !empty($galleryImages) ? json_encode($galleryImages) : null;

    // Clean up empty values
    if (empty($endDate)) $endDate = null;
    if (empty($latitude)) $latitude = null;
    if (empty($longitude)) $longitude = null;
    if (empty($virtualLink)) $virtualLink = null;
    if (empty($chapterId)) $chapterId = null;
    if (empty($registrationLimit)) $registrationLimit = null;

    try {
        $stmt = $conn->prepare("
            INSERT INTO events (
                title, slug, description, event_type, start_date, end_date,
                location, latitude, longitude, virtual_link, hero_image, gallery,
                chapter_id, registration_enabled, registration_limit, rsvp_enabled,
                status, featured, created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $result = $stmt->execute([
            $title,
            $slug,
            $description,
            $eventType,
            $startDate,
            $endDate,
            $location,
            $latitude,
            $longitude,
            $virtualLink,
            $heroImage,
            $galleryJson,
            $chapterId,
            $registrationEnabled,
            $registrationLimit,
            $rsvpEnabled,
            $status,
            $featured,
            $userId
        ]);

        if (!$result) {
            error_log("Execute failed: " . json_encode($stmt->errorInfo()));
            echo json_encode(['success' => false, 'message' => 'Failed to create event']);
            return;
        }

        $eventId = $conn->lastInsertId();
        error_log("Event created with ID: " . $eventId);

        // Log activity
        try {
            $logStmt = $conn->prepare("
                INSERT INTO activity_log (user_id, action, entity_type, entity_id, ip_address, user_agent)
                VALUES (?, 'event_created', 'event', ?, ?, ?)
            ");
            $logStmt->execute([$userId, $eventId, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? '']);
        } catch (PDOException $e) {
            error_log("Activity log error: " . $e->getMessage());
        }

        // Send notifications to all users if enabled
        $notificationsSent = 0;
        if ($notifyUsers) {
            $notificationsSent = sendEventCreatedNotifications($conn, $eventId, $title, $startDate, $location);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Event created successfully',
            'event_id' => $eventId,
            'notifications_sent' => $notificationsSent
        ]);
    } catch (PDOException $e) {
        error_log("Create event error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function uploadEventImage($file)
{
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    if (!in_array($file['type'], $allowedTypes)) {
        error_log("Invalid image type: " . $file['type']);
        return false;
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        error_log("Image too large: " . $file['size']);
        return false;
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'event_' . time() . '_' . uniqid() . '.' . $extension;
    $uploadDir = __DIR__ . '/../assets/images/uploads/';
    $uploadPath = $uploadDir . $filename;

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return $filename;
    }

    return false;
}

function sendEventCreatedNotifications($conn, $eventId, $eventTitle, $startDate, $location)
{
    // Get all active users
    $usersStmt = $conn->query("SELECT email, first_name FROM users WHERE status = 'active' AND email_verified = 1");
    $users = $usersStmt->fetchAll();

    if (empty($users)) {
        return 0;
    }

    require_once __DIR__ . '/../config/mailer.php';
    $mailer = new Mailer();

    $sent = 0;
    $eventDate = date('F j, Y \a\t g:i A', strtotime($startDate));

    foreach ($users as $user) {
        $message = '
            <p>Hi ' . htmlspecialchars($user['first_name']) . ',</p>
            <p>We\'re excited to announce a new event on Scribes Global!</p>
            <div style="background: linear-gradient(135deg, rgba(107, 70, 193, 0.1) 0%, rgba(45, 156, 219, 0.1) 100%); padding: 1.5rem; border-radius: 12px; border-left: 4px solid #6B46C1; margin: 1.5rem 0;">
                <h2 style="margin: 0 0 1rem 0; color: #1A1A2E;">' . htmlspecialchars($eventTitle) . '</h2>
                <p style="margin: 0.5rem 0;"><strong>📅 Date:</strong> ' . $eventDate . '</p>
                <p style="margin: 0.5rem 0;"><strong>📍 Location:</strong> ' . htmlspecialchars($location) . '</p>
            </div>
            <p>Don\'t miss out on this amazing opportunity! Click the button below to view event details and register.</p>
        ';

        if ($mailer->sendNotificationEmail(
            $user['email'],
            $user['first_name'],
            '🎉 New Event: ' . $eventTitle,
            $message,
            SITE_URL . '/pages/events/details?id=' . $eventId,
            'View Event & Register'
        )) {
            $sent++;
        }

        // Small delay to avoid rate limiting
        usleep(100000); // 0.1 second
    }

    return $sent;
}

function generateSlug($text)
{
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text;
}


function handleGetEventMedia($conn) {
    $eventId = $_GET['event_id'] ?? 0;
    
    try {
        $stmt = $conn->prepare("SELECT id, title, gallery, videos FROM events WHERE id = ?");
        $stmt->execute([$eventId]);
        $event = $stmt->fetch();
        
        if (!$event) {
            echo json_encode(['success' => false, 'message' => 'Event not found']);
            return;
        }
        
        $gallery = !empty($event['gallery']) ? json_decode($event['gallery'], true) : [];
        $videos = !empty($event['videos']) ? json_decode($event['videos'], true) : [];
        
        echo json_encode([
            'success' => true,
            'event' => $event,
            'gallery' => $gallery,
            'videos' => $videos
        ]);
        
    } catch (PDOException $e) {
        error_log("Get event media error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to fetch event media']);
    }
}

function handleUploadEventGallery($conn) {
    $eventId = $_POST['event_id'] ?? 0;
    
    if (empty($eventId)) {
        echo json_encode(['success' => false, 'message' => 'Event ID is required']);
        return;
    }
    
    // Get current gallery
    $stmt = $conn->prepare("SELECT gallery FROM events WHERE id = ?");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch();
    
    if (!$event) {
        echo json_encode(['success' => false, 'message' => 'Event not found']);
        return;
    }
    
    $currentGallery = !empty($event['gallery']) ? json_decode($event['gallery'], true) : [];
    
    // Check limit
    if (count($currentGallery) >= 20) {
        echo json_encode(['success' => false, 'message' => 'Maximum 20 photos allowed']);
        return;
    }
    
    // Upload new images
    $newImages = [];
    if (isset($_FILES['gallery_images'])) {
        foreach ($_FILES['gallery_images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['gallery_images']['error'][$key] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $_FILES['gallery_images']['name'][$key],
                    'type' => $_FILES['gallery_images']['type'][$key],
                    'tmp_name' => $tmp_name,
                    'error' => $_FILES['gallery_images']['error'][$key],
                    'size' => $_FILES['gallery_images']['size'][$key]
                ];
                
                $uploadedImage = uploadEventImage($file);
                if ($uploadedImage) {
                    $newImages[] = $uploadedImage;
                }
            }
        }
    }
    
    if (empty($newImages)) {
        echo json_encode(['success' => false, 'message' => 'No images uploaded']);
        return;
    }
    
    // Merge with existing gallery
    $updatedGallery = array_merge($currentGallery, $newImages);
    
    // Limit to 20
    if (count($updatedGallery) > 20) {
        $updatedGallery = array_slice($updatedGallery, 0, 20);
    }
    
    try {
        $updateStmt = $conn->prepare("UPDATE events SET gallery = ? WHERE id = ?");
        $updateStmt->execute([json_encode($updatedGallery), $eventId]);
        
        echo json_encode([
            'success' => true,
            'message' => count($newImages) . ' photo(s) uploaded successfully',
            'gallery' => $updatedGallery
        ]);
        
    } catch (PDOException $e) {
        error_log("Upload gallery error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to update gallery']);
    }
}

function handleDeleteEventGalleryImage($conn) {
    $eventId = $_POST['event_id'] ?? 0;
    $imageName = $_POST['image_name'] ?? '';
    
    if (empty($eventId) || empty($imageName)) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        return;
    }
    
    try {
        // Get current gallery
        $stmt = $conn->prepare("SELECT gallery FROM events WHERE id = ?");
        $stmt->execute([$eventId]);
        $event = $stmt->fetch();
        
        if (!$event) {
            echo json_encode(['success' => false, 'message' => 'Event not found']);
            return;
        }
        
        $currentGallery = !empty($event['gallery']) ? json_decode($event['gallery'], true) : [];
        
        // Remove image from array
        $updatedGallery = array_filter($currentGallery, function($img) use ($imageName) {
            return $img !== $imageName;
        });
        
        // Re-index array
        $updatedGallery = array_values($updatedGallery);
        
        // Update database
        $updateStmt = $conn->prepare("UPDATE events SET gallery = ? WHERE id = ?");
        $updateStmt->execute([json_encode($updatedGallery), $eventId]);
        
        // Delete physical file
        $filePath = __DIR__ . '/../assets/images/uploads/' . $imageName;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Photo deleted successfully'
        ]);
        
    } catch (PDOException $e) {
        error_log("Delete gallery image error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to delete photo']);
    }
}

function handleAddEventVideo($conn) {
    $eventId = $_POST['event_id'] ?? 0;
    $videoUrl = $_POST['video_url'] ?? '';
    
    if (empty($eventId) || empty($videoUrl)) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        return;
    }
    
    // Validate YouTube URL
    if (!str_contains($videoUrl, 'youtube.com/embed/') && !str_contains($videoUrl, 'youtu.be')) {
        echo json_encode(['success' => false, 'message' => 'Invalid YouTube URL']);
        return;
    }
    
    try {
        // Get current videos
        $stmt = $conn->prepare("SELECT videos FROM events WHERE id = ?");
        $stmt->execute([$eventId]);
        $event = $stmt->fetch();
        
        if (!$event) {
            echo json_encode(['success' => false, 'message' => 'Event not found']);
            return;
        }
        
        $currentVideos = !empty($event['videos']) ? json_decode($event['videos'], true) : [];
        
        // Check limit
        if (count($currentVideos) >= 10) {
            echo json_encode(['success' => false, 'message' => 'Maximum 10 videos allowed']);
            return;
        }
        
        // Check if video already exists
        if (in_array($videoUrl, $currentVideos)) {
            echo json_encode(['success' => false, 'message' => 'This video is already added']);
            return;
        }
        
        // Add video
        $currentVideos[] = $videoUrl;
        
        // Update database
        $updateStmt = $conn->prepare("UPDATE events SET videos = ? WHERE id = ?");
        $updateStmt->execute([json_encode($currentVideos), $eventId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Video added successfully',
            'videos' => $currentVideos
        ]);
        
    } catch (PDOException $e) {
        error_log("Add video error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to add video']);
    }
}

function handleDeleteEventVideo($conn) {
    $eventId = $_POST['event_id'] ?? 0;
    $videoUrl = $_POST['video_url'] ?? '';
    
    if (empty($eventId) || empty($videoUrl)) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        return;
    }
    
    try {
        // Get current videos
        $stmt = $conn->prepare("SELECT videos FROM events WHERE id = ?");
        $stmt->execute([$eventId]);
        $event = $stmt->fetch();
        
        if (!$event) {
            echo json_encode(['success' => false, 'message' => 'Event not found']);
            return;
        }
        
        $currentVideos = !empty($event['videos']) ? json_decode($event['videos'], true) : [];
        
        // Remove video from array
        $updatedVideos = array_filter($currentVideos, function($vid) use ($videoUrl) {
            return $vid !== $videoUrl;
        });
        
        // Re-index array
        $updatedVideos = array_values($updatedVideos);
        
        // Update database
        $updateStmt = $conn->prepare("UPDATE events SET videos = ? WHERE id = ?");
        $updateStmt->execute([json_encode($updatedVideos), $eventId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Video removed successfully'
        ]);
        
    } catch (PDOException $e) {
        error_log("Delete video error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to remove video']);
    }
}