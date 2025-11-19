<?php
require_once 'config/database.php';

try {
    $db = new Database();
    $conn = $db->connect();
    $conn->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    
    // Đọc file SQL
    $sql = file_get_contents('config/setup_menu_database.sql');
    
    // Tách các câu lệnh SQL
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    // Thực thi từng câu lệnh
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $conn->exec($statement);
                echo "✅ Executed: " . substr($statement, 0, 50) . "...\n<br>";
            } catch (PDOException $e) {
                echo "⚠️ Warning: " . $e->getMessage() . "\n<br>";
            }
        }
    }
    
    echo "\n<br><strong>✅ Database setup completed!</strong>\n<br>";
    
    // Kiểm tra bảng promotions
    $stmt = $conn->query("SHOW TABLES LIKE 'promotions'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Bảng promotions đã được tạo thành công!\n<br>";
        
        $count = $conn->query("SELECT COUNT(*) FROM promotions")->fetchColumn();
        echo "📊 Số lượng khuyến mãi: $count\n<br>";
    } else {
        echo "❌ Bảng promotions chưa được tạo!\n<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
