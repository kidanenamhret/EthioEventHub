<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/db.php';

// Only organizers can export their events
if ($_SESSION['role'] !== 'organizer') {
    die("Unauthorized access.");
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT e.id, e.title, c.name as category, e.event_date, e.venue, e.capacity, e.price 
                       FROM events e 
                       JOIN categories c ON e.category_id = c.id 
                       WHERE e.organizer_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

$filename = "my_events_" . date('Ymd') . ".csv";

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'Title', 'Category', 'Date', 'Venue', 'Capacity', 'Price']);

foreach ($events as $event) {
    fputcsv($output, $event);
}

fclose($output);
exit;
?>
