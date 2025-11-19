<?php
require_once 'database.php';

try {
    $db = new Database();
    $conn = $db->connect();
    
    echo "Đang tạo bảng bình luận đánh giá...\n";
    
    // Đọc và thực thi SQL
    $sql = file_get_contents(__DIR__ . '/add_review_comments.sql');
    $conn->exec($sql);
    
    echo "✓ Đã tạo bảng review_comments thành công!\n";
    
    // Thêm dữ liệu mẫu
    echo "\nĐang thêm dữ liệu mẫu...\n";
    
    $stmt = $conn->prepare("
        INSERT INTO review_comments (review_id, customer_id, comment) 
        VALUES (?, ?, ?)
    ");
    
    $sampleComments = [
        [1, 2, 'Mình cũng đồng ý! Món này rất ngon.'],
        [1, 3, 'Cảm ơn bạn đã chia sẻ. Mình sẽ thử món này.'],
    ];
    
    foreach ($sampleComments as $comment) {
        try {
            $stmt->execute($comment);
            echo "✓ Đã thêm bình luận mẫu\n";
        } catch (PDOException $e) {
            echo "- Bỏ qua bình luận (có thể đã tồn tại)\n";
        }
    }
    
    echo "\n✅ Hoàn tất cài đặt hệ thống bình luận!\n";
    echo "Bạn có thể sử dụng chức năng bình luận ngay bây giờ.\n";
    
} catch (PDOException $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
}
