-- Thêm cột giảm giá vào bảng menu_items
ALTER TABLE menu_items 
ADD COLUMN discount_percent INT DEFAULT 0 COMMENT 'Phần trăm giảm giá (0-100)',
ADD COLUMN original_price DECIMAL(10,2) DEFAULT NULL COMMENT 'Giá gốc trước khi giảm';

-- Cập nhật giá gốc cho các món hiện có
UPDATE menu_items SET original_price = price WHERE original_price IS NULL;
