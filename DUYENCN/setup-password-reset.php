<?php
/**
 * Script tạo bảng password_resets cho tính năng quên mật khẩu
 */
require_once 'config/database.php';

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Tạo bảng password_resets
    $sql = "CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        token VARCHAR(255) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_token (token)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    
    echo "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; background: #f0fdf4; border-radius: 12px; border: 2px solid #22c55e;'>";
    echo "<h2 style='color: #166534;'>✅ Tạo bảng thành công!</h2>";
    echo "<p style='color: #15803d;'>Bảng <strong>password_resets</strong> đã được tạo.</p>";
    echo "<p style='color: #6b7280;'>Bạn có thể xóa file này sau khi chạy.</p>";
    echo "<a href='auth/login.php' style='display: inline-block; margin-top: 15px; padding: 10px 20px; background: #22c55e; color: white; text-decoration: none; border-radius: 8px;'>Đi đến trang đăng nhập</a>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; background: #fee2e2; border-radius: 12px; border: 2px solid #dc2626;'>";
    echo "<h2 style='color: #dc2626;'>❌ Lỗi!</h2>";
    echo "<p style='color: #991b1b;'>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
