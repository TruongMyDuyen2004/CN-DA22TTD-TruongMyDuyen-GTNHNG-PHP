# 🌟 Hệ thống đánh giá món ăn - Hoàn chỉnh

## ✅ Đã cài đặt thành công!

Hệ thống đánh giá món ăn đã được cài đặt và cấu hình hoàn chỉnh với đầy đủ tính năng.

## 🎯 Tính năng chính

### 👥 Dành cho khách hàng:

1. **Đánh giá món ăn**
   - Chọn số sao từ 1-5
   - Viết nhận xét chi tiết
   - Xem ngay sau khi gửi (nếu được duyệt tự động)

2. **Xem đánh giá**
   - Thống kê tổng quan: điểm TB, số lượng đánh giá
   - Phân bố theo số sao (biểu đồ thanh)
   - Danh sách đánh giá chi tiết với avatar, tên, ngày
   - Sắp xếp: Mới nhất, Cũ nhất, Cao nhất, Thấp nhất
   - Phân trang tự động (10 đánh giá/trang)

3. **Tương tác**
   - Thích (like) đánh giá hữu ích
   - Xem số lượt thích của mỗi đánh giá

### 👨‍💼 Dành cho Admin:

1. **Quản lý đánh giá**
   - Xem tất cả đánh giá trong hệ thống
   - Duyệt đánh giá chờ kiểm duyệt
   - Từ chối đánh giá không phù hợp
   - Xóa đánh giá vi phạm

2. **Thống kê**
   - Tổng số đánh giá
   - Số đánh giá đã duyệt/chờ duyệt
   - Điểm đánh giá trung bình toàn hệ thống
   - Số lượt thích của mỗi đánh giá

3. **Tìm kiếm & Lọc**
   - Lọc theo trạng thái: Tất cả, Đã duyệt, Chờ duyệt
   - Tìm kiếm theo: Tên khách hàng, Món ăn, Nội dung

## 📂 Cấu trúc file

```
├── api/
│   ├── get-reviews.php          # API lấy danh sách đánh giá
│   ├── submit-review.php        # API gửi đánh giá
│   └── review-like.php          # API thích/bỏ thích
│
├── admin/
│   └── reviews.php              # Trang quản lý đánh giá (Admin)
│
├── assets/
│   ├── css/
│   │   └── reviews.css          # Style cho hệ thống đánh giá
│   └── js/
│       └── reviews.js           # JavaScript xử lý đánh giá
│
├── includes/
│   └── menu-item-reviews.php    # Component hiển thị rating
│
├── pages/
│   └── menu-item-detail.php     # Trang chi tiết món ăn (có đánh giá)
│
└── config/
    ├── run_update_reviews.php   # Script cập nhật database
    └── add_sample_reviews.php   # Script thêm đánh giá mẫu
```

## 🗄️ Cấu trúc Database

