<?php
/**
 * Email Sandbox Helper - Simulates sending emails by logging them to a local file.
 */
function sendSandboxEmail($to, $subject, $message) {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    
    $logFile = $logDir . '/mail_sandbox.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] TO: $to | SUBJECT: $subject\nMESSAGE: $message\n--------------------------------------------\n";
    
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    return true; // Simulate success
}
?>
