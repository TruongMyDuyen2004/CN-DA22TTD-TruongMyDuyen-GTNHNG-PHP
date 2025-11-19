# 🔧 Hướng dẫn Debug lỗi "Thêm món"

## Vấn đề
Khi bấm vào nút "Thêm món mới" trong trang admin, modal không hiển thị hoặc có lỗi.

## Các bước kiểm tra

### Bước 1: Kiểm tra Console trong trình duyệt

1. Mở trang admin menu: `http://localhost/DUYENCN/admin/menu-manage.php`
2. Nhấn `F12` để mở Developer Tools
3. Chuyển sang tab **Console**
4. Bấm vào nút "Thêm món mới"
5. Xem có lỗi gì hiển thị trong Console không

**Các lỗi thường gặp:**
- `Uncaught TypeError: Cannot read property 'style' of null` → Element không tồn tại
- `Uncaught ReferenceError: showAddModal is not defined` → Function chưa được định nghĩa
- `Failed to fetch` → Lỗi kết nối API

### Bước 2: Test Modal đơn giản

Mở file test: `http://localhost/DUYENCN/test-modal.html`

Nếu modal test hoạt động → Vấn đề nằm ở file `admin/menu-manage.php`
Nếu modal test không hoạt động → Vấn đề nằm ở trình duyệt hoặc JavaScript bị tắt

### Bước 3: Kiểm tra Database

Mở file test: `http://localhost/DUYENCN/test-add-menu.php`

File này sẽ kiểm tra:
- ✅ Kết nối database
- ✅ Cấu trúc bảng menu_items
- ✅ Danh mục (categories) có tồn tại không
- ✅ Quyền ghi thư mục uploads
- ✅ Test thêm món mẫu

### Bước 4: Test Form thêm món

Mở file test: `http://localhost/DUYENCN/test-add-menu-form.php`

Form này sẽ:
- 🎯 Test trực tiếp chức năng thêm món
- 📊 Hiển thị thông tin debug chi tiết
- 🖼️ Preview ảnh trước khi upload
- ✅ Hiển thị lỗi cụ thể nếu có

### Bước 5: Kiểm tra Session Admin

Nếu bạn chưa đăng nhập admin:
1. Đăng nhập tại: `http://localhost/DUYENCN/admin/login.php`
2. Username: `admin`
3. Password: (mật khẩu bạn đã tạo)

Nếu quên mật khẩu, chạy: `http://localhost/DUYENCN/config/reset_admin_password.php`

## Các lỗi thường gặp và cách sửa

### Lỗi 1: Modal không hiển thị

**Nguyên nhân:**
- CSS bị conflict
- JavaScript bị lỗi
- Element ID bị trùng

**Cách sửa:**
1. Kiểm tra Console có lỗi không
2. Kiểm tra element `#addModal` có tồn tại không (dùng Inspect Element)
3. Kiểm tra CSS `display: flex` có được apply không

### Lỗi 2: "Chưa đăng nhập"

**Nguyên nhân:**
- Session admin không tồn tại
- Chưa đăng nhập admin

**Cách sửa:**
1. Đăng nhập lại tại `/admin/login.php`
2. Kiểm tra session trong PHP: `var_dump($_SESSION);`

### Lỗi 3: "Danh mục không tồn tại"

**Nguyên nhân:**
- Chưa có danh mục trong database
- Category ID không hợp lệ

**Cách sửa:**
Chạy SQL trong phpMyAdmin:
```sql
INSERT INTO categories (name, name_en, display_order) VALUES 
('Món chính', 'Main Dishes', 1),
('Món phụ', 'Side Dishes', 2),
('Đồ uống', 'Beverages', 3),
('Tráng miệng', 'Desserts', 4);
```

### Lỗi 4: "Không thể upload ảnh"

**Nguyên nhân:**
- Thư mục uploads không có quyền ghi
- File quá lớn (>5MB)
- Định dạng file không hợp lệ

**Cách sửa:**
1. Tạo thư mục: `uploads/menu/`
2. Set quyền: 0777 (Windows tự động)
3. Kiểm tra file size < 5MB
4. Chỉ upload JPG, PNG, GIF, WEBP

### Lỗi 5: "Thiếu thông tin bắt buộc"

**Nguyên nhân:**
- Chưa điền đầy đủ: Tên món, Giá, Danh mục

**Cách sửa:**
- Điền đầy đủ các trường có dấu `*`

## Debug với Console Log

File `admin/menu-manage.php` đã được thêm console.log:

```javascript
// Khi bấm "Thêm món mới", console sẽ hiển thị:
showAddModal called
Opening modal...
Modal opened successfully

// Nếu có lỗi, console sẽ hiển thị:
Modal element not found!
Form element not found!
```

## Kiểm tra nhanh

Chạy lệnh này trong Console của trình duyệt:
```javascript
// Kiểm tra modal có tồn tại không
console.log(document.getElementById('addModal'));

// Kiểm tra form có tồn tại không
console.log(document.getElementById('addForm'));

// Test mở modal
showAddModal();
```

## Liên hệ hỗ trợ

Nếu vẫn gặp lỗi, hãy cung cấp:
1. Screenshot lỗi trong Console
2. Kết quả từ `test-add-menu.php`
3. Phiên bản PHP và MySQL
4. Trình duyệt đang sử dụng

## Checklist

- [ ] Đã đăng nhập admin
- [ ] Console không có lỗi
- [ ] Database có danh mục
- [ ] Thư mục uploads tồn tại
- [ ] Test modal hoạt động
- [ ] Test form hoạt động
- [ ] Có thể thêm món thành công

---

**Cập nhật:** Đã thêm debug logging vào `admin/menu-manage.php` và `admin/api/add-menu-item.php`
