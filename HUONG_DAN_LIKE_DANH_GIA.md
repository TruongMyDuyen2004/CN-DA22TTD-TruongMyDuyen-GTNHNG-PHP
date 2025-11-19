# 🎯 Hướng dẫn Like đánh giá

## 📋 Tổng quan

Hệ thống Like đánh giá cho phép người dùng thể hiện sự đồng tình với các đánh giá của người khác. Mỗi người dùng có thể like hoặc unlike một đánh giá bất kỳ.

## 🚀 Cài đặt

### Bước 1: Chạy script thiết lập

Truy cập URL sau để thiết lập database:

```
http://your-domain.com/config/setup_review_likes.php
```

Script này sẽ:
- ✅ Thêm cột `likes_count` vào bảng `reviews`
- ✅ Thêm cột `is_approved` vào bảng `reviews`
- ✅ Thêm cột `updated_at` vào bảng `reviews`
- ✅ Tạo bảng `review_likes` để lưu thông tin like
- ✅ Đồng bộ số lượng likes hiện có

### Bước 2: Kiểm tra cấu trúc database

**Bảng `reviews`:**
```sql
- id (INT, PRIMARY KEY)
- customer_id (INT)
- menu_item_id (INT)
- rating (INT)
- comment (TEXT)
- likes_count (INT, DEFAULT 0)  ← Mới thêm
- is_approved (BOOLEAN, DEFAULT TRUE)  ← Mới thêm
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)  ← Mới thêm
```

**Bảng `review_likes`:**
```sql
- id (INT, PRIMARY KEY)
- review_id (INT, FOREIGN KEY)
- customer_id (INT, FOREIGN KEY)
- created_at (TIMESTAMP)
- UNIQUE KEY (review_id, customer_id)  ← Đảm bảo mỗi user chỉ like 1 lần
```

## 💡 Cách sử dụng

### Cho người dùng

1. **Like một đánh giá:**
   - Đăng nhập vào tài khoản
   - Xem chi tiết món ăn hoặc trang đánh giá
   - Click vào icon trái tim ❤️ bên dưới đánh giá
   - Icon sẽ đổi màu và số lượng likes tăng lên

2. **Unlike một đánh giá:**
   - Click lại vào icon trái tim đã like
   - Icon sẽ trở về trạng thái ban đầu
   - Số lượng likes giảm đi

3. **Xem số lượng likes:**
   - Số lượng likes hiển thị ngay bên cạnh icon trái tim
   - Ví dụ: ❤️ 15 (có 15 người đã like)

### Yêu cầu

- ✅ Phải đăng nhập mới có thể like
- ✅ Mỗi người chỉ có thể like 1 lần cho mỗi đánh giá
- ✅ Có thể unlike và like lại không giới hạn
- ✅ Không thể like đánh giá của chính mình (tùy chọn)

## 🔧 Cấu trúc kỹ thuật

### API Endpoints

**1. Like/Unlike đánh giá**
```
POST /api/review-like.php
```

**Request:**
```javascript
FormData {
  review_id: 123
}
```

**Response (Like):**
```json
{
  "success": true,
  "action": "liked",
  "likes_count": 16
}
```

**Response (Unlike):**
```json
{
  "success": true,
  "action": "unliked",
  "likes_count": 15
}
```

**Response (Error):**
```json
{
  "success": false,
  "message": "Vui lòng đăng nhập để thích đánh giá"
}
```

### Frontend (JavaScript)

**File:** `assets/js/reviews.js`

**Hàm chính:**
```javascript
async toggleLike(reviewId, button) {
    // Kiểm tra đăng nhập
    if (!this.isLoggedIn()) {
        alert('Vui lòng đăng nhập để thích đánh giá');
        return;
    }

    // Gửi request
    const formData = new FormData();
    formData.append('review_id', reviewId);

    const response = await fetch('api/review-like.php', {
        method: 'POST',
        body: formData
    });

    const data = await response.json();

    // Cập nhật UI
    if (data.success) {
        const likeCount = button.querySelector('.like-count');
        const icon = button.querySelector('i');

        likeCount.textContent = data.likes_count;

        if (data.action === 'liked') {
            button.classList.add('liked');
            icon.className = 'fas fa-heart';
        } else {
            button.classList.remove('liked');
            icon.className = 'far fa-heart';
        }
    }
}
```

### Backend (PHP)

**File:** `api/review-like.php`

**Logic:**
```php
// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['customer_id'])) {
    return error('Vui lòng đăng nhập');
}

// 2. Kiểm tra đã like chưa
$existing = checkExistingLike($review_id, $customer_id);

if ($existing) {
    // Unlike - Xóa like
    deleteLike($review_id, $customer_id);
    decrementLikesCount($review_id);
    return success('unliked', $new_likes_count);
} else {
    // Like - Thêm like
    insertLike($review_id, $customer_id);
    incrementLikesCount($review_id);
    return success('liked', $new_likes_count);
}
```

