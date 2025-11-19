<?php
require_once 'config/database.php';

try {
    $db = new Database();
    $conn = $db->connect();
    
    echo "🔧 Đang kiểm tra và sửa lỗi foreign key...\n\n";
    
    // Kiểm tra các foreign key hiện tại
    echo "📋 Kiểm tra foreign key hiện tại:\n";
    $stmt = $conn->query("
        SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'reviews'
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($constraints as $constraint) {
        echo "- " . $constraint['CONSTRAINT_NAME'] . ": " . $constraint['COLUMN_NAME'] . " -> " . $constraint['REFERENCED_TABLE_NAME'] . "." . $constraint['REFERENCED_COLUMN_NAME'] . "\n";
    }
    
    echo "\n🗑️ Xóa foreign key cũ...\n";
    
    // Xóa tất cả foreign key của bảng reviews
    foreach ($constraints as $constraint) {
        try {
            $conn->exec("ALTER TABLE reviews DROP FOREIGN KEY " . $constraint['CONSTRAINT_NAME']);
            echo "✅ Đã xóa: " . $constraint['CONSTRAINT_NAME'] . "\n";
        } catch (Exception $e) {
            echo "⚠️ Không thể xóa " . $constraint['CONSTRAINT_NAME'] . ": " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n✨ Tạo lại foreign key với cấu hình đúng...\n";
    
    // Tạo lại foreign key với ON DELETE CASCADE
    try {
        $conn->exec("
            ALTER TABLE reviews 
            ADD CONSTRAINT fk_reviews_customer 
            FOREIGN KEY (customer_id) 
            REFERENCES customers(id) 
            ON DELETE CASCADE
        ");
        echo "✅ Đã tạo foreign key cho customer_id\n";
    } catch (Exception $e) {
        echo "⚠️ Lỗi tạo FK customer_id: " . $e->getMessage() . "\n";
    }
    
    try {
        $conn->exec("
            ALTER TABLE reviews 
            ADD CONSTRAINT fk_reviews_menu_item 
            FOREIGN KEY (menu_item_id) 
            REFERENCES menu_items(id) 
            ON DELETE CASCADE
        ");
        echo "✅ Đã tạo foreign key cho menu_item_id\n";
    } catch (Exception $e) {
        echo "⚠️ Lỗi tạo FK menu_item_id: " . $e->getMessage() . "\n";
    }
    
    echo "\n🎉 Hoàn thành! Bây giờ bạn có thể đánh giá món ăn.\n";
    
} catch (Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage();
}
?>
