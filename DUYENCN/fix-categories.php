<?php
/**
 * Fix Categories - Chạy file này 1 lần để sửa lại danh mục
 * Truy cập: http://localhost/DUYENCN/fix-categories.php
 */

require_once 'includes/config.php';
require_once 'includes/Database.php';

$db = new Database();
$conn = $db->connect();

try {
    // Tắt foreign key check
    $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Xóa tất cả danh mục cũ
    $conn->exec("DELETE FROM categories");
    
    // Reset auto increment
    $conn->exec("ALTER TABLE categories AUTO_INCREMENT = 1");
    
    // Thêm đúng 5 danh mục
    $conn->exec("INSERT INTO categories (id, name, name_en, display_order) VALUES
        (1, 'Khai vị', 'Appetizer', 1),
        (2, 'Món chính', 'Main Course', 2),
        (3, 'Món phụ', 'Side Dish', 3),
        (4, 'Tráng miệng', 'Dessert', 4),
        (5, 'Đồ uống', 'Drinks', 5)");
    
    // Cập nhật menu_items có category_id không hợp lệ về Món chính
    $conn->exec("UPDATE menu_items SET category_id = 2 WHERE category_id NOT IN (1, 2, 3, 4, 5) OR category_id IS NULL");
    
    // Bật lại foreign key check
    $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "<h1 style='color: green;'>✅ Đã sửa xong danh mục!</h1>";
    echo "<p>Bây giờ chỉ còn 5 danh mục:</p>";
    echo "<ul>
        <li>Khai vị</li>
        <li>Món chính</li>
        <li>Món phụ</li>
        <li>Tráng miệng</li>
        <li>Đồ uống</li>
    </ul>";
    echo "<p><a href='index.php?page=menu'>👉 Quay lại trang Menu</a></p>";
    echo "<p style='color: red;'><strong>Lưu ý:</strong> Xóa file này sau khi chạy xong!</p>";
    
} catch (Exception $e) {
    echo "<h1 style='color: red;'>❌ Lỗi: " . $e->getMessage() . "</h1>";
}
?>
