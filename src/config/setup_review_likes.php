<?php
/**
 * Script thiết lập hệ thống Like cho đánh giá
 * Chạy file này để tạo bảng review_likes và cập nhật bảng reviews
 */

require_once 'database.php';

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Thiết lập hệ thống Like đánh giá</title>
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
        .warning {
            color: #f59e0b;
            border-left-color: #f59e0b;
            background: #fffbeb;
        }
        .icon {
            font-size: 1.2em;
            margin-right: 10px;
        }
        pre {
            background: #1f2937;
            color: #f3f4f6;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 0.9em;
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
        <h1>🎯 Thiết lập hệ thống Like đánh giá</h1>";

$db = new Database();
$conn = $db->connect();

$errors = [];
$success = [];

try {
    // Bước 1: Kiểm tra và thêm cột likes_count vào bảng reviews
    echo "<div class='step info'>
            <span class='icon'>📋</span>
            <strong>Bước 1:</strong> Kiểm tra và cập nhật bảng reviews...
          </div>";
    
    try {
        $conn->exec("ALTER TABLE reviews ADD COLUMN IF NOT EXISTS likes_count INT DEFAULT 0 AFTER comment");
        echo "<div class='step success'>
                <span class='icon'>✅</span>
                Đã thêm cột likes_count vào bảng reviews
              </div>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<div class='step warning'>
                    <span class='icon'>⚠️</span>
                    Cột likes_count đã tồn tại
                  </div>";
        } else {
            throw $e;
        }
    }
    
    try {
        $conn->exec("ALTER TABLE reviews ADD COLUMN IF NOT EXISTS is_approved BOOLEAN DEFAULT TRUE AFTER likes_count");
        echo "<div class='step success'>
                <span class='icon'>✅</span>
                Đã thêm cột is_approved vào bảng reviews
              </div>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<div class='step warning'>
                    <span class='icon'>⚠️</span>
                    Cột is_approved đã tồn tại
                  </div>";
        } else {
            throw $e;
        }
    }
    
    try {
        $conn->exec("ALTER TABLE reviews ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
        echo "<div class='step success'>
                <span class='icon'>✅</span>
                Đã thêm cột updated_at vào bảng reviews
              </div>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<div class='step warning'>
                    <span class='icon'>⚠️</span>
                    Cột updated_at đã tồn tại
                  </div>";
        } else {
            throw $e;
        }
    }
    
    // Bước 2: Tạo bảng review_likes
    echo "<div class='step info'>
            <span class='icon'>📋</span>
            <strong>Bước 2:</strong> Tạo bảng review_likes...
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
            <span class='icon'>✅</span>
            Đã tạo bảng review_likes thành công
          </div>";
    
    // Bước 3: Đồng bộ likes_count
    echo "<div class='step info'>
            <span class='icon'>📋</span>
            <strong>Bước 3:</strong> Đồng bộ số lượng likes...
          </div>";
    
    $sql = "UPDATE reviews r 
            SET likes_count = (
                SELECT COUNT(*) 
                FROM review_likes rl 
                WHERE rl.review_id = r.id
            )";
    $conn->exec($sql);
    
    $stmt = $conn->query("SELECT COUNT(*) as total FROM reviews WHERE likes_count > 0");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<div class='step success'>
            <span class='icon'>✅</span>
            Đã đồng bộ likes_count cho {$result['total']} đánh giá
          </div>";
    
    // Bước 4: Thống kê
    echo "<div class='step info'>
            <span class='icon'>📊</span>
            <strong>Thống kê hệ thống:</strong>
          </div>";
    
    $stmt = $conn->query("SELECT COUNT(*) as total FROM reviews");
    $total_reviews = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $conn->query("SELECT COUNT(*) as total FROM review_likes");
    $total_likes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $conn->query("SELECT COUNT(DISTINCT customer_id) as total FROM review_likes");
    $total_users_liked = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "<div class='step success'>
            <span class='icon'>📈</span>
            <ul style='margin: 10px 0; padding-left: 30px;'>
                <li>Tổng số đánh giá: <strong>{$total_reviews}</strong></li>
                <li>Tổng số likes: <strong>{$total_likes}</strong></li>
                <li>Số người dùng đã like: <strong>{$total_users_liked}</strong></li>
            </ul>
          </div>";
    
    // Hoàn thành
    echo "<div class='step success'>
            <span class='icon'>🎉</span>
            <strong>Hoàn thành!</strong> Hệ thống Like đánh giá đã được thiết lập thành công.
          </div>";
    
    echo "<div class='step info'>
            <span class='icon'>💡</span>
            <strong>Hướng dẫn sử dụng:</strong>
            <ul style='margin: 10px 0; padding-left: 30px;'>
                <li>Người dùng đã đăng nhập có thể like/unlike đánh giá</li>
                <li>Mỗi người dùng chỉ có thể like 1 lần cho mỗi đánh giá</li>
                <li>Số lượng likes được hiển thị ngay bên cạnh nút like</li>
                <li>Icon trái tim sẽ đổi màu khi đã like</li>
            </ul>
          </div>";
    
} catch (PDOException $e) {
    echo "<div class='step error'>
            <span class='icon'>❌</span>
            <strong>Lỗi:</strong> " . htmlspecialchars($e->getMessage()) . "
          </div>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "
        <div style='text-align: center; margin-top: 30px;'>
            <a href='../index.php' class='btn'>🏠 Về trang chủ</a>
            <a href='../pages/menu.php' class='btn'>🍽️ Xem menu</a>
        </div>
    </div>
</body>
</html>";
?>
