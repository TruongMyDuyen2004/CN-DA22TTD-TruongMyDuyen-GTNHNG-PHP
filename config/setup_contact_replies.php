<?php
require_once 'database.php';

try {
    $db = new Database();
    $conn = $db->connect();
    
    echo "Đang thiết lập bảng contact_replies...\n";
    
    // Đọc và thực thi SQL
    $sql = file_get_contents(__DIR__ . '/setup_contact_replies.sql');
    
    // Tách các câu lệnh SQL
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $conn->exec($statement);
                echo "✓ Thực thi thành công: " . substr($statement, 0, 50) . "...\n";
            } catch (PDOException $e) {
                // Bỏ qua lỗi nếu bảng/cột đã tồn tại
                if (strpos($e->getMessage(), 'already exists') === false && 
                    strpos($e->getMessage(), 'Duplicate') === false) {
                    echo "✗ Lỗi: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    echo "\n✓ Hoàn tất thiết lập hệ thống trả lời liên hệ!\n";
    
} catch (Exception $e) {
    echo "✗ Lỗi: " . $e->getMessage() . "\n";
}
