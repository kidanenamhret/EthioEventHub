<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Default language
$lang = $_SESSION['lang'] ?? 'en';

// Load translations
$translations_file = __DIR__ . '/translations.json';
$translations = [];

if (file_exists($translations_file)) {
    $json_content = file_get_contents($translations_file);
    $translations = json_decode($json_content, true);
}

/**
 * Translate a key based on the current session language
 * @param string $key
 * @return string
 */
function __($key) {
    global $translations, $lang;
    return $translations[$lang][$key] ?? $translations['en'][$key] ?? $key;
}

// Handle language change via GET
if (isset($_GET['set_lang'])) {
    $requested_lang = $_GET['set_lang'];
    if (isset($translations[$requested_lang])) {
        $_SESSION['lang'] = $requested_lang;
        
        // Redirect back to the same page without the set_lang parameter
        $url = strtok($_SERVER['REQUEST_URI'], '?');
        $params = $_GET;
        unset($params['set_lang']);
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        header("Location: " . $url);
        exit;
    }
}
?>
