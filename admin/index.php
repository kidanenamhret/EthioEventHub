<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Check if user is logged in AND is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../pages/login.php');
    exit;
}

$pdo = getDB();
$error = '';

try {
    // Basic Admin Stats
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $eventCount = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
    $bookingCount = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
    
    // Fetch Recent Users
    $recentUsers = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - EthioEvent Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #0f172a; color: white; }
        .admin-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 30px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark mb-5 p-3 shadow-lg">
        <div class="container">
            <span class="navbar-brand fw-bold"><i class="fas fa-shield-alt text-accent me-2"></i> ADMIN CONTROL CENTER</span>
            <a href="../pages/logout.php" class="btn btn-outline-danger btn-sm rounded-pill">System Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="admin-card text-center">
                    <h1 class="display-4 fw-bold text-accent"><?= $userCount ?></h1>
                    <p class="text-white-50 mb-0 text-uppercase small fw-bold">Total Users</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="admin-card text-center">
                    <h1 class="display-4 fw-bold text-success"><?= $eventCount ?></h1>
                    <p class="text-white-50 mb-0 text-uppercase small fw-bold">Total Events</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="admin-card text-center">
                    <h1 class="display-4 fw-bold text-info"><?= $bookingCount ?></h1>
                    <p class="text-white-50 mb-0 text-uppercase small fw-bold">Total Bookings</p>
                </div>
            </div>
        </div>

        <div class="admin-card">
            <h4 class="fw-bold mb-4">Recent User Registrations</h4>
            <div class="table-responsive">
                <table class="table table-dark table-hover border-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recentUsers as $u): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['name']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><span class="badge bg-secondary"><?= $u['role'] ?></span></td>
                            <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
