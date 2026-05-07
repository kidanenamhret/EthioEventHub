<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$role = $_SESSION['role'] ?? '';
$event_id = (int)($_GET['id'] ?? 0);

if (!$event_id) {
    header('Location: organizer_dashboard.php');
    exit;
}

$error = '';
$success = '';

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

try {
    $pdo = getDB();
    
    // Fetch event and verify ownership
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event || ($event['organizer_id'] != $user_id && $role !== 'admin')) {
        header('Location: organizer_dashboard.php');
        exit;
    }

    // Fetch categories
    $stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Security validation failed. Please try again.";
    } else {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $venue = trim($_POST['venue'] ?? '');
        $event_date = $_POST['event_date'] ?? '';
        $event_time = $_POST['event_time'] ?? '09:00';
        $price = (float)($_POST['price'] ?? 0);
        $capacity = (int)($_POST['capacity'] ?? 0);
        
        $errors = [];
        if (empty($title)) $errors[] = "Event title is required.";
        if (empty($description)) $errors[] = "Event description is required.";
        if ($category_id <= 0) $errors[] = "Please select a category.";
        if (empty($venue)) $errors[] = "Venue is required.";
        if (empty($event_date)) $errors[] = "Event date is required.";
        
        $fullDateTime = $event_date . ' ' . $event_time . ':00';

        // Handle Image Upload
        $image_name = $event['image']; // Default to existing image
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $file_name = $_FILES['image']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            if (in_array($file_ext, $allowed)) {
                $upload_dir = '../assets/images/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                $image_name = time() . '_' . uniqid() . '.' . $file_ext;
                move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image_name);
            } else {
                $errors[] = "Invalid image format. Allowed: JPG, PNG, WEBP.";
            }
        }
        
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE events 
                    SET title = ?, description = ?, category_id = ?, venue = ?, event_date = ?, price = ?, capacity = ?, image = ?
                    WHERE id = ?
                ");
                $stmt->execute([$title, $description, $category_id, $venue, $fullDateTime, $price, $capacity, $image_name, $event_id]);
                
                $success = "Event updated successfully! Redirecting...";
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                header("refresh:2;url=organizer_dashboard.php");
            } catch (PDOException $e) {
                $error = "Failed to update event. Please try again.";
            }
        } else {
            $error = implode("<br>", $errors);
        }
    }
}

// Split date and time for the form
$date_parts = explode(' ', $event['event_date']);
$current_date = $date_parts[0];
$current_time = isset($date_parts[1]) ? substr($date_parts[1], 0, 5) : '09:00';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event - EthioEvent Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #020617; }
        .studio-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 32px;
        }
        .form-control, .form-select {
            background: rgba(255, 255, 255, 0.05) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: white !important;
            border-radius: 12px;
        }
        .preview-box {
            width: 100%;
            height: 250px;
            background: rgba(255,255,255,0.02);
            border: 2px dashed rgba(255,255,255,0.1);
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center; overflow: hidden;
        }
        .preview-box img { width: 100%; height: 100%; object-fit: cover; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../index.php">
                <i class="fas fa-bolt me-2 text-accent"></i> ETHIO EVENT HUB
            </a>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="studio-card p-4 p-md-5">
                    <header class="mb-5">
                        <h1 class="display-5 fw-bold text-white mb-2">Edit <span class="text-accent">Experience</span></h1>
                        <p class="text-white-50">Update the details of your event.</p>
                    </header>

                    <?php if ($error): ?><div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger mb-4"><?= $error ?></div><?php endif; ?>
                    <?php if ($success): ?><div class="alert alert-success border-0 bg-success bg-opacity-10 text-success mb-4"><?= $success ?></div><?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        
                        <div class="row g-4">
                            <div class="col-md-8">
                                <div class="mb-4">
                                    <label class="form-label small fw-bold text-white-50 text-uppercase">Event Title</label>
                                    <input type="text" name="title" class="form-control py-3" value="<?= htmlspecialchars($event['title']) ?>" required>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-white-50 text-uppercase">Category</label>
                                        <select name="category_id" class="form-select py-3" required>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?= $cat['id'] ?>" <?= $event['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-white-50 text-uppercase">Venue</label>
                                        <input type="text" name="venue" class="form-control py-3" value="<?= htmlspecialchars($event['venue']) ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-white-50 text-uppercase">Event Poster</label>
                                <div class="preview-box mb-2" id="imagePreview">
                                    <?php if ($event['image']): ?>
                                        <img src="../assets/images/<?= $event['image'] ?>" alt="Current Poster">
                                    <?php else: ?>
                                        <i class="fas fa-image fa-3x text-white-50"></i>
                                    <?php endif; ?>
                                </div>
                                <input type="file" name="image" id="imageInput" class="form-control form-control-sm" accept="image/*">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-white-50 text-uppercase">Description</label>
                                <textarea name="description" class="form-control py-3" rows="5" required><?= htmlspecialchars($event['description']) ?></textarea>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-white-50 text-uppercase">Date</label>
                                <input type="date" name="event_date" class="form-control py-3" value="<?= $current_date ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-white-50 text-uppercase">Time</label>
                                <input type="time" name="event_time" class="form-control py-3" value="<?= $current_time ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-white-50 text-uppercase">Capacity</label>
                                <input type="number" name="capacity" class="form-control py-3" value="<?= $event['capacity'] ?>" required>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-white-50 text-uppercase">Ticket Price (ETB)</label>
                                <input type="number" name="price" class="form-control py-3" value="<?= $event['price'] ?>" step="0.01">
                            </div>

                            <div class="col-md-12 mt-5">
                                <button type="submit" class="btn btn-primary btn-lg w-100 py-3 shadow-lg fw-bold">UPDATE EXPERIENCE</button>
                                <a href="organizer_dashboard.php" class="btn btn-link w-100 text-white-50 mt-3 text-decoration-none">Discard Changes</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('imageInput').addEventListener('change', function(e) {
            const preview = document.getElementById('imagePreview');
            const file = e.target.files[0];
            const reader = new FileReader();
            reader.onload = function(event) { preview.innerHTML = `<img src="${event.target.result}">`; }
            if (file) reader.readAsDataURL(file);
        });
    </script>
</body>
</html>
