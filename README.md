# CN-DA22TTD-TruongMyDuyen-GTNHNG-PHP

# Website Nhà hàng Ngon Gallery

Website giới thiệu nhà hàng Việt Nam hiện đại với PHP và MySQL.

## Tính năng

### Phía khách hàng:
- ✅ **Quản lý tài khoản**: Đăng ký, đăng nhập, cập nhật thông tin cá nhân, đổi mật khẩu
- ✅ **Tìm kiếm món ăn**: Tìm kiếm theo tên, lọc theo danh mục
- ✅ **Xem chi tiết món ăn**: Hình ảnh, mô tả, giá bán, trạng thái còn/hết
- ✅ **Đặt bàn trực tuyến**: Chọn ngày giờ, số lượng khách, ghi chú đặc biệt
- ✅ **Giỏ hàng**: Thêm món, cập nhật số lượng, xóa món
- ✅ **Đặt món giao tận nơi**: Thanh toán, chọn phương thức thanh toán
- ✅ **Lịch sử đơn hàng**: Xem chi tiết, theo dõi trạng thái
- ✅ **Đánh giá**: Đánh giá đơn hàng và từng món ăn
- ✅ Thiết kế responsive, thân thiện với mobile

### Phía quản trị:
- Đăng nhập admin
- Dashboard với thống kê
- Quản lý thực đơn (thêm/sửa/xóa)
- Quản lý đặt bàn (xác nhận/hủy)
- Quản lý đơn hàng
- Quản lý liên hệ

## Cấu trúc thư mục

```
ngon-gallery/
├── admin/                 # Trang quản trị
│   ├── index.php         # Dashboard
│   ├── login.php         # Đăng nhập
│   ├── menu.php          # Quản lý thực đơn
│   └── logout.php        # Đăng xuất
├── config/               # Cấu hình
│   ├── database.php      # Kết nối database
│   ├── setup.sql         # File SQL tạo database
│   └── create_admin.php  # Tạo tài khoản admin
├── includes/             # Các file include
│   ├── header.php
│   └── footer.php
├── auth/                 # Xác thực
│   ├── login.php        # Đăng nhập khách hàng
│   ├── register.php     # Đăng ký
│   └── logout.php       # Đăng xuất
├── pages/                # Các trang nội dung
│   ├── home.php
│   ├── about.php
│   ├── menu.php         # Thực đơn + tìm kiếm
│   ├── cart.php         # Giỏ hàng
│   ├── checkout.php     # Thanh toán
│   ├── orders.php       # Lịch sử đơn hàng
│   ├── profile.php      # Thông tin cá nhân
│   ├── review.php       # Đánh giá
│   ├── reservation.php
│   └── contact.php
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── main.js
├── index.php             # File chính
└── README.md
```

## Cài đặt

### 1. Yêu cầu hệ thống
- PHP 7.4 trở lên
- MySQL 5.7 trở lên
- Web server (Apache/Nginx) hoặc PHP built-in server

### 2. Cài đặt database

Sử dụng file `config/setup_full.sql` để tạo database đầy đủ:

```sql
mysql -u root -p < config/setup_full.sql
```

File này bao gồm:
- Tất cả các bảng (customers, menu_items, cart, orders, reviews, reservations...)
- Dữ liệu mẫu cho danh mục và món ăn

### 3. Cấu hình database

Mở file `config/database.php` và chỉnh sửa thông tin kết nối:

```php
private $host = 'localhost';
private $db_name = 'ngon_gallery';
private $username = 'root';
private $password = '';
```

### 4. Tạo tài khoản admin

Truy cập: `http://localhost/config/create_admin.php`

Tài khoản mặc định:
- Username: `admin`
- Password: `admin123`

**Lưu ý:** Xóa file `config/create_admin.php` sau khi tạo tài khoản!

### 5. Chạy website

**Với PHP built-in server:**
```bash
php -S localhost:8000
```

**Với XAMPP/WAMP:**
- Copy thư mục vào `htdocs` hoặc `www`
- Truy cập: `http://localhost/ngon-gallery`

## Sử dụng

### Trang khách hàng:
- Trang chủ: `http://localhost:8000`
- Đăng ký: `http://localhost:8000/auth/register.php`
- Đăng nhập: `http://localhost:8000/auth/login.php`
- Thực đơn: `http://localhost:8000/index.php?page=menu`
- Giỏ hàng: `http://localhost:8000/index.php?page=cart`
- Đơn hàng: `http://localhost:8000/index.php?page=orders`
- Thông tin cá nhân: `http://localhost:8000/index.php?page=profile`
- Đặt bàn: `http://localhost:8000/index.php?page=reservation`
- Liên hệ: `http://localhost:8000/index.php?page=contact`

### Trang quản trị:
- Đăng nhập: `http://localhost:8000/admin/login.php`
- Dashboard: `http://localhost:8000/admin/index.php`
- Quản lý thực đơn: `http://localhost:8000/admin/menu.php`

## Hướng dẫn sử dụng cho khách hàng

1. **Đăng ký tài khoản**: Vào trang đăng ký, điền thông tin và tạo tài khoản
2. **Đăng nhập**: Sử dụng email và mật khẩu đã đăng ký
3. **Xem thực đơn**: Duyệt các món ăn, sử dụng tìm kiếm và bộ lọc
4. **Thêm vào giỏ**: Click "Thêm vào giỏ" trên món ăn yêu thích
5. **Đặt hàng**: Vào giỏ hàng → Click "Đặt hàng" → Điền thông tin giao hàng
6. **Theo dõi đơn**: Vào "Đơn hàng" để xem lịch sử và trạng thái
7. **Đánh giá**: Sau khi đơn hoàn thành, click "Đánh giá" để chia sẻ trải nghiệm
8. **Cập nhật thông tin**: Vào "Thông tin cá nhân" để chỉnh sửa profile

## Tính năng nổi bật

- **Thiết kế hiện đại:** Sử dụng CSS3 với gradient, shadow, animation
- **Responsive:** Tương thích mọi thiết bị
- **Database:** Quản lý dữ liệu động với MySQL
- **Admin Panel:** Quản trị dễ dàng
- **Bảo mật:** Sử dụng password_hash, prepared statements
- **UX tốt:** Form validation, smooth scroll, sticky header

## Tùy chỉnh

- **Màu sắc:** Chỉnh sửa CSS variables trong `assets/css/style.css`
- **Nội dung:** Sửa các file trong `pages/`
- **Database:** Thêm/sửa dữ liệu qua admin panel hoặc phpMyAdmin

## Bảo mật

- Đổi mật khẩu admin sau khi cài đặt
- Xóa file `config/create_admin.php`
- Cấu hình HTTPS cho production
- Backup database định kỳ

## License

Free to use for personal and commercial projects.
