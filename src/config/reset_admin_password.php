<?php
/**
 * File reset mật khẩu admin
 * Chạy file này để reset mật khẩu admin về mặc định
 * URL: http://localhost/your-project/config/reset_admin_password.php
 */

require_once 'database.php';

$username = 'admin';
$new_password = '123';  // Mật khẩu mới
$email = 'admin@ngongallery.vn';

$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Kiểm tra xem admin có tồn tại không
    $stmt = $conn->prepare("SELECT id FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    
    if ($stmt->rowCount() > 0) {
        // Cập nhật mật khẩu
        $stmt = $conn->prepare("UPDATE admins SET password = ?, email = ? WHERE username = ?");
        $stmt->execute([$hashed_password, $email, $username]);
        echo "<h2>✅ Reset mật khẩu thành công!</h2>";
    } else {
        // Tạo tài khoản mới
        $stmt = $conn->prepare("INSERT INTO admins (username, password, email) VALUES (?, ?, ?)");
        $stmt->execute([$username, $hashed_password, $email]);
        echo "<h2>✅ Tạo tài khoản admin thành công!</h2>";
    }
    
    echo "<div style='background: #e8f5e9; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h3>📋 Thông tin đăng nhập:</h3>";
    echo "<p><strong>Username:</strong> " . htmlspecialchars($username) . "</p>";
    echo "<p><strong>Password:</strong> " . htmlspecialchars($new_password) . "</p>";
    echo "<p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>";
    echo "</div>";
    
    echo "<div style='background: #fff3e0; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h3>⚠️ Lưu ý bảo mật:</h3>";
    echo "<p>1. Đổi mật khẩu ngay sau khi đăng nhập</p>";
    echo "<p>2. Xóa hoặc đổi tên file này sau khi sử dụng</p>";
    echo "<p>3. Không để file này trên server production</p>";
    echo "</div>";
    
    echo "<div style='margin: 20px 0;'>";
    echo "<a href='../admin/login.php' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Đăng nhập Admin</a>";
    echo "</div>";
    
} catch(PDOException $e) {
    echo "<div style='background: #ffebee; padding: 20px; border-radius: 10px; color: #c62828;'>";
    echo "<h3>❌ Lỗi:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>