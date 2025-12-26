<?php
/**
 * API áp dụng khuyến mãi cho giỏ hàng và đặt bàn
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

// Lấy dữ liệu từ request
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_promotions':
        // Lấy danh sách khuyến mãi có thể áp dụng
        getAvailablePromotions($conn, $input);
        break;
    case 'apply':
        // Áp dụng khuyến mãi
        applyPromotion($conn, $input);
        break;
    case 'validate_code':
        // Kiểm tra mã khuyến mãi
        validatePromoCode($conn, $input);
        break;
    case 'remove':
        // Xóa khuyến mãi đã áp dụng
        removePromotion($conn, $input);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

/**
 * Lấy danh sách khuyến mãi có thể áp dụng
 */
function getAvailablePromotions($conn, $input) {
    $type = $input['type'] ?? 'cart'; // cart hoặc reservation
    $order_total = floatval($input['order_total'] ?? 0);
    $customer_id = $_SESSION['customer_id'] ?? null;
    
    $now = date('Y-m-d');
    
    // Lấy khuyến mãi đang hoạt động
    $sql = "SELECT * FROM restaurant_promotions 
            WHERE is_active = 1 
            AND (start_date IS NULL OR start_date <= ?)
            AND (end_date IS NULL OR end_date >= ?)
            ORDER BY discount_percent DESC, display_order ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$now, $now]);
    $promotions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $available = [];
    foreach ($promotions as $promo) {
        // Kiểm tra điều kiện áp dụng
        $can_apply = true;
        $reason = '';
        
        // Kiểm tra đơn tối thiểu
        if ($promo['min_order_value'] > 0 && $order_total < $promo['min_order_value']) {
            $can_apply = false;
            $reason = 'Đơn hàng tối thiểu ' . number_format($promo['min_order_value'], 0, ',', '.') . 'đ';
        }
        
        // Kiểm tra giới hạn sử dụng
        if ($promo['usage_limit'] > 0 && $promo['used_count'] >= $promo['usage_limit']) {
            $can_apply = false;
            $reason = 'Đã hết lượt sử dụng';
        }
        
        // Tính số tiền giảm
        $discount_amount = 0;
        if ($promo['discount_percent'] > 0) {
            $discount_amount = $order_total * $promo['discount_percent'] / 100;
            // Giới hạn giảm tối đa
            if ($promo['max_discount'] > 0 && $discount_amount > $promo['max_discount']) {
                $discount_amount = $promo['max_discount'];
            }
        }
        
        $available[] = [
            'id' => $promo['id'],
            'title' => $promo['title'],
            'title_en' => $promo['title_en'],
            'description' => $promo['description'],
            'promo_type' => $promo['promo_type'],
            'discount_text' => $promo['discount_text'],
            'discount_percent' => $promo['discount_percent'],
            'coupon_code' => $promo['coupon_code'],
            'min_order_value' => $promo['min_order_value'],
            'max_discount' => $promo['max_discount'],
            'discount_amount' => $discount_amount,
            'can_apply' => $can_apply,
            'reason' => $reason,
            'terms' => $promo['terms']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'promotions' => $available,
        'order_total' => $order_total
    ]);
}

/**
 * Áp dụng khuyến mãi
 */
