<?php
// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Validate event ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: events.php');
    exit;
}

$eventId = (int)$_GET['id'];
$error = '';
$event = null;
$relatedEvents = [];
$avgRating = 0;
$totalReviews = 0;
$hasBooked = false;

try {
    $pdo = getDB();
    
    // Fetch event details with organizer info
    $stmt = $pdo->prepare("
        SELECT e.*, c.name as category, u.name as organizer
        FROM events e 
        JOIN categories c ON e.category_id = c.id 
        JOIN users u ON e.organizer_id = u.id 
        WHERE e.id = ?
    ");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        header('Location: events.php');
        exit;
    }
    
    // Fetch related events
    $stmt = $pdo->prepare("
        SELECT e.id, e.title, e.event_date, e.venue, e.price, c.name as category
        FROM events e
        JOIN categories c ON e.category_id = c.id
        WHERE e.category_id = ? AND e.id != ? AND e.event_date >= CURDATE()
        ORDER BY e.event_date ASC LIMIT 3
    ");
    $stmt->execute([$event['category_id'], $eventId]);
    $relatedEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch average rating
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE event_id = ?");
    $stmt->execute([$eventId]);
    $ratingStats = $stmt->fetch(PDO::FETCH_ASSOC);
    $avgRating = round($ratingStats['avg_rating'] ?? 0, 1);
    $totalReviews = $ratingStats['total_reviews'];

    // Fetch reviews
    $stmt = $pdo->prepare("
        SELECT r.*, u.name as user_name 
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        WHERE r.event_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$eventId]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check if current user has booked this event (to allow review)
    $hasBooked = false;
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT id FROM bookings WHERE user_id = ? AND event_id = ? AND status = 'confirmed'");
        $stmt->execute([$_SESSION['user_id'], $eventId]);
        $hasBooked = (bool)$stmt->fetch();
    }
    
} catch (PDOException $e) {
    $error = "Unable to load event details.";
}

