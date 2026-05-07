<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Login required']);
    exit;
}

require_once '../includes/db.php';
$input = json_decode(file_get_contents('php://input'), true);
$event_id = (int)($input['event_id'] ?? 0);
$user_id = (int)$_SESSION['user_id'];

if (!$event_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid event']);
    exit;
}

try {
    $pdo = getDB();
    
    // Check if already in wishlist
    $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND event_id = ?");
    $stmt->execute([$user_id, $event_id]);
    $exists = $stmt->fetch();
    
    if ($exists) {
        // Remove from wishlist
        $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND event_id = ?");
        $stmt->execute([$user_id, $event_id]);
        echo json_encode(['success' => true, 'action' => 'removed']);
    } else {
        // Add to wishlist
        $stmt = $pdo->prepare("INSERT INTO wishlist (user_id, event_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $event_id]);
        echo json_encode(['success' => true, 'action' => 'added']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
