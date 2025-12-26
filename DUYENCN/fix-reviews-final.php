<?php
/**
 * Tạo bảng reviews cho phép:
 * - Nhiều user đánh giá cùng 1 món
 * - Mỗi user đánh giá nhiều lần
 * - Giữ tất cả đánh giá (chỉ admin xóa)
 */

require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Fix Reviews</title></head><body>";
echo "<h2>🔧 Tạo bảng reviews - Cho phép đánh giá tự do</h2>";
echo "<pre style='background:#1e293b;color:#10b981;padding:20px;border-radius:10px;font-size:14px;'>";

try {
    // 1. Backup dữ liệu hiện tại
    echo "1. Backup dữ liệu reviews hiện tại...\n";
    $stmt = $conn->query("SELECT * FROM reviews");
    $backupData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "   Đã backup " . count($backupData) . " đánh giá\n\n";
    
    // 2. Xóa bảng cũ
    echo "2. Xóa bảng reviews cũ...\n";
    
    // Xóa foreign key constraints từ các bảng khác trước
    try {
        $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
        $conn->exec("DROP TABLE IF EXISTS review_likes");
        $conn->exec("DROP TABLE IF EXISTS review_comments");
        $conn->exec("DROP TABLE IF EXISTS reviews");
        $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
        echo "   ✓ Đã xóa bảng cũ\n\n";
    } catch (PDOException $e) {
        echo "   ⚠ " . $e->getMessage() . "\n\n";
    }
    
    // 3. Tạo bảng mới KHÔNG có UNIQUE constraint
    echo "3. Tạo bảng reviews mới (KHÔNG có UNIQUE constraint)...\n";
    $conn->exec("
        CREATE TABLE reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            menu_item_id INT NOT NULL,
            rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
            comment TEXT,
            is_approved TINYINT(1) DEFAULT 1,
            likes_count INT DEFAULT 0,
            comments_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_customer (customer_id),
            INDEX idx_menu_item (menu_item_id),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ✓ Đã tạo bảng mới\n\n";
    
    // 4. Tạo lại bảng review_likes
    echo "4. Tạo bảng review_likes...\n";
    $conn->exec("
        CREATE TABLE review_likes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            review_id INT NOT NULL,
            customer_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_like (review_id, customer_id),
            INDEX idx_review (review_id),
            INDEX idx_customer (customer_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ✓ Đã tạo bảng review_likes\n\n";
    
    // 5. Tạo lại bảng review_comments
    echo "5. Tạo bảng review_comments...\n";
    $conn->exec("
        CREATE TABLE review_comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            review_id INT NOT NULL,
            customer_id INT NOT NULL,
            comment TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_review (review_id),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ✓ Đã tạo bảng review_comments\n\n";
    
    // 6. Khôi phục dữ liệu
    echo "6. Khôi phục dữ liệu...\n";
    if (!empty($backupData)) {
        $stmt = $conn->prepare("
            INSERT INTO reviews (id, customer_id, menu_item_id, rating, comment, is_approved, likes_count, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($backupData as $row) {
            $stmt->execute([
                $row['id'],
                $row['customer_id'],
                $row['menu_item_id'],
                $row['rating'],
                $row['comment'] ?? '',
                $row['is_approved'] ?? 1,
                $row['likes_count'] ?? 0,
                $row['created_at']
            ]);
        }
        echo "   ✓ Đã khôi phục " . count($backupData) . " đánh giá\n\n";
    } else {
        echo "   Không có dữ liệu cần khôi phục\n\n";
    }
    
    // 7. Kiểm tra cấu trúc mới
    echo "7. Cấu trúc bảng mới:\n";
    $stmt = $conn->query("SHOW CREATE TABLE reviews");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo $result['Create Table'] . "\n\n";
    
    // 8. Test thêm nhiều đánh giá
    echo "8. TEST: Thêm nhiều đánh giá từ cùng 1 user cho cùng 1 món...\n";
    
    $stmt = $conn->query("SELECT id FROM customers LIMIT 1");
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $conn->query("SELECT id FROM menu_items LIMIT 1");
    $menuItem = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($customer && $menuItem) {
        $cid = $customer['id'];
        $mid = $menuItem['id'];
        
        // Thêm 3 đánh giá test
        for ($i = 1; $i <= 3; $i++) {
            $stmt = $conn->prepare("INSERT INTO reviews (customer_id, menu_item_id, rating, comment, is_approved) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$cid, $mid, rand(3,5), "Test đánh giá lần $i - " . date('H:i:s')]);
            echo "   ✓ Đánh giá $i: OK (ID: " . $conn->lastInsertId() . ")\n";
        }
        
        // Đếm số đánh giá
        $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM reviews WHERE customer_id = ? AND menu_item_id = ?");
        $stmt->execute([$cid, $mid]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
        echo "\n   Tổng số đánh giá của user $cid cho món $mid: $count\n";
    }
    
    echo "\n✅ HOÀN TẤT! Bây giờ nhiều người có thể đánh giá cùng 1 món, mỗi người có thể đánh giá nhiều lần.\n";
    
} catch (PDOException $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<p><a href='index.php?page=menu' style='color:#10b981;'>← Quay lại thực đơn để test</a></p>";
echo "</body></html>";
?>
