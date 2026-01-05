<?php
/**
 * API Points - Quản lý điểm tích lũy
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

$db = new Database();
$conn = $db->connect();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get':
        getCustomerPoints($conn);
        break;
    case 'calculate':
        calculatePointsDiscount($conn);
        break;
    case 'apply':
        applyPointsDiscount($conn);
        break;
    case 'remove':
        removePointsDiscount();
        break;
    case 'history':
        getPointsHistory($conn);
        break;
    case 'admin_history':
        getAdminPointsHistory($conn);
        break;
    case 'earn':
        earnPoints($conn);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

/**
 * Lấy thông tin điểm của khách hàng
 */
function getCustomerPoints($conn) {
    $customer_id = $_SESSION['customer_id'] ?? 0;
    
    if (!$customer_id) {
        echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM customer_points WHERE customer_id = ?");
        $stmt->execute([$customer_id]);
        $points = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$points) {
            $points = [
                'total_points' => 0,
                'available_points' => 0,
                'used_points' => 0,
                'tier' => 'bronze'
            ];
        }
        
        // Lấy cấu hình
        $settings = getPointSettings($conn);
        
        // Tính giá trị quy đổi
        $points['points_value'] = $points['available_points'] * $settings['points_to_money'];
        $points['tier_info'] = getTierInfo($points['tier'], $settings);
        $points['next_tier'] = getNextTierInfo($points['total_points'], $settings);
        
        echo json_encode(['success' => true, 'points' => $points, 'settings' => $settings]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
}

/**
 * Tính số điểm có thể dùng và giảm giá tương ứng
 */
function calculatePointsDiscount($conn) {
    $customer_id = $_SESSION['customer_id'] ?? 0;
    $order_total = floatval($_POST['order_total'] ?? 0);
    $points_to_use = intval($_POST['points'] ?? 0);
    
    if (!$customer_id) {
        echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
        return;
    }
    
    try {
        $settings = getPointSettings($conn);
        
        // Lấy điểm khả dụng
        $stmt = $conn->prepare("SELECT available_points FROM customer_points WHERE customer_id = ?");
        $stmt->execute([$customer_id]);
        $available = $stmt->fetchColumn() ?: 0;
        
        // Kiểm tra điểm phải > 0
        if ($points_to_use <= 0) {
            echo json_encode([
                'success' => false, 
                'message' => 'Vui lòng nhập số điểm muốn sử dụng'
            ]);
            return;
        }
        
        // Không dùng quá số điểm có
        if ($points_to_use > $available) {
            $points_to_use = $available;
        }
        
        // Tính giảm giá
        $discount = $points_to_use * $settings['points_to_money'];
        
        // Giới hạn không vượt quá tổng đơn hàng (cho phép dùng tất cả điểm)
        if ($discount > $order_total) {
            $discount = $order_total;
            $points_to_use = ceil($discount / $settings['points_to_money']);
        }
        
        echo json_encode([
            'success' => true,
            'points_to_use' => $points_to_use,
            'discount_amount' => $discount,
            'available_points' => $available,
            'remaining_points' => $available - $points_to_use
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
}

/**
 * Áp dụng điểm vào session
 */
function applyPointsDiscount($conn) {
    $customer_id = $_SESSION['customer_id'] ?? 0;
    $order_total = floatval($_POST['order_total'] ?? 0);
    $points_to_use = intval($_POST['points'] ?? 0);
    
    // Tính toán trước
    $_POST['order_total'] = $order_total;
    $_POST['points'] = $points_to_use;
    
    ob_start();
    calculatePointsDiscount($conn);
    $result = json_decode(ob_get_clean(), true);
    
    if (!$result['success']) {
        echo json_encode($result);
        return;
    }
    
    // Lưu vào session
    $_SESSION['applied_points'] = [
        'points' => $result['points_to_use'],
        'discount_amount' => $result['discount_amount']
    ];
    
    echo json_encode([
        'success' => true,
        'applied_points' => $_SESSION['applied_points'],
        'message' => 'Đã áp dụng ' . number_format($result['points_to_use']) . ' điểm, giảm ' . number_format($result['discount_amount']) . 'đ'
    ]);
}

/**
 * Xóa điểm khỏi session
 */
function removePointsDiscount() {
    unset($_SESSION['applied_points']);
    echo json_encode(['success' => true, 'message' => 'Đã hủy sử dụng điểm']);
}

/**
 * Lấy lịch sử điểm
 */
function getPointsHistory($conn) {
    $customer_id = $_SESSION['customer_id'] ?? 0;
    $filter = $_GET['filter'] ?? 'all';
    $page = max(1, intval($_GET['page'] ?? 1));
    $per_page = 10;
    $offset = ($page - 1) * $per_page;
    
    if (!$customer_id) {
        echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
        return;
    }
    
    try {
        // Đếm tổng
        $count_sql = "SELECT COUNT(*) FROM point_transactions WHERE customer_id = ?";
        $count_params = [$customer_id];
        if ($filter !== 'all') {
            $count_sql .= " AND type = ?";
            $count_params[] = $filter;
        }
        $stmt = $conn->prepare($count_sql);
        $stmt->execute($count_params);
        $total = $stmt->fetchColumn();
        $total_pages = ceil($total / $per_page);
        
        // Lấy dữ liệu
        $sql = "
            SELECT pt.*, o.order_number 
            FROM point_transactions pt
            LEFT JOIN orders o ON pt.order_id = o.id
            WHERE pt.customer_id = ?
        ";
        $params = [$customer_id];
        if ($filter !== 'all') {
            $sql .= " AND pt.type = ?";
            $params[] = $filter;
        }
        $sql .= " ORDER BY pt.created_at DESC LIMIT $per_page OFFSET $offset";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format ngày
        foreach ($history as &$h) {
            $h['created_at_formatted'] = date('d/m/Y H:i', strtotime($h['created_at']));
        }
        
        echo json_encode([
            'success' => true, 
            'data' => [
                'history' => $history,
                'total' => $total,
                'total_pages' => $total_pages,
                'current_page' => $page,
                'per_page' => $per_page
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
}

/**
 * Tích điểm sau khi đặt hàng (gọi từ checkout)
 */
function earnPoints($conn) {
    $customer_id = intval($_POST['customer_id'] ?? 0);
    $order_id = intval($_POST['order_id'] ?? 0);
    $order_total = floatval($_POST['order_total'] ?? 0);
    
    if (!$customer_id || !$order_id || $order_total <= 0) {
        echo json_encode(['success' => false, 'message' => 'Thiếu thông tin']);
        return;
    }
    
    try {
        $conn->beginTransaction();
        
        $settings = getPointSettings($conn);
        
        // Tính điểm cơ bản
        $base_points = floor($order_total / $settings['points_per_order']);
        
        // Lấy tier hiện tại để tính bonus
        $stmt = $conn->prepare("SELECT tier, available_points, total_points FROM customer_points WHERE customer_id = ?");
        $stmt->execute([$customer_id]);
        $cp = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $tier = $cp['tier'] ?? 'bronze';
        $bonus_percent = $settings['bonus_' . $tier] ?? 0;
        $bonus_points = floor($base_points * $bonus_percent / 100);
        $total_earned = $base_points + $bonus_points;
        
        $balance_before = $cp['available_points'] ?? 0;
        $balance_after = $balance_before + $total_earned;
        $new_total = ($cp['total_points'] ?? 0) + $total_earned;
        
        if (!$cp) {
            // Tạo mới
            $stmt = $conn->prepare("INSERT INTO customer_points (customer_id, total_points, available_points) VALUES (?, ?, ?)");
            $stmt->execute([$customer_id, $total_earned, $total_earned]);
            $balance_before = 0;
            $balance_after = $total_earned;
        } else {
            // Cập nhật
            $stmt = $conn->prepare("UPDATE customer_points SET available_points = ?, total_points = ? WHERE customer_id = ?");
            $stmt->execute([$balance_after, $new_total, $customer_id]);
        }
        
        // Ghi lịch sử
        $desc = "Tích điểm đơn hàng" . ($bonus_points > 0 ? " (+$bonus_points bonus $tier)" : "");
        $stmt = $conn->prepare("INSERT INTO point_transactions (customer_id, type, points, balance_before, balance_after, order_id, description) VALUES (?, 'earn', ?, ?, ?, ?, ?)");
        $stmt->execute([$customer_id, $total_earned, $balance_before, $balance_after, $order_id, $desc]);
        
        // Cập nhật điểm earned vào order
        $stmt = $conn->prepare("UPDATE orders SET points_earned = ? WHERE id = ?");
        $stmt->execute([$total_earned, $order_id]);
        
        // Cập nhật tier
        updateCustomerTier($conn, $customer_id, $settings);
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'points_earned' => $total_earned,
            'base_points' => $base_points,
            'bonus_points' => $bonus_points,
            'new_balance' => $balance_after
        ]);
        
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
}

/**
 * Cập nhật tier dựa trên tổng điểm
 */
function updateCustomerTier($conn, $customer_id, $settings) {
    $stmt = $conn->prepare("SELECT total_points FROM customer_points WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    $points = $stmt->fetchColumn() ?: 0;
    
    $tier = 'bronze';
    if ($points >= $settings['tier_diamond']) $tier = 'diamond';
    elseif ($points >= $settings['tier_platinum']) $tier = 'platinum';
    elseif ($points >= $settings['tier_gold']) $tier = 'gold';
    elseif ($points >= $settings['tier_silver']) $tier = 'silver';
    
    $stmt = $conn->prepare("UPDATE customer_points SET tier = ? WHERE customer_id = ?");
    $stmt->execute([$tier, $customer_id]);
}

/**
 * Lấy cấu hình điểm
 */
function getPointSettings($conn) {
    $stmt = $conn->query("SELECT setting_key, setting_value FROM point_settings");
    $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    return [
        'points_per_order' => intval($rows['points_per_order'] ?? 1000),
        'points_to_money' => intval($rows['points_to_money'] ?? 100),
        'min_redeem_points' => intval($rows['min_redeem_points'] ?? 100),
        'max_redeem_percent' => intval($rows['max_redeem_percent'] ?? 50),
        'tier_silver' => intval($rows['tier_silver'] ?? 1000),
        'tier_gold' => intval($rows['tier_gold'] ?? 5000),
        'tier_platinum' => intval($rows['tier_platinum'] ?? 15000),
        'tier_diamond' => intval($rows['tier_diamond'] ?? 50000),
        'bonus_bronze' => 0,
        'bonus_silver' => intval($rows['bonus_silver'] ?? 5),
        'bonus_gold' => intval($rows['bonus_gold'] ?? 10),
        'bonus_platinum' => intval($rows['bonus_platinum'] ?? 15),
        'bonus_diamond' => intval($rows['bonus_diamond'] ?? 25),
    ];
}

/**
 * Lấy thông tin tier
 */
function getTierInfo($tier, $settings) {
    $tiers = [
        'bronze' => ['name' => 'Bronze', 'color' => '#b45309', 'bonus' => 0],
        'silver' => ['name' => 'Silver', 'color' => '#9ca3af', 'bonus' => $settings['bonus_silver']],
        'gold' => ['name' => 'Gold', 'color' => '#fbbf24', 'bonus' => $settings['bonus_gold']],
        'platinum' => ['name' => 'Platinum', 'color' => '#a78bfa', 'bonus' => $settings['bonus_platinum']],
        'diamond' => ['name' => 'Diamond', 'color' => '#06b6d4', 'bonus' => $settings['bonus_diamond']],
    ];
    return $tiers[$tier] ?? $tiers['bronze'];
}

/**
 * Lấy thông tin tier tiếp theo
 */
function getNextTierInfo($current_points, $settings) {
    $tiers = [
        ['tier' => 'silver', 'points' => $settings['tier_silver']],
        ['tier' => 'gold', 'points' => $settings['tier_gold']],
        ['tier' => 'platinum', 'points' => $settings['tier_platinum']],
        ['tier' => 'diamond', 'points' => $settings['tier_diamond']],
    ];
    
    foreach ($tiers as $t) {
        if ($current_points < $t['points']) {
            return [
                'tier' => $t['tier'],
                'points_needed' => $t['points'] - $current_points,
                'total_needed' => $t['points']
            ];
        }
    }
    
    return null; // Đã đạt Diamond
}

/**
 * Lấy lịch sử điểm cho Admin (theo customer_id)
 */
function getAdminPointsHistory($conn) {
    // Kiểm tra quyền admin
    if (!isset($_SESSION['admin_id'])) {
        echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
        return;
    }
    
    $customer_id = intval($_POST['customer_id'] ?? 0);
    
    if (!$customer_id) {
        echo json_encode(['success' => false, 'message' => 'Thiếu customer_id']);
        return;
    }
    
    try {
        // Lấy lịch sử giao dịch điểm
        $sql = "
            SELECT pt.*, o.order_number 
            FROM point_transactions pt
            LEFT JOIN orders o ON pt.order_id = o.id
            WHERE pt.customer_id = ?
            ORDER BY pt.created_at DESC
            LIMIT 50
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$customer_id]);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format ngày
        foreach ($history as &$h) {
            $h['created_at_formatted'] = date('d/m/Y H:i', strtotime($h['created_at']));
        }
        
        echo json_encode([
            'success' => true, 
            'history' => $history
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
}
