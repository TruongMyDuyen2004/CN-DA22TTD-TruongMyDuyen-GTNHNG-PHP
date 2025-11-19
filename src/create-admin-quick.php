<?php
require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

// Tạo admin với thông tin mặc định
$username = 'admin';
$password = password_hash('admin123', PASSWORD_DEFAULT);
$email = 'admin@ngongallery.com';

try {
    // Kiểm tra bảng admins có tồn tại không
    $stmt = $conn->query("SHOW TABLES LIKE 'admins'");
    
    if ($stmt->rowCount() == 0) {
        // Tạo bảng admins
        $conn->exec("
            CREATE TABLE admins (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                full_name VARCHAR(100),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        echo "✅ Đã tạo bảng admins<br>";
    }
    
    // Thêm admin
    $stmt = $conn->prepare("INSERT INTO admins (username, password, email, full_name) VALUES (?, ?, ?, ?)");
    $stmt->execute([$username, $password, $email, 'Administrator']);
    
    echo "<h2>✅ Tạo tài khoản admin thành công!</h2>";
    echo "<p><strong>Username:</strong> admin</p>";
    echo "<p><strong>Password:</strong> admin123</p>";
    echo "<p><strong>Email:</strong> admin@ngongallery.com</p>";
    echo "<br>";
    echo "<a href='admin/login.php' style='display:inline-block; padding:12px 24px; background:#f97316; color:white; text-decoration:none; border-radius:8px;'>Đăng nhập Admin</a>";
    
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        echo "<h2>⚠️ Tài khoản admin đã tồn tại</h2>";
        echo "<p><strong>Username:</strong> admin</p>";
        echo "<p><strong>Password:</strong> admin123</p>";
        echo "<br>";
        echo "<a href='admin/login.php' style='display:inline-block; padding:12px 24px; background:#f97316; color:white; text-decoration:none; border-radius:8px;'>Đăng nhập Admin</a>";
    } else {
        echo "<h2>❌ Lỗi: " . $e->getMessage() . "</h2>";
    }
}
?>
