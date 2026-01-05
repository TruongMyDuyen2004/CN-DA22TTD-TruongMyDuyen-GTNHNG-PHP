-- Reset Menu với 50 món ăn (10 món mỗi danh mục)
-- Chạy file này trong phpMyAdmin

-- Tắt kiểm tra foreign key
SET FOREIGN_KEY_CHECKS = 0;

-- Xóa dữ liệu cũ
DELETE FROM order_items;
DELETE FROM menu_items;
DELETE FROM categories;

-- Reset AUTO_INCREMENT
ALTER TABLE categories AUTO_INCREMENT = 1;
ALTER TABLE menu_items AUTO_INCREMENT = 1;

-- Tạo 5 danh mục (không có cột description)
INSERT INTO categories (id, name, name_en, display_order) VALUES
(1, 'Khai vị', 'Appetizers', 1),
(2, 'Món chính', 'Main Dishes', 2),
(3, 'Món phụ', 'Side Dishes', 3),
(4, 'Tráng miệng', 'Desserts', 4),
(5, 'Đồ uống', 'Beverages', 5);

-- =============================================
-- KHAI VỊ (category_id = 1) - 10 món
-- =============================================
INSERT INTO menu_items (name, name_en, description, description_en, price, category_id, image, is_available) VALUES
('Gỏi cuốn tôm thịt', 'Fresh Spring Rolls', 'Gỏi cuốn tươi với tôm, thịt và rau sống', 'Fresh spring rolls with shrimp, pork and vegetables', 45000, 1, 'uploads/menu/goi-cuon.jpg', 1),
('Chả giò Sài Gòn', 'Crispy Spring Rolls', 'Chả giò chiên giòn nhân thịt và rau củ', 'Crispy fried spring rolls with meat and vegetables', 50000, 1, 'uploads/menu/cha-gio.jpg', 1),
('Bánh xèo miền Tây', 'Vietnamese Crepe', 'Bánh xèo giòn rụm với nhân tôm thịt đậy đặn', 'Crispy Vietnamese crepe with shrimp and pork', 55000, 1, 'uploads/menu/banh-xeo.jpg', 1),
('Gỏi ngó sen tôm thịt', 'Lotus Stem Salad', 'Gỏi ngó sen giòn ngọt thanh mát', 'Fresh lotus stem salad with shrimp and pork', 55000, 1, 'uploads/menu/goi-ngo-sen.jpg', 1),
('Nem nướng Nha Trang', 'Grilled Pork Rolls', 'Nem nướng thơm ngon kiểu Nha Trang', 'Nha Trang style grilled pork rolls', 60000, 1, 'uploads/menu/nem-nuong.jpg', 1),
('Bánh khọt Vũng Tàu', 'Mini Savory Pancakes', 'Bánh khọt giòn rụm với tôm tươi', 'Crispy mini pancakes with fresh shrimp', 50000, 1, 'uploads/menu/banh-khot.jpg', 1),
('Gỏi xoài tôm khô', 'Green Mango Salad', 'Gỏi xoài xanh chua ngọt với tôm khô', 'Green mango salad with dried shrimp', 45000, 1, 'uploads/menu/goi-xoai.jpg', 1),
('Chạo tôm cuốn mía', 'Shrimp on Sugarcane', 'Tôm quấn mía nướng thơm lừng', 'Grilled shrimp paste wrapped around sugarcane', 65000, 1, 'uploads/menu/chao-tom.jpg', 1),
('Bánh cuốn Thanh Trì', 'Steamed Rice Rolls', 'Bánh cuốn mỏng mịn nhân thịt', 'Thin steamed rice rolls with pork filling', 40000, 1, 'uploads/menu/banh-cuon.jpg', 1),
('Súp măng cua', 'Crab Asparagus Soup', 'Súp măng tây cua thơm ngon bổ dưỡng', 'Nutritious crab and asparagus soup', 55000, 1, 'uploads/menu/sup-mang-cua.jpg', 1);

