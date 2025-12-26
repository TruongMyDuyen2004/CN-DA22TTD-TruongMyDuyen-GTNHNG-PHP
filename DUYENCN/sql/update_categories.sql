-- Tắt kiểm tra foreign key
SET FOREIGN_KEY_CHECKS = 0;

-- Xóa tất cả danh mục cũ
DELETE FROM categories;

-- Thêm 5 danh mục mới
INSERT INTO categories (id, name, name_en, display_order) VALUES
(1, 'Khai vị', 'Appetizer', 1),
(2, 'Món chính', 'Main Course', 2),
(3, 'Món phụ', 'Side Dish', 3),
(4, 'Tráng miệng', 'Dessert', 4),
(5, 'Đồ uống', 'Drinks', 5);

-- Bật lại kiểm tra foreign key
SET FOREIGN_KEY_CHECKS = 1;

-- Cập nhật category_id trong menu_items để khớp với danh mục mới
-- Bạn cần tự cập nhật category_id cho từng món ăn theo ý muốn
-- Ví dụ:
-- UPDATE menu_items SET category_id = 1 WHERE id IN (1, 2, 3); -- Khai vị
-- UPDATE menu_items SET category_id = 2 WHERE id IN (4, 5, 6); -- Món chính
-- UPDATE menu_items SET category_id = 3 WHERE id IN (7, 8); -- Món phụ
-- UPDATE menu_items SET category_id = 4 WHERE id IN (9, 10); -- Tráng miệng
-- UPDATE menu_items SET category_id = 5 WHERE id IN (11, 12); -- Đồ uống
