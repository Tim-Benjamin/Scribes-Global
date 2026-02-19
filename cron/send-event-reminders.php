<?php
/**
 * Event Reminder Cron Job
 * Run this daily to send reminders 24 hours before events
 * 
 * Setup cron job:
 * 0 9 * * * php /path/to/scribes-global/cron/send-event-reminders.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mailer.php';

$db = new Database();
$conn = $db->connect();
$mailer = new Mailer();

// Get events happening in 24 hours
$stmt = $conn->query("
    SELECT * FROM events 
    WHERE status = 'upcoming'
    AND start_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 25 HOUR)
    AND start_date > DATE_ADD(NOW(), INTERVAL 23 HOUR)
");

$events = $stmt->fetchAll();

foreach ($events as $event) {
    // Get all registrants
    $regStmt = $conn->prepare("
        SELECT * FROM event_registrations 
        WHERE event_id = ?
    ");
    $regStmt->execute([$event['id']]);
    $registrants = $regStmt->fetchAll();
    
    foreach ($registrants as $registrant) {
        $firstName = explode(' ', $registrant['name'])[0];
        $eventData = [
            'id' => $event['id'],
            'title' => $event['title'],
            'start_date' => $event['start_date'],
            'location' => $event['location']
        ];
        
        $sent = $mailer->sendEventReminderEmail(
            $registrant['email'],
            $firstName,
            $eventData
        );
        
        if ($sent) {
            echo "✅ Reminder sent to {$registrant['email']} for {$event['title']}\n";
        } else {
            echo "❌ Failed to send reminder to {$registrant['email']}\n";
        }
        
        // Small delay to avoid rate limiting
        sleep(1);
    }
}

echo "\n✅ Event reminders completed!\n";
?>