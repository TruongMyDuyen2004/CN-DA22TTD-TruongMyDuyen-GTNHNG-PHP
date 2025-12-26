<?php
/**
 * Script sửa bảng reviews - Xóa tất cả UNIQUE constraint
 * Cho phép nhiều người dùng đánh giá cùng một món
 */

require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Fix Reviews</title></head><body>";
echo "<h2>🔧 Sửa bảng reviews</h2>";
echo "<pre style='background:#1e293b;color:#10b981;padding:20px;border-radius:10px;font-size:14px;'>";

try {
    // 1. Hiển thị cấu trúc hiện tại
    echo "=== CẤU TRÚC BẢNG REVIEWS HIỆN TẠI ===\n\n";
    $stmt = $conn->query("SHOW CREATE TABLE reviews");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo $result['Create Table'] . "\n\n";
    
    // 2. Lấy tất cả indexes
    echo "=== DANH SÁCH INDEX ===\n";
    $stmt = $conn->query("SHOW INDEX FROM reviews");
    $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $uniqueIndexes = [];
    foreach ($indexes as $idx) {
        $isUnique = $idx['Non_unique'] == 0 ? 'UNIQUE' : 'INDEX';
        echo "{$idx['Key_name']} - {$idx['Column_name']} ({$isUnique})\n";
        
        if ($idx['Non_unique'] == 0 && $idx['Key_name'] !== 'PRIMARY') {
            $uniqueIndexes[$idx['Key_name']] = true;
        }
    }
    
    // 3. Xóa tất cả UNIQUE indexes (trừ PRIMARY)
    echo "\n=== XÓA UNIQUE CONSTRAINTS ===\n";
    if (empty($uniqueIndexes)) {
        echo "Không có UNIQUE constraint nào cần xóa.\n";
    } else {
        foreach (array_keys($uniqueIndexes) as $indexName) {
            echo "Đang xóa: $indexName ... ";
            try {
                $conn->exec("ALTER TABLE reviews DROP INDEX `$indexName`");
                echo "✓ OK\n";
            } catch (PDOException $e) {
                echo "✗ Lỗi: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // 4. Kiểm tra lại
    echo "\n=== CẤU TRÚC SAU KHI SỬA ===\n\n";
    $stmt = $conn->query("SHOW CREATE TABLE reviews");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo $result['Create Table'] . "\n\n";
    
    // 5. Test thêm đánh giá
    echo "=== TEST THÊM ĐÁNH GIÁ ===\n";
    
    // Lấy một customer và menu_item để test
    $stmt = $conn->query("SELECT id FROM customers LIMIT 1");
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $conn->query("SELECT id FROM menu_items LIMIT 1");
    $menuItem = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($customer && $menuItem) {
        $customerId = $customer['id'];
        $menuItemId = $menuItem['id'];
        
        echo "Test với customer_id=$customerId, menu_item_id=$menuItemId\n";
        
        // Thử thêm 2 đánh giá từ cùng 1 user cho cùng 1 món
        try {
            $stmt = $conn->prepare("INSERT INTO reviews (customer_id, menu_item_id, rating, comment, is_approved) VALUES (?, ?, 5, 'Test review 1', 1)");
            $stmt->execute([$customerId, $menuItemId]);
            echo "✓ Thêm đánh giá 1 thành công (ID: " . $conn->lastInsertId() . ")\n";
            
            $stmt = $conn->prepare("INSERT INTO reviews (customer_id, menu_item_id, rating, comment, is_approved) VALUES (?, ?, 4, 'Test review 2', 1)");
            $stmt->execute([$customerId, $menuItemId]);
            echo "✓ Thêm đánh giá 2 thành công (ID: " . $conn->lastInsertId() . ")\n";
            
            echo "\n✅ THÀNH CÔNG! Nhiều người có thể đánh giá cùng 1 món.\n";
            
        } catch (PDOException $e) {
            echo "✗ Lỗi khi thêm đánh giá: " . $e->getMessage() . "\n";
            echo "\n⚠️ Có thể vẫn còn constraint. Kiểm tra lại database.\n";
        }
    }
    
    // 6. Thống kê
    echo "\n=== THỐNG KÊ ===\n";
    $stmt = $conn->query("SELECT COUNT(*) as total FROM reviews");
    echo "Tổng số đánh giá: " . $stmt->fetch(PDO::FETCH_ASSOC)['total'] . "\n";
    
} catch (PDOException $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<p><a href='index.php?page=menu'>← Quay lại thực đơn</a></p>";
echo "</body></html>";
?>
