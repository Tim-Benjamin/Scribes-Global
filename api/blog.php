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
    case 'create_quick_post':
        handleCreateQuickPost($conn);
        break;
    case 'create_post':
        handleCreatePost($conn);
        break;
    case 'update_post':
        handleUpdatePost($conn);
        break;
    case 'delete_post':
        handleDeletePost($conn);
        break;
    case 'toggle_like':
        handleToggleLike($conn);
        break;
    case 'add_comment':
        handleAddComment($conn);
        break;
    case 'delete_comment':
        handleDeleteComment($conn);
        break;
    case 'increment_view':
        handleIncrementView($conn);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function handleCreateQuickPost($conn) {
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Please login to post']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    $content = trim($_POST['content'] ?? '');
    
    if (empty($content)) {
        echo json_encode(['success' => false, 'message' => 'Content is required']);
        return;
    }
    
    if (strlen($content) > 280) {
        echo json_encode(['success' => false, 'message' => 'Content too long (max 280 characters)']);
        return;
    }
    
    try {
        // Generate slug and title from content
        $title = substr($content, 0, 50);
        $slug = generateSlug($title) . '-' . time();
        
        $stmt = $conn->prepare("
            INSERT INTO blog_posts (
                title, slug, content, excerpt, author_id, 
                category, status, published_at, created_at
            ) VALUES (?, ?, ?, ?, ?, 'General', 'published', NOW(), NOW())
        ");
        
        $stmt->execute([
            $title,
            $slug,
            $content,
            $content,
            $userId
        ]);
        
        $postId = $conn->lastInsertId();
        
        // Log activity
        $logStmt = $conn->prepare("
            INSERT INTO activity_log (user_id, action, entity_type, entity_id, ip_address, user_agent)
            VALUES (?, 'post_created', 'blog_post', ?, ?, ?)
        ");
        $logStmt->execute([
            $userId,
            $postId,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Post created successfully',
            'post_id' => $postId
        ]);
        
    } catch (PDOException $e) {
        error_log("Create quick post error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to create post']);
    }
}

function handleCreatePost($conn) {
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Please login to post']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $category = $_POST['category'] ?? '';
    $tags = !empty($_POST['tags']) ? $_POST['tags'] : NULL;
    $status = $_POST['status'] ?? 'draft';
    
    // Validation
    if (empty($title)) {
        echo json_encode(['success' => false, 'message' => 'Title is required']);
        return;
    }
    
    if (empty($content)) {
        echo json_encode(['success' => false, 'message' => 'Content is required']);
        return;
    }
    
    if (empty($excerpt)) {
        echo json_encode(['success' => false, 'message' => 'Excerpt is required']);
        return;
    }
    
    if (empty($category)) {
        echo json_encode(['success' => false, 'message' => 'Category is required']);
        return;
    }
    
    // Generate slug
    $slug = generateSlug($title);
    
    // Check if slug exists
    $checkStmt = $conn->prepare("SELECT id FROM blog_posts WHERE slug = ?");
    $checkStmt->execute([$slug]);
    if ($checkStmt->fetch()) {
        $slug .= '-' . time();
    }
    
    // Handle image upload
    $featuredImage = null;
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['featured_image'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'Invalid image type. Only JPG, PNG, GIF, and WEBP are allowed']);
            return;
        }
        
        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Image too large. Maximum size is 5MB']);
            return;
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'post_' . $userId . '_' . time() . '.' . $extension;
        
        // Create uploads directory if it doesn't exist
        $uploadDir = __DIR__ . '/../assets/images/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $uploadPath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            $featuredImage = $filename;
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
            return;
        }
    }
    
    // Calculate reading time (200 words per minute)
    $wordCount = str_word_count(strip_tags($content));
    $readingTime = max(1, ceil($wordCount / 200));
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO blog_posts (
                title, slug, content, excerpt, featured_image, 
                author_id, category, tags, reading_time, status, 
                published_at, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $publishedAt = ($status === 'published') ? date('Y-m-d H:i:s') : null;
        
        $result = $stmt->execute([
            $title,
            $slug,
            $content,
            $excerpt,
            $featuredImage,
            $userId,
            $category,
            $tags,
            $readingTime,
            $status,
            $publishedAt
        ]);
        
        if (!$result) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . implode(', ', $stmt->errorInfo())]);
            return;
        }
        
        $postId = $conn->lastInsertId();
        
        // Log activity
        try {
            $logStmt = $conn->prepare("
                INSERT INTO activity_log (user_id, action, entity_type, entity_id, ip_address, user_agent)
                VALUES (?, 'post_created', 'blog_post', ?, ?, ?)
            ");
            $logStmt->execute([
                $userId,
                $postId,
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
        } catch (Exception $e) {
            // Log activity failed but post was created, so continue
            error_log("Failed to log activity: " . $e->getMessage());
        }
        
        echo json_encode([
            'success' => true,
            'message' => $status === 'published' ? 'Post published successfully!' : 'Draft saved successfully!',
            'post_id' => $postId,
            'slug' => $slug
        ]);
        
    } catch (PDOException $e) {
        error_log("Create post error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function generateSlug($text) {
    // Convert to lowercase
    $text = strtolower($text);
    
    // Remove special characters
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    
    // Replace spaces and multiple hyphens with single hyphen
    $text = preg_replace('/[\s-]+/', '-', $text);
    
    // Trim hyphens from ends
    $text = trim($text, '-');
    
    return $text;
}

function handleUpdatePost($conn) {
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    $postId = $_POST['post_id'] ?? 0;
    
    // Verify ownership
    $checkStmt = $conn->prepare("SELECT author_id FROM blog_posts WHERE id = ?");
    $checkStmt->execute([$postId]);
    $post = $checkStmt->fetch();
    
    if (!$post || ($post['author_id'] != $userId && !isAdmin())) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $category = $_POST['category'] ?? 'General';
    $tags = !empty($_POST['tags']) ? $_POST['tags'] : NULL;
    $status = $_POST['status'] ?? 'draft';
    
    if (empty($title) || empty($content)) {
        echo json_encode(['success' => false, 'message' => 'Title and content are required']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("
            UPDATE blog_posts SET
                title = ?,
                content = ?,
                excerpt = ?,
                category = ?,
                tags = ?,
                status = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([
            $title,
            $content,
            $excerpt,
            $category,
            $tags,
            $status,
            $postId
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Post updated successfully'
        ]);
        
    } catch (PDOException $e) {
        error_log("Update post error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to update post']);
    }
}

function handleDeletePost($conn) {
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    $postId = $_POST['post_id'] ?? 0;
    
    // Verify ownership
    $checkStmt = $conn->prepare("SELECT author_id FROM blog_posts WHERE id = ?");
    $checkStmt->execute([$postId]);
    $post = $checkStmt->fetch();
    
    if (!$post || ($post['author_id'] != $userId && !isAdmin())) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    try {
        // Delete comments
        $conn->prepare("DELETE FROM blog_comments WHERE post_id = ?")->execute([$postId]);
        
        // Delete likes
        $conn->prepare("DELETE FROM blog_likes WHERE post_id = ?")->execute([$postId]);
        
        // Delete post
        $conn->prepare("DELETE FROM blog_posts WHERE id = ?")->execute([$postId]);
        
        echo json_encode(['success' => true, 'message' => 'Post deleted successfully']);
        
    } catch (PDOException $e) {
        error_log("Delete post error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to delete post']);
    }
}

function handleToggleLike($conn) {
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Please login to like posts']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    $postId = $_POST['post_id'] ?? 0;
    
    try {
        // Check if already liked
        $checkStmt = $conn->prepare("SELECT id FROM blog_likes WHERE post_id = ? AND user_id = ?");
        $checkStmt->execute([$postId, $userId]);
        
        if ($checkStmt->fetch()) {
            // Unlike
            $conn->prepare("DELETE FROM blog_likes WHERE post_id = ? AND user_id = ?")->execute([$postId, $userId]);
            $conn->prepare("UPDATE blog_posts SET likes = likes - 1 WHERE id = ?")->execute([$postId]);
            $action = 'unliked';
        } else {
            // Like
            $conn->prepare("INSERT INTO blog_likes (post_id, user_id, created_at) VALUES (?, ?, NOW())")->execute([$postId, $userId]);
            $conn->prepare("UPDATE blog_posts SET likes = likes + 1 WHERE id = ?")->execute([$postId]);
            $action = 'liked';
        }
        
        echo json_encode(['success' => true, 'action' => $action]);
        
    } catch (PDOException $e) {
        error_log("Toggle like error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to toggle like']);
    }
}

function handleAddComment($conn) {
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Please login to comment']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    $postId = $_POST['post_id'] ?? 0;
    $content = trim($_POST['content'] ?? '');
    $parentId = $_POST['parent_id'] ?? null;
    
    if (empty($content)) {
        echo json_encode(['success' => false, 'message' => 'Comment cannot be empty']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO blog_comments (post_id, user_id, parent_id, content, status, created_at)
            VALUES (?, ?, ?, ?, 'approved', NOW())
        ");
        
        $stmt->execute([$postId, $userId, $parentId, $content]);
        
        $commentId = $conn->lastInsertId();
        
        // Log activity
        $logStmt = $conn->prepare("
            INSERT INTO activity_log (user_id, action, entity_type, entity_id, ip_address, user_agent)
            VALUES (?, 'comment_added', 'blog_comment', ?, ?, ?)
        ");
        $logStmt->execute([
            $userId,
            $commentId,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Comment added successfully',
            'comment_id' => $commentId
        ]);
        
    } catch (PDOException $e) {
        error_log("Add comment error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to add comment']);
    }
}

function handleDeleteComment($conn) {
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    $commentId = $_POST['comment_id'] ?? 0;
    
    // Verify ownership
    $checkStmt = $conn->prepare("SELECT user_id FROM blog_comments WHERE id = ?");
    $checkStmt->execute([$commentId]);
    $comment = $checkStmt->fetch();
    
    if (!$comment || ($comment['user_id'] != $userId && !isAdmin())) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    try {
        // Delete replies first
        $conn->prepare("DELETE FROM blog_comments WHERE parent_id = ?")->execute([$commentId]);
        
        // Delete comment
        $conn->prepare("DELETE FROM blog_comments WHERE id = ?")->execute([$commentId]);
        
        echo json_encode(['success' => true, 'message' => 'Comment deleted successfully']);
        
    } catch (PDOException $e) {
        error_log("Delete comment error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to delete comment']);
    }
}

function handleIncrementView($conn) {
    $postId = $_POST['post_id'] ?? 0;
    
    try {
        $stmt = $conn->prepare("UPDATE blog_posts SET views = views + 1 WHERE id = ?");
        $stmt->execute([$postId]);
        
        echo json_encode(['success' => true]);
        
    } catch (PDOException $e) {
        error_log("Increment view error: " . $e->getMessage());
        echo json_encode(['success' => false]);
    }
}

?>