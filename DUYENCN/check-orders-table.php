<?php
/**
 * Kiểm tra cấu trúc bảng orders và order_items
 */
require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<h2>Kiểm tra bảng Orders</h2>";

// Kiểm tra bảng orders
try {
    $stmt = $conn->query("DESCRIBE orders");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Cấu trúc bảng orders:</h3>";
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
} catch (PDOException $e) {
    echo "<p style='color:red'>Lỗi bảng orders: " . $e->getMessage() . "</p>";
    
    // Tạo bảng orders nếu chưa có
    echo "<p>Đang tạo bảng orders...</p>";
    $sql = "CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        order_number VARCHAR(50) UNIQUE NOT NULL,
        delivery_address TEXT NOT NULL,
        delivery_phone VARCHAR(20) NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        delivery_fee DECIMAL(10,2) DEFAULT 0,
        discount_amount DECIMAL(10,2) DEFAULT 0,
        promo_code VARCHAR(50) DEFAULT NULL,
        note TEXT,
        payment_method ENUM('cash', 'transfer') DEFAULT 'cash',
        status ENUM('pending', 'confirmed', 'preparing', 'delivering', 'completed', 'cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
    )";
    
    try {
        $conn->exec($sql);
        echo "<p style='color:green'>Đã tạo bảng orders thành công!</p>";
    } catch (PDOException $e2) {
        echo "<p style='color:red'>Không thể tạo bảng: " . $e2->getMessage() . "</p>";
    }
}

// Kiểm tra bảng order_items
echo "<h3>Cấu trúc bảng order_items:</h3>";
try {
    $stmt = $conn->query("DESCRIBE order_items");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
} catch (PDOException $e) {
    echo "<p style='color:red'>Lỗi bảng order_items: " . $e->getMessage() . "</p>";
    
    // Tạo bảng order_items nếu chưa có
    echo "<p>Đang tạo bảng order_items...</p>";
    $sql = "CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        menu_item_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        price DECIMAL(10,2) NOT NULL,
        note TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE
    )";
    
    try {
        $conn->exec($sql);
        echo "<p style='color:green'>Đã tạo bảng order_items thành công!</p>";
    } catch (PDOException $e2) {
        echo "<p style='color:red'>Không thể tạo bảng: " . $e2->getMessage() . "</p>";
    }
}

echo "<h3>Kiểm tra hoàn tất!</h3>";
echo "<p><a href='index.php?page=checkout'>Quay lại trang thanh toán</a></p>";
?>
