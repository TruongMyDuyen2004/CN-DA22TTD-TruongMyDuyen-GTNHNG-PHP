-- Thêm dữ liệu menu Ngon Gallery giống hình mẫu
-- Chạy file này trong phpMyAdmin

-- Xóa dữ liệu cũ (nếu có)
DELETE FROM menu_items;
DELETE FROM categories;

-- Reset AUTO_INCREMENT
ALTER TABLE categories AUTO_INCREMENT = 1;
ALTER TABLE menu_items AUTO_INCREMENT = 1;

-- Thêm danh mục
INSERT INTO `categories` (`id`, `name`, `name_en`, `description`, `display_order`) VALUES
(1, 'KHAI VỊ', 'APPETIZERS', 'Món khai vị', 1),
(2, 'MÓN CHÍNH', 'MAIN DISHES', 'Món chính', 2),
(3, 'CANH - TIỀM - SUP', 'SOUP', 'Các loại canh và súp', 3),
(4, 'CƠM - MÌ - CHÁO', 'RICE & NOODLES', 'Cơm, mì, cháo', 4),
(5, 'BÁNH VÀ TRÁNG MIỆNG', 'DESSERTS', 'Bánh và tráng miệng', 5),
(6, 'ĐỒ UỐNG', 'BEVERAGES', 'Đồ uống', 6);

-- KHAI VỊ
INSERT INTO `menu_items` (`name`, `name_en`, `description`, `description_en`, `price`, `category_id`, `image`, `is_available`) VALUES
('Salad', 'Salad', 'Salad tươi ngon với rau củ đa dạng', 'Fresh salad with various vegetables', 45000, 1, '🥗', 1),
('Gỏi', 'Vietnamese Salad', 'Gỏi truyền thống Việt Nam', 'Traditional Vietnamese salad', 55000, 1, '🥙', 1);

-- MÓN CHÍNH
INSERT INTO `menu_items` (`name`, `name_en`, `description`, `description_en`, `price`, `category_id`, `image`, `is_available`) VALUES
('Món bò', 'Beef Dishes', 'Các món bò đặc sắc', 'Special beef dishes', 120000, 2, '🥩', 1),
('Món gà', 'Chicken Dishes', 'Các món gà thơm ngon', 'Delicious chicken dishes', 95000, 2, '🍗', 1),
('Món heo', 'Pork Dishes', 'Các món heo đa dạng', 'Various pork dishes', 85000, 2, '🥓', 1),
('Món cá', 'Fish Dishes', 'Cá tươi chế biến', 'Fresh fish dishes', 110000, 2, '🐟', 1);

-- CANH - TIỀM - SUP
INSERT INTO `menu_items` (`name`, `name_en`, `description`, `description_en`, `price`, `category_id`, `image`, `is_available`) VALUES
('Canh', 'Soup', 'Canh thanh mát', 'Light soup', 35000, 3, '�', '1),
('Tiềm', 'Braised Soup', 'Món tiềm bổ dưỡng', 'Nutritious braised soup', 75000, 3, '🥘', 1),
('Súp', 'Cream Soup', 'Súp kem thơm ngon', 'Delicious cream soup', 45000, 3, '🍜', 1);

-- CƠM - MÌ - CHÁO
INSERT INTO `menu_items` (`name`, `name_en`, `description`, `description_en`, `price`, `category_id`, `image`, `is_available`) VALUES
('Cơm', 'Rice', 'Cơm trắng thơm', 'Fragrant white rice', 15000, 4, '🍚', 1),
('Mì', 'Noodles', 'Mì các loại', 'Various noodles', 45000, 4, '🍝', 1),
('Cháo', 'Porridge', 'Cháo dinh dưỡng', 'Nutritious porridge', 35000, 4, '🥣', 1);

-- BÁNH VÀ TRÁNG MIỆNG
INSERT INTO `menu_items` (`name`, `name_en`, `description`, `description_en`, `price`, `category_id`, `image`, `is_available`) VALUES
('Bánh', 'Cake', 'Bánh ngọt thơm ngon', 'Delicious sweet cake', 35000, 5, '🍰', 1),
('Tráng miệng', 'Dessert', 'Tráng miệng đa dạng', 'Various desserts', 40000, 5, '🍮', 1);

-- ĐỒ UỐNG
INSERT INTO `menu_items` (`name`, `name_en`, `description`, `description_en`, `price`, `category_id`, `image`, `is_available`) VALUES
('Cà phê', 'Coffee', 'Cà phê rang xay', 'Roasted coffee', 25000, 6, '☕', 1),
('Trà sữa', 'Milk Tea', 'Trà sữa thơm ngon', 'Delicious milk tea', 35000, 6, '🧋', 1);
