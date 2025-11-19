-- Tạo database
CREATE DATABASE IF NOT EXISTS ngon_gallery CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ngon_gallery;

-- Bảng danh mục món ăn
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng món ăn
CREATE TABLE IF NOT EXISTS menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng đặt bàn
CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    reservation_date DATE NOT NULL,
    reservation_time TIME NOT NULL,
    number_of_guests INT NOT NULL,
    special_request TEXT,
    status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng liên hệ
CREATE TABLE IF NOT EXISTS contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'replied') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng admin
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thêm dữ liệu mẫu
INSERT INTO categories (name, display_order) VALUES
('Món chính', 1),
('Món phụ', 2),
('Đồ uống', 3),
('Tráng miệng', 4);

INSERT INTO menu_items (category_id, name, description, price, is_available) VALUES
(1, 'Phở bò đặc biệt', 'Phở bò truyền thống với nước dùng hầm xương 12 tiếng', 65000, TRUE),
(1, 'Bún chả Hà Nội', 'Bún chả thơm ngon với thịt nướng than hoa', 55000, TRUE),
(1, 'Cơm tấm sườn bì chả', 'Cơm tấm Sài Gòn với sườn nướng mềm ngọt', 50000, TRUE),
(1, 'Bánh xèo miền Tây', 'Bánh xèo giòn rụm với nhân tôm thịt đầy đặn', 45000, TRUE),
(2, 'Gỏi cuốn tôm thịt', 'Gỏi cuốn tươi mát với tôm và thịt heo', 35000, TRUE),
(2, 'Chả giò Sài Gòn', 'Chả giò giòn tan với nhân thịt và rau củ', 40000, TRUE),
(2, 'Gỏi ngó sen tôm thịt', 'Gỏi ngó sen giòn ngọt thanh mát', 55000, TRUE),
(3, 'Cà phê sữa đá', 'Cà phê phin truyền thống', 25000, TRUE),
(3, 'Trà đá chanh', 'Trà đá chanh tươi mát', 15000, TRUE),
(3, 'Nước dừa tươi', 'Nước dừa xiêm tươi mát', 30000, TRUE),
(3, 'Sinh tố bơ', 'Sinh tố bơ béo ngậy', 35000, TRUE),
(4, 'Chè ba màu', 'Chè ba màu truyền thống', 25000, TRUE),
(4, 'Bánh flan', 'Bánh flan mềm mịn', 20000, TRUE);
