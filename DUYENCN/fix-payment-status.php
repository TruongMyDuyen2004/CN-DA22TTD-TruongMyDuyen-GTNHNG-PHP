<?php
/**
 * Script kiểm tra và sửa lỗi payment_status
 */

require_once __DIR__ . '/config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<h2>🔧 Kiểm tra và sửa lỗi Payment Status</h2>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} table{border-collapse:collapse;margin:20px 0;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f5f5f5;}</style>";

try {
    // 1. Kiểm tra cột payment_status
    echo "<h3>1. Kiểm tra cột payment_status</h3>";
    $checkCol = $conn->query("SHOW COLUMNS FROM orders LIKE 'payment_status'");
    
    if ($checkCol->rowCount() == 0) {
        echo "<p class='error'>❌ Cột payment_status CHƯA tồn tại!</p>";
        
        // Tạo cột mới
        $conn->exec("ALTER TABLE orders ADD COLUMN payment_status VARCHAR(20) DEFAULT 'pending' AFTER payment_method");
        echo "<p class='success'>✓ Đã tạo cột payment_status</p>";
        
        // Cập nhật đơn COD thành paid
        $conn->exec("UPDATE orders SET payment_status = 'paid' WHERE payment_method = 'cash'");
        echo "<p class='success'>✓ Đã cập nhật đơn COD thành 'paid'</p>";
    } else {
        $colInfo = $checkCol->fetch(PDO::FETCH_ASSOC);
        echo "<p class='success'>✓ Cột payment_status đã tồn tại</p>";
        echo "<p class='info'>Type: " . $colInfo['Type'] . "</p>";
        echo "<p class='info'>Default: " . ($colInfo['Default'] ?? 'NULL') . "</p>";
    }
    
    // 2. Hiển thị tất cả đơn hàng chuyển khoản
    echo "<h3>2. Danh sách đơn hàng chuyển khoản</h3>";
    $stmt = $conn->query("
        SELECT id, order_number, payment_method, payment_status, status, total_amount, created_at 
        FROM orders 
        WHERE payment_method = 'transfer'
        ORDER BY created_at DESC
        LIMIT 20
    ");
    $transferOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($transferOrders)) {
        echo "<p class='info'>Không có đơn hàng chuyển khoản nào.</p>";
    } else {
        echo "<table>";
        echo "<tr><th>ID</th><th>Mã đơn</th><th>Payment Method</th><th>Payment Status</th><th>Order Status</th><th>Tổng tiền</th><th>Ngày tạo</th><th>Action</th></tr>";
        foreach ($transferOrders as $order) {
            $statusClass = ($order['payment_status'] === 'paid') ? 'success' : 'error';
            echo "<tr>";
            echo "<td>{$order['id']}</td>";
            echo "<td>{$order['order_number']}</td>";
            echo "<td>{$order['payment_method']}</td>";
            echo "<td class='{$statusClass}'><strong>{$order['payment_status']}</strong></td>";
            echo "<td>{$order['status']}</td>";
            echo "<td>" . number_format($order['total_amount']) . "đ</td>";
            echo "<td>{$order['created_at']}</td>";
            echo "<td>";
            if ($order['payment_status'] !== 'paid') {
                echo "<a href='?confirm_payment={$order['id']}' style='background:#22c55e;color:white;padding:5px 10px;border-radius:5px;text-decoration:none;'>Xác nhận đã thanh toán</a>";
            } else {
                echo "<span class='success'>✓ Đã xác nhận</span>";
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 3. Xử lý xác nhận thanh toán từ URL
    if (isset($_GET['confirm_payment'])) {
        $orderId = (int)$_GET['confirm_payment'];
        $stmt = $conn->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = ?");
        $stmt->execute([$orderId]);
        echo "<p class='success'>✓ Đã xác nhận thanh toán cho đơn hàng ID: {$orderId}</p>";
        echo "<script>setTimeout(function(){ window.location.href = 'fix-payment-status.php'; }, 1000);</script>";
    }
    
    // 4. Kiểm tra giá trị NULL
    echo "<h3>3. Kiểm tra đơn hàng có payment_status = NULL</h3>";
    $stmt = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE payment_status IS NULL");
    $nullCount = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    
    if ($nullCount > 0) {
        echo "<p class='error'>⚠️ Có {$nullCount} đơn hàng có payment_status = NULL</p>";
        
        // Fix: Cập nhật NULL thành pending hoặc paid tùy payment_method
        $conn->exec("UPDATE orders SET payment_status = 'paid' WHERE payment_status IS NULL AND payment_method = 'cash'");
        $conn->exec("UPDATE orders SET payment_status = 'pending' WHERE payment_status IS NULL AND payment_method = 'transfer'");
        echo "<p class='success'>✓ Đã sửa các đơn hàng có payment_status = NULL</p>";
    } else {
        echo "<p class='success'>✓ Không có đơn hàng nào có payment_status = NULL</p>";
    }
    
    echo "<br><br>";
    echo "<a href='admin/orders.php' style='padding:12px 24px;background:#22c55e;color:white;text-decoration:none;border-radius:8px;margin-right:10px;'>← Quản lý đơn hàng (Admin)</a>";
    echo "<a href='fix-payment-status.php' style='padding:12px 24px;background:#3b82f6;color:white;text-decoration:none;border-radius:8px;'>🔄 Refresh</a>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Lỗi: " . $e->getMessage() . "</p>";
}
