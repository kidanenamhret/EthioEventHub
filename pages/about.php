<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/lang.php';

$base_url = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - EthioEvent Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #020617; }
        .about-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 32px;
            padding: 50px;
        }
        .team-avatar {
            width: 80px; height: 80px;
            background: var(--accent);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 30px; color: white; margin: 0 auto 15px;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="about-card animate-fade-in">
                    <h1 class="display-4 fw-bold text-white mb-4">Our <span class="text-accent">Vision</span></h1>
                    <p class="lead text-white-50 mb-5">
                        EthioEvent Hub is Ethiopia's first centralized event ecosystem. Our mission is to bridge the gap between world-class event organizers and curious attendees through technology, security, and exceptional design.
                    </p>
                    
                    <div class="row g-4 mt-5">
                        <div class="col-md-4 text-center">
                            <i class="fas fa-shield-alt fa-3x text-accent mb-3"></i>
                            <h5 class="text-white fw-bold">Secure Booking</h5>
                            <p class="text-white-50 small">Verified tickets and encrypted transactions for your peace of mind.</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <i class="fas fa-rocket fa-3x text-accent mb-3"></i>
                            <h5 class="text-white fw-bold">Fast Discovery</h5>
                            <p class="text-white-50 small">Find the best cultural, musical, and tech events in seconds.</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <i class="fas fa-chart-pie fa-3x text-accent mb-3"></i>
                            <h5 class="text-white fw-bold">Organizer Tools</h5>
                            <p class="text-white-50 small">Advanced analytics and CSV reporting for professional management.</p>
                        </div>
                    </div>

                    <hr class="border-white opacity-10 my-5">

                    <h2 class="text-white fw-bold mb-5 text-center">The Project Team</h2>
                    <div class="row g-4 text-center">
                        <?php 
                        $team = ['Mesfin', 'Biruktawit', 'Yonas', 'Edget', 'Ebsitu'];
                        foreach($team as $member): 
                        ?>
                        <div class="col">
                            <div class="team-avatar"><?= substr($member, 0, 1) ?></div>
                            <h6 class="text-white fw-bold"><?= $member ?></h6>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
