<?php
/**
 * Tạo thẻ thành viên cho user đang đăng nhập
 */
session_start();
require_once __DIR__ . '/config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<h2>Tạo thẻ thành viên</h2>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;}</style>";

$customer_id = 18; // ID của user đang đăng nhập

// Kiểm tra đã có thẻ chưa
$stmt = $conn->prepare("SELECT * FROM member_cards WHERE customer_id = ?");
$stmt->execute([$customer_id]);
$existing = $stmt->fetch();

if ($existing) {
    echo "<p class='error'>Customer ID $customer_id đã có thẻ: {$existing['card_number']}</p>";
    echo "<p>Số dư: " . number_format($existing['balance']) . "đ</p>";
} else {
    // Tạo thẻ mới
    $card_number = 'NG' . date('y') . str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT);
    $card_pin = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $initial_balance = 500000; // 500k
    
    $stmt = $conn->prepare("INSERT INTO member_cards (customer_id, card_number, card_pin, balance, total_deposited) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$customer_id, $card_number, $card_pin, $initial_balance, $initial_balance]);
    $card_id = $conn->lastInsertId();
    
    // Ghi lịch sử
    $stmt = $conn->prepare("INSERT INTO card_transactions (card_id, type, amount, balance_before, balance_after, description) VALUES (?, 'deposit', ?, 0, ?, 'Nạp tiền khi tạo thẻ')");
    $stmt->execute([$card_id, $initial_balance, $initial_balance]);
    
    echo "<p class='success'>✓ Tạo thẻ thành công!</p>";
    echo "<p><strong>Số thẻ:</strong> $card_number</p>";
    echo "<p><strong>PIN:</strong> $card_pin</p>";
    echo "<p><strong>Số dư:</strong> " . number_format($initial_balance) . "đ</p>";
}

echo "<br><a href='index.php?page=member-card' style='padding:10px 20px;background:#8b5cf6;color:white;text-decoration:none;border-radius:8px;'>Xem thẻ của tôi</a>";
echo " <a href='index.php?page=checkout' style='padding:10px 20px;background:#22c55e;color:white;text-decoration:none;border-radius:8px;margin-left:10px;'>Đi checkout</a>";
