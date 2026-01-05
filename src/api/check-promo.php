<?php
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

$code = strtoupper(trim($_POST['code'] ?? $_GET['code'] ?? ''));
$subtotal = floatval($_POST['subtotal'] ?? $_GET['subtotal'] ?? 0);

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập mã khuyến mãi']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT * FROM promotions WHERE code = ? AND is_active = 1 AND start_date <= NOW() AND end_date >= NOW()");
    $stmt->execute([$code]);
    $promo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$promo) {
        echo json_encode(['success' => false, 'message' => 'Mã khuyến mãi không hợp lệ hoặc đã hết hạn']);
        exit;
    }
    
    if ($subtotal < $promo['min_order_value']) {
        echo json_encode([
            'success' => false, 
            'message' => 'Đơn hàng tối thiểu: ' . number_format($promo['min_order_value'], 0, ',', '.') . 'đ'
        ]);
        exit;
    }
    
    if ($promo['usage_limit'] !== null && $promo['used_count'] >= $promo['usage_limit']) {
        echo json_encode(['success' => false, 'message' => 'Mã khuyến mãi đã hết lượt sử dụng']);
        exit;
    }
    
    // Tính giảm giá
    $discount = 0;
    if ($promo['discount_type'] === 'percent') {
        $discount = $subtotal * ($promo['discount_value'] / 100);
        if ($promo['max_discount'] && $discount > $promo['max_discount']) {
            $discount = $promo['max_discount'];
        }
    } else {
        $discount = $promo['discount_value'];
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Áp dụng mã thành công!',
        'promo' => [
            'code' => $promo['code'],
            'name' => $promo['name'],
            'discount_type' => $promo['discount_type'],
            'discount_value' => $promo['discount_value'],
            'discount_amount' => $discount,
            'discount_formatted' => number_format($discount, 0, ',', '.') . 'đ'
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Hệ thống khuyến mãi chưa sẵn sàng']);
}
