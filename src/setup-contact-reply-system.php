<?php
/**
 * Setup Contact Reply System
 * Script đơn giản để thiết lập hệ thống trả lời liên hệ
 */

require_once 'config/database.php';

echo "=== THIẾT LẬP HỆ THỐNG TRẢ LỜI LIÊN HỆ ===\n\n";

try {
    $db = new Database();
    $conn = $db->connect();
    
    echo "✓ Kết nối database thành công\n\n";
    
    // Bước 1: Tạo bảng contact_replies
    echo "Bước 1: Tạo bảng contact_replies...\n";
    try {
        $conn->exec("
            CREATE TABLE IF NOT EXISTS contact_replies (
                id INT AUTO_INCREMENT PRIMARY KEY,
                contact_id INT NOT NULL,
                admin_id INT NOT NULL,
                reply_message TEXT NOT NULL,
                sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_contact_id (contact_id),
                INDEX idx_admin_id (admin_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "  ✓ Bảng contact_replies đã sẵn sàng\n\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "  ℹ️  Bảng đã tồn tại\n\n";
        } else {
            throw $e;
        }
    }
    
    // Bước 2: Thêm cột admin_reply vào contacts
    echo "Bước 2: Thêm cột admin_reply vào bảng contacts...\n";
    try {
        $conn->exec("ALTER TABLE contacts ADD COLUMN admin_reply TEXT NULL");
        echo "  ✓ Đã thêm cột admin_reply\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "  ℹ️  Cột admin_reply đã tồn tại\n";
        } else {
            echo "  ⚠️  " . $e->getMessage() . "\n";
        }
    }
    
    // Bước 3: Thêm cột replied_at
    echo "Bước 3: Thêm cột replied_at vào bảng contacts...\n";
    try {
        $conn->exec("ALTER TABLE contacts ADD COLUMN replied_at TIMESTAMP NULL");
        echo "  ✓ Đã thêm cột replied_at\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "  ℹ️  Cột replied_at đã tồn tại\n";
        } else {
            echo "  ⚠️  " . $e->getMessage() . "\n";
        }
    }
    
    // Bước 4: Thêm cột replied_by
    echo "Bước 4: Thêm cột replied_by vào bảng contacts...\n";
    try {
        $conn->exec("ALTER TABLE contacts ADD COLUMN replied_by INT NULL");
        echo "  ✓ Đã thêm cột replied_by\n\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "  ℹ️  Cột replied_by đã tồn tại\n\n";
        } else {
            echo "  ⚠️  " . $e->getMessage() . "\n\n";
        }
    }
    
    // Bước 5: Thêm foreign keys
    echo "Bước 5: Thiết lập foreign keys...\n";
    
    // FK cho contact_replies -> contacts
    try {
        $conn->exec("
            ALTER TABLE contact_replies 
            ADD CONSTRAINT fk_contact_replies_contact 
            FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE
        ");
        echo "  ✓ FK contact_replies -> contacts\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false || 
            strpos($e->getMessage(), 'Duplicate') !== false) {
            echo "  ℹ️  FK contact_replies -> contacts đã tồn tại\n";
        }
    }
    
    // FK cho contact_replies -> admins
    try {
        $conn->exec("
            ALTER TABLE contact_replies 
            ADD CONSTRAINT fk_contact_replies_admin 
            FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
        ");
        echo "  ✓ FK contact_replies -> admins\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false || 
            strpos($e->getMessage(), 'Duplicate') !== false) {
            echo "  ℹ️  FK contact_replies -> admins đã tồn tại\n";
        }
    }
    
    // FK cho contacts -> admins
    try {
        $conn->exec("
            ALTER TABLE contacts 
            ADD CONSTRAINT fk_contacts_replied_by 
            FOREIGN KEY (replied_by) REFERENCES admins(id) ON DELETE SET NULL
        ");
        echo "  ✓ FK contacts -> admins (replied_by)\n\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false || 
            strpos($e->getMessage(), 'Duplicate') !== false) {
            echo "  ℹ️  FK contacts -> admins đã tồn tại\n\n";
        }
    }
    
    // Kiểm tra kết quả
    echo "=== KIỂM TRA KẾT QUẢ ===\n\n";
    
    // Kiểm tra bảng contact_replies
    $stmt = $conn->query("SHOW TABLES LIKE 'contact_replies'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Bảng contact_replies: OK\n";
        
        $stmt = $conn->query("SELECT COUNT(*) as count FROM contact_replies");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "  Số phản hồi hiện có: $count\n\n";
    } else {
        echo "✗ Bảng contact_replies: KHÔNG TỒN TẠI\n\n";
    }
    
    // Kiểm tra các cột trong contacts
    $stmt = $conn->query("DESCRIBE contacts");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $required_columns = ['admin_reply', 'replied_at', 'replied_by'];
    foreach ($required_columns as $col) {
        if (in_array($col, $columns)) {
            echo "✓ Cột contacts.$col: OK\n";
        } else {
            echo "✗ Cột contacts.$col: THIẾU\n";
        }
    }
    
    echo "\n=== HOÀN TẤT ===\n\n";
    echo "🎉 Hệ thống trả lời liên hệ đã được thiết lập thành công!\n\n";
    echo "Các bước tiếp theo:\n";
    echo "1. Truy cập: admin/contacts.php\n";
    echo "2. Đăng nhập với tài khoản admin\n";
    echo "3. Xem và trả lời các tin nhắn liên hệ\n\n";
    echo "📖 Xem hướng dẫn chi tiết: HUONG_DAN_TRA_LOI_LIEN_HE.md\n";
    echo "🧪 Chạy test: test-contact-reply.php\n\n";
    
} catch (Exception $e) {
    echo "\n✗ LỖI: " . $e->getMessage() . "\n";
    echo "\nVui lòng kiểm tra:\n";
    echo "- Kết nối database\n";
    echo "- Quyền truy cập database\n";
    echo "- Bảng contacts và admins đã tồn tại\n";
}
?>
