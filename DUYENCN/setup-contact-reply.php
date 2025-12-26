<?php
/**
 * Script cập nhật bảng contacts để hỗ trợ tính năng phản hồi
 * Chạy file này một lần để thêm các cột cần thiết
 */

require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

$messages = [];

// Danh sách các cột cần thêm
$columns = [
    'admin_reply' => 'TEXT DEFAULT NULL',
    'replied_at' => 'DATETIME DEFAULT NULL',
    'replied_by' => 'INT DEFAULT NULL',
    'user_read_at' => 'DATETIME DEFAULT NULL'
];

foreach ($columns as $column => $definition) {
    try {
        // Kiểm tra cột đã tồn tại chưa
        $stmt = $conn->query("SHOW COLUMNS FROM contacts LIKE '$column'");
        if ($stmt->rowCount() == 0) {
            // Thêm cột mới
            $conn->exec("ALTER TABLE contacts ADD COLUMN $column $definition");
            $messages[] = "✅ Đã thêm cột '$column'";
        } else {
            $messages[] = "ℹ️ Cột '$column' đã tồn tại";
        }
    } catch (PDOException $e) {
        $messages[] = "❌ Lỗi khi thêm cột '$column': " . $e->getMessage();
    }
}

// Hiển thị kết quả
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Cập nhật Database - Contact Reply</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; background: #1a1a2e; color: #fff; }
        .container { max-width: 600px; margin: 0 auto; background: #16213e; padding: 30px; border-radius: 15px; }
        h1 { color: #d4a574; margin-bottom: 20px; }
        .message { padding: 12px 15px; margin: 10px 0; border-radius: 8px; background: rgba(255,255,255,0.1); }
        .success { border-left: 4px solid #4ade80; }
        .info { border-left: 4px solid #60a5fa; }
        .error { border-left: 4px solid #f87171; }
        a { color: #d4a574; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .back-link { margin-top: 20px; display: inline-block; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Cập nhật Database</h1>
        <p>Kết quả cập nhật bảng <strong>contacts</strong>:</p>
        
        <?php foreach ($messages as $msg): ?>
        <div class="message <?php echo strpos($msg, '✅') !== false ? 'success' : (strpos($msg, '❌') !== false ? 'error' : 'info'); ?>">
            <?php echo $msg; ?>
        </div>
        <?php endforeach; ?>
        
        <p style="margin-top: 20px; color: rgba(255,255,255,0.7);">
            Bây giờ admin có thể trả lời liên hệ và người dùng sẽ thấy phản hồi trong trang Profile.
        </p>
        
        <a href="index.php" class="back-link">← Về trang chủ</a>
    </div>
</body>
</html>
