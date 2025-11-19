# 💬 HƯỚNG DẪN CÀI ĐẶT HỆ THỐNG BÌNH LUẬN

## 📋 Tổng quan
Hệ thống bình luận cho phép người dùng bình luận vào các đánh giá của người khác.

## 🚀 Cài đặt

### Bước 1: Tạo bảng database
Truy cập: `http://localhost/DUYENCN/config/setup_review_comments.php`

Hoặc chạy SQL trực tiếp trong phpMyAdmin:

```sql
CREATE TABLE IF NOT EXISTS review_comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    review_id INT NOT NULL,
    customer_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_review_id (review_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thêm cột comments_count vào bảng reviews
ALTER TABLE reviews ADD COLUMN IF NOT EXISTS comments_count INT DEFAULT 0;
```

### Bước 2: Thêm dữ liệu mẫu (tùy chọn)
```sql
INSERT INTO review_comments (review_id, customer_id, comment) VALUES
(1, 2, 'Mình cũng đồng ý! Món này rất ngon.'),
(1, 3, 'Cảm ơn bạn đã chia sẻ. Mình sẽ thử món này.');
```

## ✨ Tính năng

### 1. Bình luận vào đánh giá
- Người dùng đã đăng nhập có thể bình luận vào bất kỳ đánh giá nào
- Hiển thị số lượng bình luận trên mỗi đánh giá
- Click vào nút bình luận để xem/ẩn danh sách bình luận

### 2. Hiển thị bình luận
- Hiển thị avatar và tên người bình luận
- Hiển thị thời gian bình luận
- Tự động tải bình luận khi mở

### 3. Gửi bình luận
- Form nhập bình luận với textarea
- Giới hạn 500 ký tự
- Validate nội dung trước khi gửi
- Cập nhật số lượng bình luận real-time

### 4. Bảo mật
- Chỉ người dùng đã đăng nhập mới có thể bình luận
- Validate dữ liệu đầu vào
- Escape HTML để tránh XSS

## 📁 Files đã tạo/cập nhật

### Database
- `config/add_review_comments.sql` - SQL tạo bảng
- `config/setup_review_comments.php` - Script cài đặt

### API
- `api/review-comment.php` - API xử lý bình luận
  - POST: Thêm bình luận mới
  - GET: Lấy danh sách bình luận
  - DELETE: Xóa bình luận (chủ sở hữu)

### Frontend
- `assets/js/reviews.js` - Thêm logic bình luận
  - `toggleComments()` - Hiển thị/ẩn bình luận
  - `loadComments()` - Tải danh sách bình luận
  - `submitComment()` - Gửi bình luận mới
  - `renderComment()` - Render HTML bình luận

- `assets/css/reviews.css` - Thêm styles bình luận
  - Comment button styles
  - Comment section layout
  - Comment item design
  - Comment form styles

### Updated Files
- `api/get-reviews.php` - Thêm comments_count vào response

## 🎨 Giao diện

### Nút bình luận
```
[💬 5] - Hiển thị số lượng bình luận
```

### Phần bình luận
```
┌─────────────────────────────────────┐
│ 👤 Nguyễn Văn A    12/11/2024 10:30 │
│ Mình cũng đồng ý! Món này rất ngon. │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│ [Viết bình luận...]                 │
│                                     │
│                          [📤 Gửi]   │
└─────────────────────────────────────┘
```

## 🔧 Cách sử dụng

### Cho người dùng:
1. Vào trang chi tiết món ăn
2. Cuộn xuống phần đánh giá
3. Click vào nút 💬 trên đánh giá muốn bình luận
4. Nhập nội dung và click "Gửi"

### Cho admin:
- Admin có thể xem tất cả bình luận trong database
- Có thể thêm chức năng quản lý bình luận trong admin panel nếu cần

## 📊 Database Schema

### Bảng `review_comments`
| Cột | Kiểu | Mô tả |
|-----|------|-------|
| id | INT | ID bình luận (Primary Key) |
| review_id | INT | ID đánh giá (Foreign Key) |
| customer_id | INT | ID khách hàng (Foreign Key) |
| comment | TEXT | Nội dung bình luận |
| created_at | TIMESTAMP | Thời gian tạo |

### Bảng `reviews` (cập nhật)
| Cột mới | Kiểu | Mô tả |
|---------|------|-------|
| comments_count | INT | Số lượng bình luận |

## 🔒 Bảo mật

1. **Authentication**: Kiểm tra đăng nhập trước khi cho phép bình luận
2. **Validation**: 
   - Bình luận không được rỗng
   - Tối thiểu 2 ký tự
   - Tối đa 500 ký tự
3. **XSS Protection**: Escape HTML trong nội dung bình luận
4. **SQL Injection**: Sử dụng Prepared Statements

## 🎯 Tính năng có thể mở rộng

- [ ] Xóa bình luận của chính mình
- [ ] Chỉnh sửa bình luận
- [ ] Trả lời bình luận (nested comments)
- [ ] Like bình luận
- [ ] Báo cáo bình luận spam
- [ ] Admin duyệt bình luận
- [ ] Thông báo khi có bình luận mới

## ✅ Hoàn tất!

Hệ thống bình luận đã sẵn sàng sử dụng. Người dùng có thể:
- Xem số lượng bình luận trên mỗi đánh giá
- Click để xem tất cả bình luận
- Viết bình luận mới
- Tương tác với cộng đồng

🎉 Chúc bạn sử dụng vui vẻ!
