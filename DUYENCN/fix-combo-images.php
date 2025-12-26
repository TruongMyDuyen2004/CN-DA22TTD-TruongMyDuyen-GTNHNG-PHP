<?php
/**
 * Script sửa hình ảnh cho các combo còn thiếu
 */
require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<h2>🖼️ Sửa hình ảnh cho Combo</h2>";

// Cập nhật hình cho tất cả combo
$updates = [
    ['keyword' => 'HỌP MẶT BẠN BÈ', 'image' => 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=400&h=300&fit=crop'],
    ['keyword' => 'BUFFET', 'image' => 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=400&h=300&fit=crop'],
    ['keyword' => 'SINH NHẬT', 'image' => 'https://images.unsplash.com/photo-1558636508-e0db3814bd1d?w=400&h=300&fit=crop'],
    ['keyword' => 'LÃNG MẠN', 'image' => 'https://images.unsplash.com/photo-1529543544277-750e0862e3f0?w=400&h=300&fit=crop'],
    ['keyword' => 'VĂN PHÒNG', 'image' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=400&h=300&fit=crop'],
    ['keyword' => 'GIA ĐÌNH', 'image' => 'https://images.unsplash.com/photo-1547573854-74d2a71d0826?w=400&h=300&fit=crop'],
];

// Lấy tất cả combo chưa có hình hoặc hình không phải URL
$stmt = $conn->query("SELECT id, title, image FROM restaurant_promotions WHERE promo_type = 'combo'");
$combos = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($combos as $combo) {
    $title_upper = mb_strtoupper($combo['title'], 'UTF-8');
    $current_image = $combo['image'];
    
    // Kiểm tra nếu chưa có hình hoặc hình không phải URL
    $needs_update = empty($current_image) || !preg_match('/^https?:\/\//', $current_image);
    
    if ($needs_update) {
        foreach ($updates as $update) {
            if (mb_strpos($title_upper, $update['keyword']) !== false) {
                $stmt = $conn->prepare("UPDATE restaurant_promotions SET image = ? WHERE id = ?");
                $stmt->execute([$update['image'], $combo['id']]);
                echo "✅ Đã cập nhật: <strong>{$combo['title']}</strong><br>";
                echo "&nbsp;&nbsp;&nbsp;→ <a href='{$update['image']}' target='_blank'>Xem hình</a><br><br>";
                break;
            }
        }
    } else {
        echo "ℹ️ <strong>{$combo['title']}</strong> - Đã có hình<br><br>";
    }
}

echo "<hr>";
echo "<p style='color: green; font-weight: bold;'>✅ Hoàn tất!</p>";
echo "<a href='index.php?page=promotions' style='background: #22c55e; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; display: inline-block;'>🎉 Xem trang Khuyến mãi</a>";
?>
