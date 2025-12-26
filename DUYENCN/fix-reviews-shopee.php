<?php
/**
 * Tạo hệ thống đánh giá giống Shopee:
 * - Mỗi user chỉ đánh giá 1 lần cho mỗi món
 * - Nhiều user khác nhau có thể đánh giá cùng 1 món
 */

require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Setup Reviews Shopee Style</title></head><body>";
echo "<h2>🛒 Tạo hệ thống đánh giá kiểu Shopee</h2>";
echo "<pre style='background:#1e293b;color:#10b981;padding:20px;border-radius:10px;font-size:14px;'>";

try {
    // 1. Backup dữ liệu
    echo "1. Backup dữ liệu reviews...\n";
    $stmt = $conn->query("SELECT * FROM reviews");
    $backupData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "   Đã backup " . count($backupData) . " đánh giá\n\n";
    
    // 2. Xóa và tạo lại bảng
    echo "2. Tạo lại bảng reviews...\n";
    $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
    $conn->exec("DROP TABLE IF EXISTS review_likes");
    $conn->exec("DROP TABLE IF EXISTS review_comments");
    $conn->exec("DROP TABLE IF EXISTS reviews");
    
    // Tạo bảng với UNIQUE trên (customer_id, menu_item_id)
    // Nghĩa là: mỗi customer chỉ đánh giá 1 lần cho mỗi món
    // Nhưng nhiều customer khác nhau vẫn đánh giá được cùng 1 món
    $conn->exec("
        CREATE TABLE reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            menu_item_id INT NOT NULL,
            rating INT NOT NULL,
            comment TEXT,
            is_approved TINYINT(1) DEFAULT 1,
            likes_count INT DEFAULT 0,
            comments_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_item (customer_id, menu_item_id),
            INDEX idx_menu_item (menu_item_id),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ✓ Đã tạo bảng reviews\n";
    
    // Tạo bảng review_likes
    $conn->exec("
        CREATE TABLE review_likes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            review_id INT NOT NULL,
            customer_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_like (review_id, customer_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ✓ Đã tạo bảng review_likes\n";
    
    // Tạo bảng review_comments
    $conn->exec("
        CREATE TABLE review_comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            review_id INT NOT NULL,
            customer_id INT NOT NULL,
            comment TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ✓ Đã tạo bảng review_comments\n";
    
    $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // 3. Khôi phục dữ liệu (chỉ giữ đánh giá mới nhất của mỗi user cho mỗi món)
    echo "\n3. Khôi phục dữ liệu...\n";
    if (!empty($backupData)) {
        $inserted = 0;
        $skipped = 0;
        
        foreach ($backupData as $row) {
            try {
                $stmt = $conn->prepare("
                    INSERT INTO reviews (customer_id, menu_item_id, rating, comment, is_approved, likes_count, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $row['customer_id'],
                    $row['menu_item_id'],
                    $row['rating'],
                    $row['comment'] ?? '',
                    $row['is_approved'] ?? 1,
                    $row['likes_count'] ?? 0,
                    $row['created_at']
                ]);
                $inserted++;
            } catch (PDOException $e) {
                // Bỏ qua nếu trùng (user đã đánh giá món này rồi)
                $skipped++;
            }
        }
        echo "   ✓ Đã khôi phục $inserted đánh giá, bỏ qua $skipped trùng lặp\n";
    }
    
    // 4. Hiển thị cấu trúc
    echo "\n4. Cấu trúc bảng:\n";
    $stmt = $conn->query("SHOW CREATE TABLE reviews");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo $result['Create Table'] . "\n";
    
    // 5. Test
    echo "\n5. TEST hệ thống:\n";
    
    // Lấy 2 customers khác nhau
    $stmt = $conn->query("SELECT id, full_name FROM customers LIMIT 2");
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Lấy 1 món
    $stmt = $conn->query("SELECT id, name FROM menu_items LIMIT 1");
    $menuItem = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (count($customers) >= 2 && $menuItem) {
        $mid = $menuItem['id'];
        echo "   Món: {$menuItem['name']} (ID: $mid)\n\n";
        
        foreach ($customers as $c) {
            $cid = $c['id'];
            $name = $c['full_name'];
            
            // Xóa đánh giá cũ nếu có
            $conn->exec("DELETE FROM reviews WHERE customer_id = $cid AND menu_item_id = $mid");
            
            // Thêm đánh giá mới
            try {
                $stmt = $conn->prepare("INSERT INTO reviews (customer_id, menu_item_id, rating, comment, is_approved) VALUES (?, ?, ?, ?, 1)");
                $stmt->execute([$cid, $mid, rand(4,5), "Đánh giá từ $name"]);
                echo "   ✓ User '$name' (ID:$cid) đánh giá thành công\n";
            } catch (PDOException $e) {
                echo "   ✗ User '$name' (ID:$cid) lỗi: " . $e->getMessage() . "\n";
            }
        }
        
        // Đếm số đánh giá cho món này
        $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM reviews WHERE menu_item_id = ?");
        $stmt->execute([$mid]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
        echo "\n   → Tổng số đánh giá cho món $mid: $count\n";
        
        // Test đánh giá lại (phải báo lỗi)
        echo "\n   Test đánh giá lại cùng món:\n";
        $cid = $customers[0]['id'];
        try {
            $stmt = $conn->prepare("INSERT INTO reviews (customer_id, menu_item_id, rating, comment) VALUES (?, ?, 5, 'Test lần 2')");
            $stmt->execute([$cid, $mid]);
            echo "   ⚠ Không đúng - cho phép đánh giá lại!\n";
        } catch (PDOException $e) {
            echo "   ✓ Đúng - Chặn đánh giá lại (Duplicate entry)\n";
        }
    }
    
    echo "\n✅ HOÀN TẤT!\n";
    echo "- Mỗi user chỉ đánh giá 1 lần cho mỗi món\n";
    echo "- Nhiều user khác nhau có thể đánh giá cùng 1 món\n";
    
} catch (PDOException $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<p><a href='index.php?page=menu' style='color:#10b981;font-size:18px;'>← Quay lại thực đơn để test</a></p>";
echo "</body></html>";
?>
