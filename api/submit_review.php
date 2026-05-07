<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Auth check
if (!isset($_SESSION['user_id'])) {
    header('Location: ../pages/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int)$_SESSION['user_id'];
    $eventId = (int)($_POST['event_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 5);
    $comment = trim($_POST['comment'] ?? '');

    if (!$eventId || !$comment) {
        header("Location: ../pages/event_details.php?id=$eventId&error=invalid");
        exit;
    }

    try {
        $pdo = getDB();
        
        // Verify booking existence before allowing review
        $stmt = $pdo->prepare("SELECT id FROM bookings WHERE user_id = ? AND event_id = ? AND status = 'confirmed'");
        $stmt->execute([$userId, $eventId]);
        if (!$stmt->fetch()) {
            die("Unauthorized: You must book this event to leave a review.");
        }

        // Insert review
        $stmt = $pdo->prepare("
            INSERT INTO reviews (user_id, event_id, rating, comment, created_at)
            VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment), created_at = NOW()
        ");
        $stmt->execute([$userId, $eventId, $rating, $comment]);

        header("Location: ../pages/event_details.php?id=$eventId&success=reviewed");
        exit;

    } catch (PDOException $e) {
        error_log("Review submission error: " . $e->getMessage());
        header("Location: ../pages/event_details.php?id=$eventId&error=db");
        exit;
    }
} else {
    header('Location: ../index.php');
    exit;
}
?>
