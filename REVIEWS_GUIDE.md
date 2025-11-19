# 🌟 Hệ thống Đánh giá & Reviews - Ngon Gallery

## ✨ Tính năng đã hoàn thành

### 1. **Hiển thị đánh giá trên từng món ăn**
- ⭐ Rating stars (1-5 sao)
- 📊 Thống kê tổng quan (điểm trung bình, số lượng đánh giá)
- 📈 Biểu đồ phân bố đánh giá theo sao
- 💬 Danh sách bình luận từ khách hàng

### 2. **Chức năng Like bình luận**
- ❤️ Like/Unlike đánh giá
- 🔢 Đếm số lượng likes
- 🎨 Animation khi like (heartBeat effect)
- 👤 Hiển thị trạng thái đã like cho user

### 3. **Viết đánh giá mới**
- ⭐ Chọn số sao (1-5)
- ✍️ Viết bình luận
- ✅ Kiểm tra đã đánh giá chưa (mỗi user chỉ đánh giá 1 lần/món)
- 🔐 Yêu cầu đăng nhập

## 📁 Files đã tạo

### Database
```sql
config/add_review_likes.sql
```
- Thêm cột `likes_count`, `is_approved` vào bảng `reviews`
- Tạo bảng `review_likes` để lưu ai đã like review nào
- Thêm dữ liệu mẫu

### API Endpoints
```
api/get-reviews.php       - Lấy danh sách reviews của món ăn
api/review-like.php       - Like/Unlike review
api/submit-review.php     - Gửi đánh giá mới
```

### Frontend
```
assets/css/reviews.css    - Styles cho reviews system
assets/js/reviews.js      - JavaScript xử lý reviews
```

### Pages & Components
```
pages/menu-item-detail.php        - Trang chi tiết món ăn với reviews
includes/menu-item-reviews.php    - Component hiển thị rating summary
```

## 🚀 Cách sử dụng

### Bước 1: Chạy SQL
```sql
-- Truy cập phpMyAdmin và chạy file:
config/add_review_likes.sql
```

### Bước 2: Thêm CSS vào index.php
```php
<link rel="stylesheet" href="assets/css/reviews.css">
```

### Bước 3: Xem chi tiết món ăn
```
http://localhost/DUYENCN/index.php?page=menu-item-detail&id=1
```

### Bước 4: Cập nhật trang menu
Thêm link "Chi tiết" và hiển thị rating cho mỗi món ăn trong `pages/menu.php`:

```php
<!-- Thêm rating -->
<?php 
$menu_item_id = $item['id'];
include 'includes/menu-item-reviews.php'; 
?>

<!-- Thêm link chi tiết -->
<a href="index.php?page=menu-item-detail&id=<?php echo $item['id']; ?>" class="btn btn-small">
    <i class="fas fa-eye"></i> Chi tiết
</a>
```

## 🎨 Giao diện

### Rating Summary
- **Điểm trung bình lớn** với stars
- **Biểu đồ thanh** cho mỗi mức sao (5⭐ đến 1⭐)
- **Phần trăm** và số lượng đánh giá

### Review Item
- **Avatar** với chữ cái đầu tên
- **Tên khách hàng** và ngày đánh giá
- **Rating stars** (1-5 sao)
- **Bình luận** của khách hàng
- **Nút Like** với số lượng likes
- **Hover effects** mượt mà

### Review Modal
- **Rating input** với stars có thể click
- **Textarea** cho bình luận
- **Validation** form
- **Animation** khi mở/đóng

## 💡 Tính năng nổi bật

### 1. Like System
```javascript
// Tự động cập nhật UI khi like
- Toggle liked/unliked state
- Update like count
- HeartBeat animation
- Kiểm tra đăng nhập
```

### 2. Rating Display
```javascript
// Hiển thị sao chính xác
- Full star: ⭐
- Half star: ⭐½
- Empty star: ☆
```

### 3. Responsive Design
```css
- Mobile friendly
- Touch optimized
- Smooth animations
- Modern gradients
```

## 🔒 Bảo mật

- ✅ Kiểm tra đăng nhập trước khi like/review
- ✅ Validate input (rating 1-5, comment không rỗng)
- ✅ Escape HTML để tránh XSS
- ✅ Prepared statements để tránh SQL injection
- ✅ Unique constraint (1 user chỉ like 1 lần/review)

## 📊 Database Schema

### Table: reviews
```sql
- id (PK)
- customer_id (FK)
- menu_item_id (FK)
- order_id (nullable)
- rating (1-5)
- comment (TEXT)
- likes_count (INT, default 0)
- is_approved (BOOLEAN, default TRUE)
- created_at
- updated_at
```

### Table: review_likes
```sql
- id (PK)
- review_id (FK)
- customer_id (FK)
- created_at
- UNIQUE(review_id, customer_id)
```

## 🎯 API Response Format

### GET /api/get-reviews.php
```json
{
  "success": true,
  "stats": {
    "total_reviews": 10,
    "avg_rating": 4.5,
    "star_5": 6,
    "star_4": 3,
    "star_3": 1,
    "star_2": 0,
    "star_1": 0
  },
  "reviews": [
    {
      "id": 1,
      "customer_id": 1,
      "full_name": "Nguyễn Văn A",
      "rating": 5,
      "comment": "Món ăn rất ngon!",
      "likes_count": 5,
      "is_liked_by_user": true,
      "created_at": "2024-01-01 10:00:00"
    }
  ]
}
```

### POST /api/review-like.php
```json
{
  "success": true,
  "action": "liked",
  "likes_count": 6
}
```

### POST /api/submit-review.php
```json
{
  "success": true,
  "message": "Cảm ơn bạn đã đánh giá!"
}
```

## 🎨 CSS Classes

```css
.reviews-section          - Container chính
.rating-summary          - Tổng quan đánh giá
.rating-overview         - Điểm trung bình
.rating-breakdown        - Biểu đồ phân bố
.reviews-list            - Danh sách reviews
.review-item             - Mỗi review
.review-like-btn         - Nút like
.review-like-btn.liked   - Trạng thái đã like
.review-modal            - Modal viết đánh giá
```

## 🚀 Performance

- **Lazy loading** reviews khi cần
- **Debounce** cho like button
- **Optimized queries** với indexes
- **Cached** rating stats

## 📱 Mobile Responsive

```css
@media (max-width: 768px) {
  - Single column layout
  - Larger touch targets
  - Simplified rating bars
  - Full-width modals
}
```

## ✅ Checklist hoàn thành

- [x] Database schema
- [x] API endpoints
- [x] Frontend UI/UX
- [x] Like/Unlike functionality
- [x] Submit review
- [x] Rating display
- [x] Responsive design
- [x] Animations & effects
- [x] Security measures
- [x] Error handling

Hệ thống reviews đã hoàn thiện và sẵn sàng sử dụng! 🎉
