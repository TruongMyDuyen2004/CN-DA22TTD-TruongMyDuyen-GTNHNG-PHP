<?php
session_start();
header('Content-Type: application/json');

// Lấy dữ liệu từ request
$data = json_decode(file_get_contents('php://input'), true);
$language = $data['language'] ?? 'vi';

// Danh sách ngôn ngữ được hỗ trợ
$supported_languages = ['vi', 'en'];

// Kiểm tra ngôn ngữ hợp lệ
if (in_array($language, $supported_languages)) {
    $_SESSION['language'] = $language;
    
    // Có thể lưu vào cookie để persistent
    setcookie('language', $language, time() + (86400 * 365), '/'); // 1 năm
    
    echo json_encode([
        'success' => true,
        'language' => $language,
        'message' => 'Language changed successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid language'
    ]);
}
