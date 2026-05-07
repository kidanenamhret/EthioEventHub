<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
requireLogin();

if (!isOrganizer() && !isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

require_once '../includes/db.php';
$pdo = getDB();

if (isAdmin()) {
    $stmt = $pdo->prepare("SELECT e.*, u.name as organizer_name FROM events e JOIN users u ON e.organizer_id = u.id ORDER BY e.event_date DESC");
    $stmt->execute();
} else {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE organizer_id = ? ORDER BY event_date DESC");
    $stmt->execute([$_SESSION['user_id']]);
}
$events = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events - EthioEvent Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #020617; }
        .glass-table-container {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 32px;
            overflow: hidden;
        }
        .table { color: white !important; margin-bottom: 0; }
        .table thead { background: rgba(255, 255, 255, 0.05); }
        .table th { border: none; padding: 25px; color: rgba(255,255,255,0.5); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; }
        .table td { border-bottom: 1px solid rgba(255, 255, 255, 0.05); padding: 25px; vertical-align: middle; }
        .table tr:last-child td { border-bottom: none; }
        .btn-action { width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; transition: all 0.3s; }
        .btn-edit { background: rgba(99, 102, 241, 0.1); color: #6366f1; }
        .btn-delete { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        .btn-action:hover { transform: scale(1.1); filter: brightness(1.2); }
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
        <div class="d-flex justify-content-between align-items-center mb-5 animate-fade-in">
            <div>
                <h1 class="display-5 fw-bold text-white mb-2">Event Architecture</h1>
                <p class="text-white-50">Manage your published experiences and track real-time sales.</p>
            </div>
            <div class="d-flex gap-3">
                <a href="export_events.php" class="btn btn-outline-light px-4 rounded-pill border-opacity-25"><i class="fas fa-download me-2"></i>Export</a>
                <a href="create_event.php" class="btn btn-primary px-4 rounded-pill shadow-lg"><i class="fas fa-plus me-2"></i>Create New</a>
            </div>
        </div>

        <?php if (count($events) > 0): ?>
            <div class="glass-table-container animate-fade-in">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Event Details</th>
                                <?php if (isAdmin()): ?><th>Organizer</th><?php endif; ?>
                                <th>Schedule</th>
                                <th>Performance</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($events as $e): ?>
                            <tr>
                                <td>
                                    <h6 class="fw-bold text-white mb-1"><?= htmlspecialchars($e['title']) ?></h6>
                                    <span class="badge bg-soft-primary text-primary small">ID: #<?= $e['id'] ?></span>
                                </td>
                                <?php if (isAdmin()): ?>
                                    <td><span class="text-white-50 small"><?= htmlspecialchars($e['organizer_name']) ?></span></td>
                                <?php endif; ?>
                                <td>
                                    <div class="text-white small fw-bold mb-1"><?= date('M d, Y', strtotime($e['event_date'])) ?></div>
                                    <div class="text-white-50 small"><?= htmlspecialchars($e['venue']) ?></div>
                                </td>
                                <td>
                                    <?php
                                    $bStmt = $pdo->prepare("SELECT COUNT(*) as total FROM bookings WHERE event_id = ?");
                                    $bStmt->execute([$e['id']]);
                                    $booked = $bStmt->fetch()['total'] ?? 0;
                                    $percent = ($e['capacity'] > 0) ? ($booked / $e['capacity']) * 100 : 0;
                                    ?>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="progress flex-grow-1" style="height: 6px; background: rgba(255,255,255,0.05);">
                                            <div class="progress-bar bg-accent" style="width: <?= $percent ?>%"></div>
                                        </div>
                                        <span class="text-white small fw-bold"><?= $booked ?> / <?= $e['capacity'] ?></span>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="edit_event.php?id=<?= $e['id'] ?>" class="btn-action btn-edit" title="Edit"><i class="fas fa-pen"></i></a>
                                        <a href="delete_event.php?id=<?= $e['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Archive this event?')" title="Delete"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="glass-table-container p-5 text-center animate-fade-in">
                <i class="fas fa-calendar-plus fa-4x text-accent opacity-25 mb-4"></i>
                <h3 class="text-white fw-bold">No events launched yet</h3>
                <p class="text-white-50 mb-5">Launch your first premium experience and start selling tickets today.</p>
                <a href="create_event.php" class="btn btn-primary btn-lg px-5 rounded-pill shadow-lg">CREATE FIRST EVENT</a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>