-- =============================================
-- MÓN CHÍNH (category_id = 2) - 10 món
-- =============================================
INSERT INTO menu_items (name, name_en, description, description_en, price, category_id, image, is_available) VALUES
('Phở bò đặc biệt', 'Special Beef Pho', 'Phở bò truyền thống với nước dùng hầm xương 12 tiếng', 'Traditional beef pho with 12-hour broth', 65000, 2, 'uploads/menu/pho-bo.jpg', 1),
('Bún chả Hà Nội', 'Hanoi Grilled Pork Noodles', 'Bún chả thơm ngon với thịt nướng than hoa', 'Grilled pork with vermicelli Hanoi style', 55000, 2, 'uploads/menu/bun-cha.jpg', 1),
('Cơm tấm sườn bì chả', 'Broken Rice with Pork', 'Cơm tấm Sài Gòn với sườn nướng mềm ngọt', 'Saigon broken rice with grilled pork chop', 60000, 2, 'uploads/menu/com-tam.jpg', 1),
('Bún bò Huế', 'Hue Spicy Beef Noodles', 'Bún bò cay nồng đặc trưng xứ Huế', 'Spicy beef noodle soup Hue style', 60000, 2, 'uploads/menu/bun-bo-hue.jpg', 1),
('Cá kho tộ', 'Caramelized Fish in Clay Pot', 'Cá kho tộ đậm đà vị miền Nam', 'Southern style caramelized fish in clay pot', 75000, 2, 'uploads/menu/ca-kho-to.jpg', 1),
('Thịt kho tàu', 'Braised Pork Belly', 'Thịt kho tàu mềm thơm với trứng', 'Braised pork belly with eggs', 70000, 2, 'uploads/menu/thit-kho-tau.jpg', 1),
('Gà nướng mật ong', 'Honey Grilled Chicken', 'Gà nướng mật ong vàng óng thơm lừng', 'Golden honey glazed grilled chicken', 85000, 2, 'uploads/menu/ga-nuong.jpg', 1),
('Bò lúc lắc', 'Shaking Beef', 'Bò lúc lắc mềm ngọt xào với rau củ', 'Tender beef cubes stir-fried with vegetables', 95000, 2, 'uploads/menu/bo-luc-lac.jpg', 1),
('Mì Quảng', 'Quang Noodles', 'Mì Quảng đặc sản miền Trung', 'Central Vietnam specialty noodles', 55000, 2, 'uploads/menu/mi-quang.jpg', 1),
('Cơm chiên Dương Châu', 'Yang Chow Fried Rice', 'Cơm chiên với tôm, lạp xưởng và trứng', 'Fried rice with shrimp, sausage and egg', 55000, 2, 'uploads/menu/com-chien.jpg', 1);


-- =============================================
-- MÓN PHỤ (category_id = 3) - 10 món
-- =============================================
INSERT INTO menu_items (name, name_en, description, description_en, price, category_id, image, is_available) VALUES
('Rau muống xào tỏi', 'Stir-fried Water Spinach', 'Rau muống xào tỏi giòn ngọt', 'Garlic stir-fried water spinach', 35000, 3, 'uploads/menu/rau-muong.jpg', 1),
('Đậu hũ sốt cà', 'Tofu in Tomato Sauce', 'Đậu hũ chiên sốt cà chua thơm ngon', 'Fried tofu in savory tomato sauce', 40000, 3, 'uploads/menu/dau-hu.jpg', 1),
('Canh chua cá lóc', 'Sour Fish Soup', 'Canh chua miền Nam với cá lóc tươi', 'Southern Vietnamese sour soup with snakehead fish', 50000, 3, 'uploads/menu/canh-chua.jpg', 1),
('Cải thìa xào nấm', 'Bok Choy with Mushrooms', 'Cải thìa xào nấm đông cô thơm ngon', 'Bok choy stir-fried with shiitake mushrooms', 40000, 3, 'uploads/menu/cai-thia.jpg', 1),
('Trứng chiên hành', 'Scallion Omelette', 'Trứng chiên hành lá vàng ươm', 'Golden omelette with green onions', 30000, 3, 'uploads/menu/trung-chien.jpg', 1),
('Khoai lang chiên', 'Sweet Potato Fries', 'Khoai lang chiên giòn rụm', 'Crispy sweet potato fries', 35000, 3, 'uploads/menu/khoai-lang.jpg', 1),
('Canh bí đỏ tôm khô', 'Pumpkin Soup', 'Canh bí đỏ nấu tôm khô ngọt thanh', 'Pumpkin soup with dried shrimp', 40000, 3, 'uploads/menu/canh-bi.jpg', 1),
('Đậu que xào tỏi', 'Garlic Green Beans', 'Đậu que xào tỏi giòn ngon', 'Garlic stir-fried green beans', 35000, 3, 'uploads/menu/dau-que.jpg', 1),
('Cơm trắng', 'Steamed Rice', 'Cơm trắng thơm dẻo', 'Fragrant steamed jasmine rice', 10000, 3, 'uploads/menu/com-trang.jpg', 1),
('Mì xào rau củ', 'Vegetable Stir-fried Noodles', 'Mì xào với các loại rau củ tươi', 'Stir-fried noodles with fresh vegetables', 45000, 3, 'uploads/menu/mi-xao.jpg', 1);

