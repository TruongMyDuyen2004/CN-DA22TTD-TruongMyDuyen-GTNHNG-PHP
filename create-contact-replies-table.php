<?php
/**
 * Tạo bảng contact_replies nhanh
 */

require_once 'config/database.php';

try {
    $db = new Database();
    $conn = $db->connect();
    
    echo "<h2>Tạo bảng contact_replies</h2>";
    
    // Tạo bảng contact_replies
    $sql = "CREATE TABLE IF NOT EXISTS contact_replies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        contact_id INT NOT NULL,
        admin_id INT NOT NULL,
        reply_message TEXT NOT NULL,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_contact_id (contact_id),
        INDEX idx_admin_id (admin_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "<p style='color: green;'>✓ Đã tạo bảng contact_replies</p>";
    
    // Thêm cột admin_reply
    try {
        $conn->exec("ALTER TABLE contacts ADD COLUMN admin_reply TEXT NULL");
        echo "<p style='color: green;'>✓ Đã thêm cột admin_reply</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false) {
            echo "<p style='color: blue;'>ℹ️ Cột admin_reply đã tồn tại</p>";
        }
    }
    
    // Thêm cột replied_at
    try {
        $conn->exec("ALTER TABLE contacts ADD COLUMN replied_at TIMESTAMP NULL");
        echo "<p style='color: green;'>✓ Đã thêm cột replied_at</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false) {
            echo "<p style='color: blue;'>ℹ️ Cột replied_at đã tồn tại</p>";
        }
    }
    
    // Thêm cột replied_by
    try {
        $conn->exec("ALTER TABLE contacts ADD COLUMN replied_by INT NULL");
        echo "<p style='color: green;'>✓ Đã thêm cột replied_by</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false) {
            echo "<p style='color: blue;'>ℹ️ Cột replied_by đã tồn tại</p>";
        }
    }
    
    echo "<h3 style='color: green;'>✓ Hoàn tất!</h3>";
    echo "<p><a href='admin/contacts.php'>Quay lại trang quản lý liên hệ</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Lỗi: " . $e->getMessage() . "</p>";
}
?>
