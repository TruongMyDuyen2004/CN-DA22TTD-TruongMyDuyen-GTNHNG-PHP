<?php
/**
 * Script thêm 4 combo mới vào trang khuyến mãi
 */
require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<h2>🍽️ Thêm 4 Combo Mới</h2>";

// Lấy danh sách món ăn
$stmt = $conn->query("SELECT id, name, price FROM menu_items WHERE is_available = 1 ORDER BY RAND() LIMIT 20");
$menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($menu_items) < 16) {
    echo "❌ Cần ít nhất 16 món ăn để tạo 4 combo (mỗi combo 4 món)<br>";
    exit;
}

// Định nghĩa 4 combo mới
$combos = [
    [
        'title' => 'COMBO TIỆC SINH NHẬT',
        'description' => 'Combo hoàn hảo cho bữa tiệc sinh nhật với gia đình và bạn bè',
        'discount_percent' => 25,
        'items_start' => 0
    ],
    [
        'title' => 'COMBO ĐÔI LÃNG MẠN',
        'description' => 'Bữa tối lãng mạn dành cho 2 người với giá ưu đãi',
        'discount_percent' => 15,
        'items_start' => 4
    ],
    [
        'title' => 'COMBO HỌP MẶT BẠN BÈ',
        'description' => 'Combo dành cho nhóm bạn bè họp mặt cuối tuần',
        'discount_percent' => 30,
        'items_start' => 8
    ],
    [
        'title' => 'COMBO TRƯA VĂN PHÒNG',
        'description' => 'Combo tiết kiệm cho bữa trưa văn phòng',
        'discount_percent' => 20,
        'items_start' => 12
    ]
];

// Ngày bắt đầu và kết thúc
$start_date = date('Y-m-d');
$end_dates = [
    date('Y-m-d', strtotime('+10 days')),
    date('Y-m-d', strtotime('+7 days')),
    date('Y-m-d', strtotime('+14 days')),
    date('Y-m-d', strtotime('+5 days'))
];

echo "<h3>📋 Tạo các combo:</h3>";

foreach ($combos as $index => $combo) {
    // Lấy 4 món cho combo này
    $combo_items = array_slice($menu_items, $combo['items_start'], 4);
    
    if (count($combo_items) < 4) {
        echo "⚠️ Không đủ món cho combo: {$combo['title']}<br>";
        continue;
    }
    
    // Tính giá gốc và giá combo
    $total_price = 0;
    foreach ($combo_items as $item) {
        $total_price += $item['price'];
    }
    $combo_price = round($total_price * (100 - $combo['discount_percent']) / 100);
    $discount_text = "Tiết kiệm {$combo['discount_percent']}%";
    
    // Thêm vào bảng restaurant_promotions
    $stmt = $conn->prepare("INSERT INTO restaurant_promotions 
        (title, description, promo_type, discount_text, discount_percent, start_date, end_date, is_active, is_featured, combo_price) 
        VALUES (?, ?, 'combo', ?, ?, ?, ?, 1, 1, ?)");
    
    $stmt->execute([
        $combo['title'],
        $combo['description'],
        $discount_text,
        $combo['discount_percent'],
        $start_date,
        $end_dates[$index],
        $combo_price
    ]);
    
    $promo_id = $conn->lastInsertId();
    
    echo "<br><strong>✅ {$combo['title']}</strong> (ID: {$promo_id})<br>";
    echo "   Giảm: {$combo['discount_percent']}%<br>";
    echo "   Giá gốc: " . number_format($total_price) . "đ → Giá combo: " . number_format($combo_price) . "đ<br>";
    echo "   Hết hạn: {$end_dates[$index]}<br>";
    echo "   Các món:<br>";
    
    // Thêm món vào promotion_items
    foreach ($combo_items as $item) {
        $stmt = $conn->prepare("INSERT INTO promotion_items (promotion_id, menu_item_id, quantity) VALUES (?, ?, 1)");
        $stmt->execute([$promo_id, $item['id']]);
        echo "   - {$item['name']} (" . number_format($item['price']) . "đ)<br>";
    }
}

echo "<br><hr><br>";
echo "<p style='color: green; font-weight: bold;'>✅ Đã thêm 4 combo mới thành công!</p>";
echo "<a href='index.php?page=promotions' style='background: #22c55e; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; display: inline-block; margin-top: 10px;'>🎉 Xem trang Khuyến mãi</a>";
?>
