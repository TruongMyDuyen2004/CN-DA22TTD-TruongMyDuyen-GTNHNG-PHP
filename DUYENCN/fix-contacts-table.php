<?php
/**
 * Thêm cột user_read_at vào bảng contacts
 */
require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<h2>🔧 Sửa bảng contacts</h2>";
echo "<pre style='background:#1e293b;color:#10b981;padding:20px;border-radius:10px;'>";

try {
    // Kiểm tra cột user_read_at
    echo "1. Kiểm tra cột user_read_at...\n";
    
    $stmt = $conn->query("SHOW COLUMNS FROM contacts LIKE 'user_read_at'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        echo "   Cột chưa tồn tại, đang thêm...\n";
        $conn->exec("ALTER TABLE contacts ADD COLUMN user_read_at DATETIME DEFAULT NULL");
        echo "   ✓ Đã thêm cột user_read_at\n";
    } else {
        echo "   ✓ Cột user_read_at đã tồn tại\n";
    }
    
    // Kiểm tra các cột khác cần thiết
    echo "\n2. Kiểm tra các cột khác...\n";
    
    $columns_to_check = [
        'admin_reply' => 'TEXT DEFAULT NULL',
        'replied_at' => 'DATETIME DEFAULT NULL',
        'replied_by' => 'INT DEFAULT NULL',
        'status' => "ENUM('pending','read','replied') DEFAULT 'pending'"
    ];
    
    foreach ($columns_to_check as $col => $definition) {
        $stmt = $conn->query("SHOW COLUMNS FROM contacts LIKE '$col'");
        if (!$stmt->fetch()) {
            echo "   Thêm cột $col...\n";
            try {
                $conn->exec("ALTER TABLE contacts ADD COLUMN $col $definition");
                echo "   ✓ Đã thêm $col\n";
            } catch (PDOException $e) {
                echo "   ⚠ Không thể thêm $col: " . $e->getMessage() . "\n";
            }
        } else {
            echo "   ✓ $col đã tồn tại\n";
        }
    }
    
    // Hiển thị cấu trúc bảng
    echo "\n3. Cấu trúc bảng contacts:\n";
    $stmt = $conn->query("DESCRIBE contacts");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "   - {$col['Field']}: {$col['Type']}\n";
    }
    
    echo "\n✅ Hoàn tất!\n";
    
} catch (PDOException $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<p><a href='index.php'>← Quay lại trang chủ</a></p>";
?>
