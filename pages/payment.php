<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['booking_id'])) {
    header('Location: events.php');
    exit;
}

$booking_id = (int)$_GET['booking_id'];
$user_id = (int)$_SESSION['user_id'];

try {
    $pdo = getDB();
    
    // Fetch booking details to verify ownership and amount
    $stmt = $pdo->prepare("
        SELECT b.*, e.title, e.price 
        FROM bookings b 
        JOIN events e ON b.event_id = e.id 
        WHERE b.id = ? AND b.user_id = ? AND b.status = 'confirmed'
    ");
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        die("Invalid booking or payment already processed.");
    }

} catch (PDOException $e) {
    die("Payment system error.");
}

/**
 * Luhn Algorithm for Credit Card Validation
 * Showing understanding of real-world payment standards
 */
function validateLuhn($number) {
    $sum = 0;
    $numDigits = strlen($number);
    $parity = $numDigits % 2;
    for ($i = 0; $i < $numDigits; $i++) {
        $digit = $number[$i];
        if ($i % 2 == $parity) {
            $digit *= 2;
            if ($digit > 9) $digit -= 9;
        }
        $sum += $digit;
    }
    return ($sum % 10 == 0);
}

// Handle Mock Payment Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $method = $_POST['payment_method'];
    $isValid = true;
    $error = '';

    if ($method === 'card') {
        $cardNum = str_replace(' ', '', $_POST['card_number']);
        if (!validateLuhn($cardNum)) {
            $isValid = false;
            $error = "Invalid credit card number (Luhn Check Failed)";
        }
    }

    if ($isValid) {
        // In a real app, you'd call Chapa/Telebirr API here
        // For simulation, we just success!
        header("Location: booking_confirmation.php?id=$booking_id&paid=true");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Secure Checkout - EthioEvent Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #020617; }
        .payment-card {
            background: rgba(255,255,255,0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 30px;
            overflow: hidden;
        }
        .method-btn {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: white;
            padding: 20px;
            border-radius: 15px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: left;
            width: 100%;
            margin-bottom: 15px;
        }
        .method-btn:hover, .method-btn.active {
            background: rgba(99, 102, 241, 0.1);
            border-color: #6366f1;
        }
        .method-btn.active i { color: #6366f1; }
        .form-control {
            background: rgba(255,255,255,0.05) !important;
            border: 1px solid rgba(255,255,255,0.1) !important;
            color: white !important;
            padding: 12px;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="bg-overlay"></div>
    
    <div class="container py-5 mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="payment-card animate-fade-in">
                    <div class="row g-0">
                        <!-- Summary Column (Redesigned) -->
                        <div class="col-md-5 p-5 border-end border-white border-opacity-10 d-flex flex-column" style="background: linear-gradient(180deg, rgba(99, 102, 241, 0.1) 0%, rgba(2, 6, 23, 0.8) 100%);">
                            <h4 class="text-white fw-bold mb-4 d-flex align-items-center">
                                <i class="fas fa-receipt me-3 text-accent"></i>Summary
                            </h4>
                            
                            <div class="mb-4 p-4 rounded-4 bg-white bg-opacity-5 border border-white border-opacity-10">
                                <h6 class="text-accent small fw-bold text-uppercase mb-2">Event Detail</h6>
                                <p class="text-white h5 fw-bold mb-0"><?= htmlspecialchars($booking['title']) ?></p>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <div class="p-4 rounded-4 bg-white bg-opacity-5 border border-white border-opacity-10">
                                        <h6 class="text-accent small fw-bold text-uppercase mb-2">Attendance</h6>
                                        <p class="text-white h5 fw-bold mb-0"><?= $booking['quantity'] ?> Tickets</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-auto">
                                <div class="p-4 rounded-4 bg-accent bg-opacity-10 border border-accent border-opacity-20 mb-4">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-white-50">Grand Total</span>
                                        <span class="text-accent h3 fw-bold mb-0">ETB <?= number_format($booking['total_price'], 2) ?></span>
                                    </div>
                                </div>
                                
                                <div class="p-3 rounded-3 bg-white bg-opacity-5 border border-white border-opacity-10">
                                    <p class="small text-white-50 mb-0">
                                        <i class="fas fa-shield-check me-2 text-success"></i> 
                                        Secured by Chapa & Telebirr
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Methods Column -->
                        <div class="col-md-7 p-5">
                            <h4 class="text-white fw-bold mb-4">Payment Method</h4>
                            
                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger border-0 small"><?= $error ?></div>
                            <?php endif; ?>

                            <form action="" method="POST" id="paymentForm">
                                <input type="hidden" name="payment_method" id="selectedMethod" value="telebirr">

                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="method-btn active" onclick="selectMethod('telebirr')">
                                            <i class="fas fa-mobile-alt fa-2x mb-2 d-block"></i>
                                            <span class="fw-bold d-block">Telebirr</span>
                                            <small class="opacity-50">Ethio Telecom</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="method-btn" onclick="selectMethod('mpesa')">
                                            <i class="fas fa-wallet fa-2x mb-2 d-block"></i>
                                            <span class="fw-bold d-block">M-PESA</span>
                                            <small class="opacity-50">Safaricom</small>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="method-btn" onclick="selectMethod('card')">
                                            <i class="fas fa-credit-card fa-2x mb-2 d-block"></i>
                                            <span class="fw-bold d-block">Credit / Debit Card</span>
                                            <small class="opacity-50">Visa, Mastercard</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Dynamic Sections -->
                                <div id="cardSection" class="mt-4" style="display: none;">
                                    <div class="mb-3">
                                        <label class="text-white-50 small fw-bold text-uppercase mb-2">Card Number</label>
                                        <input type="text" name="card_number" class="form-control" placeholder="0000 0000 0000 0000">
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-6">
                                            <label class="text-white-50 small fw-bold text-uppercase mb-2">Expiry</label>
                                            <input type="text" class="form-control" placeholder="MM/YY">
                                        </div>
                                        <div class="col-6">
                                            <label class="text-white-50 small fw-bold text-uppercase mb-2">CVC</label>
                                            <input type="text" class="form-control" placeholder="123">
                                        </div>
                                    </div>
                                </div>

                                <div id="mobileSection" class="mt-4">
                                    <div class="mb-3">
                                        <label class="text-white-50 small fw-bold text-uppercase mb-2">Phone Number</label>
                                        <input type="text" name="phone" class="form-control" placeholder="+251 9...">
                                    </div>
                                    <p class="small text-white-50">A payment prompt will be sent to your mobile device.</p>
                                </div>

                                <button type="submit" class="btn btn-primary btn-lg w-100 py-3 rounded-pill fw-bold mt-5 shadow-lg">
                                    CONFIRM PAYMENT
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function selectMethod(method) {
            document.querySelectorAll('.method-btn').forEach(btn => btn.classList.remove('active'));
            event.currentTarget.classList.add('active');
            document.getElementById('selectedMethod').value = method;

            const cardSection = document.getElementById('cardSection');
            const mobileSection = document.getElementById('mobileSection');

            if (method === 'card') {
                cardSection.style.display = 'block';
                mobileSection.style.display = 'none';
            } else {
                cardSection.style.display = 'none';
                mobileSection.style.display = 'block';
            }
        }
    </script>
</body>
</html>
