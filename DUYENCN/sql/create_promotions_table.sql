-- =====================================================
-- TẠO BẢNG MÃ KHUYẾN MÃI (PROMOTIONS)
-- =====================================================

-- Tạo bảng promotions
CREATE TABLE IF NOT EXISTS promotions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL COMMENT 'Mã khuyến mãi (VD: SALE20, FREESHIP)',
    name VARCHAR(255) NOT NULL COMMENT 'Tên chương trình khuyến mãi',
    description TEXT COMMENT 'Mô tả chi tiết',
    discount_type ENUM('percent', 'fixed') DEFAULT 'percent' COMMENT 'Loại giảm: percent=%, fixed=VNĐ',
    discount_value DECIMAL(10,2) NOT NULL COMMENT 'Giá trị giảm (20 = 20% hoặc 50000 = 50.000đ)',
    min_order_value DECIMAL(10,2) DEFAULT 0 COMMENT 'Đơn hàng tối thiểu để áp dụng',
    max_discount DECIMAL(10,2) DEFAULT NULL COMMENT 'Giảm tối đa (chỉ áp dụng cho loại percent)',
    usage_limit INT DEFAULT NULL COMMENT 'Giới hạn số lần sử dụng (NULL = không giới hạn)',
    used_count INT DEFAULT 0 COMMENT 'Số lần đã sử dụng',
    start_date DATETIME NOT NULL COMMENT 'Ngày bắt đầu',
    end_date DATETIME NOT NULL COMMENT 'Ngày kết thúc',
    is_active TINYINT(1) DEFAULT 1 COMMENT '1=Hoạt động, 0=Tắt',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- THÊM MÃ KHUYẾN MÃI MẪU
-- =====================================================

-- Mã giảm 10% cho khách mới
INSERT INTO promotions (code, name, description, discount_type, discount_value, min_order_value, max_discount, usage_limit, start_date, end_date, is_active) VALUES
('NEWUSER10', 'Giảm 10% cho khách mới', 'Áp dụng cho khách hàng đặt đơn lần đầu', 'percent', 10, 100000, 50000, 100, NOW(), DATE_ADD(NOW(), INTERVAL 3 MONTH), 1);

-- Mã giảm 20% đơn từ 300k
INSERT INTO promotions (code, name, description, discount_type, discount_value, min_order_value, max_discount, usage_limit, start_date, end_date, is_active) VALUES
('SALE20', 'Giảm 20% đơn từ 300K', 'Giảm 20% cho đơn hàng từ 300.000đ, tối đa 100.000đ', 'percent', 20, 300000, 100000, 50, NOW(), DATE_ADD(NOW(), INTERVAL 1 MONTH), 1);

-- Mã giảm 50k cố định
INSERT INTO promotions (code, name, description, discount_type, discount_value, min_order_value, max_discount, usage_limit, start_date, end_date, is_active) VALUES
('GIAM50K', 'Giảm ngay 50.000đ', 'Giảm 50.000đ cho đơn từ 200.000đ', 'fixed', 50000, 200000, NULL, 200, NOW(), DATE_ADD(NOW(), INTERVAL 2 MONTH), 1);

-- Mã freeship (giảm phí ship 20k)
INSERT INTO promotions (code, name, description, discount_type, discount_value, min_order_value, max_discount, usage_limit, start_date, end_date, is_active) VALUES
('FREESHIP', 'Miễn phí giao hàng', 'Miễn phí giao hàng cho đơn từ 150.000đ', 'fixed', 20000, 150000, NULL, NULL, NOW(), DATE_ADD(NOW(), INTERVAL 6 MONTH), 1);

-- Mã VIP giảm 30%
INSERT INTO promotions (code, name, description, discount_type, discount_value, min_order_value, max_discount, usage_limit, start_date, end_date, is_active) VALUES
('VIP30', 'Ưu đãi VIP 30%', 'Dành cho khách hàng VIP, giảm 30% tối đa 200.000đ', 'percent', 30, 500000, 200000, 20, NOW(), DATE_ADD(NOW(), INTERVAL 1 MONTH), 1);

-- Mã cuối tuần
INSERT INTO promotions (code, name, description, discount_type, discount_value, min_order_value, max_discount, usage_limit, start_date, end_date, is_active) VALUES
('WEEKEND15', 'Ưu đãi cuối tuần 15%', 'Giảm 15% cho đơn hàng cuối tuần', 'percent', 15, 200000, 80000, 100, NOW(), DATE_ADD(NOW(), INTERVAL 2 MONTH), 1);

-- =====================================================
-- HƯỚNG DẪN SỬ DỤNG
-- =====================================================
-- 1. Chạy file SQL này trong phpMyAdmin hoặc MySQL client
-- 2. Hoặc truy cập: admin/promotions.php (bảng sẽ tự động tạo)
-- 3. Khách hàng nhập mã tại trang thanh toán (checkout)
-- 
-- CÁC MÃ MẪU:
-- - NEWUSER10: Giảm 10%, đơn từ 100k, tối đa 50k
-- - SALE20: Giảm 20%, đơn từ 300k, tối đa 100k  
-- - GIAM50K: Giảm 50.000đ, đơn từ 200k
-- - FREESHIP: Miễn phí ship, đơn từ 150k
-- - VIP30: Giảm 30%, đơn từ 500k, tối đa 200k
-- - WEEKEND15: Giảm 15%, đơn từ 200k, tối đa 80k
