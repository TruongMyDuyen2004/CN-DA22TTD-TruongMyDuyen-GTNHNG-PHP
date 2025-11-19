<?php
/**
 * Script sửa lỗi ngay lập tức - Tự động redirect
 */

require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

$errors = [];
$success = [];

try {
    // 1. Thêm cột likes_count vào bảng reviews
    try {
        $conn->exec("ALTER TABLE reviews ADD COLUMN likes_count INT DEFAULT 0 AFTER comment");
        $success[] = "Đã thêm cột likes_count";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            $success[] = "Cột likes_count đã tồn tại";
        } else {
            $errors[] = "Lỗi thêm cột likes_count: " . $e->getMessage();
        }
    }
    
    // 2. Thêm cột is_approved
    try {
        $conn->exec("ALTER TABLE reviews ADD COLUMN is_approved BOOLEAN DEFAULT TRUE AFTER likes_count");
        $success[] = "Đã thêm cột is_approved";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            $success[] = "Cột is_approved đã tồn tại";
        } else {
            // Không quan trọng lắm
        }
    }
    
    // 3. Tạo bảng review_likes
    try {
        $sql = "CREATE TABLE IF NOT EXISTS review_likes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            review_id INT NOT NULL,
            customer_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_like (review_id, customer_id),
            INDEX idx_review (review_id),
            INDEX idx_customer (customer_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $conn->exec($sql);
        $success[] = "Đã tạo bảng review_likes";
    } catch (PDOException $e) {
        $errors[] = "Lỗi tạo bảng review_likes: " . $e->getMessage();
    }
    
    // 4. Thêm foreign keys (nếu chưa có)
    try {
        $conn->exec("ALTER TABLE review_likes ADD CONSTRAINT fk_review_likes_review FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE");
    } catch (PDOException $e) {
        // Foreign key có thể đã tồn tại
    }
    
    try {
        $conn->exec("ALTER TABLE review_likes ADD CONSTRAINT fk_review_likes_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE");
    } catch (PDOException $e) {
        // Foreign key có thể đã tồn tại
    }
    
    // 5. Đồng bộ likes_count
    $conn->exec("UPDATE reviews r SET likes_count = (SELECT COUNT(*) FROM review_likes rl WHERE rl.review_id = r.id)");
    $success[] = "Đã đồng bộ likes_count";
    
    // Redirect về trang đánh giá
    if (count($errors) === 0) {
        header("Location: index.php?page=all-reviews&fixed=1");
        exit;
    }
    
} catch (PDOException $e) {
    $errors[] = "Lỗi: " . $e->getMessage();
}

// Nếu có lỗi, hiển thị
if (count($errors) > 0) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Lỗi</title></head><body>";
    echo "<h1>Có lỗi xảy ra:</h1>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li style='color: red;'>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>";
    echo "<h2>Thành công:</h2>";
    echo "<ul>";
    foreach ($success as $msg) {
        echo "<li style='color: green;'>" . htmlspecialchars($msg) . "</li>";
    }
    echo "</ul>";
    echo "<a href='index.php?page=all-reviews'>Quay lại trang đánh giá</a>";
    echo "</body></html>";
} else {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Thành công</title></head><body>";
    echo "<h1>Sửa lỗi thành công!</h1>";
    echo "<p>Đang chuyển hướng...</p>";
    echo "<script>setTimeout(function(){ window.location.href='index.php?page=all-reviews'; }, 1000);</script>";
    echo "</body></html>";
}
?>
