<?php
require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<h2>Cấu trúc bảng categories:</h2>";
echo "<pre>";

$stmt = $conn->query("DESCRIBE categories");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($columns as $col) {
    echo $col['Field'] . " - " . $col['Type'] . " - " . ($col['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
}

echo "</pre>";
