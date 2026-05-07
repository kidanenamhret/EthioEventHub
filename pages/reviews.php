<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/db.php';

$event_id = $_GET['event_id'] ?? 0;
$pdo = getDB();

$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) die("Event not found");

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);
    
    try {
        $insert = $pdo->prepare("INSERT INTO reviews (user_id, event_id, rating, comment) VALUES (?, ?, ?, ?)");
        $insert->execute([$_SESSION['user_id'], $event_id, $rating, $comment]);
        $message = '<div class="alert alert-success">Review submitted successfully!</div>';
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">You have already reviewed this event.</div>';
    }
}

$stmt = $pdo->prepare("SELECT r.*, u.name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.event_id = ? ORDER BY r.created_at DESC");
$stmt->execute([$event_id]);
$reviews = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews - <?= htmlspecialchars($event['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="../index.php">EthioEvent Hub</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="event_details.php?id=<?= $event_id ?>">Back to Event</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="mb-5 animate-fade-in">
                    <h1 class="fw-bold display-6 mb-2">Community Reviews</h1>
                    <p class="text-muted">What people are saying about <span class="fw-bold text-dark"><?= htmlspecialchars($event['title']) ?></span></p>
                </div>

                <?= $message ?>

                <?php if (isset($_SESSION['user_id'])): ?>
                <div class="card p-4 mb-5 shadow-sm border-0 animate-fade-in">
                    <h5 class="fw-bold mb-4">Share Your Experience</h5>
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Rating</label>
                                <select name="rating" class="form-select" required>
                                    <option value="5">5 ★★★★★ (Excellent)</option>
                                    <option value="4">4 ★★★★☆ (Good)</option>
                                    <option value="3">3 ★★★☆☆ (Average)</option>
                                    <option value="2">2 ★★☆☆☆ (Poor)</option>
                                    <option value="1">1 ★☆☆☆☆ (Terrible)</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold small">Your Comment</label>
                                <textarea name="comment" class="form-control" rows="3" placeholder="Tell others about your experience..." required></textarea>
                            </div>
                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-primary px-4 py-2">Post Review</button>
                            </div>
                        </div>
                    </form>
                </div>
                <?php else: ?>
                    <div class="alert bg-soft-primary text-primary border-0 p-4 mb-5">
                        <i class="fas fa-info-circle me-2"></i> <a href="login.php" class="fw-bold text-primary">Login</a> to share your review with the community.
                    </div>
                <?php endif; ?>

                <div class="reviews-list">
                    <h4 class="fw-bold mb-4">Past Reviews (<?= count($reviews) ?>)</h4>
                    <?php if (count($reviews) > 0): ?>
                        <?php foreach ($reviews as $r): ?>
                            <div class="card p-4 mb-3 border-0 shadow-sm animate-fade-in">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold mb-0"><?= htmlspecialchars($r['name']) ?></h6>
                                    <div class="text-warning fw-bold">
                                        <?php for($i=1; $i<=5; $i++) echo $i <= $r['rating'] ? '★' : '☆'; ?>
                                    </div>
                                </div>
                                <p class="text-muted mb-2"><?= htmlspecialchars($r['comment']) ?></p>
                                <small class="text-uppercase fw-bold text-muted" style="font-size: 0.7rem;">
                                    <i class="far fa-clock me-1"></i> <?= date('M d, Y', strtotime($r['created_at'])) ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-5 opacity-50">
                            <i class="fas fa-comments fa-3x mb-3"></i>
                            <p>No reviews yet. Be the first to share!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
