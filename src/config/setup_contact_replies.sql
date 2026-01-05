-- Tạo bảng lưu trữ phản hồi của admin cho liên hệ
CREATE TABLE IF NOT EXISTS contact_replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contact_id INT NOT NULL,
    admin_id INT NOT NULL,
    reply_message TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_contact_id (contact_id),
    INDEX idx_admin_id (admin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thêm foreign keys cho contact_replies (nếu chưa có)
-- Bỏ qua lỗi nếu đã tồn tại
SET @exist := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
    WHERE CONSTRAINT_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'contact_replies' 
    AND CONSTRAINT_NAME = 'contact_replies_ibfk_1');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE contact_replies ADD CONSTRAINT contact_replies_ibfk_1 FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE',
    'SELECT "Foreign key contact_replies_ibfk_1 already exists"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
    WHERE CONSTRAINT_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'contact_replies' 
    AND CONSTRAINT_NAME = 'contact_replies_ibfk_2');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE contact_replies ADD CONSTRAINT contact_replies_ibfk_2 FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE',
    'SELECT "Foreign key contact_replies_ibfk_2 already exists"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Thêm các cột vào bảng contacts (nếu chưa có)
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'contacts' 
    AND COLUMN_NAME = 'admin_reply');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE contacts ADD COLUMN admin_reply TEXT NULL',
    'SELECT "Column admin_reply already exists"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'contacts' 
    AND COLUMN_NAME = 'replied_at');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE contacts ADD COLUMN replied_at TIMESTAMP NULL',
    'SELECT "Column replied_at already exists"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'contacts' 
    AND COLUMN_NAME = 'replied_by');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE contacts ADD COLUMN replied_by INT NULL',
    'SELECT "Column replied_by already exists"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Thêm foreign key cho replied_by (nếu chưa có)
SET @exist := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
    WHERE CONSTRAINT_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'contacts' 
    AND CONSTRAINT_NAME = 'contacts_replied_by_fk');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE contacts ADD CONSTRAINT contacts_replied_by_fk FOREIGN KEY (replied_by) REFERENCES admins(id) ON DELETE SET NULL',
    'SELECT "Foreign key contacts_replied_by_fk already exists"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