function applyPromotion($conn, $input) {
    $promo_id = intval($input['promo_id'] ?? 0);
    $order_total = floatval($input['order_total'] ?? 0);
    $type = $input['type'] ?? 'cart';
    
    if (!$promo_id) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng chọn khuyến mãi']);
        return;
    }
    
    $now = date('Y-m-d');
    
    // Lấy thông tin khuyến mãi
    $stmt = $conn->prepare("SELECT * FROM restaurant_promotions WHERE id = ? AND is_active = 1");
    $stmt->execute([$promo_id]);
    $promo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$promo) {
        echo json_encode(['success' => false, 'message' => 'Khuyến mãi không tồn tại hoặc đã hết hạn']);
        return;
    }
    
    // Kiểm tra thời gian
    if ($promo['start_date'] && $promo['start_date'] > $now) {
        echo json_encode(['success' => false, 'message' => 'Khuyến mãi chưa bắt đầu']);
        return;
    }
    if ($promo['end_date'] && $promo['end_date'] < $now) {
        echo json_encode(['success' => false, 'message' => 'Khuyến mãi đã hết hạn']);
        return;
    }
    
    // Kiểm tra đơn tối thiểu
    if ($promo['min_order_value'] > 0 && $order_total < $promo['min_order_value']) {
        echo json_encode([
            'success' => false, 
            'message' => 'Đơn hàng tối thiểu ' . number_format($promo['min_order_value'], 0, ',', '.') . 'đ để áp dụng khuyến mãi này'
        ]);
        return;
    }
    
    // Kiểm tra giới hạn sử dụng
    if ($promo['usage_limit'] > 0 && $promo['used_count'] >= $promo['usage_limit']) {
        echo json_encode(['success' => false, 'message' => 'Khuyến mãi đã hết lượt sử dụng']);
        return;
    }
    
    // Tính số tiền giảm
    $discount_amount = 0;
    $gift_item = null;
    
    if ($promo['discount_percent'] > 0) {
        $discount_amount = $order_total * $promo['discount_percent'] / 100;
        // Giới hạn giảm tối đa
        if ($promo['max_discount'] > 0 && $discount_amount > $promo['max_discount']) {
            $discount_amount = $promo['max_discount'];
        }
    }
    
    // Nếu là khuyến mãi đặt bàn, có thể tặng nước
    if ($type === 'reservation' && $promo['promo_type'] === 'member') {
        $gift_item = 'Tặng 1 ly nước ngọt';
    }
    
    // Lưu vào session
    $_SESSION['applied_promotion'] = [
        'id' => $promo['id'],
        'title' => $promo['title'],
        'discount_percent' => $promo['discount_percent'],
        'discount_amount' => $discount_amount,
        'coupon_code' => $promo['coupon_code'],
        'gift_item' => $gift_item,
        'type' => $type
    ];
    
    $final_total = $order_total - $discount_amount;
    
    echo json_encode([
        'success' => true,
        'message' => 'Áp dụng khuyến mãi thành công!',
        'promo' => [
            'id' => $promo['id'],
            'title' => $promo['title'],
            'discount_percent' => $promo['discount_percent'],
            'discount_amount' => $discount_amount,
            'discount_text' => $promo['discount_text'],
            'gift_item' => $gift_item
        ],
        'order_total' => $order_total,
        'final_total' => $final_total
    ]);
}

/**
 * Kiểm tra mã khuyến mãi
 */
function validatePromoCode($conn, $input) {
    $code = strtoupper(trim($input['code'] ?? ''));
    $order_total = floatval($input['order_total'] ?? 0);
    
    if (empty($code)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng nhập mã khuyến mãi']);
        return;
    }
    
    $now = date('Y-m-d');
    
    // Tìm khuyến mãi theo mã
    $stmt = $conn->prepare("
        SELECT * FROM restaurant_promotions 
        WHERE coupon_code = ? 
        AND is_active = 1
        AND (start_date IS NULL OR start_date <= ?)
        AND (end_date IS NULL OR end_date >= ?)
    ");
    $stmt->execute([$code, $now, $now]);
    $promo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$promo) {
        echo json_encode(['success' => false, 'message' => 'Mã khuyến mãi không hợp lệ hoặc đã hết hạn']);
        return;
    }
    
    // Kiểm tra đơn tối thiểu
    if ($promo['min_order_value'] > 0 && $order_total < $promo['min_order_value']) {
        echo json_encode([
            'success' => false, 
            'message' => 'Đơn hàng tối thiểu ' . number_format($promo['min_order_value'], 0, ',', '.') . 'đ'
        ]);
        return;
    }
    
    // Tính số tiền giảm
    $discount_amount = 0;
    if ($promo['discount_percent'] > 0) {
        $discount_amount = $order_total * $promo['discount_percent'] / 100;
        if ($promo['max_discount'] > 0 && $discount_amount > $promo['max_discount']) {
            $discount_amount = $promo['max_discount'];
        }
    }
    
    // Lưu vào session
    $_SESSION['applied_promotion'] = [
        'id' => $promo['id'],
        'title' => $promo['title'],
        'discount_percent' => $promo['discount_percent'],
        'discount_amount' => $discount_amount,
        'coupon_code' => $promo['coupon_code'],
        'type' => 'cart'
    ];
    
    echo json_encode([
        'success' => true,
        'message' => 'Áp dụng mã thành công!',
        'promo' => [
            'id' => $promo['id'],
            'title' => $promo['title'],
            'discount_percent' => $promo['discount_percent'],
            'discount_amount' => $discount_amount,
            'discount_text' => $promo['discount_text']
        ],
        'final_total' => $order_total - $discount_amount
    ]);
}

/**
 * Xóa khuyến mãi đã áp dụng
 */
function removePromotion($conn, $input) {
    unset($_SESSION['applied_promotion']);
    echo json_encode([
        'success' => true,
        'message' => 'Đã xóa khuyến mãi'
    ]);
}
?>
