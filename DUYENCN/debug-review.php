<?php
session_start();
require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<h2>🔍 Debug Review System</h2>";
echo "<pre style='background:#1e293b;color:#10b981;padding:20px;border-radius:10px;'>";

// 1. Kiểm tra session
echo "=== SESSION ===\n";
echo "customer_id: " . ($_SESSION['customer_id'] ?? 'CHƯA ĐĂNG NHẬP') . "\n\n";

// 2. Kiểm tra cấu trúc bảng
echo "=== CẤU TRÚC BẢNG REVIEWS ===\n";
$stmt = $conn->query("DESCRIBE reviews");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    echo "{$col['Field']} - {$col['Type']} - {$col['Key']}\n";
}

// 3. Kiểm tra indexes
echo "\n=== INDEXES ===\n";
$stmt = $conn->query("SHOW INDEX FROM reviews");
$indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($indexes as $idx) {
    $unique = $idx['Non_unique'] == 0 ? 'UNIQUE' : 'INDEX';
    echo "{$idx['Key_name']} ({$idx['Column_name']}) - $unique\n";
}

// 4. Test INSERT trực tiếp
echo "\n=== TEST INSERT ===\n";
$testCustomerId = $_SESSION['customer_id'] ?? 1;
$testMenuItemId = 26; // ID món trong ảnh

try {
    $stmt = $conn->prepare("INSERT INTO reviews (customer_id, menu_item_id, rating, comment, is_approved) VALUES (?, ?, 5, 'Test từ debug', 1)");
    $stmt->execute([$testCustomerId, $testMenuItemId]);
    $newId = $conn->lastInsertId();
    echo "✓ INSERT thành công! ID mới: $newId\n";
    
    // Xóa test record
    $conn->exec("DELETE FROM reviews WHERE id = $newId");
    echo "✓ Đã xóa record test\n";
} catch (PDOException $e) {
    echo "✗ Lỗi INSERT: " . $e->getMessage() . "\n";
}

// 5. Đếm reviews cho món 26
echo "\n=== REVIEWS CHO MÓN #26 ===\n";
$stmt = $conn->prepare("SELECT r.*, c.full_name FROM reviews r JOIN customers c ON r.customer_id = c.id WHERE r.menu_item_id = ?");
$stmt->execute([26]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Tổng: " . count($reviews) . " đánh giá\n";
foreach ($reviews as $r) {
    echo "- ID:{$r['id']} | User:{$r['full_name']} | Rating:{$r['rating']} | {$r['comment']}\n";
}

echo "</pre>";

// Form test
echo "<h3>Test Form Đánh Giá</h3>";
echo "<form method='POST' action='api/submit-review.php' style='background:#333;padding:20px;border-radius:10px;max-width:400px;'>";
echo "<input type='hidden' name='menu_item_id' value='26'>";
echo "<p><label style='color:#fff;'>Rating:</label><br><select name='rating' style='padding:10px;width:100%;'>";
for ($i = 1; $i <= 5; $i++) echo "<option value='$i'>$i sao</option>";
echo "</select></p>";
echo "<p><label style='color:#fff;'>Comment:</label><br><textarea name='comment' style='padding:10px;width:100%;height:80px;'>Test đánh giá</textarea></p>";
echo "<button type='submit' style='background:#d4a574;color:#000;padding:10px 20px;border:none;border-radius:5px;cursor:pointer;'>Gửi đánh giá</button>";
echo "</form>";
?>
