<?php
session_start();
require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<h2>🔍 Debug Messages</h2>";

// Kiểm tra cấu trúc bảng contacts
echo "<h3>Cấu trúc bảng contacts:</h3>";
$stmt = $conn->query("DESCRIBE contacts");
echo "<table border='1' cellpadding='5'><tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Default']}</td></tr>";
}
echo "</table>";

// Lấy tất cả tin nhắn
echo "<h3>Tất cả tin nhắn trong contacts:</h3>";
$stmt = $conn->query("SELECT * FROM contacts ORDER BY created_at DESC LIMIT 20");
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5'><tr>";
if (count($messages) > 0) {
    foreach (array_keys($messages[0]) as $key) {
        echo "<th>$key</th>";
    }
    echo "</tr>";
    foreach ($messages as $msg) {
        echo "<tr>";
        foreach ($msg as $val) {
            $val = htmlspecialchars($val ?? '');
            echo "<td>" . (strlen($val) > 50 ? substr($val, 0, 50) . '...' : $val) . "</td>";
        }
        echo "</tr>";
    }
} else {
    echo "<tr><td>Không có tin nhắn</td></tr>";
}
echo "</table>";

// Kiểm tra tin nhắn admin
echo "<h3>Tin nhắn có is_admin_message = 1:</h3>";
try {
    $stmt = $conn->query("SELECT id, name, email, message, is_admin_message, created_at FROM contacts WHERE is_admin_message = 1 ORDER BY created_at DESC");
    $adminMsgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($adminMsgs) > 0) {
        echo "<ul>";
        foreach ($adminMsgs as $m) {
            echo "<li>ID: {$m['id']} | Name: {$m['name']} | Email: {$m['email']} | Message: " . htmlspecialchars($m['message']) . " | Time: {$m['created_at']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color:orange;'>Không có tin nhắn admin nào với is_admin_message = 1</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>Lỗi: " . $e->getMessage() . " - Có thể cột is_admin_message chưa tồn tại!</p>";
}

// Kiểm tra tin nhắn có name = 'Admin'
echo "<h3>Tin nhắn có name = 'Admin':</h3>";
$stmt = $conn->query("SELECT id, name, email, message, created_at FROM contacts WHERE name = 'Admin' ORDER BY created_at DESC");
$adminNameMsgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (count($adminNameMsgs) > 0) {
    echo "<ul>";
    foreach ($adminNameMsgs as $m) {
        echo "<li>ID: {$m['id']} | Email: {$m['email']} | Message: " . htmlspecialchars($m['message']) . " | Time: {$m['created_at']}</li>";
    }
    echo "</ul>";
} else {
    echo "<p>Không có tin nhắn nào với name = 'Admin'</p>";
}
?>
