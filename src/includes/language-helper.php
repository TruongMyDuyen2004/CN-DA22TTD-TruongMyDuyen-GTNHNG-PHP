<?php
// Language Helper Functions

// Lấy ngôn ngữ hiện tại
function getCurrentLanguage() {
    $supported = ['vi', 'en'];
    
    // Ưu tiên session, sau đó cookie, cuối cùng mặc định
    $lang = null;
    
    if (isset($_SESSION['language'])) {
        $lang = $_SESSION['language'];
    } elseif (isset($_COOKIE['language'])) {
        $lang = $_COOKIE['language'];
    }
    
    // Kiểm tra ngôn ngữ có hợp lệ không
    if ($lang && in_array($lang, $supported)) {
        $_SESSION['language'] = $lang;
        return $lang;
    }
    
    // Mặc định tiếng Việt
    $_SESSION['language'] = 'vi';
    return 'vi';
}

// Load file ngôn ngữ
function loadLanguage($lang = null) {
    if ($lang === null) {
        $lang = getCurrentLanguage();
    }
    
    $file = __DIR__ . "/../lang/{$lang}.php";
    
    if (file_exists($file)) {
        return include $file;
    }
    
    // Fallback to Vietnamese
    return include __DIR__ . "/../lang/vi.php";
}

// Hàm dịch
function __($key, $default = null) {
    static $translations = null;
    static $last_lang = null;
    
    $current_lang = getCurrentLanguage();
    
    // Reload translations nếu ngôn ngữ thay đổi
    if ($translations === null || $last_lang !== $current_lang) {
        $translations = loadLanguage($current_lang);
        $last_lang = $current_lang;
    }
    
    return $translations[$key] ?? $default ?? $key;
}

// Hàm dịch với tham số
function __f($key, $params = []) {
    $text = __($key);
    
    foreach ($params as $param => $value) {
        $text = str_replace("{{$param}}", $value, $text);
    }
    
    return $text;
}

// Lấy tất cả ngôn ngữ
function getAvailableLanguages() {
    return [
        'vi' => ['name' => 'Tiếng Việt', 'flag' => '🇻🇳'],
        'en' => ['name' => 'English', 'flag' => '🇬🇧']
    ];
}
