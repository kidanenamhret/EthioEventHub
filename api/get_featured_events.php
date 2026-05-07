<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$category_id = (int)($_GET['category_id'] ?? 0);

try {
    $pdo = getDB();
    
    $sql = "
        SELECT e.*, c.name as category
        FROM events e 
        JOIN categories c ON e.category_id = c.id 
        WHERE DATE(e.event_date) >= CURDATE()
    ";
    
    if ($category_id > 0) {
        $sql .= " AND e.category_id = :cat_id";
    }
    
    $sql .= " ORDER BY e.event_date ASC LIMIT 6";
    
    $stmt = $pdo->prepare($sql);
    if ($category_id > 0) {
        $stmt->bindValue(':cat_id', $category_id, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format for JS
    $output = [];
    foreach ($events as $e) {
        // Database priority for images
        $img = 'assets/images/hero.png';
        if (!empty($e['image']) && file_exists(__DIR__ . '/../assets/images/' . $e['image'])) {
            $img = 'assets/images/' . $e['image'];
        } else {
            $localMap = [
                'Music' => 'assets/images/jazz.jpg',
                'Tech' => 'assets/images/tech.jpg',
                'Cultural' => 'assets/images/irreecha.jpg'
            ];
            $img = $localMap[$e['category']] ?? 'assets/images/hero.png';
        }
        
        $output[] = [
            'id' => $e['id'],
            'title' => $e['title'],
            'venue' => $e['venue'],
            'price' => $e['price'],
            'category' => $e['category'],
            'image_path' => $img,
            'formatted_date' => date('M d, Y', strtotime($e['event_date']))
        ];
    }

    echo json_encode($output);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
