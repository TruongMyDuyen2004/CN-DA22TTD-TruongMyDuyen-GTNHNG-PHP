<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config/database.php';

echo "<h1>Test Admin Menu Page</h1>";

// Kiểm tra session
echo "<h2>1. Kiểm tra Session:</h2>";
if (isset($_SESSION['admin_id'])) {
    echo "✅ Đã đăng nhập admin - ID: " . $_SESSION['admin_id'] . "<br>";
} else {
    echo "❌ Chưa đăng nhập admin<br>";
    echo "<a href='admin/login.php'>Đăng nhập</a><br>";
    exit;
}

// Kiểm tra database
echo "<h2>2. Kiểm tra Database:</h2>";
try {
    $db = new Database();
    $conn = $db->connect();
    echo "✅ Kết nối database thành công<br>";
    
    // Kiểm tra bảng menu_items
    $stmt = $conn->query("SELECT COUNT(*) as total FROM menu_items");
    $total = $stmt->fetch()['total'];
    echo "✅ Có {$total} món ăn trong database<br>";
    
    // Kiểm tra bảng categories
    $stmt = $conn->query("SELECT COUNT(*) as total FROM categories");
    $total_cat = $stmt->fetch()['total'];
    echo "✅ Có {$total_cat} danh mục<br>";
    
} catch (Exception $e) {
    echo "❌ Lỗi database: " . $e->getMessage() . "<br>";
}

// Kiểm tra file admin/menu.php
echo "<h2>3. Kiểm tra file admin/menu.php:</h2>";
if (file_exists('admin/menu.php')) {
    echo "✅ File tồn tại<br>";
    echo "Kích thước: " . filesize('admin/menu.php') . " bytes<br>";
} else {
    echo "❌ File không tồn tại<br>";
}

echo "<h2>4. Thử truy cập:</h2>";
echo "<a href='admin/menu.php' style='display:inline-block; padding:12px 24px; background:#f97316; color:white; text-decoration:none; border-radius:8px;'>Mở trang Admin Menu</a>";
?>
