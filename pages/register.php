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
$success = '';
$formData = [
    'name' => '',
    'email' => '',
    'role' => 'attendee',
    'phone' => ''
];

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Security validation failed. Please try again.';
    } else {
        // Get and sanitize form data
        $formData['name'] = trim($_POST['name'] ?? '');
        $formData['email'] = trim($_POST['email'] ?? '');
        $formData['role'] = $_POST['role'] ?? 'attendee';
        $formData['phone'] = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $terms = isset($_POST['terms']);
        
        $errors = [];
        if (empty($formData['name'])) $errors[] = 'Full name is required.';
        if (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
        if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
        if ($password !== $confirm_password) $errors[] = 'Passwords do not match.';
        if (!$terms) $errors[] = 'You must agree to the terms.';
        
        // Check if email exists
        if (empty($errors)) {
            try {
                $pdo = getDB();
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$formData['email']]);
                if ($stmt->fetch()) {
                    $errors[] = 'Email is already registered.';
                }
            } catch (PDOException $e) {
                $errors[] = 'Database error. Please try again.';
            }
        }
        
        if (empty($errors)) {
            try {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, phone) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$formData['name'], $formData['email'], $hashedPassword, $formData['role'], $formData['phone']]);
                
                // Send Welcome Email
                require_once __DIR__ . '/../includes/email_helper.php';
                sendWelcomeEmail($formData['email'], $formData['name']);

                $success = 'Success! <a href="login.php" class="text-white fw-bold">Click here to login</a>.';
                $formData = ['name' => '', 'email' => '', 'role' => 'attendee', 'phone' => ''];
            } catch (PDOException $e) {
                $error = 'Unable to create account.';
            }
        } else {
            $error = implode('<br>', $errors);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - EthioEvent Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #020617; min-height: 100vh; }
        .auth-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 32px;
            margin: 80px 0;
        }
        .form-control, .form-select {
            background: rgba(255, 255, 255, 0.05) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: white !important;
            border-radius: 12px;
            padding: 12px;
        }
        .input-group-text {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.5);
            border-radius: 12px 0 0 12px;
        }
        .password-strength {
            height: 4px;
            margin-top: 8px;
            border-radius: 10px;
            background: rgba(255,255,255,0.1);
        }
        .strength-weak { width: 33%; background: #ef4444; }
        .strength-medium { width: 66%; background: #f59e0b; }
        .strength-strong { width: 100%; background: #10b981; }
        .back-home { position: fixed; top: 30px; left: 30px; z-index: 100; }
    </style>
</head>
<body>
    <div class="bg-overlay"></div>
    <div class="hero-bg-image" style="opacity: 0.15;"></div>

    <div class="back-home">
        <a href="../index.php" class="btn btn-secondary btn-sm px-4"><i class="fas fa-arrow-left me-2"></i> Home</a>
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-6">
                <div class="auth-card p-4 p-md-5 animate-fade-in">
                    <div class="text-center mb-5">
                        <i class="fas fa-user-plus fa-3x mb-3 text-accent"></i>
                        <h2 class="fw-bold text-white">Join the Hub</h2>
                        <p class="text-white-50">Create your account to start experiencing events</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger"><?= $error ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success border-0 bg-success bg-opacity-10 text-success"><?= $success ?></div>
                    <?php endif; ?>

                    <form method="POST" id="regForm">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-white-50 text-uppercase">Full Name</label>
                            <input type="text" name="name" class="form-control" placeholder="Alemu Bekele" value="<?= htmlspecialchars($formData['name']) ?>" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-white-50 text-uppercase">Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="alemu@example.com" value="<?= htmlspecialchars($formData['email']) ?>" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label small fw-bold text-white-50 text-uppercase">Password</label>
                                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
                                <div id="strengthBar" class="password-strength"></div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label small fw-bold text-white-50 text-uppercase">Confirm</label>
                                <input type="password" name="confirm_password" id="confirm" class="form-control" placeholder="••••••••" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label small fw-bold text-white-50 text-uppercase">Role</label>
                                <select name="role" class="form-select">
                                    <option value="attendee">Attendee</option>
                                    <option value="organizer">Organizer</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label small fw-bold text-white-50 text-uppercase">Phone</label>
                                <input type="tel" name="phone" class="form-control" placeholder="0911223344" value="<?= htmlspecialchars($formData['phone']) ?>">
                            </div>
                        </div>

                        <div class="mb-4 form-check">
                            <input type="checkbox" name="terms" class="form-check-input" id="terms" required>
                            <label class="form-check-label small text-white-50" for="terms">I agree to the Terms & Privacy Policy</label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-3 shadow-lg">CREATE ACCOUNT</button>
                    </form>

                    <div class="mt-5 text-center">
                        <p class="mb-0 text-white-50 small">Already a member? <a href="login.php" class="text-accent fw-bold text-decoration-none">Sign In</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const pass = document.getElementById('password');
        const bar = document.getElementById('strengthBar');
        pass.addEventListener('input', () => {
            const val = pass.value;
            bar.className = 'password-strength';
            if (val.length > 0) {
                if (val.length < 6) bar.classList.add('strength-weak');
                else if (val.length < 10) bar.classList.add('strength-medium');
                else bar.classList.add('strength-strong');
            }
        });
    </script>
</body>
</html>