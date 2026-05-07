<?php
// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $error = 'Security validation failed. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $error = 'Please enter both email and password.';
        } else {
            require_once __DIR__ . '/../includes/rate_limit.php';
            require_once __DIR__ . '/../includes/activity_logger.php';

            if (!checkRateLimit($_SERVER['REMOTE_ADDR'], 5, 300)) {
                $error = 'Too many attempts. Please try again in 5 minutes.';
            } else {
                try {
                    $pdo = getDB();
                    $stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($user && password_verify($password, $user['password'])) {
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['role'] = $user['role'];
                        
                        logActivity('USER_LOGIN', "Successful login from {$_SERVER['REMOTE_ADDR']}");
                        header('Location: dashboard.php');
                        exit;
                    } else {
                        logActivity('LOGIN_FAILED', "Failed attempt for email: $email");
                        $error = 'Invalid email or password.';
                    }
                } catch (PDOException $e) {
                    $error = 'System error. Please try again later.';
                }
            }
        }
    }
}

// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EthioEvent Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: #020617;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .form-control {
            background: rgba(255, 255, 255, 0.05) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: white !important;
            border-radius: 12px;
            padding: 12px 15px;
        }
        .form-control:focus {
            background: rgba(255, 255, 255, 0.08) !important;
            border-color: #6366f1 !important;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.2);
        }
        .input-group-text {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.5);
            border-radius: 12px 0 0 12px;
        }
        .btn-primary {
            background: linear-gradient(45deg, #4f46e5, #9333ea);
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-weight: 700;
        }
        .toggle-password {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.5);
            border-radius: 0 12px 12px 0;
        }
        .back-home {
            position: fixed;
            top: 30px;
            left: 30px;
        }
    </style>
</head>
<body>
    <div class="bg-overlay"></div>
    <div class="hero-bg-image" style="opacity: 0.15;"></div>

    <div class="back-home">
        <a href="../index.php" class="btn btn-secondary btn-sm px-4">
            <i class="fas fa-arrow-left me-2"></i> Back to Home
        </a>
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="login-card p-4 p-md-5 animate-fade-in">
                    <div class="text-center mb-5">
                        <i class="fas fa-bolt fa-3x mb-3" style="color: #6366f1;"></i>
                        <h2 class="fw-bold text-white">EthioEvent Hub</h2>
                        <p class="text-white-50">Secure Login Portal</p>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger rounded-3" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-white-50 text-uppercase">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" name="email" class="form-control" placeholder="name@example.com" value="<?= htmlspecialchars($email) ?>" required autofocus>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-white-50 text-uppercase">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
                                <button type="button" class="btn toggle-password" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-4 d-flex justify-content-between align-items-center">
                            <div class="form-check">
                                <input type="checkbox" name="remember" class="form-check-input" id="rememberCheck">
                                <label class="form-check-label small text-white-50" for="rememberCheck">Remember Me</label>
                            </div>
                            <a href="forgot_password.php" class="text-accent small text-decoration-none fw-bold">Forgot?</a>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 shadow-lg">
                            SIGN IN
                        </button>
                    </form>
                    
                    <div class="mt-5 text-center">
                        <p class="mb-0 text-white-50 small">
                            New here? <a href="register.php" class="text-accent fw-bold text-decoration-none">Create Account</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });
        }
    </script>
</body>
</html>