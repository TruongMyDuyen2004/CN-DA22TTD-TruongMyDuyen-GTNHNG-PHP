<?php
/**
 * Thêm đánh giá mẫu để test hệ thống
 */

require_once 'database.php';

try {
    $db = new Database();
    $conn = $db->connect();
    
    echo "Đang thêm đánh giá mẫu...\n\n";
    
    // Lấy danh sách khách hàng
    $stmt = $conn->query("SELECT id FROM customers LIMIT 5");
    $customers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($customers)) {
        echo "❌ Không tìm thấy khách hàng nào. Vui lòng tạo tài khoản khách hàng trước.\n";
        exit(1);
    }
    
    // Lấy danh sách món ăn
    $stmt = $conn->query("SELECT id FROM menu_items LIMIT 10");
    $menu_items = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($menu_items)) {
        echo "❌ Không tìm thấy món ăn nào. Vui lòng thêm món ăn trước.\n";
        exit(1);
    }
    
    // Các mẫu comment
    $comments = [
        "Món ăn rất ngon, phục vụ tận tình. Tôi sẽ quay lại!",
        "Chất lượng tuyệt vời, giá cả hợp lý. Rất hài lòng!",
        "Đồ ăn tươi ngon, không gian đẹp. Recommend!",
        "Phục vụ nhanh, món ăn đúng gu. 5 sao!",
        "Ngon như mong đợi, sẽ giới thiệu bạn bè đến thử",
        "Món ăn khá ổn nhưng hơi mặn một chút",
        "Giá hơi cao so với chất lượng",
        "Bình thường, không có gì đặc biệt",
        "Món ăn ngon nhưng phục vụ hơi chậm",
        "Rất tuyệt vời! Đúng là món ăn đặc sản",
        "Vị ngon, trình bày đẹp mắt",
        "Phần ăn nhiều, giá cả phải chăng",
        "Không gian thoáng mát, món ăn ngon",
        "Đồ ăn tươi, nấu vừa miệng",
        "Sẽ quay lại lần sau với gia đình"
    ];
    
    $count = 0;
    
    // Thêm đánh giá cho mỗi món ăn
    foreach ($menu_items as $menu_item_id) {
        // Random 2-5 đánh giá cho mỗi món
        $num_reviews = rand(2, 5);
        
        for ($i = 0; $i < $num_reviews; $i++) {
            $customer_id = $customers[array_rand($customers)];
            $rating = rand(3, 5); // Chủ yếu đánh giá tích cực
            $comment = $comments[array_rand($comments)];
            $is_approved = rand(0, 10) > 1; // 90% được duyệt
            
            // Kiểm tra xem đã có đánh giá chưa
            $stmt = $conn->prepare("
                SELECT id FROM reviews 
                WHERE customer_id = ? AND menu_item_id = ?
            ");
            $stmt->execute([$customer_id, $menu_item_id]);
            
            if ($stmt->rowCount() == 0) {
                $stmt = $conn->prepare("
                    INSERT INTO reviews (customer_id, menu_item_id, rating, comment, is_approved, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW() - INTERVAL ? DAY)
                ");
                
                $days_ago = rand(1, 30);
                $stmt->execute([
                    $customer_id,
                    $menu_item_id,
                    $rating,
                    $comment,
                    $is_approved,
                    $days_ago
                ]);
                
                $count++;
            }
        }
    }
    
    echo "✅ Đã thêm {$count} đánh giá mẫu thành công!\n\n";
    
    // Hiển thị thống kê
    $stmt = $conn->query("
        SELECT 
            COUNT(*) as total,
            AVG(rating) as avg_rating,
            SUM(CASE WHEN is_approved = TRUE THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN is_approved = FALSE THEN 1 ELSE 0 END) as pending
        FROM reviews
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "📊 Thống kê:\n";
    echo "- Tổng đánh giá: {$stats['total']}\n";
    echo "- Đã duyệt: {$stats['approved']}\n";
    echo "- Chờ duyệt: {$stats['pending']}\n";
    echo "- Điểm trung bình: " . number_format($stats['avg_rating'], 1) . " sao\n";
    
} catch (PDOException $e) {
    echo "\n❌ Lỗi: " . $e->getMessage() . "\n";
    exit(1);
}
?>