// Helper for assets
function getEventImage($category, $dbImage) {
    $assetsDir = 'assets/images/';
    
    if (!empty($dbImage)) {
        if (file_exists(__DIR__ . '/../' . $assetsDir . $dbImage)) {
            return $assetsDir . $dbImage;
        }
    }

    $localMap = [
        'Music' => 'jazz.jpg',
        'Tech' => 'tech.jpg',
        'Cultural' => 'irreecha.jpg'
    ];
    return $assetsDir . ($localMap[$category] ?? 'hero.png');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($event['title']) ?> - EthioEvent Hub</title>
    
    <!-- Social Sharing Meta Tags -->
    <meta property="og:title" content="<?= htmlspecialchars($event['title']) ?>">
    <meta property="og:description" content="<?= htmlspecialchars(substr($event['description'], 0, 160)) ?>...">
    <meta property="og:image" content="<?= SITE_URL . getEventImage($event['category'], $event['image']) ?>">
    <meta property="og:url" content="<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" ?>">
    <meta property="og:type" content="event">
    <meta name="twitter:card" content="summary_large_image">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #020617; }
        .event-hero {
            padding: 80px 0;
            background: linear-gradient(135deg, rgba(99,102,241,0.1), rgba(147,51,234,0.1));
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .detail-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 30px;
        }
        .booking-sidebar {
            position: sticky;
            top: 100px;
            background: rgba(99, 102, 241, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 32px;
            padding: 40px;
        }
        .info-pill {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
        }
        .info-pill:hover { transform: translateY(-5px); border-color: #6366f1; }
        .share-btn {
            width: 45px; height: 45px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 12px;
            background: rgba(255,255,255,0.05);
            color: white;
            transition: all 0.3s;
        }
        .share-btn:hover { background: #6366f1; transform: scale(1.1); }
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
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link px-3" href="events.php">Browse Events</a></li>
                    <li class="nav-item">
                        <div class="theme-toggle nav-link" onclick="toggleDarkMode()" title="Toggle Dark/Light Mode">
                            <i class="fas fa-moon"></i>
                        </div>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item"><a class="nav-link px-3" href="dashboard.php">Dashboard</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="event-hero animate-fade-in">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <span class="badge bg-soft-primary text-accent px-3 py-2 rounded-pill mb-4"><?= htmlspecialchars($event['category']) ?></span>
                    <h1 class="display-3 fw-bold text-white mb-3"><?= htmlspecialchars($event['title']) ?></h1>
                    <div class="d-flex align-items-center mb-4">
                        <div class="text-warning me-3">
                            <?php for($i=1; $i<=5; $i++) echo $i <= $avgRating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                        </div>
                        <span class="text-white-50 small fw-bold">(<?= $avgRating ?>/5 based on <?= $totalReviews ?> reviews)</span>
                    </div>
                    <p class="text-white-50 lead"><i class="fas fa-user-circle me-2"></i> Organized by <span class="text-white fw-bold"><?= htmlspecialchars($event['organizer']) ?></span></p>
                </div>
                <div class="col-lg-5">
                    <div class="rounded-4 overflow-hidden shadow-2xl" style="border: 2px solid rgba(255,255,255,0.1);">
                        <img src="../<?= getEventImage($event['category'], $event['image']) ?>" class="w-100 img-fluid" style="object-fit: cover; max-height: 400px;" alt="<?= htmlspecialchars($event['title']) ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-5">
        <div class="row g-5">
            <div class="col-lg-8">
                <div class="mb-5 animate-fade-in" style="animation-delay: 0.1s">
                    <img src="<?= getEventImage($event['category'], $event['image']) ?>" class="w-100 rounded-5 shadow-lg" style="max-height: 500px; object-fit: cover;">
                </div>

                <div class="row g-4 mb-5 animate-fade-in" style="animation-delay: 0.2s">
                    <div class="col-md-4">
                        <div class="info-pill">
                            <i class="far fa-calendar-alt fa-2x mb-3 text-accent"></i>
                            <h6 class="text-white-50 small text-uppercase fw-bold">Date</h6>
                            <p class="text-white mb-0"><?= date('F d, Y', strtotime($event['event_date'])) ?></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-pill">
                            <i class="fas fa-clock fa-2x mb-3 text-accent"></i>
                            <h6 class="text-white-50 small text-uppercase fw-bold">Time</h6>
                            <p class="text-white mb-0"><?= date('g:i A', strtotime($event['event_date'])) ?></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-pill">
                            <i class="fas fa-map-marker-alt fa-2x mb-3 text-accent"></i>
                            <h6 class="text-white-50 small text-uppercase fw-bold">Venue</h6>
                            <p class="text-white mb-0"><?= htmlspecialchars($event['venue']) ?></p>
                        </div>
                    </div>

                    <!-- Venue Location Map -->
                    <div class="mt-5 animate-fade-in">
                        <div class="detail-card">
                            <h5 class="text-white fw-bold mb-4">
                                <i class="fas fa-map-marked-alt text-accent me-2"></i> Venue Location
                            </h5>
                            <div class="rounded-4 overflow-hidden shadow-lg" style="height: 400px; border: 1px solid rgba(255,255,255,0.1);">
                                <iframe 
                                    width="100%" 
                                    height="100%" 
                                    frameborder="0" 
                                    style="border:0; filter: invert(90%) hue-rotate(180deg) brightness(95%) contrast(90%);" 
                                    src="https://maps.google.com/maps?q=<?= urlencode($event['venue'] . ', Ethiopia') ?>&t=&z=15&ie=UTF8&iwloc=&output=embed" 
                                    allowfullscreen>
                                </iframe>
                            </div>
                            <div class="mt-3 d-flex align-items-center">
                                <i class="fas fa-info-circle text-accent me-2"></i>
                                <p class="text-white-50 small mb-0">Exact location: <?= htmlspecialchars($event['venue']) ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="detail-card animate-fade-in" style="animation-delay: 0.3s">
                    <h4 class="text-white fw-bold mb-4">About This Experience</h4>
                    <p class="text-white-50 lead" style="line-height: 1.8;"><?= nl2br(htmlspecialchars($event['description'])) ?></p>
                    
                    <div class="mt-5 d-flex align-items-center gap-3 share-buttons">
                        <span class="text-white-50 fw-bold small text-uppercase">Share:</span>
                        <button onclick="shareOnFacebook()" class="share-btn"><i class="fab fa-facebook-f"></i></button>
                        <button onclick="shareOnTwitter('<?= urlencode($event['title']) ?>')" class="share-btn"><i class="fab fa-twitter"></i></button>
                        <button onclick="shareOnWhatsApp('<?= urlencode($event['title']) ?>')" class="share-btn"><i class="fab fa-whatsapp"></i></button>
                        <button onclick="copyEventLink()" class="share-btn" title="Copy Link"><i class="fas fa-link"></i></button>
                    </div>
                </div>

                <!-- Reviews Section -->
                <div class="mt-5 animate-fade-in">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="text-white fw-bold mb-0">Community Reviews</h4>
                        <div class="text-end">
                            <span class="display-6 fw-bold text-accent"><?= $avgRating ?></span>
                            <div class="text-warning small">
                                <?php for($i=1; $i<=5; $i++) echo $i <= $avgRating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                            </div>
                            <p class="text-white-50 small mb-0"><?= $totalReviews ?> Reviews</p>
                        </div>
                    </div>
                    
                    <?php if ($hasBooked): ?>
                        <div class="detail-card mb-5">
                            <h5 class="text-white mb-3">Share Your Experience</h5>
                            <form action="../api/submit_review.php" method="POST">
                                <input type="hidden" name="event_id" value="<?= $eventId ?>">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="text-white-50 small fw-bold text-uppercase mb-2">Rating</label>
                                        <select name="rating" class="form-select bg-white bg-opacity-5 border-white border-opacity-10 text-white" required>
                                            <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
                                            <option value="4">⭐⭐⭐⭐ Very Good</option>
                                            <option value="3">⭐⭐⭐ Good</option>
                                            <option value="2">⭐⭐ Fair</option>
                                            <option value="1">⭐ Poor</option>
                                        </select>
                                    </div>
                                    <div class="col-md-12">
                                        <textarea name="comment" class="form-control bg-white bg-opacity-5 border-white border-opacity-10 text-white" rows="3" placeholder="What did you think of the event?" required></textarea>
                                    </div>
                                    <div class="col-md-12">
                                        <button type="submit" class="btn btn-primary px-5 rounded-pill shadow-lg">Post Review</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>

                    <div class="reviews-list">
                        <?php if (empty($reviews)): ?>
                            <div class="text-center py-5 text-white-50">
                                <i class="far fa-comment-dots fa-3x mb-3 opacity-20"></i>
                                <p>Be the first to share your thoughts!</p>
                            </div>
                        <?php else: foreach ($reviews as $r): ?>
                            <div class="detail-card mb-4 border-0" style="background: rgba(255,255,255,0.02);">
                                <div class="d-flex gap-3">
                                    <div class="flex-shrink-0">
                                        <div class="avatar-circle bg-accent text-white fw-bold d-flex align-items-center justify-content-center" style="width:50px; height:50px; border-radius:50%;">
                                            <?= strtoupper(substr($r['user_name'], 0, 1)) ?>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <h6 class="text-white fw-bold mb-0"><?= htmlspecialchars($r['user_name']) ?></h6>
                                                <div class="text-warning small" style="font-size: 0.7rem;">
                                                    <?php for($i=1; $i<=5; $i++) echo $i <= $r['rating'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                                                    <span class="ms-2 text-white-50"><?= date('M d, Y', strtotime($r['created_at'])) ?></span>
                                                </div>
                                            </div>
                                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                                <a href="../api/delete_review.php?id=<?= $r['id'] ?>&event_id=<?= $eventId ?>" class="text-danger small text-decoration-none"><i class="fas fa-trash"></i></a>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-white-50 mb-3" style="font-size: 0.95rem;"><?= nl2br(htmlspecialchars($r['comment'])) ?></p>
                                        <div class="d-flex align-items-center gap-3">
                                            <button class="btn btn-sm btn-outline-light border-opacity-10 py-0 px-2 rounded-pill" style="font-size: 0.7rem;" onclick="voteReview(<?= $r['id'] ?>)">
                                                <i class="far fa-thumbs-up me-1"></i> Helpful (<?= $r['helpful_count'] ?? 0 ?>)
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="booking-sidebar animate-fade-in" style="animation-delay: 0.4s">
                    <h5 class="text-white fw-bold mb-4 text-center">TICKETS AVAILABLE</h5>
                    <div class="text-center mb-5">
                        <h2 class="display-4 fw-bold text-white mb-0">
                            <?= $event['price'] > 0 ? 'ETB ' . number_format($event['price'], 0) : 'FREE' ?>
                        </h2>
                        <p class="text-white-50 small">Secure and instant booking</p>
                    </div>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($_SESSION['role'] === 'attendee'): ?>
                            <a href="book_event.php?id=<?= $event['id'] ?>" class="btn btn-primary btn-lg w-100 py-3 shadow-lg fw-bold">BOOK NOW</a>
                        <?php else: ?>
                            <div class="alert bg-white bg-opacity-5 text-white-50 border-0 text-center">Organizers cannot book events.</div>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-primary btn-lg w-100 py-3 shadow-lg fw-bold">LOGIN TO BOOK</a>
                    <?php endif; ?>

                    <div class="mt-5 text-center">
                        <p class="text-white-50 small mb-0"><i class="fas fa-shield-alt me-2 text-accent"></i> 100% Secure Payment Guarantee</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>