<?php
/**
 * API Voucher - Kiểm tra và áp dụng mã giảm giá
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

$db = new Database();
$conn = $db->connect();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'check':
        checkVoucher($conn);
        break;
    case 'apply':
        applyVoucher($conn);
        break;
    case 'remove':
        removeVoucher();
        break;
    case 'list':
        listAvailableVouchers($conn);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

/**
 * Kiểm tra mã voucher có hợp lệ không
 */
function checkVoucher($conn) {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $order_total = floatval($_POST['order_total'] ?? 0);
    $customer_id = $_SESSION['customer_id'] ?? 0;
    
    // Debug log
    error_log("Voucher check - Code: $code, Order total: $order_total, Customer: $customer_id");
    
    if (empty($code)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng nhập mã voucher']);
        return;
    }
    
    try {
        // Tìm voucher (không cần is_active để debug)
        $stmt = $conn->prepare("SELECT * FROM vouchers WHERE code = ?");
        $stmt->execute([$code]);
        $voucher = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$voucher) {
            echo json_encode(['success' => false, 'message' => "Mã voucher '$code' không tồn tại trong hệ thống"]);
            return;
        }
        
        // Kiểm tra is_active
        if (!$voucher['is_active']) {
            echo json_encode(['success' => false, 'message' => 'Voucher này đã bị vô hiệu hóa']);
            return;
        }
        
        // Kiểm tra thời hạn
        $now = new DateTime();
        $start = new DateTime($voucher['start_date']);
        $end = new DateTime($voucher['end_date']);
        
        if ($now < $start) {
            echo json_encode(['success' => false, 'message' => 'Voucher chưa đến thời gian sử dụng (bắt đầu: ' . $start->format('d/m/Y H:i') . ')']);
            return;
        }
        
        if ($now > $end) {
            echo json_encode(['success' => false, 'message' => 'Voucher đã hết hạn (hết hạn: ' . $end->format('d/m/Y H:i') . ')']);
            return;
        }
        
        // Kiểm tra số lần sử dụng tổng
        if ($voucher['usage_limit'] !== null && $voucher['used_count'] >= $voucher['usage_limit']) {
            echo json_encode(['success' => false, 'message' => 'Voucher đã hết lượt sử dụng']);
            return;
        }
        
        // Kiểm tra số lần sử dụng của user
        if ($customer_id > 0) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM voucher_usage WHERE voucher_id = ? AND customer_id = ?");
            $stmt->execute([$voucher['id'], $customer_id]);
            $user_usage = $stmt->fetchColumn();
            
            if ($user_usage >= $voucher['usage_per_user']) {
                echo json_encode(['success' => false, 'message' => 'Bạn đã sử dụng hết lượt cho voucher này']);
                return;
            }
        }
        
        // Kiểm tra đơn tối thiểu
        if ($voucher['min_order_value'] > 0 && $order_total < $voucher['min_order_value']) {
            echo json_encode([
                'success' => false, 
                'message' => 'Đơn hàng tối thiểu ' . number_format($voucher['min_order_value']) . 'đ để sử dụng voucher này (đơn hiện tại: ' . number_format($order_total) . 'đ)'
            ]);
            return;
        }
        
        // Tính giảm giá
        $discount = calculateDiscount($voucher, $order_total);
        
        echo json_encode([
            'success' => true,
            'voucher' => [
                'id' => $voucher['id'],
                'code' => $voucher['code'],
                'name' => $voucher['name'],
                'description' => $voucher['description'],
                'discount_type' => $voucher['discount_type'],
                'discount_value' => $voucher['discount_value'],
                'max_discount' => $voucher['max_discount'],
                'min_order_value' => $voucher['min_order_value'],
                'discount_amount' => $discount
            ],
            'message' => 'Voucher hợp lệ! Giảm ' . number_format($discount) . 'đ'
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
    }
}

/**
 * Áp dụng voucher vào session
 */
function applyVoucher($conn) {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $order_total = floatval($_POST['order_total'] ?? 0);
    
    // Kiểm tra voucher trước
    ob_start();
    checkVoucher($conn);
    $result = json_decode(ob_get_clean(), true);
    
    if (!$result['success']) {
        echo json_encode($result);
        return;
    }
    
    // Lưu vào session
    $_SESSION['applied_voucher'] = [
        'id' => $result['voucher']['id'],
        'code' => $result['voucher']['code'],
        'name' => $result['voucher']['name'],
        'discount_type' => $result['voucher']['discount_type'],
        'discount_value' => $result['voucher']['discount_value'],
        'max_discount' => $result['voucher']['max_discount'],
        'discount_amount' => $result['voucher']['discount_amount']
    ];
    
    echo json_encode([
        'success' => true,
        'voucher' => $_SESSION['applied_voucher'],
        'message' => 'Áp dụng voucher thành công!'
    ]);
}

/**
 * Xóa voucher khỏi session
 */
function removeVoucher() {
    unset($_SESSION['applied_voucher']);
    echo json_encode(['success' => true, 'message' => 'Đã xóa voucher']);
}

/**
 * Lấy danh sách voucher khả dụng cho user
 */
function listAvailableVouchers($conn) {
    $customer_id = $_SESSION['customer_id'] ?? 0;
    $order_total = floatval($_GET['order_total'] ?? 0);
    
    try {
        $sql = "SELECT v.*, 
                (SELECT COUNT(*) FROM voucher_usage vu WHERE vu.voucher_id = v.id AND vu.customer_id = ?) as user_used
                FROM vouchers v 
                WHERE v.is_active = 1 
                AND v.start_date <= NOW() 
                AND v.end_date >= NOW()
                AND (v.usage_limit IS NULL OR v.used_count < v.usage_limit)
                ORDER BY v.discount_value DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$customer_id]);
        $vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $available = [];
        foreach ($vouchers as $v) {
            // Kiểm tra user đã dùng hết chưa
            if ($v['user_used'] >= $v['usage_per_user']) {
                continue;
            }
            
            $discount = calculateDiscount($v, $order_total);
            $can_use = $order_total >= $v['min_order_value'];
            
            $available[] = [
                'id' => $v['id'],
                'code' => $v['code'],
                'name' => $v['name'],
                'description' => $v['description'],
                'discount_type' => $v['discount_type'],
                'discount_value' => $v['discount_value'],
                'max_discount' => $v['max_discount'],
                'min_order_value' => $v['min_order_value'],
                'end_date' => $v['end_date'],
                'discount_amount' => $discount,
                'can_use' => $can_use,
                'remaining_uses' => $v['usage_per_user'] - $v['user_used']
            ];
        }
        
        echo json_encode(['success' => true, 'vouchers' => $available]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
}

/**
 * Tính số tiền giảm giá
 */
function calculateDiscount($voucher, $order_total) {
    if ($voucher['discount_type'] === 'percent') {
        $discount = $order_total * ($voucher['discount_value'] / 100);
        if ($voucher['max_discount'] && $discount > $voucher['max_discount']) {
            $discount = $voucher['max_discount'];
        }
    } else {
        $discount = $voucher['discount_value'];
    }
    
    // Không giảm quá tổng đơn
    if ($discount > $order_total) {
        $discount = $order_total;
    }
    
    return $discount;
}
