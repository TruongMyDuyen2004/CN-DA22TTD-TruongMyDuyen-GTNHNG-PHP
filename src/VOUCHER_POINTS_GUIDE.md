# 🎟️ Hệ thống Voucher & Tích điểm

## Tổng quan

Hệ thống bao gồm 2 tính năng chính:
1. **Voucher/Coupon** - Mã giảm giá
2. **Tích điểm** - Loyalty points

---

## 🚀 Cài đặt

Chạy file setup để tạo database:
```
http://localhost/DUYENCN/setup-voucher-system.php
```

---

## 🎟️ Hệ thống Voucher

### Tính năng
- Giảm giá theo % hoặc số tiền cố định
- Giới hạn giảm tối đa (cho loại %)
- Đơn hàng tối thiểu
- Giới hạn số lần sử dụng tổng
- Giới hạn số lần/người dùng
- Thời hạn sử dụng

### Quản lý (Admin)
- Truy cập: `admin/vouchers.php`
- Tạo, sửa, xóa voucher
- Bật/tắt voucher
- Xem thống kê sử dụng

### Voucher mẫu đã tạo
| Mã | Mô tả | Giảm |
|---|---|---|
| WELCOME10 | Chào mừng thành viên mới | 10% (max 50K) |
| FREESHIP | Miễn phí vận chuyển | 30.000đ |
| SALE20 | Giảm 20% cuối tuần | 20% (max 100K) |
| VIP50K | Ưu đãi VIP | 50.000đ |

---

## ⭐ Hệ thống Tích điểm

### Cách tích điểm
- Mỗi **1.000đ** đơn hàng = **1 điểm**
- Hạng cao hơn = bonus điểm nhiều hơn

### Cách đổi điểm
- **1 điểm** = **100đ** giảm giá
- Tối thiểu 100 điểm để đổi
- Tối đa 50% giá trị đơn hàng

### Hạng thành viên

| Hạng | Điểm cần | Bonus điểm |
|---|---|---|
| 🥉 Bronze | Mặc định | 0% |
| 🥈 Silver | 1.000 | +5% |
| 🥇 Gold | 5.000 | +10% |
| 💎 Platinum | 15.000 | +15% |
| 👑 Diamond | 50.000 | +25% |

### Quản lý (Admin)
- Truy cập: `admin/points.php`
- Xem điểm của khách hàng
- Điều chỉnh điểm (cộng/trừ)
- Cấu hình quy đổi điểm

---

## 📱 Trang khách hàng

- **Xem voucher**: `?page=vouchers`
- **Xem điểm**: `?page=my-points`

---

## 🔌 API

### Voucher API (`api/voucher.php`)

```javascript
// Kiểm tra voucher
fetch('api/voucher.php', {
    method: 'POST',
    body: 'action=check&code=SALE20&order_total=200000'
})

// Áp dụng voucher
fetch('api/voucher.php', {
    method: 'POST', 
    body: 'action=apply&code=SALE20&order_total=200000'
})

// Xóa voucher
fetch('api/voucher.php', {
    method: 'POST',
    body: 'action=remove'
})

// Lấy danh sách voucher khả dụng
fetch('api/voucher.php?action=list&order_total=200000')
```

### Points API (`api/points.php`)

```javascript
// Lấy thông tin điểm
fetch('api/points.php?action=get')

// Tính điểm có thể dùng
fetch('api/points.php', {
    method: 'POST',
    body: 'action=calculate&order_total=200000&points=100'
})

// Áp dụng điểm
fetch('api/points.php', {
    method: 'POST',
    body: 'action=apply&order_total=200000&points=100'
})

// Lấy lịch sử điểm
fetch('api/points.php?action=history&limit=20')
```

---

## 📊 Database Schema

### Bảng `vouchers`
- id, code, name, description
- discount_type (percent/fixed)
- discount_value, max_discount
- min_order_value
- usage_limit, usage_per_user, used_count
- start_date, end_date, is_active

### Bảng `voucher_usage`
- id, voucher_id, customer_id, order_id
- discount_amount, used_at

### Bảng `customer_points`
- id, customer_id
- total_points, available_points, used_points
- tier (bronze/silver/gold/platinum/diamond)

### Bảng `point_transactions`
- id, customer_id, type, points
- balance_before, balance_after
- order_id, description

### Bảng `point_settings`
- Cấu hình quy đổi điểm
- Ngưỡng hạng thành viên
- Bonus theo hạng

---

## ✅ Checklist tích hợp Checkout

Để tích hợp vào trang checkout, cần:

1. Thêm form nhập mã voucher
2. Thêm slider/input chọn số điểm dùng
3. Hiển thị giảm giá từ voucher + điểm
4. Khi đặt hàng thành công:
   - Ghi nhận voucher đã dùng
   - Trừ điểm đã dùng
   - Tích điểm mới cho đơn hàng

---

## 🎯 Ví dụ sử dụng

**Khách hàng Gold đặt đơn 500.000đ:**
- Dùng voucher SALE20: -100.000đ (20% max 100K)
- Dùng 200 điểm: -20.000đ
- Tổng thanh toán: 380.000đ
- Điểm tích được: 380 + 38 (bonus 10%) = 418 điểm
