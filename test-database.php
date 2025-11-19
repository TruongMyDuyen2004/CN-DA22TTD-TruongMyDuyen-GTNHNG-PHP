<?php
/**
 * File test kết nối database
 * Chạy file này để kiểm tra kết nối database có hoạt động không
 * URL: http://localhost/your-project/test-database.php
 */

require_once 'config/database.php';

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Test Database Connection</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .container {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
        }
        h1 {
            color: #333;
            margin-bottom: 2rem;
            text-align: center;
        }
        .status {
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 2px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #f5c6cb;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 2px solid #bee5eb;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }
        .info strong {
            display: block;
            margin-bottom: 0.5rem;
        }
        .icon {
            font-size: 2rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        th, td {
            padding: 0.8rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .btn {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 1rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔍 Test Kết Nối Database</h1>";

try {
    $db = new Database();
    $conn = $db->connect();
    
    if ($conn) {
        echo "<div class='status success'>
                <span class='icon'>✅</span>
                <div>
                    <strong>Kết nối thành công!</strong>
                    <p>Database đã được kết nối thành công.</p>
                </div>
              </div>";
        
        // Kiểm tra các bảng
        echo "<h3 style='margin-top: 2rem; margin-bottom: 1rem;'>📊 Danh sách bảng trong database:</h3>";
        
        $stmt = $conn->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($tables) > 0) {
            echo "<table>";
            echo "<thead><tr><th>#</th><th>Tên bảng</th><th>Số dòng</th></tr></thead>";
            echo "<tbody>";
            
            $i = 1;
            foreach ($tables as $table) {
                $countStmt = $conn->query("SELECT COUNT(*) as count FROM `$table`");
                $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
                
                echo "<tr>";
                echo "<td>$i</td>";
                echo "<td><strong>$table</strong></td>";
                echo "<td>$count dòng</td>";
                echo "</tr>";
                $i++;
            }
            
            echo "</tbody></table>";
        } else {
            echo "<div class='info'>
                    <strong>⚠️ Chưa có bảng nào!</strong>
                    <p>Vui lòng chạy file <code>config/setup_full.sql</code> trong phpMyAdmin để tạo các bảng.</p>
                  </div>";
        }
        
        // Kiểm tra admin
        echo "<h3 style='margin-top: 2rem; margin-bottom: 1rem;'>👤 Kiểm tra tài khoản Admin:</h3>";
        
        $stmt = $conn->query("SELECT COUNT(*) as count FROM admins");
        $adminCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($adminCount > 0) {
            echo "<div class='status success'>
                    <span class='icon'>✅</span>
                    <div>
                        <strong>Đã có tài khoản admin!</strong>
                        <p>Có $adminCount tài khoản admin trong hệ thống.</p>
                    </div>
                  </div>";
        } else {
            echo "<div class='info'>
                    <strong>⚠️ Chưa có tài khoản admin!</strong>
                    <p>Vui lòng truy cập <a href='config/create_admin.php'>config/create_admin.php</a> để tạo tài khoản admin.</p>
                  </div>";
        }
        
    } else {
        echo "<div class='status error'>
                <span class='icon'>❌</span>
                <div>
                    <strong>Kết nối thất bại!</strong>
                    <p>Không thể kết nối đến database.</p>
                </div>
              </div>";
    }
    
} catch(PDOException $e) {
    echo "<div class='status error'>
            <span class='icon'>❌</span>
            <div>
                <strong>Lỗi kết nối!</strong>
                <p>" . $e->getMessage() . "</p>
            </div>
          </div>";
    
    echo "<div class='info'>
            <strong>💡 Hướng dẫn khắc phục:</strong>
            <ol style='margin-top: 0.5rem; padding-left: 1.5rem;'>
                <li>Kiểm tra MySQL đã chạy chưa (XAMPP/WAMP)</li>
                <li>Kiểm tra thông tin trong file <code>config/database.php</code></li>
                <li>Đảm bảo database <code>ngon_gallery</code> đã được tạo</li>
                <li>Kiểm tra username và password MySQL</li>
            </ol>
          </div>";
}

echo "
        <div style='text-align: center; margin-top: 2rem;'>
            <a href='admin/login.php' class='btn'>🔐 Đăng nhập Admin</a>
            <a href='index.php' class='btn' style='margin-left: 1rem;'>🏠 Trang chủ</a>
        </div>
    </div>
</body>
</html>";
?>
