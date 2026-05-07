<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/lang.php';

$base_url = '../';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$error = '';
$success = '';

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

try {
    $pdo = getDB();
    
    // Fetch current user data
    $stmt = $pdo->prepare("SELECT name, email, phone, role, profile_pic FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Process profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Security validation failed.";
    } else {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        
        // Handle profile picture upload
        $profile_pic = $user['profile_pic'];
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['profile_pic']['tmp_name'];
            $file_name = $_FILES['profile_pic']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (in_array($file_ext, $allowed)) {
                $new_name = 'user_' . $user_id . '_' . time() . '.' . $file_ext;
                $upload_dir = __DIR__ . '/../assets/images/profiles/';
                
                if (move_uploaded_file($file_tmp, $upload_dir . $new_name)) {
                    // Delete old pic if exists
                    if ($profile_pic && file_exists($upload_dir . $profile_pic)) {
                        unlink($upload_dir . $profile_pic);
                    }
                    $profile_pic = $new_name;
                } else {
                    $error = "Failed to upload image.";
                }
            } else {
                $error = "Invalid file type. Allowed: JPG, PNG, WEBP.";
            }
        }

        if (empty($name)) {
            $error = "Name cannot be empty.";
        } else if (!$error) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, profile_pic = ? WHERE id = ?");
                $stmt->execute([$name, $phone, $profile_pic, $user_id]);
                $_SESSION['user_name'] = $name; // Update session name
                $success = "Profile updated successfully!";
                $user['name'] = $name;
                $user['phone'] = $phone;
                $user['profile_pic'] = $profile_pic;
            } catch (PDOException $e) {
                $error = "Failed to update profile.";
            }
        }
    }
}

// Process password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Security validation failed.";
    } else {
        $current_pass = $_POST['current_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';
        
        if (strlen($new_pass) < 8) {
            $error = "New password must be at least 8 characters.";
        } elseif ($new_pass !== $confirm_pass) {
            $error = "New passwords do not match.";
        } else {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $db_pass = $stmt->fetchColumn();
            
            if (password_verify($current_pass, $db_pass)) {
                $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed, $user_id]);
                $success = "Password changed successfully!";
            } else {
                $error = "Current password is incorrect.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - EthioEvent Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #020617; }
        .profile-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 32px;
        }
        .form-control {
            background: rgba(255, 255, 255, 0.05) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: white !important;
            border-radius: 12px;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="profile-card p-4 p-md-5">
                    <h2 class="text-white fw-bold mb-4"><i class="fas fa-id-card me-2 text-accent"></i> My Account</h2>

                    <?php if ($error): ?><div class="alert alert-danger bg-danger bg-opacity-10 border-0 text-danger"><?= $error ?></div><?php endif; ?>
                    <?php if ($success): ?><div class="alert alert-success bg-success bg-opacity-10 border-0 text-success"><?= $success ?></div><?php endif; ?>

                    <!-- Profile Picture Section -->
                    <div class="text-center mb-5">
                        <div class="position-relative d-inline-block">
                            <?php 
                                $pic_path = !empty($user['profile_pic']) ? '../assets/images/profiles/' . $user['profile_pic'] : 'https://ui-avatars.com/api/?name=' . urlencode($user['name']) . '&background=6366f1&color=fff&size=128';
                            ?>
                            <img src="<?= $pic_path ?>" class="rounded-circle border border-4 border-accent shadow-lg" style="width: 150px; height: 150px; object-fit: cover;">
                            <div class="position-absolute bottom-0 end-0">
                                <label for="profile_pic_input" class="btn btn-accent btn-sm rounded-circle p-2 shadow-lg" style="cursor: pointer;">
                                    <i class="fas fa-camera"></i>
                                </label>
                            </div>
                        </div>
                        <h3 class="text-white mt-3 fw-bold"><?= htmlspecialchars($user['name']) ?></h3>
                        <p class="text-white-50"><?= ucfirst($user['role']) ?></p>
                    </div>

                    <!-- Profile Info Form -->
                    <form method="POST" enctype="multipart/form-data" class="mb-5">
                        <input type="file" name="profile_pic" id="profile_pic_input" class="d-none" onchange="this.form.submit()">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="update_profile" value="1">
                        
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-white-50 text-uppercase">Full Name</label>
                            <input type="text" name="name" class="form-control py-3" value="<?= htmlspecialchars($user['name']) ?>" required>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-white-50 text-uppercase">Email (Cannot Change)</label>
                            <input type="email" class="form-control py-3" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-white-50 text-uppercase">Phone</label>
                            <input type="tel" name="phone" class="form-control py-3" value="<?= htmlspecialchars($user['phone']) ?>">
                        </div>
                        
                        <button type="submit" class="btn btn-primary px-5 py-3 rounded-pill fw-bold">Update Profile</button>
                    </form>

                    <hr class="border-white opacity-10 my-5">

                    <!-- Password Change Form -->
                    <h4 class="text-white fw-bold mb-4">Change Password</h4>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="change_password" value="1">
                        
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-white-50 text-uppercase">Current Password</label>
                            <input type="password" name="current_password" class="form-control py-3" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label small fw-bold text-white-50 text-uppercase">New Password</label>
                                <input type="password" name="new_password" class="form-control py-3" required>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label small fw-bold text-white-50 text-uppercase">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control py-3" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-outline-light px-5 py-3 rounded-pill fw-bold">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
