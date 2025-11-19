<?php
/**
 * Test Review Like System
 * Kiểm tra hệ thống like đánh giá
 */

require_once 'config/database.php';

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <title>Test Review Like System</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; border-radius: 8px; margin: 10px 0; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #FF6B35; color: white; }
    </style>
</head>
<body>
    <h1>🧪 Test Review Like System</h1>";

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Test 1: Kiểm tra bảng review_likes
    echo "<div class='box'>";
    echo "<h2>1️⃣ Kiểm tra bảng review_likes</h2>";
    
    try {
        $stmt = $conn->query("DESCRIBE review_likes");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p class='success'>✓ Bảng review_likes tồn tại</p>";
        echo "<table>";
        echo "<tr><th>Cột</th><th>Kiểu dữ liệu</th><th>Key</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } catch (PDOException $e) {
        echo "<p class='error'>✗ Bảng review_likes chưa tồn tại</p>";
        echo "<p class='info'>💡 Chạy: php config/setup_review_likes.php</p>";
    }
    echo "</div>";
    
    // Test 2: Kiểm tra cột likes_count trong reviews
    echo "<div class='box'>";
    echo "<h2>2️⃣ Kiểm tra cột likes_count</h2>";
    
    $stmt = $conn->query("DESCRIBE reviews");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('likes_count', $columns)) {
        echo "<p class='success'>✓ Cột likes_count đã tồn tại trong bảng reviews</p>";
    } else {
        echo "<p class='error'>✗ Cột likes_count chưa tồn tại</p>";
        echo "<p class='info'>💡 Cần thêm cột: ALTER TABLE reviews ADD COLUMN likes_count INT DEFAULT 0</p>";
    }
    echo "</div>";
    
    // Test 3: Kiểm tra API
    echo "<div class='box'>";
    echo "<h2>3️⃣ Kiểm tra API</h2>";
    
    $api_file = 'api/review-like.php';
    if (file_exists($api_file)) {
        echo "<p class='success'>✓ File API tồn tại: $api_file</p>";
    } else {
        echo "<p class='error'>✗ File API không tồn tại</p>";
    }
    echo "</div>";
    
    // Test 4: Kiểm tra JavaScript
    echo "<div class='box'>";
    echo "<h2>4️⃣ Kiểm tra JavaScript</h2>";
    
    $js_file = 'assets/js/reviews.js';
    if (file_exists($js_file)) {
        echo "<p class='success'>✓ File JavaScript tồn tại: $js_file</p>";
        
        $js_content = file_get_contents($js_file);
        if (strpos($js_content, 'toggleLike') !== false) {
            echo "<p class='success'>✓ Hàm toggleLike() đã được định nghĩa</p>";
        } else {
            echo "<p class='error'>✗ Hàm toggleLike() chưa có</p>";
        }
    } else {
        echo "<p class='error'>✗ File JavaScript không tồn tại</p>";
    }
    echo "</div>";
    
    // Test 5: Dữ liệu mẫu
    echo "<div class='box'>";
    echo "<h2>5️⃣ Dữ liệu đánh giá</h2>";
    
    $stmt = $conn->query("
        SELECT 
            r.id,
            r.rating,
            r.comment,
            r.likes_count,
            c.full_name,
            m.name as menu_name,
            (SELECT COUNT(*) FROM review_likes WHERE review_id = r.id) as actual_likes
        FROM reviews r
        LEFT JOIN customers c ON r.customer_id = c.id
        LEFT JOIN menu_items m ON r.menu_item_id = m.id
        ORDER BY r.created_at DESC
        LIMIT 10
    ");
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($reviews) > 0) {
        echo "<p class='info'>📊 Tìm thấy " . count($reviews) . " đánh giá (hiển thị 10 mới nhất)</p>";
        echo "<table>";
        echo "<tr>
                <th>ID</th>
                <th>Món ăn</th>
                <th>Người đánh giá</th>
                <th>Rating</th>
                <th>Likes (DB)</th>
                <th>Likes (Count)</th>
                <th>Trạng thái</th>
              </tr>";
        
        foreach ($reviews as $review) {
            $status = ($review['likes_count'] == $review['actual_likes']) ? 
                "<span class='success'>✓ Đồng bộ</span>" : 
                "<span class='error'>✗ Không đồng bộ</span>";
            
            echo "<tr>";
            echo "<td>#{$review['id']}</td>";
            echo "<td>" . htmlspecialchars($review['menu_name']) . "</td>";
            echo "<td>" . htmlspecialchars($review['full_name']) . "</td>";
            echo "<td>" . str_repeat('⭐', $review['rating']) . "</td>";
            echo "<td>{$review['actual_likes']}</td>";
            echo "<td>{$review['likes_count']}</td>";
            echo "<td>$status</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='info'>ℹ️ Chưa có đánh giá nào</p>";
    }
    echo "</div>";
    
    // Test 6: Kiểm tra likes
    echo "<div class='box'>";
    echo "<h2>6️⃣ Lịch sử likes</h2>";
    
    try {
        $stmt = $conn->query("
            SELECT 
                rl.*,
                r.comment,
                c.full_name,
                m.name as menu_name
            FROM review_likes rl
            JOIN reviews r ON rl.review_id = r.id
            LEFT JOIN customers c ON rl.customer_id = c.id
            LEFT JOIN menu_items m ON r.menu_item_id = m.id
            ORDER BY rl.created_at DESC
            LIMIT 10
        ");
        $likes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($likes) > 0) {
            echo "<p class='info'>📊 Tìm thấy " . count($likes) . " likes</p>";
            echo "<table>";
            echo "<tr>
                    <th>Review ID</th>
                    <th>Món ăn</th>
                    <th>Người like</th>
                    <th>Thời gian</th>
                  </tr>";
            
            foreach ($likes as $like) {
                echo "<tr>";
                echo "<td>#{$like['review_id']}</td>";
                echo "<td>" . htmlspecialchars($like['menu_name']) . "</td>";
                echo "<td>" . htmlspecialchars($like['full_name']) . "</td>";
                echo "<td>" . date('d/m/Y H:i', strtotime($like['created_at'])) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='info'>ℹ️ Chưa có ai like đánh giá</p>";
        }
    } catch (PDOException $e) {
        echo "<p class='error'>✗ Không thể truy vấn: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
    
    // Hướng dẫn
    echo "<div class='box'>";
    echo "<h2>7️⃣ Hướng dẫn sử dụng</h2>";
    echo "<div class='info'>";
    echo "<h3>Để sử dụng tính năng like đánh giá:</h3>";
    echo "<ol>";
    echo "<li><strong>Đăng nhập</strong> tài khoản người dùng</li>";
    echo "<li>Vào trang chi tiết món ăn có đánh giá</li>";
    echo "<li>Scroll xuống phần <strong>Đánh giá</strong></li>";
    echo "<li>Click nút <strong>👍 Thích</strong> trên đánh giá</li>";
    echo "<li>Số lượng like sẽ tăng và icon đổi màu</li>";
    echo "<li>Click lại để <strong>unlike</strong></li>";
    echo "</ol>";
    
    echo "<h3>Nếu không hoạt động:</h3>";
    echo "<ol>";
    echo "<li>Chạy setup: <code>php config/setup_review_likes.php</code></li>";
    echo "<li>Kiểm tra console browser (F12) xem có lỗi JavaScript không</li>";
    echo "<li>Kiểm tra Network tab xem API có được gọi không</li>";
    echo "</ol>";
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='box'>";
    echo "<p class='error'>✗ Lỗi: " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</body></html>";
?>
