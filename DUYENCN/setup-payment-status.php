<?php
/**
 * Script thêm cột payment_status vào bảng orders
 * Chạy file này 1 lần để cập nhật database
 */

require_once __DIR__ . '/config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<h2>Setup Payment Status Column</h2>";

try {
    // Kiểm tra xem cột đã tồn tại chưa
    $checkCol = $conn->query("SHOW COLUMNS FROM orders LIKE 'payment_status'");
    if ($checkCol->rowCount() == 0) {
        // Thêm cột payment_status
        $sql = "ALTER TABLE orders ADD COLUMN payment_status VARCHAR(20) DEFAULT 'pending' AFTER payment_method";
        $conn->exec($sql);
        echo "<p style='color: green;'>✓ Thêm cột payment_status thành công!</p>";
        
        // Cập nhật các đơn hàng cũ thanh toán tiền mặt thành 'paid'
        $sql = "UPDATE orders SET payment_status = 'paid' WHERE payment_method = 'cash'";
        $conn->exec($sql);
        echo "<p style='color: green;'>✓ Cập nhật trạng thái đơn hàng COD thành công!</p>";
    } else {
        echo "<p style='color: blue;'>Cột payment_status đã tồn tại.</p>";
    }
    
    echo "<br><p style='color: green; font-weight: bold;'>✓ Setup hoàn tất!</p>";
    
    echo "<br><h3>Giải thích trạng thái thanh toán:</h3>";
    echo "<ul>";
    echo "<li><strong>pending</strong>: Chờ thanh toán (chuyển khoản chưa xác nhận)</li>";
    echo "<li><strong>paid</strong>: Đã thanh toán</li>";
    echo "</ul>";
    
    echo "<br><a href='admin/orders.php' style='padding: 10px 20px; background: #22c55e; color: white; text-decoration: none; border-radius: 8px;'>Quay lại quản lý đơn hàng</a>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Lỗi: " . $e->getMessage() . "</p>";
}
