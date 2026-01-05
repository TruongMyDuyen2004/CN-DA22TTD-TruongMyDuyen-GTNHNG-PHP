<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing admin menu page...<br>";

session_start();
echo "Session started<br>";

if (!isset($_SESSION['admin_id'])) {
    echo "Not logged in as admin<br>";
    echo "<a href='login.php'>Login</a>";
    exit;
}

echo "Admin ID: " . $_SESSION['admin_id'] . "<br>";

require_once '../config/database.php';
echo "Database class loaded<br>";

$db = new Database();
echo "Database object created<br>";

$conn = $db->connect();
echo "Database connected<br>";

$stmt = $conn->query("SELECT COUNT(*) as total FROM menu_items");
$total = $stmt->fetch()['total'];
echo "Total menu items: {$total}<br>";

echo "<br><a href='menu.php'>Go to menu.php</a>";
?>
