# ❤️ Hệ thống Like Đánh giá

## 🚀 Cài đặt nhanh

### Bước 1: Chạy setup
```
http://your-domain.com/config/setup_review_likes.php
```

### Bước 2: Kiểm tra
```
http://your-domain.com/test-review-likes.php
```

## ✨ Tính năng

- ✅ Like/Unlike đánh giá
- ✅ Mỗi user chỉ like 1 lần
- ✅ Hiển thị số lượng likes
- ✅ Icon trái tim đổi màu khi like
- ✅ Animation mượt mà
- ✅ Yêu cầu đăng nhập

## 📁 Files quan trọng

```
api/review-like.php          → API Like/Unlike
api/get-reviews.php          → API lấy reviews (có thông tin like)
assets/js/reviews.js         → JavaScript xử lý like
assets/css/reviews.css       → CSS styling
config/setup_review_likes.php → Script thiết lập
```

## 💻 Sử dụng

### Người dùng
1. Đăng nhập
2. Xem chi tiết món ăn
3. Click icon ❤️ để like
4. Click lại để unlike

### Developer
```javascript
// Toggle like
reviewSystem.toggleLike(reviewId, button);

// Kiểm tra đã like chưa
review.is_liked_by_user // true/false

// Số lượng likes
review.likes_count // number
```

## 🗄️ Database

### Bảng `reviews`
```sql
likes_count INT DEFAULT 0
is_approved BOOLEAN DEFAULT TRUE
updated_at TIMESTAMP
```

### Bảng `review_likes`
```sql
id, review_id, customer_id, created_at
UNIQUE(review_id, customer_id)
```

## 📊 API

### Like/Unlike
```
POST /api/review-like.php
Body: { review_id: 123 }

Response: {
  "success": true,
  "action": "liked",
  "likes_count": 16
}
```

## 🎨 UI States

- **Chưa like:** Icon outline, màu xám
- **Đã like:** Icon solid, màu đỏ, background hồng
- **Hover:** Scale up, border đổi màu

## 📖 Tài liệu đầy đủ

Xem file `HUONG_DAN_LIKE_DANH_GIA.md` để biết chi tiết.

---

**Lưu ý:** Phải chạy setup trước khi sử dụng!
