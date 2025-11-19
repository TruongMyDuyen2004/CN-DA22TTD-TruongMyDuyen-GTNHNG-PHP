-- Cơ sở dữ liệu đặt bàn nâng cao
USE ngon_gallery;

-- Xóa bảng cũ nếu cần cập nhật
DROP TABLE IF EXISTS reservation_tables;
DROP TABLE IF EXISTS reservations;
DROP TABLE IF EXISTS tables;
DROP TABLE IF EXISTS time_slots;

-- Bảng bàn ăn
CREATE TABLE IF NOT EXISTS tables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_number VARCHAR(10) NOT NULL UNIQUE,
    capacity INT NOT NULL,
    location ENUM('indoor', 'outdoor', 'vip', 'private_room') DEFAULT 'indoor',
    is_available BOOLEAN DEFAULT TRUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng khung giờ
CREATE TABLE IF NOT EXISTS time_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    time_slot TIME NOT NULL UNIQUE,
    is_available BOOLEAN DEFAULT TRUE,
    max_reservations INT DEFAULT 10,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng đặt bàn (nâng cao)
CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NULL,
    customer_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    reservation_date DATE NOT NULL,
    reservation_time TIME NOT NULL,
    number_of_guests INT NOT NULL,
    special_request TEXT,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed', 'no_show') DEFAULT 'pending',
    deposit_amount DECIMAL(10,2) DEFAULT 0,
    deposit_status ENUM('not_required', 'pending', 'paid', 'refunded') DEFAULT 'not_required',
    table_preference ENUM('indoor', 'outdoor', 'vip', 'private_room', 'any') DEFAULT 'any',
    occasion VARCHAR(100) NULL COMMENT 'Sinh nhật, kỷ niệm, họp mặt...',
    admin_note TEXT,
    confirmed_by INT NULL,
    confirmed_at TIMESTAMP NULL,
    cancelled_reason TEXT,
    cancelled_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (confirmed_by) REFERENCES admins(id) ON DELETE SET NULL,
    INDEX idx_date_time (reservation_date, reservation_time),
    INDEX idx_status (status),
    INDEX idx_customer (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng liên kết đặt bàn và bàn ăn
CREATE TABLE IF NOT EXISTS reservation_tables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    table_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    FOREIGN KEY (table_id) REFERENCES tables(id) ON DELETE CASCADE,
    UNIQUE KEY unique_reservation_table (reservation_id, table_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thêm dữ liệu mẫu cho bàn ăn
INSERT INTO tables (table_number, capacity, location, description) VALUES
('T01', 2, 'indoor', 'Bàn 2 người gần cửa sổ'),
('T02', 2, 'indoor', 'Bàn 2 người khu vực trung tâm'),
('T03', 4, 'indoor', 'Bàn 4 người khu vực trung tâm'),
('T04', 4, 'indoor', 'Bàn 4 người gần quầy bar'),
('T05', 6, 'indoor', 'Bàn 6 người khu vực rộng rãi'),
('T06', 6, 'indoor', 'Bàn 6 người gần cửa sổ'),
('T07', 8, 'indoor', 'Bàn 8 người cho nhóm lớn'),
('O01', 4, 'outdoor', 'Bàn 4 người khu vực sân vườn'),
('O02', 4, 'outdoor', 'Bàn 4 người khu vực sân vườn'),
('O03', 6, 'outdoor', 'Bàn 6 người khu vực sân vườn'),
('V01', 4, 'vip', 'Bàn VIP 4 người khu vực riêng tư'),
('V02', 6, 'vip', 'Bàn VIP 6 người khu vực riêng tư'),
('P01', 10, 'private_room', 'Phòng riêng 10 người có máy lạnh'),
('P02', 15, 'private_room', 'Phòng riêng 15 người có máy lạnh và karaoke'),
('P03', 20, 'private_room', 'Phòng riêng 20 người cho sự kiện lớn');

-- Thêm dữ liệu mẫu cho khung giờ
INSERT INTO time_slots (time_slot, max_reservations) VALUES
('10:00:00', 8),
('10:30:00', 8),
('11:00:00', 10),
('11:30:00', 10),
('12:00:00', 12),
('12:30:00', 12),
('13:00:00', 10),
('13:30:00', 8),
('17:00:00', 8),
('17:30:00', 10),
('18:00:00', 12),
('18:30:00', 12),
('19:00:00', 12),
('19:30:00', 10),
('20:00:00', 10),
('20:30:00', 8),
('21:00:00', 6);

-- Thêm một số đặt bàn mẫu
INSERT INTO reservations (customer_name, email, phone, reservation_date, reservation_time, number_of_guests, special_request, status, table_preference, occasion) VALUES
('Nguyễn Văn A', 'nguyenvana@email.com', '0901234567', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '18:00:00', 4, 'Cần bàn gần cửa sổ', 'confirmed', 'indoor', 'Sinh nhật'),
('Trần Thị B', 'tranthib@email.com', '0912345678', DATE_ADD(CURDATE(), INTERVAL 2 DAY), '19:00:00', 6, 'Cần ghế trẻ em', 'pending', 'any', 'Họp mặt gia đình'),
('Lê Văn C', 'levanc@email.com', '0923456789', DATE_ADD(CURDATE(), INTERVAL 3 DAY), '12:00:00', 2, '', 'confirmed', 'outdoor', 'Hẹn hò'),
('Phạm Thị D', 'phamthid@email.com', '0934567890', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '20:00:00', 8, 'Cần không gian riêng tư', 'pending', 'vip', 'Kỷ niệm'),
('Hoàng Văn E', 'hoangvane@email.com', '0945678901', DATE_ADD(CURDATE(), INTERVAL 5 DAY), '18:30:00', 12, 'Tổ chức sinh nhật, cần bánh kem', 'pending', 'private_room', 'Sinh nhật');

-- View để xem thống kê đặt bàn
CREATE OR REPLACE VIEW reservation_statistics AS
SELECT 
    DATE(reservation_date) as date,
    COUNT(*) as total_reservations,
    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
    SUM(number_of_guests) as total_guests
FROM reservations
GROUP BY DATE(reservation_date)
ORDER BY date DESC;

-- View để xem tình trạng bàn
CREATE OR REPLACE VIEW table_availability AS
SELECT 
    t.id,
    t.table_number,
    t.capacity,
    t.location,
    t.is_available,
    COUNT(rt.id) as current_reservations
FROM tables t
LEFT JOIN reservation_tables rt ON t.id = rt.table_id
LEFT JOIN reservations r ON rt.reservation_id = r.id 
    AND r.reservation_date = CURDATE() 
    AND r.status IN ('confirmed', 'pending')
GROUP BY t.id, t.table_number, t.capacity, t.location, t.is_available;

-- Stored Procedure: Kiểm tra tình trạng còn chỗ
DELIMITER //
CREATE PROCEDURE check_availability(
    IN p_date DATE,
    IN p_time TIME,
    IN p_guests INT
)
BEGIN
    SELECT 
        ts.time_slot,
        ts.max_reservations,
        COUNT(r.id) as current_reservations,
        (ts.max_reservations - COUNT(r.id)) as available_slots,
        CASE 
            WHEN COUNT(r.id) < ts.max_reservations THEN 'Available'
            ELSE 'Full'
        END as status
    FROM time_slots ts
    LEFT JOIN reservations r ON r.reservation_time = ts.time_slot 
        AND r.reservation_date = p_date
        AND r.status IN ('confirmed', 'pending')
    WHERE ts.time_slot = p_time
    GROUP BY ts.id, ts.time_slot, ts.max_reservations;
END //
DELIMITER ;

-- Stored Procedure: Tự động hủy đặt bàn quá hạn
DELIMITER //
CREATE PROCEDURE cancel_expired_reservations()
BEGIN
    UPDATE reservations
    SET status = 'cancelled',
        cancelled_reason = 'Tự động hủy do quá thời gian',
        cancelled_at = NOW()
    WHERE status = 'pending'
    AND CONCAT(reservation_date, ' ', reservation_time) < NOW() - INTERVAL 2 HOUR;
END //
DELIMITER ;

-- Trigger: Cập nhật thời gian sửa đổi
DELIMITER //
CREATE TRIGGER before_reservation_update
BEFORE UPDATE ON reservations
FOR EACH ROW
BEGIN
    SET NEW.updated_at = NOW();
END //
DELIMITER ;

-- Event: Tự động hủy đặt bàn quá hạn (chạy mỗi giờ)
SET GLOBAL event_scheduler = ON;

CREATE EVENT IF NOT EXISTS auto_cancel_expired_reservations
ON SCHEDULE EVERY 1 HOUR
DO
    CALL cancel_expired_reservations();

