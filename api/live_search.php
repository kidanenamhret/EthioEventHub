<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

$pdo = getDB();
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$userId = $_SESSION['user_id'] ?? 0;
$searchTerm = "%$search%";

$sql = "SELECT e.id, e.title, e.event_date, e.venue, e.price, e.image, c.name as category_name,
               (SELECT COUNT(*) FROM wishlist WHERE user_id = :userId AND event_id = e.id) as is_wishlisted
        FROM events e
        JOIN categories c ON e.category_id = c.id
        WHERE (e.title LIKE :search OR e.venue LIKE :search)";
$params = [':userId' => $userId, ':search' => $searchTerm];

if ($category > 0) {
    $sql .= " AND e.category_id = :category";
    $params[':category'] = $category;
}
$sql .= " ORDER BY e.event_date LIMIT 20";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

echo json_encode($events);
?>