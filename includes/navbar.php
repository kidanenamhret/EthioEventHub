<?php
if (!isset($base_url)) {
    // Determine the relative path to the root directory
    $script_path = $_SERVER['PHP_SELF'];
    $base_url = (strpos($script_path, '/pages/') !== false) ? '../' : '';
}
?>
<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center fw-bold" href="<?= $base_url ?>index.php">
            <img src="<?= $base_url ?>assets/images/logo.png" alt="EthioEvent Hub" width="40" height="40" class="me-2 rounded-circle object-fit-cover">
            ETHIO EVENT HUB
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link px-3" href="<?= $base_url ?>index.php"><?= __('home') ?></a></li>
                <li class="nav-item"><a class="nav-link px-3" href="<?= $base_url ?>pages/events.php"><?= __('events') ?></a></li>
                <li class="nav-item"><a class="nav-link px-3" href="<?= $base_url ?>pages/about.php"><?= __('about') ?></a></li>
                <li class="nav-item"><a class="nav-link px-3" href="<?= $base_url ?>pages/contact.php"><?= __('contact') ?></a></li>
                <li class="nav-item dropdown ms-lg-2">
                    <a class="nav-link dropdown-toggle px-3" href="#" id="langDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-globe me-1"></i> <?= strtoupper($lang) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end border-secondary shadow-lg" style="background: rgba(15, 23, 42, 0.9); backdrop-filter: blur(10px);">
                        <li><a class="dropdown-item <?= $lang === 'en' ? 'active' : '' ?>" href="?set_lang=en">English</a></li>
                        <li><a class="dropdown-item <?= $lang === 'am' ? 'active' : '' ?>" href="?set_lang=am">አማርኛ (Amharic)</a></li>
                        <li><a class="dropdown-item <?= $lang === 'om' ? 'active' : '' ?>" href="?set_lang=om">Afan Oromo</a></li>
                    </ul>
                </li>
                <li class="nav-item ms-lg-2">
                    <div class="theme-toggle" onclick="toggleDarkMode()" title="Toggle Dark/Light Mode">
                        <i class="fas fa-moon"></i>
                    </div>
                </li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item position-relative">
                        <a class="nav-link px-3" href="<?= $base_url ?>pages/notifications.php">
                            <i class="fas fa-bell"></i>
                            <span id="notification-badge" class="position-absolute translate-middle badge rounded-pill bg-danger d-none" style="font-size: 0.5rem; top: 10px; right: 0;">0</span>
                        </a>
                    </li>
                    <li class="nav-item"><a class="nav-link px-3" href="<?= $base_url ?>pages/wishlist.php"><?= __('wishlist') ?></a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="<?= $base_url ?>pages/dashboard.php"><?= __('dashboard') ?></a></li>
                    <li class="nav-item ms-lg-3"><a class="btn btn-outline-light btn-sm px-4 rounded-pill" href="<?= $base_url ?>pages/logout.php"><?= __('logout') ?></a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link px-3" href="<?= $base_url ?>pages/login.php"><?= __('login') ?></a></li>
                    <li class="nav-item ms-lg-3"><a class="btn btn-primary px-4 shadow-lg" href="<?= $base_url ?>pages/register.php"><?= __('get_started') ?></a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
