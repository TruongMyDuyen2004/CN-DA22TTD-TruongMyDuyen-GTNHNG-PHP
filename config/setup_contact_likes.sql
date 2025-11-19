-- Tạo bảng lưu likes cho phản hồi admin
CREATE TABLE IF NOT EXISTS contact_reply_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contact_id INT NOT NULL,
    customer_id INT NULL,
    customer_email VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_contact_id (contact_id),
    INDEX idx_customer_id (customer_id),
    INDEX idx_customer_email (customer_email),
    UNIQUE KEY unique_like (contact_id, customer_id),
    UNIQUE KEY unique_like_email (contact_id, customer_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thêm cột likes_count vào bảng contacts
ALTER TABLE contacts ADD COLUMN IF NOT EXISTS likes_count INT DEFAULT 0;
