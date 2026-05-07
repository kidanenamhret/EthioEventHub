<?php
/**
 * Rate Limiting System
 * Prevents brute-force attacks and abuse.
 */
function checkRateLimit($key, $limit = 5, $window = 60) {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }

    $file = $logDir . "/ratelimit_" . md5($key) . ".json";
    $now = time();
    
    $attempts = [];
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        // Filter attempts within the time window
        $attempts = array_filter($data['attempts'] ?? [], function($timestamp) use ($now, $window) {
            return ($now - $timestamp) < $window;
        });
    }
    
    if (count($attempts) >= $limit) {
        return false; // Limit exceeded
    }
    
    $attempts[] = $now;
    file_put_contents($file, json_encode(['attempts' => array_values($attempts)]));
    return true;
}
?>
