-- Fix Categories - Chỉ giữ 5 danh mục chính
-- Chạy file này để sửa lại danh mục

SET FOREIGN_KEY_CHECKS = 0;

-- Xóa tất cả danh mục cũ
TRUNCATE TABLE categories;

-- Thêm đúng 5 danh mục
INSERT INTO categories (id, name, name_en, display_order) VALUES
(1, 'Khai vị', 'Appetizer', 1),
(2, 'Món chính', 'Main Course', 2),
(3, 'Món phụ', 'Side Dish', 3),
(4, 'Tráng miệng', 'Dessert', 4),
(5, 'Đồ uống', 'Drinks', 5);

-- Cập nhật tất cả menu_items có category_id không hợp lệ về Món chính (id=2)
UPDATE menu_items SET category_id = 2 WHERE category_id NOT IN (1, 2, 3, 4, 5) OR category_id IS NULL;

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Done! Categories fixed.' AS result;
