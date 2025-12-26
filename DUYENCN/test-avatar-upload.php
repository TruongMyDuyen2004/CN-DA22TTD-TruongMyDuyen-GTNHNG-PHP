<?php
session_start();
require_once 'config/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['customer_id'])) {
    die('Chưa đăng nhập');
}

$db = new Database();
$conn = $db->connect();

// Lấy thông tin customer
$stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$_SESSION['customer_id']]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h2>Debug Avatar Upload</h2>";
echo "<p><strong>Customer ID:</strong> " . $_SESSION['customer_id'] . "</p>";
echo "<p><strong>Avatar trong DB:</strong> " . ($customer['avatar'] ?? 'NULL') . "</p>";

// Kiểm tra file tồn tại
if (!empty($customer['avatar'])) {
    $avatar_path = $customer['avatar'];
    echo "<p><strong>File tồn tại:</strong> " . (file_exists($avatar_path) ? 'CÓ' : 'KHÔNG') . "</p>";
    echo "<p><strong>Đường dẫn đầy đủ:</strong> " . realpath($avatar_path) . "</p>";
    echo "<p><strong>Hiển thị ảnh:</strong></p>";
    echo "<img src='" . htmlspecialchars($avatar_path) . "' style='max-width:200px; border:2px solid red;'>";
}

// Kiểm tra thư mục uploads
$upload_dir = 'uploads/avatars/';
echo "<hr>";
echo "<p><strong>Thư mục upload:</strong> " . $upload_dir . "</p>";
echo "<p><strong>Thư mục tồn tại:</strong> " . (is_dir($upload_dir) ? 'CÓ' : 'KHÔNG') . "</p>";
echo "<p><strong>Có thể ghi:</strong> " . (is_writable($upload_dir) ? 'CÓ' : 'KHÔNG') . "</p>";

// Liệt kê file trong thư mục
if (is_dir($upload_dir)) {
    echo "<p><strong>Các file trong thư mục:</strong></p><ul>";
    foreach (scandir($upload_dir) as $file) {
        if ($file != '.' && $file != '..') {
            echo "<li>$file</li>";
        }
    }
    echo "</ul>";
}

// Test upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['test_avatar'])) {
    echo "<hr><h3>Kết quả upload:</h3>";
    echo "<pre>";
    print_r($_FILES['test_avatar']);
    echo "</pre>";
    
    if ($_FILES['test_avatar']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['test_avatar']['name'], PATHINFO_EXTENSION));
        $new_filename = 'test_' . time() . '.' . $ext;
        $upload_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['test_avatar']['tmp_name'], $upload_path)) {
            echo "<p style='color:green;'>Upload thành công: $upload_path</p>";
            
            // Cập nhật DB
            $stmt = $conn->prepare("UPDATE customers SET avatar = ? WHERE id = ?");
            if ($stmt->execute([$upload_path, $_SESSION['customer_id']])) {
                echo "<p style='color:green;'>Đã cập nhật DB</p>";
            } else {
                echo "<p style='color:red;'>Lỗi cập nhật DB</p>";
            }
        } else {
            echo "<p style='color:red;'>Lỗi move_uploaded_file</p>";
        }
    } else {
        echo "<p style='color:red;'>Lỗi upload: " . $_FILES['test_avatar']['error'] . "</p>";
    }
}
?>

<hr>
<h3>Test Upload Form:</h3>
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="test_avatar" accept="image/*">
    <button type="submit">Upload Test</button>
</form>

<hr>
<p><a href="index.php?page=profile">Quay lại Profile</a></p>
