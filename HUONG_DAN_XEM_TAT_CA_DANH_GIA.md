# Hướng dẫn xem tất cả đánh giá

## Tính năng mới: Trang "Tất cả đánh giá"

Hệ thống đã được cập nhật với trang hiển thị tất cả đánh giá từ khách hàng.

## Cách truy cập

### 1. Từ Menu chính
- Nhấp vào menu **"Đánh giá"** (Reviews) trên thanh điều hướng chính
- Hoặc truy cập trực tiếp: `index.php?page=all-reviews`

### 2. Từ Trang chủ
- Cuộn xuống phần **"Đánh giá từ khách hàng"**
- Nhấp vào nút **"Xem tất cả đánh giá"**

## Tính năng

### 1. Thống kê tổng quan
- **Điểm đánh giá trung bình**: Hiển thị số sao trung bình
- **Tổng số đánh giá**: Số lượng đánh giá đã được duyệt
- **Biểu đồ phân bố**: Số lượng đánh giá theo từng mức sao (1-5)

### 2. Bộ lọc đánh giá
- **Lọc theo số sao**: Chọn xem đánh giá 1-5 sao
- **Lọc theo món ăn**: Chọn món ăn cụ thể để xem đánh giá
- **Sắp xếp**: 
  - Mới nhất
  - Cũ nhất
  - Điểm cao nhất
  - Điểm thấp nhất

### 3. Hiển thị đánh giá
Mỗi đánh giá bao gồm:
- **Avatar khách hàng**: Chữ cái đầu của tên
- **Tên khách hàng**: Người viết đánh giá
- **Số sao**: Đánh giá từ 1-5 sao
- **Ngày đánh giá**: Thời gian viết đánh giá
- **Món ăn**: Tên món được đánh giá (có link đến chi tiết món)
- **Nội dung**: Nhận xét chi tiết
- **Hình ảnh món ăn**: Nếu có
- **Nút Like**: Thích đánh giá

### 4. Tương tác
- **Like đánh giá**: Nhấp vào nút 👍 để thích đánh giá
- **Xem chi tiết món**: Nhấp vào tên món ăn để xem thông tin chi tiết

## Hiển thị trên Trang chủ

Trang chủ hiện hiển thị **3 đánh giá mới nhất** trong phần "Đánh giá từ khách hàng":
- Thiết kế card đẹp mắt với gradient vàng
- Hiển thị số sao, nội dung, tên khách hàng và món ăn
- Nút "Xem tất cả đánh giá" để chuyển đến trang đầy đủ

## Cài đặt Database

Nếu cần thêm cột `likes` vào bảng reviews:

```sql
-- Chạy file SQL
mysql -u root -p restaurant_db < config/add_likes_to_reviews.sql
```

Hoặc chạy trực tiếp trong phpMyAdmin:
```sql
ALTER TABLE reviews 
ADD COLUMN IF NOT EXISTS likes INT DEFAULT 0;

UPDATE reviews SET likes = 0 WHERE likes IS NULL;
```

## Đa ngôn ngữ

Trang hỗ trợ cả Tiếng Việt và Tiếng Anh:
- Tự động chuyển đổi theo ngôn ngữ đã chọn
- Tất cả text đều được dịch
- Tên món ăn hiển thị theo ngôn ngữ (nếu có bản dịch)

## Responsive Design

Trang được thiết kế responsive:
- **Desktop**: Hiển thị đầy đủ với layout 2 cột
- **Tablet**: Tự động điều chỉnh layout
- **Mobile**: Hiển thị 1 cột, dễ dàng cuộn và đọc

## Lưu ý

1. Chỉ hiển thị đánh giá đã được **duyệt** (is_approved = TRUE)
2. Khách hàng cần **đăng nhập** để like đánh giá
3. Đánh giá được sắp xếp mặc định theo **mới nhất**
4. Bộ lọc tự động submit khi thay đổi lựa chọn

## File liên quan

- `pages/all-reviews.php` - Trang hiển thị tất cả đánh giá
- `pages/home.php` - Trang chủ (có phần đánh giá nổi bật)
- `includes/header.php` - Menu điều hướng
- `lang/vi.php` - Bản dịch Tiếng Việt
- `lang/en.php` - Bản dịch Tiếng Anh
- `config/add_likes_to_reviews.sql` - Script thêm cột likes

## Tích hợp với các tính năng khác

- **Trang Menu**: Có link xem đánh giá của từng món
- **Chi tiết món ăn**: Hiển thị đánh giá của món đó
- **Trang đánh giá**: Khách hàng có thể viết đánh giá sau khi hoàn thành đơn hàng
- **Admin**: Quản lý và duyệt đánh giá

## Hỗ trợ

Nếu có vấn đề, kiểm tra:
1. Database đã có bảng `reviews` chưa
2. Có đánh giá nào được duyệt chưa
3. File `pages/all-reviews.php` đã được tạo chưa
4. Route đã được thêm vào `index.php` chưa
