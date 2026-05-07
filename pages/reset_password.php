<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';
$validToken = false;

if (empty($token)) {
    header('Location: login.php');
    exit;
}

try {
    $pdo = getDB();
    
    // Check if token is valid and not expired
    $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND token_expiry > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $validToken = true;
    } else {
        $error = "This reset link is invalid or has expired.";
    }
} catch (PDOException $e) {
    $error = "An error occurred. Please try again later.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        try {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, token_expiry = NULL WHERE id = ?");
            $stmt->execute([$hashed, $user['id']]);
            
            $success = "Password reset successfully! You can now login.";
            header("refresh:2;url=login.php");
        } catch (PDOException $e) {
            $error = "Failed to reset password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - EthioEvent Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #020617; }
        .auth-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 32px;
            margin-top: 100px;
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
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="auth-card p-5 animate-fade-in">
                    <div class="text-center mb-5">
                        <i class="fas fa-lock-open fa-3x text-accent mb-4"></i>
                        <h2 class="text-white fw-bold">New Password</h2>
                        <p class="text-white-50">Choose a strong password for your account.</p>
                    </div>

                    <?php if ($error): ?><div class="alert alert-danger bg-danger bg-opacity-10 border-0 text-danger"><?= $error ?></div><?php endif; ?>
                    <?php if ($success): ?><div class="alert alert-success bg-success bg-opacity-10 border-0 text-success"><?= $success ?></div><?php endif; ?>

                    <?php if ($validToken && !$success): ?>
                    <form method="POST">
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-white-50 text-uppercase">New Password</label>
                            <input type="password" name="password" class="form-control py-3" placeholder="Min 8 characters" required>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-white-50 text-uppercase">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control py-3" placeholder="Repeat password" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 py-3 rounded-pill fw-bold mb-4">RESET PASSWORD</button>
                    </form>
                    <?php else: ?>
                        <div class="text-center">
                            <a href="forgot_password.php" class="btn btn-outline-light rounded-pill px-4">Request New Link</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
