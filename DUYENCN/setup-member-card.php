<?php
/**
 * Setup hệ thống Thẻ thành viên (Member Card)
 * Chạy file này 1 lần để tạo các bảng cần thiết
 */

require_once __DIR__ . '/config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<h2>🎴 Setup Hệ thống Thẻ Thành Viên</h2>";
echo "<style>body{font-family:Arial;padding:20px;max-width:900px;margin:0 auto;} .success{color:green;} .error{color:red;} .info{color:blue;} code{background:#f5f5f5;padding:2px 6px;border-radius:4px;}</style>";

try {
    // 1. Tạo bảng member_cards - Thẻ thành viên
    echo "<h3>1. Tạo bảng member_cards</h3>";
    $sql = "CREATE TABLE IF NOT EXISTS member_cards (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        card_number VARCHAR(20) NOT NULL UNIQUE,
        card_pin VARCHAR(6) NOT NULL,
        balance DECIMAL(12,0) DEFAULT 0,
        total_deposited DECIMAL(12,0) DEFAULT 0,
        total_spent DECIMAL(12,0) DEFAULT 0,
        status ENUM('active', 'inactive', 'blocked') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
        INDEX idx_card_number (card_number),
        INDEX idx_customer (customer_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->exec($sql);
    echo "<p class='success'>✓ Tạo bảng member_cards thành công!</p>";
    
    // 2. Tạo bảng card_transactions - Lịch sử giao dịch thẻ
    echo "<h3>2. Tạo bảng card_transactions</h3>";
    $sql = "CREATE TABLE IF NOT EXISTS card_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        card_id INT NOT NULL,
        type ENUM('deposit', 'payment', 'refund') NOT NULL,
        amount DECIMAL(12,0) NOT NULL,
        balance_before DECIMAL(12,0) NOT NULL,
        balance_after DECIMAL(12,0) NOT NULL,
        order_id INT NULL,
        description VARCHAR(255),
        admin_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (card_id) REFERENCES member_cards(id) ON DELETE CASCADE,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
        INDEX idx_card (card_id),
        INDEX idx_type (type),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->exec($sql);
    echo "<p class='success'>✓ Tạo bảng card_transactions thành công!</p>";
    
    // 3. Thêm cột card_id vào bảng orders (nếu chưa có)
    echo "<h3>3. Thêm cột card_id vào bảng orders</h3>";
    $checkCol = $conn->query("SHOW COLUMNS FROM orders LIKE 'card_id'");
    if ($checkCol->rowCount() == 0) {
        $conn->exec("ALTER TABLE orders ADD COLUMN card_id INT NULL AFTER payment_status");
        echo "<p class='success'>✓ Thêm cột card_id thành công!</p>";
    } else {
        echo "<p class='info'>Cột card_id đã tồn tại.</p>";
    }
    
    // 4. Cập nhật ENUM payment_method để thêm 'card'
    echo "<h3>4. Cập nhật payment_method</h3>";
    try {
        // Kiểm tra kiểu dữ liệu hiện tại
        $colInfo = $conn->query("SHOW COLUMNS FROM orders LIKE 'payment_method'")->fetch(PDO::FETCH_ASSOC);
        if (strpos($colInfo['Type'], 'card') === false) {
            // Nếu là VARCHAR thì không cần sửa
            if (strpos($colInfo['Type'], 'varchar') !== false) {
                echo "<p class='info'>payment_method là VARCHAR, không cần sửa.</p>";
            } else {
                // Nếu là ENUM thì thêm 'card'
                $conn->exec("ALTER TABLE orders MODIFY COLUMN payment_method VARCHAR(20) DEFAULT 'cash'");
                echo "<p class='success'>✓ Đã cập nhật payment_method thành VARCHAR!</p>";
            }
        } else {
            echo "<p class='info'>payment_method đã hỗ trợ 'card'.</p>";
        }
    } catch (Exception $e) {
        echo "<p class='info'>Bỏ qua: " . $e->getMessage() . "</p>";
    }
    
    echo "<br><h3 style='color:#22c55e;'>✓ Setup hoàn tất!</h3>";
    
    echo "<h3>📋 Hướng dẫn sử dụng:</h3>";
    echo "<ul>";
    echo "<li><strong>Khách hàng:</strong> Vào trang Profile > Thẻ thành viên để xem số dư và lịch sử</li>";
    echo "<li><strong>Admin:</strong> Vào Admin > Thẻ thành viên để nạp tiền cho khách</li>";
    echo "<li><strong>Thanh toán:</strong> Khi checkout, chọn 'Thẻ thành viên' để trừ tiền từ thẻ</li>";
    echo "</ul>";
    
    echo "<br>";
    echo "<a href='admin/member-cards.php' style='padding:12px 24px;background:#22c55e;color:white;text-decoration:none;border-radius:8px;margin-right:10px;'>Quản lý thẻ (Admin)</a>";
    echo "<a href='index.php' style='padding:12px 24px;background:#3b82f6;color:white;text-decoration:none;border-radius:8px;'>Về trang chủ</a>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Lỗi: " . $e->getMessage() . "</p>";
}
