# 🔄 Luồng hoạt động hệ thống đánh giá

## 📋 Tổng quan

Hệ thống đánh giá đã được tích hợp hoàn chỉnh với luồng từ người dùng → database → admin.

## 🎯 Luồng hoạt động chi tiết

### 1️⃣ Người dùng đánh giá món ăn

#### Bước 1: Truy cập trang món ăn
```
Trang menu → Click món ăn → Trang chi tiết món ăn
URL: index.php?page=menu-item-detail&id={menu_item_id}
```

#### Bước 2: Viết đánh giá
1. Nhấn nút **"Viết đánh giá"** hoặc **"Đánh giá"**
2. Modal đánh giá hiển thị
3. Chọn số sao (1-5)
4. Viết nhận xét
5. Nhấn **"Gửi đánh giá"**

#### Bước 3: Gửi đánh giá
```javascript
// File: pages/menu-item-detail.php
async function submitReview(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    const response = await fetch('api/submit-review.php', {
        method: 'POST',
        body: formData
    });
}
```

### 2️⃣ API xử lý đánh giá

#### File: `api/submit-review.php`

**Kiểm tra:**
- ✅ Người dùng đã đăng nhập chưa?
- ✅ Dữ liệu đầy đủ chưa?
- ✅ Rating hợp lệ (1-5)?
- ✅ Đã đánh giá món này chưa?

**Lưu vào database:**
```sql
INSERT INTO reviews (
    customer_id, 
    menu_item_id, 
    rating, 
    comment, 
    is_approved
) VALUES (?, ?, ?, ?, TRUE)
```

**Trạng thái mặc định:**
- `is_approved = TRUE` → Hiển thị ngay lập tức
- Có thể đổi thành `FALSE` để yêu cầu admin duyệt

### 3️⃣ Hiển thị đánh giá cho người dùng

#### Trang menu (pages/menu.php)
```php
// Hiển thị rating tóm tắt
⭐⭐⭐⭐⭐ 4.5 (18 đánh giá)
```

#### Trang chi tiết món (pages/menu-item-detail.php)
```php
// Hiển thị đầy đủ:
- Thống kê tổng quan
- Phân bố theo sao
- Danh sách đánh giá chi tiết
- Tính năng like
- Sắp xếp và phân trang
```

### 4️⃣ Admin quản lý đánh giá

#### Truy cập trang admin
```
URL: admin/reviews.php
Yêu cầu: Đăng nhập admin
```

#### Tính năng admin:

**1. Xem thống kê:**
```
┌─────────────────────────────────────┐
│ Tổng đánh giá: 18                   │
│ Đã duyệt: 16                        │
│ Chờ duyệt: 2                        │
│ Điểm TB: 4.0 ⭐                     │
└─────────────────────────────────────┘
```

**2. Lọc đánh giá:**
- 📋 Tất cả
- ✅ Đã duyệt
- ⏳ Chờ duyệt

**3. Tìm kiếm:**
- Theo tên khách hàng
- Theo tên món ăn
- Theo nội dung đánh giá

**4. Hành động:**
- ✅ **Duyệt** → `is_approved = TRUE`
- ❌ **Từ chối** → `is_approved = FALSE`
- 🗑️ **Xóa** → Xóa vĩnh viễn

## 🗄️ Cấu trúc Database

### Bảng `reviews`
```sql
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    menu_item_id INT NOT NULL,
    order_id INT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT NOT NULL,
    is_approved BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id),
    
    INDEX idx_menu_item (menu_item_id),
    INDEX idx_customer (customer_id),
    INDEX idx_approved (is_approved),
    INDEX idx_created (created_at)
);
```

### Bảng `review_likes`
```sql
CREATE TABLE review_likes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    review_id INT NOT NULL,
    customer_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_like (review_id, customer_id)
);
```

## 📊 Sơ đồ luồng dữ liệu

```
┌─────────────────┐
│   Người dùng    │
│  (Khách hàng)   │
└────────┬────────┘
         │
         │ 1. Viết đánh giá
         ▼
┌─────────────────────────┐
│  pages/menu-item-       │
│     detail.php          │
│  (Modal đánh giá)       │
└────────┬────────────────┘
         │
         │ 2. Submit form
         ▼
┌─────────────────────────┐
│  api/submit-review.php  │
│  - Validate             │
│  - Check duplicate      │
│  - Insert DB            │
└────────┬────────────────┘
         │
         │ 3. Lưu vào DB
         ▼
┌─────────────────────────┐
│   Database (MySQL)      │
│   Bảng: reviews         │
│   is_approved = TRUE    │
└────────┬────────────────┘
         │
         ├─────────────────────────┐
         │                         │
         │ 4a. Hiển thị            │ 4b. Quản lý
         ▼                         ▼
┌──────────────────┐    ┌──────────────────┐
│  Trang người     │    │   Admin Panel    │
│     dùng         │    │  admin/reviews   │
│                  │    │                  │
│ - Menu           │    │ - Xem tất cả     │
│ - Chi tiết món   │    │ - Duyệt/Từ chối  │
│ - Rating         │    │ - Xóa            │
│ - Like           │    │ - Thống kê       │
└──────────────────┘    └──────────────────┘
```

## 🔐 Bảo mật

### 1. Kiểm tra đăng nhập
```php
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}
```

### 2. Validate dữ liệu
```php
// Rating phải từ 1-5
if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Đánh giá không hợp lệ']);
    exit;
}
```

