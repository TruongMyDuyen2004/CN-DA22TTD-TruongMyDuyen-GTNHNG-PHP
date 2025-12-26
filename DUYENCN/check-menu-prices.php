<?php
require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

echo "=== DANH SÁCH MÓN ĂN VÀ GIÁ ===\n\n";

$stmt = $conn->query('SELECT name, price, category_id FROM menu_items ORDER BY price');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['name'] . ' - ' . number_format($row['price'], 0, ',', '.') . 'đ' . "\n";
}

echo "\n=== THỐNG KÊ ===\n";
$stats = $conn->query('SELECT MIN(price) as min, MAX(price) as max, AVG(price) as avg FROM menu_items')->fetch(PDO::FETCH_ASSOC);
echo "Giá thấp nhất: " . number_format($stats['min'], 0, ',', '.') . "đ\n";
echo "Giá cao nhất: " . number_format($stats['max'], 0, ',', '.') . "đ\n";
echo "Giá trung bình: " . number_format($stats['avg'], 0, ',', '.') . "đ\n";
?>
