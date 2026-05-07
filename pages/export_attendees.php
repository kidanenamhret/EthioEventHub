<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Auth check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['organizer', 'admin'])) {
    die("Unauthorized");
}

if (!isset($_GET['event_id']) || !is_numeric($_GET['event_id'])) {
    die("Invalid Event ID");
}

$event_id = (int)$_GET['event_id'];
$user_id = (int)$_SESSION['user_id'];

try {
    $pdo = getDB();
    
    // Verify ownership
    $stmt = $pdo->prepare("SELECT title FROM events WHERE id = ? AND (organizer_id = ? OR ? = 'admin')");
    $stmt->execute([$event_id, $user_id, $_SESSION['role']]);
    $event = $stmt->fetch();
    
    if (!$event) {
        die("Unauthorized access to this event data.");
    }

    // Fetch attendees
    $stmt = $pdo->prepare("
        SELECT u.name, u.email, u.phone, b.quantity, b.booking_reference, b.booking_date 
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        WHERE b.event_id = ? AND b.status = 'confirmed'
        ORDER BY b.booking_date DESC
    ");
    $stmt->execute([$event_id]);
    $attendees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Set headers for download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Attendees_' . str_replace(' ', '_', $event['title']) . '.csv');

    // Create file pointer
    $output = fopen('php://output', 'w');

    // Add column headers
    fputcsv($output, ['Full Name', 'Email', 'Phone', 'Tickets', 'Reference', 'Booking Date']);

    // Add data
    foreach ($attendees as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
