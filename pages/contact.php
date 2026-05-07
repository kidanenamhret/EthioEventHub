<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/lang.php';

$base_url = '../';

$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = h($_POST['name']);
    $email = h($_POST['email']);
    $msg = h($_POST['message']);
    
    // Simulate sending message to admin
    sendSimulatedEmail("admin@ethioeventhub.com", "New Contact Inquiry from $name", "Message: $msg\nReply-To: $email");
    $success = "Message sent! We will get back to you soon.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - EthioEvent Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #020617; }
        .contact-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 32px;
            padding: 50px;
        }
        .form-control {
            background: rgba(255, 255, 255, 0.05) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: white !important;
            border-radius: 12px;
        }
        .contact-icon {
            width: 50px; height: 50px;
            background: var(--accent-soft);
            color: var(--accent);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="row g-5">
            <div class="col-lg-5">
                <h1 class="display-5 fw-bold text-white mb-4">Get in <span class="text-accent">Touch</span></h1>
                <p class="text-white-50 mb-5">Have questions about an event or need technical support? We're here to help.</p>
                
                <div class="d-flex align-items-center mb-4 text-white">
                    <div class="contact-icon me-3"><i class="fas fa-envelope"></i></div>
                    <div>
                        <div class="small text-white-50">Email Support</div>
                        <div class="fw-bold">hello@ethioeventhub.com</div>
                    </div>
                </div>
                
                <div class="d-flex align-items-center mb-4 text-white">
                    <div class="contact-icon me-3"><i class="fas fa-phone"></i></div>
                    <div>
                        <div class="small text-white-50">Phone</div>
                        <div class="fw-bold">+251 911 22 33 44</div>
                    </div>
                </div>
                
                <div class="d-flex align-items-center mb-5 text-white">
                    <div class="contact-icon me-3"><i class="fas fa-map-marker-alt"></i></div>
                    <div>
                        <div class="small text-white-50">Office</div>
                        <div class="fw-bold">Bole, Addis Ababa, Ethiopia</div>
                    </div>
                </div>

                <div class="d-flex gap-3">
                    <a href="#" class="btn btn-outline-light rounded-circle" style="width:45px; height:45px; display:flex; align-items:center; justify-content:center;"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="btn btn-outline-light rounded-circle" style="width:45px; height:45px; display:flex; align-items:center; justify-content:center;"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="btn btn-outline-light rounded-circle" style="width:45px; height:45px; display:flex; align-items:center; justify-content:center;"><i class="fab fa-telegram-plane"></i></a>
                </div>
            </div>
            
            <div class="col-lg-7">
                <div class="contact-card">
                    <?php if ($success): ?>
                        <div class="alert alert-success bg-success bg-opacity-10 border-0 text-success p-4 rounded-4 mb-4 animate-fade-in">
                            <i class="fas fa-check-circle me-2"></i> <?= $success ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-white-50 text-uppercase">Full Name</label>
                                <input type="text" name="name" class="form-control py-3" placeholder="Abebe Bikila" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-white-50 text-uppercase">Email</label>
                                <input type="email" name="email" class="form-control py-3" placeholder="abebe@example.com" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-white-50 text-uppercase">Subject</label>
                                <select class="form-select bg-dark border-0 text-white py-3 rounded-4" style="background: rgba(255,255,255,0.05) !important; color:white !important; border: 1px solid rgba(255,255,255,0.1) !important;">
                                    <option>General Inquiry</option>
                                    <option>Technical Support</option>
                                    <option>Organizer Partnership</option>
                                    <option>Ticket Issue</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-white-50 text-uppercase">Message</label>
                                <textarea name="message" class="form-control py-3" rows="5" placeholder="How can we help you?" required></textarea>
                            </div>
                            <div class="col-md-12 mt-4">
                                <button type="submit" class="btn btn-primary btn-lg w-100 py-3 rounded-pill fw-bold shadow-lg">SEND MESSAGE</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
