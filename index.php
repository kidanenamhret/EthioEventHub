<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/lang.php';

// Fetch featured events (only future events)
$featuredEvents = [];
$dbError = false;

try {
    $pdo = getDB();
    $userId = $_SESSION['user_id'] ?? 0;
    
    // Fetch featured events with wishlist status
    $stmt = $pdo->prepare("
        SELECT e.*, c.name as category, c.id as category_id,
               (SELECT COUNT(*) FROM wishlist WHERE user_id = ? AND event_id = e.id) as is_wishlisted
        FROM events e 
        JOIN categories c ON e.category_id = c.id 
        WHERE e.event_date >= CURDATE() 
        ORDER BY e.event_date ASC 
        LIMIT 3
    ");
    $stmt->execute([$userId]);
    $featuredEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Index page error: " . $e->getMessage());
    $dbError = true;
}

// Helper function to get event image with database priority
function getEventImage($category, $dbImage) {
    // Check if there is an uploaded image in the database
    if (!empty($dbImage)) {
        $path = 'assets/images/' . $dbImage;
        if (file_exists(__DIR__ . '/' . $path)) {
            return $path;
        }
    }

    // Category fallbacks
    $localMap = [
        'Music' => 'assets/images/jazz.jpg',
        'Tech' => 'assets/images/tech.jpg',
        'Cultural' => 'assets/images/irreecha.jpg'
    ];
    
    if (isset($localMap[$category]) && file_exists(__DIR__ . '/' . $localMap[$category])) {
        return $localMap[$category];
    }
    
    return 'assets/images/hero.png';
}

// Fetch categories from DB
try {
    $stmt = $pdo->query("SELECT id, name, icon FROM categories ORDER BY name");
    $categoriesList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Clean up icons (remove fa- prefix if present for compatibility with the frontend logic)
    foreach ($categoriesList as &$cat) {
        $cat['icon'] = str_replace('fa-', '', $cat['icon']);
    }
} catch (PDOException $e) {
    $categoriesList = [];
}

$base_url = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EthioEvent Hub - Experience Ethiopia's Premier Events</title>
    <meta name="description" content="Discover and book premium events across Ethiopia - cultural festivals, concerts, tech summits, and more.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { background: #020617; }
        .hero-section {
            min-height: 85vh;
            display: flex;
            align-items: center;
            text-align: center;
            padding-top: 100px;
        }
        .hero-title {
            font-size: 4rem;
            font-weight: 900;
            line-height: 1.1;
            letter-spacing: -2px;
            background: linear-gradient(135deg, #fff, #a5b4fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .stat-item {
            background: rgba(255,255,255,0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 32px;
            padding: 30px;
        }
        .category-card {
            background: rgba(255,255,255,0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 24px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .category-card:hover {
            transform: translateY(-10px) scale(1.05);
            background: rgba(99,102,241,0.15);
            border-color: #6366f1;
        }
        .featured-card {
            background: rgba(255,255,255,0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 32px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .featured-card:hover {
            transform: translateY(-12px);
            border-color: #6366f1;
            box-shadow: 0 30px 60px -12px rgba(0,0,0,0.5);
        }
    </style>
</head>
<body>
    <div class="bg-overlay"></div>
    <div class="hero-bg-image" style="background-image: url('assets/images/hero.png');"></div>

    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <header class="hero-section">
        <div class="container">
            <span class="badge bg-soft-primary mb-3 animate-fade-in">
                <i class="fas fa-sparkles me-1"></i> <?= __('#1_platform') ?>
            </span>
            <h1 class="hero-title animate-fade-in"><?= __('welcome') ?></h1>
            <p class="lead mb-5 fs-4 text-white-50 animate-fade-in" style="animation-delay: 0.1s"><?= __('subtitle') ?></p>
            <div class="d-flex justify-content-center gap-3 animate-fade-in" style="animation-delay: 0.2s">
                <a href="pages/events.php" class="btn btn-primary btn-lg px-5 shadow"><?= __('explore_events') ?></a>
                <a href="pages/register.php" class="btn btn-secondary btn-lg px-5"><?= __('join_organizer') ?></a>
            </div>
        </div>
    </header>

    <section class="container my-5 py-5">
        <div class="row g-4 text-center">
            <div class="col-md-3 col-6"><div class="stat-item"><h2 class="counter" data-target="500" data-suffix="+">0+</h2><p class="text-white-50 small mb-0"><?= __('stats_events') ?></p></div></div>
            <div class="col-md-3 col-6"><div class="stat-item"><h2 class="counter" data-target="12000" data-suffix="+">0+</h2><p class="text-white-50 small mb-0"><?= __('stats_users') ?></p></div></div>
            <div class="col-md-3 col-6"><div class="stat-item"><h2 class="counter" data-target="50" data-suffix="+">0+</h2><p class="text-white-50 small mb-0"><?= __('stats_venues') ?></p></div></div>
            <div class="col-md-3 col-6"><div class="stat-item"><h2 class="counter" data-target="100" data-suffix="%">0%</h2><p class="text-white-50 small mb-0"><?= __('stats_verified') ?></p></div></div>
        </div>
    </section>

    <section class="py-5">
        <div class="container text-center">
            <h2 class="fw-bold mb-5 display-5"><?= __('browse_categories') ?></h2>
            <div class="row g-3 justify-content-center mb-5">
                <div class="col-auto">
                    <button class="btn btn-primary rounded-pill px-4 category-filter-btn active" data-id="0"><?= __('all_events') ?></button>
                </div>
                <?php foreach ($categoriesList as $cat): ?>
                <div class="col-auto">
                    <button class="btn btn-outline-light rounded-pill px-4 category-filter-btn" data-id="<?= $cat['id'] ?>">
                        <i class="fas fa-<?= $cat['icon'] ?> me-2"></i><?= htmlspecialchars($cat['name']) ?>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="container my-5 py-5">
        <div class="d-flex justify-content-between align-items-end mb-5">
            <div>
                <h2 class="fw-bold display-5 mb-2"><?= __('featured_experiences') ?></h2>
                <p class="text-white-50 mb-0">Handpicked events you can't afford to miss</p>
            </div>
            <a href="pages/events.php" class="text-accent fw-bold text-decoration-none"><?= __('view_all') ?> <i class="fas fa-arrow-right ms-2"></i></a>
        </div>
        
        <div class="row" id="featured-events-container" style="transition: opacity 0.3s ease;">
            <?php if ($dbError): ?>
                <div class="col-12 text-center py-5 text-white-50">Unable to load events.</div>
            <?php else: foreach ($featuredEvents as $event): 
                $priceLabel = ($event['price'] > 0) ? 'ETB ' . number_format($event['price'], 0) : 'Free';
                $eventImg = getEventImage($event['category'], $event['image']);
            ?>
                <div class="col-md-4 mb-4">
                    <div class="featured-card h-100 border-0">
                        <div style="height: 260px; position: relative;">
                            <img src="<?= htmlspecialchars($eventImg) ?>" class="w-100 h-100" style="object-fit: cover;">
                            <div class="position-absolute top-0 start-0 m-3 px-3 py-1 rounded-pill bg-accent text-dark small fw-bold">
                                <?= htmlspecialchars($event['category']) ?>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <h4 class="card-title fw-bold text-white h5 mb-3"><?= htmlspecialchars($event['title']) ?></h4>
                            <div class="d-flex flex-column gap-2 mb-4 opacity-75 text-white small">
                                <div><i class="far fa-calendar-alt me-2 text-accent"></i> <?= date('M d, Y', strtotime($event['event_date'])) ?></div>
                                <div><i class="fas fa-map-marker-alt me-2 text-accent"></i> <?= htmlspecialchars($event['venue']) ?></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-white fs-5"><?= $priceLabel ?></span>
                                <a href="pages/event_details.php?id=<?= (int)$event['id'] ?>" class="btn btn-primary btn-sm px-4">Details</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </section>

    <footer class="py-5 mt-5">
        <div class="container">
            <div class="row g-4 text-start">
                <div class="col-lg-4">
                    <h4 class="fw-bold text-white mb-4 d-flex align-items-center">
                        <img src="assets/images/logo.png" alt="Logo" width="35" height="35" class="me-2 rounded-circle object-fit-cover">
                        ETHIO EVENT HUB
                    </h4>
                    <p class="text-white-50">The premium platform for Ethiopia's event architecture. Discover and book experiences that matter.</p>
                </div>
                <div class="col-lg-2 offset-lg-2">
                    <h6 class="text-white fw-bold mb-4">Quick Links</h6>
                    <ul class="list-unstyled text-white-50 small">
                        <li class="mb-2"><a href="index.php" class="text-decoration-none text-reset">Home</a></li>
                        <li class="mb-2"><a href="pages/events.php" class="text-decoration-none text-reset">Events</a></li>
                        <li class="mb-2"><a href="pages/about.php" class="text-decoration-none text-reset">About Us</a></li>
                        <li class="mb-2"><a href="pages/contact.php" class="text-decoration-none text-reset">Contact</a></li>
                    </ul>
                </div>
                <div class="col-lg-2">
                    <h6 class="text-white fw-bold mb-4">Account</h6>
                    <ul class="list-unstyled text-white-50 small">
                        <li class="mb-2"><a href="pages/dashboard.php" class="text-decoration-none text-reset">Dashboard</a></li>
                        <li class="mb-2"><a href="pages/profile.php" class="text-decoration-none text-reset">My Profile</a></li>
                        <li class="mb-2"><a href="pages/wishlist.php" class="text-decoration-none text-reset">My Wishlist</a></li>
                    </ul>
                </div>
                <div class="col-lg-2">
                    <h6 class="text-white fw-bold mb-4">Support</h6>
                    <ul class="list-unstyled text-white-50 small">
                        <li class="mb-2"><a href="docs/USER_MANUAL.md" class="text-decoration-none text-reset">User Manual</a></li>
                        <li class="mb-2"><a href="pages/contact.php" class="text-decoration-none text-reset">Help Center</a></li>
                    </ul>
                </div>
            </div>
            <hr class="border-white opacity-10 my-5">
            <p class="small opacity-25 text-center mb-0">&copy; 2026 EthioEvent Hub. Developed by Team Mesfin et al. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>