<?php
/**
 * Script tự động sửa lỗi thêm món
 * Chạy file này để kiểm tra và sửa các vấn đề phổ biến
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Tự động sửa lỗi thêm món</h1>";
echo "<style>
    body { font-family: Arial; padding: 2rem; background: #f3f4f6; }
    .success { color: #10b981; font-weight: bold; }
    .error { color: #ef4444; font-weight: bold; }
    .warning { color: #f59e0b; font-weight: bold; }
    .info { color: #3b82f6; font-weight: bold; }
    .box { background: white; padding: 1rem; margin: 1rem 0; border-radius: 8px; border-left: 4px solid #3b82f6; }
</style>";

$fixes = [];
$errors = [];

// 1. Kiểm tra kết nối database
echo "<div class='box'>";
echo "<h2>1️⃣ Kiểm tra Database</h2>";
try {
    require_once 'config/database.php';
    $db = new Database();
    $conn = $db->connect();
    
    if ($conn) {
        echo "<p class='success'>✅ Kết nối database thành công</p>";
    } else {
        echo "<p class='error'>❌ Không thể kết nối database</p>";
        $errors[] = "Database connection failed";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Lỗi: " . $e->getMessage() . "</p>";
    $errors[] = $e->getMessage();
}
echo "</div>";

// 2. Kiểm tra và tạo danh mục
echo "<div class='box'>";
echo "<h2>2️⃣ Kiểm tra Danh mục</h2>";
try {
    $stmt = $conn->query("SELECT COUNT(*) as total FROM categories");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['total'] == 0) {
        echo "<p class='warning'>⚠️ Chưa có danh mục nào</p>";
        echo "<p class='info'>🔧 Đang tạo danh mục mặc định...</p>";
        
        $categories = [
            ['Món chính', 'Main Dishes', 1],
            ['Món phụ', 'Side Dishes', 2],
            ['Đồ uống', 'Beverages', 3],
            ['Tráng miệng', 'Desserts', 4]
        ];
        
        $stmt = $conn->prepare("INSERT INTO categories (name, name_en, display_order) VALUES (?, ?, ?)");
        
        foreach ($categories as $cat) {
            $stmt->execute($cat);
        }
        
        echo "<p class='success'>✅ Đã tạo 4 danh mục mặc định</p>";
        $fixes[] = "Created default categories";
    } else {
        echo "<p class='success'>✅ Có {$result['total']} danh mục</p>";
        
        // Hiển thị danh sách
        $stmt = $conn->query("SELECT * FROM categories ORDER BY display_order");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<ul>";
        foreach ($categories as $cat) {
            echo "<li>ID: {$cat['id']} - {$cat['name']}</li>";
        }
        echo "</ul>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>❌ Lỗi: " . $e->getMessage() . "</p>";
    $errors[] = $e->getMessage();
}
echo "</div>";

// 3. Kiểm tra và tạo thư mục uploads
echo "<div class='box'>";
echo "<h2>3️⃣ Kiểm tra Thư mục Uploads</h2>";

$upload_dirs = [
    'uploads/',
    'uploads/menu/',
    'uploads/avatar/'
];

foreach ($upload_dirs as $dir) {
    if (!file_exists($dir)) {
        echo "<p class='warning'>⚠️ Thư mục {$dir} chưa tồn tại</p>";
        if (mkdir($dir, 0777, true)) {
            echo "<p class='success'>✅ Đã tạo thư mục {$dir}</p>";
            $fixes[] = "Created directory: $dir";
        } else {
            echo "<p class='error'>❌ Không thể tạo thư mục {$dir}</p>";
            $errors[] = "Cannot create directory: $dir";
        }
    } else {
        echo "<p class='success'>✅ Thư mục {$dir} đã tồn tại</p>";
        
        if (is_writable($dir)) {
            echo "<p class='success'>✅ Thư mục có quyền ghi</p>";
        } else {
            echo "<p class='warning'>⚠️ Thư mục không có quyền ghi</p>";
            $errors[] = "Directory not writable: $dir";
        }
    }
}
echo "</div>";

// 4. Kiểm tra cấu trúc bảng menu_items
echo "<div class='box'>";
echo "<h2>4️⃣ Kiểm tra Cấu trúc Bảng</h2>";
try {
    $stmt = $conn->query("DESCRIBE menu_items");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $required_columns = ['id', 'name', 'name_en', 'price', 'category_id', 'description', 'description_en', 'is_available', 'image'];
    $existing_columns = array_column($columns, 'Field');
    
    $missing = array_diff($required_columns, $existing_columns);
    
    if (empty($missing)) {
        echo "<p class='success'>✅ Bảng menu_items có đầy đủ các cột cần thiết</p>";
        echo "<ul>";
        foreach ($columns as $col) {
            echo "<li>{$col['Field']} ({$col['Type']})</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='error'>❌ Thiếu các cột: " . implode(', ', $missing) . "</p>";
        $errors[] = "Missing columns in menu_items table";
    }
} catch (PDOException $e) {
    echo "<p class='error'>❌ Lỗi: " . $e->getMessage() . "</p>";
    $errors[] = $e->getMessage();
}
echo "</div>";

// 5. Test thêm món mẫu
echo "<div class='box'>";
echo "<h2>5️⃣ Test Thêm Món</h2>";
try {
    // Lấy category_id đầu tiên
    $stmt = $conn->query("SELECT id FROM categories LIMIT 1");
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$category) {
        echo "<p class='error'>❌ Không có danh mục để test</p>";
        $errors[] = "No categories available for testing";
    } else {
        $test_name = "Test món " . time();
        
        $stmt = $conn->prepare("
            INSERT INTO menu_items (name, name_en, price, category_id, description, description_en, is_available, image) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $test_name,
            'Test Dish',
            50000,
            $category['id'],
            'Mô tả test',
            'Test description',
            1,
            ''
        ]);
        
        if ($result) {
            $test_id = $conn->lastInsertId();
            echo "<p class='success'>✅ Test thêm món thành công! ID: {$test_id}</p>";
            
            // Xóa món test
            $stmt = $conn->prepare("DELETE FROM menu_items WHERE id = ?");
            $stmt->execute([$test_id]);
            echo "<p class='info'>🗑️ Đã xóa món test</p>";
            
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

// 6. Kiểm tra file API
echo "<div class='box'>";
echo "<h2>6️⃣ Kiểm tra File API</h2>";

$api_files = [
    'admin/api/add-menu-item.php',
    'admin/api/update-menu-item.php',
    'admin/api/delete-menu-item.php'
];

foreach ($api_files as $file) {
    if (file_exists($file)) {
        echo "<p class='success'>✅ File {$file} tồn tại</p>";
        
        if (is_readable($file)) {
            echo "<p class='success'>✅ File có quyền đọc</p>";
        } else {
            echo "<p class='error'>❌ File không có quyền đọc</p>";
            $errors[] = "File not readable: $file";
        }
    } else {
        echo "<p class='error'>❌ File {$file} không tồn tại</p>";
        $errors[] = "File not found: $file";
    }
}
echo "</div>";

// Tổng kết
echo "<div class='box' style='border-left-color: " . (empty($errors) ? "#10b981" : "#ef4444") . ";'>";
echo "<h2>📊 Tổng kết</h2>";

if (empty($errors)) {
    echo "<p class='success' style='font-size: 1.2rem;'>✅ Tất cả kiểm tra đều PASS!</p>";
    echo "<p>Chức năng thêm món hoạt động bình thường.</p>";
} else {
    echo "<p class='error' style='font-size: 1.2rem;'>❌ Có " . count($errors) . " lỗi cần sửa</p>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li class='error'>{$error}</li>";
    }
    echo "</ul>";
}

if (!empty($fixes)) {
    echo "<p class='info'>🔧 Đã tự động sửa " . count($fixes) . " vấn đề:</p>";
    echo "<ul>";
    foreach ($fixes as $fix) {
        echo "<li class='success'>{$fix}</li>";
    }
    echo "</ul>";
}

echo "</div>";

echo "<div class='box'>";
echo "<h2>🎯 Bước tiếp theo</h2>";
echo "<ol>";
echo "<li>Nếu tất cả PASS, hãy thử thêm món tại: <a href='admin/menu-manage.php' target='_blank'>admin/menu-manage.php</a></li>";
echo "<li>Nếu vẫn lỗi, hãy mở Console (F12) và xem lỗi JavaScript</li>";
echo "<li>Hoặc test với form đơn giản: <a href='test-add-menu-form.php' target='_blank'>test-add-menu-form.php</a></li>";
echo "<li>Xem hướng dẫn chi tiết: <a href='HUONG_DAN_DEBUG_THEM_MON.md' target='_blank'>HUONG_DAN_DEBUG_THEM_MON.md</a></li>";
echo "</ol>";
echo "</div>";
?>
