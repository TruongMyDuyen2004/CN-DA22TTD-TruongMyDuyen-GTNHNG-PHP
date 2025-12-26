<?php
/**
 * Setup/Update bảng topup_requests
 * Chạy file này để cập nhật cấu trúc bảng
 */

require_once __DIR__ . '/config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<h2>🔧 Setup Bảng Yêu Cầu Nạp Tiền</h2>";
echo "<style>body{font-family:Arial;padding:20px;max-width:900px;margin:0 auto;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

try {
    // Kiểm tra bảng có tồn tại không
    $tableExists = $conn->query("SHOW TABLES LIKE 'topup_requests'")->rowCount() > 0;
    
    if ($tableExists) {
        echo "<p class='info'>Bảng topup_requests đã tồn tại. Đang cập nhật...</p>";
        
        // Cập nhật ENUM status để thêm 'waiting'
        $conn->exec("ALTER TABLE topup_requests MODIFY COLUMN status ENUM('pending', 'waiting', 'completed', 'failed', 'expired') DEFAULT 'pending'");
        echo "<p class='success'>✓ Đã cập nhật cột status!</p>";
        
    } else {
        // Tạo bảng mới
        $conn->exec("CREATE TABLE topup_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            card_id INT NOT NULL,
            transaction_code VARCHAR(30) NOT NULL UNIQUE,
            amount DECIMAL(12,0) NOT NULL,
            method VARCHAR(20) NOT NULL,
            status ENUM('pending', 'waiting', 'completed', 'failed', 'expired') DEFAULT 'pending',
            payment_info TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            completed_at TIMESTAMP NULL,
            INDEX idx_customer (customer_id),
            INDEX idx_code (transaction_code),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "<p class='success'>✓ Đã tạo bảng topup_requests!</p>";
    }
    
    // Hiển thị dữ liệu hiện có
    $count = $conn->query("SELECT COUNT(*) FROM topup_requests")->fetchColumn();
    echo "<p>Số yêu cầu hiện có: <strong>$count</strong></p>";
    
    // Hiển thị các yêu cầu đang chờ
    $waiting = $conn->query("SELECT * FROM topup_requests WHERE status = 'waiting' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Yêu cầu đang chờ duyệt: " . count($waiting) . "</h3>";
    
    if (!empty($waiting)) {
        echo "<table border='1' cellpadding='10' style='border-collapse:collapse;'>";
        echo "<tr><th>ID</th><th>Mã GD</th><th>Số tiền</th><th>Phương thức</th><th>Trạng thái</th><th>Thời gian</th></tr>";
        foreach ($waiting as $req) {
            echo "<tr>";
            echo "<td>{$req['id']}</td>";
            echo "<td>{$req['transaction_code']}</td>";
            echo "<td>" . number_format($req['amount']) . "đ</td>";
            echo "<td>{$req['method']}</td>";
            echo "<td>{$req['status']}</td>";
            echo "<td>{$req['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Hiển thị tất cả yêu cầu
    $all = $conn->query("SELECT * FROM topup_requests ORDER BY created_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Tất cả yêu cầu gần đây:</h3>";
    
    if (!empty($all)) {
        echo "<table border='1' cellpadding='10' style='border-collapse:collapse;'>";
        echo "<tr><th>ID</th><th>Customer ID</th><th>Mã GD</th><th>Số tiền</th><th>Phương thức</th><th>Trạng thái</th><th>Thời gian</th></tr>";
        foreach ($all as $req) {
            $statusColor = $req['status'] == 'waiting' ? 'orange' : ($req['status'] == 'completed' ? 'green' : 'gray');
            echo "<tr>";
            echo "<td>{$req['id']}</td>";
            echo "<td>{$req['customer_id']}</td>";
            echo "<td>{$req['transaction_code']}</td>";
            echo "<td>" . number_format($req['amount']) . "đ</td>";
            echo "<td>{$req['method']}</td>";
            echo "<td style='color:$statusColor;font-weight:bold;'>{$req['status']}</td>";
            echo "<td>{$req['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Chưa có yêu cầu nào.</p>";
    }
    
    echo "<br><br>";
    echo "<a href='admin/topup-requests.php' style='padding:12px 24px;background:#f59e0b;color:white;text-decoration:none;border-radius:8px;'>Đi đến trang Admin</a>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Lỗi: " . $e->getMessage() . "</p>";
}
