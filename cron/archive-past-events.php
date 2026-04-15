<?php
/**
 * Archive Past Events Cron Job
 * Run this daily to automatically move completed events to "past" status
 * 
 * Setup cron job:
 * 0 0 * * * php /path/to/scribes-global/cron/archive-past-events.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = new Database();
$conn = $db->connect();

// Update events that have ended and are not already archived
$stmt = $conn->query("
    UPDATE events 
    SET status = 'completed', archived_at = NOW()
    WHERE status = 'upcoming' 
    AND end_date < NOW()
    AND archived_at IS NULL
");

$archivedCount = $stmt->rowCount();

echo "✅ Archived {$archivedCount} past event(s)\n";

// Also update events where start_date has passed (if no end_date)
$stmt2 = $conn->query("
    UPDATE events 
    SET status = 'completed', archived_at = NOW()
    WHERE status = 'upcoming' 
    AND start_date < NOW()
    AND end_date IS NULL
    AND archived_at IS NULL
");

$additionalArchived = $stmt2->rowCount();

echo "✅ Archived {$additionalArchived} additional past event(s) (based on start date)\n";

$totalArchived = $archivedCount + $additionalArchived;
echo "\n✅ Total archived: {$totalArchived} event(s)\n";
?>