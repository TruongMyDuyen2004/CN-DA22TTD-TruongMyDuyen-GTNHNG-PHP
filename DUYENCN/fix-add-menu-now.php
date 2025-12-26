<?php
/**
 * Fix nhanh lỗi thêm món - Kiểm tra và sửa tất cả vấn đề
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <title>Fix Lỗi Thêm Món</title>
    <style>
        body { font-family: Arial; padding: 2rem; background: #f3f4f6; }
        .box { background: white; padding: 1.5rem; margin: 1rem 0; border-radius: 8px; }
        .success { color: #10b981; font-weight: bold; }
        .error { color: #ef4444; font-weight: bold; }
        .warning { color: #f59e0b; font-weight: bold; }
        .btn { display: inline-block; padding: 0.75rem 1.5rem; background: #3b82f6; color: white; text-decoration: none; border-radius: 8px; margin: 0.5rem; }
    </style>
</head>
<body>
    <h1>🔧 Fix Lỗi Thêm Món</h1>";

$fixes = [];
$errors = [];

// 1. Kiểm tra categories
echo "<div class='box'>";
echo "<h2>1️⃣ Kiểm tra Categories</h2>";
try {
    $stmt = $conn->query("SELECT COUNT(*) as total FROM categories");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['total'] == 0) {
        echo "<p class='warning'>⚠️ Chưa có danh mục</p>";
        echo "<p>Đang tạo danh mục mặc định...</p>";
        
        $conn->exec("INSERT INTO categories (name, name_en, display_order) VALUES 
            ('Món chính', 'Main Dishes', 1),
            ('Món phụ', 'Side Dishes', 2),
            ('Đồ uống', 'Beverages', 3),
            ('Tráng miệng', 'Desserts', 4)");
        
        echo "<p class='success'>✅ Đã tạo 4 danh mục</p>";
        $fixes[] = "Created categories";
    } else {
        echo "<p class='success'>✅ Có {$result['total']} danh mục</p>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>❌ Lỗi: " . $e->getMessage() . "</p>";
    $errors[] = $e->getMessage();
}
echo "</div>";

// 2. Kiểm tra thư mục uploads
echo "<div class='box'>";
echo "<h2>2️⃣ Kiểm tra Thư mục Uploads</h2>";
$upload_dir = 'uploads/menu/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
    echo "<p class='success'>✅ Đã tạo thư mục {$upload_dir}</p>";
    $fixes[] = "Created uploads directory";
} else {
    echo "<p class='success'>✅ Thư mục đã tồn tại</p>";
}
echo "</div>";

// 3. Test thêm món
echo "<div class='box'>";
echo "<h2>3️⃣ Test Thêm Món</h2>";
try {
    $stmt = $conn->query("SELECT id FROM categories LIMIT 1");
    $cat = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($cat) {
        $test_name = "Test món " . time();
        $stmt = $conn->prepare("
            INSERT INTO menu_items (name, name_en, price, category_id, description, description_en, is_available, image) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $test_name,
            'Test Dish',
            50000,
            $cat['id'],
            'Test description',
            'Test description EN',
            1,
            ''
        ]);
        
        if ($result) {
            $test_id = $conn->lastInsertId();
            echo "<p class='success'>✅ Test thêm món thành công! ID: {$test_id}</p>";
            
            // Xóa món test
            $stmt = $conn->prepare("DELETE FROM menu_items WHERE id = ?");
            $stmt->execute([$test_id]);
            echo "<p>🗑️ Đã xóa món test</p>";
            $fixes[] = "Test insert successful";
        } else {
            echo "<p class='error'>❌ Không thể thêm món test</p>";
            $errors[] = "Test insert failed";
        }
    }
} catch (PDOException $e) {
    echo "<p class='error'>❌ Lỗi: " . $e->getMessage() . "</p>";
    $errors[] = $e->getMessage();
}
echo "</div>";

// 4. Kiểm tra file API
echo "<div class='box'>";
echo "<h2>4️⃣ Kiểm tra File API</h2>";
$api_file = 'admin/api/add-menu-item.php';
if (file_exists($api_file)) {
    echo "<p class='success'>✅ File API tồn tại</p>";
    echo "<p>Path: " . realpath($api_file) . "</p>";
} else {
    echo "<p class='error'>❌ File API không tồn tại!</p>";
    $errors[] = "API file not found";
}
echo "</div>";

// Kết luận
echo "<div class='box' style='border-left: 4px solid " . (empty($errors) ? "#10b981" : "#ef4444") . ";'>";
echo "<h2>📊 Kết quả</h2>";

if (empty($errors)) {
    echo "<p class='success' style='font-size: 1.2rem;'>✅ Tất cả kiểm tra đều PASS!</p>";
    echo "<p>Chức năng thêm món đã sẵn sàng.</p>";
    echo "<a href='admin/menu-manage.php' class='btn'>🍽️ Đến trang Quản lý Menu</a>";
    echo "<a href='test-add-form-simple.html' class='btn'>🧪 Test Form Đơn Giản</a>";
} else {
    echo "<p class='error' style='font-size: 1.2rem;'>❌ Có " . count($errors) . " lỗi</p>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li class='error'>{$error}</li>";
    }
    echo "</ul>";
}

if (!empty($fixes)) {
    echo "<p class='success'>🔧 Đã tự động sửa " . count($fixes) . " vấn đề</p>";
}

echo "</div>";

echo "</body></html>";
?>
