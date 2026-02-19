<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => SESSION_LIFETIME,
        'cookie_httponly' => true,
        'cookie_secure' => false, // Set to true in production with HTTPS
        'use_strict_mode' => true,
        'sid_length' => 48,
    ]);
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Get current user data
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null; // Changed from returning nothing to explicitly returning null
    }
    
    require_once __DIR__ . '/database.php';
    $db = new Database();
    $conn = $db->connect();
    
    try {
        $stmt = $conn->prepare("
            SELECT u.*, 
                   GROUP_CONCAT(DISTINCT b.badge_type) as badges,
                   GROUP_CONCAT(DISTINCT m.ministry_name) as ministries
            FROM users u
            LEFT JOIN user_badges b ON u.id = b.user_id
            LEFT JOIN user_ministries um ON u.id = um.user_id
            LEFT JOIN ministries m ON um.ministry_id = m.id
            WHERE u.id = ?
            GROUP BY u.id
        ");
        
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        return $user ?: null; // Return null if no user found
    } catch (PDOException $e) {
        error_log("Error fetching user: " . $e->getMessage());
        return null;
    }
}

// Check if user has specific role
function hasRole($role) {
    $user = getCurrentUser();
    return $user && isset($user['role']) && $user['role'] === $role;
}

// Check if user is admin
function isAdmin() {
    return hasRole('super_admin') || hasRole('administrator');
}

// Regenerate session ID for security
function regenerateSession() {
    session_regenerate_id(true);
}
?>