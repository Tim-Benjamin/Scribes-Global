<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<h1>Chapter Debug Information</h1>";
echo "<pre>";

// Get all chapters
$stmt = $conn->query("SELECT * FROM chapters ORDER BY created_at DESC");
$chapters = $stmt->fetchAll();

echo "Total Chapters: " . count($chapters) . "\n\n";

foreach ($chapters as $chapter) {
    echo "===========================================\n";
    echo "ID: " . $chapter['id'] . "\n";
    echo "Name: " . $chapter['name'] . "\n";
    echo "Location: " . $chapter['location'] . "\n";
    echo "Latitude: " . ($chapter['latitude'] ?? 'NULL') . "\n";
    echo "Longitude: " . ($chapter['longitude'] ?? 'NULL') . "\n";
    echo "Is Campus: " . $chapter['is_campus'] . "\n";
    echo "Status: " . $chapter['status'] . "\n";
    echo "Created: " . $chapter['created_at'] . "\n";
    echo "===========================================\n\n";
}

echo "</pre>";
?>