<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test Settings Page</h1>";

session_start();

// Giả lập admin login
$_SESSION['admin_id'] = 1;
$_SESSION['admin_username'] = 'admin';

echo "<p>✅ Session set</p>";

require_once 'config/database.php';
echo "<p>✅ Database class loaded</p>";

$db = new Database();
echo "<p>✅ Database object created</p>";

$conn = $db->connect();
echo "<p>✅ Database connected</p>";

// Test query
try {
    $stmt = $conn->query("SELECT COUNT(*) as total FROM customers");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>✅ Query successful - Customers: " . $result['total'] . "</p>";
} catch (Exception $e) {
    echo "<p>❌ Query error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='admin/settings.php'>Go to Settings Page</a></p>";
echo "<p><a href='admin/index.php'>Go to Admin Dashboard</a></p>";
?>
