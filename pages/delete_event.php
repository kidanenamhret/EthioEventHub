<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Auth check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['organizer', 'admin'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: organizer_dashboard.php');
    exit;
}

$eventId = (int)$_GET['id'];
$userId = (int)$_SESSION['user_id'];
$role = $_SESSION['role'];

try {
    $pdo = getDB();
    
    // Ownership check (Admins can delete anything)
    if ($role !== 'admin') {
        $stmt = $pdo->prepare("SELECT id FROM events WHERE id = ? AND organizer_id = ?");
        $stmt->execute([$eventId, $userId]);
        if (!$stmt->fetch()) {
            die("Unauthorized: You do not own this event.");
        }
    }
    
    // Delete event (Bookings will be deleted via CASCADE as defined in db.sql)
    $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
    $stmt->execute([$eventId]);
    
    // Success redirect
    header('Location: organizer_dashboard.php?deleted=success');
    exit;
    
} catch (PDOException $e) {
    error_log("Delete event error: " . $e->getMessage());
    die("An error occurred while deleting the event.");
}
?>
