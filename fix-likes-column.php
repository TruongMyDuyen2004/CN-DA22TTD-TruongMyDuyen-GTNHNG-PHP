<?php
/**
 * Script sửa lỗi thiếu cột likes_count
 */

require_once 'config/database.php';

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Sửa lỗi likes_count</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 {
            color: #667eea;
            border-bottom: 3px solid #667eea;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .step {
            margin: 20px 0;
            padding: 15px;
            border-left: 4px solid #667eea;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .success {
            color: #10b981;
            border-left-color: #10b981;
            background: #ecfdf5;
        }
        .error {
            color: #ef4444;
            border-left-color: #ef4444;
            background: #fef2f2;
        }
        .info {
            color: #3b82f6;
            border-left-color: #3b82f6;
            background: #eff6ff;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 20px;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #764ba2;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 Sửa lỗi thiếu cột likes_count</h1>";

$db = new Database();
$conn = $db->connect();

try {
    // Kiểm tra xem cột likes_count đã tồn tại chưa
    echo "<div class='step info'>
            <strong>Bước 1:</strong> Kiểm tra cột likes_count...
          </div>";
    
    $stmt = $conn->query("SHOW COLUMNS FROM reviews LIKE 'likes_count'");
    $column_exists = $stmt->rowCount() > 0;
    
    if ($column_exists) {
        echo "<div class='step success'>
                ✅ Cột likes_count đã tồn tại
              </div>";
    } else {
        echo "<div class='step error'>
                ❌ Cột likes_count chưa tồn tại
              </div>";
        
        // Thêm cột likes_count
        echo "<div class='step info'>
                <strong>Bước 2:</strong> Thêm cột likes_count...
              </div>";
        
        $conn->exec("ALTER TABLE reviews ADD COLUMN likes_count INT DEFAULT 0 AFTER comment");
        
        echo "<div class='step success'>
                ✅ Đã thêm cột likes_count thành công
              </div>";
    }
    
    // Kiểm tra bảng review_likes
    echo "<div class='step info'>
            <strong>Bước 3:</strong> Kiểm tra bảng review_likes...
          </div>";
    
    $stmt = $conn->query("SHOW TABLES LIKE 'review_likes'");
    $table_exists = $stmt->rowCount() > 0;
    
    if (!$table_exists) {
        echo "<div class='step error'>
                ❌ Bảng review_likes chưa tồn tại
              </div>";
        
        echo "<div class='step info'>
                <strong>Bước 4:</strong> Tạo bảng review_likes...
              </div>";
        
        $sql = "CREATE TABLE IF NOT EXISTS review_likes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            review_id INT NOT NULL,
            customer_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
            FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
            UNIQUE KEY unique_like (review_id, customer_id),
            INDEX idx_review (review_id),
            INDEX idx_customer (customer_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $conn->exec($sql);
        
        echo "<div class='step success'>
                ✅ Đã tạo bảng review_likes thành công
              </div>";
    } else {
        echo "<div class='step success'>
                ✅ Bảng review_likes đã tồn tại
              </div>";
    }
    
    // Đồng bộ likes_count
    echo "<div class='step info'>
            <strong>Bước 5:</strong> Đồng bộ số lượng likes...
          </div>";
    
    $sql = "UPDATE reviews r 
            SET likes_count = (
                SELECT COUNT(*) 
                FROM review_likes rl 
                WHERE rl.review_id = r.id
            )";
    $conn->exec($sql);
    
    echo "<div class='step success'>
            ✅ Đã đồng bộ likes_count thành công
          </div>";
    
    // Thống kê
    $stmt = $conn->query("SELECT COUNT(*) as total FROM reviews");
    $total_reviews = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $conn->query("SELECT COUNT(*) as total FROM review_likes");
    $total_likes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "<div class='step success'>
            <strong>✅ Hoàn thành!</strong><br>
            - Tổng số đánh giá: <strong>{$total_reviews}</strong><br>
            - Tổng số likes: <strong>{$total_likes}</strong>
          </div>";
    
    echo "<div class='step info'>
            <strong>💡 Hướng dẫn:</strong><br>
            Bây giờ bạn có thể quay lại trang đánh giá và thử like/unlike các đánh giá.
          </div>";
    
} catch (PDOException $e) {
    echo "<div class='step error'>
            <strong>❌ Lỗi:</strong> " . htmlspecialchars($e->getMessage()) . "
          </div>";
}

echo "
        <div style='text-align: center; margin-top: 30px;'>
            <a href='index.php?page=all-reviews' class='btn'>📋 Xem tất cả đánh giá</a>
            <a href='index.php' class='btn'>🏠 Về trang chủ</a>
        </div>
    </div>
</body>
</html>";
?>
