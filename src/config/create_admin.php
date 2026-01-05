<?php
// File này dùng để tạo tài khoản admin
// Chạy file này một lần để tạo tài khoản admin
// Sau đó xóa file này đi để bảo mật

require_once 'database.php';

$username = 'admin';
$password = 'admin123';
$email = 'admin@ngongallery.vn';

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Kiểm tra xem admin đã tồn tại chưa
    $stmt = $conn->prepare("SELECT id FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    
    if ($stmt->rowCount() > 0) {
        echo "Tài khoản admin đã tồn tại!<br>";
    } else {
        $stmt = $conn->prepare("INSERT INTO admins (username, password, email) VALUES (?, ?, ?)");
        $stmt->execute([$username, $hashed_password, $email]);
        echo "Tạo tài khoản admin thành công!<br>";
    }
    
    echo "<br>Thông tin đăng nhập:<br>";
    echo "Username: " . $username . "<br>";
    echo "Password: " . $password . "<br>";
    echo "<br><strong>Lưu ý: Xóa file này sau khi tạo tài khoản!</strong>";
    
} catch(PDOException $e) {
    echo "Lỗi: " . $e->getMessage();
}
?>
