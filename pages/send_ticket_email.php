<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$booking_id = $input['booking_id'] ?? 0;
$email = $input['email'] ?? '';

if (!$booking_id || !$email) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit;
}

require_once '../includes/db.php';

try {
    $pdo = getDB();
    
    // Fetch booking details
    $stmt = $pdo->prepare("
        SELECT b.*, e.title, e.venue, e.event_date 
        FROM bookings b
        JOIN events e ON b.event_id = e.id
        WHERE b.id = ? AND b.user_id = ?
    ");
    $stmt->execute([$booking_id, $_SESSION['user_id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        echo json_encode(['success' => false, 'error' => 'Booking not found']);
        exit;
    }
    
    // Email content
    $subject = "Your Ticket: " . $booking['title'];
    $message = "
        <html>
        <head>
            <title>Your Event Ticket</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { width: 80%; margin: 20px auto; border: 1px solid #ddd; padding: 20px; border-radius: 10px; }
                .header { background: #6366f1; color: white; padding: 20px; border-radius: 10px 10px 0 0; text-align: center; }
                .details { padding: 20px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Booking Confirmation</h2>
                </div>
                <div class='details'>
                    <p>Dear " . htmlspecialchars($_SESSION['user_name']) . ",</p>
                    <p>Thank you for booking with EthioEvent Hub!</p>
                    <h3>Event Details:</h3>
                    <ul>
                        <li><strong>Event:</strong> " . htmlspecialchars($booking['title']) . "</li>
                        <li><strong>Date:</strong> " . date('F d, Y', strtotime($booking['event_date'])) . "</li>
                        <li><strong>Venue:</strong> " . htmlspecialchars($booking['venue']) . "</li>
                        <li><strong>Quantity:</strong> " . $booking['quantity'] . "</li>
                        <li><strong>Reference:</strong> " . $booking['booking_reference'] . "</li>
                    </ul>
                    <p>Please log in to your account to download your full QR ticket.</p>
                    <p>Best regards,<br>EthioEvent Hub Team</p>
                </div>
            </div>
        </body>
        </html>
    ";
    
    // Headers for HTML email
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: no-reply@ethioevent.com" . "\r\n";
    
    // Send email
    $sent = mail($email, $subject, $message, $headers);
    
    if ($sent) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Email sending failed']);
    }
    
} catch (Exception $e) {
    error_log("Email error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
?>
