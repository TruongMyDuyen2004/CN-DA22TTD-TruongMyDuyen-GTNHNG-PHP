<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để sử dụng tính năng này', 'require_login' => true]);
    exit;
}

$db = new Database();
$conn = $db->connect();
$customer_id = $_SESSION['customer_id'];

// Tự động tạo bảng favorites nếu chưa tồn tại
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS favorites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        menu_item_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_favorite (customer_id, menu_item_id),
        INDEX idx_favorites_customer (customer_id),
        INDEX idx_favorites_menu_item (menu_item_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (Exception $e) {
    // Bảng đã tồn tại, bỏ qua
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$menu_item_id = intval($_POST['menu_item_id'] ?? $_GET['menu_item_id'] ?? 0);

switch ($action) {
    case 'toggle':
        // Thêm hoặc xóa khỏi yêu thích
        if ($menu_item_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID món ăn không hợp lệ']);
            exit;
        }
        
        // Kiểm tra món ăn có tồn tại không
        $stmt = $conn->prepare("SELECT id FROM menu_items WHERE id = ?");
        $stmt->execute([$menu_item_id]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Món ăn không tồn tại']);
            exit;
        }
        
        // Kiểm tra đã yêu thích chưa
        $stmt = $conn->prepare("SELECT id FROM favorites WHERE customer_id = ? AND menu_item_id = ?");
        $stmt->execute([$customer_id, $menu_item_id]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Đã có -> Xóa
            $stmt = $conn->prepare("DELETE FROM favorites WHERE customer_id = ? AND menu_item_id = ?");
            $stmt->execute([$customer_id, $menu_item_id]);
            echo json_encode(['success' => true, 'action' => 'removed', 'message' => 'Đã xóa khỏi danh sách yêu thích']);
        } else {
            // Chưa có -> Thêm
            $stmt = $conn->prepare("INSERT INTO favorites (customer_id, menu_item_id) VALUES (?, ?)");
            $stmt->execute([$customer_id, $menu_item_id]);
            echo json_encode(['success' => true, 'action' => 'added', 'message' => 'Đã thêm vào danh sách yêu thích']);
        }
        break;
        
    case 'check':
        // Kiểm tra món có trong yêu thích không
        if ($menu_item_id <= 0) {
            echo json_encode(['success' => false, 'is_favorite' => false]);
            exit;
        }
        
        $stmt = $conn->prepare("SELECT id FROM favorites WHERE customer_id = ? AND menu_item_id = ?");
        $stmt->execute([$customer_id, $menu_item_id]);
        $is_favorite = $stmt->fetch() ? true : false;
        
        echo json_encode(['success' => true, 'is_favorite' => $is_favorite]);
        break;
        
    case 'list':
        // Lấy danh sách ID món yêu thích
        $stmt = $conn->prepare("SELECT menu_item_id FROM favorites WHERE customer_id = ?");
        $stmt->execute([$customer_id]);
        $favorites = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo json_encode(['success' => true, 'favorites' => $favorites]);
        break;
        
    case 'count':
        // Đếm số món yêu thích
        $stmt = $conn->prepare("SELECT COUNT(*) FROM favorites WHERE customer_id = ?");
        $stmt->execute([$customer_id]);
        $count = $stmt->fetchColumn();
        
        echo json_encode(['success' => true, 'count' => intval($count)]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
}
