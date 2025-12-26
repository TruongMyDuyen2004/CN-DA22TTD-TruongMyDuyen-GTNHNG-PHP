-- Reset Menu Items - 100 món sang trọng cho nhà hàng Ngon Gallery
-- Mỗi danh mục 20 món

SET FOREIGN_KEY_CHECKS = 0;

-- Xóa dữ liệu các bảng liên quan trước
DELETE FROM cart;
DELETE FROM order_items;
DELETE FROM reviews;
DELETE FROM review_comments;
DELETE FROM review_likes;

-- Xóa dữ liệu menu_items
DELETE FROM menu_items;
ALTER TABLE menu_items AUTO_INCREMENT = 1;

-- Reset categories
DELETE FROM categories;
ALTER TABLE categories AUTO_INCREMENT = 1;

INSERT INTO categories (id, name, name_en, display_order) VALUES
(1, 'Khai vị', 'Appetizer', 1),
(2, 'Món chính', 'Main Course', 2),
(3, 'Món phụ', 'Side Dish', 3),
(4, 'Tráng miệng', 'Dessert', 4),
(5, 'Đồ uống', 'Drinks', 5);

-- =============================================
-- KHAI VỊ (category_id = 1) - 20 món
-- =============================================
INSERT INTO menu_items (category_id, name, name_en, description, description_en, price, image, is_available) VALUES
(1, 'Gỏi cuốn tôm thịt', 'Fresh Spring Rolls', 'Gỏi cuốn tươi với tôm, thịt heo, bún và rau thơm, chấm nước mắm chua ngọt', 'Fresh rice paper rolls with shrimp, pork, vermicelli and herbs', 85000, 'https://images.unsplash.com/photo-1544025162-d76694265947?w=400&q=80', 1),
(1, 'Chả giò Sài Gòn', 'Saigon Crispy Spring Rolls', 'Chả giò giòn rụm nhân thịt heo, mộc nhĩ, miến, chấm nước mắm pha', 'Crispy fried spring rolls with pork and wood ear mushroom', 95000, 'https://images.unsplash.com/photo-1548943487-a2e4e43b4853?w=400&q=80', 1),
(1, 'Bánh xèo miền Tây', 'Vietnamese Crispy Pancake', 'Bánh xèo giòn với nhân tôm, thịt, giá đỗ, ăn kèm rau sống', 'Crispy Vietnamese pancake with shrimp and pork', 120000, 'https://images.unsplash.com/photo-1565557623262-b51c2513a641?w=400&q=80', 1),
(1, 'Nem nướng Nha Trang', 'Nha Trang Grilled Pork Rolls', 'Nem nướng thơm lừng, cuốn bánh tráng với rau sống và bún', 'Grilled pork sausage wrapped in rice paper with herbs', 135000, 'https://images.unsplash.com/photo-1529692236671-f1f6cf9683ba?w=400&q=80', 1),
(1, 'Súp bào ngư vi cá', 'Abalone Shark Fin Soup', 'Súp bào ngư vi cá thượng hạng, bổ dưỡng và sang trọng', 'Premium abalone and shark fin soup', 450000, 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=400&q=80', 1),
(1, 'Gỏi ngó sen tôm thịt', 'Lotus Stem Salad', 'Gỏi ngó sen giòn ngọt với tôm, thịt và nước mắm chua ngọt', 'Crispy lotus stem salad with shrimp and pork', 125000, 'https://images.unsplash.com/photo-1512058564366-18510be2db19?w=400&q=80', 1),
(1, 'Bò cuốn lá lốt', 'Beef Wrapped in Betel Leaves', 'Bò cuốn lá lốt nướng than hoa thơm lừng', 'Grilled beef wrapped in wild betel leaves', 145000, 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=400&q=80', 1),
(1, 'Tôm chiên hoàng kim', 'Golden Fried Shrimp', 'Tôm sú chiên giòn phủ sốt trứng muối béo ngậy', 'Crispy fried shrimp with salted egg sauce', 185000, 'https://images.unsplash.com/photo-1565680018434-b513d5e5fd47?w=400&q=80', 1),
(1, 'Chạo tôm mía', 'Shrimp on Sugarcane', 'Tôm quết mịn cuốn mía nướng, chấm nước mắm tỏi ớt', 'Minced shrimp grilled on sugarcane sticks', 155000, 'https://images.unsplash.com/photo-1559847844-5315695dadae?w=400&q=80', 1),
(1, 'Súp cua hoàng đế', 'Imperial Crab Soup', 'Súp cua thịt trắng với trứng bắc thảo và nấm đông cô', 'Premium crab meat soup with century egg', 165000, 'https://images.unsplash.com/photo-1594756202469-9ff9799b2e4e?w=400&q=80', 1),
(1, 'Gỏi bưởi tôm khô', 'Pomelo Salad with Dried Shrimp', 'Gỏi bưởi chua ngọt với tôm khô và đậu phộng rang', 'Fresh pomelo salad with dried shrimp and peanuts', 115000, 'https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?w=400&q=80', 1),
(1, 'Hàu nướng mỡ hành', 'Grilled Oysters with Scallion Oil', 'Hàu tươi nướng mỡ hành thơm béo, ăn kèm đậu phộng', 'Fresh oysters grilled with scallion oil', 195000, 'https://images.unsplash.com/photo-1606731219412-2b5e7c601e37?w=400&q=80', 1),
(1, 'Bánh khọt Vũng Tàu', 'Mini Savory Pancakes', 'Bánh khọt giòn rụm với tôm tươi, ăn kèm rau sống', 'Crispy mini pancakes with fresh shrimp', 105000, 'https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?w=400&q=80', 1),
(1, 'Mực chiên giòn', 'Crispy Fried Calamari', 'Mực tươi chiên giòn, chấm sốt mayonnaise wasabi', 'Crispy fried squid with wasabi mayo', 165000, 'https://images.unsplash.com/photo-1599487488170-d11ec9c172f0?w=400&q=80', 1),
(1, 'Gỏi xoài cua lột', 'Green Mango Salad with Soft Shell Crab', 'Gỏi xoài xanh giòn với cua lột chiên giòn', 'Green mango salad with crispy soft shell crab', 225000, 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&q=80', 1),
(1, 'Phô mai que chiên', 'Fried Cheese Sticks', 'Phô mai mozzarella chiên giòn, chấm sốt cà chua', 'Crispy fried mozzarella sticks with marinara', 95000, 'https://images.unsplash.com/photo-1531749668029-2db88e4276c7?w=400&q=80', 1),
(1, 'Cánh gà chiên nước mắm', 'Fish Sauce Glazed Chicken Wings', 'Cánh gà chiên giòn rim nước mắm tỏi ớt', 'Crispy chicken wings glazed with fish sauce', 125000, 'https://images.unsplash.com/photo-1527477396000-e27163b481c2?w=400&q=80', 1),
(1, 'Salad trộn hải sản', 'Seafood Salad', 'Salad tươi với tôm, mực, sò điệp và sốt chanh dây', 'Fresh salad with shrimp, squid and scallops', 175000, 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=400&q=80', 1),
(1, 'Đậu hũ chiên sả ớt', 'Lemongrass Chili Tofu', 'Đậu hũ chiên giòn xào sả ớt thơm lừng', 'Crispy tofu stir-fried with lemongrass and chili', 85000, 'https://images.unsplash.com/photo-1546069901-d5bfd2cbfb1f?w=400&q=80', 1),
(1, 'Bò tái chanh', 'Beef Carpaccio Vietnamese Style', 'Bò tái mềm ướp chanh, hành tây và rau răm', 'Thinly sliced beef cured in lime juice', 165000, 'https://images.unsplash.com/photo-1588168333986-5078d3ae3976?w=400&q=80', 1);


-- =============================================
-- MÓN CHÍNH (category_id = 2) - 20 món
-- =============================================
INSERT INTO menu_items (category_id, name, name_en, description, description_en, price, image, is_available) VALUES
(2, 'Phở bò truyền thống', 'Traditional Beef Pho', 'Phở bò với nước dùng hầm xương 12 tiếng, thịt bò tái chín', 'Traditional beef pho with 12-hour bone broth', 95000, 'https://images.unsplash.com/photo-1582878826629-29b7ad1cdc43?w=400&q=80', 1),
(2, 'Bún chả Hà Nội', 'Hanoi Grilled Pork with Noodles', 'Bún chả thơm ngon với thịt nướng than hoa và nước mắm pha', 'Grilled pork patties with rice noodles and dipping sauce', 105000, 'https://images.unsplash.com/photo-1569058242567-93de6f36f8e6?w=400&q=80', 1),
(2, 'Cơm tấm sườn bì chả', 'Broken Rice with Grilled Pork', 'Cơm tấm Sài Gòn với sườn nướng, bì, chả và trứng ốp la', 'Broken rice with grilled pork chop, skin and egg', 115000, 'https://images.unsplash.com/photo-1455619452474-d2be8b1e70cd?w=400&q=80', 1),
(2, 'Bò kho bánh mì', 'Vietnamese Beef Stew', 'Bò kho mềm thơm với cà rốt, ăn kèm bánh mì nóng giòn', 'Aromatic beef stew served with crusty baguette', 125000, 'https://images.unsplash.com/photo-1547592180-85f173990554?w=400&q=80', 1),
(2, 'Cá kho tộ', 'Caramelized Fish in Clay Pot', 'Cá basa kho tộ đậm đà với nước màu và tiêu', 'Caramelized catfish in traditional clay pot', 145000, 'https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=400&q=80', 1),
(2, 'Gà nướng muối ớt', 'Salt and Chili Roasted Chicken', 'Gà ta nướng muối ớt giòn da, thơm lừng', 'Free-range chicken roasted with salt and chili', 285000, 'https://images.unsplash.com/photo-1598103442097-8b74394b95c6?w=400&q=80', 1),
(2, 'Tôm hùm nướng bơ tỏi', 'Grilled Lobster with Garlic Butter', 'Tôm hùm Alaska nướng bơ tỏi thơm béo', 'Alaska lobster grilled with garlic butter', 850000, 'https://images.unsplash.com/photo-1553247407-23251ce81f59?w=400&q=80', 1),
(2, 'Bò wagyu nướng đá', 'Wagyu Beef Stone Grill', 'Bò wagyu A5 Nhật Bản nướng trên đá nóng', 'Premium A5 Japanese wagyu grilled on hot stone', 750000, 'https://images.unsplash.com/photo-1546833998-877b37c2e5c6?w=400&q=80', 1),
(2, 'Vịt quay Bắc Kinh', 'Peking Duck', 'Vịt quay da giòn kiểu Bắc Kinh, ăn kèm bánh tráng và hành', 'Crispy Peking duck with pancakes and scallions', 650000, 'https://images.unsplash.com/photo-1518492104633-130d0cc84637?w=400&q=80', 1),
(2, 'Cua rang me', 'Tamarind Crab', 'Cua biển rang me chua ngọt đậm đà', 'Fresh crab stir-fried with tamarind sauce', 450000, 'https://images.unsplash.com/photo-1559737558-2f5a35f4523b?w=400&q=80', 1),
(2, 'Lẩu thái hải sản', 'Thai Seafood Hot Pot', 'Lẩu thái chua cay với tôm, mực, nghêu và cá', 'Spicy Thai hot pot with assorted seafood', 385000, 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?w=400&q=80', 1),
(2, 'Sườn non xào chua ngọt', 'Sweet and Sour Pork Ribs', 'Sườn non chiên giòn xào sốt chua ngọt với dứa', 'Crispy pork ribs in sweet and sour sauce', 175000, 'https://images.unsplash.com/photo-1544025162-d76694265947?w=400&q=80', 1),
(2, 'Cá chẽm hấp Hồng Kông', 'Hong Kong Style Steamed Sea Bass', 'Cá chẽm tươi hấp xì dầu kiểu Hồng Kông', 'Fresh sea bass steamed with soy sauce', 385000, 'https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?w=400&q=80', 1),
(2, 'Bún bò Huế', 'Hue Style Beef Noodle Soup', 'Bún bò Huế cay nồng với giò heo và chả cua', 'Spicy Hue beef noodle soup with pork knuckle', 115000, 'https://images.unsplash.com/photo-1576577445504-6af96477db52?w=400&q=80', 1),
(2, 'Mì Quảng Đà Nẵng', 'Quang Noodles', 'Mì Quảng với tôm, thịt, trứng cút và bánh tráng', 'Quang noodles with shrimp, pork and quail eggs', 105000, 'https://images.unsplash.com/photo-1555126634-323283e090fa?w=400&q=80', 1),
(2, 'Cơm chiên hải sản', 'Seafood Fried Rice', 'Cơm chiên với tôm, mực, sò điệp và rau củ', 'Fried rice with shrimp, squid and scallops', 145000, 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=400&q=80', 1),
(2, 'Bê chao Mộc Châu', 'Moc Chau Veal Stir-fry', 'Bê non Mộc Châu xào lăn với sả ớt', 'Young veal stir-fried with lemongrass', 225000, 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=400&q=80', 1),
(2, 'Ốc hương rang muối', 'Salt Roasted Sea Snails', 'Ốc hương tươi rang muối ớt thơm lừng', 'Fresh sea snails roasted with salt and chili', 285000, 'https://images.unsplash.com/photo-1559847844-5315695dadae?w=400&q=80', 1),
(2, 'Gà hầm thuốc bắc', 'Herbal Chicken Soup', 'Gà ta hầm với thuốc bắc bổ dưỡng', 'Free-range chicken stewed with Chinese herbs', 325000, 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=400&q=80', 1),
(2, 'Cơm niêu Singapore', 'Singapore Clay Pot Rice', 'Cơm niêu với lạp xưởng, trứng muối và thịt xá xíu', 'Clay pot rice with Chinese sausage and char siu', 155000, 'https://images.unsplash.com/photo-1512058564366-18510be2db19?w=400&q=80', 1);

-- =============================================
-- MÓN PHỤ (category_id = 3) - 20 món
-- =============================================
INSERT INTO menu_items (category_id, name, name_en, description, description_en, price, image, is_available) VALUES
(3, 'Rau muống xào tỏi', 'Stir-fried Water Spinach', 'Rau muống xào tỏi giòn ngọt, đậm đà', 'Water spinach stir-fried with garlic', 65000, 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=400&q=80', 1),
(3, 'Đậu que xào thịt bò', 'Green Beans with Beef', 'Đậu que xào thịt bò mềm thơm', 'Green beans stir-fried with tender beef', 95000, 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&q=80', 1),
(3, 'Cải thìa xào nấm', 'Bok Choy with Mushrooms', 'Cải thìa xào nấm đông cô và nấm kim châm', 'Bok choy with shiitake and enoki mushrooms', 85000, 'https://images.unsplash.com/photo-1518779578993-ec3579fee39f?w=400&q=80', 1),
(3, 'Khoai tây chiên truffle', 'Truffle Fries', 'Khoai tây chiên giòn với dầu truffle và parmesan', 'Crispy fries with truffle oil and parmesan', 125000, 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?w=400&q=80', 1),
(3, 'Măng tây xào tỏi', 'Garlic Asparagus', 'Măng tây xào tỏi giòn ngọt tự nhiên', 'Fresh asparagus sautéed with garlic', 115000, 'https://images.unsplash.com/photo-1515516969-d4008cc6241a?w=400&q=80', 1),
(3, 'Bông cải xanh xào bò', 'Broccoli with Beef', 'Bông cải xanh xào thịt bò sốt dầu hào', 'Broccoli stir-fried with beef in oyster sauce', 105000, 'https://images.unsplash.com/photo-1459411552884-841db9b3cc2a?w=400&q=80', 1),
(3, 'Cơm trắng', 'Steamed Rice', 'Cơm gạo ST25 thơm dẻo', 'Premium ST25 steamed rice', 25000, 'https://images.unsplash.com/photo-1516684732162-798a0062be99?w=400&q=80', 1),
(3, 'Cơm chiên tỏi', 'Garlic Fried Rice', 'Cơm chiên tỏi thơm lừng với hành phi', 'Fragrant garlic fried rice with crispy shallots', 55000, 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=400&q=80', 1),
(3, 'Canh chua cá lóc', 'Sour Fish Soup', 'Canh chua miền Tây với cá lóc và rau thơm', 'Southern Vietnamese sour soup with snakehead fish', 95000, 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=400&q=80', 1),
(3, 'Canh khổ qua nhồi thịt', 'Stuffed Bitter Melon Soup', 'Canh khổ qua nhồi thịt heo xay thanh mát', 'Bitter melon stuffed with minced pork in clear broth', 85000, 'https://images.unsplash.com/photo-1594756202469-9ff9799b2e4e?w=400&q=80', 1),
(3, 'Nấm xào thập cẩm', 'Mixed Mushroom Stir-fry', 'Nấm đông cô, nấm bào ngư, nấm kim châm xào', 'Assorted mushrooms stir-fried with vegetables', 95000, 'https://images.unsplash.com/photo-1504545102780-26774c1bb073?w=400&q=80', 1),
(3, 'Đậu hũ sốt cà', 'Tofu in Tomato Sauce', 'Đậu hũ chiên sốt cà chua thơm ngon', 'Fried tofu in savory tomato sauce', 75000, 'https://images.unsplash.com/photo-1546069901-d5bfd2cbfb1f?w=400&q=80', 1),
(3, 'Salad rau củ', 'Garden Salad', 'Salad tươi với rau xà lách, cà chua và dưa leo', 'Fresh garden salad with house dressing', 65000, 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=400&q=80', 1),
(3, 'Kim chi Hàn Quốc', 'Korean Kimchi', 'Kim chi cải thảo lên men truyền thống', 'Traditional fermented napa cabbage kimchi', 45000, 'https://images.unsplash.com/photo-1583224994076-e3c28a5c8d8e?w=400&q=80', 1),
(3, 'Dưa chua nhà làm', 'Homemade Pickles', 'Dưa chua cà rốt, củ cải muối chua ngọt', 'House-made pickled carrots and daikon', 35000, 'https://images.unsplash.com/photo-1589135233689-0e6f9c5c5f0e?w=400&q=80', 1),
(3, 'Bánh mì nướng bơ tỏi', 'Garlic Bread', 'Bánh mì nướng giòn với bơ tỏi thơm lừng', 'Toasted bread with garlic butter', 45000, 'https://images.unsplash.com/photo-1549931319-a545dcf3bc73?w=400&q=80', 1),
(3, 'Khoai lang nướng', 'Roasted Sweet Potato', 'Khoai lang Đà Lạt nướng mật ong', 'Dalat sweet potato roasted with honey', 55000, 'https://images.unsplash.com/photo-1596097635121-14b63a7a0c19?w=400&q=80', 1),
(3, 'Bắp nướng mỡ hành', 'Grilled Corn with Scallion Oil', 'Bắp nếp nướng mỡ hành thơm béo', 'Grilled sticky corn with scallion oil', 45000, 'https://images.unsplash.com/photo-1551754655-cd27e38d2076?w=400&q=80', 1),
(3, 'Xôi gấc', 'Red Sticky Rice', 'Xôi gấc đỏ thơm dẻo với dừa nạo', 'Red sticky rice with gac fruit and coconut', 55000, 'https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?w=400&q=80', 1),
(3, 'Mì xào giòn', 'Crispy Noodles', 'Mì xào giòn với rau củ và sốt dầu hào', 'Crispy noodles with vegetables in oyster sauce', 85000, 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?w=400&q=80', 1);


-- =============================================
-- TRÁNG MIỆNG (category_id = 4) - 20 món
-- =============================================
INSERT INTO menu_items (category_id, name, name_en, description, description_en, price, image, is_available) VALUES
(4, 'Chè khúc bạch', 'Almond Jelly Dessert', 'Chè khúc bạch mát lạnh với nhãn và vải', 'Chilled almond jelly with longan and lychee', 55000, 'https://images.unsplash.com/photo-1551024506-0bccd828d307?w=400&q=80', 1),
(4, 'Bánh flan caramel', 'Creme Caramel', 'Bánh flan mềm mịn với caramel đắng nhẹ', 'Silky smooth flan with caramel sauce', 45000, 'https://images.unsplash.com/photo-1528975604071-b4dc52a2d18c?w=400&q=80', 1),
(4, 'Chè đậu xanh', 'Mung Bean Sweet Soup', 'Chè đậu xanh đánh nhuyễn với nước cốt dừa', 'Smooth mung bean dessert with coconut milk', 45000, 'https://images.unsplash.com/photo-1563805042-7684c019e1cb?w=400&q=80', 1),
(4, 'Kem dừa trái dừa', 'Coconut Ice Cream in Shell', 'Kem dừa tươi phục vụ trong trái dừa', 'Fresh coconut ice cream served in coconut shell', 85000, 'https://images.unsplash.com/photo-1570197788417-0e82375c9371?w=400&q=80', 1),
(4, 'Bánh chuối nướng', 'Baked Banana Cake', 'Bánh chuối nướng thơm lừng với nước cốt dừa', 'Baked banana cake with coconut cream', 55000, 'https://images.unsplash.com/photo-1571115177098-24ec42ed204d?w=400&q=80', 1),
(4, 'Xoài dầm nếp', 'Mango Sticky Rice', 'Xoài chín dầm với xôi nếp và nước cốt dừa', 'Fresh mango with sticky rice and coconut cream', 75000, 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&q=80', 1),
(4, 'Sữa chua nếp cẩm', 'Purple Rice Yogurt', 'Sữa chua mịn với nếp cẩm và đậu phộng', 'Creamy yogurt with purple sticky rice', 55000, 'https://images.unsplash.com/photo-1488477181946-6428a0291777?w=400&q=80', 1),
(4, 'Chè ba màu', 'Three Color Dessert', 'Chè ba màu với đậu xanh, đậu đỏ và thạch', 'Three-color dessert with beans and jelly', 50000, 'https://images.unsplash.com/photo-1551024506-0bccd828d307?w=400&q=80', 1),
(4, 'Tiramisu', 'Tiramisu', 'Tiramisu Y với cà phê espresso và mascarpone', 'Italian tiramisu with espresso and mascarpone', 85000, 'https://images.unsplash.com/photo-1571877227200-a0d98ea607e9?w=400&q=80', 1),
(4, 'Bánh chocolate lava', 'Chocolate Lava Cake', 'Bánh chocolate nóng với nhân chảy mềm mịn', 'Warm chocolate cake with molten center', 95000, 'https://images.unsplash.com/photo-1624353365286-3f8d62daad51?w=400&q=80', 1),
(4, 'Panna cotta', 'Panna Cotta', 'Panna cotta Y với sốt dâu tươi', 'Italian panna cotta with fresh berry sauce', 75000, 'https://images.unsplash.com/photo-1488477181946-6428a0291777?w=400&q=80', 1),
(4, 'Trái cây tươi theo mùa', 'Seasonal Fresh Fruits', 'Đĩa trái cây tươi theo mùa', 'Assorted seasonal fresh fruits', 95000, 'https://images.unsplash.com/photo-1619566636858-adf3ef46400b?w=400&q=80', 1),
(4, 'Kem gelato Y', 'Italian Gelato', 'Kem gelato Y với 3 vị tự chọn', 'Authentic Italian gelato, choice of 3 flavors', 85000, 'https://images.unsplash.com/photo-1567206563064-6f60f40a2b57?w=400&q=80', 1),
(4, 'Bánh su kem', 'Cream Puffs', 'Bánh su kem giòn với kem vanilla béo ngậy', 'Crispy choux pastry with vanilla cream', 65000, 'https://images.unsplash.com/photo-1612203985729-70726954388c?w=400&q=80', 1),
(4, 'Mousse chanh dây', 'Passion Fruit Mousse', 'Mousse chanh dây chua ngọt thanh mát', 'Light and tangy passion fruit mousse', 75000, 'https://images.unsplash.com/photo-1541783245831-57d6fb0926d3?w=400&q=80', 1),
(4, 'Chè thái', 'Thai Dessert', 'Chè thái với nước cốt dừa và trái cây nhiệt đới', 'Thai dessert with coconut milk and tropical fruits', 55000, 'https://images.unsplash.com/photo-1563805042-7684c019e1cb?w=400&q=80', 1),
(4, 'Bánh crepe sầu riêng', 'Durian Crepe', 'Bánh crepe mỏng với nhân sầu riêng Ri6', 'Thin crepe with premium Ri6 durian filling', 95000, 'https://images.unsplash.com/photo-1519676867240-f03562e64548?w=400&q=80', 1),
(4, 'Tàu hũ đường phèn', 'Silken Tofu Dessert', 'Tàu hũ mềm mịn với đường phèn và gừng', 'Silken tofu in ginger syrup', 40000, 'https://images.unsplash.com/photo-1546069901-d5bfd2cbfb1f?w=400&q=80', 1),
(4, 'Bánh bông lan trứng muối', 'Salted Egg Sponge Cake', 'Bánh bông lan mềm xốp với nhân trứng muối', 'Fluffy sponge cake with salted egg filling', 75000, 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=400&q=80', 1),
(4, 'Chè đậu đỏ', 'Red Bean Sweet Soup', 'Chè đậu đỏ nấu với nước cốt dừa béo ngậy', 'Red bean dessert with rich coconut milk', 45000, 'https://images.unsplash.com/photo-1551024506-0bccd828d307?w=400&q=80', 1);

-- =============================================
-- ĐỒ UỐNG (category_id = 5) - 20 món
-- =============================================
INSERT INTO menu_items (category_id, name, name_en, description, description_en, price, image, is_available) VALUES
(5, 'Cà phê sữa đá', 'Vietnamese Iced Coffee', 'Cà phê phin truyền thống với sữa đặc', 'Traditional drip coffee with condensed milk', 35000, 'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?w=400&q=80', 1),
(5, 'Trà đá chanh', 'Iced Lemon Tea', 'Trà đá chanh tươi mát lạnh', 'Refreshing iced tea with fresh lemon', 30000, 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?w=400&q=80', 1),
(5, 'Nước dừa xiêm tươi', 'Fresh Young Coconut', 'Nước dừa xiêm tươi mát ngọt tự nhiên', 'Fresh young coconut water', 45000, 'https://images.unsplash.com/photo-1544252890-c3e95e867d73?w=400&q=80', 1),
(5, 'Sinh tố bơ', 'Avocado Smoothie', 'Sinh tố bơ béo ngậy với sữa đặc', 'Creamy avocado smoothie with condensed milk', 55000, 'https://images.unsplash.com/photo-1638176066666-ffb2f013c7dd?w=400&q=80', 1),
(5, 'Nước ép cam tươi', 'Fresh Orange Juice', 'Nước ép cam tươi 100% nguyên chất', '100% freshly squeezed orange juice', 50000, 'https://images.unsplash.com/photo-1534353473418-4cfa6c56fd38?w=400&q=80', 1),
(5, 'Trà sen vàng', 'Golden Lotus Tea', 'Trà sen Tây Hồ thơm ngát', 'Fragrant West Lake lotus tea', 65000, 'https://images.unsplash.com/photo-1544787219-7f47ccb76574?w=400&q=80', 1),
(5, 'Sữa chua đánh đá', 'Iced Yogurt Drink', 'Sữa chua đánh đá mát lạnh', 'Refreshing iced yogurt drink', 45000, 'https://images.unsplash.com/photo-1488477181946-6428a0291777?w=400&q=80', 1),
(5, 'Nước mía lau', 'Fresh Sugarcane Juice', 'Nước mía lau tươi mát ngọt thanh', 'Freshly pressed sugarcane juice', 35000, 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&q=80', 1),
(5, 'Trà đào cam sả', 'Peach Lemongrass Tea', 'Trà đào với cam và sả thơm mát', 'Peach tea with orange and lemongrass', 55000, 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?w=400&q=80', 1),
(5, 'Sinh tố xoài', 'Mango Smoothie', 'Sinh tố xoài Cát Hòa Lộc ngọt lịm', 'Sweet Cat Hoa Loc mango smoothie', 55000, 'https://images.unsplash.com/photo-1546173159-315724a31696?w=400&q=80', 1),
(5, 'Matcha latte', 'Matcha Latte', 'Matcha Nhật Bản với sữa tươi', 'Japanese matcha with fresh milk', 65000, 'https://images.unsplash.com/photo-1515823064-d6e0c04616a7?w=400&q=80', 1),
(5, 'Chanh muối', 'Salted Lemonade', 'Chanh muối giải khát thanh nhiệt', 'Refreshing salted lemonade', 35000, 'https://images.unsplash.com/photo-1621263764928-df1444c5e859?w=400&q=80', 1),
(5, 'Trà sữa trân châu', 'Bubble Milk Tea', 'Trà sữa với trân châu đường đen', 'Milk tea with brown sugar boba', 55000, 'https://images.unsplash.com/photo-1558857563-b371033873b8?w=400&q=80', 1),
(5, 'Nước ép dưa hấu', 'Watermelon Juice', 'Nước ép dưa hấu tươi mát', 'Fresh watermelon juice', 45000, 'https://images.unsplash.com/photo-1527661591475-527312dd65f5?w=400&q=80', 1),
(5, 'Cà phê muối', 'Salted Coffee', 'Cà phê muối Huế đặc biệt', 'Special Hue-style salted coffee', 45000, 'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?w=400&q=80', 1),
(5, 'Rượu vang đỏ', 'Red Wine', 'Rượu vang đỏ Chile nhập khẩu (ly)', 'Imported Chilean red wine (glass)', 125000, 'https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?w=400&q=80', 1),
(5, 'Bia Sài Gòn', 'Saigon Beer', 'Bia Sài Gòn Special lon 330ml', 'Saigon Special beer 330ml can', 35000, 'https://images.unsplash.com/photo-1608270586620-248524c67de9?w=400&q=80', 1),
(5, 'Mojito', 'Mojito', 'Cocktail mojito với bạc hà và chanh tươi', 'Classic mojito with fresh mint and lime', 95000, 'https://images.unsplash.com/photo-1551538827-9c037cb4f32a?w=400&q=80', 1),
(5, 'Nước suối Evian', 'Evian Water', 'Nước khoáng Evian 500ml', 'Evian mineral water 500ml', 55000, 'https://images.unsplash.com/photo-1548839140-29a749e1cf4d?w=400&q=80', 1),
(5, 'Soda chanh dây', 'Passion Fruit Soda', 'Soda chanh dây tươi mát sảng khoái', 'Refreshing passion fruit soda', 45000, 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?w=400&q=80', 1);

SET FOREIGN_KEY_CHECKS = 1;

-- Kiểm tra kết quả
SELECT c.name as 'Danh muc', COUNT(m.id) as 'So mon' 
FROM categories c 
LEFT JOIN menu_items m ON c.id = m.category_id 
GROUP BY c.id, c.name 
ORDER BY c.display_order;
