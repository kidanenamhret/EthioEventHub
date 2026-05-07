<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = "Please enter your email address.";
    } else {
        try {
            $pdo = getDB();
            $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Generate token
                $token = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, token_expiry = ? WHERE id = ?");
                $stmt->execute([$token, $expiry, $user['id']]);
                
                // Send simulated email
                $resetLink = SITE_URL . "pages/reset_password.php?token=" . $token;
                $message = "Hello " . $user['name'] . ",\n\nYou requested a password reset. Click the link below to set a new password:\n$resetLink\n\nThis link expires in 1 hour.";
                
                sendSimulatedEmail($email, "Password Reset - EthioEvent Hub", $message);
                
                $success = "Recovery instructions have been sent to our <strong>Email Sandbox</strong>.";
                if ($_SERVER['SERVER_NAME'] === 'localhost') {
                    $success .= "<br><br><div class='mt-3 p-3 bg-white bg-opacity-5 rounded-3 border border-white border-opacity-10 small'>
                        <span class='text-accent fw-bold'><i class='fas fa-flask me-2'></i>DEV PREVIEW:</span><br>
                        <a href='$resetLink' class='text-white text-break'>$resetLink</a>
                    </div>";
                }
            } else {
                // For security, show the same message even if email doesn't exist
                $success = "Recovery instructions have been sent to our <strong>Email Sandbox</strong>.";
            }
        } catch (PDOException $e) {
            $error = "An error occurred. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - EthioEvent Hub</title>
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
                        <i class="fas fa-key fa-3x text-accent mb-4"></i>
                        <h2 class="text-white fw-bold">Recover Access</h2>
                        <p class="text-white-50">Enter your email to receive a reset link.</p>
                    </div>

                    <?php if ($error): ?><div class="alert alert-danger bg-danger bg-opacity-10 border-0 text-danger"><?= $error ?></div><?php endif; ?>
                    <?php if ($success): ?><div class="alert alert-success bg-success bg-opacity-10 border-0 text-success"><?= $success ?></div><?php endif; ?>

                    <form method="POST">
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-white-50 text-uppercase">Email Address</label>
                            <input type="email" name="email" class="form-control py-3" placeholder="your@email.com" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 py-3 rounded-pill fw-bold mb-4">SEND RESET LINK</button>
                        
                        <div class="text-center">
                            <a href="login.php" class="text-white-50 small text-decoration-none"><i class="fas fa-arrow-left me-2"></i> Back to Login</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
