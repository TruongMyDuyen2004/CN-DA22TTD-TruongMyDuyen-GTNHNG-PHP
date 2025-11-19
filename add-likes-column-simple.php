<?php
require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<h1>Thêm cột likes_count</h1>";

try {
    // Thử thêm cột
    $conn->exec("ALTER TABLE reviews ADD COLUMN likes_count INT DEFAULT 0");
    echo "<p style='color: green;'>✅ Đã thêm cột likes_count thành công!</p>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "<p style='color: orange;'>⚠️ Cột likes_count đã tồn tại rồi!</p>";
    } else {
        echo "<p style='color: red;'>❌ Lỗi: " . $e->getMessage() . "</p>";
    }
}

// Kiểm tra lại
try {
    $stmt = $conn->query("SHOW COLUMNS FROM reviews LIKE 'likes_count'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ Xác nhận: Cột likes_count đã tồn tại!</p>";
        echo "<p><a href='index.php?page=all-reviews'>Quay lại trang đánh giá</a></p>";
    } else {
        echo "<p style='color: red;'>❌ Cột likes_count vẫn chưa có!</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Lỗi kiểm tra: " . $e->getMessage() . "</p>";
}
?>