### Bảng `reviews`
```sql
- id (INT, PRIMARY KEY)
- customer_id (INT, FOREIGN KEY)
- menu_item_id (INT, FOREIGN KEY)
- order_id (INT, NULLABLE)
- rating (INT, 1-5)
- comment (TEXT)
- is_approved (BOOLEAN, DEFAULT TRUE)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

### Bảng `review_likes`
```sql
- id (INT, PRIMARY KEY)
- review_id (INT, FOREIGN KEY)
- customer_id (INT, FOREIGN KEY)
- created_at (TIMESTAMP)
```

## 🚀 Cách sử dụng

### Khách hàng đánh giá món ăn:

1. Truy cập trang chi tiết món ăn:
   ```
   http://localhost/DUYENCN/index.php?page=menu-item-detail&id={menu_item_id}
   ```

2. Nhấn nút **"Viết đánh giá"** hoặc **"Đánh giá"**

3. Chọn số sao (1-5 sao)

4. Viết nhận xét

5. Nhấn **"Gửi đánh giá"**

### Admin quản lý đánh giá:

1. Đăng nhập admin:
   ```
   http://localhost/DUYENCN/admin/login.php
   ```

2. Vào menu **"Đánh giá"** trên sidebar

3. Xem và quản lý đánh giá:
   - **Duyệt**: Cho phép hiển thị công khai
   - **Từ chối**: Ẩn khỏi trang công khai
   - **Xóa**: Xóa vĩnh viễn

## 📊 API Endpoints

### 1. Lấy danh sách đánh giá
```
GET /api/get-reviews.php
```

**Parameters:**
- `menu_item_id` (required): ID món ăn
- `page` (optional): Số trang (default: 1)
- `sort` (optional): Sắp xếp (newest, oldest, highest, lowest)

**Response:**
```json
{
  "success": true,
  "stats": {
    "total_reviews": 18,
    "avg_rating": 4.0,
    "star_5": 8,
    "star_4": 6,
    "star_3": 4,
    "star_2": 0,
    "star_1": 0
  },
  "reviews": [...],
  "total": 18,
  "has_more": false
}
```

### 2. Gửi đánh giá
```
POST /api/submit-review.php
```

**Body:**
- `menu_item_id`: ID món ăn
- `rating`: Số sao (1-5)
- `comment`: Nội dung đánh giá

**Response:**
```json
{
  "success": true,
  "message": "Đánh giá của bạn đã được gửi thành công"
}
```

### 3. Thích/Bỏ thích đánh giá
```
POST /api/review-like.php
```

**Body:**
- `review_id`: ID đánh giá

**Response:**
```json
{
  "success": true,
  "action": "liked",
  "likes_count": 5
}
```

## 🎨 Giao diện

### Trang khách hàng:
- ✅ Hiển thị điểm trung bình và số sao
- ✅ Biểu đồ phân bố đánh giá theo sao
- ✅ Dropdown sắp xếp đánh giá
- ✅ Danh sách đánh giá với avatar, tên, ngày
- ✅ Nút thích với số lượt thích
- ✅ Nút "Xem thêm" khi có nhiều đánh giá
- ✅ Modal đánh giá đẹp mắt

### Trang Admin:
- ✅ Dashboard với thống kê tổng quan
- ✅ Bộ lọc và tìm kiếm mạnh mẽ
- ✅ Card đánh giá với đầy đủ thông tin
- ✅ Nút hành động rõ ràng
- ✅ Badge trạng thái (Đã duyệt/Chờ duyệt)

## 🔧 Cấu hình

### Số đánh giá mỗi trang
Chỉnh sửa trong `api/get-reviews.php`:
```php
$limit = 10; // Thay đổi số này
```

### Tự động duyệt đánh giá
Mặc định: Đánh giá mới được tự động duyệt

Để yêu cầu duyệt thủ công, sửa trong `api/submit-review.php`:
```php
is_approved = FALSE  // Thay vì TRUE
```

### Giới hạn số sao
Hiện tại: 1-5 sao (chuẩn)

Để thay đổi, cập nhật:
- Database constraint trong bảng `reviews`
- Validation trong `api/submit-review.php`
- UI trong `pages/menu-item-detail.php`

## 🔒 Bảo mật

- ✅ Kiểm tra đăng nhập trước khi đánh giá
- ✅ Validate dữ liệu đầu vào (rating 1-5, comment không rỗng)
- ✅ Escape HTML để tránh XSS
- ✅ Prepared Statements để tránh SQL Injection
- ✅ Kiểm tra quyền admin trước khi duyệt/xóa
- ✅ CSRF protection (session-based)

## 📱 Responsive Design

Giao diện tự động điều chỉnh cho:
- 💻 Desktop (> 1024px)
- 📱 Tablet (768px - 1024px)
- 📱 Mobile (< 768px)

## 🐛 Troubleshooting

### Không hiển thị đánh giá?
1. Kiểm tra `is_approved = TRUE` trong database
2. Xóa cache trình duyệt (Ctrl + F5)
3. Kiểm tra Console (F12) xem có lỗi JavaScript không

### Không gửi được đánh giá?
1. Đảm bảo đã đăng nhập
2. Kiểm tra kết nối database
3. Xem PHP error log

### Admin không thấy đánh giá?
1. Kiểm tra đăng nhập admin
2. Kiểm tra quyền truy cập database
3. Chạy lại `config/run_update_reviews.php`

## 📈 Thống kê hiện tại

Sau khi chạy script mẫu:
- ✅ 18 đánh giá mẫu
- ✅ 16 đánh giá đã duyệt
- ✅ 2 đánh giá chờ duyệt
- ✅ Điểm trung bình: 4.0 sao

## 🎯 Tính năng nâng cao (Tương lai)

- [ ] Upload ảnh kèm đánh giá
- [ ] Trả lời đánh giá (admin/chủ nhà hàng)
- [ ] Báo cáo đánh giá spam/không phù hợp
- [ ] Xếp hạng người đánh giá (Top Reviewer)
- [ ] Thông báo real-time khi có đánh giá mới
- [ ] Xuất báo cáo Excel/PDF
- [ ] Phân tích sentiment (AI)
- [ ] Tích hợp với đơn hàng (chỉ cho phép đánh giá sau khi mua)

## 📞 Liên kết quan trọng

- **Trang khách hàng**: `index.php?page=menu-item-detail&id={id}`
- **Trang Admin**: `admin/reviews.php`
- **API Documentation**: Xem file này
- **Hướng dẫn chi tiết**: `HUONG_DAN_DANH_GIA.md`

## ✨ Hoàn thành!

Hệ thống đánh giá món ăn đã sẵn sàng sử dụng với đầy đủ tính năng cho cả khách hàng và admin!
