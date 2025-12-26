-- Cập nhật bảng reviews để thêm likes
ALTER TABLE reviews 
ADD COLUMN IF NOT EXISTS likes_count INT DEFAULT 0 AFTER comment,
ADD COLUMN IF NOT EXISTS is_approved BOOLEAN DEFAULT TRUE AFTER likes_count,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- Tạo bảng review_likes để lưu ai đã like review nào
CREATE TABLE IF NOT EXISTS review_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    customer_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_like (review_id, customer_id),
    INDEX idx_review (review_id),
    INDEX idx_customer (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thêm một số đánh giá mẫu (nếu có khách hàng và món ăn)
INSERT IGNORE INTO reviews (customer_id, menu_item_id, rating, comment, is_approved) 
SELECT 
    c.id,
    m.id,
    FLOOR(4 + RAND() * 2),
    CASE FLOOR(RAND() * 5)
        WHEN 0 THEN 'Món ăn rất ngon, phục vụ tận tình!'
        WHEN 1 THEN 'Chất lượng tuyệt vời, sẽ quay lại!'
        WHEN 2 THEN 'Giá cả hợp lý, món ăn đậm đà!'
        WHEN 3 THEN 'Không gian đẹp, món ăn ngon!'
        ELSE 'Rất hài lòng với dịch vụ!'
    END,
    TRUE
FROM customers c
CROSS JOIN menu_items m
WHERE c.id <= 3 AND m.id <= 5
LIMIT 10;