### 3. Chống duplicate
```php
// Kiểm tra đã đánh giá chưa
$stmt = $conn->prepare("
    SELECT id FROM reviews 
    WHERE customer_id = ? AND menu_item_id = ?
");
```

### 4. SQL Injection
```php
// Sử dụng Prepared Statements
$stmt = $conn->prepare("INSERT INTO reviews ... VALUES (?, ?, ?, ?)");
$stmt->execute([$customer_id, $menu_item_id, $rating, $comment]);
```

### 5. XSS Protection
```php
// Escape HTML khi hiển thị
echo htmlspecialchars($review['comment']);
```

## 📱 API Endpoints

### 1. Gửi đánh giá
```
POST /api/submit-review.php

Body:
- menu_item_id: ID món ăn
- rating: Số sao (1-5)
- comment: Nội dung đánh giá

Response:
{
    "success": true,
    "message": "Cảm ơn bạn đã đánh giá!"
}
```

### 2. Lấy danh sách đánh giá
```
GET /api/get-reviews.php?menu_item_id={id}&page={page}&sort={sort}

Response:
{
    "success": true,
    "stats": {
        "total_reviews": 18,
        "avg_rating": 4.0,
        "star_5": 8,
        "star_4": 6,
        ...
    },
    "reviews": [...],
    "has_more": false
}
```

### 3. Like/Unlike đánh giá
```
POST /api/review-like.php

Body:
- review_id: ID đánh giá

Response:
{
    "success": true,
    "action": "liked",
    "likes_count": 5
}
```

## 🎯 Quy trình kiểm duyệt

### Tự động duyệt (Mặc định)
```php
// api/submit-review.php
is_approved = TRUE  // Hiển thị ngay
```

### Yêu cầu duyệt thủ công
```php
// Đổi thành:
is_approved = FALSE  // Chờ admin duyệt
```

### Admin duyệt
```php
// admin/reviews.php
UPDATE reviews SET is_approved = TRUE WHERE id = ?
```

## 📊 Thống kê và báo cáo

### Thống kê tổng quan
```sql
SELECT 
    COUNT(*) as total,
    AVG(rating) as avg_rating,
    SUM(CASE WHEN is_approved = TRUE THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN is_approved = FALSE THEN 1 ELSE 0 END) as pending
FROM reviews
```

### Thống kê theo món ăn
```sql
SELECT 
    m.name,
    COUNT(r.id) as total_reviews,
    AVG(r.rating) as avg_rating
FROM menu_items m
LEFT JOIN reviews r ON m.id = r.menu_item_id AND r.is_approved = TRUE
GROUP BY m.id
ORDER BY avg_rating DESC
```

### Top món ăn được đánh giá cao
```sql
SELECT 
    m.name,
    AVG(r.rating) as avg_rating,
    COUNT(r.id) as total_reviews
FROM menu_items m
JOIN reviews r ON m.id = r.menu_item_id
WHERE r.is_approved = TRUE
GROUP BY m.id
HAVING COUNT(r.id) >= 5
ORDER BY avg_rating DESC
LIMIT 10
```

## 🔧 Cấu hình

### Thay đổi chế độ kiểm duyệt

**File:** `api/submit-review.php`

```php
// Tự động duyệt
is_approved = TRUE

// Yêu cầu duyệt
is_approved = FALSE
```

### Giới hạn số đánh giá

```php
// Chỉ cho phép đánh giá 1 lần/món
$stmt = $conn->prepare("
    SELECT id FROM reviews 
    WHERE customer_id = ? AND menu_item_id = ?
");
```

### Thời gian chờ giữa các đánh giá

```php
// Chỉ cho phép đánh giá sau 24h
$stmt = $conn->prepare("
    SELECT id FROM reviews 
    WHERE customer_id = ? 
    AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
");
```

## 🎨 Tùy chỉnh giao diện

### Màu sắc sao
```css
.rating-stars i {
    color: #f59e0b; /* Vàng */
}
```

### Số sao hiển thị
```php
<?php for($i = 1; $i <= 5; $i++): ?>
    <!-- Thay 5 thành số khác nếu muốn -->
<?php endfor; ?>
```

## 📞 Liên kết quan trọng

### Người dùng:
- **Trang menu**: `index.php?page=menu`
- **Chi tiết món**: `index.php?page=menu-item-detail&id={id}`

### Admin:
- **Đăng nhập**: `admin/login.php`
- **Quản lý đánh giá**: `admin/reviews.php`
- **Dashboard**: `admin/index.php`

## ✅ Checklist triển khai

- [x] Cập nhật database (chạy `config/run_update_reviews.php`)
- [x] Tạo API submit review
- [x] Tạo API get reviews
- [x] Tạo API like review
- [x] Tạo trang chi tiết món với đánh giá
- [x] Hiển thị rating trên trang menu
- [x] Tạo trang admin quản lý đánh giá
- [x] Thêm đánh giá mẫu để test
- [x] Kiểm tra bảo mật
- [x] Test responsive
- [x] Viết tài liệu

## 🚀 Kết luận

Hệ thống đánh giá đã hoàn chỉnh với luồng:

```
Người dùng → Đánh giá → Database → Admin quản lý → Hiển thị công khai
```

Tất cả các thành phần đã được liên kết và hoạt động đồng bộ! ✨
