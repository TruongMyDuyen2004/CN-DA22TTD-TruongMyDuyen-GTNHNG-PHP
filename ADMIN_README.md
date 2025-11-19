# 🎯 HỆ THỐNG QUẢN TRỊ NGON GALLERY

## 🚀 Cài đặt nhanh (3 bước)

### Bước 1: Import Database
```
1. Mở phpMyAdmin (http://localhost/phpmyadmin)
2. Tạo database mới tên: ngon_gallery
3. Import file: config/setup_full.sql
```

### Bước 2: Tạo tài khoản Admin
```
Truy cập: http://localhost/your-project/config/create_admin.php
Username: admin
Password: admin123
```

### Bước 3: Đăng nhập
```
Truy cập: http://localhost/your-project/admin/login.php
Đăng nhập với tài khoản vừa tạo
```

## 🧪 Test hệ thống

Kiểm tra kết nối database:
```
http://localhost/your-project/test-database.php
```

## 📱 Các trang quản trị

| Trang | Đường dẫn | Chức năng |
|-------|-----------|-----------|
| 🏠 Dashboard | `/admin/index.php` | Tổng quan |
| 🍽️ Thực đơn | `/admin/menu.php` | Quản lý món ăn |
| 📦 Đơn hàng | `/admin/orders.php` | Quản lý đơn hàng |
| 📅 Đặt bàn | `/admin/reservations.php` | Quản lý đặt bàn |
| 💬 Liên hệ | `/admin/contacts.php` | Quản lý liên hệ |
| 👥 Khách hàng | `/admin/customers.php` | Quản lý khách hàng |
| ⭐ Đánh giá | `/admin/reviews.php` | Quản lý đánh giá |
| ⚙️ Cài đặt | `/admin/settings.php` | Cài đặt hệ thống |

## 🎨 Tính năng nổi bật

✅ **Dashboard trực quan** - Thống kê realtime
✅ **Quản lý đầy đủ** - Tất cả chức năng trong một hệ thống
✅ **Giao diện đẹp** - Modern, responsive design
✅ **Bảo mật cao** - Session, password hash
✅ **Dễ sử dụng** - UX/UI thân thiện

## 🔐 Bảo mật

⚠️ **Quan trọng:**
1. Đổi mật khẩu admin sau khi đăng nhập lần đầu
2. Xóa file `config/create_admin.php` sau khi tạo tài khoản
3. Không để lộ thông tin database
4. Sử dụng HTTPS trong production

## 📞 Cấu trúc Database

```
ngon_gallery/
├── admins          - Tài khoản quản trị
├── customers       - Khách hàng
├── categories      - Danh mục món ăn
├── menu_items      - Món ăn
├── cart            - Giỏ hàng
├── orders          - Đơn hàng
├── order_items     - Chi tiết đơn hàng
├── reviews         - Đánh giá
├── reservations    - Đặt bàn
└── contacts        - Liên hệ
```

## 🛠️ Công nghệ sử dụng

- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+
- **Frontend:** HTML5, CSS3, JavaScript
- **Icons:** Font Awesome 6
- **Design:** Modern Gradient UI

## 📖 Tài liệu chi tiết

Xem file `HUONG_DAN_CAI_DAT.md` để biết hướng dẫn chi tiết hơn.

---

**Phát triển bởi:** Ngon Gallery Team
**Phiên bản:** 1.0.0
**Cập nhật:** 2024
