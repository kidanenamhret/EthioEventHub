<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
requireLogin();

if ($_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

require_once '../includes/db.php';
$pdo = getDB();

// --- DATA AGGREGATION ---
try {
    // 1. Core KPIs
    $stats = [];
    $stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $stats['total_events'] = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
    $stats['total_bookings'] = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
    $stats['total_revenue'] = $pdo->query("SELECT SUM(total_price) FROM bookings WHERE status != 'cancelled'")->fetchColumn() ?? 0;

    // 2. Category Distribution (Chart)
    $catData = $pdo->query("
        SELECT c.name, COUNT(e.id) as count 
        FROM categories c 
        LEFT JOIN events e ON c.id = e.category_id 
        GROUP BY c.id
    ")->fetchAll(PDO::FETCH_ASSOC);

    // 3. Monthly Revenue (Chart)
    $revData = $pdo->query("
        SELECT DATE_FORMAT(booking_date, '%b %Y') as month, SUM(total_price) as revenue
        FROM bookings
        WHERE status != 'cancelled'
        GROUP BY month
        ORDER BY booking_date DESC
        LIMIT 6
    ")->fetchAll(PDO::FETCH_ASSOC);
    $revData = array_reverse($revData);

    // 4. Booking Trends (Daily - Last 14 Days)
    $trendData = $pdo->query("
        SELECT DATE_FORMAT(booking_date, '%d %b') as day, COUNT(*) as count
        FROM bookings
        WHERE booking_date >= DATE_SUB(NOW(), INTERVAL 14 DAY)
        GROUP BY day
        ORDER BY booking_date ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // 5. Popular Events
    $popularEvents = $pdo->query("
        SELECT e.title, COUNT(b.id) as bookings, SUM(b.total_price) as revenue
        FROM events e
        JOIN bookings b ON e.id = b.event_id
        GROUP BY e.id
        ORDER BY bookings DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    // 6. Top Organizers
    $topOrganizers = $pdo->query("
        SELECT u.name, COUNT(e.id) as event_count
        FROM users u
        JOIN events e ON u.id = e.organizer_id
        WHERE u.role = 'organizer'
        GROUP BY u.id
        ORDER BY event_count DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Intelligence Engine Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Intelligence - EthioEvent Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background: #020617; }
        .stat-card-mini {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 20px;
            transition: transform 0.3s ease;
        }
        .stat-card-mini:hover { transform: translateY(-5px); }
        .chart-box {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 24px;
            padding: 25px;
            height: 100%;
        }
        .table-custom { color: white; font-size: 0.9rem; }
        .table-custom th { color: rgba(255,255,255,0.5); font-weight: 600; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 1px; }
        .table-custom td { padding: 15px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
    </style>
</head>
<body>
    <div class="bg-overlay"></div>
    
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../index.php">
                <i class="fas fa-shield-alt me-2 text-accent"></i> COMMAND CENTER
            </a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link px-3" href="dashboard.php">Personal Dashboard</a></li>
                    <li class="nav-item ms-lg-3"><a class="btn btn-outline-light btn-sm px-4 rounded-pill" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-end mb-5">
            <div>
                <h1 class="display-5 fw-bold text-white mb-2">Platform <span class="text-accent">Intelligence</span></h1>
                <p class="text-white-50 mb-0">Aggregated real-time metrics and growth analytics.</p>
            </div>
            <div class="text-end">
                <span class="badge bg-success bg-opacity-10 text-success p-2 px-3 rounded-pill">
                    <i class="fas fa-circle me-2 small"></i> SYSTEM LIVE
                </span>
            </div>
        </div>

        <!-- KPI Grid -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="stat-card-mini">
                    <p class="text-white-50 small fw-bold mb-1">TOTAL REVENUE</p>
                    <h3 class="text-white fw-bold mb-0">ETB <?= number_format($stats['total_revenue']) ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card-mini">
                    <p class="text-white-50 small fw-bold mb-1">TOTAL USERS</p>
                    <h3 class="text-white fw-bold mb-0"><?= number_format($stats['total_users']) ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card-mini">
                    <p class="text-white-50 small fw-bold mb-1">EVENTS HOSTED</p>
                    <h3 class="text-white fw-bold mb-0"><?= number_format($stats['total_events']) ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card-mini">
                    <p class="text-white-50 small fw-bold mb-1">TOTAL BOOKINGS</p>
                    <h3 class="text-white fw-bold mb-0"><?= number_format($stats['total_bookings']) ?></h3>
                </div>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="row g-4 mb-5">
            <div class="col-lg-8">
                <div class="chart-box">
                    <h5 class="text-white fw-bold mb-4">Revenue Growth (Last 6 Months)</h5>
                    <canvas id="revenueChart" height="200"></canvas>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="chart-box">
                    <h5 class="text-white fw-bold mb-4">Market Share</h5>
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Charts Row 2 & Tables -->
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="chart-box">
                    <h5 class="text-white fw-bold mb-4">Booking Velocity (14 Days)</h5>
                    <canvas id="trendChart" height="150"></canvas>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="chart-box">
                    <h5 class="text-white fw-bold mb-4">Top Performing Events</h5>
                    <table class="table table-custom">
                        <thead>
                            <tr><th>Event</th><th>Bookings</th><th>Revenue</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($popularEvents as $e): ?>
                                <tr>
                                    <td><?= htmlspecialchars(substr($e['title'],0,20)) ?>...</td>
                                    <td><?= $e['bookings'] ?></td>
                                    <td class="text-accent fw-bold">ETB <?= number_format($e['revenue']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Common Chart Config
        Chart.defaults.color = 'rgba(255,255,255,0.5)';
        Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";

        // 1. Revenue Chart
        new Chart(document.getElementById('revenueChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($revData, 'month')) ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?= json_encode(array_column($revData, 'revenue')) ?>,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            }
        });

        // 2. Category Chart
        new Chart(document.getElementById('categoryChart'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($catData, 'name')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($catData, 'count')) ?>,
                    backgroundColor: ['#6366f1', '#ec4899', '#f59e0b', '#10b981', '#3b82f6'],
                    borderWidth: 0
                }]
            },
            options: { plugins: { legend: { position: 'bottom' } } }
        });

        // 3. Trend Chart
        new Chart(document.getElementById('trendChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($trendData, 'day')) ?>,
                datasets: [{
                    label: 'New Bookings',
                    data: <?= json_encode(array_column($trendData, 'count')) ?>,
                    backgroundColor: '#6366f1',
                    borderRadius: 5
                }]
            }
        });
    </script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
