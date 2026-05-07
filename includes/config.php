<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'ethioeventhub');
define('DB_USER', 'root');
define('DB_PASS', '');

// Updated Site configuration to match your subfolder
define('SITE_URL', 'http://localhost/Wep_Programming_Pro/EthioEventHub/');
?>