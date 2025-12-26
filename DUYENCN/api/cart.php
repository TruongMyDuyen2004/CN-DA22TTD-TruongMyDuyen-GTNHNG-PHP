<?php
// Tắt hiển thị lỗi để không làm hỏng JSON response
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once '../config/database.php';

// Set header JSON ngay lập tức
header('Content-Type: application/json; charset=utf-8');

// Kiểm tra đăng nhập
if (!isset($_SESSION['customer_id'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Vui lòng đăng nhập để thêm món vào giỏ hàng',
        'require_login' => true
    ]);
    exit;
}

try {
    $db = new Database();
    $conn = $db->connect();
    
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Không thể kết nối database']);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add':
        $menu_item_id = intval($_POST['menu_item_id'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 1);
        $note = trim($_POST['note'] ?? '');
        
        if ($menu_item_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Món ăn không hợp lệ']);
            exit;
        }
        
        // Kiểm tra món còn không
        $stmt = $conn->prepare("SELECT name, is_available FROM menu_items WHERE id = ?");
        $stmt->execute([$menu_item_id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$item) {
            echo json_encode(['success' => false, 'message' => 'Món ăn không tồn tại']);
            exit;
        }
        
        if (!$item['is_available']) {
            echo json_encode(['success' => false, 'message' => 'Món này hiện đã hết']);
            exit;
        }
        
        // Thêm vào giỏ
        $stmt = $conn->prepare("
            INSERT INTO cart (customer_id, menu_item_id, quantity, note) 
            VALUES (?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE quantity = quantity + ?, note = ?
        ");
        
        if ($stmt->execute([$_SESSION['customer_id'], $menu_item_id, $quantity, $note, $quantity, $note])) {
            // Lấy tổng số món trong giỏ
            $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE customer_id = ?");
            $stmt->execute([$_SESSION['customer_id']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Đã thêm "' . $item['name'] . '" vào giỏ hàng',
                'cart_count' => $result['total'] ?? 0
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra']);
        }
        break;
        
    case 'update':
        $cart_id = intval($_POST['cart_id'] ?? 0);
        $quantity = max(1, intval($_POST['quantity'] ?? 1));
        
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND customer_id = ?");
        
        if ($stmt->execute([$quantity, $cart_id, $_SESSION['customer_id']])) {
            // Tính lại tổng
            $stmt = $conn->prepare("
                SELECT SUM(c.quantity * m.price) as total, SUM(c.quantity) as count
                FROM cart c 
                JOIN menu_items m ON c.menu_item_id = m.id 
                WHERE c.customer_id = ?
            ");
            $stmt->execute([$_SESSION['customer_id']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'message' => 'Đã cập nhật',
                'subtotal' => $result['total'] ?? 0,
                'cart_count' => $result['count'] ?? 0
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra']);
        }
        break;
        
    case 'remove':
        $cart_id = intval($_POST['cart_id'] ?? $_GET['cart_id'] ?? 0);
        
        $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND customer_id = ?");
        
        if ($stmt->execute([$cart_id, $_SESSION['customer_id']])) {
            // Lấy tổng số món
            $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE customer_id = ?");
            $stmt->execute([$_SESSION['customer_id']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'message' => 'Đã xóa khỏi giỏ hàng',
                'cart_count' => $result['total'] ?? 0
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra']);
        }
        break;
        
    case 'get_count':
        $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE customer_id = ?");
        $stmt->execute([$_SESSION['customer_id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'cart_count' => $result['total'] ?? 0
        ]);
        break;
        
    case 'get_items':
        $stmt = $conn->prepare("
            SELECT c.*, m.name, m.price, m.is_available 
            FROM cart c 
            JOIN menu_items m ON c.menu_item_id = m.id 
            WHERE c.customer_id = ?
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$_SESSION['customer_id']]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $total = 0;
        foreach ($items as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        
        echo json_encode([
            'success' => true,
            'items' => $items,
            'subtotal' => $total,
            'cart_count' => array_sum(array_column($items, 'quantity'))
        ]);
        break;
    
    case 'add_combo':
        $promo_id = intval($_POST['promo_id'] ?? 0);
        
        if ($promo_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Combo không hợp lệ']);
            exit;
        }
        
        // Lấy thông tin combo
        $stmt = $conn->prepare("SELECT * FROM restaurant_promotions WHERE id = ? AND promo_type = 'combo'");
        $stmt->execute([$promo_id]);
        $promo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$promo) {
            echo json_encode(['success' => false, 'message' => 'Combo không tồn tại']);
            exit;
        }
        
        // Lấy danh sách món trong combo
        $stmt = $conn->prepare("
            SELECT pi.menu_item_id, pi.quantity, m.name, m.price, m.is_available
            FROM promotion_items pi
            JOIN menu_items m ON pi.menu_item_id = m.id
            WHERE pi.promotion_id = ?
        ");
        $stmt->execute([$promo_id]);
        $combo_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($combo_items)) {
            echo json_encode(['success' => false, 'message' => 'Combo này chưa có món nào']);
            exit;
        }
        
        // Tính tổng giá gốc
        $original_total = 0;
        foreach ($combo_items as $item) {
            $original_total += $item['price'] * $item['quantity'];
        }
        
        // Tính giá combo (sau giảm)
        $discount_percent = $promo['discount_percent'] ?? 0;
        $combo_price = $promo['combo_price'] ?? 0;
        
        if (!$combo_price && $discount_percent > 0) {
            $combo_price = round($original_total * (100 - $discount_percent) / 100);
        }
        
        $discount_amount = $original_total - ($combo_price ?: $original_total);
        
        // Thêm từng món vào giỏ hàng
        $added_count = 0;
        $added_names = [];
        
        foreach ($combo_items as $item) {
            if (!$item['is_available']) continue;
            
            $stmt = $conn->prepare("
                INSERT INTO cart (customer_id, menu_item_id, quantity, note) 
                VALUES (?, ?, ?, 'Combo') 
                ON DUPLICATE KEY UPDATE quantity = quantity + ?
            ");
            
            if ($stmt->execute([$_SESSION['customer_id'], $item['menu_item_id'], $item['quantity'], $item['quantity']])) {
                $added_count++;
                $added_names[] = $item['name'];
            }
        }
        
        // Lưu khuyến mãi combo vào session (hỗ trợ nhiều combo)
        if ($discount_amount > 0) {
            // Khởi tạo mảng nếu chưa có
            if (!isset($_SESSION['applied_combos']) || !is_array($_SESSION['applied_combos'])) {
                $_SESSION['applied_combos'] = [];
            }
            
            // Kiểm tra combo này đã được áp dụng chưa
            $combo_exists = false;
            $combo_index = -1;
            foreach ($_SESSION['applied_combos'] as $idx => $combo) {
                if ($combo['promo_id'] == $promo_id) {
                    $combo_exists = true;
                    $combo_index = $idx;
                    break;
                }
            }
            
            // Thêm combo mới hoặc cập nhật số lần đặt
            if (!$combo_exists) {
                // Combo mới - thêm vào mảng
                $_SESSION['applied_combos'][] = [
                    'promo_id' => $promo_id,
                    'title' => $promo['title'],
                    'type' => 'combo',
                    'discount_percent' => $discount_percent,
                    'discount_amount' => $discount_amount,
                    'combo_price' => $combo_price,
                    'original_total' => $original_total,
                    'count' => 1,
                    'base_discount' => $discount_amount,
                    'added_at' => time()
                ];
            } else {
                // Combo đã tồn tại - tăng số lần đặt và cập nhật giảm giá
                $current_count = $_SESSION['applied_combos'][$combo_index]['count'] ?? 1;
                $base_discount = $_SESSION['applied_combos'][$combo_index]['base_discount'] ?? $discount_amount;
                
                $_SESSION['applied_combos'][$combo_index]['count'] = $current_count + 1;
                $_SESSION['applied_combos'][$combo_index]['discount_amount'] = $base_discount * ($current_count + 1);
            }
            
            // Tính tổng giảm giá từ tất cả combo
            $total_discount = 0;
            $combo_titles = [];
            $total_combo_count = 0;
            foreach ($_SESSION['applied_combos'] as $combo) {
                $total_discount += $combo['discount_amount'];
                $count = $combo['count'] ?? 1;
                $total_combo_count += $count;
                if ($count > 1) {
                    $combo_titles[] = $combo['title'] . ' x' . $count;
                } else {
                    $combo_titles[] = $combo['title'];
                }
            }
            
            // Cập nhật applied_promo để tương thích với code cũ
            $_SESSION['applied_promo'] = [
                'promo_id' => $promo_id,
                'title' => implode(' + ', $combo_titles),
                'type' => 'combo',
                'discount_amount' => $total_discount,
                'combo_count' => $total_combo_count
            ];
        }
        
        // Lấy tổng số món trong giỏ
        $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE customer_id = ?");
        $stmt->execute([$_SESSION['customer_id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($added_count > 0) {
            echo json_encode([
                'success' => true, 
                'message' => "Đã thêm $added_count món vào giỏ hàng",
                'cart_count' => $result['total'] ?? 0,
                'items_added' => $added_names,
                'discount_amount' => $discount_amount,
                'combo_price' => $combo_price,
                'original_total' => $original_total
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không thể thêm món vào giỏ']);
        }
        break;
    
    case 'remove_combo_discount':
        $promo_id = intval($_POST['promo_id'] ?? 0);
        
        // Nếu có promo_id cụ thể, chỉ xóa combo đó
        if ($promo_id > 0 && isset($_SESSION['applied_combos'])) {
            $_SESSION['applied_combos'] = array_filter($_SESSION['applied_combos'], function($combo) use ($promo_id) {
                return $combo['promo_id'] != $promo_id;
            });
            $_SESSION['applied_combos'] = array_values($_SESSION['applied_combos']); // Reindex
            
            // Cập nhật lại applied_promo
            if (empty($_SESSION['applied_combos'])) {
                unset($_SESSION['applied_promo']);
                unset($_SESSION['applied_combos']);
            } else {
                $total_discount = 0;
                $combo_titles = [];
                foreach ($_SESSION['applied_combos'] as $combo) {
                    $total_discount += $combo['discount_amount'];
                    $combo_titles[] = $combo['title'];
                }
                $_SESSION['applied_promo'] = [
                    'promo_id' => $_SESSION['applied_combos'][0]['promo_id'],
                    'title' => implode(' + ', $combo_titles),
                    'type' => 'combo',
                    'discount_amount' => $total_discount,
                    'combo_count' => count($_SESSION['applied_combos'])
                ];
            }
        } else {
            // Xóa tất cả combo
            unset($_SESSION['applied_promo']);
            unset($_SESSION['applied_combos']);
        }
        echo json_encode(['success' => true, 'message' => 'Đã xóa giảm giá combo']);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
}
