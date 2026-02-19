<?php
require_once __DIR__ . '/config/database.php';

try {
    $db = new Database();
    $conn = $db->connect();
    
    echo "✅ Database connection successful!<br>";
    
    // Test if tables exist
    $tables = ['users', 'chapters', 'ministries', 'events', 'blog_posts'];
    
    foreach ($tables as $table) {
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' exists<br>";
        } else {
            echo "❌ Table '$table' does NOT exist<br>";
        }
    }
    
    // Test user count
    $userStmt = $conn->query("SELECT COUNT(*) as count FROM users");
    $userCount = $userStmt->fetch();
    echo "<br>Total users: " . $userCount['count'] . "<br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>