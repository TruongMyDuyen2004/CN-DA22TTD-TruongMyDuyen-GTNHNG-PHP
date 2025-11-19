-- Thêm cột status vào bảng customers nếu chưa có
ALTER TABLE customers 
ADD COLUMN IF NOT EXISTS status ENUM('active', 'blocked') DEFAULT 'active' AFTER address;

-- Thêm index cho status
ALTER TABLE customers 
ADD INDEX IF NOT EXISTS idx_status (status);

-- Cập nhật tất cả khách hàng hiện tại thành active
UPDATE customers SET status = 'active' WHERE status IS NULL;
