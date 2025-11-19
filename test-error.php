<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing PHP Errors</h2>";

// Test database connection
try {
    require_once 'config/database.php';
    $db = new Database();
    $conn = $db->connect();
    echo "<p style='color: green;'>✓ Database connection OK</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>";
}

// Test session
session_start();
if (isset($_SESSION['customer_id'])) {
    echo "<p style='color: green;'>✓ Session OK - Customer ID: " . $_SESSION['customer_id'] . "</p>";
    
    // Test avatar query
    try {
        $stmt = $conn->prepare("SELECT avatar, full_name FROM customers WHERE id = ?");
        $stmt->execute([$_SESSION['customer_id']]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user_data) {
            echo "<p style='color: green;'>✓ User data found</p>";
            echo "<pre>";
            print_r($user_data);
            echo "</pre>";
        } else {
            echo "<p style='color: orange;'>⚠ No user data found</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Query error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠ No active session</p>";
}

// Test include header
echo "<h3>Testing Header Include:</h3>";
try {
    ob_start();
    include 'includes/header.php';
    $header_output = ob_get_clean();
    echo "<p style='color: green;'>✓ Header loaded successfully</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Header error: " . $e->getMessage() . "</p>";
}
?>
