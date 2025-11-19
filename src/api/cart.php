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
        
    default:
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
}
