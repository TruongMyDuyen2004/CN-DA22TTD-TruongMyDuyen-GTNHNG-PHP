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
$rating = $_POST['rating'] ?? 0;
$comment = trim($_POST['comment'] ?? '');

// Validate
if (!$review_id) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin đánh giá']);
    exit;
}

if (!$rating || !$comment) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
    exit;
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Đánh giá không hợp lệ']);
    exit;
}

try {
    // Thêm cột updated_at và original_comment nếu chưa có
    try {
        $conn->exec("ALTER TABLE reviews ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL");
    } catch (PDOException $e) {}
    
    try {
        $conn->exec("ALTER TABLE reviews ADD COLUMN original_comment TEXT NULL DEFAULT NULL");
    } catch (PDOException $e) {}
    
    try {
        $conn->exec("ALTER TABLE reviews ADD COLUMN original_rating INT NULL DEFAULT NULL");
    } catch (PDOException $e) {}
    
    // Kiểm tra đánh giá có thuộc về user không
    $stmt = $conn->prepare("SELECT id, comment, rating, original_comment FROM reviews WHERE id = ? AND customer_id = ?");
    $stmt->execute([$review_id, $customer_id]);
    $review = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$review) {
        echo json_encode(['success' => false, 'message' => 'Bạn không có quyền sửa đánh giá này']);
        exit;
    }
    
    // Nếu chưa có original_comment, lưu nội dung gốc
    if (empty($review['original_comment'])) {
        $stmt = $conn->prepare("UPDATE reviews SET original_comment = ?, original_rating = ? WHERE id = ?");
        $stmt->execute([$review['comment'], $review['rating'], $review_id]);
    }
    
    // Cập nhật đánh giá với thời gian sửa
    $stmt = $conn->prepare("UPDATE reviews SET rating = ?, comment = ?, updated_at = NOW() WHERE id = ? AND customer_id = ?");
    $stmt->execute([$rating, $comment, $review_id, $customer_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã cập nhật đánh giá!'
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}
?>
