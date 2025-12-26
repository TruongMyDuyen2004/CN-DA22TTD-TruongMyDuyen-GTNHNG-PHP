<?php
/**
 * Script kiểm tra hệ thống Like đánh giá
 */

session_start();
require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Review Likes System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        h1 {
            color: #667eea;
            margin-bottom: 2rem;
            text-align: center;
            font-size: 2.5rem;
        }

        .test-section {
            margin: 2rem 0;
            padding: 1.5rem;
            background: #f8fafc;
            border-radius: 12px;
            border-left: 4px solid #667eea;
        }

        .test-section h2 {
            color: #1e293b;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            margin: 0.5rem 0;
        }

        .status.success {
            background: #d1fae5;
            color: #065f46;
        }

        .status.error {
            background: #fee2e2;
            color: #991b1b;
        }

        .status.warning {
            background: #fef3c7;
            color: #92400e;
        }

        .status.info {
            background: #dbeafe;
            color: #1e40af;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            background: #667eea;
            color: white;
            font-weight: 600;
        }

        tr:hover {
            background: #f8fafc;
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin: 0.5rem;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-weight: 600;
        }

        .btn:hover {
            background: #764ba2;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-danger {
            background: #dc2626;
        }

        .btn-danger:hover {
            background: #991b1b;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .stat-card .number {
            font-size: 2.5rem;
            font-weight: 800;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .stat-card .label {
            color: #64748b;
            font-weight: 600;
        }

        .code-block {
            background: #1e293b;
            color: #f1f5f9;
            padding: 1rem;
            border-radius: 8px;
            overflow-x: auto;
            margin: 1rem 0;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }

        .like-demo {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            margin: 0.5rem 0;
        }

        .like-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            color: #64748b;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .like-btn:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }

        .like-btn.liked {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border-color: #fca5a5;
            color: #dc2626;
        }

        .like-btn i {
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-heart"></i> Test Review Likes System</h1>

        <?php
        // Test 1: Kiểm tra bảng reviews có cột likes_count
        echo '<div class="test-section">';
        echo '<h2><i class="fas fa-database"></i> Test 1: Kiểm tra cấu trúc bảng reviews</h2>';
        
        try {
            $stmt = $conn->query("DESCRIBE reviews");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $has_likes_count = false;
            $has_is_approved = false;
            $has_updated_at = false;
            
            foreach ($columns as $column) {
                if ($column['Field'] === 'likes_count') $has_likes_count = true;
                if ($column['Field'] === 'is_approved') $has_is_approved = true;
                if ($column['Field'] === 'updated_at') $has_updated_at = true;
            }
            
            if ($has_likes_count) {
                echo '<div class="status success"><i class="fas fa-check"></i> Cột likes_count: Tồn tại</div>';
            } else {
                echo '<div class="status error"><i class="fas fa-times"></i> Cột likes_count: Không tồn tại</div>';
            }
            
            if ($has_is_approved) {
                echo '<div class="status success"><i class="fas fa-check"></i> Cột is_approved: Tồn tại</div>';
            } else {
                echo '<div class="status warning"><i class="fas fa-exclamation"></i> Cột is_approved: Không tồn tại</div>';
            }
            
            if ($has_updated_at) {
                echo '<div class="status success"><i class="fas fa-check"></i> Cột updated_at: Tồn tại</div>';
            } else {
                echo '<div class="status warning"><i class="fas fa-exclamation"></i> Cột updated_at: Không tồn tại</div>';
            }
            
        } catch (PDOException $e) {
            echo '<div class="status error"><i class="fas fa-times"></i> Lỗi: ' . $e->getMessage() . '</div>';
        }
        echo '</div>';

        // Test 2: Kiểm tra bảng review_likes
        echo '<div class="test-section">';
        echo '<h2><i class="fas fa-table"></i> Test 2: Kiểm tra bảng review_likes</h2>';
        
        try {
            $stmt = $conn->query("SHOW TABLES LIKE 'review_likes'");
            $table_exists = $stmt->rowCount() > 0;
            
            if ($table_exists) {
                echo '<div class="status success"><i class="fas fa-check"></i> Bảng review_likes: Tồn tại</div>';
                
                // Kiểm tra cấu trúc
                $stmt = $conn->query("DESCRIBE review_likes");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo '<table>';
                echo '<tr><th>Cột</th><th>Kiểu dữ liệu</th><th>Null</th><th>Key</th><th>Default</th></tr>';
                foreach ($columns as $column) {
                    echo '<tr>';
                    echo '<td>' . $column['Field'] . '</td>';
                    echo '<td>' . $column['Type'] . '</td>';
                    echo '<td>' . $column['Null'] . '</td>';
                    echo '<td>' . $column['Key'] . '</td>';
                    echo '<td>' . ($column['Default'] ?? 'NULL') . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
                
            } else {
                echo '<div class="status error"><i class="fas fa-times"></i> Bảng review_likes: Không tồn tại</div>';
                echo '<p>Vui lòng chạy script: <code>config/setup_review_likes.php</code></p>';
            }
            
        } catch (PDOException $e) {
            echo '<div class="status error"><i class="fas fa-times"></i> Lỗi: ' . $e->getMessage() . '</div>';
        }
        echo '</div>';

        // Test 3: Thống kê
        echo '<div class="test-section">';
        echo '<h2><i class="fas fa-chart-bar"></i> Test 3: Thống kê hệ thống</h2>';
        
        try {
            // Tổng số đánh giá
            $stmt = $conn->query("SELECT COUNT(*) as total FROM reviews");
            $total_reviews = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Tổng số likes
            $stmt = $conn->query("SELECT COUNT(*) as total FROM review_likes");
            $total_likes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Số người dùng đã like
            $stmt = $conn->query("SELECT COUNT(DISTINCT customer_id) as total FROM review_likes");
            $total_users_liked = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Đánh giá có nhiều likes nhất
            $stmt = $conn->query("SELECT MAX(likes_count) as max_likes FROM reviews");
            $max_likes = $stmt->fetch(PDO::FETCH_ASSOC)['max_likes'] ?? 0;
            
            echo '<div class="stats-grid">';
            echo '<div class="stat-card">';
            echo '<div class="number">' . $total_reviews . '</div>';
            echo '<div class="label">Tổng đánh giá</div>';
            echo '</div>';
            
            echo '<div class="stat-card">';
            echo '<div class="number">' . $total_likes . '</div>';
            echo '<div class="label">Tổng likes</div>';
            echo '</div>';
            
            echo '<div class="stat-card">';
            echo '<div class="number">' . $total_users_liked . '</div>';
            echo '<div class="label">Người dùng đã like</div>';
            echo '</div>';
            
            echo '<div class="stat-card">';
            echo '<div class="number">' . $max_likes . '</div>';
            echo '<div class="label">Likes cao nhất</div>';
            echo '</div>';
            echo '</div>';
            
        } catch (PDOException $e) {
            echo '<div class="status error"><i class="fas fa-times"></i> Lỗi: ' . $e->getMessage() . '</div>';
        }
        echo '</div>';

        // Test 4: Top đánh giá được like nhiều nhất
        echo '<div class="test-section">';
        echo '<h2><i class="fas fa-trophy"></i> Test 4: Top đánh giá được like nhiều nhất</h2>';
        
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
                ORDER BY r.likes_count DESC
                LIMIT 10
            ");
            $top_reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($top_reviews) > 0) {
                echo '<table>';
                echo '<tr><th>ID</th><th>Món ăn</th><th>Người đánh giá</th><th>Rating</th><th>Likes</th><th>Bình luận</th></tr>';
                foreach ($top_reviews as $review) {
                    echo '<tr>';
                    echo '<td>' . $review['id'] . '</td>';
                    echo '<td>' . htmlspecialchars($review['menu_item_name'] ?? 'N/A') . '</td>';
                    echo '<td>' . htmlspecialchars($review['full_name'] ?? 'Anonymous') . '</td>';
                    echo '<td>';
                    for ($i = 0; $i < $review['rating']; $i++) {
                        echo '<i class="fas fa-star" style="color: #f59e0b;"></i>';
                    }
                    echo '</td>';
                    echo '<td><strong>' . $review['likes_count'] . '</strong> <i class="fas fa-heart" style="color: #dc2626;"></i></td>';
                    echo '<td>' . htmlspecialchars(substr($review['comment'], 0, 50)) . '...</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<div class="status info"><i class="fas fa-info-circle"></i> Chưa có đánh giá nào</div>';
            }
            
        } catch (PDOException $e) {
            echo '<div class="status error"><i class="fas fa-times"></i> Lỗi: ' . $e->getMessage() . '</div>';
        }
        echo '</div>';

        // Test 5: Demo giao diện Like button
        echo '<div class="test-section">';
        echo '<h2><i class="fas fa-paint-brush"></i> Test 5: Demo giao diện Like button</h2>';
        
        echo '<div class="like-demo">';
        echo '<span>Chưa like:</span>';
        echo '<button class="like-btn">';
        echo '<i class="far fa-heart"></i>';
        echo '<span>15</span>';
        echo '</button>';
        echo '</div>';
        
        echo '<div class="like-demo">';
        echo '<span>Đã like:</span>';
        echo '<button class="like-btn liked">';
        echo '<i class="fas fa-heart"></i>';
        echo '<span>16</span>';
        echo '</button>';
        echo '</div>';
        
        echo '<div class="like-demo">';
        echo '<span>Hover effect:</span>';
        echo '<button class="like-btn" onmouseover="this.style.transform=\'scale(1.05)\'" onmouseout="this.style.transform=\'scale(1)\'">';
        echo '<i class="far fa-heart"></i>';
        echo '<span>42</span>';
        echo '</button>';
        echo '</div>';
        
        echo '</div>';

        // Test 6: API Endpoints
        echo '<div class="test-section">';
        echo '<h2><i class="fas fa-code"></i> Test 6: API Endpoints</h2>';
        
        echo '<div class="status info"><i class="fas fa-info-circle"></i> API Like/Unlike: <code>POST /api/review-like.php</code></div>';
        echo '<div class="code-block">';
        echo 'FormData {<br>';
        echo '&nbsp;&nbsp;review_id: 123<br>';
        echo '}';
        echo '</div>';
        
        echo '<div class="status info"><i class="fas fa-info-circle"></i> API Get Reviews: <code>GET /api/get-reviews.php?menu_item_id=1</code></div>';
        echo '<div class="code-block">';
        echo 'Response {<br>';
        echo '&nbsp;&nbsp;"success": true,<br>';
        echo '&nbsp;&nbsp;"reviews": [<br>';
        echo '&nbsp;&nbsp;&nbsp;&nbsp;{<br>';
        echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"id": 1,<br>';
        echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"likes_count": 15,<br>';
        echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"is_liked_by_user": false<br>';
        echo '&nbsp;&nbsp;&nbsp;&nbsp;}<br>';
        echo '&nbsp;&nbsp;]<br>';
        echo '}';
        echo '</div>';
        
        echo '</div>';

        // Test 7: Kiểm tra file
        echo '<div class="test-section">';
        echo '<h2><i class="fas fa-file-code"></i> Test 7: Kiểm tra file hệ thống</h2>';
        
        $files = [
            'api/review-like.php' => 'API Like/Unlike',
            'api/get-reviews.php' => 'API Get Reviews',
            'assets/js/reviews.js' => 'JavaScript Reviews',
            'assets/css/reviews.css' => 'CSS Reviews',
            'config/add_review_likes.sql' => 'SQL Migration',
            'config/setup_review_likes.php' => 'Setup Script'
        ];
        
        foreach ($files as $file => $description) {
            if (file_exists($file)) {
                echo '<div class="status success"><i class="fas fa-check"></i> ' . $description . ': <code>' . $file . '</code></div>';
            } else {
                echo '<div class="status error"><i class="fas fa-times"></i> ' . $description . ': <code>' . $file . '</code> (Không tồn tại)</div>';
            }
        }
        
        echo '</div>';
        ?>

        <div style="text-align: center; margin-top: 2rem;">
            <a href="config/setup_review_likes.php" class="btn">
                <i class="fas fa-cog"></i> Chạy Setup
            </a>
            <a href="index.php" class="btn">
                <i class="fas fa-home"></i> Về trang chủ
            </a>
            <a href="pages/menu.php" class="btn">
                <i class="fas fa-utensils"></i> Xem menu
            </a>
        </div>
    </div>
</body>
</html>
