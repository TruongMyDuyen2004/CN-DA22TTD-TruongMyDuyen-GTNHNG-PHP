<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

$menu_item_id = $_GET['menu_item_id'] ?? 0;
$customer_id = $_SESSION['customer_id'] ?? 0;
$sort = $_GET['sort'] ?? 'newest'; // newest, oldest, highest, lowest
$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

if (!$menu_item_id) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin món ăn']);
    exit;
}

// Xác định thứ tự sắp xếp
$order_by = match($sort) {
    'oldest' => 'r.created_at ASC',
    'highest' => 'r.rating DESC, r.created_at DESC',
    'lowest' => 'r.rating ASC, r.created_at DESC',
    default => 'r.created_at DESC' // newest
};

try {
    // Lấy thống kê đánh giá
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_reviews,
            AVG(rating) as avg_rating,
            SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as star_5,
            SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as star_4,
            SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as star_3,
            SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as star_2,
            SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as star_1
        FROM reviews 
        WHERE menu_item_id = ? AND is_approved = TRUE
    ");
    $stmt->execute([$menu_item_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Thêm cột comments_count nếu chưa có
    try {
        $conn->exec("ALTER TABLE reviews ADD COLUMN IF NOT EXISTS comments_count INT DEFAULT 0");
    } catch (PDOException $e) {
        // Column might already exist
    }
    
    // Lấy danh sách đánh giá với phân trang
    $stmt = $conn->prepare("
        SELECT 
            r.*,
            c.full_name,
            c.avatar,
            c.email,
            CASE WHEN rl.id IS NOT NULL THEN TRUE ELSE FALSE END as is_liked_by_user,
            (SELECT COUNT(*) FROM review_likes WHERE review_id = r.id) as likes_count,
            (SELECT COUNT(*) FROM review_comments WHERE review_id = r.id) as comments_count
        FROM reviews r
        LEFT JOIN customers c ON r.customer_id = c.id
        LEFT JOIN review_likes rl ON r.id = rl.review_id AND rl.customer_id = ?
        WHERE r.menu_item_id = ? AND r.is_approved = TRUE
        ORDER BY {$order_by}
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$customer_id, $menu_item_id, $limit, $offset]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Đếm tổng số đánh giá
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM reviews WHERE menu_item_id = ? AND is_approved = TRUE");
    $stmt->execute([$menu_item_id]);
    $total_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'reviews' => $reviews,
        'total' => $total_count,
        'has_more' => ($offset + $limit) < $total_count,
        'current_page' => $page
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}
?>
