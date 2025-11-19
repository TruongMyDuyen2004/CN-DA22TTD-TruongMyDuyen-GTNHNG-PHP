# Hướng dẫn quản lý khách hàng - Admin

## Tính năng quản lý khách hàng

### 1. Danh sách khách hàng (`admin/customers.php`)

#### Chức năng:
- ✅ Xem danh sách tất cả khách hàng
- ✅ Tìm kiếm theo tên, email, số điện thoại
- ✅ Lọc theo trạng thái (Hoạt động/Đã khóa)
- ✅ Xem thống kê tổng quan
- ✅ Khóa/Mở khóa tài khoản
- ✅ Xóa khách hàng
- ✅ Xuất danh sách Excel

#### Thông tin hiển thị:
- ID khách hàng
- Họ tên
- Email
- Số điện thoại
- Tổng số đơn hàng
- Tổng chi tiêu
- Ngày đăng ký
- Trạng thái tài khoản

#### Thống kê:
- **Tổng khách hàng**: Số lượng khách hàng đã đăng ký
- **Đăng ký hôm nay**: Số khách hàng mới trong ngày

### 2. Chi tiết khách hàng (`admin/customer_detail.php`)

#### Thông tin cá nhân:
- Họ tên
- Email
- Số điện thoại
- Địa chỉ
- Ngày đăng ký
- Trạng thái tài khoản

#### Thống kê mua hàng:
- Tổng đơn hàng
- Tổng chi tiêu
- Giá trị trung bình/đơn

#### Lịch sử đơn hàng:
- Danh sách tất cả đơn hàng
- Mã đơn, ngày đặt, tổng tiền
- Trạng thái đơn hàng
- Link xem chi tiết đơn

#### Đánh giá:
- Tất cả đánh giá của khách hàng
- Số sao, món ăn, nhận xét
- Ngày đánh giá

## Cấu trúc Database

### Bảng `customers`

```sql
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    avatar VARCHAR(255),
    status ENUM('active', 'blocked') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Trường `status`:
- **active**: Tài khoản hoạt động bình thường
- **blocked**: Tài khoản bị khóa, không thể đăng nhập

## Cài đặt

### 1. Cập nhật Database

Chạy file SQL để thêm trường status:

```bash
mysql -u root -p ngon_gallery < config/update_customers_table.sql
```

Hoặc chạy trực tiếp trong phpMyAdmin:

```sql
ALTER TABLE customers 
ADD COLUMN status ENUM('active', 'blocked') DEFAULT 'active' AFTER address;

ALTER TABLE customers 
ADD INDEX idx_status (status);

UPDATE customers SET status = 'active' WHERE status IS NULL;
```

### 2. Truy cập trang quản lý

- **URL**: `http://localhost/admin/customers.php`
- **Yêu cầu**: Đã đăng nhập admin

## Sử dụng

### Tìm kiếm khách hàng

1. Nhập từ khóa vào ô tìm kiếm
2. Có thể tìm theo:
   - Họ tên
   - Email
   - Số điện thoại
3. Click "Lọc" hoặc Enter

### Lọc theo trạng thái

1. Chọn trạng thái từ dropdown:
   - Tất cả trạng thái
   - Hoạt động
   - Đã khóa
2. Tự động lọc khi chọn

### Khóa/Mở khóa tài khoản

1. Click icon khóa/mở khóa ở cột "Thao tác"
2. Xác nhận thao tác
3. Tài khoản bị khóa sẽ không thể đăng nhập

**Lưu ý**: Khóa tài khoản không xóa dữ liệu, chỉ ngăn đăng nhập

### Xóa khách hàng

1. Click icon thùng rác ở cột "Thao tác"
2. Xác nhận xóa
3. **Cảnh báo**: Không thể xóa nếu khách hàng có đơn hàng

### Xem chi tiết

1. Click icon mắt ở cột "Thao tác"
2. Xem đầy đủ thông tin:
   - Thông tin cá nhân
   - Thống kê mua hàng
   - Lịch sử đơn hàng
   - Đánh giá

### Xuất Excel

1. Click nút "Xuất Excel" ở góc trên
2. File Excel sẽ được tải về với:
   - Danh sách khách hàng
   - Thông tin chi tiết
   - Thống kê

