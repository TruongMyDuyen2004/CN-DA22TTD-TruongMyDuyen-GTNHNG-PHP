# 📖 Hướng dẫn xem đánh giá cho người dùng

## 🎯 Các cách xem đánh giá

### 1️⃣ Từ Menu chính (Header)
```
Nhấn vào: ⭐ Đánh giá (Reviews)
→ Xem tất cả đánh giá của nhà hàng
```

**Vị trí**: Thanh menu phía trên, giữa "Thực đơn" và "Liên hệ"

---

### 2️⃣ Từ Trang chủ
```
Cuộn xuống → Phần "Đánh giá từ khách hàng"
→ Nhấn nút "Xem tất cả đánh giá"
```

**Hiển thị**: 
- 3 đánh giá mới nhất
- Nút "Xem tất cả đánh giá" ở cuối phần

---

### 3️⃣ Từ Trang Menu (Thực đơn)
```
Vào trang Menu → Tìm món ăn
→ Nhấn vào phần rating (⭐⭐⭐⭐⭐)
→ Popup hiển thị tất cả đánh giá của món đó
```

**Đặc điểm**:
- Mỗi món có rating với số sao và số lượng đánh giá
- Ví dụ: "⭐ 4.5 (12 đánh giá)"
- Click vào phần này để xem chi tiết
- Có icon mắt (👁️) để biết có thể click

---

### 4️⃣ Từ Trang chi tiết món ăn
```
Click vào tên món → Trang chi tiết
→ Cuộn xuống phần "Đánh giá"
```

**Hiển thị**:
- Tất cả đánh giá của món đó
- Có thể viết đánh giá mới (nếu đã đăng nhập)

---

## 📊 Trang "Tất cả đánh giá" có gì?

### Thống kê tổng quan
- ⭐ Điểm trung bình (ví dụ: 4.5/5)
- 📊 Biểu đồ phân bố sao (5⭐, 4⭐, 3⭐, 2⭐, 1⭐)
- 📝 Tổng số đánh giá

### Bộ lọc
1. **Lọc theo số sao**: Xem chỉ đánh giá 5⭐, 4⭐, v.v.
2. **Lọc theo món ăn**: Xem đánh giá của món cụ thể
3. **Sắp xếp**: 
   - Mới nhất
   - Cũ nhất
   - Điểm cao nhất
   - Điểm thấp nhất

### Mỗi đánh giá hiển thị
- 👤 Tên khách hàng
- ⭐ Số sao đánh giá
- 📅 Ngày đánh giá
- 🍜 Món ăn được đánh giá (có link đến chi tiết món)
- 💬 Nội dung đánh giá
- 🖼️ Hình ảnh món ăn (nếu có)
- 👍 Nút like

---

## 🎨 Giao diện trực quan

### Trên trang Menu:
```
┌─────────────────────────────────────┐
│  🍜 Phở Bò                          │
│  ⭐⭐⭐⭐⭐ 4.5 (12 đánh giá) 👁️    │
│  Phở bò truyền thống...             │
│  💰 85,000đ                         │
│  [🛒 Thêm vào giỏ]                  │
└─────────────────────────────────────┘
        ↑ Click vào đây để xem đánh giá
```

### Trên trang chủ:
```
┌─────────────────────────────────────┐
│  ⭐ Đánh giá từ khách hàng          │
│                                     │
│  [Đánh giá 1] [Đánh giá 2] [...]   │
│                                     │
│  [Xem tất cả đánh giá →]           │
└─────────────────────────────────────┘
        ↑ Click vào đây
```

---

## 💡 Lưu ý

1. **Không cần đăng nhập** để xem đánh giá
2. **Cần đăng nhập** để viết đánh giá mới
3. Chỉ hiển thị đánh giá **đã được duyệt** bởi admin
4. Có thể **like** đánh giá (nếu đã đăng nhập)
5. Click vào **tên món ăn** trong đánh giá để xem chi tiết món

---

## 🔗 Đường dẫn trực tiếp

```
Tất cả đánh giá: index.php?page=all-reviews
Chi tiết món: index.php?page=menu-item-detail&id=[ID]
```

---

## 📱 Responsive

Giao diện tự động điều chỉnh trên:
- 💻 Desktop
- 📱 Mobile
- 📲 Tablet

Tất cả tính năng hoạt động mượt mà trên mọi thiết bị!
