-- Add English language columns to categories table
ALTER TABLE categories 
ADD COLUMN name_en VARCHAR(100) AFTER name;

-- Update categories with English translations
UPDATE categories SET 
    name_en = CASE 
        WHEN name = 'Món chính' THEN 'Main Dishes'
        WHEN name = 'Món phụ' THEN 'Side Dishes'
        WHEN name = 'Đồ uống' THEN 'Beverages'
        WHEN name = 'Tráng miệng' THEN 'Desserts'
        ELSE name
    END;

-- Add English language columns to menu_items table
ALTER TABLE menu_items 
ADD COLUMN name_en VARCHAR(200) AFTER name,
ADD COLUMN description_en TEXT AFTER description;

-- Update existing data with English translations
UPDATE menu_items SET 
    name_en = CASE 
        WHEN name = 'Phở bò đặc biệt' THEN 'Special Beef Pho'
        WHEN name = 'Bún chả Hà Nội' THEN 'Hanoi Grilled Pork with Noodles'
        WHEN name = 'Cơm tấm sườn' THEN 'Broken Rice with Grilled Pork Chop'
        WHEN name = 'Bánh mì thịt' THEN 'Vietnamese Pork Sandwich'
        WHEN name = 'Gỏi cuốn' THEN 'Fresh Spring Rolls'
        WHEN name = 'Nem rán' THEN 'Fried Spring Rolls'
        WHEN name = 'Cà phê sữa đá' THEN 'Vietnamese Iced Coffee'
        WHEN name = 'Trà đá' THEN 'Iced Tea'
        ELSE name
    END,
    description_en = CASE 
        WHEN description = 'Nước dùng hầm xương 12 tiếng' THEN 'Broth simmered for 12 hours'
        WHEN description = 'Thịt nướng than hoa thơm ngon' THEN 'Delicious charcoal grilled pork'
        WHEN description = 'Sườn nướng mềm ngọt đặc trưng' THEN 'Signature tender grilled pork chop'
        ELSE description
    END
WHERE name_en IS NULL;
