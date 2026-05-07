<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header('Location: login.php');
    exit;
}

$booking_id = (int)$_GET['id'];
$user_id = (int)$_SESSION['user_id'];

try {
    $pdo = getDB();
    
    // Fetch full booking details with event and user info
    $stmt = $pdo->prepare("
        SELECT b.*, e.title, e.event_date, e.venue, e.price, u.name as attendee_name, c.name as category
        FROM bookings b
        JOIN events e ON b.event_id = e.id
        JOIN users u ON b.user_id = u.id
        JOIN categories c ON e.category_id = c.id
        WHERE b.id = ? AND (b.user_id = ? OR ? IN (SELECT id FROM users WHERE role = 'admin'))
    ");
    $stmt->execute([$booking_id, $user_id, $user_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        die("Ticket not found or access denied.");
    }

} catch (PDOException $e) {
    die("Error generating ticket.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ticket - <?= htmlspecialchars($ticket['booking_reference']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Libre+Barcode+128&family=Outfit:wght@400;700&display=swap');
        
        body { background: #f1f5f9; font-family: 'Outfit', sans-serif; padding: 40px 0; }
        
        .ticket-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(0,0,0,0.1);
            display: flex;
        }
        
        .ticket-main {
            padding: 40px;
            flex-grow: 1;
            border-right: 2px dashed #e2e8f0;
            position: relative;
        }
        
        .ticket-stub {
            width: 250px;
            background: linear-gradient(135deg, #6366f1, #9333ea);
            color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: center;
        }
        
        .brand { color: #6366f1; font-weight: 800; font-size: 1.2rem; margin-bottom: 30px; }
        .event-title { font-size: 2rem; font-weight: 800; color: #1e1b4b; margin-bottom: 20px; }
        
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .info-item label { color: #94a3b8; font-weight: 700; font-size: 0.7rem; text-transform: uppercase; margin-bottom: 5px; display: block; }
        .info-item p { color: #1e293b; font-weight: 700; margin-bottom: 0; }
        
        .barcode { font-family: 'Libre Barcode 128', cursive; font-size: 4rem; color: #1e293b; margin-top: 30px; }
        
        .qr-code {
            width: 150px;
            height: 150px;
            background: white;
            margin: 0 auto 20px;
            padding: 10px;
            border-radius: 15px;
        }
        
        .qr-placeholder { width: 100%; height: 100%; background: #eee; display: flex; align-items: center; justify-content: center; color: #ccc; }

        @media print {
            body { background: white; padding: 0; }
            .no-print { display: none; }
            .ticket-container { box-shadow: none; border: 1px solid #eee; }
        }
    </style>
</head>
<body>

    <div class="container text-center mb-4 no-print">
        <button onclick="window.print()" class="btn btn-primary px-5 rounded-pill fw-bold">
            <i class="fas fa-download me-2"></i> SAVE AS PDF / PRINT
        </button>
        <a href="dashboard.php" class="btn btn-link text-secondary text-decoration-none ms-3">Return to Dashboard</a>
    </div>

    <div class="ticket-container">
        <div class="ticket-main">
            <div class="brand"><i class="fas fa-bolt"></i> ETHIO EVENT HUB</div>
            <h1 class="event-title"><?= htmlspecialchars($ticket['title']) ?></h1>
            
            <div class="info-grid mt-5">
                <div class="info-item">
                    <label>Attendee Name</label>
                    <p><?= htmlspecialchars($ticket['attendee_name']) ?></p>
                </div>
                <div class="info-item">
                    <label>Ticket Reference</label>
                    <p><?= htmlspecialchars($ticket['booking_reference']) ?></p>
                </div>
                <div class="info-item">
                    <label>Event Date & Venue</label>
                    <p><?= date('l, M d, Y', strtotime($ticket['event_date'])) ?></p>
                    <small class="text-muted"><?= htmlspecialchars($ticket['venue']) ?></small>
                </div>
                <div class="info-item">
                    <label>Booking Time</label>
                    <p><?= date('D, M d, Y @ g:i A', strtotime($ticket['booking_date'])) ?></p>
                </div>
                <div class="info-item">
                    <label>Ticket Type</label>
                    <p><?= htmlspecialchars($ticket['category']) ?></p>
                </div>
                <div class="info-item">
                    <label>Quantity</label>
                    <p><?= $ticket['quantity'] ?> Person(s)</p>
                </div>
            </div>

            <div class="barcode"><?= $ticket['booking_reference'] ?></div>
        </div>
        
        <div class="ticket-stub">
            <div class="qr-code">
                <div class="qr-placeholder">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= $ticket['booking_reference'] ?>" alt="QR" style="width:100%">
                </div>
            </div>
            <h6 class="fw-bold mb-1">SCAN ME</h6>
            <p class="small opacity-75">Present at entrance</p>
            <hr class="border-white border-opacity-25 my-4">
            <h4 class="fw-bold mb-0">ETB <?= number_format($ticket['total_price'], 0) ?></h4>
            <p class="small opacity-75">Paid via EthioEvent Hub</p>
        </div>
    </div>

    <div class="text-center mt-5 text-muted small no-print">
        <p>Tip: When the print dialog opens, select "Save as PDF" to download your ticket.</p>
    </div>

</body>
</html>
