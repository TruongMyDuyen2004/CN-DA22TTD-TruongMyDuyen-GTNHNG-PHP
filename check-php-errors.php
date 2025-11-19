<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Kiểm tra lỗi PHP</h2>";
echo "<hr>";

// Kiểm tra PHP version
echo "<h3>PHP Version: " . phpversion() . "</h3>";

// Kiểm tra extensions
echo "<h3>Extensions:</h3>";
$required = ['pdo', 'pdo_mysql', 'mbstring', 'json'];
foreach ($required as $ext) {
    if (extension_loaded($ext)) {
        echo "✓ $ext<br>";
    } else {
        echo "❌ $ext NOT LOADED<br>";
    }
}

// Kiểm tra file config
echo "<h3>Config Files:</h3>";
if (file_exists('config/database.php')) {
    echo "✓ config/database.php exists<br>";
    
    // Thử include
    try {
        require_once 'config/database.php';
        echo "✓ config/database.php loaded<br>";
    } catch (Exception $e) {
        echo "❌ Error loading: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ config/database.php NOT FOUND<br>";
}

// Kiểm tra admin files
echo "<h3>Admin Files:</h3>";
$admin_files = ['admin/menu.php', 'admin/login.php', 'admin/includes/sidebar.php'];
foreach ($admin_files as $file) {
    if (file_exists($file)) {
        echo "✓ $file exists<br>";
    } else {
        echo "❌ $file NOT FOUND<br>";
    }
}

// Test database connection
echo "<h3>Database Connection:</h3>";
try {
    $db = new Database();
    $conn = $db->connect();
    if ($conn) {
        echo "✓ Connected successfully<br>";
        
        // Test query
        $stmt = $conn->query("SELECT DATABASE() as db");
        $result = $stmt->fetch();
        echo "✓ Current database: " . $result['db'] . "<br>";
    } else {
        echo "❌ Connection failed<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>Nếu tất cả OK, thử các link sau:</h3>";
echo "<a href='admin/login.php' style='display:inline-block;margin:10px;padding:10px 20px;background:#3b82f6;color:white;text-decoration:none;border-radius:8px;'>Admin Login</a>";
echo "<a href='admin/test-simple.php' style='display:inline-block;margin:10px;padding:10px 20px;background:#059669;color:white;text-decoration:none;border-radius:8px;'>Test Simple</a>";
echo "<a href='admin/debug-menu.php' style='display:inline-block;margin:10px;padding:10px 20px;background:#f97316;color:white;text-decoration:none;border-radius:8px;'>Debug Menu</a>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 30px auto;
    padding: 20px;
    background: #f5f5f5;
}
h2 { color: #f97316; }
h3 { 
    color: #334155; 
    margin-top: 1.5rem;
    background: #e2e8f0;
    padding: 0.5rem;
    border-radius: 8px;
}
</style>
