<?php
require_once 'database.php';

try {
    $db = new Database();
    $conn = $db->connect();
    
    echo "Đang kiểm tra và tạo các bảng cần thiết...\n\n";
    
    // 1. Tạo bảng review_likes nếu chưa có
    echo "1. Kiểm tra bảng review_likes...\n";
    $sql = "CREATE TABLE IF NOT EXISTS review_likes (
        id INT PRIMARY KEY AUTO_INCREMENT,
        review_id INT NOT NULL,
        customer_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_like (review_id, customer_id),
        FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
        INDEX idx_review_id (review_id),
        INDEX idx_customer_id (customer_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "✓ Bảng review_likes đã sẵn sàng\n\n";
    
    // 2. Tạo bảng review_comments nếu chưa có
    echo "2. Kiểm tra bảng review_comments...\n";
    $sql = "CREATE TABLE IF NOT EXISTS review_comments (
        id INT PRIMARY KEY AUTO_INCREMENT,
        review_id INT NOT NULL,
        customer_id INT NOT NULL,
        comment TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
        INDEX idx_review_id (review_id),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "✓ Bảng review_comments đã sẵn sàng\n\n";
    
    // 3. Thêm cột comments_count vào bảng reviews nếu chưa có
    echo "3. Kiểm tra cột comments_count trong bảng reviews...\n";
    try {
        $conn->exec("ALTER TABLE reviews ADD COLUMN comments_count INT DEFAULT 0");
        echo "✓ Đã thêm cột comments_count\n\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "✓ Cột comments_count đã tồn tại\n\n";
        } else {
            throw $e;
        }
    }
    
    // 4. Kiểm tra và hiển thị thông tin các bảng
    echo "4. Thông tin các bảng:\n";
    
    $tables = ['reviews', 'review_likes', 'review_comments'];
    foreach ($tables as $table) {
        $stmt = $conn->query("SELECT COUNT(*) as count FROM {$table}");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "   - {$table}: {$count} bản ghi\n";
    }
    
    echo "\n✅ Hoàn tất! Tất cả các bảng đã sẵn sàng.\n";
    echo "\nBạn có thể:\n";
    echo "- Truy cập trang admin/reviews.php để quản lý đánh giá\n";
    echo "- Người dùng có thể like và comment vào đánh giá\n";
    
} catch (PDOException $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
    echo "\nVui lòng kiểm tra:\n";
    echo "1. Kết nối database trong config/database.php\n";
    echo "2. Bảng reviews đã tồn tại chưa\n";
    echo "3. Quyền truy cập database\n";
}
