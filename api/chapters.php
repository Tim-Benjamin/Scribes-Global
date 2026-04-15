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
    case 'get_chapter':
        handleGetChapter($conn);
        break;
    case 'create_chapter':
        if (!isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
        handleCreateChapter($conn);
        break;
    case 'update_chapter':
        if (!isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
        handleUpdateChapter($conn);
        break;
    case 'delete_chapter':
        if (!isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
        handleDeleteChapter($conn);
        break;
    case 'submit_join_request':
        handleJoinRequest($conn);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function handleGetChapter($conn) {
    $chapterId = $_GET['id'] ?? 0;
    
    try {
        $stmt = $conn->prepare("
            SELECT c.*, 
                   u.first_name as leader_first_name,
                   u.last_name as leader_last_name,
                   u.email as leader_email,
                   (SELECT COUNT(*) FROM users WHERE chapter_id = c.id) as member_count
            FROM chapters c
            LEFT JOIN users u ON c.leader_id = u.id
            WHERE c.id = ?
        ");
        $stmt->execute([$chapterId]);
        $chapter = $stmt->fetch();
        
        if (!$chapter) {
            echo json_encode(['success' => false, 'message' => 'Chapter not found']);
            return;
        }
        
        // Format response
        $chapter['leader_name'] = $chapter['leader_first_name'] 
            ? $chapter['leader_first_name'] . ' ' . $chapter['leader_last_name']
            : null;
        
        echo json_encode([
            'success' => true,
            'chapter' => $chapter
        ]);
        
    } catch (PDOException $e) {
        error_log("Get chapter error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to get chapter']);
    }
}

function handleCreateChapter($conn) {
    $name = trim($_POST['name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $latitude = $_POST['latitude'] ?? '';
    $longitude = $_POST['longitude'] ?? '';
    $contactEmail = trim($_POST['contact_email'] ?? '');
    $contactPhone = trim($_POST['contact_phone'] ?? '');
    $meetingSchedule = trim($_POST['meeting_schedule'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $aboutText = trim($_POST['about_text'] ?? '');
    $leaderId = $_POST['leader_id'] ?? '';
    $status = $_POST['status'] ?? 'active';
    $isCampus = isset($_POST['is_campus']) ? (int)$_POST['is_campus'] : 0;
    $campusUniversity = trim($_POST['campus_university'] ?? '');
    
    // Validation
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Chapter name is required']);
        return;
    }
    
    if (empty($location)) {
        echo json_encode(['success' => false, 'message' => 'Location is required']);
        return;
    }
    
    // Convert empty strings to null for optional fields
    $latitude = ($latitude === '' || $latitude === null) ? null : (float)$latitude;
    $longitude = ($longitude === '' || $longitude === null) ? null : (float)$longitude;
    $leaderId = ($leaderId === '' || $leaderId === null) ? null : (int)$leaderId;
    $campusUniversity = ($campusUniversity === '') ? null : $campusUniversity;
    $contactEmail = ($contactEmail === '') ? null : $contactEmail;
    $contactPhone = ($contactPhone === '') ? null : $contactPhone;
    $meetingSchedule = ($meetingSchedule === '') ? null : $meetingSchedule;
    $description = ($description === '') ? null : $description;
    $aboutText = ($aboutText === '') ? null : $aboutText;
    
    // Handle hero image upload
    $heroImage = null;
    if (isset($_FILES['hero_image']) && $_FILES['hero_image']['error'] === UPLOAD_ERR_OK) {
        $heroImage = handleFileUpload($_FILES['hero_image'], 'hero');
        if (!$heroImage) {
            echo json_encode(['success' => false, 'message' => 'Failed to upload hero image']);
            return;
        }
    }
    
    // Handle gallery images upload
    $galleryImages = [];
    if (isset($_FILES['gallery']) && is_array($_FILES['gallery']['name'])) {
        foreach ($_FILES['gallery']['name'] as $key => $fileName) {
            if (!empty($fileName) && $_FILES['gallery']['error'][$key] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $_FILES['gallery']['name'][$key],
                    'type' => $_FILES['gallery']['type'][$key],
                    'tmp_name' => $_FILES['gallery']['tmp_name'][$key],
                    'error' => $_FILES['gallery']['error'][$key],
                    'size' => $_FILES['gallery']['size'][$key]
                ];
                $uploaded = handleFileUpload($file, 'gallery');
                if ($uploaded) {
                    $galleryImages[] = $uploaded;
                }
            }
        }
    }
    $gallery = !empty($galleryImages) ? json_encode($galleryImages) : null;
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO chapters (
                name, location, latitude, longitude, 
                contact_email, contact_phone, meeting_schedule, 
                description, about_text, hero_image, gallery,
                is_campus, campus_university, leader_id, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $result = $stmt->execute([
            $name, 
            $location, 
            $latitude, 
            $longitude,
            $contactEmail, 
            $contactPhone, 
            $meetingSchedule,
            $description, 
            $aboutText, 
            $heroImage, 
            $gallery,
            $isCampus, 
            $campusUniversity, 
            $leaderId, 
            $status
        ]);
        
        if ($result) {
            $chapterId = $conn->lastInsertId();
            
            // Log the creation
            error_log("Chapter created successfully: ID=$chapterId, Name=$name, Location=$location, Lat=$latitude, Lng=$longitude");
            
            echo json_encode([
                'success' => true,
                'message' => 'Chapter created successfully',
                'chapter_id' => $chapterId
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to insert chapter']);
        }
        
    } catch (PDOException $e) {
        error_log("Create chapter error: " . $e->getMessage());
        error_log("Data: name=$name, location=$location, lat=$latitude, lng=$longitude");
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleUpdateChapter($conn) {
    $chapterId = $_POST['chapter_id'] ?? 0;
    $name = trim($_POST['name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $latitude = $_POST['latitude'] ?? '';
    $longitude = $_POST['longitude'] ?? '';
    $contactEmail = trim($_POST['contact_email'] ?? '');
    $contactPhone = trim($_POST['contact_phone'] ?? '');
    $meetingSchedule = trim($_POST['meeting_schedule'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $aboutText = trim($_POST['about_text'] ?? '');
    $leaderId = $_POST['leader_id'] ?? '';
    $status = $_POST['status'] ?? 'active';
    $isCampus = isset($_POST['is_campus']) ? (int)$_POST['is_campus'] : 0;
    $campusUniversity = trim($_POST['campus_university'] ?? '');
    
    // Validation
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Chapter name is required']);
        return;
    }
    
    if (empty($location)) {
        echo json_encode(['success' => false, 'message' => 'Location is required']);
        return;
    }
    
    // Convert empty strings to null
    $latitude = ($latitude === '' || $latitude === null) ? null : (float)$latitude;
    $longitude = ($longitude === '' || $longitude === null) ? null : (float)$longitude;
    $leaderId = ($leaderId === '' || $leaderId === null) ? null : (int)$leaderId;
    $campusUniversity = ($campusUniversity === '') ? null : $campusUniversity;
    $contactEmail = ($contactEmail === '') ? null : $contactEmail;
    $contactPhone = ($contactPhone === '') ? null : $contactPhone;
    $meetingSchedule = ($meetingSchedule === '') ? null : $meetingSchedule;
    $description = ($description === '') ? null : $description;
    $aboutText = ($aboutText === '') ? null : $aboutText;
    
    try {
        // Get existing chapter data
        $existingStmt = $conn->prepare("SELECT hero_image, gallery FROM chapters WHERE id = ?");
        $existingStmt->execute([$chapterId]);
        $existing = $existingStmt->fetch();
        
        if (!$existing) {
            echo json_encode(['success' => false, 'message' => 'Chapter not found']);
            return;
        }
        
        // Handle hero image upload
        $heroImage = $existing['hero_image'];
        if (isset($_FILES['hero_image']) && $_FILES['hero_image']['error'] === UPLOAD_ERR_OK) {
            $newHeroImage = handleFileUpload($_FILES['hero_image'], 'hero');
            if ($newHeroImage) {
                // Delete old hero image
                if ($heroImage && file_exists(__DIR__ . '/../assets/images/uploads/' . $heroImage)) {
                    unlink(__DIR__ . '/../assets/images/uploads/' . $heroImage);
                }
                $heroImage = $newHeroImage;
            }
        }
        
        // Handle gallery images upload
        $gallery = $existing['gallery'];
        if (isset($_FILES['gallery']) && is_array($_FILES['gallery']['name'])) {
            $newGalleryImages = [];
            foreach ($_FILES['gallery']['name'] as $key => $fileName) {
                if (!empty($fileName) && $_FILES['gallery']['error'][$key] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $_FILES['gallery']['name'][$key],
                        'type' => $_FILES['gallery']['type'][$key],
                        'tmp_name' => $_FILES['gallery']['tmp_name'][$key],
                        'error' => $_FILES['gallery']['error'][$key],
                        'size' => $_FILES['gallery']['size'][$key]
                    ];
                    $uploaded = handleFileUpload($file, 'gallery');
                    if ($uploaded) {
                        $newGalleryImages[] = $uploaded;
                    }
                }
            }
            
            if (!empty($newGalleryImages)) {
                // Merge with existing gallery
                $existingGallery = $gallery ? json_decode($gallery, true) : [];
                $mergedGallery = array_merge($existingGallery, $newGalleryImages);
                $gallery = json_encode(array_slice($mergedGallery, 0, 10)); // Limit to 10 images
            }
        }
        
        // Check if updated_at column exists
        $columnsStmt = $conn->query("SHOW COLUMNS FROM chapters LIKE 'updated_at'");
        $hasUpdatedAt = $columnsStmt->rowCount() > 0;
        
        if ($hasUpdatedAt) {
            $sql = "
                UPDATE chapters SET
                    name = ?,
                    location = ?,
                    latitude = ?,
                    longitude = ?,
                    contact_email = ?,
                    contact_phone = ?,
                    meeting_schedule = ?,
                    description = ?,
                    about_text = ?,
                    hero_image = ?,
                    gallery = ?,
                    is_campus = ?,
                    campus_university = ?,
                    leader_id = ?,
                    status = ?,
                    updated_at = NOW()
                WHERE id = ?
            ";
        } else {
            $sql = "
                UPDATE chapters SET
                    name = ?,
                    location = ?,
                    latitude = ?,
                    longitude = ?,
                    contact_email = ?,
                    contact_phone = ?,
                    meeting_schedule = ?,
                    description = ?,
                    about_text = ?,
                    hero_image = ?,
                    gallery = ?,
                    is_campus = ?,
                    campus_university = ?,
                    leader_id = ?,
                    status = ?
                WHERE id = ?
            ";
        }
        
        $stmt = $conn->prepare($sql);
        
        $result = $stmt->execute([
            $name, 
            $location, 
            $latitude, 
            $longitude,
            $contactEmail, 
            $contactPhone, 
            $meetingSchedule,
            $description, 
            $aboutText, 
            $heroImage, 
            $gallery,
            $isCampus, 
            $campusUniversity, 
            $leaderId, 
            $status, 
            $chapterId
        ]);
        
        if ($result) {
            error_log("Chapter updated successfully: ID=$chapterId, Name=$name, Location=$location, Lat=$latitude, Lng=$longitude");
            
            echo json_encode([
                'success' => true,
                'message' => 'Chapter updated successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update chapter']);
        }
        
    } catch (PDOException $e) {
        error_log("Update chapter error: " . $e->getMessage());
        error_log("Chapter ID: $chapterId, Name: $name, Location: $location");
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        error_log("Update chapter error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function handleDeleteChapter($conn) {
    $chapterId = $_POST['chapter_id'] ?? 0;
    
    try {
        // Get chapter images to delete
        $stmt = $conn->prepare("SELECT hero_image, gallery FROM chapters WHERE id = ?");
        $stmt->execute([$chapterId]);
        $chapter = $stmt->fetch();
        
        if ($chapter) {
            // Delete hero image
            if ($chapter['hero_image'] && file_exists(__DIR__ . '/../assets/images/uploads/' . $chapter['hero_image'])) {
                unlink(__DIR__ . '/../assets/images/uploads/' . $chapter['hero_image']);
            }
            
            // Delete gallery images
            if ($chapter['gallery']) {
                $gallery = json_decode($chapter['gallery'], true);
                if (is_array($gallery)) {
                    foreach ($gallery as $img) {
                        if (file_exists(__DIR__ . '/../assets/images/uploads/' . $img)) {
                            unlink(__DIR__ . '/../assets/images/uploads/' . $img);
                        }
                    }
                }
            }
        }
        
        // Delete chapter (cascade will handle join_requests)
        $deleteStmt = $conn->prepare("DELETE FROM chapters WHERE id = ?");
        $deleteStmt->execute([$chapterId]);
        
        // Update users who had this chapter
        $updateUsersStmt = $conn->prepare("UPDATE users SET chapter_id = NULL WHERE chapter_id = ?");
        $updateUsersStmt->execute([$chapterId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Chapter deleted successfully'
        ]);
        
    } catch (PDOException $e) {
        error_log("Delete chapter error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to delete chapter']);
    }
}

function handleJoinRequest($conn) {
    $chapterId = $_POST['chapter_id'] ?? 0;
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $userId = isLoggedIn() ? $_SESSION['user_id'] : null;
    
    // Validation
    if (empty($chapterId) || empty($firstName) || empty($lastName) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        return;
    }
    
    // Check if chapter exists
    $chapterStmt = $conn->prepare("SELECT id, name FROM chapters WHERE id = ? AND status = 'active'");
    $chapterStmt->execute([$chapterId]);
    $chapter = $chapterStmt->fetch();
    
    if (!$chapter) {
        echo json_encode(['success' => false, 'message' => 'Chapter not found']);
        return;
    }
    
    // Check for existing request
    $checkStmt = $conn->prepare("
        SELECT id FROM chapter_join_requests 
        WHERE chapter_id = ? AND email = ? AND status = 'pending'
    ");
    $checkStmt->execute([$chapterId, $email]);
    
    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'You already have a pending request for this chapter']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO chapter_join_requests (
                chapter_id, user_id, first_name, last_name, 
                email, phone, message, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $chapterId, $userId, $firstName, $lastName,
            $email, $phone, $message
        ]);
        
        // Send notification email to chapter leader
        // TODO: Implement email notification
        
        echo json_encode([
            'success' => true,
            'message' => 'Your request has been submitted successfully! The chapter leaders will review it and get back to you soon.'
        ]);
        
    } catch (PDOException $e) {
        error_log("Join request error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to submit request']);
    }
}

function handleFileUpload($file, $prefix = 'chapter') {
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        error_log("Invalid file type: " . $file['type']);
        return false;
    }
    
    if ($file['size'] > $maxSize) {
        error_log("File too large: " . $file['size']);
        return false;
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $prefix . '_' . uniqid() . '_' . time() . '.' . $extension;
    $uploadPath = __DIR__ . '/../assets/images/uploads/' . $filename;
    
    // Create uploads directory if it doesn't exist
    $uploadsDir = __DIR__ . '/../assets/images/uploads/';
    if (!file_exists($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        error_log("File uploaded successfully: " . $filename);
        return $filename;
    }
    
    error_log("Failed to move uploaded file");
    return false;
}
?>