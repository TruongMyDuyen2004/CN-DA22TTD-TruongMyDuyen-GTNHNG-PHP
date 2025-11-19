# 📝 Hướng dẫn hệ thống đánh giá món ăn

## 🎯 Tính năng

### Cho khách hàng:
- ✅ Đánh giá món ăn từ 1-5 sao
- ✅ Viết nhận xét chi tiết
- ✅ Xem đánh giá của người khác
- ✅ Thích (like) đánh giá hữu ích
- ✅ Sắp xếp đánh giá (mới nhất, cũ nhất, cao nhất, thấp nhất)
- ✅ Xem thống kê đánh giá theo số sao
- ✅ Phân trang tự động khi có nhiều đánh giá

### Cho Admin:
- ✅ Xem tất cả đánh giá
- ✅ Duyệt/từ chối đánh giá
- ✅ Xóa đánh giá không phù hợp
- ✅ Thống kê tổng quan
- ✅ Tìm kiếm và lọc đánh giá
- ✅ Xem số lượt thích của mỗi đánh giá

## 📦 Cài đặt

### Bước 1: Cập nhật cơ sở dữ liệu

Chạy lệnh sau để cập nhật bảng reviews:

```bash
php config/run_update_reviews.php
```

Hoặc truy cập trực tiếp:
```
http://localhost/DUYENCN/config/run_update_reviews.php
```

### Bước 2: Kiểm tra cấu trúc database

Bảng `reviews` cần có các cột:
- `id` - ID đánh giá
- `customer_id` - ID khách hàng
- `menu_item_id` - ID món ăn
- `order_id` - ID đơn hàng (nullable)
- `rating` - Số sao (1-5)
- `comment` - Nội dung đánh giá
- `is_approved` - Trạng thái duyệt (TRUE/FALSE)
- `created_at` - Thời gian tạo
- `updated_at` - Thời gian cập nhật

Bảng `review_likes` để lưu lượt thích:
- `id` - ID
- `review_id` - ID đánh giá
- `customer_id` - ID khách hàng
- `created_at` - Thời gian

## 🎨 Sử dụng

### Khách hàng đánh giá món ăn

1. Truy cập trang chi tiết món ăn
2. Nhấn nút "Đánh giá" hoặc "Viết đánh giá"
3. Chọn số sao (1-5)
4. Viết nhận xét
5. Nhấn "Gửi đánh giá"

### Xem đánh giá

- Đánh giá hiển thị ngay trên trang chi tiết món ăn
- Có thống kê tổng quan: điểm trung bình, số lượng đánh giá theo sao
- Sắp xếp theo: mới nhất, cũ nhất, cao nhất, thấp nhất
- Tự động phân trang (10 đánh giá/trang)

### Admin quản lý đánh giá

1. Đăng nhập admin: `admin/login.php`
2. Vào menu "Đánh giá"
3. Xem danh sách đánh giá với các bộ lọc:
   - Tất cả
   - Đã duyệt
   - Chờ duyệt
4. Tìm kiếm theo tên khách hàng, món ăn, nội dung
5. Thực hiện hành động:
   - **Duyệt**: Đánh giá sẽ hiển thị công khai
   - **Từ chối**: Ẩn đánh giá khỏi trang công khai
   - **Xóa**: Xóa vĩnh viễn đánh giá

## 📊 Thống kê

### Trang chi tiết món ăn:
- Điểm đánh giá trung bình
- Tổng số đánh giá
- Phân bố theo số sao (5 sao, 4 sao, ...)

### Trang Admin:
- Tổng số đánh giá
- Số đánh giá đã duyệt
- Số đánh giá chờ duyệt
- Điểm đánh giá trung bình toàn hệ thống

## 🔧 Cấu hình

### Số đánh giá mỗi trang

Chỉnh sửa trong `api/get-reviews.php`:
```php
$limit = 10; // Thay đổi số này
```

### Tự động duyệt đánh giá

Mặc định: đánh giá mới được tự động duyệt (`is_approved = TRUE`)

Để yêu cầu duyệt thủ công, sửa trong `api/submit-review.php`:
```php
is_approved = FALSE  // Thay vì TRUE
```

## 🎯 API Endpoints

### Lấy danh sách đánh giá
```
GET api/get-reviews.php?menu_item_id={id}&page={page}&sort={sort}
```

Tham số:
- `menu_item_id`: ID món ăn (bắt buộc)
- `page`: Trang hiện tại (mặc định: 1)
- `sort`: Sắp xếp (newest, oldest, highest, lowest)

### Gửi đánh giá
```
POST api/submit-review.php
```

Dữ liệu:
- `menu_item_id`: ID món ăn
- `rating`: Số sao (1-5)
- `comment`: Nội dung đánh giá

### Thích/bỏ thích đánh giá
```
POST api/review-like.php
```

Dữ liệu:
- `review_id`: ID đánh giá

## 🎨 Tùy chỉnh giao diện

### CSS
- `assets/css/reviews.css` - Style cho hệ thống đánh giá

### JavaScript
- `assets/js/reviews.js` - Logic xử lý đánh giá

## 🔒 Bảo mật

- ✅ Kiểm tra đăng nhập trước khi đánh giá
- ✅ Validate dữ liệu đầu vào
- ✅ Escape HTML để tránh XSS
- ✅ Sử dụng Prepared Statements để tránh SQL Injection
- ✅ Kiểm tra quyền admin trước khi duyệt/xóa

## 📱 Responsive

Giao diện tự động điều chỉnh cho:
- Desktop
- Tablet
- Mobile

## 🐛 Xử lý lỗi

### Không hiển thị đánh giá?
1. Kiểm tra `is_approved = TRUE` trong database
2. Xóa cache trình duyệt
3. Kiểm tra console JavaScript

### Không gửi được đánh giá?
1. Đảm bảo đã đăng nhập
2. Kiểm tra kết nối database
3. Xem log lỗi PHP

### Admin không thấy đánh giá?
1. Kiểm tra đăng nhập admin
2. Kiểm tra quyền truy cập database
3. Xem bảng `reviews` có dữ liệu không

## 📞 Hỗ trợ

Nếu gặp vấn đề, kiểm tra:
1. File `config/database.php` - Cấu hình database
2. Console trình duyệt - Lỗi JavaScript
3. PHP error log - Lỗi server

## 🚀 Nâng cấp trong tương lai

- [ ] Upload ảnh kèm đánh giá
- [ ] Trả lời đánh giá (admin/chủ nhà hàng)
- [ ] Báo cáo đánh giá không phù hợp
- [ ] Xếp hạng người đánh giá
- [ ] Thông báo khi có đánh giá mới
- [ ] Xuất báo cáo đánh giá
