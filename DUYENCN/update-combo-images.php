<?php
/**
 * Script cập nhật hình ảnh cho các combo
 * Sử dụng hình ảnh từ Unsplash (miễn phí)
 */
require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<h2>🖼️ Cập nhật hình ảnh cho Combo</h2>";

// Danh sách hình ảnh theo tên combo
$combo_images = [
    'SINH NHẬT' => 'https://images.unsplash.com/photo-1558636508-e0db3814bd1d?w=400&h=300&fit=crop',
    'TIỆC SINH NHẬT' => 'https://images.unsplash.com/photo-1558636508-e0db3814bd1d?w=400&h=300&fit=crop',
    'LÃNG MẠN' => 'https://images.unsplash.com/photo-1529543544277-750e0862e3f0?w=400&h=300&fit=crop',
    'ĐÔI' => 'https://images.unsplash.com/photo-1529543544277-750e0862e3f0?w=400&h=300&fit=crop',
    'BẠN BÈ' => 'https://images.unsplash.com/photo-1529543544277-750e0862e3f0?w=400&h=300&fit=crop',
    'HỌP MẶT' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=400&h=300&fit=crop',
    'VĂN PHÒNG' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=400&h=300&fit=crop',
    'TRƯA' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=400&h=300&fit=crop',
    'BUFFET' => 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=400&h=300&fit=crop',
    'GIA ĐÌNH' => 'https://images.unsplash.com/photo-1547573854-74d2a71d0826?w=400&h=300&fit=crop',
    'CUỐI TUẦN' => 'https://images.unsplash.com/photo-1547573854-74d2a71d0826?w=400&h=300&fit=crop'
];

// Lấy tất cả combo
$stmt = $conn->query("SELECT id, title FROM restaurant_promotions WHERE promo_type = 'combo'");
$combos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>📋 Cập nhật hình ảnh:</h3>";

foreach ($combos as $combo) {
    $title_upper = mb_strtoupper($combo['title'], 'UTF-8');
    $image_url = null;
    
    // Tìm hình ảnh phù hợp
    foreach ($combo_images as $keyword => $url) {
        if (strpos($title_upper, $keyword) !== false) {
            $image_url = $url;
            break;
        }
    }
    
    if ($image_url) {
        // Cập nhật vào database
        $stmt = $conn->prepare("UPDATE restaurant_promotions SET image = ? WHERE id = ?");
        $stmt->execute([$image_url, $combo['id']]);
        echo "✅ <strong>{$combo['title']}</strong><br>";
        echo "&nbsp;&nbsp;&nbsp;→ Hình: <a href='{$image_url}' target='_blank'>Xem hình</a><br><br>";
    } else {
        echo "⚠️ <strong>{$combo['title']}</strong> - Không tìm thấy hình phù hợp<br><br>";
    }
}

echo "<hr>";
echo "<p style='color: green; font-weight: bold;'>✅ Đã cập nhật hình ảnh cho các combo!</p>";
echo "<a href='index.php?page=promotions' style='background: #22c55e; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; display: inline-block; margin-top: 10px;'>🎉 Xem trang Khuyến mãi</a>";
?>
