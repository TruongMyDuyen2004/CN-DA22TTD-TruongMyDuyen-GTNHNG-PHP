-- Cập nhật schema cho các chức năng mới
-- Chạy file này để thêm các cột và bảng cần thiết

-- Thêm cột cho bảng categories nếu chưa có
ALTER TABLE categories ADD COLUMN IF NOT EXISTS name_en VARCHAR(255) DEFAULT NULL;
ALTER TABLE categories ADD COLUMN IF NOT EXISTS description TEXT DEFAULT NULL;
ALTER TABLE categories ADD COLUMN IF NOT EXISTS icon VARCHAR(50) DEFAULT '📋';
ALTER TABLE categories ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1;

-- Tạo bảng promotions nếu chưa có
CREATE TABLE IF NOT EXISTS promotions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    discount_type ENUM('percent', 'fixed') DEFAULT 'percent',
    discount_value DECIMAL(10,2) NOT NULL,
    min_order_value DECIMAL(10,2) DEFAULT 0,
    max_discount DECIMAL(10,2) DEFAULT NULL,
    usage_limit INT DEFAULT NULL,
    used_count INT DEFAULT 0,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Thêm một số khuyến mãi mẫu
INSERT INTO promotions (code, name, description, discount_type, discount_value, min_order_value, max_discount, usage_limit, start_date, end_date, is_active) VALUES
('WELCOME10', 'Chào mừng khách mới', 'Giảm 10% cho đơn hàng đầu tiên', 'percent', 10, 100000, 50000, 100, NOW(), DATE_ADD(NOW(), INTERVAL 3 MONTH), 1),
('FREESHIP', 'Miễn phí vận chuyển', 'Giảm 30.000đ phí vận chuyển', 'fixed', 30000, 200000, NULL, NULL, NOW(), DATE_ADD(NOW(), INTERVAL 1 MONTH), 1),
('SALE20', 'Giảm 20% cuối tuần', 'Áp dụng cho đơn từ 300.000đ', 'percent', 20, 300000, 100000, 50, NOW(), DATE_ADD(NOW(), INTERVAL 2 WEEK), 1)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Cập nhật icon cho các danh mục hiện có
UPDATE categories SET icon = '🥗' WHERE name LIKE '%Khai vị%' OR name LIKE '%Appetizer%';
UPDATE categories SET icon = '🍽️' WHERE name LIKE '%Món chính%' OR name LIKE '%Main%';
UPDATE categories SET icon = '🥘' WHERE name LIKE '%Món phụ%' OR name LIKE '%Side%';
UPDATE categories SET icon = '🍰' WHERE name LIKE '%Tráng miệng%' OR name LIKE '%Dessert%';
UPDATE categories SET icon = '🥤' WHERE name LIKE '%Đồ uống%' OR name LIKE '%Drink%' OR name LIKE '%Beverage%';


-- Thêm cột cho bảng orders để lưu thông tin khuyến mãi
ALTER TABLE orders ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(10,2) DEFAULT 0;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS promo_code VARCHAR(50) DEFAULT NULL;
