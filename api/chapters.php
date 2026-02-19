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
    $latitude = $_POST['latitude'] ?? null;
    $longitude = $_POST['longitude'] ?? null;
    $contactEmail = trim($_POST['contact_email'] ?? '');
    $contactPhone = trim($_POST['contact_phone'] ?? '');
    $meetingSchedule = trim($_POST['meeting_schedule'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $leaderId = $_POST['leader_id'] ?? null;
    $status = $_POST['status'] ?? 'active';
    
    // Validation
    if (empty($name) || empty($location)) {
        echo json_encode(['success' => false, 'message' => 'Name and location are required']);
        return;
    }
    
    // Convert empty strings to null for numeric fields
    $latitude = $latitude === '' ? null : $latitude;
    $longitude = $longitude === '' ? null : $longitude;
    $leaderId = $leaderId === '' ? null : $leaderId;
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO chapters (
                name, location, latitude, longitude, 
                contact_email, contact_phone, meeting_schedule, 
                description, leader_id, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $name, $location, $latitude, $longitude,
            $contactEmail, $contactPhone, $meetingSchedule,
            $description, $leaderId, $status
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Chapter created successfully'
        ]);
        
    } catch (PDOException $e) {
        error_log("Create chapter error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to create chapter']);
    }
}

function handleUpdateChapter($conn) {
    $chapterId = $_POST['chapter_id'] ?? 0;
    $name = trim($_POST['name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $latitude = $_POST['latitude'] ?? null;
    $longitude = $_POST['longitude'] ?? null;
    $contactEmail = trim($_POST['contact_email'] ?? '');
    $contactPhone = trim($_POST['contact_phone'] ?? '');
    $meetingSchedule = trim($_POST['meeting_schedule'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $leaderId = $_POST['leader_id'] ?? null;
    $status = $_POST['status'] ?? 'active';
    
    // Validation
    if (empty($name) || empty($location)) {
        echo json_encode(['success' => false, 'message' => 'Name and location are required']);
        return;
    }
    
    // Convert empty strings to null
    $latitude = $latitude === '' ? null : $latitude;
    $longitude = $longitude === '' ? null : $longitude;
    $leaderId = $leaderId === '' ? null : $leaderId;
    
    try {
        $stmt = $conn->prepare("
            UPDATE chapters SET
                name = ?,
                location = ?,
                latitude = ?,
                longitude = ?,
                contact_email = ?,
                contact_phone = ?,
                meeting_schedule = ?,
                description = ?,
                leader_id = ?,
                status = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([
            $name, $location, $latitude, $longitude,
            $contactEmail, $contactPhone, $meetingSchedule,
            $description, $leaderId, $status, $chapterId
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Chapter updated successfully'
        ]);
        
    } catch (PDOException $e) {
        error_log("Update chapter error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to update chapter']);
    }
}

function handleDeleteChapter($conn) {
    $chapterId = $_POST['chapter_id'] ?? 0;
    
    try {
        // Check if chapter has members
        $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE chapter_id = ?");
        $checkStmt->execute([$chapterId]);
        $memberCount = $checkStmt->fetch()['count'];
        
        if ($memberCount > 0) {
            echo json_encode([
                'success' => false,
                'message' => "Cannot delete chapter with {$memberCount} member(s). Please reassign members first."
            ]);
            return;
        }
        
        // Delete chapter
        $stmt = $conn->prepare("DELETE FROM chapters WHERE id = ?");
        $stmt->execute([$chapterId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Chapter deleted successfully'
        ]);
        
    } catch (PDOException $e) {
        error_log("Delete chapter error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to delete chapter']);
    }
}
?>