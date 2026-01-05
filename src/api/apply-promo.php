<?php
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';
$db = new Database();
$conn = $db->connect();

$data = json_decode(file_get_contents('php://input'), true);
$code = strtoupper(trim($data['code'] ?? ''));
$subtotal = floatval($data['subtotal'] ?? 0);

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập mã giảm giá']);
    exit;
}

// Tìm mã khuyến mãi
$now = date('Y-m-d H:i:s');
$stmt = $conn->prepare("
    SELECT * FROM promotions 
    WHERE code = ? 
    AND is_active = 1 
    AND start_date <= ? 
    AND end_date >= ?
");
$stmt->execute([$code, $now, $now]);
$promo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$promo) {
    echo json_encode(['success' => false, 'message' => 'Mã không hợp lệ hoặc đã hết hạn']);
    exit;
}

// Kiểm tra giới hạn sử dụng
if ($promo['usage_limit'] && $promo['used_count'] >= $promo['usage_limit']) {
    echo json_encode(['success' => false, 'message' => 'Mã đã hết lượt sử dụng']);
    exit;
}

// Kiểm tra đơn tối thiểu
if ($subtotal < $promo['min_order_value']) {
    $min = number_format($promo['min_order_value'], 0, ',', '.');
    echo json_encode(['success' => false, 'message' => "Đơn hàng tối thiểu {$min}đ để sử dụng mã này"]);
    exit;
}

// Tính giảm giá
if ($promo['discount_type'] === 'percent') {
    $discount = $subtotal * ($promo['discount_value'] / 100);
    if ($promo['max_discount'] && $discount > $promo['max_discount']) {
        $discount = $promo['max_discount'];
    }
} else {
    $discount = $promo['discount_value'];
}

$discount = min($discount, $subtotal); // Không giảm quá tổng đơn

echo json_encode([
    'success' => true,
    'message' => "Áp dụng mã {$code} thành công!",
    'promo' => $promo,
    'discount' => $discount
]);
