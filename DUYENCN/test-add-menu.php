<?php
// Test thêm món ăn - kiểm tra lỗi chi tiết
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<h2>🔍 Kiểm tra chức năng thêm món ăn</h2>";

$db = new Database();
$conn = $db->connect();

if (!$conn) {
    die("❌ Không thể kết nối database");
}

echo "✅ Kết nối database thành công<br><br>";

// Kiểm tra bảng menu_items
echo "<h3>1. Kiểm tra cấu trúc bảng menu_items:</h3>";
try {
    $stmt = $conn->query("DESCRIBE menu_items");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Cột</th><th>Kiểu dữ liệu</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table><br>";
} catch (PDOException $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "<br><br>";
}

// Kiểm tra bảng categories
echo "<h3>2. Kiểm tra danh mục:</h3>";
try {
    $stmt = $conn->query("SELECT * FROM categories ORDER BY display_order");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($categories) > 0) {
        echo "✅ Có " . count($categories) . " danh mục:<br>";
        echo "<ul>";
        foreach ($categories as $cat) {
            echo "<li>ID: {$cat['id']} - {$cat['name']}</li>";
        }
        echo "</ul><br>";
    } else {
        echo "⚠️ Chưa có danh mục nào<br><br>";
    }
} catch (PDOException $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "<br><br>";
}

// Test thêm món mẫu
echo "<h3>3. Test thêm món mẫu:</h3>";
try {
    // Lấy category_id đầu tiên
    $stmt = $conn->query("SELECT id FROM categories LIMIT 1");
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$category) {
        echo "❌ Không có danh mục nào để test<br>";
        echo "<p>Bạn cần tạo danh mục trước. Chạy lệnh SQL:</p>";
        echo "<pre>INSERT INTO categories (name, name_en, display_order) VALUES ('Món chính', 'Main Dishes', 1);</pre>";
    } else {
        $test_name = "Món test " . time();
        $test_price = 50000;
        $test_category = $category['id'];
        
        $stmt = $conn->prepare("
            INSERT INTO menu_items (name, name_en, price, category_id, description, description_en, is_available, image) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $test_name,
            'Test Dish',
            $test_price,
            $test_category,
            'Mô tả test',
            'Test description',
            1,
            ''
        ]);
        
        if ($result) {
            $new_id = $conn->lastInsertId();
            echo "✅ Thêm món test thành công! ID: {$new_id}<br>";
            echo "Tên món: {$test_name}<br>";
            echo "Giá: " . number_format($test_price) . "đ<br>";
            
            // Xóa món test
            $stmt = $conn->prepare("DELETE FROM menu_items WHERE id = ?");
            $stmt->execute([$new_id]);
            echo "<br>🗑️ Đã xóa món test<br>";
        } else {
            echo "❌ Không thể thêm món test<br>";
        }
    }
} catch (PDOException $e) {
    echo "❌ Lỗi khi thêm món: " . $e->getMessage() . "<br>";
    echo "<br><strong>Chi tiết lỗi:</strong><br>";
    echo "Error Code: " . $e->getCode() . "<br>";
    echo "SQL State: " . $e->errorInfo[0] . "<br>";
}

// Kiểm tra quyền thư mục uploads
echo "<h3>4. Kiểm tra thư mục uploads:</h3>";
$upload_dir = 'uploads/menu/';

if (!file_exists($upload_dir)) {
    echo "⚠️ Thư mục {$upload_dir} chưa tồn tại<br>";
    if (mkdir($upload_dir, 0777, true)) {
        echo "✅ Đã tạo thư mục {$upload_dir}<br>";
    } else {
        echo "❌ Không thể tạo thư mục {$upload_dir}<br>";
    }
} else {
    echo "✅ Thư mục {$upload_dir} đã tồn tại<br>";
}

if (is_writable($upload_dir)) {
    echo "✅ Thư mục có quyền ghi<br>";
} else {
    echo "❌ Thư mục không có quyền ghi<br>";
}

echo "<br><h3>5. Kiểm tra số lượng món hiện tại:</h3>";
try {
    $stmt = $conn->query("SELECT COUNT(*) as total FROM menu_items");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "📊 Tổng số món trong database: {$result['total']}<br>";
} catch (PDOException $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "<br>";
}

echo "<br><hr>";
echo "<p><strong>Kết luận:</strong></p>";
echo "<p>Nếu tất cả các test trên đều PASS (✅), chức năng thêm món hoạt động bình thường.</p>";
echo "<p>Nếu có lỗi (❌), hãy kiểm tra:</p>";
echo "<ul>";
echo "<li>Cấu trúc bảng menu_items có đúng không</li>";
echo "<li>Có danh mục (categories) chưa</li>";
echo "<li>Quyền ghi thư mục uploads</li>";
echo "<li>Thông tin kết nối database</li>";
echo "</ul>";
?>
