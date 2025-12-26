<?php
/**
 * Script thêm món vào combo BUFFET TRƯA VĂN PHÒNG
 */
require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<h2>🍽️ Thêm món vào BUFFET TRƯA VĂN PHÒNG</h2>";

// Tìm combo BUFFET TRƯA VĂN PHÒNG
$stmt = $conn->prepare("SELECT id, title, combo_price FROM restaurant_promotions WHERE title LIKE '%BUFFET%' OR title LIKE '%buffet%'");
$stmt->execute();
$combo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$combo) {
    echo "❌ Không tìm thấy combo BUFFET TRƯA VĂN PHÒNG<br>";
    exit;
}

echo "✅ Tìm thấy combo: <strong>{$combo['title']}</strong> (ID: {$combo['id']})<br><br>";

// Kiểm tra xem combo đã có món chưa
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM promotion_items WHERE promotion_id = ?");
$stmt->execute([$combo['id']]);
$count = $stmt->fetch()['count'];

if ($count > 0) {
    echo "ℹ️ Combo này đã có {$count} món. Xóa và thêm mới...<br>";
    $stmt = $conn->prepare("DELETE FROM promotion_items WHERE promotion_id = ?");
    $stmt->execute([$combo['id']]);
}

// Lấy 4 món ngẫu nhiên
$stmt = $conn->query("SELECT id, name, price FROM menu_items WHERE is_available = 1 ORDER BY RAND() LIMIT 4");
$menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($menu_items) < 4) {
    echo "❌ Không đủ món ăn trong menu<br>";
    exit;
}

// Thêm món vào combo
$total_price = 0;
echo "<h3>📋 Các món trong combo:</h3>";

foreach ($menu_items as $item) {
    $stmt = $conn->prepare("INSERT INTO promotion_items (promotion_id, menu_item_id, quantity) VALUES (?, ?, 1)");
    $stmt->execute([$combo['id'], $item['id']]);
    $total_price += $item['price'];
    echo "✅ {$item['name']} - " . number_format($item['price']) . "đ<br>";
}

// Cập nhật giá combo (giảm 20%)
$combo_price = round($total_price * 0.8);
$stmt = $conn->prepare("UPDATE restaurant_promotions SET combo_price = ?, discount_percent = 20, discount_text = 'Tiết kiệm 20%' WHERE id = ?");
$stmt->execute([$combo_price, $combo['id']]);

echo "<br><hr>";
echo "<p>💰 Tổng giá gốc: <strong>" . number_format($total_price) . "đ</strong></p>";
echo "<p>💰 Giá combo (giảm 20%): <strong style='color: #22c55e;'>" . number_format($combo_price) . "đ</strong></p>";

echo "<br><p style='color: green; font-weight: bold;'>✅ Đã thêm 4 món vào combo thành công!</p>";
echo "<a href='index.php?page=promotions' style='background: #22c55e; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; display: inline-block; margin-top: 10px;'>🎉 Xem trang Khuyến mãi</a>";
?>
