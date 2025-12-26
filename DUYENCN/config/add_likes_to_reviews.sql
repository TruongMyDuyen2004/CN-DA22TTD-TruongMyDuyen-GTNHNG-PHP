-- Thêm cột likes vào bảng reviews nếu chưa có
ALTER TABLE reviews 
ADD COLUMN IF NOT EXISTS likes INT DEFAULT 0;

-- Cập nhật giá trị mặc định cho các bản ghi cũ
UPDATE reviews SET likes = 0 WHERE likes IS NULL;