## Bảo mật

### Kiểm tra đăng nhập

Mọi trang admin đều có kiểm tra session:

```php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
```

### Xác thực thao tác

- Xóa: Yêu cầu xác nhận
- Khóa tài khoản: Yêu cầu xác nhận
- Sử dụng prepared statements để tránh SQL injection

### Phân quyền

Chỉ admin mới có quyền:
- Xem danh sách khách hàng
- Khóa/mở khóa tài khoản
- Xóa khách hàng
- Xem thông tin chi tiết

## Giao diện

### Màu sắc trạng thái

- **Hoạt động**: Badge xanh lá
- **Đã khóa**: Badge đỏ

### Icons

- 👁️ Xem chi tiết
- 🔒 Khóa tài khoản
- 🔓 Mở khóa tài khoản
- 🗑️ Xóa khách hàng

### Responsive

- Desktop: Hiển thị đầy đủ
- Tablet: Tối ưu layout
- Mobile: Sidebar ẩn, table scroll ngang

## Tính năng nâng cao

### 1. Thống kê chi tiết

```php
// Tổng đơn hàng
COUNT(DISTINCT o.id) as total_orders

// Tổng chi tiêu
SUM(o.total_amount) as total_spent

// Giá trị TB/đơn
AVG(o.total_amount) as avg_order
```

### 2. Tìm kiếm nâng cao

```php
// Tìm theo nhiều trường
WHERE (c.full_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)
```

### 3. Lọc kết hợp

- Tìm kiếm + Lọc trạng thái
- Kết quả chính xác

## Troubleshooting

### Lỗi: Không thể xóa khách hàng

**Nguyên nhân**: Khách hàng có đơn hàng (Foreign key constraint)

**Giải pháp**: 
- Khóa tài khoản thay vì xóa
- Hoặc xóa đơn hàng trước

### Lỗi: Không hiển thị trạng thái

**Nguyên nhân**: Chưa chạy update database

**Giải pháp**: Chạy `config/update_customers_table.sql`

### Lỗi: Không thể khóa tài khoản

**Nguyên nhân**: Lỗi database hoặc quyền

**Giải pháp**: Kiểm tra:
- Kết nối database
- Quyền UPDATE trên bảng customers

## Best Practices

### 1. Quản lý khách hàng

- ✅ Khóa tài khoản thay vì xóa
- ✅ Ghi chú lý do khóa
- ✅ Backup trước khi xóa
- ✅ Kiểm tra đơn hàng trước khi xóa

### 2. Bảo mật

- ✅ Luôn kiểm tra session admin
- ✅ Validate input
- ✅ Sử dụng prepared statements
- ✅ Log các thao tác quan trọng

### 3. UX

- ✅ Xác nhận trước khi xóa
- ✅ Thông báo rõ ràng
- ✅ Loading state khi xử lý
- ✅ Responsive design

## API Endpoints

### GET /admin/customers.php
- Xem danh sách khách hàng
- Params: `search`, `status`

### GET /admin/customer_detail.php
- Xem chi tiết khách hàng
- Params: `id`

### POST /admin/customers.php
- Khóa/mở khóa tài khoản
- Body: `customer_id`, `status`, `toggle_status`

### GET /admin/customers.php?delete=ID
- Xóa khách hàng
- Params: `delete`

## Files

```
admin/
├── customers.php           # Danh sách khách hàng
├── customer_detail.php     # Chi tiết khách hàng
├── includes/
│   └── sidebar.php        # Sidebar navigation
└── export_customers.php   # Xuất Excel (TODO)

assets/
└── css/
    └── admin.css          # CSS cho admin panel

config/
└── update_customers_table.sql  # Update database
```

## Changelog

### Version 1.0 (November 2025)
- ✅ Danh sách khách hàng
- ✅ Tìm kiếm và lọc
- ✅ Chi tiết khách hàng
- ✅ Khóa/mở khóa tài khoản
- ✅ Xóa khách hàng
- ✅ Thống kê tổng quan
- ✅ Responsive design

### Planned Features
- [ ] Xuất Excel
- [ ] Gửi email cho khách hàng
- [ ] Lịch sử thao tác
- [ ] Phân tích hành vi
- [ ] Segmentation khách hàng
