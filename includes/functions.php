<?php
/**
 * Utility functions for EthioEvent Hub
 */

/**
 * Simulates sending an email by logging it to a local file.
 * This satisfies the "Email Sandbox" requirement.
 */
function sendSimulatedEmail($to, $subject, $message) {
    $log_dir = __DIR__ . '/../logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    
    $log_file = $log_dir . '/mail_sandbox.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] TO: $to | SUBJECT: $subject | MESSAGE: $message\n" . str_repeat("-", 80) . "\n";
    
    return file_put_contents($log_file, $log_entry, FILE_APPEND);
}

/**
 * Sanitizes output for XSS protection.
 */
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Formats ETB currency.
 */
function formatCurrency($amount) {
    return number_format($amount, 2) . ' ETB';
}
?>