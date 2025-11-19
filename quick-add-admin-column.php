<?php
require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

try {
    // Thêm cột is_admin
    $conn->exec("ALTER TABLE customers ADD COLUMN is_admin BOOLEAN DEFAULT FALSE");
    echo "✅ Đã thêm cột is_admin thành công!<br>";
    
    // Cấp quyền admin cho user đầu tiên
    $conn->exec("UPDATE customers SET is_admin = 1 WHERE id = 1");
    echo "✅ Đã cấp quyền admin cho user ID 1<br>";
    
    echo "<br><a href='index.php?page=menu'>Xem trang Menu</a>";
    
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "⚠️ Cột is_admin đã tồn tại<br>";
        echo "<br><a href='setup-admin-user.php'>Cấp quyền admin</a>";
    } else {
        echo "❌ Lỗi: " . $e->getMessage();
    }
}
?>
