<?php
/**
 * Kiểm tra cấu trúc bảng reviews
 */

require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <title>Kiểm tra bảng reviews</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #667eea; color: white; }
        .success { color: #10b981; font-weight: bold; }
        .error { color: #ef4444; font-weight: bold; }
        h2 { color: #667eea; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔍 Kiểm tra cấu trúc bảng reviews</h1>";

$db = new Database();
$conn = $db->connect();

try {
    // Kiểm tra cấu trúc bảng reviews
    echo "<h2>Cấu trúc bảng reviews:</h2>";
    $stmt = $conn->query("DESCRIBE reviews");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>Cột</th><th>Kiểu</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    $has_likes_count = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'likes_count') {
            $has_likes_count = true;
        }
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($column['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    if ($has_likes_count) {
        echo "<p class='success'>✅ Cột likes_count đã tồn tại</p>";
    } else {
        echo "<p class='error'>❌ Cột likes_count CHƯA tồn tại - Cần chạy fix-likes-column.php</p>";
    }
    
    // Kiểm tra bảng review_likes
    echo "<h2>Kiểm tra bảng review_likes:</h2>";
    $stmt = $conn->query("SHOW TABLES LIKE 'review_likes'");
    if ($stmt->rowCount() > 0) {
        echo "<p class='success'>✅ Bảng review_likes đã tồn tại</p>";
        
        $stmt = $conn->query("DESCRIBE review_likes");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table>";
        echo "<tr><th>Cột</th><th>Kiểu</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($column['Field']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Đếm số lượng likes
        $stmt = $conn->query("SELECT COUNT(*) as total FROM review_likes");
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "<p>Tổng số likes: <strong>{$total}</strong></p>";
        
    } else {
        echo "<p class='error'>❌ Bảng review_likes CHƯA tồn tại - Cần chạy fix-likes-column.php</p>";
    }
    
    // Thống kê reviews
    echo "<h2>Thống kê reviews:</h2>";
    $stmt = $conn->query("SELECT COUNT(*) as total FROM reviews");
    $total_reviews = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "<p>Tổng số đánh giá: <strong>{$total_reviews}</strong></p>";
    
    if ($has_likes_count) {
        $stmt = $conn->query("SELECT SUM(likes_count) as total FROM reviews");
        $total_likes = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        echo "<p>Tổng số likes (từ cột likes_count): <strong>{$total_likes}</strong></p>";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Lỗi: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "
        <div style='margin-top: 30px; text-align: center;'>
            <a href='fix-likes-column.php' style='display: inline-block; padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 8px; margin: 5px;'>🔧 Sửa lỗi</a>
            <a href='index.php?page=all-reviews' style='display: inline-block; padding: 12px 24px; background: #10b981; color: white; text-decoration: none; border-radius: 8px; margin: 5px;'>📋 Xem đánh giá</a>
            <a href='index.php' style='display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 8px; margin: 5px;'>🏠 Trang chủ</a>
        </div>
    </div>
</body>
</html>";
?>
