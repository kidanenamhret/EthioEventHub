<?php
// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Validate booking ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: my_bookings.php');
    exit;
}

$booking_id = (int)$_GET['id'];
$user_id = (int)$_SESSION['user_id'];

try {
    $pdo = getDB();
    
    // Start transaction to ensure data integrity
    $pdo->beginTransaction();
    
    // Get booking details and event date
    $stmt = $pdo->prepare("
        SELECT b.*, e.event_date 
        FROM bookings b
        JOIN events e ON b.event_id = e.id
        WHERE b.id = ? AND b.user_id = ? AND b.status = 'confirmed'
        FOR UPDATE
    ");
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        $pdo->rollBack();
        header('Location: my_bookings.php?error=Booking not found or already cancelled');
        exit;
    }
    
    // Policy Check: 24-hour rule
    $eventDate = strtotime($booking['event_date']);
    $now = time();
    $hoursUntilEvent = ($eventDate - $now) / 3600;
    
    if ($hoursUntilEvent <= 0) {
        // Event already passed
        $pdo->rollBack();
        header('Location: my_bookings.php?error=Cannot cancel past events');
        exit;
    }

    // Determine refund eligibility
    $refundPercentage = ($hoursUntilEvent >= 24) ? 100 : 0;
    $refundAmount = $booking['total_price'] * ($refundPercentage / 100);
    
    // 1. Update booking status
    $stmt = $pdo->prepare("
        UPDATE bookings 
        SET status = 'cancelled', 
            cancelled_at = NOW(),
            refund_amount = ?,
            refund_percentage = ?
        WHERE id = ?
    ");
    $stmt->execute([$refundAmount, $refundPercentage, $booking_id]);
    
    // 2. Create Audit Log
    $stmt = $pdo->prepare("
        INSERT INTO cancellation_logs (booking_id, user_id, cancelled_at, refund_amount) 
        VALUES (?, ?, NOW(), ?)
    ");
    $stmt->execute([$booking_id, $user_id, $refundAmount]);
    
    // Commit transaction
    $pdo->commit();
    
    $msg = ($refundPercentage > 0) 
        ? "success: Your booking was cancelled. A refund of ETB " . number_format($refundAmount, 0) . " has been processed."
        : "success: Your booking was cancelled. No refund eligible (less than 24h notice).";
        
    header('Location: my_bookings.php?msg=' . urlencode($msg));
    exit;
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Cancellation error: " . $e->getMessage());
    header('Location: my_bookings.php?error=Cancellation failed. Please contact support.');
    exit;
}
