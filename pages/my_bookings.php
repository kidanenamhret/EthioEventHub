<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$bookings = [];

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT b.*, e.title as event_title, e.event_date, e.venue, c.name as category 
        FROM bookings b
        JOIN events e ON b.event_id = e.id
        JOIN categories c ON e.category_id = c.id
        WHERE b.user_id = ?
        ORDER BY b.booking_date DESC
    ");
    $stmt->execute([$user_id]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error loading your bookings.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tickets - EthioEvent Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #020617; }
        .ticket-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 30px;
            transition: all 0.3s;
            margin-bottom: 20px;
        }
        .ticket-card:hover { border-color: #6366f1; transform: translateX(10px); }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../index.php">
                <i class="fas fa-bolt me-2 text-accent"></i> ETHIO EVENT HUB
            </a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link px-3" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item ms-lg-3"><a class="btn btn-outline-light btn-sm px-4 rounded-pill" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <header class="mb-5">
            <h1 class="display-5 fw-bold text-white mb-2">My <span class="text-secondary">Digital Passports</span></h1>
            <p class="text-white-50">Manage your upcoming and past experiences.</p>
        </header>

        <?php if (empty($bookings)): ?>
            <div class="text-center py-5">
                <i class="fas fa-ticket-alt fa-4x text-accent opacity-25 mb-4"></i>
                <h3 class="text-white fw-bold">No tickets yet</h3>
                <p class="text-white-50 mb-4">Discover your first event and start making memories.</p>
                <a href="events.php" class="btn btn-primary px-5 rounded-pill shadow-lg">EXPLORE EVENTS</a>
            </div>
        <?php else: foreach ($bookings as $b): ?>
            <div class="ticket-card animate-fade-in">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <span class="badge bg-soft-primary text-primary mb-2"><?= htmlspecialchars($b['category']) ?></span>
                        <h4 class="text-white fw-bold mb-1"><?= htmlspecialchars($b['event_title']) ?></h4>
                        <p class="text-white-50 small mb-0"><i class="fas fa-map-marker-alt me-2 text-accent"></i> <?= htmlspecialchars($b['venue']) ?></p>
                    </div>
                    <div class="col-md-3">
                        <div class="text-white-50 small fw-bold text-uppercase">Date</div>
                        <div class="text-white"><?= date('M d, Y', strtotime($b['event_date'])) ?></div>
                    </div>
                    <div class="col-md-3 text-md-end mt-3 mt-md-0">
                        <a href="event_details.php?id=<?= $b['event_id'] ?>#reviews" class="btn btn-outline-primary px-3 rounded-pill me-2">RATE EVENT</a>
                        <a href="booking_confirmation.php?id=<?= $b['id'] ?>" class="btn btn-primary px-4 rounded-pill">VIEW PASS</a>
                    </div>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>
</body>
</html>