<?php
/**
 * Phân bổ món ăn vào các danh mục
 * Truy cập: http://localhost/DUYENCN/assign-categories.php
 */

require_once 'includes/config.php';
require_once 'includes/Database.php';

$db = new Database();
$conn = $db->connect();

try {
    // Lấy tổng số món
    $stmt = $conn->query("SELECT COUNT(*) as total FROM menu_items");
    $total = $stmt->fetch()['total'];
    
    // Phân bổ món ăn theo tỷ lệ hợp lý
    // Khai vị (id=1): 20% đầu
    // Món chính (id=2): 40% tiếp theo  
    // Món phụ (id=3): 15%
    // Tráng miệng (id=4): 15%
    // Đồ uống (id=5): 10% cuối
    
    $khaivi_end = floor($total * 0.2);
    $monchinh_end = floor($total * 0.6);
    $monphu_end = floor($total * 0.75);
    $trangmieng_end = floor($total * 0.9);
    
    // Lấy tất cả ID món ăn
    $stmt = $conn->query("SELECT id FROM menu_items ORDER BY id");
    $items = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $updated = 0;
    
    foreach ($items as $index => $id) {
        if ($index < $khaivi_end) {
            $category_id = 1; // Khai vị
        } elseif ($index < $monchinh_end) {
            $category_id = 2; // Món chính
        } elseif ($index < $monphu_end) {
            $category_id = 3; // Món phụ
        } elseif ($index < $trangmieng_end) {
            $category_id = 4; // Tráng miệng
        } else {
            $category_id = 5; // Đồ uống
        }
        
        $stmt = $conn->prepare("UPDATE menu_items SET category_id = ? WHERE id = ?");
        $stmt->execute([$category_id, $id]);
        $updated++;
    }
    
    // Hiển thị kết quả
    echo "<h1 style='color: green;'>✅ Đã phân bổ $updated món ăn vào các danh mục!</h1>";
    
    // Đếm số món mỗi danh mục
    $stmt = $conn->query("SELECT c.name, COUNT(m.id) as count 
                          FROM categories c 
                          LEFT JOIN menu_items m ON c.id = m.category_id 
                          GROUP BY c.id, c.name 
                          ORDER BY c.display_order");
    $counts = $stmt->fetchAll();
    
    echo "<h2>Số món mỗi danh mục:</h2><ul>";
    foreach ($counts as $row) {
        echo "<li><strong>{$row['name']}</strong>: {$row['count']} món</li>";
    }
    echo "</ul>";
    
    echo "<p><a href='index.php?page=menu'>👉 Quay lại trang Menu</a></p>";
    echo "<p style='color: red;'><strong>Lưu ý:</strong> Xóa file này sau khi chạy xong!</p>";
    
} catch (Exception $e) {
    echo "<h1 style='color: red;'>❌ Lỗi: " . $e->getMessage() . "</h1>";
}
?>
