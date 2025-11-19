<?php
/**
 * Fix Contact Reply - Tạo các cột cần thiết
 */

require_once 'config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Fix Contact Reply</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; border-radius: 8px; margin: 10px 0; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
    </style>
</head>
<body>
    <h1>🔧 Fix Contact Reply System</h1>";

try {
    $db = new Database();
    $conn = $db->connect();
    
    echo "<div class='box'>";
    echo "<h2>Bước 1: Kiểm tra bảng contacts</h2>";
    
    // Kiểm tra các cột hiện có
    $stmt = $conn->query("DESCRIBE contacts");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p>Các cột hiện có: " . implode(', ', $columns) . "</p>";
    
    // Thêm cột admin_reply
    if (!in_array('admin_reply', $columns)) {
        try {
            $conn->exec("ALTER TABLE contacts ADD COLUMN admin_reply TEXT NULL");
            echo "<p class='success'>✓ Đã thêm cột admin_reply</p>";
        } catch (PDOException $e) {
            echo "<p class='error'>✗ Lỗi thêm admin_reply: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p class='info'>ℹ️ Cột admin_reply đã tồn tại</p>";
    }
    
    // Thêm cột replied_at
    if (!in_array('replied_at', $columns)) {
        try {
            $conn->exec("ALTER TABLE contacts ADD COLUMN replied_at TIMESTAMP NULL");
            echo "<p class='success'>✓ Đã thêm cột replied_at</p>";
        } catch (PDOException $e) {
            echo "<p class='error'>✗ Lỗi thêm replied_at: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p class='info'>ℹ️ Cột replied_at đã tồn tại</p>";
    }
    
    // Thêm cột replied_by
    if (!in_array('replied_by', $columns)) {
        try {
            $conn->exec("ALTER TABLE contacts ADD COLUMN replied_by INT NULL");
            echo "<p class='success'>✓ Đã thêm cột replied_by</p>";
        } catch (PDOException $e) {
            echo "<p class='error'>✗ Lỗi thêm replied_by: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p class='info'>ℹ️ Cột replied_by đã tồn tại</p>";
    }
    
    echo "</div>";
    
    // Tạo bảng contact_replies
    echo "<div class='box'>";
    echo "<h2>Bước 2: Tạo bảng contact_replies</h2>";
    
    try {
        $conn->exec("CREATE TABLE IF NOT EXISTS contact_replies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            contact_id INT NOT NULL,
            admin_id INT NOT NULL,
            reply_message TEXT NOT NULL,
            sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_contact_id (contact_id),
            INDEX idx_admin_id (admin_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        echo "<p class='success'>✓ Bảng contact_replies đã sẵn sàng</p>";
    } catch (PDOException $e) {
        echo "<p class='error'>✗ Lỗi tạo bảng: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
    
    // Kiểm tra lại
    echo "<div class='box'>";
    echo "<h2>Bước 3: Kiểm tra kết quả</h2>";
    
    $stmt = $conn->query("DESCRIBE contacts");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $required = ['admin_reply', 'replied_at', 'replied_by'];
    $missing = array_diff($required, $columns);
    
    if (empty($missing)) {
        echo "<p class='success'>✓ Tất cả các cột đã có trong bảng contacts</p>";
    } else {
        echo "<p class='error'>✗ Còn thiếu các cột: " . implode(', ', $missing) . "</p>";
    }
    
    // Kiểm tra bảng contact_replies
    try {
        $stmt = $conn->query("SELECT COUNT(*) FROM contact_replies");
        echo "<p class='success'>✓ Bảng contact_replies hoạt động tốt</p>";
    } catch (PDOException $e) {
        echo "<p class='error'>✗ Bảng contact_replies có vấn đề: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
    
    echo "<div class='box' style='background: #d4edda; border: 2px solid #28a745;'>";
    echo "<h2 style='color: #155724;'>✅ Hoàn tất!</h2>";
    echo "<p>Bây giờ bạn có thể:</p>";
    echo "<ul>";
    echo "<li><a href='admin/contacts.php' style='color: #FF6B35; font-weight: bold;'>Quay lại trang quản lý liên hệ</a></li>";
    echo "<li><a href='test-contact-reply.php' style='color: #FF6B35; font-weight: bold;'>Chạy test hệ thống</a></li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='box' style='background: #f8d7da; border: 2px solid #dc3545;'>";
    echo "<h2 style='color: #721c24;'>❌ Lỗi kết nối</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</body></html>";
?>
