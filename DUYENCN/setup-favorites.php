<?php
/**
 * Script tạo bảng favorites
 * Chạy file này 1 lần để tạo bảng trong database
 */

require_once __DIR__ . '/config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<h2>Setup Favorites Table</h2>";

try {
    // Tạo bảng favorites
    $sql = "CREATE TABLE IF NOT EXISTS favorites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        menu_item_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_favorite (customer_id, menu_item_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "<p style='color: green;'>✓ Tạo bảng favorites thành công!</p>";
    
    // Thêm index
    try {
        $conn->exec("CREATE INDEX idx_favorites_customer ON favorites(customer_id)");
        echo "<p style='color: green;'>✓ Tạo index customer_id thành công!</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>Index customer_id đã tồn tại</p>";
    }
    
    try {
        $conn->exec("CREATE INDEX idx_favorites_menu_item ON favorites(menu_item_id)");
        echo "<p style='color: green;'>✓ Tạo index menu_item_id thành công!</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>Index menu_item_id đã tồn tại</p>";
    }
    
    echo "<br><p style='color: blue; font-weight: bold;'>✓ Setup hoàn tất! Bạn có thể xóa file này.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Lỗi: " . $e->getMessage() . "</p>";
}
