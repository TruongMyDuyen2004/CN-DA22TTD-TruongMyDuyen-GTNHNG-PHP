<?php
/**
 * Script để cho phép nhiều người dùng đánh giá cùng một món ăn
 * Xóa UNIQUE constraint trên (customer_id, menu_item_id) nếu có
 */

require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<h2>🔧 Sửa bảng reviews - Cho phép nhiều người đánh giá</h2>";
echo "<pre style='background:#1e293b;color:#10b981;padding:20px;border-radius:10px;'>";

try {
    // 1. Kiểm tra cấu trúc bảng reviews
    echo "1. Kiểm tra cấu trúc bảng reviews...\n";
    $stmt = $conn->query("SHOW CREATE TABLE reviews");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Cấu trúc hiện tại:\n";
    echo $result['Create Table'] . "\n\n";
    
    // 2. Tìm và xóa các UNIQUE constraint liên quan đến customer_id và menu_item_id
    echo "2. Tìm UNIQUE constraints...\n";
    $stmt = $conn->query("SHOW INDEX FROM reviews WHERE Non_unique = 0");
    $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $constraintsToRemove = [];
    foreach ($indexes as $idx) {
        $keyName = $idx['Key_name'];
        $columnName = $idx['Column_name'];
        
        // Bỏ qua PRIMARY KEY
        if ($keyName === 'PRIMARY') continue;
        
        // Tìm các constraint có chứa customer_id hoặc menu_item_id
        if (strpos($columnName, 'customer_id') !== false || 
            strpos($columnName, 'menu_item_id') !== false ||
            strpos($keyName, 'customer') !== false ||
            strpos($keyName, 'menu') !== false ||
            strpos($keyName, 'unique') !== false) {
            $constraintsToRemove[$keyName] = true;
        }
    }
    
    if (empty($constraintsToRemove)) {
        echo "✓ Không tìm thấy UNIQUE constraint cần xóa.\n";
    } else {
        echo "Tìm thấy " . count($constraintsToRemove) . " constraint(s) cần xóa:\n";
        foreach (array_keys($constraintsToRemove) as $keyName) {
            echo "  - $keyName\n";
            try {
                $conn->exec("ALTER TABLE reviews DROP INDEX `$keyName`");
                echo "    ✓ Đã xóa $keyName\n";
            } catch (PDOException $e) {
                echo "    ⚠ Không thể xóa $keyName: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // 3. Kiểm tra lại cấu trúc sau khi sửa
    echo "\n3. Cấu trúc sau khi sửa:\n";
    $stmt = $conn->query("SHOW CREATE TABLE reviews");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo $result['Create Table'] . "\n\n";
    
    // 4. Thống kê
    echo "4. Thống kê đánh giá:\n";
    $stmt = $conn->query("SELECT COUNT(*) as total FROM reviews");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "   - Tổng số đánh giá: $total\n";
    
    $stmt = $conn->query("
        SELECT menu_item_id, COUNT(*) as review_count 
        FROM reviews 
        GROUP BY menu_item_id 
        HAVING review_count > 1 
        ORDER BY review_count DESC 
        LIMIT 5
    ");
    $multiReviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($multiReviews)) {
        echo "   - Món có nhiều đánh giá:\n";
        foreach ($multiReviews as $row) {
            echo "     + Món #{$row['menu_item_id']}: {$row['review_count']} đánh giá\n";
        }
    }
    
    echo "\n✅ Hoàn tất! Bây giờ nhiều người dùng có thể đánh giá cùng một món.\n";
    
} catch (PDOException $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<p><a href='index.php?page=menu'>← Quay lại thực đơn</a></p>";
?>
