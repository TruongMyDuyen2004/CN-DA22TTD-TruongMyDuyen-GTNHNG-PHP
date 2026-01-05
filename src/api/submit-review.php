<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để đánh giá']);
    exit;
}

$db = new Database();
$conn = $db->connect();

$customer_id = $_SESSION['customer_id'];
$menu_item_id = $_POST['menu_item_id'] ?? 0;
$rating = $_POST['rating'] ?? 0;
$comment = trim($_POST['comment'] ?? '');

// Validate
if (!$menu_item_id || !$rating || !$comment) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
    exit;
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Đánh giá không hợp lệ']);
    exit;
}

try {
    // Luôn thêm đánh giá mới (cho phép đánh giá nhiều lần, giữ tất cả)
    $stmt = $conn->prepare("
        INSERT INTO reviews (customer_id, menu_item_id, rating, comment, is_approved)
        VALUES (?, ?, ?, ?, TRUE)
    ");
    $stmt->execute([$customer_id, $menu_item_id, $rating, $comment]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Cảm ơn bạn đã đánh giá!'
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}
?>