-- =============================================
-- TRÁNG MIỆNG (category_id = 4) - 10 món
-- =============================================
INSERT INTO menu_items (name, name_en, description, description_en, price, category_id, image, is_available) VALUES
('Chè ba màu', 'Three Color Dessert', 'Chè ba màu truyền thống mát lạnh', 'Traditional three color sweet soup', 30000, 4, 'uploads/menu/che-ba-mau.jpg', 1),
('Bánh flan', 'Caramel Flan', 'Bánh flan mềm mịn vị caramel', 'Silky smooth caramel custard', 25000, 4, 'uploads/menu/banh-flan.jpg', 1),
('Chè đậu xanh', 'Mung Bean Sweet Soup', 'Chè đậu xanh nước cốt dừa béo ngậy', 'Mung bean dessert with coconut milk', 25000, 4, 'uploads/menu/che-dau-xanh.jpg', 1),
('Rau câu dừa', 'Coconut Jelly', 'Rau câu dừa mát lạnh thơm ngon', 'Refreshing coconut jelly', 25000, 4, 'uploads/menu/rau-cau.jpg', 1),
('Bánh chuối nướng', 'Baked Banana Cake', 'Bánh chuối nướng thơm lừng', 'Fragrant baked banana cake', 30000, 4, 'uploads/menu/banh-chuoi.jpg', 1),
('Kem dừa', 'Coconut Ice Cream', 'Kem dừa tươi mát béo ngậy', 'Fresh creamy coconut ice cream', 35000, 4, 'uploads/menu/kem-dua.jpg', 1),
('Chè thái', 'Thai Style Dessert', 'Chè thái với trái cây nhiệt đới', 'Thai style dessert with tropical fruits', 35000, 4, 'uploads/menu/che-thai.jpg', 1),
('Xôi xoài', 'Mango Sticky Rice', 'Xôi xoài Thái Lan ngọt ngào', 'Thai mango sticky rice', 40000, 4, 'uploads/menu/xoi-xoai.jpg', 1),
('Bánh đậu xanh', 'Mung Bean Cake', 'Bánh đậu xanh Hải Dương truyền thống', 'Traditional Hai Duong mung bean cake', 20000, 4, 'uploads/menu/banh-dau-xanh.jpg', 1),
('Sương sáo hạt lựu', 'Grass Jelly with Pomegranate', 'Sương sáo mát lạnh với hạt lựu', 'Cool grass jelly with pomegranate seeds', 30000, 4, 'uploads/menu/suong-sao.jpg', 1);

-- =============================================
-- ĐỒ UỐNG (category_id = 5) - 10 món
-- =============================================
INSERT INTO menu_items (name, name_en, description, description_en, price, category_id, image, is_available) VALUES
('Cà phê sữa đá', 'Vietnamese Iced Coffee', 'Cà phê phin truyền thống với sữa đặc', 'Traditional drip coffee with condensed milk', 25000, 5, 'uploads/menu/ca-phe-sua.jpg', 1),
('Trà đá chanh', 'Iced Lemon Tea', 'Trà đá chanh tươi mát', 'Refreshing iced lemon tea', 15000, 5, 'uploads/menu/tra-chanh.jpg', 1),
('Nước dừa tươi', 'Fresh Coconut Water', 'Nước dừa xiêm tươi mát', 'Fresh young coconut water', 30000, 5, 'uploads/menu/nuoc-dua.jpg', 1),
('Sinh tố bơ', 'Avocado Smoothie', 'Sinh tố bơ béo ngậy', 'Creamy avocado smoothie', 35000, 5, 'uploads/menu/sinh-to-bo.jpg', 1),
('Nước mía', 'Sugarcane Juice', 'Nước mía tươi nguyên chất', 'Fresh pressed sugarcane juice', 20000, 5, 'uploads/menu/nuoc-mia.jpg', 1),
('Trà sen', 'Lotus Tea', 'Trà sen Tây Hồ thơm ngát', 'Fragrant West Lake lotus tea', 30000, 5, 'uploads/menu/tra-sen.jpg', 1),
('Sữa đậu nành', 'Soy Milk', 'Sữa đậu nành nóng/lạnh', 'Hot or cold soy milk', 20000, 5, 'uploads/menu/sua-dau-nanh.jpg', 1),
('Nước ép cam', 'Fresh Orange Juice', 'Nước ép cam tươi nguyên chất', 'Freshly squeezed orange juice', 35000, 5, 'uploads/menu/nuoc-cam.jpg', 1),
('Trà sữa trân châu', 'Bubble Milk Tea', 'Trà sữa với trân châu đen dẻo', 'Milk tea with chewy tapioca pearls', 35000, 5, 'uploads/menu/tra-sua.jpg', 1),
('Chanh muối', 'Salted Lemonade', 'Chanh muối giải khát', 'Refreshing salted lemonade', 20000, 5, 'uploads/menu/chanh-muoi.jpg', 1);

-- Bật lại kiểm tra foreign key
SET FOREIGN_KEY_CHECKS = 1;

-- Hoàn tất!
SELECT 'Da tao thanh cong 50 mon an!' as Result;
