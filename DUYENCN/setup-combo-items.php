<?php
/**
 * Script thêm món vào combo
 * Chạy 1 lần để thêm dữ liệu mẫu
 */
require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<h2>🍽️ Setup Combo Items</h2>";

// 1. Tạo bảng promotion_items nếu chưa có
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS promotion_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        promotion_id INT NOT NULL,
        menu_item_id INT NOT NULL,
        quantity INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✅ Bảng promotion_items đã sẵn sàng<br>";
} catch (PDOException $e) {
    echo "❌ Lỗi tạo bảng: " . $e->getMessage() . "<br>";
}

// 2. Thêm cột combo_price vào restaurant_promotions nếu chưa có
try {
    $conn->query("SELECT combo_price FROM restaurant_promotions LIMIT 1");
    echo "✅ Cột combo_price đã có<br>";
} catch (PDOException $e) {
    $conn->exec("ALTER TABLE restaurant_promotions ADD COLUMN combo_price DECIMAL(10,0) DEFAULT NULL");
    echo "✅ Đã thêm cột combo_price<br>";
}

// 3. Lấy danh sách combo
$stmt = $conn->query("SELECT id, title FROM restaurant_promotions WHERE promo_type = 'combo'");
$combos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<br><h3>📋 Danh sách Combo:</h3>";
if (empty($combos)) {
    echo "⚠️ Chưa có combo nào. Hãy tạo combo trong admin trước.<br>";
} else {
    foreach ($combos as $combo) {
        echo "- ID {$combo['id']}: {$combo['title']}<br>";
    }
}

// 4. Lấy danh sách món ăn
$stmt = $conn->query("SELECT id, name, price FROM menu_items WHERE is_available = 1 ORDER BY id LIMIT 20");
$menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<br><h3>🍴 Danh sách món ăn (20 món đầu):</h3>";
foreach ($menu_items as $item) {
    echo "- ID {$item['id']}: {$item['name']} - " . number_format($item['price']) . "đ<br>";
}

// 5. Thêm món vào combo đầu tiên (nếu có)
if (!empty($combos) && !empty($menu_items)) {
    $combo_id = $combos[0]['id'];
    
    // Kiểm tra xem combo đã có món chưa
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM promotion_items WHERE promotion_id = ?");
    $stmt->execute([$combo_id]);
    $count = $stmt->fetch()['count'];
    
    if ($count == 0) {
        echo "<br><h3>➕ Thêm món vào combo ID {$combo_id}:</h3>";
        
        // Lấy 4 món đầu tiên để thêm vào combo
        $items_to_add = array_slice($menu_items, 0, 4);
        $total_price = 0;
        
        foreach ($items_to_add as $item) {
            $stmt = $conn->prepare("INSERT INTO promotion_items (promotion_id, menu_item_id, quantity) VALUES (?, ?, 1)");
            $stmt->execute([$combo_id, $item['id']]);
            $total_price += $item['price'];
            echo "✅ Đã thêm: {$item['name']}<br>";
        }
        
        // Tính giá combo (giảm 20%)
        $combo_price = round($total_price * 0.8);
        $stmt = $conn->prepare("UPDATE restaurant_promotions SET combo_price = ? WHERE id = ?");
        $stmt->execute([$combo_price, $combo_id]);
        
        echo "<br>💰 Tổng giá gốc: " . number_format($total_price) . "đ<br>";
        echo "💰 Giá combo (giảm 20%): " . number_format($combo_price) . "đ<br>";
    } else {
        echo "<br>ℹ️ Combo ID {$combo_id} đã có {$count} món<br>";
    }
}

echo "<br><hr><br>";
echo "<a href='index.php?page=promotions' style='background: #22c55e; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px;'>Xem trang Khuyến mãi</a>";
?>
