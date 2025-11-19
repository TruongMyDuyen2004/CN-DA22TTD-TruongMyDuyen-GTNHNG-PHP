<?php
// Test API get reviews
session_start();
require_once 'config/database.php';

echo "<h2>Test Reviews API</h2>";

$db = new Database();
$conn = $db->connect();

// Lấy món ăn đầu tiên
$stmt = $conn->query("SELECT id, name FROM menu_items LIMIT 1");
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    echo "<p style='color: red;'>Không có món ăn nào trong database</p>";
    exit;
}

echo "<p>Đang test với món: <strong>{$item['name']}</strong> (ID: {$item['id']})</p>";

// Kiểm tra bảng reviews
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM reviews WHERE menu_item_id = ?");
$stmt->execute([$item['id']]);
$count = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<p>Số đánh giá cho món này: <strong>{$count['total']}</strong></p>";

if ($count['total'] == 0) {
    echo "<p style='color: orange;'>⚠️ Chưa có đánh giá nào. Hãy thêm đánh giá mẫu.</p>";
    echo "<p><a href='config/add_sample_reviews.php'>Click để thêm đánh giá mẫu</a></p>";
} else {
    // Test API call
    $_GET['menu_item_id'] = $item['id'];
    $_GET['page'] = 1;
    $_GET['sort'] = 'newest';
    
    ob_start();
    include 'api/get-reviews.php';
    $response = ob_get_clean();
    
    echo "<h3>Response từ API:</h3>";
    echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>";
    $data = json_decode($response, true);
    print_r($data);
    echo "</pre>";
    
    if ($data['success']) {
        echo "<p style='color: green;'>✅ API hoạt động tốt!</p>";
    } else {
        echo "<p style='color: red;'>❌ API có lỗi: {$data['message']}</p>";
    }
}
?>
