<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/lang.php';

$base_url = '../';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = getDB();
$role = $_SESSION['role'] ?? 'attendee';
$user_id = (int)$_SESSION['user_id'];
$user_name = htmlspecialchars($_SESSION['user_name'] ?? 'User');

// Initialize statistics
$stats = [
    'total_events' => 0,
    'total_bookings' => 0,
    'total_users' => 0,
    'pending_events' => 0
];

// Data for charts
$chartData = [];
$chartError = false;

try {
    // Fetch statistics based on role
    if ($role === 'admin') {
        // Admin statistics
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM events");
        $stats['total_events'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM bookings");
        $stats['total_bookings'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'attendee'");
        $stats['total_users'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM events WHERE event_date >= CURDATE()");
        $stats['pending_events'] = $stmt->fetchColumn();
        
        // Chart data - all events
        $stmt = $pdo->query("
            SELECT e.title, COUNT(b.id) as total_bookings, SUM(b.total_price) as total_revenue
            FROM events e 
            LEFT JOIN bookings b ON e.id = b.event_id 
            GROUP BY e.id
            ORDER BY e.event_date DESC
            LIMIT 10
        ");
        $chartData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } elseif ($role === 'organizer') {
        // Organizer statistics
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM events WHERE organizer_id = ?");
        $stmt->execute([$user_id]);
        $stats['total_events'] = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("
            SELECT COUNT(b.id) as total 
            FROM bookings b 
            JOIN events e ON b.event_id = e.id 
            WHERE e.organizer_id = ?
        ");
        $stmt->execute([$user_id]);
        $stats['total_bookings'] = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM events 
            WHERE organizer_id = ? AND event_date >= CURDATE()
        ");
        $stmt->execute([$user_id]);
        $stats['pending_events'] = $stmt->fetchColumn();
        
        // Chart data - organizer's events
        $stmt = $pdo->prepare("
            SELECT e.title, COUNT(b.id) as total_bookings, SUM(b.total_price) as total_revenue
            FROM events e 
            LEFT JOIN bookings b ON e.id = b.event_id 
            WHERE e.organizer_id = ? 
            GROUP BY e.id
            ORDER BY e.event_date DESC
        ");
        $stmt->execute([$user_id]);
        $chartData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } else {
        // Attendee statistics
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bookings WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $stats['total_bookings'] = $stmt->fetchColumn();
        
        $stats['total_users'] = $stmt->fetchColumn(); // Reuse as wishlist count

        // Fetch recent bookings
        $stmt = $pdo->prepare("
            SELECT b.*, e.title as event_title, e.event_date, e.venue 
            FROM bookings b
            JOIN events e ON b.event_id = e.id
            WHERE b.user_id = ?
            ORDER BY b.booking_date DESC
            LIMIT 4
        ");
        $stmt->execute([$user_id]);
        $recentBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $chartError = true;
}

// Generate CSRF token for any forms
$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - EthioEvent Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background: #020617; }
        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 25px;
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.08);
            border-color: #6366f1;
        }
        .stat-icon {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 20px;
        }
        .chart-container {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 30px;
        }
        .action-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            transition: all 0.3s ease;
        }
        .action-card:hover {
            transform: scale(1.03);
            border-color: #6366f1;
        }
    </style>
</head>
<body>
    <div class="bg-overlay"></div>
    <div class="hero-bg-image" style="opacity: 0.1;"></div>

    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="row mb-5 align-items-center">
            <div class="col-md-8">
                <h1 class="fw-bold display-5 text-white mb-2 animate-fade-in">Hello, <?= $user_name ?>!</h1>
                <p class="text-white-50 animate-fade-in" style="animation-delay: 0.1s">
                    <span class="badge bg-accent px-3 py-2 rounded-pill text-uppercase"><?= htmlspecialchars($role) ?> PANEL</span>
                </p>
            </div>
            <div class="col-md-4 text-md-end mt-4 mt-md-0 animate-fade-in" style="animation-delay: 0.2s">
                <?php if ($role === 'admin'): ?>
                    <a href="admin_backup.php" class="btn btn-outline-warning px-4 rounded-pill me-2"><i class="fas fa-database me-2"></i>System Backup</a>
                    <a href="my_events.php" class="btn btn-secondary px-4 shadow-sm me-2">Manage All</a>
                    <a href="create_event.php" class="btn btn-primary px-4 shadow-lg">New Event</a>
                <?php elseif ($role === 'organizer'): ?>
                                    <div class="mt-3 d-flex gap-2">
                                        <a href="create_event.php" class="btn btn-outline-primary btn-sm px-4 rounded-pill">Create Event</a>
                                        <a href="organizer_dashboard.php" class="btn btn-primary btn-sm px-4 rounded-pill">Organizer Studio</a>
                                    </div>
                                <?php endif; ?>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <?php if ($role === 'admin'): ?>
                <div class="col-sm-6 col-lg-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between mb-3">
                            <h6 class="text-white-50 fw-bold small text-uppercase">Total Events</h6>
                            <div class="stat-icon bg-primary bg-opacity-20 text-primary"><i class="fas fa-calendar-alt"></i></div>
                        </div>
                        <h2 class="fw-bold text-white mb-0"><?= number_format($stats['total_events']) ?></h2>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between mb-3">
                            <h6 class="text-white-50 fw-bold small text-uppercase">Total Bookings</h6>
                            <div class="stat-icon bg-success bg-opacity-20 text-success"><i class="fas fa-ticket-alt"></i></div>
                        </div>
                        <h2 class="fw-bold text-white mb-0"><?= number_format($stats['total_bookings']) ?></h2>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between mb-3">
                            <h6 class="text-white-50 fw-bold small text-uppercase">Active Users</h6>
                            <div class="stat-icon bg-info bg-opacity-20 text-info"><i class="fas fa-users"></i></div>
                        </div>
                        <h2 class="fw-bold text-white mb-0"><?= number_format($stats['total_users']) ?>+</h2>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between mb-3">
                            <h6 class="text-white-50 fw-bold small text-uppercase">Upcoming</h6>
                            <div class="stat-icon bg-warning bg-opacity-20 text-warning"><i class="fas fa-clock"></i></div>
                        </div>
                        <h2 class="fw-bold text-white mb-0"><?= number_format($stats['pending_events']) ?></h2>
                    </div>
                </div>
            <?php elseif ($role === 'organizer'): ?>
                <div class="col-sm-6 col-lg-4">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="text-white-50 fw-bold small text-uppercase mb-0">My Events</h6>
                            <a href="my_events.php" class="text-accent small text-decoration-none fw-bold">View All</a>
                        </div>
                        <h2 class="fw-bold text-white"><?= number_format($stats['total_events']) ?></h2>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <div class="stat-card">
                        <h6 class="text-white-50 fw-bold small text-uppercase mb-3">Total Sales</h6>
                        <h2 class="fw-bold text-white"><?= number_format($stats['total_bookings']) ?></h2>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <div class="stat-card">
                        <h6 class="text-white-50 fw-bold small text-uppercase mb-3">Upcoming</h6>
                        <h2 class="fw-bold text-white"><?= number_format($stats['pending_events']) ?></h2>
                    </div>
                </div>
            <?php else: ?>
                <div class="col-sm-6">
                    <div class="stat-card">
                        <h6 class="text-white-50 fw-bold small text-uppercase mb-3">My Tickets</h6>
                        <h2 class="fw-bold text-white"><?= number_format($stats['total_bookings']) ?></h2>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="stat-card">
                        <h6 class="text-white-50 fw-bold small text-uppercase mb-3">Saved Events</h6>
                        <h2 class="fw-bold text-white"><?= number_format($stats['total_users']) ?></h2>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php if (($role === 'admin' || $role === 'organizer') && !empty($chartData) && !$chartError): ?>
        <div class="chart-container mb-5 animate-fade-in">
            <h5 class="fw-bold text-white mb-4"><i class="fas fa-chart-line me-2 text-accent"></i> Booking Analytics</h5>
            <canvas id="bookingsChart" height="120"></canvas>
        </div>
        <script>
            (function() {
                const ctx = document.getElementById('bookingsChart').getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode(array_column($chartData, 'title')) ?>,
                        datasets: [
                            {
                                label: 'Bookings',
                                data: <?= json_encode(array_column($chartData, 'total_bookings')) ?>,
                                backgroundColor: 'rgba(99, 102, 241, 0.6)',
                                borderColor: '#6366f1',
                                borderWidth: 2,
                                borderRadius: 10
                            },
                            {
                                label: 'Revenue (ETB)',
                                data: <?= json_encode(array_column($chartData, 'total_revenue')) ?>,
                                backgroundColor: 'rgba(236, 72, 153, 0.6)',
                                borderColor: '#ec4899',
                                borderWidth: 2,
                                borderRadius: 10,
                                type: 'line'
                            }
                        ]
                    },
                    options: {
                        scales: {
                            y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: 'rgba(255,255,255,0.5)' } },
                            x: { grid: { display: false }, ticks: { color: 'rgba(255,255,255,0.5)' } }
                        },
                        plugins: { legend: { display: false } }
                    }
                });
            })();
        </script>
        <?php endif; ?>

        <?php if ($role === 'attendee' && !empty($recentBookings)): ?>
            <div class="mb-5 animate-fade-in">
                <div class="d-flex justify-content-between align-items-end mb-4">
                    <h4 class="fw-bold text-white mb-0">Recent Tickets</h4>
                    <a href="my_bookings.php" class="text-accent small text-decoration-none fw-bold">View All <i class="fas fa-arrow-right ms-2"></i></a>
                </div>
                <div class="row g-4">
                    <?php foreach ($recentBookings as $b): ?>
                        <div class="col-md-6 col-lg-3">
                            <div class="stat-card h-100">
                                <h6 class="text-white fw-bold mb-2"><?= htmlspecialchars($b['event_title']) ?></h6>
                                <p class="text-white-50 small mb-3"><i class="fas fa-calendar-alt me-2"></i><?= date('M d, Y', strtotime($b['event_date'])) ?></p>
                                <a href="view_ticket.php?id=<?= $b['id'] ?>" class="btn btn-outline-primary btn-sm w-100 rounded-pill">Digital Ticket</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <a href="<?= ($role === 'admin' || $role === 'organizer') ? 'organizer_dashboard.php' : 'my_bookings.php' ?>" class="text-decoration-none">
                    <div class="action-card p-5 text-center h-100">
                        <i class="fas <?= $role === 'admin' ? 'fa-user-shield' : ($role === 'organizer' ? 'fa-calendar-check' : 'fa-ticket-alt') ?> fa-3x text-accent mb-4"></i>
                        <h4 class="fw-bold text-white"><?= $role === 'admin' ? 'System Management' : ($role === 'organizer' ? 'Organizer Studio' : 'My Tickets') ?></h4>
                        <p class="text-white-50 small">Manage your <?= ($role === 'admin' || $role === 'organizer') ? 'events and platform activities' : 'purchased digital passes' ?>.</p>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="events.php" class="text-decoration-none">
                    <div class="action-card p-5 text-center h-100">
                        <i class="fas fa-search fa-3x text-accent mb-4"></i>
                        <h4 class="fw-bold text-white">Explore Events</h4>
                        <p class="text-white-50 small">Discover new experiences across Ethiopia.</p>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="profile.php" class="text-decoration-none">
                    <div class="action-card p-5 text-center h-100">
                        <i class="fas fa-cog fa-3x text-accent mb-4"></i>
                        <h4 class="fw-bold text-white">Account Settings</h4>
                        <p class="text-white-50 small">Manage your profile and preferences.</p>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
