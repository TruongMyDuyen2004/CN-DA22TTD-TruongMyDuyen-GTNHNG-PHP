<?php
/**
 * Setup Google Login - Cập nhật database
 * Chạy file này một lần để thêm các cột cần thiết
 */

require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<h2>🔧 Setup Google Login</h2>";

try {
    // Thêm cột google_id
    try {
        $conn->exec("ALTER TABLE customers ADD COLUMN google_id VARCHAR(255) DEFAULT NULL");
        echo "<p>✅ Đã thêm cột google_id</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<p>ℹ️ Cột google_id đã tồn tại</p>";
        } else {
            throw $e;
        }
    }
    
    // Thêm cột avatar
    try {
        $conn->exec("ALTER TABLE customers ADD COLUMN avatar VARCHAR(500) DEFAULT NULL");
        echo "<p>✅ Đã thêm cột avatar</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<p>ℹ️ Cột avatar đã tồn tại</p>";
        } else {
            throw $e;
        }
    }
    
    // Tạo index cho google_id
    try {
        $conn->exec("CREATE INDEX idx_google_id ON customers(google_id)");
        echo "<p>✅ Đã tạo index cho google_id</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key') !== false) {
            echo "<p>ℹ️ Index đã tồn tại</p>";
        } else {
            // Bỏ qua lỗi index
        }
    }
    
    echo "<hr>";
    echo "<h3>✅ Setup hoàn tất!</h3>";
    echo "<p>Bây giờ bạn cần:</p>";
    echo "<ol>";
    echo "<li>Truy cập <a href='https://console.cloud.google.com/' target='_blank'>Google Cloud Console</a></li>";
    echo "<li>Tạo project mới hoặc chọn project có sẵn</li>";
    echo "<li>Vào <strong>APIs & Services > Credentials</strong></li>";
    echo "<li>Click <strong>Create Credentials > OAuth client ID</strong></li>";
    echo "<li>Chọn <strong>Web application</strong></li>";
    echo "<li>Thêm Authorized redirect URI: <code>http://localhost/DUYENCN/auth/google-callback.php</code></li>";
    echo "<li>Copy <strong>Client ID</strong> và <strong>Client Secret</strong></li>";
    echo "<li>Mở file <code>config/google-oauth.php</code> và thay thế các giá trị</li>";
    echo "</ol>";
    
} catch (PDOException $e) {
    echo "<p>❌ Lỗi: " . $e->getMessage() . "</p>";
}
?>
