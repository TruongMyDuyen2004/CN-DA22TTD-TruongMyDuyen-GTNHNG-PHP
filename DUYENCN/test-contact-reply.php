<?php
/**
 * Test Contact Reply System
 * Kiểm tra hệ thống trả lời liên hệ
 */

require_once 'config/database.php';

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Test Contact Reply System</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-section {
            background: white;
            padding: 25px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #FF6B35;
            border-bottom: 3px solid #FF6B35;
            padding-bottom: 10px;
        }
        h2 {
            color: #333;
            margin-top: 0;
        }
        .success {
            color: #28a745;
            padding: 10px;
            background: #d4edda;
            border-left: 4px solid #28a745;
            margin: 10px 0;
        }
        .error {
            color: #dc3545;
            padding: 10px;
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            margin: 10px 0;
        }
        .info {
            color: #0c5460;
            padding: 10px;
            background: #d1ecf1;
            border-left: 4px solid #17a2b8;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #FF6B35;
            color: white;
        }
        tr:hover {
            background: #f5f5f5;
        }
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-success { background: #28a745; color: white; }
        .badge-danger { background: #dc3545; color: white; }
        .badge-warning { background: #ffc107; color: #333; }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn-primary {
            background: #FF6B35;
            color: white;
        }
        .btn-primary:hover {
            background: #e55a2b;
        }
        pre {
            background: #f4f4f4;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>🧪 Test Contact Reply System</h1>";

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Test 1: Kiểm tra bảng contacts
    echo "<div class='test-section'>";
    echo "<h2>1️⃣ Kiểm tra bảng contacts</h2>";
    
    try {
        $stmt = $conn->query("DESCRIBE contacts");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<div class='success'>✓ Bảng contacts tồn tại</div>";
        echo "<table>";
        echo "<tr><th>Cột</th><th>Kiểu dữ liệu</th><th>Null</th><th>Key</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Kiểm tra các cột cần thiết
        $required_columns = ['admin_reply', 'replied_at', 'replied_by'];
        $existing_columns = array_column($columns, 'Field');
        
        foreach ($required_columns as $col) {
            if (in_array($col, $existing_columns)) {
                echo "<div class='success'>✓ Cột '$col' đã tồn tại</div>";
            } else {
                echo "<div class='error'>✗ Cột '$col' chưa tồn tại - Cần chạy setup</div>";
            }
        }
        
    } catch (PDOException $e) {
        echo "<div class='error'>✗ Lỗi: " . $e->getMessage() . "</div>";
    }
    echo "</div>";
    
    // Test 2: Kiểm tra bảng contact_replies
    echo "<div class='test-section'>";
    echo "<h2>2️⃣ Kiểm tra bảng contact_replies</h2>";
    
    try {
        $stmt = $conn->query("DESCRIBE contact_replies");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<div class='success'>✓ Bảng contact_replies tồn tại</div>";
        echo "<table>";
        echo "<tr><th>Cột</th><th>Kiểu dữ liệu</th><th>Null</th><th>Key</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } catch (PDOException $e) {
        echo "<div class='error'>✗ Bảng contact_replies chưa tồn tại</div>";
        echo "<div class='info'>💡 Chạy lệnh: php config/setup_contact_replies.php</div>";
    }
    echo "</div>";
    
    // Test 3: Kiểm tra dữ liệu contacts
    echo "<div class='test-section'>";
    echo "<h2>3️⃣ Dữ liệu contacts hiện có</h2>";
    
    $stmt = $conn->query("
        SELECT 
            c.*,
            a.username as admin_username
        FROM contacts c
        LEFT JOIN admins a ON c.replied_by = a.id
        ORDER BY c.created_at DESC
        LIMIT 10
    ");
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($contacts) > 0) {
        echo "<div class='info'>📊 Tìm thấy " . count($contacts) . " liên hệ (hiển thị 10 mới nhất)</div>";
        echo "<table>";
        echo "<tr>
                <th>ID</th>
                <th>Tên</th>
                <th>Email</th>
                <th>Trạng thái</th>
                <th>Đã trả lời</th>
                <th>Admin</th>
                <th>Ngày tạo</th>
              </tr>";
        
        foreach ($contacts as $contact) {
            $status_badge = [
                'new' => '<span class="badge badge-warning">Chưa đọc</span>',
                'read' => '<span class="badge" style="background:#17a2b8;color:white;">Đã đọc</span>',
                'replied' => '<span class="badge badge-success">Đã trả lời</span>'
            ];
            
            echo "<tr>";
            echo "<td>#{$contact['id']}</td>";
            echo "<td>" . htmlspecialchars($contact['name']) . "</td>";
            echo "<td>" . htmlspecialchars($contact['email']) . "</td>";
            echo "<td>" . ($status_badge[$contact['status']] ?? $contact['status']) . "</td>";
            echo "<td>" . ($contact['admin_reply'] ? '✓' : '✗') . "</td>";
            echo "<td>" . ($contact['admin_username'] ?? '-') . "</td>";
            echo "<td>" . date('d/m/Y H:i', strtotime($contact['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='info'>ℹ️ Chưa có liên hệ nào</div>";
    }
    echo "</div>";
    
    // Test 4: Kiểm tra contact_replies
    echo "<div class='test-section'>";
    echo "<h2>4️⃣ Lịch sử phản hồi</h2>";
    
    try {
        $stmt = $conn->query("
            SELECT 
                cr.*,
                c.name as contact_name,
                c.email as contact_email,
                a.username as admin_username
            FROM contact_replies cr
            JOIN contacts c ON cr.contact_id = c.id
            JOIN admins a ON cr.admin_id = a.id
            ORDER BY cr.sent_at DESC
            LIMIT 10
        ");
        $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($replies) > 0) {
            echo "<div class='info'>📊 Tìm thấy " . count($replies) . " phản hồi (hiển thị 10 mới nhất)</div>";
            echo "<table>";
            echo "<tr>
                    <th>ID</th>
                    <th>Khách hàng</th>
                    <th>Admin</th>
                    <th>Nội dung</th>
                    <th>Thời gian</th>
                  </tr>";
            
            foreach ($replies as $reply) {
                echo "<tr>";
                echo "<td>#{$reply['id']}</td>";
                echo "<td>" . htmlspecialchars($reply['contact_name']) . "<br><small>" . htmlspecialchars($reply['contact_email']) . "</small></td>";
                echo "<td>" . htmlspecialchars($reply['admin_username']) . "</td>";
                echo "<td>" . htmlspecialchars(substr($reply['reply_message'], 0, 100)) . "...</td>";
                echo "<td>" . date('d/m/Y H:i', strtotime($reply['sent_at'])) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='info'>ℹ️ Chưa có phản hồi nào</div>";
        }
    } catch (PDOException $e) {
        echo "<div class='error'>✗ Không thể truy vấn: " . $e->getMessage() . "</div>";
    }
    echo "</div>";
    
    // Test 5: Thống kê
    echo "<div class='test-section'>";
    echo "<h2>5️⃣ Thống kê</h2>";
    
    $stats = $conn->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_count,
            SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as read_count,
            SUM(CASE WHEN status = 'replied' THEN 1 ELSE 0 END) as replied_count,
            SUM(CASE WHEN admin_reply IS NOT NULL THEN 1 ELSE 0 END) as has_reply
        FROM contacts
    ")->fetch(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>Chỉ số</th><th>Giá trị</th></tr>";
    echo "<tr><td>Tổng liên hệ</td><td><strong>{$stats['total']}</strong></td></tr>";
    echo "<tr><td>Chưa đọc</td><td><span class='badge badge-warning'>{$stats['new_count']}</span></td></tr>";
    echo "<tr><td>Đã đọc</td><td><span class='badge' style='background:#17a2b8;color:white;'>{$stats['read_count']}</span></td></tr>";
    echo "<tr><td>Đã trả lời</td><td><span class='badge badge-success'>{$stats['replied_count']}</span></td></tr>";
    echo "<tr><td>Có nội dung phản hồi</td><td><strong>{$stats['has_reply']}</strong></td></tr>";
    echo "</table>";
    
    // Tính tỷ lệ phản hồi
    if ($stats['total'] > 0) {
        $reply_rate = round(($stats['replied_count'] / $stats['total']) * 100, 2);
        echo "<div class='info'>📈 Tỷ lệ phản hồi: <strong>{$reply_rate}%</strong></div>";
    }
    echo "</div>";
    
    // Test 6: Kiểm tra API
    echo "<div class='test-section'>";
    echo "<h2>6️⃣ Kiểm tra API</h2>";
    
    $api_file = 'api/send-contact-reply.php';
    if (file_exists($api_file)) {
        echo "<div class='success'>✓ File API tồn tại: $api_file</div>";
        
        // Kiểm tra quyền đọc
        if (is_readable($api_file)) {
            echo "<div class='success'>✓ File có thể đọc được</div>";
        } else {
            echo "<div class='error'>✗ File không thể đọc được</div>";
        }
    } else {
        echo "<div class='error'>✗ File API không tồn tại: $api_file</div>";
    }
    
    // Kiểm tra admin page
    $admin_file = 'admin/contacts.php';
    if (file_exists($admin_file)) {
        echo "<div class='success'>✓ Trang admin tồn tại: $admin_file</div>";
    } else {
        echo "<div class='error'>✗ Trang admin không tồn tại: $admin_file</div>";
    }
    echo "</div>";
    
    // Hướng dẫn
    echo "<div class='test-section'>";
    echo "<h2>7️⃣ Hướng dẫn sử dụng</h2>";
    echo "<div class='info'>";
    echo "<h3>Để sử dụng tính năng trả lời liên hệ:</h3>";
    echo "<ol>";
    echo "<li>Đảm bảo database đã được setup: <code>php config/setup_contact_replies.php</code></li>";
    echo "<li>Đăng nhập vào admin panel</li>";
    echo "<li>Vào trang <strong>Quản lý liên hệ</strong></li>";
    echo "<li>Click nút <strong>Xem</strong> hoặc <strong>Trả lời</strong> trên tin nhắn</li>";
    echo "<li>Nhập nội dung phản hồi và gửi</li>";
    echo "</ol>";
    echo "<p><a href='admin/contacts.php' class='btn btn-primary'>🚀 Đi đến Quản lý liên hệ</a></p>";
    echo "<p><a href='HUONG_DAN_TRA_LOI_LIEN_HE.md' class='btn btn-primary'>📖 Xem hướng dẫn chi tiết</a></p>";
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>✗ Lỗi kết nối database: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>
