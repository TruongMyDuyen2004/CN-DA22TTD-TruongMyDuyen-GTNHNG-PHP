<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

$db = new Database();
$conn = $db->connect();

// Thêm bình luận
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $review_id = $_POST['review_id'] ?? 0;
    $comment = trim($_POST['comment'] ?? '');
    $customer_id = $_SESSION['customer_id'];
    
    // Validate
    if (empty($comment)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng nhập nội dung bình luận']);
        exit;
    }
    
    if (strlen($comment) < 2) {
        echo json_encode(['success' => false, 'message' => 'Bình luận quá ngắn']);
        exit;
    }
    
    if (strlen($comment) > 500) {
        echo json_encode(['success' => false, 'message' => 'Bình luận quá dài (tối đa 500 ký tự)']);
        exit;
    }
    
    // Kiểm tra review có tồn tại
    $stmt = $conn->prepare("SELECT id FROM reviews WHERE id = ?");
    $stmt->execute([$review_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Đánh giá không tồn tại']);
        exit;
    }
    
    try {
        // Thêm bình luận
        $stmt = $conn->prepare("
            INSERT INTO review_comments (review_id, customer_id, comment) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$review_id, $customer_id, $comment]);
        
            // Lấy thông tin bình luận vừa tạo
        $comment_id = $conn->lastInsertId();
        $stmt = $conn->prepare("
            SELECT 
                rc.*,
                c.full_name,
                c.email
            FROM review_comments rc
            JOIN customers c ON rc.customer_id = c.id
            WHERE rc.id = ?
        ");
        $stmt->execute([$comment_id]);
        $newComment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Cập nhật số lượng bình luận trong reviews
        $stmt = $conn->prepare("
            UPDATE reviews 
            SET comments_count = (SELECT COUNT(*) FROM review_comments WHERE review_id = ?)
            WHERE id = ?
        ");
        $stmt->execute([$review_id, $review_id]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Đã thêm bình luận',
            'comment' => $newComment
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
    }
    exit;
}

// Lấy danh sách bình luận
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $review_id = $_GET['review_id'] ?? 0;
    
    try {
        $stmt = $conn->prepare("
            SELECT 
                rc.*,
                c.full_name,
                c.email
            FROM review_comments rc
            JOIN customers c ON rc.customer_id = c.id
            WHERE rc.review_id = ?
            ORDER BY rc.created_at ASC
        ");
        $stmt->execute([$review_id]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'comments' => $comments
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
    }
    exit;
}

// Xóa bình luận
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents("php://input"), $_DELETE);
    $comment_id = $_DELETE['comment_id'] ?? 0;
    $customer_id = $_SESSION['customer_id'];
    
    try {
        // Kiểm tra quyền sở hữu
        $stmt = $conn->prepare("SELECT customer_id FROM review_comments WHERE id = ?");
        $stmt->execute([$comment_id]);
        $comment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$comment) {
            echo json_encode(['success' => false, 'message' => 'Bình luận không tồn tại']);
            exit;
        }
        
        if ($comment['customer_id'] != $customer_id) {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xóa bình luận này']);
            exit;
        }
        
        // Xóa bình luận
        $stmt = $conn->prepare("DELETE FROM review_comments WHERE id = ?");
        $stmt->execute([$comment_id]);
        
        echo json_encode(['success' => true, 'message' => 'Đã xóa bình luận']);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request method']);
