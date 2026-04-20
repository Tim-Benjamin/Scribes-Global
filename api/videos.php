<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Check if admin
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = new Database();
$conn = $db->connect();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        listVideos($conn);
        break;
    case 'get':
        getVideo($conn);
        break;
    case 'add':
        addVideo($conn);
        break;
    case 'update':
        updateVideo($conn);
        break;
    case 'delete':
        deleteVideo($conn);
        break;
    case 'reorder':
        reorderVideos($conn);
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function listVideos($conn) {
    $status = $_GET['status'] ?? 'active';
    
    $stmt = $conn->prepare("
        SELECT * FROM homepage_videos 
        WHERE status = ?
        ORDER BY row_title ASC, sort_order ASC
    ");
    $stmt->execute([$status]);
    $videos = $stmt->fetchAll();
    
    // Group by row_title
    $grouped = [];
    foreach ($videos as $video) {
        $row = $video['row_title'] ?? 'Uncategorized';
        if (!isset($grouped[$row])) {
            $grouped[$row] = [];
        }
        $grouped[$row][] = $video;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $grouped,
        'total' => count($videos)
    ]);
}

function getVideo($conn) {
    $id = $_GET['id'] ?? 0;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Video ID required']);
        return;
    }
    
    $stmt = $conn->prepare("SELECT * FROM homepage_videos WHERE id = ?");
    $stmt->execute([$id]);
    $video = $stmt->fetch();
    
    if (!$video) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Video not found']);
        return;
    }
    
    echo json_encode(['success' => true, 'data' => $video]);
}

function addVideo($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (empty($data['youtube_url']) || empty($data['title'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'YouTube URL and Title are required']);
        return;
    }
    
    // Extract YouTube ID if full URL provided
    $youtubeUrl = extractYoutubeId($data['youtube_url']);
    
    $user = getCurrentUser();
    
    $stmt = $conn->prepare("
        INSERT INTO homepage_videos 
        (youtube_url, title, description, video_date, row_title, sort_order, status, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $sort_order = $data['sort_order'] ?? 0;
    $status = $data['status'] ?? 'active';
    $row_title = $data['row_title'] ?? 'Featured Videos';
    $video_date = $data['video_date'] ?? date('Y-m-d');
    
    $result = $stmt->execute([
        $youtubeUrl,
        $data['title'],
        $data['description'] ?? '',
        $video_date,
        $row_title,
        $sort_order,
        $status,
        $user['id']
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Video added successfully',
            'id' => $conn->lastInsertId()
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to add video']);
    }
}

function updateVideo($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Video ID required']);
        return;
    }
    
    $youtubeUrl = extractYoutubeId($data['youtube_url'] ?? '');
    
    $stmt = $conn->prepare("
        UPDATE homepage_videos 
        SET 
            youtube_url = COALESCE(?, youtube_url),
            title = COALESCE(?, title),
            description = COALESCE(?, description),
            video_date = COALESCE(?, video_date),
            row_title = COALESCE(?, row_title),
            sort_order = COALESCE(?, sort_order),
            status = COALESCE(?, status)
        WHERE id = ?
    ");
    
    $result = $stmt->execute([
        $youtubeUrl ?: null,
        $data['title'] ?? null,
        $data['description'] ?? null,
        $data['video_date'] ?? null,
        $data['row_title'] ?? null,
        isset($data['sort_order']) ? $data['sort_order'] : null,
        $data['status'] ?? null,
        $data['id']
    ]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Video updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update video']);
    }
}

function deleteVideo($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Video ID required']);
        return;
    }
    
    $stmt = $conn->prepare("DELETE FROM homepage_videos WHERE id = ?");
    $result = $stmt->execute([$data['id']]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Video deleted successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to delete video']);
    }
}

function reorderVideos($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['videos']) || !is_array($data['videos'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid video list']);
        return;
    }
    
    try {
        foreach ($data['videos'] as $index => $videoId) {
            $stmt = $conn->prepare("UPDATE homepage_videos SET sort_order = ? WHERE id = ?");
            $stmt->execute([$index, $videoId]);
        }
        echo json_encode(['success' => true, 'message' => 'Videos reordered successfully']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to reorder videos']);
    }
}

function extractYoutubeId($url) {
    // If already just an ID
    if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $url)) {
        return $url;
    }
    
    // Extract from various YouTube URL formats
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([^&\n?#]+)/', $url, $matches)) {
        return $matches[1];
    }
    
    return $url;
}