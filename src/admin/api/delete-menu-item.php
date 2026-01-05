<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$db = new Database();
$conn = $db->connect();

$id = $_POST['id'] ?? 0;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Thiếu ID món ăn']);
    exit;
}

try {
    // Kiểm tra món ăn có tồn tại không
    $stmt = $conn->prepare("SELECT name FROM menu_items WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        echo json_encode(['success' => false, 'message' => 'Món ăn không tồn tại']);
        exit;
    }
    
    // Bắt đầu transaction để đảm bảo xóa toàn bộ hoặc không xóa gì
    $conn->beginTransaction();
    
    try {
        // 1. Xóa các likes của reviews liên quan đến món ăn này
        $conn->exec("DELETE FROM review_likes WHERE review_id IN (SELECT id FROM reviews WHERE menu_item_id = $id)");
        
        // 2. Xóa các comments của reviews liên quan đến món ăn này
        $conn->exec("DELETE FROM review_comments WHERE review_id IN (SELECT id FROM reviews WHERE menu_item_id = $id)");
        
        // 3. Xóa các reviews của món ăn này
        $conn->exec("DELETE FROM reviews WHERE menu_item_id = $id");
        
        // 4. Xóa món ăn khỏi giỏ hàng
        $conn->exec("DELETE FROM cart WHERE menu_item_id = $id");
        
        // 5. Xóa món ăn khỏi order_items (chi tiết đơn hàng)
        $conn->exec("DELETE FROM order_items WHERE menu_item_id = $id");
        
        // 6. Cuối cùng xóa món ăn
        $stmt = $conn->prepare("DELETE FROM menu_items WHERE id = ?");
        $stmt->execute([$id]);
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Đã xóa món "' . $item['name'] . '" và tất cả dữ liệu liên quan thành công'
        ]);
        
    } catch (PDOException $e) {
        // Rollback nếu có lỗi
        $conn->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?>
