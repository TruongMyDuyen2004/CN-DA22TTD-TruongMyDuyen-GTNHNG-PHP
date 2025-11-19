<?php
/**
 * Script cập nhật bảng reviews
 * Chạy file này để thêm cột is_approved và các index cần thiết
 */

require_once 'database.php';

try {
    $db = new Database();
    $conn = $db->connect();
    
    echo "Đang cập nhật bảng reviews...\n\n";
    
    // Kiểm tra và thêm cột is_approved
    $stmt = $conn->query("SHOW COLUMNS FROM reviews LIKE 'is_approved'");
    if ($stmt->rowCount() == 0) {
        echo "- Thêm cột is_approved...\n";
        $conn->exec("ALTER TABLE reviews ADD COLUMN is_approved BOOLEAN DEFAULT TRUE");
        echo "  ✓ Đã thêm cột is_approved\n";
    } else {
        echo "  ✓ Cột is_approved đã tồn tại\n";
    }
    
    // Kiểm tra và thêm cột updated_at
    $stmt = $conn->query("SHOW COLUMNS FROM reviews LIKE 'updated_at'");
    if ($stmt->rowCount() == 0) {
        echo "- Thêm cột updated_at...\n";
        $conn->exec("ALTER TABLE reviews ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        echo "  ✓ Đã thêm cột updated_at\n";
    } else {
        echo "  ✓ Cột updated_at đã tồn tại\n";
    }
    
    // Cập nhật các đánh giá cũ thành approved
    echo "- Cập nhật trạng thái đánh giá cũ...\n";
    $stmt = $conn->exec("UPDATE reviews SET is_approved = TRUE WHERE is_approved IS NULL");
    echo "  ✓ Đã cập nhật {$stmt} đánh giá\n";
    
    // Thêm các index
    echo "- Thêm các index...\n";
    
    try {
        $conn->exec("ALTER TABLE reviews ADD INDEX idx_menu_item (menu_item_id)");
        echo "  ✓ Đã thêm index idx_menu_item\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "  ✓ Index idx_menu_item đã tồn tại\n";
        } else {
            throw $e;
        }
    }
    
    try {
        $conn->exec("ALTER TABLE reviews ADD INDEX idx_customer (customer_id)");
        echo "  ✓ Đã thêm index idx_customer\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "  ✓ Index idx_customer đã tồn tại\n";
        } else {
            throw $e;
        }
    }
    
    try {
        $conn->exec("ALTER TABLE reviews ADD INDEX idx_approved (is_approved)");
        echo "  ✓ Đã thêm index idx_approved\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "  ✓ Index idx_approved đã tồn tại\n";
        } else {
            throw $e;
        }
    }
    
    try {
        $conn->exec("ALTER TABLE reviews ADD INDEX idx_created (created_at)");
        echo "  ✓ Đã thêm index idx_created\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "  ✓ Index idx_created đã tồn tại\n";
        } else {
            throw $e;
        }
    }
    
    echo "\n✅ Cập nhật bảng reviews thành công!\n";
    echo "\nBạn có thể truy cập trang quản lý đánh giá tại: admin/reviews.php\n";
    
} catch (PDOException $e) {
    echo "\n❌ Lỗi: " . $e->getMessage() . "\n";
    exit(1);
}
?>
