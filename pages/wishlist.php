<?php
// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=wishlist.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$pdo = getDB();

try {
    // Fetch wishlisted events with categories
    $stmt = $pdo->prepare("
        SELECT e.*, c.name as category_name, c.icon as category_icon
        FROM wishlist w
        JOIN events e ON w.event_id = e.id
        JOIN categories c ON e.category_id = c.id
        WHERE w.user_id = ?
        ORDER BY w.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $events = [];
}

// Helper for local assets
function getEventImage($category) {
    $localMap = [
        'Music' => '../assets/images/jazz.jpg',
        'Tech' => '../assets/images/tech.jpg',
        'Cultural' => '../assets/images/irreecha.jpg'
    ];
    return $localMap[$category] ?? '../assets/images/hero.png';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Saved Events - EthioEvent Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #020617; }
        .wishlist-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 28px;
            overflow: hidden;
            transition: all 0.3s;
        }
        .wishlist-card:hover { transform: translateY(-10px); border-color: #ef4444; }
        .btn-heart {
            position: absolute;
            top: 20px; right: 20px;
            width: 40px; height: 40px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            border: none; cursor: pointer;
            z-index: 10;
        }
    </style>
</head>
<body>
    <div class="bg-overlay"></div>
    <div class="hero-bg-image" style="opacity: 0.1;"></div>

    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../index.php">
                <i class="fas fa-bolt me-2 text-accent"></i> ETHIO EVENT HUB
            </a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link px-3" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item ms-lg-3"><a class="btn btn-outline-light btn-sm px-4 rounded-pill" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <header class="mb-5">
            <h1 class="display-5 fw-bold text-white mb-2">Saved <span class="text-danger">Experiences</span></h1>
            <p class="text-white-50">Events you're interested in. Book them before they sell out!</p>
        </header>

        <?php if (empty($events)): ?>
            <div class="wishlist-card p-5 text-center">
                <i class="fas fa-heart fa-4x text-danger opacity-25 mb-4"></i>
                <h3 class="text-white fw-bold">Your collection is empty</h3>
                <p class="text-white-50 mb-5">Heart your favorite events to keep them here for later.</p>
                <a href="events.php" class="btn btn-primary btn-lg px-5 rounded-pill shadow-lg">DISCOVER EVENTS</a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach($events as $e): 
                    $img = getEventImage($e['category_name']);
                ?>
                <div class="col-md-6 col-lg-4 animate-fade-in">
                    <div class="wishlist-card h-100 position-relative">
                        <button class="btn-heart" onclick="toggleWishlist(<?= $e['id'] ?>, this)">
                            <i class="fas fa-heart"></i>
                        </button>
                        <div style="background-image: url('<?= $img ?>'); height: 200px; background-size: cover; background-position: center;"></div>
                        <div class="p-4">
                            <span class="badge bg-soft-primary text-primary mb-3"><?= htmlspecialchars($e['category_name']) ?></span>
                            <h4 class="text-white fw-bold mb-3"><?= htmlspecialchars($e['title']) ?></h4>
                            <div class="text-white-50 small mb-2"><i class="far fa-calendar-alt me-2 text-accent"></i> <?= date('M d, Y', strtotime($e['event_date'])) ?></div>
                            <div class="text-white-50 small mb-4"><i class="fas fa-map-marker-alt me-2 text-accent"></i> <?= htmlspecialchars($e['venue']) ?></div>
                            <div class="d-grid">
                                <a href="event_details.php?id=<?= $e['id'] ?>" class="btn btn-primary rounded-pill">VIEW & BOOK</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        async function toggleWishlist(eventId, btn) {
            try {
                const response = await fetch('../api/toggle_wishlist.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ event_id: eventId })
                });
                const data = await response.json();
                if (data.success && data.action === 'removed') {
                    btn.closest('.col-md-6').remove();
                    if (document.querySelectorAll('.wishlist-card').length === 0) {
                        location.reload();
                    }
                }
            } catch (error) {
                console.error('Wishlist error:', error);
            }
        }
    </script>
</body>
</html>
