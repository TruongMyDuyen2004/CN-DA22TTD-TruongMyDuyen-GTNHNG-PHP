# 🌟 Hướng dẫn hiển thị đánh giá trên trang Menu

## ✅ Đã cập nhật thành công!

Trang menu (`pages/menu.php`) đã được cập nhật để hiển thị đánh giá ở mỗi món ăn.

## 🎯 Tính năng mới

### 1. Hiển thị rating trên mỗi món ăn
- ⭐ Số sao trung bình (1-5 sao)
- 📊 Tổng số đánh giá
- 🎨 Biểu tượng sao đầy đủ/nửa/rỗng

### 2. Link đến trang chi tiết
- 🔗 Click vào ảnh món ăn → Trang chi tiết
- 🔗 Click vào tên món ăn → Trang chi tiết
- 🔗 Nút "Chi tiết" → Trang chi tiết

### 3. Hiển thị ảnh món ăn
- 🖼️ Hiển thị ảnh thật nếu có
- 🎨 Hiển thị icon emoji nếu chưa có ảnh
- ✨ Hiệu ứng hover zoom khi di chuột

### 4. Nút hành động
- 📝 Nút "Chi tiết" - Xem thông tin đầy đủ
- 🛒 Nút "Thêm vào giỏ" - Thêm nhanh vào giỏ hàng

## 📊 Cách hoạt động

### Hiển thị rating:

```php
// Lấy thống kê đánh giá cho mỗi món
$stmt_review = $conn->prepare("
    SELECT 
        COUNT(*) as total_reviews,
        AVG(rating) as avg_rating
    FROM reviews 
    WHERE menu_item_id = ? AND is_approved = TRUE
");
```

### Hiển thị sao:
- ⭐⭐⭐⭐⭐ - 5 sao đầy
- ⭐⭐⭐⭐☆ - 4 sao
- ⭐⭐⭐⭐🌟 - 4.5 sao (nửa sao)

### Chỉ hiển thị khi có đánh giá:
```php
<?php if ($total_reviews > 0): ?>
    <!-- Hiển thị rating -->
<?php endif; ?>
```

## 🎨 Giao diện

### Card món ăn bao gồm:

1. **Ảnh món ăn** (có thể click)
   - Hiệu ứng zoom khi hover
   - Gradient background nếu chưa có ảnh

2. **Tên món ăn** (có thể click)
   - Màu thay đổi khi hover
   - Link đến trang chi tiết

3. **Giá tiền**
   - Hiển thị rõ ràng
   - Format: 65.000đ

4. **Rating** (nếu có)
   - Sao màu vàng
   - Điểm số và số lượng đánh giá
   - Ví dụ: ⭐⭐⭐⭐⭐ **4.5** (18 đánh giá)

5. **Mô tả ngắn**
   - 1-2 dòng giới thiệu

6. **Trạng thái**
   - ✅ Còn món (màu xanh)
   - ❌ Hết món (màu đỏ)

7. **Nút hành động**
   - 📝 Chi tiết
   - 🛒 Thêm vào giỏ

## 📱 Responsive

### Desktop (> 768px):
- Grid 3 cột
- Nút nằm ngang

### Mobile (< 768px):
- Grid 1 cột
- Nút xếp dọc
- Ảnh tự động điều chỉnh

## 🎯 Ví dụ hiển thị

```
┌─────────────────────────────┐
│      [Ảnh món ăn]          │
├─────────────────────────────┤
│ Phở bò đặc biệt    65.000đ │
│ ⭐⭐⭐⭐⭐ 4.5 (18 đánh giá)  │
│                             │
│ Phở bò truyền thống với     │
│ nước dùng hầm xương...      │
│                             │
│ ✅ Còn món                  │
│ [Chi tiết] [Thêm vào giỏ]  │
└─────────────────────────────┘
```

## 🔧 Tùy chỉnh

### Thay đổi số sao hiển thị:

Trong `pages/menu.php`, tìm:
```php
<?php for($i = 1; $i <= 5; $i++): ?>
```

Thay `5` thành số sao tối đa bạn muốn.

### Thay đổi màu sao:

Trong CSS:
```css
.menu-item-rating .rating-stars i {
    color: #f59e0b; /* Màu vàng */
}
```

### Ẩn rating nếu ít hơn X đánh giá:

```php
<?php if ($total_reviews >= 3): ?>
    <!-- Chỉ hiển thị khi có từ 3 đánh giá trở lên -->
<?php endif; ?>
```

## 🚀 Tính năng bổ sung

### Sắp xếp theo rating:

Thêm vào form tìm kiếm:
```php
<select name="sort">
    <option value="">Mặc định</option>
    <option value="rating_desc">Đánh giá cao nhất</option>
    <option value="rating_asc">Đánh giá thấp nhất</option>
</select>
```

### Lọc theo số sao:

```php
<select name="min_rating">
    <option value="">Tất cả</option>
    <option value="4">Từ 4 sao trở lên</option>
    <option value="3">Từ 3 sao trở lên</option>
</select>
```

### Badge "Được yêu thích":

```php
<?php if ($avg_rating >= 4.5 && $total_reviews >= 10): ?>
    <span class="badge-popular">🔥 Được yêu thích</span>
<?php endif; ?>
```

## 📊 Hiệu suất

### Tối ưu query:

Hiện tại: Query riêng cho mỗi món (có thể chậm với nhiều món)

Cải thiện: Lấy tất cả rating một lần:
```php
// Lấy tất cả rating trước
$stmt = $conn->query("
    SELECT 
        menu_item_id,
        COUNT(*) as total_reviews,
        AVG(rating) as avg_rating
    FROM reviews 
    WHERE is_approved = TRUE
    GROUP BY menu_item_id
");
$ratings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $ratings[$row['menu_item_id']] = $row;
}

// Sau đó dùng trong loop
$review_stats = $ratings[$item['id']] ?? ['total_reviews' => 0, 'avg_rating' => 0];
```

## 🎨 CSS Classes

### Classes có sẵn:

- `.menu-item` - Container món ăn
- `.menu-item-link` - Link đến chi tiết
- `.menu-item-image` - Container ảnh
- `.menu-item-icon` - Icon emoji
- `.menu-item-rating` - Container rating
- `.rating-stars` - Container sao
- `.rating-text` - Text đánh giá
- `.menu-item-footer` - Footer với nút
- `.menu-item-actions` - Container nút

## 🐛 Xử lý lỗi

### Không hiển thị rating?

1. Kiểm tra có đánh giá trong database:
```sql
SELECT * FROM reviews WHERE menu_item_id = 1 AND is_approved = TRUE;
```

2. Kiểm tra query có lỗi không:
```php
var_dump($review_stats);
```

3. Xóa cache trình duyệt (Ctrl + F5)

### Ảnh không hiển thị?

1. Kiểm tra đường dẫn ảnh trong database
2. Đảm bảo file ảnh tồn tại
3. Kiểm tra quyền truy cập folder

## 📞 Liên kết

- **Trang menu**: `index.php?page=menu`
- **Trang chi tiết món**: `index.php?page=menu-item-detail&id={id}`
- **Admin quản lý đánh giá**: `admin/reviews.php`

## ✨ Kết quả

Giờ đây khách hàng có thể:
- ✅ Xem rating ngay trên trang menu
- ✅ Click vào món để xem chi tiết và đánh giá đầy đủ
- ✅ Quyết định nhanh dựa trên đánh giá của người khác
- ✅ Thêm món vào giỏ ngay từ trang menu

Trải nghiệm mua sắm được cải thiện đáng kể! 🎉
