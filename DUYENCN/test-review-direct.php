<?php
/**
 * Test trực tiếp thêm đánh giá từ 2 user khác nhau cho cùng 1 món
 */
session_start();
require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body>";
echo "<h2>🧪 Test đánh giá từ nhiều user</h2>";
echo "<pre style='background:#1e293b;color:#10b981;padding:20px;border-radius:10px;'>";

try {
    // Lấy thông tin user đang đăng nhập
    echo "=== THÔNG TIN SESSION ===\n";
    $currentUserId = $_SESSION['customer_id'] ?? null;
    echo "User đang đăng nhập: " . ($currentUserId ? "ID = $currentUserId" : "CHƯA ĐĂNG NHẬP") . "\n\n";
    
    // Lấy 2 customers
    echo "=== LẤY 2 CUSTOMERS ===\n";
    $stmt = $conn->query("SELECT id, full_name, email FROM customers LIMIT 3");
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($customers as $c) {
        echo "- ID: {$c['id']} | {$c['full_name']} | {$c['email']}\n";
    }
    
    // Lấy 1 món để test
    echo "\n=== CHỌN MÓN ĐỂ TEST ===\n";
    $stmt = $conn->query("SELECT id, name FROM menu_items WHERE id = 1 OR name LIKE '%chocolate%' LIMIT 1");
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$item) {
        $stmt = $conn->query("SELECT id, name FROM menu_items LIMIT 1");
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    echo "Món: {$item['name']} (ID: {$item['id']})\n";
    $menuItemId = $item['id'];
    
    // Xóa tất cả đánh giá cũ cho món này
    echo "\n=== XÓA ĐÁNH GIÁ CŨ ===\n";
    $conn->exec("DELETE FROM reviews WHERE menu_item_id = $menuItemId");
    echo "Đã xóa tất cả đánh giá cho món $menuItemId\n";
    
    // Thêm đánh giá từ mỗi customer
    echo "\n=== THÊM ĐÁNH GIÁ TỪ NHIỀU USER ===\n";
    
    foreach ($customers as $c) {
        $cid = $c['id'];
        $name = $c['full_name'];
        
        try {
            $stmt = $conn->prepare("
                INSERT INTO reviews (customer_id, menu_item_id, rating, comment, is_approved)
                VALUES (?, ?, ?, ?, 1)
            ");
            $stmt->execute([$cid, $menuItemId, rand(4,5), "Đánh giá từ $name - " . date('H:i:s')]);
            echo "✓ User '$name' (ID:$cid) đánh giá thành công!\n";
        } catch (PDOException $e) {
            echo "✗ User '$name' (ID:$cid) THẤT BẠI: " . $e->getMessage() . "\n";
        }
    }
    
    // Kiểm tra kết quả
    echo "\n=== KẾT QUẢ ===\n";
    $stmt = $conn->prepare("
        SELECT r.*, c.full_name 
        FROM reviews r 
        JOIN customers c ON r.customer_id = c.id 
        WHERE r.menu_item_id = ?
    ");
    $stmt->execute([$menuItemId]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Tổng số đánh giá cho món $menuItemId: " . count($reviews) . "\n\n";
    foreach ($reviews as $r) {
        echo "- ID:{$r['id']} | User:{$r['full_name']} | Rating:{$r['rating']} | {$r['comment']}\n";
    }
    
    if (count($reviews) >= 2) {
        echo "\n✅ THÀNH CÔNG! Nhiều user có thể đánh giá cùng 1 món.\n";
    } else {
        echo "\n❌ THẤT BẠI! Chỉ có " . count($reviews) . " đánh giá.\n";
    }
    
    // Kiểm tra indexes
    echo "\n=== KIỂM TRA INDEXES ===\n";
    $stmt = $conn->query("SHOW INDEX FROM reviews WHERE Non_unique = 0");
    $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($indexes as $idx) {
        echo "{$idx['Key_name']} - {$idx['Column_name']} (UNIQUE)\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<p><a href='index.php?page=menu-item-detail&id=" . ($menuItemId ?? 1) . "'>Xem món vừa test</a></p>";
echo "</body></html>";
?>
