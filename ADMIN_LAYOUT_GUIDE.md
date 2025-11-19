# 📐 Hướng dẫn Bố cục Admin

## ✅ Đã tối ưu toàn bộ layout admin

### 📦 File CSS đã tạo:

1. **admin-compact.css** - Tối ưu cơ bản
2. **admin-global-compact.css** - Tối ưu toàn diện (MỚI)

### 🎯 Cách áp dụng cho tất cả trang admin:

Thêm dòng này vào `<head>` của mọi trang admin:

```html
<link rel="stylesheet" href="../assets/css/admin-global-compact.css">
```

### 📄 Thứ tự load CSS đúng:

```html
<link rel="stylesheet" href="../assets/css/admin.css">
<link rel="stylesheet" href="../assets/css/admin-unified.css">
<link rel="stylesheet" href="../assets/css/admin-orange-theme.css">
<link rel="stylesheet" href="../assets/css/admin-compact.css">
<link rel="stylesheet" href="../assets/css/admin-global-compact.css">
```

### ✨ Các cải tiến:

#### 📏 Spacing (Khoảng cách)
- Main content padding: 1rem
- Card padding: 1rem
- Form group margin: 0.875rem
- Table cell padding: 0.5rem x 0.75rem

#### 📊 Typography (Chữ)
- Page header: 1.375rem
- Card header: 0.9375rem
- Table header: 0.75rem
- Table body: 0.8125rem
- Form label: 0.8125rem
- Button: 0.8125rem

#### 🎨 Components
- Border radius: 6-8px
- Border width: 1px
- Shadow: Minimal (0 1px 3px)
- Transitions: 0.2s ease

#### 📱 Responsive
- Desktop: Full layout
- < 1400px: Compact
- < 768px: Mobile optimized

### 🔧 Áp dụng nhanh:

Chạy script này để thêm CSS vào tất cả trang admin:

```bash
# Tìm tất cả file PHP trong admin/
# Thêm dòng CSS vào <head>
```

Hoặc thêm thủ công vào các file:
- admin/index.php
- admin/customers.php
- admin/orders.php
- admin/reservations.php
- admin/reviews.php
- admin/contacts.php
- admin/menu-manage.php
- admin/settings.php

### 📋 Checklist:

- [x] Tạo admin-global-compact.css
- [ ] Thêm vào admin/index.php
- [ ] Thêm vào admin/customers.php
- [ ] Thêm vào admin/orders.php
- [ ] Thêm vào admin/reservations.php
- [ ] Thêm vào admin/reviews.php
- [ ] Thêm vào admin/contacts.php
- [ ] Thêm vào admin/menu-manage.php
- [x] Thêm vào admin/settings.php (đã có)

### 🎯 Kết quả mong đợi:

- ✅ Không còn khoảng trắng dư thừa
- ✅ Bố cục cân đối, hài hòa
- ✅ Dễ đọc, dễ sử dụng
- ✅ Hiện đại, chuyên nghiệp
- ✅ Responsive tốt

### 💡 Tips:

1. Load CSS theo thứ tự từ general → specific
2. File global-compact.css phải load cuối cùng
3. Sử dụng !important để override
4. Test trên nhiều màn hình khác nhau

---

**Cập nhật:** Đã tạo file admin-global-compact.css với tối ưu toàn diện
