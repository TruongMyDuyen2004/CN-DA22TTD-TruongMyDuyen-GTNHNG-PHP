-- Database đã tồn tại, chỉ cần thêm/cập nhật dữ liệu

-- Bảng categories (danh mục món ăn)
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    name_en VARCHAR(100),
    description TEXT,
    description_en TEXT,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng menu_items (món ăn)
CREATE TABLE IF NOT EXISTS menu_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    name_en VARCHAR(200),
    description TEXT,
    description_en TEXT,
    price DECIMAL(10,2) NOT NULL,
    category_id INT,
    image VARCHAR(255),
    is_available TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng admins
CREATE TABLE IF NOT EXISTS admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    full_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng promotions (khuyến mãi)
CREATE TABLE IF NOT EXISTS promotions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    discount_type ENUM('percentage', 'fixed') DEFAULT 'percentage',
    discount_value DECIMAL(10,2) NOT NULL,
    min_order_amount DECIMAL(10,2) DEFAULT 0,
    max_discount_amount DECIMAL(10,2) DEFAULT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    usage_limit INT DEFAULT NULL,
    used_count INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_active (is_active),
    INDEX idx_dates (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thêm dữ liệu mẫu categories
INSERT INTO categories (name, name_en, display_order) VALUES
('Khai vị', 'Appetizers', 1),
('Món chính', 'Main Courses', 2),
('Món phụ', 'Side Dishes', 3),
('Tráng miệng', 'Desserts', 4),
('Đồ uống', 'Beverages', 5)
ON DUPLICATE KEY UPDATE name=name;

-- Thêm dữ liệu mẫu menu_items
INSERT INTO menu_items (name, name_en, description, description_en, price, category_id, is_available) VALUES
('Gỏi cuốn tôm thịt', 'Fresh Spring Rolls', 'Gỏi cuốn tươi với tôm, thịt và rau sống', 'Fresh spring rolls with shrimp, pork and vegetables', 45000, 1, 1),
('Chả giò giòn', 'Crispy Spring Rolls', 'Chả giò chiên giòn nhân thịt và rau củ', 'Crispy fried spring rolls with meat and vegetables', 50000, 1, 1),
('Phở bò đặc biệt', 'Special Beef Pho', 'Phở bò truyền thống với đầy đủ các loại thịt', 'Traditional beef pho with full meat options', 85000, 2, 1),
('Bún chả Hà Nội', 'Hanoi Grilled Pork with Noodles', 'Bún chả nướng kiểu Hà Nội', 'Hanoi-style grilled pork with vermicelli', 75000, 2, 1),
('Cơm tấm sườn nướng', 'Grilled Pork Chop Rice', 'Cơm tấm với sườn nướng, trứng và bì', 'Broken rice with grilled pork chop, egg and pork skin', 70000, 2, 1),
('Bánh mì thịt nướng', 'Grilled Pork Banh Mi', 'Bánh mì Việt Nam với thịt nướng', 'Vietnamese sandwich with grilled pork', 35000, 3, 1),
('Rau câu dừa', 'Coconut Jelly', 'Rau câu dừa mát lạnh', 'Refreshing coconut jelly', 25000, 4, 1),
('Chè ba màu', 'Three Color Dessert', 'Chè ba màu truyền thống', 'Traditional three color sweet soup', 30000, 4, 1),
('Cà phê sữa đá', 'Iced Milk Coffee', 'Cà phê sữa đá truyền thống Việt Nam', 'Traditional Vietnamese iced milk coffee', 30000, 5, 1),
('Trà đá chanh', 'Iced Lemon Tea', 'Trà đá chanh tươi mát', 'Fresh iced lemon tea', 20000, 5, 1)
ON DUPLICATE KEY UPDATE name=name;

-- Tạo admin mặc định (username: admin, password: admin123)
INSERT INTO admins (username, password, email, full_name) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@ngongallery.com', 'Administrator')
ON DUPLICATE KEY UPDATE username=username;

-- Thêm dữ liệu mẫu promotions
INSERT INTO promotions (code, name, description, discount_type, discount_value, min_order_amount, start_date, end_date, usage_limit) VALUES
('WELCOME20', 'Giảm 20% đơn đầu tiên', 'Giảm 20% cho đơn hàng đầu tiên, tối đa 50.000đ', 'percentage', 20, 100000, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 100),
('FREESHIP', 'Miễn phí giao hàng', 'Miễn phí giao hàng cho đơn từ 200.000đ', 'fixed', 30000, 200000, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 60 DAY), NULL),
('SUMMER50', 'Giảm 50K mùa hè', 'Giảm 50.000đ cho đơn hàng từ 300.000đ', 'fixed', 50000, 300000, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 90 DAY), 200)
ON DUPLICATE KEY UPDATE code=code;

-- Hiển thị thông tin
SELECT 'Database setup completed!' as Status;
SELECT COUNT(*) as 'Total Categories' FROM categories;
SELECT COUNT(*) as 'Total Menu Items' FROM menu_items;
SELECT COUNT(*) as 'Total Admins' FROM admins;
