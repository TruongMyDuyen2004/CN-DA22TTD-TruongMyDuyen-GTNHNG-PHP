<?php
/**
 * Debug đơn giản - Kiểm tra lỗi thêm món
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
$_SESSION['admin_id'] = 1; // Giả lập admin

require_once 'config/database.php';

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <title>Debug Thêm Món</title>
    <style>
        body { font-family: Arial; padding: 2rem; background: #f3f4f6; }
        .box { background: white; padding: 1.5rem; margin: 1rem 0; border-radius: 8px; border-left: 4px solid #3b82f6; }
        .success { color: #10b981; font-weight: bold; }
        .error { color: #ef4444; font-weight: bold; }
        pre { background: #1f2937; color: #10b981; padding: 1rem; border-radius: 8px; overflow-x: auto; }
        .btn { display: inline-block; padding: 0.75rem 1.5rem; background: #3b82f6; color: white; text-decoration: none; border-radius: 8px; margin-top: 1rem; }
    </style>
</head>
<body>
    <h1>🔍 Debug Thêm Món</h1>";

$db = new Database();
$conn = $db->connect();

// Test 1: Kiểm tra kết nối
echo "<div class='box'>";
echo "<h2>1️⃣ Kết nối Database</h2>";
if ($conn) {
    echo "<p class='success'>✅ Kết nối thành công</p>";
} else {
    echo "<p class='error'>❌ Không thể kết nối</p>";
    exit;
}
echo "</div>";

// Test 2: Kiểm tra categories
echo "<div class='box'>";
echo "<h2>2️⃣ Kiểm tra Categories</h2>";
try {
    $stmt = $conn->query("SELECT * FROM categories ORDER BY display_order");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($categories) > 0) {
        echo "<p class='success'>✅ Có " . count($categories) . " danh mục</p>";
        echo "<ul>";
        foreach ($categories as $cat) {
            echo "<li>ID: {$cat['id']} - {$cat['name']}</li>";
        }
        echo "</ul>";
        $test_category_id = $categories[0]['id'];
    } else {
        echo "<p class='error'>❌ Chưa có danh mục nào!</p>";
        echo "<p>Đang tạo danh mục mặc định...</p>";
        
        $conn->exec("INSERT INTO categories (name, name_en, display_order) VALUES 
            ('Món chính', 'Main Dishes', 1),
            ('Đồ uống', 'Beverages', 2)");
        
        $stmt = $conn->query("SELECT * FROM categories LIMIT 1");
        $cat = $stmt->fetch(PDO::FETCH_ASSOC);
        $test_category_id = $cat['id'];
        echo "<p class='success'>✅ Đã tạo danh mục mặc định</p>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>❌ Lỗi: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 3: Test thêm món
echo "<div class='box'>";
echo "<h2>3️⃣ Test Thêm Món</h2>";

$test_name = "Test món " . time();
$test_data = [
    'name' => $test_name,
    'name_en' => 'Test Dish',
    'price' => 50000,
    'category_id' => $test_category_id,
    'description' => 'Mô tả test',
    'description_en' => 'Test description',
    'is_available' => 1,
    'image' => ''
];

echo "<p><strong>Dữ liệu test:</strong></p>";
echo "<pre>" . print_r($test_data, true) . "</pre>";

try {
    $stmt = $conn->prepare("
        INSERT INTO menu_items (name, name_en, price, category_id, description, description_en, is_available, image) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $test_data['name'],
        $test_data['name_en'],
        $test_data['price'],
        $test_data['category_id'],
        $test_data['description'],
        $test_data['description_en'],
        $test_data['is_available'],
        $test_data['image']
    ]);
    
    if ($result) {
        $new_id = $conn->lastInsertId();
        echo "<p class='success'>✅ Thêm món thành công! ID: {$new_id}</p>";
        
        // Xóa món test
        $stmt = $conn->prepare("DELETE FROM menu_items WHERE id = ?");
        $stmt->execute([$new_id]);
        echo "<p>🗑️ Đã xóa món test</p>";
    } else {
        echo "<p class='error'>❌ Không thể thêm món</p>";
        echo "<pre>" . print_r($stmt->errorInfo(), true) . "</pre>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>❌ Lỗi SQL: " . $e->getMessage() . "</p>";
    echo "<p>Error Code: " . $e->getCode() . "</p>";
}
echo "</div>";

// Test 4: Test API trực tiếp
echo "<div class='box'>";
echo "<h2>4️⃣ Test API add-menu-item.php</h2>";

$_POST = [
    'name' => 'Test API ' . time(),
    'name_en' => 'Test API Dish',
    'price' => 60000,
    'category_id' => $test_category_id,
    'description' => 'Test API description',
    'description_en' => 'Test API description EN',
    'is_available' => 1
];

echo "<p><strong>POST Data:</strong></p>";
echo "<pre>" . print_r($_POST, true) . "</pre>";

ob_start();
include 'admin/api/add-menu-item.php';
$api_output = ob_get_clean();

echo "<p><strong>API Response:</strong></p>";
echo "<pre>" . htmlspecialchars($api_output) . "</pre>";

$api_response = json_decode($api_output, true);
if ($api_response) {
    if ($api_response['success']) {
        echo "<p class='success'>✅ API hoạt động bình thường!</p>";
        
        // Xóa món test
        if (isset($api_response['id'])) {
            $stmt = $conn->prepare("DELETE FROM menu_items WHERE id = ?");
            $stmt->execute([$api_response['id']]);
            echo "<p>🗑️ Đã xóa món test API (ID: {$api_response['id']})</p>";
        }
    } else {
        echo "<p class='error'>❌ API trả về lỗi: " . ($api_response['message'] ?? 'Unknown') . "</p>";
        if (isset($api_response['debug'])) {
            echo "<pre>" . print_r($api_response['debug'], true) . "</pre>";
        }
    }
} else {
    echo "<p class='error'>❌ API không trả về JSON hợp lệ</p>";
}

echo "</div>";

// Kết luận
echo "<div class='box' style='border-left-color: #10b981;'>";
echo "<h2>📊 Kết luận</h2>";
echo "<p>Nếu tất cả test đều PASS (✅), chức năng thêm món hoạt động bình thường.</p>";
echo "<p>Nếu có lỗi, hãy:</p>";
echo "<ol>";
echo "<li>Kiểm tra Console trong trình duyệt (F12)</li>";
echo "<li>Xem lỗi chi tiết ở trên</li>";
echo "<li>Chụp màn hình và báo lỗi</li>";
echo "</ol>";
echo "<a href='admin/menu-manage.php' class='btn'>🍽️ Đến trang Quản lý Menu</a>";
echo "</div>";

echo "</body></html>";
?>
