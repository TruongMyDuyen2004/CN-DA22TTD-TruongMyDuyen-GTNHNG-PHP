<?php
require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

echo "=== CẤU TRÚC BẢNG MENU_ITEMS ===\n\n";

$stmt = $conn->query("DESCRIBE menu_items");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($columns as $col) {
    echo "- " . $col['Field'] . " (" . $col['Type'] . ")";
    if ($col['Null'] == 'NO') echo " NOT NULL";
    if ($col['Default']) echo " DEFAULT " . $col['Default'];
    echo "\n";
}
?>
