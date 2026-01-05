<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

$db = new Database();
$conn = $db->connect();

$customer_id = $_SESSION['customer_id'];
$review_id = $_POST['review_id'] ?? 0;

// Validate
if (!$review_id) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin đánh giá']);
    exit;
}

try {
    // Kiểm tra đánh giá có thuộc về user không
    $stmt = $conn->prepare("SELECT id FROM reviews WHERE id = ? AND customer_id = ?");
    $stmt->execute([$review_id, $customer_id]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xóa đánh giá này']);
        exit;
    }
    
    // Xóa các like của đánh giá
    $stmt = $conn->prepare("DELETE FROM review_likes WHERE review_id = ?");
    $stmt->execute([$review_id]);
    
    // Xóa các comment của đánh giá
    $stmt = $conn->prepare("DELETE FROM review_comments WHERE review_id = ?");
    $stmt->execute([$review_id]);
    
    // Xóa đánh giá
    $stmt = $conn->prepare("DELETE FROM reviews WHERE id = ? AND customer_id = ?");
    $stmt->execute([$review_id, $customer_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã xóa đánh giá!'
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}
?>
