<?php
session_start();
require_once __DIR__ . '/config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<h2>🔍 Debug Thẻ Thành Viên</h2>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} table{border-collapse:collapse;margin:20px 0;width:100%;} th,td{border:1px solid #ddd;padding:10px;text-align:left;} th{background:#f5f5f5;}</style>";

// 1. Kiểm tra session
echo "<h3>1. Session hiện tại</h3>";
if (isset($_SESSION['customer_id'])) {
    echo "<p class='success'>✓ Đã đăng nhập với customer_id = <strong>" . $_SESSION['customer_id'] . "</strong></p>";
    
    // Lấy thông tin customer
    $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$_SESSION['customer_id']]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($customer) {
        echo "<p class='info'>Tên: <strong>" . htmlspecialchars($customer['full_name']) . "</strong></p>";
        echo "<p class='info'>Email: " . htmlspecialchars($customer['email']) . "</p>";
        echo "<p class='info'>Phone: " . htmlspecialchars($customer['phone'] ?? 'N/A') . "</p>";
    }
} else {
    echo "<p class='error'>❌ Chưa đăng nhập! Vui lòng đăng nhập trước.</p>";
}

// 2. Kiểm tra tất cả thẻ thành viên
echo "<h3>2. Tất cả thẻ thành viên trong hệ thống</h3>";
try {
    $cards = $conn->query("
        SELECT mc.*, c.full_name, c.email, c.phone
        FROM member_cards mc
        JOIN customers c ON mc.customer_id = c.id
        ORDER BY mc.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($cards)) {
        echo "<p class='error'>Chưa có thẻ nào trong hệ thống!</p>";
    } else {
        echo "<table>";
        echo "<tr><th>ID</th><th>Customer ID</th><th>Số thẻ</th><th>Tên KH</th><th>Phone</th><th>Số dư</th><th>Status</th></tr>";
        foreach ($cards as $card) {
            $highlight = (isset($_SESSION['customer_id']) && $card['customer_id'] == $_SESSION['customer_id']) ? 'style="background:#dcfce7;"' : '';
            echo "<tr $highlight>";
            echo "<td>{$card['id']}</td>";
            echo "<td><strong>{$card['customer_id']}</strong></td>";
            echo "<td>{$card['card_number']}</td>";
            echo "<td>" . htmlspecialchars($card['full_name']) . "</td>";
            echo "<td>{$card['phone']}</td>";
            echo "<td>" . number_format($card['balance']) . "đ</td>";
            echo "<td>{$card['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Lỗi: " . $e->getMessage() . "</p>";
}

// 3. Kiểm tra thẻ của user hiện tại
echo "<h3>3. Thẻ của user đang đăng nhập</h3>";
if (isset($_SESSION['customer_id'])) {
    $stmt = $conn->prepare("SELECT * FROM member_cards WHERE customer_id = ?");
    $stmt->execute([$_SESSION['customer_id']]);
    $my_card = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($my_card) {
        echo "<p class='success'>✓ Tìm thấy thẻ!</p>";
        echo "<pre>" . print_r($my_card, true) . "</pre>";
    } else {
        echo "<p class='error'>❌ Không tìm thấy thẻ cho customer_id = " . $_SESSION['customer_id'] . "</p>";
        
        // Gợi ý
        echo "<h4>Gợi ý sửa lỗi:</h4>";
        echo "<p>Có thể admin đã tạo thẻ cho customer_id khác. Kiểm tra bảng trên để xem customer_id nào có thẻ.</p>";
    }
}

// 4. Danh sách tất cả customers
echo "<h3>4. Danh sách tất cả khách hàng</h3>";
$customers = $conn->query("SELECT id, full_name, email, phone FROM customers ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
echo "<table>";
echo "<tr><th>ID</th><th>Tên</th><th>Email</th><th>Phone</th><th>Có thẻ?</th></tr>";
foreach ($customers as $c) {
    $hasCard = $conn->prepare("SELECT id FROM member_cards WHERE customer_id = ?");
    $hasCard->execute([$c['id']]);
    $cardStatus = $hasCard->fetch() ? '<span class="success">✓ Có</span>' : '<span class="error">✗ Chưa</span>';
    
    $highlight = (isset($_SESSION['customer_id']) && $c['id'] == $_SESSION['customer_id']) ? 'style="background:#dbeafe;"' : '';
    echo "<tr $highlight>";
    echo "<td>{$c['id']}</td>";
    echo "<td>" . htmlspecialchars($c['full_name']) . "</td>";
    echo "<td>{$c['email']}</td>";
    echo "<td>{$c['phone']}</td>";
    echo "<td>$cardStatus</td>";
    echo "</tr>";
}
echo "</table>";

echo "<br><br>";
echo "<a href='index.php' style='padding:12px 24px;background:#22c55e;color:white;text-decoration:none;border-radius:8px;margin-right:10px;'>Về trang chủ</a>";
echo "<a href='?page=member-card' style='padding:12px 24px;background:#8b5cf6;color:white;text-decoration:none;border-radius:8px;'>Xem trang Thẻ Thành Viên</a>";
