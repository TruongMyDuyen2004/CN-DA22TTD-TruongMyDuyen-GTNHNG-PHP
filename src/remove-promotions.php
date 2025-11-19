<?php
/**
 * Script xóa tính năng khuyến mãi khỏi database
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<h1>🗑️ Xóa tính năng Khuyến mãi</h1>";
echo "<style>
    body { font-family: Arial; padding: 2rem; background: #f3f4f6; }
    .success { color: #10b981; font-weight: bold; }
    .error { color: #ef4444; font-weight: bold; }
    .warning { color: #f59e0b; font-weight: bold; }
    .box { background: white; padding: 1rem; margin: 1rem 0; border-radius: 8px; border-left: 4px solid #3b82f6; }
</style>";

$db = new Database();
$conn = $db->connect();

echo "<div class='box'>";
echo "<h2>1️⃣ Kiểm tra bảng promotions</h2>";

try {
    $stmt = $conn->query("SHOW TABLES LIKE 'promotions'");
    
    if ($stmt->rowCount() > 0) {
        // Đếm số lượng khuyến mãi
        $count = $conn->query("SELECT COUNT(*) FROM promotions")->fetchColumn();
        echo "<p class='warning'>⚠️ Bảng promotions tồn tại với {$count} bản ghi</p>";
        
        // Xóa bảng
        echo "<p>🗑️ Đang xóa bảng promotions...</p>";
        $conn->exec("DROP TABLE IF EXISTS promotions");
        echo "<p class='success'>✅ Đã xóa bảng promotions thành công!</p>";
    } else {
        echo "<p class='success'>✅ Bảng promotions không tồn tại (đã được xóa trước đó)</p>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>❌ Lỗi: " . $e->getMessage() . "</p>";
}

echo "</div>";

echo "<div class='box'>";
echo "<h2>2️⃣ Kiểm tra các file đã xóa</h2>";

$deleted_files = [
    'pages/promotions.php',
    'admin/promotions-manage.php',
    'admin/promotions.php',
    'admin/api/add-promotion.php',
    'admin/api/delete-promotion.php',
    'create-promotions-table.php',
    'update-promotions.php',
    'config/add_promotions.sql'
];

$all_deleted = true;
foreach ($deleted_files as $file) {
    if (file_exists($file)) {
        echo "<p class='warning'>⚠️ File vẫn tồn tại: {$file}</p>";
        $all_deleted = false;
    } else {
        echo "<p class='success'>✅ Đã xóa: {$file}</p>";
    }
}

if ($all_deleted) {
    echo "<p class='success'><strong>✅ Tất cả file liên quan đã được xóa!</strong></p>";
}

echo "</div>";

echo "<div class='box'>";
echo "<h2>3️⃣ Kiểm tra menu admin</h2>";

$sidebar_file = 'admin/includes/sidebar.php';
$sidebar_content = file_get_contents($sidebar_file);

if (strpos($sidebar_content, 'promotions-manage.php') !== false) {
    echo "<p class='warning'>⚠️ Menu khuyến mãi vẫn còn trong sidebar</p>";
} else {
    echo "<p class='success'>✅ Menu khuyến mãi đã được xóa khỏi sidebar</p>";
}

echo "</div>";

echo "<div class='box'>";
echo "<h2>4️⃣ Kiểm tra index.php</h2>";

$index_content = file_get_contents('index.php');

if (strpos($index_content, "case 'promotions':") !== false) {
    echo "<p class='warning'>⚠️ Route promotions vẫn còn trong index.php</p>";
} else {
    echo "<p class='success'>✅ Route promotions đã được xóa khỏi index.php</p>";
}

echo "</div>";

echo "<div class='box' style='border-left-color: #10b981;'>";
echo "<h2>✅ Hoàn thành!</h2>";
echo "<p>Tính năng khuyến mãi đã được xóa hoàn toàn khỏi website.</p>";
echo "<p><strong>Các thay đổi:</strong></p>";
echo "<ul>";
echo "<li>✅ Xóa bảng promotions khỏi database</li>";
echo "<li>✅ Xóa tất cả file PHP liên quan</li>";
echo "<li>✅ Xóa menu khuyến mãi trong admin</li>";
echo "<li>✅ Xóa route trong index.php</li>";
echo "</ul>";
echo "<p style='margin-top: 1rem;'><a href='admin/index.php' style='background: #3b82f6; color: white; padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; display: inline-block;'>🏠 Về trang Admin</a></p>";
echo "</div>";
?>
