-- Thêm cột google_id và avatar vào bảng customers để hỗ trợ đăng nhập Google

-- Thêm cột google_id
ALTER TABLE customers ADD COLUMN IF NOT EXISTS google_id VARCHAR(255) DEFAULT NULL;

-- Thêm cột avatar
ALTER TABLE customers ADD COLUMN IF NOT EXISTS avatar VARCHAR(500) DEFAULT NULL;

-- Tạo index cho google_id để tìm kiếm nhanh hơn
CREATE INDEX IF NOT EXISTS idx_google_id ON customers(google_id);

-- Cho phép password rỗng (cho user đăng nhập bằng Google)
ALTER TABLE customers MODIFY COLUMN password VARCHAR(255) DEFAULT '';
