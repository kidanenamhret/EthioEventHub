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
    'pending_events' => 0,
    'total_revenue' => 0
];

// Data for charts
$chartData = [];
$chartError = false;

try {
    // Fetch statistics based on role
    if ($role === 'admin') {
        $stmt = $pdo->query("SELECT COUNT(*) FROM events");
        $stats['total_events'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM bookings");
        $stats['total_bookings'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'attendee'");
        $stats['total_users'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT SUM(total_price) FROM bookings");
        $stats['total_revenue'] = $stmt->fetchColumn() ?: 0;
        
        $stmt = $pdo->query("SELECT e.title, COUNT(b.id) as total_bookings, SUM(b.total_price) as total_revenue FROM events e LEFT JOIN bookings b ON e.id = b.event_id GROUP BY e.id ORDER BY e.event_date DESC LIMIT 10");
        $chartData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } elseif ($role === 'organizer') {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM events WHERE organizer_id = ?");
        $stmt->execute([$user_id]);
        $stats['total_events'] = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(b.id), SUM(b.total_price) FROM bookings b JOIN events e ON b.event_id = e.id WHERE e.organizer_id = ?");
        $stmt->execute([$user_id]);
        $res = $stmt->fetch();
        $stats['total_bookings'] = $res[0];
        $stats['total_revenue'] = $res[1] ?: 0;
        
        $stmt = $pdo->prepare("SELECT e.title, COUNT(b.id) as total_bookings, SUM(b.total_price) as total_revenue FROM events e LEFT JOIN bookings b ON e.id = b.event_id WHERE e.organizer_id = ? GROUP BY e.id ORDER BY e.event_date DESC");
        $stmt->execute([$user_id]);
        $chartData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $stats['total_bookings'] = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $stats['wishlist_count'] = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT b.*, e.title as event_title, e.event_date FROM bookings b JOIN events e ON b.event_id = e.id WHERE b.user_id = ? ORDER BY b.booking_date DESC LIMIT 4");
        $stmt->execute([$user_id]);
        $recentBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $chartError = true;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - EthioEvent Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar" id="sidebar">
            <div class="mb-5 px-3">
                <a class="navbar-brand d-flex align-items-center fw-bold text-primary" href="<?= $base_url ?>index.php">
                    <img src="<?= $base_url ?>assets/images/logo.png" alt="Logo" width="35" height="35" class="me-2 rounded-circle">
                    <span class="text-white">ETHIO EVENT</span>
                </a>
            </div>
            
            <nav class="flex-grow-1">
                <a href="#" class="sidebar-link active"><i class="fas fa-th-large"></i> Dashboard</a>
                <a href="profile.php" class="sidebar-link"><i class="fas fa-user-circle"></i> Your Profile</a>
                
                <div class="text-uppercase small fw-bold text-white-50 mt-4 mb-2 px-3" style="letter-spacing: 1px;">Operations</div>
                <?php if ($role === 'organizer' || $role === 'admin'): ?>
                    <a href="organizer_dashboard.php" class="sidebar-link"><i class="fas fa-calendar-plus"></i> Manage Events</a>
                    <a href="#" class="sidebar-link"><i class="fas fa-chart-pie"></i> Analytics</a>
                <?php else: ?>
                    <a href="events.php" class="sidebar-link"><i class="fas fa-search"></i> Active Events</a>
                    <a href="my_bookings.php" class="sidebar-link"><i class="fas fa-ticket-alt"></i> My Tickets</a>
                    <a href="wishlist.php" class="sidebar-link"><i class="fas fa-heart"></i> Wishlist</a>
                <?php endif; ?>

                <div class="text-uppercase small fw-bold text-white-50 mt-4 mb-2 px-3" style="letter-spacing: 1px;">Other</div>
                <a href="contact.php" class="sidebar-link"><i class="fas fa-headset"></i> Support</a>
                <a href="logout.php" class="sidebar-link text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="dashboard-content">
            <!-- Topbar -->
            <div class="dashboard-topbar">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-dark d-lg-none rounded-circle" onclick="document.getElementById('sidebar').classList.toggle('show')">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h4 class="fw-bold text-white mb-0">Welcome, <?= $user_name ?>!</h4>
                </div>
                <div class="d-flex align-items-center gap-4">
                    <div class="position-relative">
                        <i class="far fa-bell text-white fs-5 cursor-pointer"></i>
                        <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
                    </div>
                    <div class="theme-toggle" onclick="toggleDarkMode()">
                        <i class="fas fa-moon text-white"></i>
                    </div>
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($user_name) ?>&background=6366f1&color=fff" class="rounded-circle" width="40">
                </div>
            </div>

            <!-- Summary Blocks (Reference Style) -->
            <div class="row g-4 mb-5">
                <div class="col-md-3">
                    <div class="summary-box">
                        <div class="icon-box bg-soft-blue"><i class="fas fa-calendar-check"></i></div>
                        <h3 class="fw-bold mb-1"><?= number_format($stats['total_events'] ?? 0) ?></h3>
                        <p class="text-muted small mb-0">Total Events</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="summary-box">
                        <div class="icon-box bg-soft-purple"><i class="fas fa-users"></i></div>
                        <h3 class="fw-bold mb-1"><?= number_format($stats['total_bookings'] ?? 0) ?></h3>
                        <p class="text-muted small mb-0">Total Bookings</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="summary-box">
                        <div class="icon-box bg-soft-orange"><i class="fas fa-wallet"></i></div>
                        <h3 class="fw-bold mb-1"><?= number_format($stats['total_revenue'] ?? 0) ?> <span class="small opacity-50">ETB</span></h3>
                        <p class="text-muted small mb-0">Total Revenue</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="summary-box">
                        <div class="icon-box bg-soft-green"><i class="fas fa-check-circle"></i></div>
                        <h3 class="fw-bold mb-1">Active</h3>
                        <p class="text-muted small mb-0">Account Status</p>
                    </div>
                </div>
            </div>

            <!-- Charts & Lists -->
            <div class="row g-4">
                <?php if (($role === 'admin' || $role === 'organizer') && !empty($chartData)): ?>
                <div class="col-lg-8">
                    <div class="summary-box">
                        <h5 class="fw-bold mb-4">Performance Overview</h5>
                        <canvas id="performanceChart" height="250"></canvas>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="col-lg-4">
                    <div class="summary-box">
                        <h5 class="fw-bold mb-4">Quick Actions</h5>
                        <div class="d-grid gap-3">
                            <a href="events.php" class="btn btn-primary py-3 rounded-4 fw-bold shadow-sm">
                                <i class="fas fa-plus-circle me-2"></i> Explore Events
                            </a>
                            <a href="profile.php" class="btn btn-outline-secondary py-3 rounded-4 fw-bold">
                                <i class="fas fa-user-edit me-2"></i> Edit Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        <?php if (!empty($chartData)): ?>
        const ctx = document.getElementById('performanceChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($chartData, 'title')) ?>,
                datasets: [
                    {
                        label: 'Bookings',
                        data: <?= json_encode(array_column($chartData, 'total_bookings')) ?>,
                        backgroundColor: 'rgba(99, 102, 241, 0.5)',
                        borderRadius: 8
                    },
                    {
                        label: 'Revenue (ETB)',
                        data: <?= json_encode(array_column($chartData, 'total_revenue')) ?>,
                        type: 'line',
                        borderColor: '#ec4899',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' } },
                    x: { grid: { display: false } }
                }
            }
        });
        <?php endif; ?>

        function toggleDarkMode() {
            const html = document.documentElement;
            const theme = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
        }
    </script>
</body>
</html>
