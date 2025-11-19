<?php
/**
 * Test API thêm món trực tiếp
 */
session_start();

// Giả lập đăng nhập admin
$_SESSION['admin_id'] = 1;

echo "<h1>🧪 Test API Thêm Món</h1>";
echo "<style>
    body { font-family: Arial; padding: 2rem; background: #f3f4f6; }
    .success { color: #10b981; }
    .error { color: #ef4444; }
    pre { background: #1f2937; color: #10b981; padding: 1rem; border-radius: 8px; overflow-x: auto; }
    .box { background: white; padding: 1rem; margin: 1rem 0; border-radius: 8px; }
</style>";

echo "<div class='box'>";
echo "<h2>1️⃣ Kiểm tra Session</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Admin ID: " . ($_SESSION['admin_id'] ?? 'Not set') . "\n";
echo "</pre>";
echo "</div>";

echo "<div class='box'>";
echo "<h2>2️⃣ Test POST Request</h2>";

// Giả lập POST data
$_POST = [
    'name' => 'Test món ' . time(),
    'name_en' => 'Test Dish',
    'price' => 50000,
    'category_id' => 1,
    'description' => 'Mô tả test',
    'description_en' => 'Test description',
    'is_available' => 1
];

echo "<p><strong>POST Data:</strong></p>";
echo "<pre>" . print_r($_POST, true) . "</pre>";

// Gọi API
ob_start();
include 'admin/api/add-menu-item.php';
$output = ob_get_clean();

echo "<p><strong>API Response:</strong></p>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";

// Parse JSON
$response = json_decode($output, true);
if ($response) {
    if ($response['success']) {
        echo "<p class='success'>✅ API hoạt động bình thường!</p>";
        
        // Xóa món test
        if (isset($response['id'])) {
            require_once 'config/database.php';
            $db = new Database();
            $conn = $db->connect();
            $stmt = $conn->prepare("DELETE FROM menu_items WHERE id = ?");
            $stmt->execute([$response['id']]);
            echo "<p>🗑️ Đã xóa món test (ID: {$response['id']})</p>";
        }
    } else {
        echo "<p class='error'>❌ API trả về lỗi: " . ($response['message'] ?? 'Unknown') . "</p>";
    }
} else {
    echo "<p class='error'>❌ Response không phải JSON hợp lệ</p>";
}

echo "</div>";

echo "<div class='box'>";
echo "<h2>3️⃣ Kiểm tra File API</h2>";
$api_file = 'admin/api/add-menu-item.php';
if (file_exists($api_file)) {
    echo "<p class='success'>✅ File tồn tại</p>";
    echo "<p>Path: " . realpath($api_file) . "</p>";
    echo "<p>Size: " . filesize($api_file) . " bytes</p>";
    echo "<p>Readable: " . (is_readable($api_file) ? 'Yes' : 'No') . "</p>";
} else {
    echo "<p class='error'>❌ File không tồn tại</p>";
}
echo "</div>";

echo "<div class='box'>";
echo "<h2>4️⃣ Kiểm tra PHP Errors</h2>";
$errors = error_get_last();
if ($errors) {
    echo "<pre>" . print_r($errors, true) . "</pre>";
} else {
    echo "<p class='success'>✅ Không có lỗi PHP</p>";
}
echo "</div>";
?>
