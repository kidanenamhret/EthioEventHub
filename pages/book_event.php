<?php
// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=book_event.php?id=' . ($_GET['id'] ?? ''));
    exit;
}

// Only attendees can book
if (($_SESSION['role'] ?? '') !== 'attendee') {
    die("<div class='container mt-5'><div class='alert alert-danger border-0 bg-danger bg-opacity-10 text-danger'>Only attendees can book events. <a href='dashboard.php' class='alert-link'>Return to Dashboard</a></div></div>");
}

// Validate event ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: events.php');
    exit;
}

$event_id = (int)$_GET['id'];
$user_id = (int)$_SESSION['user_id'];
$error = '';
$event = null;

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

try {
    $pdo = getDB();
    
    // Get event details
    $stmt = $pdo->prepare("
        SELECT e.*, u.name as organizer_name 
        FROM events e 
        JOIN users u ON e.organizer_id = u.id 
        WHERE e.id = ? AND e.event_date >= CURDATE()
    ");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        header('Location: events.php');
        exit;
    }
    
    // Check if user already has a booking
    $stmt = $pdo->prepare("SELECT id FROM bookings WHERE event_id = ? AND user_id = ? AND status != 'cancelled'");
    $stmt->execute([$event_id, $user_id]);
    $userAlreadyBooked = $stmt->fetch();
    
    // Calculate remaining capacity
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) as booked FROM bookings WHERE event_id = ? AND status != 'cancelled'");
    $stmt->execute([$event_id]);
    $bookedCount = $stmt->fetch(PDO::FETCH_ASSOC)['booked'];
    $remainingCapacity = $event['capacity'] - $bookedCount;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $error = "Security validation failed.";
        } else {
            $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 10]]);
            
            if (!$quantity || $quantity > $remainingCapacity) {
                $error = "Invalid quantity or tickets sold out.";
            } elseif ($userAlreadyBooked) {
                $error = "You have already booked this event.";
            } else {
                try {
                    $pdo->beginTransaction();
                    
                    // Capacity lock
                    $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) as booked FROM bookings WHERE event_id = ? AND status != 'cancelled' FOR UPDATE");
                    $stmt->execute([$event_id]);
                    $currentBooked = $stmt->fetch(PDO::FETCH_ASSOC)['booked'];
                    
                    if ($quantity > ($event['capacity'] - $currentBooked)) {
                        throw new Exception("Tickets just sold out. Try a smaller quantity.");
                    }
                    
                    $bookingRef = 'ETH-' . strtoupper(bin2hex(random_bytes(4)));
                    $totalPrice = $quantity * $event['price'];
                    
                    $insert = $pdo->prepare("INSERT INTO bookings (user_id, event_id, quantity, total_price, booking_reference, status, booking_date) VALUES (?, ?, ?, ?, ?, 'confirmed', NOW())");
                    $insert->execute([$user_id, $event_id, $quantity, $totalPrice, $bookingRef]);
                    
                    $bookingId = $pdo->lastInsertId();

                    // Get User details for email
                    $stmtUser = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
                    $stmtUser->execute([$user_id]);
                    $userData = $stmtUser->fetch();

                    // Load Email Helper
                    require_once __DIR__ . '/../includes/email_helper.php';
                    
                    // Prepare booking data for email
                    $bookingData = [
                        'quantity' => $quantity,
                        'total_price' => $totalPrice,
                        'booking_reference' => $bookingRef
                    ];

                    // Send Email Notification
                    sendBookingConfirmationEmail($userData['email'], $userData['name'], $bookingData, $event);

                    // Log Activity
                    require_once __DIR__ . '/../includes/activity_logger.php';
                    logActivity('BOOKING_CREATED', "User {$_SESSION['user_name']} booked {$quantity} tickets for {$event['title']}");

                    // Notify Attendee
                    $notifyAttendee = $pdo->prepare("INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())");
                    $notifyAttendee->execute([$user_id, "Booking confirmed for " . $event['title'] . ". Reference: $bookingRef"]);

                    // Notify Organizer
                    $notifyOrganizer = $pdo->prepare("INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())");
                    $notifyOrganizer->execute([$event['organizer_id'], "New booking for " . $event['title'] . ": $quantity tickets purchased."]);

                    $pdo->commit();
                    
                    header('Location: payment.php?booking_id=' . $bookingId);
                    exit;
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = $e->getMessage();
                }
            }
        }
    }
} catch (PDOException $e) {
    $error = "System error. Try again later.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - EthioEvent Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #020617; }
        .checkout-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 32px;
            overflow: hidden;
        }
        .price-summary {
            background: rgba(99, 102, 241, 0.1);
            border-radius: 20px;
            padding: 25px;
        }
        .form-control {
            background: rgba(255, 255, 255, 0.05) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: white !important;
            font-size: 1.5rem;
            font-weight: bold;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="bg-overlay"></div>
    <div class="hero-bg-image" style="opacity: 0.1;"></div>

    <div class="container py-5 mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="checkout-card animate-fade-in">
                    <div class="bg-primary bg-opacity-10 p-5 text-center">
                        <i class="fas fa-ticket-alt fa-3x text-accent mb-3"></i>
                        <h2 class="text-white fw-bold mb-0">Secure Checkout</h2>
                    </div>

                    <div class="p-5">
                        <div class="mb-4">
                            <h5 class="text-white fw-bold mb-1"><?= htmlspecialchars($event['title']) ?></h5>
                            <p class="text-white-50 small mb-0"><i class="fas fa-map-marker-alt me-2 text-accent"></i> <?= htmlspecialchars($event['venue']) ?></p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger mb-4"><?= $error ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            
                            <div class="mb-4">
                                <label class="form-label text-white-50 small fw-bold text-uppercase">Ticket Quantity</label>
                                <input type="number" name="quantity" id="quantity" class="form-control py-3" value="1" min="1" max="<?= min(10, $remainingCapacity) ?>" required>
                                <div class="text-center mt-2 small text-accent"><?= $remainingCapacity ?> tickets remaining</div>
                            </div>

                            <div class="price-summary mb-5">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-white-50">Unit Price:</span>
                                    <span class="text-white fw-bold">ETB <?= number_format($event['price'], 0) ?></span>
                                </div>
                                <hr class="border-white border-opacity-10">
                                <div class="d-flex justify-content-between">
                                    <span class="text-white fw-bold fs-5">TOTAL COST:</span>
                                    <span class="text-accent fw-bold fs-5" id="totalPrice">ETB <?= number_format($event['price'], 0) ?></span>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100 py-3 shadow-lg fw-bold mb-3">CONFIRM & PAY</button>
                            <a href="event_details.php?id=<?= $event_id ?>" class="btn btn-outline-light w-100 py-3 border-opacity-10">CANCEL</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const price = <?= $event['price'] ?>;
        const qtyInput = document.getElementById('quantity');
        const totalDisplay = document.getElementById('totalPrice');

        qtyInput.addEventListener('input', () => {
            const total = price * qtyInput.value;
            totalDisplay.textContent = 'ETB ' + total.toLocaleString();
        });
    </script>
</body>
</html>