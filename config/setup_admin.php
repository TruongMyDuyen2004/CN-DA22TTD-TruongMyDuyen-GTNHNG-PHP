<?php
/**
 * Setup Admin Account
 * Truy cập: http://localhost/DUYENCN/config/setup_admin.php
 */

require_once 'database.php';

echo "<!DOCTYPE html>";
echo "<html lang='vi'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>Setup Admin - Ngon Gallery</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }";
echo ".success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }";
echo ".error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }";
echo ".info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }";
echo ".btn { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<h1>🔧 Setup Admin Account</h1>";

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Thông tin admin
    $username = 'admin';
    $password = '123';
    $email = 'admin@ngongallery.vn';
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Kiểm tra admin đã tồn tại chưa
    $stmt = $conn->prepare("SELECT id, username FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        // Cập nhật mật khẩu
        $stmt = $conn->prepare("UPDATE admins SET password = ?, email = ? WHERE username = ?");
        $stmt->execute([$hashed_password, $email, $username]);
        
        echo "<div class='success'>";
        echo "<h2>✅ Cập nhật mật khẩu thành công!</h2>";
        echo "<p>Tài khoản admin đã được reset mật khẩu.</p>";
        echo "</div>";
    } else {
        // Tạo admin mới
        $stmt = $conn->prepare("INSERT INTO admins (username, password, email) VALUES (?, ?, ?)");
        $stmt->execute([$username, $hashed_password, $email]);
        
        echo "<div class='success'>";
        echo "<h2>✅ Tạo tài khoản admin thành công!</h2>";
        echo "<p>Tài khoản admin mới đã được tạo.</p>";
        echo "</div>";
    }
    
    // Hiển thị thông tin đăng nhập
    echo "<div class='info'>";
    echo "<h3>📋 Thông tin đăng nhập:</h3>";
    echo "<table style='width: 100%; border-collapse: collapse;'>";
    echo "<tr><td style='padding: 10px; border: 1px solid #ddd;'><strong>Username:</strong></td><td style='padding: 10px; border: 1px solid #ddd;'>" . htmlspecialchars($username) . "</td></tr>";
    echo "<tr><td style='padding: 10px; border: 1px solid #ddd;'><strong>Password:</strong></td><td style='padding: 10px; border: 1px solid #ddd;'>" . htmlspecialchars($password) . "</td></tr>";
    echo "<tr><td style='padding: 10px; border: 1px solid #ddd;'><strong>Email:</strong></td><td style='padding: 10px; border: 1px solid #ddd;'>" . htmlspecialchars($email) . "</td></tr>";
    echo "</table>";
    echo "</div>";
    
    // Kiểm tra lại trong database
    $stmt = $conn->prepare("SELECT id, username, email, created_at FROM admins");
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='info'>";
    echo "<h3>👥 Danh sách Admin trong database:</h3>";
    echo "<table style='width: 100%; border-collapse: collapse;'>";
    echo "<tr style='background: #f8f9fa;'>";
    echo "<th style='padding: 10px; border: 1px solid #ddd; text-align: left;'>ID</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd; text-align: left;'>Username</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd; text-align: left;'>Email</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd; text-align: left;'>Created At</th>";
    echo "</tr>";
    
    foreach ($admins as $admin) {
        echo "<tr>";
        echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . $admin['id'] . "</td>";
        echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . htmlspecialchars($admin['username']) . "</td>";
        echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . htmlspecialchars($admin['email']) . "</td>";
        echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . $admin['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    
    echo "<div style='margin: 20px 0;'>";
    echo "<a href='../admin/login.php' class='btn'>🔐 Đăng nhập Admin</a>";
    echo "<a href='../index.php' class='btn' style='background: #6c757d;'>🏠 Trang chủ</a>";
    echo "</div>";
    
    echo "<div class='error'>";
    echo "<h3>⚠️ Cảnh báo bảo mật:</h3>";
    echo "<ul>";
    echo "<li>Xóa file này sau khi setup xong</li>";
    echo "<li>Đổi mật khẩu ngay sau khi đăng nhập</li>";
    echo "<li>Không để file này trên server production</li>";
    echo "</ul>";
    echo "</div>";
    
} catch(PDOException $e) {
    echo "<div class='error'>";
    echo "<h2>❌ Lỗi kết nối database:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Kiểm tra:</strong></p>";
    echo "<ul>";
    echo "<li>XAMPP đã chạy chưa?</li>";
    echo "<li>MySQL service đã start chưa?</li>";
    echo "<li>Database 'ngon_gallery' đã được tạo chưa?</li>";
    echo "<li>Thông tin kết nối trong config/database.php đúng chưa?</li>";
    echo "</ul>";
    echo "</div>";
}

echo "</body>";
echo "</html>";
?>
