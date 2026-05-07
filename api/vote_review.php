<?php
require_once '../includes/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit;
}

$review_id = (int)($_POST['review_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if ($review_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid review']);
    exit;
}

try {
    $pdo = getDB();
    
    // Simple implementation: Increment helpful count
    // In a full implementation, you'd have a review_votes table to prevent double voting
    $stmt = $pdo->prepare("UPDATE reviews SET helpful_count = helpful_count + 1 WHERE id = ?");
    $stmt->execute([$review_id]);
    
    echo json_encode(['success' => true, 'message' => 'Vote recorded']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
