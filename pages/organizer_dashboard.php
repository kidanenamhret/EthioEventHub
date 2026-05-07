<?php
// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Check if user is logged in and is organizer/admin
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['organizer', 'admin'])) {
    header('Location: dashboard.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$user_name = htmlspecialchars($_SESSION['user_name'] ?? 'Organizer');
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? 'all';

try {
    $pdo = getDB();
    
    // Build events query with advanced stats
    $query = "
        SELECT e.*, 
               (SELECT COUNT(*) FROM bookings WHERE event_id = e.id AND status = 'confirmed') as total_bookings,
               (SELECT COALESCE(SUM(quantity), 0) FROM bookings WHERE event_id = e.id AND status = 'confirmed') as tickets_sold
        FROM events e
    ";
    $params = [];
    
    if ($role === 'organizer') {
        $query .= " WHERE e.organizer_id = ?";
        $params[] = $user_id;
    } else {
        $query .= " WHERE 1=1";
    }
    
    if (!empty($search)) {
        $query .= " AND (e.title LIKE ? OR e.venue LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if ($statusFilter === 'upcoming') {
        $query .= " AND e.event_date >= CURDATE()";
    } elseif ($statusFilter === 'past') {
        $query .= " AND e.event_date < CURDATE()";
    }
    
    $query .= " ORDER BY e.event_date ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate dashboard statistics
    $totalEvents = count($events);
    $totalSold = array_sum(array_column($events, 'tickets_sold'));
    $totalRevenue = array_sum(array_map(function($e) {
        return $e['tickets_sold'] * $e['price'];
    }, $events));
    $upcomingEvents = count(array_filter($events, function($e) {
        return strtotime($e['event_date']) > time();
    }));
    
} catch (PDOException $e) {
    error_log("Organizer dashboard error: " . $e->getMessage());
    $events = [];
    $error = "Unable to load your events.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organizer Command Center - EthioEvent Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #020617; }
        .stat-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s;
        }
        .stat-card:hover { border-color: #6366f1; transform: translateY(-5px); }
        .event-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 28px;
            overflow: hidden;
            margin-bottom: 25px;
            transition: all 0.3s;
        }
        .event-card:hover { transform: translateX(10px); border-color: #6366f1; }
        .capacity-track {
            height: 6px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            overflow: hidden;
        }
        .capacity-bar {
            height: 100%;
            background: linear-gradient(90deg, #6366f1, #9333ea);
            border-radius: 10px;
        }
        .action-side {
            background: rgba(255, 255, 255, 0.02);
            border-left: 2px dashed rgba(255, 255, 255, 0.1);
            padding: 30px;
            display: flex;
            flex-direction: column;
            justify-content: center;
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
                    <li class="nav-item ms-lg-3"><a class="btn btn-primary btn-sm px-4 rounded-pill" href="create_event.php"><i class="fas fa-plus me-2"></i>CREATE EVENT</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <header class="mb-5 d-flex justify-content-between align-items-center flex-wrap gap-4">
            <div>
                <h1 class="display-5 fw-bold text-white mb-2">Organizer <span class="text-accent">Studio</span></h1>
                <p class="text-white-50">Manage your published experiences and monitor ticket performance.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="?status=all" class="btn <?= $statusFilter === 'all' ? 'btn-primary' : 'btn-outline-light border-opacity-25' ?> rounded-pill px-4">All</a>
                <a href="?status=upcoming" class="btn <?= $statusFilter === 'upcoming' ? 'btn-primary' : 'btn-outline-light border-opacity-25' ?> rounded-pill px-4">Upcoming</a>
            </div>
        </header>

        <!-- Global Stats -->
        <div class="row g-4 mb-5 animate-fade-in">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="text-white-50 small fw-bold text-uppercase mb-2">Total Events</div>
                    <div class="h2 text-white fw-bold mb-0"><?= $totalEvents ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="text-white-50 small fw-bold text-uppercase mb-2">Tickets Sold</div>
                    <div class="h2 text-white fw-bold mb-0"><?= number_format($totalSold) ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="text-white-50 small fw-bold text-uppercase mb-2">Est. Revenue</div>
                    <div class="h2 text-accent fw-bold mb-0">ETB <?= number_format($totalRevenue, 0) ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="text-white-50 small fw-bold text-uppercase mb-2">Upcoming</div>
                    <div class="h2 text-white fw-bold mb-0"><?= $upcomingEvents ?></div>
                </div>
            </div>
        </div>

        <!-- Search Bar -->
        <form method="GET" class="mb-5 animate-fade-in">
            <div class="input-group">
                <span class="input-group-text bg-white bg-opacity-5 border-white border-opacity-10 text-white-50"><i class="fas fa-search"></i></span>
                <input type="text" name="search" class="form-control bg-white bg-opacity-5 border-white border-opacity-10 text-white py-3" placeholder="Search your events by title or venue..." value="<?= htmlspecialchars($search) ?>">
                <input type="hidden" name="status" value="<?= $statusFilter ?>">
                <button type="submit" class="btn btn-primary px-4">SEARCH</button>
            </div>
        </form>

        <!-- Events List -->
        <?php if (empty($events)): ?>
            <div class="stat-card p-5 text-center">
                <i class="fas fa-calendar-times fa-4x text-accent opacity-25 mb-4"></i>
                <h3 class="text-white fw-bold">No events matching your filters</h3>
                <p class="text-white-50 mb-4">Launch a new experience to start selling tickets.</p>
                <a href="create_event.php" class="btn btn-primary px-5 rounded-pill shadow-lg">LAUNCH NEW EVENT</a>
            </div>
        <?php else: foreach ($events as $event): 
            $soldPercentage = $event['capacity'] > 0 ? ($event['tickets_sold'] / $event['capacity']) * 100 : 0;
            $isUpcoming = strtotime($event['event_date']) > time();
        ?>
            <div class="event-card animate-fade-in">
                <div class="row g-0">
                    <div class="col-md-8 p-4 p-md-5">
                        <div class="d-flex justify-content-between align-items-start mb-4">
                            <div>
                                <h3 class="text-white fw-bold mb-1"><?= htmlspecialchars($event['title']) ?></h3>
                                <p class="text-white-50 small mb-0"><i class="fas fa-map-marker-alt me-2 text-accent"></i> <?= htmlspecialchars($event['venue']) ?></p>
                            </div>
                            <span class="badge <?= $isUpcoming ? 'bg-success' : 'bg-secondary' ?> bg-opacity-10 text-<?= $isUpcoming ? 'success' : 'secondary' ?> px-3 py-2 rounded-pill"><?= $isUpcoming ? 'UPCOMING' : 'PAST' ?></span>
                        </div>
                        <div class="row g-4 mb-4">
                            <div class="col-6 col-md-4">
                                <div class="text-white-50 small fw-bold text-uppercase">Date</div>
                                <div class="text-white fw-bold"><?= date('M d, Y', strtotime($event['event_date'])) ?></div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="text-white-50 small fw-bold text-uppercase">Unit Price</div>
                                <div class="text-white fw-bold">ETB <?= number_format($event['price'], 0) ?></div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="text-white-50 small fw-bold text-uppercase">Total Sales</div>
                                <div class="text-accent fw-bold">ETB <?= number_format($event['tickets_sold'] * $event['price'], 0) ?></div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="d-flex justify-content-between text-white-50 small mb-2">
                                <span>Capacity: <?= $event['tickets_sold'] ?> / <?= $event['capacity'] ?> sold</span>
                                <span class="fw-bold text-white"><?= round($soldPercentage) ?>%</span>
                            </div>
                            <div class="capacity-track">
                                <div class="capacity-bar" style="width: <?= $soldPercentage ?>%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 action-side">
                        <div class="d-grid gap-3">
                            <a href="event_details.php?id=<?= $event['id'] ?>" class="btn btn-outline-light py-3 rounded-pill border-opacity-10">VIEW LIVE</a>
                            <a href="edit_event.php?id=<?= $event['id'] ?>" class="btn btn-outline-primary py-3 rounded-pill">EDIT EVENT</a>
                            <a href="export_attendees.php?event_id=<?= $event['id'] ?>" class="btn btn-outline-success py-3 rounded-pill border-opacity-10">EXPORT ATTENDEES</a>
                            <?php if ($isUpcoming): ?>
                                <button onclick="confirmDelete(<?= $event['id'] ?>, '<?= htmlspecialchars($event['title']) ?>')" class="btn btn-outline-danger py-3 rounded-pill border-opacity-10">DELETE EVENT</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark border-secondary rounded-4">
                <div class="modal-body p-5 text-center">
                    <i class="fas fa-trash-alt fa-3x text-danger mb-4"></i>
                    <h3 class="text-white fw-bold mb-3">Delete Event?</h3>
                    <p class="text-white-50 mb-4">Are you sure you want to delete <strong class="text-white" id="deleteEventName"></strong>? This will cancel all existing bookings and cannot be undone.</p>
                    <div class="d-grid gap-3">
                        <button type="button" id="confirmDeleteBtn" class="btn btn-danger py-3 rounded-pill fw-bold">YES, DELETE PERMANENTLY</button>
                        <button type="button" class="btn btn-outline-light py-3 rounded-pill border-opacity-25" data-bs-dismiss="modal">CANCEL</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedId = null;
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

        function confirmDelete(id, name) {
            selectedId = id;
            document.getElementById('deleteEventName').textContent = name;
            deleteModal.show();
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', () => {
            if (selectedId) window.location.href = `delete_event.php?id=${selectedId}`;
        });
    </script>
</body>
</html>
