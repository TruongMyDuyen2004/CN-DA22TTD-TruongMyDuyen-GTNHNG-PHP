<?php
/**
 * Script cập nhật hình ảnh cho TẤT CẢ combo - mỗi combo 1 hình khác nhau
 */
require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<h2>🖼️ Cập nhật hình ảnh cho TẤT CẢ Combo</h2>";

// Hình ảnh khác nhau cho từng combo
$all_images = [
    'https://images.unsplash.com/photo-1558636508-e0db3814bd1d?w=400&h=300&fit=crop', // Sinh nhật - bánh
    'https://images.unsplash.com/photo-1559339352-11d035aa65de?w=400&h=300&fit=crop', // Lãng mạn - nến
    'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=400&h=300&fit=crop', // Bạn bè - nhà hàng
    'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&h=300&fit=crop', // Văn phòng - salad
    'https://images.unsplash.com/photo-1547573854-74d2a71d0826?w=400&h=300&fit=crop', // Gia đình - bàn ăn
    'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=400&h=300&fit=crop', // Buffet - nhiều món
];

// Lấy tất cả combo
$stmt = $conn->query("SELECT id, title FROM restaurant_promotions WHERE promo_type = 'combo' ORDER BY id");
$combos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>📋 Cập nhật:</h3>";

$index = 0;
foreach ($combos as $combo) {
    // Lấy hình theo thứ tự, nếu hết thì quay lại đầu
    $image_url = $all_images[$index % count($all_images)];
    
    // Cập nhật vào database
    $stmt = $conn->prepare("UPDATE restaurant_promotions SET image = ? WHERE id = ?");
    $stmt->execute([$image_url, $combo['id']]);
    
    echo "✅ <strong>{$combo['title']}</strong><br>";
    echo "&nbsp;&nbsp;&nbsp;→ <img src='{$image_url}' style='width:100px;height:60px;object-fit:cover;border-radius:8px;vertical-align:middle;'><br><br>";
    
    $index++;
}

echo "<hr>";
echo "<p style='color: green; font-weight: bold; font-size: 18px;'>✅ Đã cập nhật hình cho " . count($combos) . " combo!</p>";
echo "<a href='index.php?page=promotions' style='background: #22c55e; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; display: inline-block;'>🎉 Xem trang Khuyến mãi</a>";
?>
