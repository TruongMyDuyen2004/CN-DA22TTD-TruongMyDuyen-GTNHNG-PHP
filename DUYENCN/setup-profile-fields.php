<?php
/**
 * Script thêm các trường thông tin cá nhân vào bảng customers
 * Chạy file này một lần để cập nhật database
 */

require_once 'config/database.php';

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Thêm các cột mới nếu chưa có
    $columns = [
        'birthday' => "ALTER TABLE customers ADD COLUMN birthday DATE NULL AFTER address",
        'gender' => "ALTER TABLE customers ADD COLUMN gender ENUM('male', 'female', 'other') NULL AFTER birthday"
    ];
    
    $added = [];
    $existed = [];
    
    foreach ($columns as $column => $sql) {
        // Kiểm tra cột đã tồn tại chưa
        $check = $conn->query("SHOW COLUMNS FROM customers LIKE '$column'");
        if ($check->rowCount() == 0) {
            $conn->exec($sql);
            $added[] = $column;
        } else {
            $existed[] = $column;
        }
    }
    
    echo "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 30px; background: #f0fdf4; border-radius: 16px; border: 2px solid #22c55e;'>";
    echo "<h2 style='color: #166534; margin-bottom: 20px;'>✅ Cập nhật database thành công!</h2>";
    
    if (!empty($added)) {
        echo "<p style='color: #374151;'><strong>Đã thêm các cột:</strong> " . implode(', ', $added) . "</p>";
    }
    
    if (!empty($existed)) {
        echo "<p style='color: #6b7280;'><strong>Các cột đã tồn tại:</strong> " . implode(', ', $existed) . "</p>";
    }
    
    echo "<p style='color: #374151; margin-top: 15px;'>Bạn có thể xóa file này sau khi chạy.</p>";
    echo "<a href='index.php?page=profile' style='display: inline-block; margin-top: 20px; padding: 12px 24px; background: #22c55e; color: white; text-decoration: none; border-radius: 8px; font-weight: 600;'>Đi đến trang Profile</a>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 30px; background: #fee2e2; border-radius: 16px; border: 2px solid #dc2626;'>";
    echo "<h2 style='color: #dc2626; margin-bottom: 20px;'>❌ Lỗi!</h2>";
    echo "<p style='color: #374151;'>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
