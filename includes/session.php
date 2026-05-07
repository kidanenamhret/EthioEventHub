<?php
require_once __DIR__ . '/config.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isOrganizer() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'organizer';
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . 'pages/login.php');
        exit;
    }
}

function requireOrganizer() {
    requireLogin();
    if (!isOrganizer()) {
        die("Access denied. Organizer only.");
    }
}
?>