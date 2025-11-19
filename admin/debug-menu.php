<?php
// Bật hiển thị tất cả lỗi
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h2>🔍 Debug Admin Menu</h2>";
echo "<hr>";

// Test 1: Session
echo "<h3>1. Kiểm tra Session</h3>";
session_start();
echo "✓ Session started<br>";
if (isset($_SESSION['admin_id'])) {
    echo "✓ Admin logged in (ID: " . $_SESSION['admin_id'] . ")<br>";
} else {
    echo "⚠️ <strong>CHƯA ĐĂNG NHẬP ADMIN</strong><br>";
    echo "<a href='login.php'>→ Đăng nhập ngay</a><br>";
}

// Test 2: Database connection
echo "<h3>2. Kiểm tra Database</h3>";
try {
    require_once '../config/database.php';
    echo "✓ File database.php loaded<br>";
    
    $db = new Database();
    echo "✓ Database object created<br>";
    
    $conn = $db->connect();
    echo "✓ Database connected<br>";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
    die();
}

// Test 3: Check tables
echo "<h3>3. Kiểm tra Tables</h3>";
try {
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $required_tables = ['categories', 'menu_items', 'admins'];
    foreach ($required_tables as $table) {
        if (in_array($table, $tables)) {
            echo "✓ Table '$table' exists<br>";
        } else {
            echo "❌ Table '$table' NOT FOUND<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 4: Check data
echo "<h3>4. Kiểm tra Dữ liệu</h3>";
try {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM categories");
    $count = $stmt->fetch()['count'];
    echo "✓ Categories: $count records<br>";
    
    $stmt = $conn->query("SELECT COUNT(*) as count FROM menu_items");
    $count = $stmt->fetch()['count'];
    echo "✓ Menu Items: $count records<br>";
    
    $stmt = $conn->query("SELECT COUNT(*) as count FROM admins");
    $count = $stmt->fetch()['count'];
    echo "✓ Admins: $count records<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 5: Check sidebar file
echo "<h3>5. Kiểm tra Files</h3>";
if (file_exists('includes/sidebar.php')) {
    echo "✓ sidebar.php exists<br>";
} else {
    echo "❌ sidebar.php NOT FOUND<br>";
}

// Test 6: Try to load menu.php
echo "<h3>6. Thử load menu.php</h3>";
echo "<p>Nếu có lỗi, nó sẽ hiện bên dưới:</p>";
echo "<hr>";

ob_start();
try {
    include 'menu.php';
    $output = ob_get_clean();
    echo "✓ Menu.php loaded successfully!<br>";
    echo "<a href='menu.php' style='display:inline-block;margin-top:20px;padding:10px 20px;background:#059669;color:white;text-decoration:none;border-radius:8px;'>Mở Menu.php</a>";
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ Error loading menu.php:<br>";
    echo "<pre style='background:#fee;padding:15px;border-radius:8px;color:#c00;'>";
    echo htmlspecialchars($e->getMessage());
    echo "\n\nStack trace:\n";
    echo htmlspecialchars($e->getTraceAsString());
    echo "</pre>";
}
?>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    max-width: 900px;
    margin: 30px auto;
    padding: 20px;
    background: #f8f9fa;
}
h2 { color: #f97316; }
h3 { 
    color: #334155; 
    margin-top: 1.5rem;
    padding: 0.5rem;
    background: #e2e8f0;
    border-radius: 8px;
}
</style>
