<?php
/**
 * Platform Audit Trail System
 * Tracks critical user actions for security and debugging.
 */
function logActivity($action, $details = "") {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $user_id = $_SESSION['user_id'] ?? 0;
    $user_name = $_SESSION['user_name'] ?? 'Guest';
    
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'user_id' => $user_id,
        'user_name' => $user_name,
        'action' => $action,
        'details' => $details,
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    ];

    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }

    $logFile = $logDir . '/activity.log';
    file_put_contents($logFile, json_encode($logEntry) . PHP_EOL, FILE_APPEND);
}
?>