## 🎨 Giao diện

### HTML Structure

```html
<div class="review-item">
    <div class="review-header">...</div>
    <div class="review-content">...</div>
    <div class="review-footer">
        <button class="review-like-btn" data-review-id="123">
            <i class="far fa-heart"></i>
            <span class="like-count">15</span>
        </button>
        <button class="review-comment-btn">...</button>
    </div>
</div>
```

### CSS Styling

**File:** `assets/css/reviews.css`

```css
.review-like-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border: 2px solid #e5e7eb;
    background: white;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.review-like-btn:hover {
    border-color: #ef4444;
    background: #fef2f2;
}

.review-like-btn.liked {
    border-color: #ef4444;
    background: #fef2f2;
}

.review-like-btn.liked i {
    color: #ef4444;
}

.review-like-btn i {
    color: #9ca3af;
    transition: color 0.3s ease;
}

.like-count {
    font-weight: 600;
    color: #374151;
}
```

## 📊 Thống kê

### Query lấy top đánh giá được like nhiều nhất

```sql
SELECT 
    r.*,
    c.full_name,
    m.name as menu_item_name,
    r.likes_count
FROM reviews r
JOIN customers c ON r.customer_id = c.id
JOIN menu_items m ON r.menu_item_id = m.id
WHERE r.is_approved = TRUE
ORDER BY r.likes_count DESC
LIMIT 10;
```

### Query lấy người dùng like nhiều nhất

```sql
SELECT 
    c.id,
    c.full_name,
    COUNT(*) as total_likes
FROM review_likes rl
JOIN customers c ON rl.customer_id = c.id
GROUP BY c.id
ORDER BY total_likes DESC
LIMIT 10;
```

## 🔒 Bảo mật

### Ngăn chặn spam likes

1. **Unique constraint:** Mỗi user chỉ like 1 lần
```sql
UNIQUE KEY unique_like (review_id, customer_id)
```

2. **Session validation:** Kiểm tra đăng nhập
```php
if (!isset($_SESSION['customer_id'])) {
    return error('Unauthorized');
}
```

3. **Foreign key constraints:** Đảm bảo tính toàn vẹn dữ liệu
```sql
FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE
FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
```

## 🐛 Xử lý lỗi

### Lỗi thường gặp

1. **"Vui lòng đăng nhập để thích đánh giá"**
   - Nguyên nhân: Chưa đăng nhập
   - Giải pháp: Đăng nhập vào tài khoản

2. **"Thiếu thông tin đánh giá"**
   - Nguyên nhân: Không truyền review_id
   - Giải pháp: Kiểm tra data-review-id attribute

3. **"Duplicate entry"**
   - Nguyên nhân: Đã like rồi nhưng UI chưa cập nhật
   - Giải pháp: Refresh trang hoặc kiểm tra logic toggle

## 📱 Responsive Design

### Mobile
```css
@media (max-width: 768px) {
    .review-like-btn {
        padding: 0.4rem 0.8rem;
        font-size: 0.9rem;
    }
    
    .review-like-btn i {
        font-size: 1rem;
    }
}
```

## 🎯 Tính năng mở rộng

### Có thể thêm sau

1. **Thông báo khi có người like:**
   - Gửi notification cho tác giả đánh giá
   - Hiển thị số lượng likes mới

2. **Xem ai đã like:**
   - Modal hiển thị danh sách người đã like
   - Avatar và tên người dùng

3. **Sắp xếp theo likes:**
   - Thêm option "Nhiều likes nhất" trong dropdown sort
   - Query: `ORDER BY likes_count DESC`

4. **Giới hạn số lượng likes:**
   - Chống spam bằng rate limiting
   - Ví dụ: Tối đa 50 likes/ngày

5. **Analytics:**
   - Biểu đồ likes theo thời gian
   - Top đánh giá được like nhiều nhất
   - Người dùng tích cực nhất

## 📝 Testing

### Test cases

1. ✅ Like một đánh giá lần đầu
2. ✅ Unlike một đánh giá đã like
3. ✅ Like khi chưa đăng nhập (phải báo lỗi)
4. ✅ Like nhiều đánh giá khác nhau
5. ✅ Kiểm tra số lượng likes hiển thị đúng
6. ✅ Kiểm tra icon đổi màu khi like
7. ✅ Kiểm tra database constraint (unique)
8. ✅ Kiểm tra cascade delete

## 🎉 Kết luận

Hệ thống Like đánh giá giúp:
- ✅ Tăng tương tác người dùng
- ✅ Làm nổi bật đánh giá chất lượng
- ✅ Tạo cộng đồng đánh giá tích cực
- ✅ Cải thiện trải nghiệm người dùng

---

**Lưu ý:** Đảm bảo đã chạy script `config/setup_review_likes.php` trước khi sử dụng tính năng này!
