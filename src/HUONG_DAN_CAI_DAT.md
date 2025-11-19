# 🚀 HƯỚNG DẪN CÀI ĐẶT HỆ THỐNG QUẢN TRỊ

## 📋 Yêu cầu hệ thống
- PHP 7.4 trở lên
- MySQL 5.7 trở lên
- Apache/Nginx web server
- XAMPP/WAMP/MAMP (khuyến nghị cho môi trường phát triển)

## 🔧 Các bước cài đặt

### Bước 1: Cài đặt cơ sở dữ liệu

1. Mở **phpMyAdmin** (thường tại `http://localhost/phpmyadmin`)

2. Chạy file SQL để tạo database và các bảng:
   - Vào tab **SQL**
   - Copy toàn bộ nội dung file `config/setup_full.sql`
   - Paste vào và nhấn **Go**

### Bước 2: Cấu hình kết nối database

Mở file `config/database.php` và kiểm tra thông tin:

```php
private $host = 'localhost';      // Địa chỉ MySQL server
private $db_name = 'ngon_gallery'; // Tên database
private $username = 'root';        // Username MySQL
private $password = '';            // Password MySQL (mặc định XAMPP là rỗng)
```

### Bước 3: Tạo tài khoản admin

1. Truy cập: `http://localhost/your-project/config/create_admin.php`

2. Bạn sẽ thấy thông tin đăng nhập:
   - **Username:** admin
   - **Password:** admin123

3. **QUAN TRỌNG:** Sau khi tạo xong, XÓA file `config/create_admin.php` để bảo mật!

### Bước 4: Đăng nhập vào trang quản trị

1. Truy cập: `http://localhost/your-project/admin/login.php`

2. Đăng nhập với tài khoản vừa tạo:
   - Username: `admin`
   - Password: `admin123`

3. Sau khi đăng nhập thành công, bạn sẽ vào Dashboard quản trị

## 📱 Các trang quản trị có sẵn

| Trang | URL | Chức năng |
|-------|-----|-----------|
| Dashboard | `/admin/index.php` | Tổng quan hệ thống |
| Thực đơn | `/admin/menu.php` | Quản lý món ăn, danh mục |
| Đơn hàng | `/admin/orders.php` | Quản lý đơn hàng |
| Đặt bàn | `/admin/reservations.php` | Quản lý đặt bàn |
| Liên hệ | `/admin/contacts.php` | Quản lý tin nhắn liên hệ |
| Khách hàng | `/admin/customers.php` | Quản lý thông tin khách hàng |
| Đánh giá | `/admin/reviews.php` | Quản lý đánh giá món ăn |
| Cài đặt | `/admin/settings.php` | Cài đặt hệ thống |

## 🎯 Tính năng chính

### Dashboard
- Thống kê đặt bàn chờ xác nhận
- Thống kê liên hệ mới
- Tổng số món ăn
- Danh sách đặt bàn gần đây
- Danh sách liên hệ gần đây

### Quản lý thực đơn
- Thêm/sửa/xóa món ăn
- Quản lý danh mục
- Upload hình ảnh món ăn
- Bật/tắt trạng thái món ăn

### Quản lý đơn hàng
- Xem chi tiết đơn hàng
- Cập nhật trạng thái đơn hàng
- In hóa đơn
- Thống kê doanh thu

### Quản lý đặt bàn
- Xác nhận/hủy đặt bàn
- Xem thông tin chi tiết
- Ghi chú yêu cầu đặc biệt

### Quản lý khách hàng
- Xem danh sách khách hàng
- Xem lịch sử đơn hàng
- Xem lịch sử đặt bàn

## 🔒 Bảo mật

1. **Đổi mật khẩu admin ngay sau khi đăng nhập lần đầu**
2. Xóa file `config/create_admin.php` sau khi tạo tài khoản
3. Không để lộ thông tin database
4. Sử dụng HTTPS trong môi trường production

## 🐛 Xử lý lỗi thường gặp

### Lỗi: "Connection Error"
- Kiểm tra MySQL đã chạy chưa
- Kiểm tra thông tin trong `config/database.php`
- Kiểm tra database `ngon_gallery` đã được tạo chưa

### Lỗi: "Access denied"
- Kiểm tra username/password MySQL
- Đảm bảo user có quyền truy cập database

### Lỗi: "Table doesn't exist"
- Chạy lại file `config/setup_full.sql`
- Kiểm tra database đã được tạo đúng

## 📞 Hỗ trợ

Nếu gặp vấn đề, hãy kiểm tra:
1. PHP error log
2. MySQL error log
3. Browser console (F12)

## 🎉 Hoàn tất!

Bây giờ bạn đã có một hệ thống quản trị hoàn chỉnh với:
- ✅ Đăng nhập bảo mật
- ✅ Dashboard trực quan
- ✅ Quản lý đầy đủ các chức năng
- ✅ Giao diện đẹp, responsive
- ✅ Kết nối database ổn định

Chúc bạn sử dụng hiệu quả! 🚀
