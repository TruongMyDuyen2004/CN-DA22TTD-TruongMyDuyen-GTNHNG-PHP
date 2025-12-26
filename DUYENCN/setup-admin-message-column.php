<?php
require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<h2>🔧 Setup Admin Message Column</h2>";

try {
    // Kiểm tra cột đã tồn tại chưa
    $stmt = $conn->query("SHOW COLUMNS FROM contacts LIKE 'is_admin_message'");
    $exists = $stmt->fetch();
    
    if ($exists) {
        echo "<p style='color: #22c55e;'>✅ Cột is_admin_message đã tồn tại.</p>";
    } else {
        // Thêm cột is_admin_message
        $conn->exec("ALTER TABLE contacts ADD COLUMN is_admin_message TINYINT(1) DEFAULT 0");
        echo "<p style='color: #22c55e;'>✅ Đã thêm cột is_admin_message thành công!</p>";
    }
    
    // Kiểm tra cột phone
    $stmt = $conn->query("SHOW COLUMNS FROM contacts LIKE 'phone'");
    if (!$stmt->fetch()) {
        $conn->exec("ALTER TABLE contacts ADD COLUMN phone VARCHAR(20) DEFAULT NULL");
        echo "<p style='color: #22c55e;'>✅ Đã thêm cột phone.</p>";
    }
    
    echo "<p style='color: #3b82f6;'>ℹ️ Hệ thống đã sẵn sàng cho phép admin và người dùng nhắn nhiều tin nhắn.</p>";
    echo "<p><a href='admin/contacts.php' style='color: #22c55e;'>→ Quay lại trang quản lý liên hệ</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: #ef4444;'>❌ Lỗi: " . $e->getMessage() . "</p>";
}
?>
