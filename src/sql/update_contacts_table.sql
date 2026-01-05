-- Cập nhật bảng contacts để hỗ trợ tính năng phản hồi

-- Thêm cột admin_reply nếu chưa có
ALTER TABLE contacts ADD COLUMN IF NOT EXISTS admin_reply TEXT DEFAULT NULL;

-- Thêm cột replied_at nếu chưa có
ALTER TABLE contacts ADD COLUMN IF NOT EXISTS replied_at DATETIME DEFAULT NULL;

-- Thêm cột replied_by nếu chưa có
ALTER TABLE contacts ADD COLUMN IF NOT EXISTS replied_by INT DEFAULT NULL;

-- Thêm cột user_read_at để đánh dấu người dùng đã đọc phản hồi
ALTER TABLE contacts ADD COLUMN IF NOT EXISTS user_read_at DATETIME DEFAULT NULL;

-- Nếu MySQL không hỗ trợ IF NOT EXISTS, chạy từng lệnh riêng:
-- ALTER TABLE contacts ADD COLUMN admin_reply TEXT DEFAULT NULL;
-- ALTER TABLE contacts ADD COLUMN replied_at DATETIME DEFAULT NULL;
-- ALTER TABLE contacts ADD COLUMN replied_by INT DEFAULT NULL;
-- ALTER TABLE contacts ADD COLUMN user_read_at DATETIME DEFAULT NULL;
