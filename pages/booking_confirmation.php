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

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: my_bookings.php');
    exit;
}

$bookingId = (int)$_GET['id'];
$pdo = getDB();

try {
    // Fetch booking details with event and category info
    $stmt = $pdo->prepare("
        SELECT b.*, e.title as event_title, e.event_date, e.venue, c.name as category, c.icon as category_icon
        FROM bookings b
        JOIN events e ON b.event_id = e.id
        JOIN categories c ON e.category_id = c.id
        WHERE b.id = ? AND b.user_id = ?
    ");
    $stmt->execute([$bookingId, $_SESSION['user_id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        header('Location: my_bookings.php');
        exit;
    }
} catch (PDOException $e) {
    die("Error loading confirmation.");
}

// Helper function to generate QR code
function generateQRCode($data, $size = 200) {
    return "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl=" . urlencode($data) . "&choe=UTF-8";
}

$qrData = json_encode([
    'ref' => $booking['booking_reference'],
    'event' => $booking['event_title'],
    'user' => $_SESSION['user_name']
]);
$qrCodeUrl = generateQRCode($qrData);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed - EthioEvent Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #020617; }
        .ticket-wrapper {
            background: white;
            border-radius: 32px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0,0,0,0.5);
            color: #1e293b;
        }
        .ticket-header {
            background: linear-gradient(135deg, #6366f1, #9333ea);
            padding: 40px;
            text-align: center;
            color: white;
            position: relative;
        }
        .ticket-body { padding: 40px; }
        .qr-section {
            background: #f8fafc;
            border-left: 2px dashed #e2e8f0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }
        .ticket-detail-label { color: #64748b; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
        .ticket-detail-value { font-weight: 700; color: #1e293b; margin-bottom: 20px; display: block; }
        
        @media (max-width: 768px) {
            .qr-section { border-left: none; border-top: 2px dashed #e2e8f0; }
        }
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            .ticket-wrapper { box-shadow: none; border: 1px solid #e2e8f0; }
        }
    </style>
</head>
<body>
    <div class="bg-overlay no-print"></div>

    <div class="container py-5 mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="ticket-wrapper animate-fade-in">
                    <div class="ticket-header">
                        <div class="mb-3">
                            <i class="fas fa-check-circle fa-3x"></i>
                        </div>
                        <h1 class="fw-bold mb-0">BOOKING CONFIRMED</h1>
                        <p class="opacity-75 mb-0">Your digital pass is ready for <?= htmlspecialchars($booking['event_title']) ?></p>
                    </div>

                    <div class="row g-0">
                        <div class="col-md-8 ticket-body">
                            <div class="row">
                                <div class="col-6">
                                    <span class="ticket-detail-label">Event</span>
                                    <span class="ticket-detail-value"><?= htmlspecialchars($booking['event_title']) ?></span>
                                </div>
                                <div class="col-6 text-end">
                                    <span class="ticket-detail-label">Reference</span>
                                    <span class="ticket-detail-value text-primary"><?= $booking['booking_reference'] ?></span>
                                </div>
                                <div class="col-6">
                                    <span class="ticket-detail-label">Date</span>
                                    <span class="ticket-detail-value"><?= date('F d, Y', strtotime($booking['event_date'])) ?></span>
                                </div>
                                <div class="col-6 text-end">
                                    <span class="ticket-detail-label">Time</span>
                                    <span class="ticket-detail-value"><?= date('g:i A', strtotime($booking['event_date'])) ?></span>
                                </div>
                                <div class="col-12">
                                    <span class="ticket-detail-label">Venue</span>
                                    <span class="ticket-detail-value"><?= htmlspecialchars($booking['venue']) ?></span>
                                </div>
                                <div class="col-6">
                                    <span class="ticket-detail-label">Attendee</span>
                                    <span class="ticket-detail-value"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                                </div>
                                <div class="col-6 text-end">
                                    <span class="ticket-detail-label">Tickets</span>
                                    <span class="ticket-detail-value"><?= $booking['quantity'] ?> Guest(s)</span>
                                </div>
                                <div class="col-12 mt-3 pt-3 border-top border-secondary border-opacity-10">
                                    <span class="ticket-detail-label">Booked On</span>
                                    <span class="ticket-detail-value text-muted small"><?= date('l, F d, Y \a\t g:i A', strtotime($booking['booking_date'])) ?></span>
                                </div>
                            </div>

                            <div class="d-flex gap-3 mt-4 no-print">
                                <button onclick="window.print()" class="btn btn-dark px-4 rounded-pill fw-bold"><i class="fas fa-print me-2"></i>PRINT</button>
                                <button onclick="downloadTicket()" class="btn btn-outline-dark px-4 rounded-pill fw-bold"><i class="fas fa-download me-2"></i>PDF</button>
                            </div>
                        </div>
                        <div class="col-md-4 qr-section">
                            <h6 class="ticket-detail-label mb-3">Check-in QR</h6>
                            <img src="<?= $qrCodeUrl ?>" class="img-fluid mb-3" style="max-width: 150px;">
                            <p class="small text-muted text-center mb-0">Present this code at the venue for instant entry.</p>
                        </div>
                        
                        <!-- Mini Navigation Map -->
                        <div class="mt-4 no-print">
                            <h6 class="ticket-detail-label mb-3">NAVIGATE TO VENUE</h6>
                            <div class="rounded-4 overflow-hidden" style="height: 150px; border: 1px solid #e2e8f0;">
                                <iframe 
                                    width="100%" 
                                    height="100%" 
                                    frameborder="0" 
                                    style="border:0;" 
                                    src="https://maps.google.com/maps?q=<?= urlencode($booking['venue']) ?>&t=&z=14&ie=UTF8&iwloc=&output=embed" 
                                    allowfullscreen>
                                </iframe>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-5 no-print">
                    <a href="dashboard.php" class="text-white-50 text-decoration-none fw-bold">
                        <i class="fas fa-arrow-left me-2"></i> BACK TO DASHBOARD
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function downloadTicket() {
            const element = document.querySelector('.ticket-wrapper');
            const opt = {
                margin: 10,
                filename: 'EthioEvent_Ticket_<?= $booking['booking_reference'] ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>
</html>
