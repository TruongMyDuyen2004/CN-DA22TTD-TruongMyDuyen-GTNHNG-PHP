<?php
/**
 * Script thiết lập quyền admin cho user
 */

session_start();
require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Thiết lập Admin User</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 {
            color: #667eea;
            border-bottom: 3px solid #667eea;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .step {
            margin: 20px 0;
            padding: 15px;
            border-left: 4px solid #667eea;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .success {
            color: #10b981;
            border-left-color: #10b981;
            background: #ecfdf5;
        }
        .error {
            color: #ef4444;
            border-left-color: #ef4444;
            background: #fef2f2;
        }
        .info {
            color: #3b82f6;
            border-left-color: #3b82f6;
            background: #eff6ff;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background: #667eea;
            color: white;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin: 5px;
        }
        .btn:hover {
            background: #764ba2;
        }
        form {
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        input, select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 2px solid #ddd;
            border-radius: 5px;
        }
        button {
            padding: 12px 24px;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
        }
        button:hover {
            background: #059669;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 Thiết lập quyền Admin</h1>";

try {
    // Bước 1: Kiểm tra cột is_admin
    echo "<div class='step info'><strong>Bước 1:</strong> Kiểm tra cột is_admin...</div>";
    
    $stmt = $conn->query("SHOW COLUMNS FROM customers LIKE 'is_admin'");
    $column_exists = $stmt->rowCount() > 0;
    
    if (!$column_exists) {
        echo "<div class='step error'>❌ Cột is_admin chưa tồn tại. Đang tạo...</div>";
        $conn->exec("ALTER TABLE customers ADD COLUMN is_admin BOOLEAN DEFAULT FALSE AFTER email");
        echo "<div class='step success'>✅ Đã tạo cột is_admin thành công!</div>";
    } else {
        echo "<div class='step success'>✅ Cột is_admin đã tồn tại</div>";
    }
    
    // Bước 2: Xử lý form nếu có submit
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['customer_id'])) {
        $customer_id = $_POST['customer_id'];
        $stmt = $conn->prepare("UPDATE customers SET is_admin = 1 WHERE id = ?");
        $stmt->execute([$customer_id]);
        echo "<div class='step success'>✅ Đã cấp quyền admin cho user ID: {$customer_id}</div>";
    }
    
    // Bước 3: Hiển thị danh sách users
    echo "<div class='step info'><strong>Bước 2:</strong> Danh sách người dùng</div>";
    
    $stmt = $conn->query("SELECT id, full_name, email, is_admin FROM customers ORDER BY id DESC LIMIT 20");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Tên</th><th>Email</th><th>Admin</th><th>Hành động</th></tr>";
        foreach ($users as $user) {
            $is_admin = isset($user['is_admin']) && $user['is_admin'] == 1;
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . ($is_admin ? '✅ Admin' : '❌ User') . "</td>";
            echo "<td>";
            if (!$is_admin) {
                echo "<form method='POST' style='margin:0; padding:0; background:none;'>";
                echo "<input type='hidden' name='customer_id' value='{$user['id']}'>";
                echo "<button type='submit'>Cấp quyền Admin</button>";
                echo "</form>";
            } else {
                echo "<span style='color: #10b981;'>Đã là Admin</span>";
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='step error'>❌ Không có user nào trong hệ thống</div>";
    }
    
    // Bước 4: Hướng dẫn
    echo "<div class='step info'>
            <strong>💡 Hướng dẫn:</strong><br>
            1. Click nút 'Cấp quyền Admin' bên cạnh user bạn muốn cấp quyền<br>
            2. Sau khi cấp quyền, đăng nhập lại với tài khoản đó<br>
            3. Vào trang Menu, bạn sẽ thấy nút 'Quản lý thực đơn'<br>
            4. Click vào nút đó để mở trang admin menu
          </div>";
    
    // Kiểm tra user hiện tại
    if (isset($_SESSION['customer_id'])) {
        $stmt = $conn->prepare("SELECT full_name, email, is_admin FROM customers WHERE id = ?");
        $stmt->execute([$_SESSION['customer_id']]);
        $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($current_user) {
            $is_current_admin = isset($current_user['is_admin']) && $current_user['is_admin'] == 1;
            echo "<div class='step " . ($is_current_admin ? 'success' : 'info') . "'>
                    <strong>👤 Tài khoản hiện tại:</strong><br>
                    Tên: " . htmlspecialchars($current_user['full_name']) . "<br>
                    Email: " . htmlspecialchars($current_user['email']) . "<br>
                    Quyền: " . ($is_current_admin ? '✅ Admin' : '❌ User thường') . "
                  </div>";
        }
    } else {
        echo "<div class='step info'>
                <strong>⚠️ Chưa đăng nhập</strong><br>
                <a href='auth/login.php' class='btn'>Đăng nhập ngay</a>
              </div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='step error'>❌ Lỗi: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "
        <div style='text-align: center; margin-top: 30px;'>
            <a href='index.php?page=menu' class='btn'>🍽️ Xem trang Menu</a>
            <a href='index.php' class='btn'>🏠 Về trang chủ</a>
        </div>
    </div>
</body>
</html>";
?>
