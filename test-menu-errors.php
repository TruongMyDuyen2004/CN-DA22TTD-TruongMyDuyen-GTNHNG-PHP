<?php
// Bật hiển thị lỗi
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing admin/menu.php...<br><br>";

// Test include database
try {
    require_once 'config/database.php';
    echo "✓ Database connection OK<br>";
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "<br>";
}

// Test session
session_start();
echo "✓ Session started<br>";

// Kiểm tra có admin session không
if (isset($_SESSION['admin_id'])) {
    echo "✓ Admin logged in (ID: " . $_SESSION['admin_id'] . ")<br>";
} else {
    echo "⚠ Not logged in as admin<br>";
}

echo "<br><a href='admin/menu.php'>Go to admin/menu.php</a>";
?>
