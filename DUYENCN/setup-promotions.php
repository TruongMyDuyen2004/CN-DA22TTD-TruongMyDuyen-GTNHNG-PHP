<?php
/**
 * SETUP HỆ THỐNG MÃ KHUYẾN MÃI
 * Chạy file này 1 lần để tạo bảng và thêm mã mẫu
 */

require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<h2>🎫 Setup Hệ Thống Mã Khuyến Mãi</h2>";

try {
    // Tạo bảng promotions
    $conn->exec("
        CREATE TABLE IF NOT EXISTS promotions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50) UNIQUE NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            discount_type ENUM('percent', 'fixed') DEFAULT 'percent',
            discount_value DECIMAL(10,2) NOT NULL,
            min_order_value DECIMAL(10,2) DEFAULT 0,
            max_discount DECIMAL(10,2) DEFAULT NULL,
            usage_limit INT DEFAULT NULL,
            used_count INT DEFAULT 0,
            start_date DATETIME NOT NULL,
            end_date DATETIME NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p>✅ Tạo bảng promotions thành công!</p>";
    
    // Kiểm tra đã có dữ liệu chưa
    $stmt = $conn->query("SELECT COUNT(*) FROM promotions");
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        // Thêm mã mẫu
        $promos = [
            ['NEWUSER10', 'Giảm 10% cho khách mới', 'Áp dụng cho khách hàng đặt đơn lần đầu', 'percent', 10, 100000, 50000, 100],
            ['SALE20', 'Giảm 20% đơn từ 300K', 'Giảm 20% cho đơn hàng từ 300.000đ', 'percent', 20, 300000, 100000, 50],
            ['GIAM50K', 'Giảm ngay 50.000đ', 'Giảm 50.000đ cho đơn từ 200.000đ', 'fixed', 50000, 200000, null, 200],
            ['FREESHIP', 'Miễn phí giao hàng', 'Miễn phí giao hàng cho đơn từ 150.000đ', 'fixed', 20000, 150000, null, null],
            ['VIP30', 'Ưu đãi VIP 30%', 'Dành cho khách hàng VIP', 'percent', 30, 500000, 200000, 20],
            ['WEEKEND15', 'Ưu đãi cuối tuần 15%', 'Giảm 15% cho đơn hàng cuối tuần', 'percent', 15, 200000, 80000, 100],
        ];
        
        $stmt = $conn->prepare("
            INSERT INTO promotions (code, name, description, discount_type, discount_value, min_order_value, max_discount, usage_limit, start_date, end_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 3 MONTH))
        ");
        
        foreach ($promos as $p) {
            $stmt->execute($p);
        }
        
        echo "<p>✅ Đã thêm " . count($promos) . " mã khuyến mãi mẫu!</p>";
    } else {
        echo "<p>ℹ️ Đã có $count mã khuyến mãi trong hệ thống</p>";
    }
    
    // Hiển thị danh sách mã
    echo "<h3>📋 Danh sách mã khuyến mãi:</h3>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f59e0b; color: white;'><th>Mã</th><th>Tên</th><th>Giảm</th><th>Đơn tối thiểu</th><th>Trạng thái</th></tr>";
    
    $stmt = $conn->query("SELECT * FROM promotions ORDER BY created_at DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $discount = $row['discount_type'] === 'percent' 
            ? $row['discount_value'] . '%' 
            : number_format($row['discount_value'], 0, ',', '.') . 'đ';
        $status = $row['is_active'] ? '✅ Hoạt động' : '❌ Tắt';
        
        echo "<tr>";
        echo "<td><strong style='color: #dc2626;'>{$row['code']}</strong></td>";
        echo "<td>{$row['name']}</td>";
        echo "<td>-$discount</td>";
        echo "<td>" . number_format($row['min_order_value'], 0, ',', '.') . "đ</td>";
        echo "<td>$status</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br><p><a href='admin/promotions.php' style='background: #f97316; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px;'>🔧 Quản lý mã khuyến mãi</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Lỗi: " . $e->getMessage() . "</p>";
}
?>
