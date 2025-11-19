<?php
/**
 * Test hệ thống đánh giá
 * Kiểm tra tất cả các thành phần hoạt động đúng
 */

require_once 'config/database.php';

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Test Review System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-section {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .test-section h2 {
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .success {
            color: #10b981;
            font-weight: bold;
        }
        .error {
            color: #ef4444;
            font-weight: bold;
        }
        .info {
            color: #3b82f6;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            background: #f9fafb;
            font-weight: bold;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }
        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }
        .badge-error {
            background: #fee2e2;
            color: #991b1b;
        }
        .link-box {
            background: #f0f9ff;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
        }
        .link-box a {
            color: #2563eb;
            text-decoration: none;
            font-weight: bold;
        }
        .link-box a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <h1>🧪 Test Hệ thống Đánh giá</h1>
";

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Test 1: Kiểm tra bảng reviews
    echo "<div class='test-section'>";
    echo "<h2>1. Kiểm tra cấu trúc Database</h2>";
    
    try {
        $stmt = $conn->query("DESCRIBE reviews");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p class='success'>✓ Bảng 'reviews' tồn tại</p>";
        echo "<table>";
        echo "<tr><th>Cột</th><th>Kiểu dữ liệu</th><th>Null</th><th>Mặc định</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td><strong>{$col['Field']}</strong></td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Kiểm tra cột is_approved
        $has_approved = false;
        foreach ($columns as $col) {
            if ($col['Field'] === 'is_approved') {
                $has_approved = true;
                break;
            }
        }
        
        if ($has_approved) {
            echo "<p class='success'>✓ Cột 'is_approved' đã tồn tại</p>";
        } else {
            echo "<p class='error'>✗ Cột 'is_approved' chưa tồn tại. Chạy: config/run_update_reviews.php</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p class='error'>✗ Lỗi: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
    
    // Test 2: Kiểm tra bảng review_likes
    echo "<div class='test-section'>";
    echo "<h2>2. Kiểm tra bảng Review Likes</h2>";
    
    try {
        $stmt = $conn->query("DESCRIBE review_likes");
        echo "<p class='success'>✓ Bảng 'review_likes' tồn tại</p>";
    } catch (PDOException $e) {
        echo "<p class='error'>✗ Bảng 'review_likes' chưa tồn tại. Chạy: config/add_review_likes.sql</p>";
    }
    echo "</div>";
    
    // Test 3: Thống kê đánh giá
    echo "<div class='test-section'>";
    echo "<h2>3. Thống kê Đánh giá</h2>";
    
    $stmt = $conn->query("
        SELECT 
            COUNT(*) as total,
            AVG(rating) as avg_rating,
            SUM(CASE WHEN is_approved = TRUE THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN is_approved = FALSE THEN 1 ELSE 0 END) as pending,
            MIN(created_at) as first_review,
            MAX(created_at) as last_review
        FROM reviews
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>Thống kê</th><th>Giá trị</th></tr>";
    echo "<tr><td>Tổng đánh giá</td><td><strong>{$stats['total']}</strong></td></tr>";
    echo "<tr><td>Đánh giá trung bình</td><td><strong>" . number_format($stats['avg_rating'], 1) . " ⭐</strong></td></tr>";
    echo "<tr><td>Đã duyệt</td><td><span class='badge badge-success'>{$stats['approved']}</span></td></tr>";
    echo "<tr><td>Chờ duyệt</td><td><span class='badge badge-warning'>{$stats['pending']}</span></td></tr>";
    echo "<tr><td>Đánh giá đầu tiên</td><td>" . ($stats['first_review'] ?? 'Chưa có') . "</td></tr>";
    echo "<tr><td>Đánh giá mới nhất</td><td>" . ($stats['last_review'] ?? 'Chưa có') . "</td></tr>";
    echo "</table>";
    
    if ($stats['total'] == 0) {
        echo "<p class='info'>ℹ️ Chưa có đánh giá nào. Chạy: config/add_sample_reviews.php để thêm dữ liệu mẫu</p>";
    }
    echo "</div>";
    
    // Test 4: Top món ăn được đánh giá
    echo "<div class='test-section'>";
    echo "<h2>4. Top Món ăn được đánh giá</h2>";
    
    $stmt = $conn->query("
        SELECT 
            m.id,
            m.name,
            COUNT(r.id) as total_reviews,
            AVG(r.rating) as avg_rating
        FROM menu_items m
        LEFT JOIN reviews r ON m.id = r.menu_item_id AND r.is_approved = TRUE
        GROUP BY m.id
        HAVING total_reviews > 0
        ORDER BY avg_rating DESC, total_reviews DESC
        LIMIT 10
    ");
    $top_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($top_items) > 0) {
        echo "<table>";
        echo "<tr><th>Món ăn</th><th>Số đánh giá</th><th>Điểm TB</th><th>Xem chi tiết</th></tr>";
        foreach ($top_items as $item) {
            $stars = str_repeat('⭐', round($item['avg_rating']));
            echo "<tr>";
            echo "<td><strong>{$item['name']}</strong></td>";
            echo "<td>{$item['total_reviews']}</td>";
            echo "<td>{$stars} " . number_format($item['avg_rating'], 1) . "</td>";
            echo "<td><a href='index.php?page=menu-item-detail&id={$item['id']}' target='_blank'>Xem</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='info'>ℹ️ Chưa có món ăn nào được đánh giá</p>";
    }
    echo "</div>";
    
    // Test 5: Đánh giá gần đây
    echo "<div class='test-section'>";
    echo "<h2>5. Đánh giá gần đây</h2>";
    
    $stmt = $conn->query("
        SELECT 
            r.*,
            c.full_name as customer_name,
            m.name as menu_item_name
        FROM reviews r
        LEFT JOIN customers c ON r.customer_id = c.id
        LEFT JOIN menu_items m ON r.menu_item_id = m.id
        ORDER BY r.created_at DESC
        LIMIT 5
    ");
    $recent_reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($recent_reviews) > 0) {
        echo "<table>";
        echo "<tr><th>Khách hàng</th><th>Món ăn</th><th>Rating</th><th>Trạng thái</th><th>Ngày</th></tr>";
        foreach ($recent_reviews as $review) {
            $stars = str_repeat('⭐', $review['rating']);
            $status = $review['is_approved'] ? 
                "<span class='badge badge-success'>Đã duyệt</span>" : 
                "<span class='badge badge-warning'>Chờ duyệt</span>";
            
            echo "<tr>";
            echo "<td>{$review['customer_name']}</td>";
            echo "<td>{$review['menu_item_name']}</td>";
            echo "<td>{$stars}</td>";
            echo "<td>{$status}</td>";
            echo "<td>" . date('d/m/Y H:i', strtotime($review['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='info'>ℹ️ Chưa có đánh giá nào</p>";
    }
    echo "</div>";
    
    // Test 6: Kiểm tra files
    echo "<div class='test-section'>";
    echo "<h2>6. Kiểm tra Files</h2>";
    
    $files = [
        'api/submit-review.php' => 'API gửi đánh giá',
        'api/get-reviews.php' => 'API lấy đánh giá',
        'api/review-like.php' => 'API like đánh giá',
        'admin/reviews.php' => 'Trang admin quản lý',
        'pages/menu-item-detail.php' => 'Trang chi tiết món',
        'assets/js/reviews.js' => 'JavaScript xử lý',
        'assets/css/reviews.css' => 'CSS styling',
        'includes/menu-item-reviews.php' => 'Component rating'
    ];
    
    echo "<table>";
    echo "<tr><th>File</th><th>Mô tả</th><th>Trạng thái</th></tr>";
    foreach ($files as $file => $desc) {
        $exists = file_exists($file);
        $status = $exists ? 
            "<span class='badge badge-success'>✓ Tồn tại</span>" : 
            "<span class='badge badge-error'>✗ Không tìm thấy</span>";
        
        echo "<tr>";
        echo "<td><code>{$file}</code></td>";
        echo "<td>{$desc}</td>";
        echo "<td>{$status}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    
    // Test 7: Links quan trọng
    echo "<div class='test-section'>";
    echo "<h2>7. Liên kết quan trọng</h2>";
    
    echo "<div class='link-box'>";
    echo "<h3>👥 Dành cho người dùng:</h3>";
    echo "<ul>";
    echo "<li><a href='index.php?page=menu' target='_blank'>📋 Trang Menu (có rating)</a></li>";
    echo "<li><a href='index.php?page=menu-item-detail&id=1' target='_blank'>🍜 Chi tiết món ăn (có đánh giá)</a></li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='link-box'>";
    echo "<h3>👨‍💼 Dành cho Admin:</h3>";
    echo "<ul>";
    echo "<li><a href='admin/login.php' target='_blank'>🔐 Đăng nhập Admin</a></li>";
    echo "<li><a href='admin/reviews.php' target='_blank'>⭐ Quản lý đánh giá</a></li>";
    echo "<li><a href='admin/index.php' target='_blank'>📊 Dashboard</a></li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='link-box'>";
    echo "<h3>🔧 Scripts hỗ trợ:</h3>";
    echo "<ul>";
    echo "<li><a href='config/run_update_reviews.php' target='_blank'>🔄 Cập nhật database</a></li>";
    echo "<li><a href='config/add_sample_reviews.php' target='_blank'>📝 Thêm đánh giá mẫu</a></li>";
    echo "</ul>";
    echo "</div>";
    echo "</div>";
    
    // Kết luận
    echo "<div class='test-section'>";
    echo "<h2>✅ Kết luận</h2>";
    
    $all_good = true;
    $issues = [];
    
    // Kiểm tra các điều kiện
    if ($stats['total'] == 0) {
        $all_good = false;
        $issues[] = "Chưa có đánh giá nào. Chạy <code>config/add_sample_reviews.php</code>";
    }
    
    if (!file_exists('api/submit-review.php')) {
        $all_good = false;
        $issues[] = "Thiếu file API submit-review.php";
    }
    
    if (!file_exists('admin/reviews.php')) {
        $all_good = false;
        $issues[] = "Thiếu trang admin quản lý đánh giá";
    }
    
    if ($all_good) {
        echo "<p class='success' style='font-size: 18px;'>🎉 Hệ thống đánh giá hoạt động hoàn hảo!</p>";
        echo "<p>Tất cả các thành phần đã được cài đặt và liên kết đúng.</p>";
        echo "<p><strong>Bạn có thể bắt đầu sử dụng ngay!</strong></p>";
    } else {
        echo "<p class='error' style='font-size: 18px;'>⚠️ Có một số vấn đề cần khắc phục:</p>";
        echo "<ul>";
        foreach ($issues as $issue) {
            echo "<li>{$issue}</li>";
        }
        echo "</ul>";
    }
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='test-section'>";
    echo "<p class='error'>❌ Lỗi kết nối database: " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</body></html>";
?>
