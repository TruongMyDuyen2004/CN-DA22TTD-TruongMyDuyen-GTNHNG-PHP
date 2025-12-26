<?php
/**
 * Debug script để kiểm tra tại sao không like được
 */

session_start();
require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Debug Review Like</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #1e293b;
            color: #f1f5f9;
            padding: 2rem;
            line-height: 1.6;
        }
        .section {
            background: #334155;
            padding: 1.5rem;
            margin: 1rem 0;
            border-radius: 8px;
            border-left: 4px solid #3b82f6;
        }
        .success { border-left-color: #10b981; }
        .error { border-left-color: #ef4444; }
        .warning { border-left-color: #f59e0b; }
        h2 { color: #60a5fa; margin-top: 0; }
        pre { 
            background: #1e293b; 
            padding: 1rem; 
            border-radius: 4px; 
            overflow-x: auto;
        }
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin: 0.5rem;
        }
    </style>
</head>
<body>";

echo "<h1>🔍 Debug Review Like System</h1>";

$db = new Database();
$conn = $db->connect();

// 1. Kiểm tra session
echo "<div class='section " . (isset($_SESSION['customer_id']) ? 'success' : 'error') . "'>";
echo "<h2>1. Kiểm tra Session</h2>";
if (isset($_SESSION['customer_id'])) {
    echo "<p>✅ Đã đăng nhập - Customer ID: " . $_SESSION['customer_id'] . "</p>";
    
    // Lấy thông tin user
    $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$_SESSION['customer_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<pre>";
        echo "Tên: " . htmlspecialchars($user['full_name']) . "\n";
        echo "Email: " . htmlspecialchars($user['email']) . "\n";
        echo "</pre>";
    }
} else {
    echo "<p>❌ Chưa đăng nhập</p>";
    echo "<p><a href='auth/login.php' class='btn'>Đăng nhập ngay</a></p>";
}
echo "</div>";

// 2. Kiểm tra bảng reviews
echo "<div class='section'>";
echo "<h2>2. Kiểm tra bảng reviews</h2>";
try {
    $stmt = $conn->query("DESCRIBE reviews");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $has_likes_count = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'likes_count') {
            $has_likes_count = true;
            break;
        }
    }
    
    if ($has_likes_count) {
        echo "<p>✅ Cột likes_count tồn tại</p>";
    } else {
        echo "<p>❌ Cột likes_count KHÔNG tồn tại</p>";
        echo "<p><a href='config/setup_review_likes.php' class='btn'>Chạy Setup</a></p>";
    }
    
    // Đếm số reviews
    $stmt = $conn->query("SELECT COUNT(*) as total FROM reviews");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "<p>Tổng số reviews: <strong>{$total}</strong></p>";
    
} catch (PDOException $e) {
    echo "<p>❌ Lỗi: " . $e->getMessage() . "</p>";
}
echo "</div>";

// 3. Kiểm tra bảng review_likes
echo "<div class='section'>";
echo "<h2>3. Kiểm tra bảng review_likes</h2>";
try {
    $stmt = $conn->query("SHOW TABLES LIKE 'review_likes'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Bảng review_likes tồn tại</p>";
        
        // Đếm số likes
        $stmt = $conn->query("SELECT COUNT(*) as total FROM review_likes");
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "<p>Tổng số likes: <strong>{$total}</strong></p>";
        
        // Kiểm tra constraints
        $stmt = $conn->query("SHOW CREATE TABLE review_likes");
        $create_table = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<details><summary>Xem cấu trúc bảng</summary>";
        echo "<pre>" . htmlspecialchars($create_table['Create Table']) . "</pre>";
        echo "</details>";
        
    } else {
        echo "<p>❌ Bảng review_likes KHÔNG tồn tại</p>";
        echo "<p><a href='config/setup_review_likes.php' class='btn'>Chạy Setup</a></p>";
    }
} catch (PDOException $e) {
    echo "<p>❌ Lỗi: " . $e->getMessage() . "</p>";
}
echo "</div>";

// 4. Kiểm tra file API
echo "<div class='section'>";
echo "<h2>4. Kiểm tra file API</h2>";

$api_file = 'api/review-like.php';
if (file_exists($api_file)) {
    echo "<p>✅ File {$api_file} tồn tại</p>";
    echo "<p>Kích thước: " . filesize($api_file) . " bytes</p>";
    echo "<p>Quyền: " . substr(sprintf('%o', fileperms($api_file)), -4) . "</p>";
} else {
    echo "<p>❌ File {$api_file} KHÔNG tồn tại</p>";
}

$js_file = 'assets/js/reviews.js';
if (file_exists($js_file)) {
    echo "<p>✅ File {$js_file} tồn tại</p>";
    echo "<p>Kích thước: " . filesize($js_file) . " bytes</p>";
} else {
    echo "<p>❌ File {$js_file} KHÔNG tồn tại</p>";
}
echo "</div>";

// 5. Test API trực tiếp
if (isset($_SESSION['customer_id'])) {
    echo "<div class='section'>";
    echo "<h2>5. Test API Like</h2>";
    
    // Lấy 1 review để test
    $stmt = $conn->query("SELECT id FROM reviews LIMIT 1");
    $review = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($review) {
        $review_id = $review['id'];
        echo "<p>Test với Review ID: <strong>{$review_id}</strong></p>";
        
        echo "<button onclick='testLike({$review_id})' class='btn'>Test Like</button>";
        echo "<div id='test-result' style='margin-top: 1rem;'></div>";
        
        echo "<script>
        async function testLike(reviewId) {
            const resultDiv = document.getElementById('test-result');
            resultDiv.innerHTML = '<p>⏳ Đang gửi request...</p>';
            
            try {
                const formData = new FormData();
                formData.append('review_id', reviewId);
                
                const response = await fetch('api/review-like.php', {
                    method: 'POST',
                    body: formData
                });
                
                const text = await response.text();
                console.log('Raw response:', text);
                
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    resultDiv.innerHTML = '<p>❌ Response không phải JSON:</p><pre>' + text + '</pre>';
                    return;
                }
                
                if (data.success) {
                    resultDiv.innerHTML = '<p>✅ Thành công!</p>' +
                        '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                } else {
                    resultDiv.innerHTML = '<p>❌ Lỗi: ' + data.message + '</p>' +
                        '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                }
            } catch (error) {
                resultDiv.innerHTML = '<p>❌ Lỗi JavaScript: ' + error.message + '</p>';
                console.error('Error:', error);
            }
        }
        </script>";
    } else {
        echo "<p>⚠️ Không có review nào để test</p>";
    }
    
    echo "</div>";
}

// 6. Kiểm tra JavaScript console
echo "<div class='section warning'>";
echo "<h2>6. Hướng dẫn kiểm tra lỗi</h2>";
echo "<ol>";
echo "<li>Mở Developer Tools (F12)</li>";
echo "<li>Chuyển sang tab Console</li>";
echo "<li>Click nút Like trên trang</li>";
echo "<li>Xem có lỗi gì không</li>";
echo "</ol>";
echo "<p>Các lỗi thường gặp:</p>";
echo "<ul>";
echo "<li>❌ <code>404 Not Found</code> - File API không tồn tại</li>";
echo "<li>❌ <code>500 Internal Server Error</code> - Lỗi PHP</li>";
echo "<li>❌ <code>Vui lòng đăng nhập</code> - Chưa login</li>";
echo "<li>❌ <code>reviewSystem is not defined</code> - JavaScript chưa load</li>";
echo "</ul>";
echo "</div>";

// 7. Sample reviews
echo "<div class='section'>";
echo "<h2>7. Danh sách Reviews (để test)</h2>";
try {
    $stmt = $conn->query("
        SELECT 
            r.id,
            r.rating,
            r.comment,
            r.likes_count,
            c.full_name,
            m.name as menu_item_name
        FROM reviews r
        LEFT JOIN customers c ON r.customer_id = c.id
        LEFT JOIN menu_items m ON r.menu_item_id = m.id
        LIMIT 5
    ");
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($reviews) > 0) {
        echo "<table style='width: 100%; border-collapse: collapse;'>";
        echo "<tr style='background: #1e293b;'>";
        echo "<th style='padding: 0.5rem; border: 1px solid #475569;'>ID</th>";
        echo "<th style='padding: 0.5rem; border: 1px solid #475569;'>Món ăn</th>";
        echo "<th style='padding: 0.5rem; border: 1px solid #475569;'>Người đánh giá</th>";
        echo "<th style='padding: 0.5rem; border: 1px solid #475569;'>Rating</th>";
        echo "<th style='padding: 0.5rem; border: 1px solid #475569;'>Likes</th>";
        echo "</tr>";
        
        foreach ($reviews as $r) {
            echo "<tr>";
            echo "<td style='padding: 0.5rem; border: 1px solid #475569;'>{$r['id']}</td>";
            echo "<td style='padding: 0.5rem; border: 1px solid #475569;'>" . htmlspecialchars($r['menu_item_name'] ?? 'N/A') . "</td>";
            echo "<td style='padding: 0.5rem; border: 1px solid #475569;'>" . htmlspecialchars($r['full_name'] ?? 'Anonymous') . "</td>";
            echo "<td style='padding: 0.5rem; border: 1px solid #475569;'>{$r['rating']}/5</td>";
            echo "<td style='padding: 0.5rem; border: 1px solid #475569;'>{$r['likes_count']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>⚠️ Chưa có review nào</p>";
    }
} catch (PDOException $e) {
    echo "<p>❌ Lỗi: " . $e->getMessage() . "</p>";
}
echo "</div>";

echo "<div style='text-align: center; margin-top: 2rem;'>";
echo "<a href='config/setup_review_likes.php' class='btn'>🔧 Chạy Setup</a>";
echo "<a href='test-review-likes.php' class='btn'>🧪 Test System</a>";
echo "<a href='pages/menu.php' class='btn'>🍽️ Xem Menu</a>";
echo "<a href='index.php' class='btn'>🏠 Trang chủ</a>";
echo "</div>";

echo "</body></html>";
?>
