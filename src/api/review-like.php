<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để thích đánh giá']);
    exit;
}

$db = new Database();
$conn = $db->connect();

$customer_id = $_SESSION['customer_id'];
$review_id = $_POST['review_id'] ?? 0;

if (!$review_id) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin đánh giá']);
    exit;
}

try {
    // Kiểm tra đã like chưa
    $stmt = $conn->prepare("SELECT id FROM review_likes WHERE review_id = ? AND customer_id = ?");
    $stmt->execute([$review_id, $customer_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        // Unlike - Xóa like
        $stmt = $conn->prepare("DELETE FROM review_likes WHERE review_id = ? AND customer_id = ?");
        $stmt->execute([$review_id, $customer_id]);
        
        // Giảm likes_count
        $stmt = $conn->prepare("UPDATE reviews SET likes_count = GREATEST(0, likes_count - 1) WHERE id = ?");
        $stmt->execute([$review_id]);
        
        $action = 'unliked';
    } else {
        // Like - Thêm like
        $stmt = $conn->prepare("INSERT INTO review_likes (review_id, customer_id) VALUES (?, ?)");
        $stmt->execute([$review_id, $customer_id]);
        
        // Tăng likes_count
        $stmt = $conn->prepare("UPDATE reviews SET likes_count = likes_count + 1 WHERE id = ?");
        $stmt->execute([$review_id]);
        
        $action = 'liked';
    }
    
    // Lấy số likes mới
    $stmt = $conn->prepare("SELECT likes_count FROM reviews WHERE id = ?");
    $stmt->execute([$review_id]);
    $likes_count = $stmt->fetch(PDO::FETCH_ASSOC)['likes_count'];
    
    echo json_encode([
        'success' => true,
        'action' => $action,
        'likes_count' => $likes_count
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}
?>
