<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/lang.php';

$base_url = '../';

$pdo = getDB();
$error = '';
$events = [];
$categories = [];

// Fetch categories for filter dropdown
try {
    $stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Categories fetch error: " . $e->getMessage());
    $error = "Unable to load categories.";
}

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$maxPrice = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 2000;
$dateFilter = $_GET['date_filter'] ?? 'all';
$sortBy = $_GET['sort_by'] ?? 'date_asc';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 9;
$offset = ($page - 1) * $perPage;

// Build query with filters
try {
    $userId = $_SESSION['user_id'] ?? 0;
    
    $baseQuery = "
        SELECT e.*, c.name as category_name,
               (SELECT COUNT(*) FROM wishlist WHERE user_id = ? AND event_id = e.id) as is_wishlisted,
               (SELECT COUNT(*) FROM bookings WHERE event_id = e.id) as booking_count
        FROM events e
        JOIN categories c ON e.category_id = c.id
        WHERE 1=1
    ";
    $countQuery = "
        SELECT COUNT(*) as total
        FROM events e
        JOIN categories c ON e.category_id = c.id
        WHERE 1=1
    ";
    $params = [$userId];
    $countParams = [];
    
    // Search filter
    if (!empty($search)) {
        $searchTerm = "%$search%";
        $baseQuery .= " AND (e.title LIKE ? OR e.venue LIKE ?)";
        $countQuery .= " AND (e.title LIKE ? OR e.venue LIKE ?)";
        $params[] = $searchTerm; $params[] = $searchTerm;
        $countParams[] = $searchTerm; $countParams[] = $searchTerm;
    }
    
    // Category filter
    if ($categoryId > 0) {
        $baseQuery .= " AND e.category_id = ?";
        $countQuery .= " AND e.category_id = ?";
        $params[] = $categoryId;
        $countParams[] = $categoryId;
    }

    // Price filter
    $baseQuery .= " AND e.price <= ?";
    $countQuery .= " AND e.price <= ?";
    $params[] = $maxPrice;
    $countParams[] = $maxPrice;

    // Date filters
    switch($dateFilter) {
        case 'today':
            $baseQuery .= " AND DATE(e.event_date) = CURDATE()";
            $countQuery .= " AND DATE(e.event_date) = CURDATE()";
            break;
        case 'week':
            $baseQuery .= " AND e.event_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
            $countQuery .= " AND e.event_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $baseQuery .= " AND e.event_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
            $countQuery .= " AND e.event_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
            break;
        default:
            $baseQuery .= " AND DATE(e.event_date) >= CURDATE()";
            $countQuery .= " AND DATE(e.event_date) >= CURDATE()";
    }

    // Sorting
    switch($sortBy) {
        case 'price_asc': $baseQuery .= " ORDER BY e.price ASC"; break;
        case 'price_desc': $baseQuery .= " ORDER BY e.price DESC"; break;
        case 'popularity': $baseQuery .= " ORDER BY booking_count DESC"; break;
        case 'date_desc': $baseQuery .= " ORDER BY e.event_date DESC"; break;
        default: $baseQuery .= " ORDER BY e.event_date ASC";
    }
    
    $baseQuery .= " LIMIT ? OFFSET ?";
    
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($countParams);
    $totalEvents = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalEvents / $perPage);
    
    $stmt = $pdo->prepare($baseQuery);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k + 1, $v);
    }
    $stmt->bindValue(count($params) + 1, (int)$perPage, PDO::PARAM_INT);
    $stmt->bindValue(count($params) + 2, (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Events query error: " . $e->getMessage());
    $error = "Unable to load events.";
}

// Helper function for local premium event images
function getEventImage($category, $dbImage) {
    if (!empty($dbImage)) {
        $path = '../assets/images/' . $dbImage;
        if (file_exists(__DIR__ . '/' . $path)) {
            return $path;
        }
    }

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
    <title>Browse Events - EthioEvent Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #020617; }
        .search-container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 30px;
            margin-bottom: 50px;
        }
        .form-control, .form-select {
            background: rgba(255, 255, 255, 0.05) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: white !important;
            border-radius: 12px;
            padding: 12px 20px;
        }
        .form-control::placeholder { color: rgba(255,255,255,0.3); }
        .pagination .page-link {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: white;
            border-radius: 8px;
            margin: 0 5px;
        }
        .pagination .active .page-link { background: #6366f1; border-color: #6366f1; }
    </style>
</head>
<body>
    <div class="bg-overlay"></div>
    <div class="hero-bg-image" style="opacity: 0.1;"></div>

    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container py-5">
        <header class="text-center mb-5 animate-fade-in">
            <span class="badge bg-soft-primary text-primary mb-3 px-3 py-2 rounded-pill"><?= __('browse_categories') ?></span>
            <h1 class="display-4 fw-bold text-white mb-3"><?= __('welcome') ?></h1>
        </header>

        <div class="search-container p-4 mb-5 animate-fade-in">
            <form action="" method="GET" id="filterForm">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-0"><i class="fas fa-search text-accent"></i></span>
                            <input type="text" name="search" id="liveSearch" class="form-control bg-transparent border-0 text-white" placeholder="Search experiences..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="category_id" id="categoryFilter" class="form-select bg-transparent border-0 text-white" onchange="this.form.submit()">
                            <option value="0" class="text-dark">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?> class="text-dark">
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="sort_by" class="form-select bg-transparent border-0 text-white" onchange="this.form.submit()">
                            <option value="date_asc" <?= $sortBy == 'date_asc' ? 'selected' : '' ?> class="text-dark">Date (Earliest)</option>
                            <option value="date_desc" <?= $sortBy == 'date_desc' ? 'selected' : '' ?> class="text-dark">Date (Latest)</option>
                            <option value="price_asc" <?= $sortBy == 'price_asc' ? 'selected' : '' ?> class="text-dark">Price (Low to High)</option>
                            <option value="price_desc" <?= $sortBy == 'price_desc' ? 'selected' : '' ?> class="text-dark">Price (High to Low)</option>
                            <option value="popularity" <?= $sortBy == 'popularity' ? 'selected' : '' ?> class="text-dark">Most Popular</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="button" class="btn btn-primary rounded-pill" data-bs-toggle="collapse" data-bs-target="#advancedFilters">
                            <i class="fas fa-sliders-h me-2"></i> Filters
                        </button>
                    </div>
                </div>

                <!-- Advanced Filters Collapse -->
                <div class="collapse <?= ($maxPrice < 2000 || $dateFilter != 'all') ? 'show' : '' ?> mt-4 pt-4 border-top border-white border-opacity-10" id="advancedFilters">
                    <div class="row g-4 align-items-center">
                        <div class="col-md-4">
                            <label class="text-white-50 small fw-bold text-uppercase mb-2 d-block">Price Range: ETB 0 - <span id="priceValDisplay"><?= (int)$maxPrice ?></span></label>
                            <input type="range" name="max_price" class="form-range" min="0" max="5000" step="100" value="<?= $maxPrice ?>" 
                                   oninput="document.getElementById('priceValDisplay').innerText = this.value"
                                   onchange="this.form.submit()">
                        </div>
                        <div class="col-md-4">
                            <label class="text-white-50 small fw-bold text-uppercase mb-2 d-block">Timeframe</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="date_filter" id="date_all" value="all" <?= $dateFilter == 'all' ? 'checked' : '' ?> onchange="this.form.submit()">
                                <label class="btn btn-outline-light btn-sm" for="date_all">All</label>
                                
                                <input type="radio" class="btn-check" name="date_filter" id="date_today" value="today" <?= $dateFilter == 'today' ? 'checked' : '' ?> onchange="this.form.submit()">
                                <label class="btn btn-outline-light btn-sm" for="date_today">Today</label>
                                
                                <input type="radio" class="btn-check" name="date_filter" id="date_week" value="week" <?= $dateFilter == 'week' ? 'checked' : '' ?> onchange="this.form.submit()">
                                <label class="btn btn-outline-light btn-sm" for="date_week">Week</label>
                                
                                <input type="radio" class="btn-check" name="date_filter" id="date_month" value="month" <?= $dateFilter == 'month' ? 'checked' : '' ?> onchange="this.form.submit()">
                                <label class="btn btn-outline-light btn-sm" for="date_month">Month</label>
                            </div>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <a href="events.php" class="text-white-50 small text-decoration-none"><i class="fas fa-undo me-1"></i> Reset All Filters</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div id="searchResults" class="row">
            <?php if (empty($events)): ?>
                <div class="col-12 text-center py-5 text-white-50">
                    <i class="fas fa-search fa-3x mb-3"></i>
                    <p><?= __('no_events_found') ?? 'No upcoming events found matching your criteria.' ?></p>
                </div>
            <?php else: foreach ($events as $e): 
                $price = $e['price'] > 0 ? 'ETB ' . number_format($e['price'], 0) : 'Free';
                $img = getEventImage($e['category_name'], $e['image']);
            ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 border-0">
                        <div style="background-image: url('<?= $img ?>'); height: 240px; background-size: cover; background-position: center; position: relative;">
                            <div class="event-badge" style="position: absolute; top: 15px; left: 15px; color: white; padding: 5px 15px; border-radius: 50px; font-weight: 800; font-size: 0.65rem; background: rgba(0,0,0,0.5); backdrop-filter: blur(10px);"><?= htmlspecialchars($e['category_name']) ?></div>
                            <div style="position: absolute; top: 15px; right: 15px;">
                                <button class="btn-wishlist <?= $e['is_wishlisted'] ? 'active' : '' ?>" onclick="event.preventDefault(); toggleWishlist(<?= $e['id'] ?>, this)">
                                    <i class="<?= $e['is_wishlisted'] ? 'fa-solid' : 'fa-regular' ?> fa-heart"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <h4 class="card-title fw-bold h5 text-white mb-3"><?= htmlspecialchars($e['title']) ?></h4>
                            <div class="d-flex flex-column gap-2 mb-4 opacity-75 text-white small">
                                <div><i class="far fa-calendar-alt me-2 text-accent"></i> <?= date('M d, Y', strtotime($e['event_date'])) ?></div>
                                <div><i class="fas fa-map-marker-alt me-2 text-accent"></i> <?= htmlspecialchars($e['venue']) ?></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-white fs-5"><?= $price ?></span>
                                <a href="event_details.php?id=<?= $e['id'] ?>" class="btn btn-primary btn-sm px-4">Details</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <nav class="mt-5">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&category_id=<?= $categoryId ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
