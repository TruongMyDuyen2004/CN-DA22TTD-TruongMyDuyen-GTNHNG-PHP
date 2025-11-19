<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài đặt hệ thống đánh giá</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            max-width: 800px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        h1 {
            color: #1e293b;
            margin-bottom: 1rem;
            font-size: 2rem;
        }
        
        .subtitle {
            color: #64748b;
            margin-bottom: 2rem;
        }
        
        .output {
            background: #1e293b;
            color: #10b981;
            padding: 1.5rem;
            border-radius: 12px;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
            margin: 2rem 0;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .success {
            color: #10b981;
        }
        
        .error {
            color: #ef4444;
        }
        
        .info {
            color: #3b82f6;
        }
        
        .btn {
            display: inline-block;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 700;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
        }
        
        .btn-secondary:hover {
            background: #e2e8f0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .status-badge.success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-badge.error {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Cài đặt hệ thống đánh giá</h1>
        <p class="subtitle">Tạo các bảng cần thiết cho hệ thống đánh giá, like và bình luận</p>
        
        <?php
        require_once 'config/database.php';
        
        $success = true;
        $messages = [];
        
        try {
            $db = new Database();
            $conn = $db->connect();
            
            $messages[] = ['type' => 'info', 'text' => 'Đang kết nối database...'];
            $messages[] = ['type' => 'success', 'text' => '✓ Kết nối thành công!'];
            
            // 1. Tạo bảng review_likes
            $messages[] = ['type' => 'info', 'text' => "\n1. Tạo bảng review_likes..."];
            $conn->exec("CREATE TABLE IF NOT EXISTS review_likes (
                id INT PRIMARY KEY AUTO_INCREMENT,
                review_id INT NOT NULL,
                customer_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_like (review_id, customer_id),
                FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
                FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
                INDEX idx_review_id (review_id),
                INDEX idx_customer_id (customer_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $messages[] = ['type' => 'success', 'text' => '✓ Bảng review_likes đã sẵn sàng'];
            
            // 2. Tạo bảng review_comments
            $messages[] = ['type' => 'info', 'text' => "\n2. Tạo bảng review_comments..."];
            $conn->exec("CREATE TABLE IF NOT EXISTS review_comments (
                id INT PRIMARY KEY AUTO_INCREMENT,
                review_id INT NOT NULL,
                customer_id INT NOT NULL,
                comment TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
                FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
                INDEX idx_review_id (review_id),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $messages[] = ['type' => 'success', 'text' => '✓ Bảng review_comments đã sẵn sàng'];
            
            // 3. Thêm cột comments_count
            $messages[] = ['type' => 'info', 'text' => "\n3. Thêm cột comments_count vào bảng reviews..."];
            try {
                $conn->exec("ALTER TABLE reviews ADD COLUMN comments_count INT DEFAULT 0");
                $messages[] = ['type' => 'success', 'text' => '✓ Đã thêm cột comments_count'];
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                    $messages[] = ['type' => 'success', 'text' => '✓ Cột comments_count đã tồn tại'];
                } else {
                    throw $e;
                }
            }
            
            // 4. Thống kê
            $messages[] = ['type' => 'info', 'text' => "\n4. Thống kê dữ liệu:"];
            
            $stmt = $conn->query("SELECT COUNT(*) as count FROM reviews");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            $messages[] = ['type' => 'info', 'text' => "   - Đánh giá: {$count} bản ghi"];
            
            $stmt = $conn->query("SELECT COUNT(*) as count FROM review_likes");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            $messages[] = ['type' => 'info', 'text' => "   - Lượt thích: {$count} bản ghi"];
            
            $stmt = $conn->query("SELECT COUNT(*) as count FROM review_comments");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            $messages[] = ['type' => 'info', 'text' => "   - Bình luận: {$count} bản ghi"];
            
            $messages[] = ['type' => 'success', 'text' => "\n✅ Hoàn tất! Hệ thống đã sẵn sàng."];
            
        } catch (PDOException $e) {
            $success = false;
            $messages[] = ['type' => 'error', 'text' => "\n❌ Lỗi: " . $e->getMessage()];
            $messages[] = ['type' => 'error', 'text' => "\nVui lòng kiểm tra:"];
            $messages[] = ['type' => 'error', 'text' => "1. Kết nối database trong config/database.php"];
            $messages[] = ['type' => 'error', 'text' => "2. Bảng reviews đã tồn tại chưa"];
            $messages[] = ['type' => 'error', 'text' => "3. Quyền truy cập database"];
        }
        ?>
        
        <?php if ($success): ?>
            <div class="status-badge success">✓ Cài đặt thành công</div>
        <?php else: ?>
            <div class="status-badge error">✗ Cài đặt thất bại</div>
        <?php endif; ?>
        
        <div class="output">
<?php
foreach ($messages as $msg) {
    $class = $msg['type'];
    echo "<span class='{$class}'>{$msg['text']}</span>\n";
}
?>
        </div>
        
        <div class="actions">
            <a href="index.php" class="btn">Về trang chủ</a>
            <a href="admin/reviews.php" class="btn btn-secondary">Quản lý đánh giá</a>
            <a href="setup-review-system.php" class="btn btn-secondary">Chạy lại</a>
        </div>
    </div>
</body>
</html>
