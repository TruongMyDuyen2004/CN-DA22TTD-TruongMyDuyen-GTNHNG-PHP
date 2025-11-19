<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔧 Setup Menu Database</h2>";
echo "<hr>";

// Đọc thông tin database từ config
$config_file = 'config/database.php';
if (!file_exists($config_file)) {
    die("❌ File config/database.php không tồn tại!");
}

// Kết nối database
try {
    // Kết nối sử dụng config hiện tại
    require_once 'config/database.php';
    $db = new Database();
    $conn = $db->connect();
    
    echo "📡 Đang kết nối database...<br>";
    echo "✅ Kết nối thành công!<br><br>";
    
    // Đọc và thực thi file SQL
    $sql_file = 'config/setup_menu_database.sql';
    if (!file_exists($sql_file)) {
        die("❌ File SQL không tồn tại!");
    }
    
    $sql = file_get_contents($sql_file);
    
    echo "📝 Đang thực thi SQL...<br>";
    
    // Tách các câu lệnh SQL
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($statements as $statement) {
        if (empty(trim($statement))) continue;
        
        try {
            $conn->exec($statement);
            $success_count++;
        } catch (PDOException $e) {
            // Bỏ qua lỗi duplicate key
            if (strpos($e->getMessage(), 'Duplicate entry') === false) {
                echo "⚠️ Warning: " . $e->getMessage() . "<br>";
                $error_count++;
            }
        }
    }
    
    echo "<br>✅ Hoàn thành! Đã thực thi $success_count câu lệnh<br>";
    if ($error_count > 0) {
        echo "⚠️ Có $error_count cảnh báo<br>";
    }
    
    // Kiểm tra kết quả
    echo "<br><h3>📊 Thống kê:</h3>";
    
    $result = $conn->query("SELECT COUNT(*) as count FROM categories");
    $count = $result->fetch()['count'];
    echo "✓ Categories: $count<br>";
    
    $result = $conn->query("SELECT COUNT(*) as count FROM menu_items");
    $count = $result->fetch()['count'];
    echo "✓ Menu Items: $count<br>";
    
    $result = $conn->query("SELECT COUNT(*) as count FROM admins");
    $count = $result->fetch()['count'];
    echo "✓ Admins: $count<br>";
    
    $result = $conn->query("SELECT COUNT(*) as count FROM customers");
    $count = $result->fetch()['count'];
    echo "✓ Customers: $count<br>";
    
    $result = $conn->query("SELECT COUNT(*) as count FROM orders");
    $count = $result->fetch()['count'];
    echo "✓ Orders: $count<br>";
    
    $result = $conn->query("SELECT COUNT(*) as count FROM reviews");
    $count = $result->fetch()['count'];
    echo "✓ Reviews: $count<br>";
    
    echo "<br><h3>🎉 Setup thành công!</h3>";
    echo "<p><strong>Thông tin đăng nhập admin:</strong></p>";
    echo "<ul>";
    echo "<li>Username: <strong>admin</strong></li>";
    echo "<li>Password: <strong>admin123</strong></li>";
    echo "</ul>";
    
    echo "<br><a href='admin/login.php' style='display:inline-block;padding:10px 20px;background:#f97316;color:white;text-decoration:none;border-radius:8px;'>Đăng nhập Admin</a> ";
    echo "<a href='admin/menu.php' style='display:inline-block;padding:10px 20px;background:#059669;color:white;text-decoration:none;border-radius:8px;'>Quản lý Menu</a>";
    
} catch (PDOException $e) {
    echo "❌ Lỗi: " . $e->getMessage();
}
?>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    max-width: 800px;
    margin: 50px auto;
    padding: 20px;
    background: #f5f5f5;
}
h2 {
    color: #f97316;
}
</style>
