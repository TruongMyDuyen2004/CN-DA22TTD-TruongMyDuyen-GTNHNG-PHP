-- Cập nhật bảng reviews để hỗ trợ đầy đủ tính năng đánh giá món ăn

USE ngon_gallery;

-- Thêm cột is_approved nếu chưa có
ALTER TABLE reviews 
ADD COLUMN IF NOT EXISTS is_approved BOOLEAN DEFAULT TRUE,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Thêm index để tối ưu truy vấn
ALTER TABLE reviews
ADD INDEX IF NOT EXISTS idx_menu_item (menu_item_id),
ADD INDEX IF NOT EXISTS idx_customer (customer_id),
ADD INDEX IF NOT EXISTS idx_approved (is_approved),
ADD INDEX IF NOT EXISTS idx_created (created_at);

-- Cập nhật các đánh giá cũ thành approved
UPDATE reviews SET is_approved = TRUE WHERE is_approved IS NULL;
