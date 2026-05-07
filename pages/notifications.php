<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/lang.php';

$base_url = '../';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$notifications = [];

try {
    $pdo = getDB();
    
    // Mark all as read when visiting the page
    $updateStmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $updateStmt->execute([$user_id]);

    // Fetch all notifications
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Notifications page error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Notifications - EthioEvent Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #020617; }
        .notification-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 25px;
            transition: all 0.3s;
            margin-bottom: 15px;
        }
        .notification-card.unread {
            border-left: 4px solid #6366f1;
            background: rgba(99, 102, 241, 0.05);
        }
    </style>
</head>
<body>
    <div class="bg-overlay"></div>
    <div class="hero-bg-image" style="opacity: 0.1;"></div>

    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container py-5 mt-4">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h1 class="display-5 fw-bold text-white mb-2">Notifications</h1>
                <p class="text-white-50">Stay updated with your latest activities</p>
            </div>
        </div>

        <?php if (empty($notifications)): ?>
            <div class="text-center py-5">
                <i class="fas fa-bell-slash fa-4x text-white-50 mb-4 opacity-25"></i>
                <h4 class="text-white">No notifications yet</h4>
                <p class="text-white-50">We'll let you know when something important happens.</p>
                <a href="events.php" class="btn btn-primary rounded-pill px-4 mt-3">Explore Events</a>
            </div>
        <?php else: ?>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <?php foreach ($notifications as $n): ?>
                        <div class="notification-card <?= !$n['is_read'] ? 'unread' : '' ?> animate-fade-in">
                            <div class="d-flex align-items-start">
                                <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-4">
                                    <i class="fas fa-info-circle text-accent"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="text-white fw-bold mb-0">System Message</h6>
                                        <small class="text-white-50"><?= date('M d, g:i A', strtotime($n['created_at'])) ?></small>
                                    </div>
                                    <p class="text-white-50 mb-0"><?= h($n['message']) ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
