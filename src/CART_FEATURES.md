# Tính năng Giỏ hàng nâng cao

## Các tính năng đã thêm

### 1. API Giỏ hàng (api/cart.php)
- ✅ Thêm món vào giỏ (AJAX)
- ✅ Cập nhật số lượng món
- ✅ Xóa món khỏi giỏ
- ✅ Lấy số lượng món trong giỏ
- ✅ Lấy danh sách món trong giỏ

### 2. JavaScript Cart Class (assets/js/cart.js)
- ✅ Quản lý giỏ hàng với AJAX
- ✅ Cập nhật số lượng món trên header tự động
- ✅ Thêm món không cần reload trang
- ✅ Hiển thị thông báo đẹp mắt
- ✅ Mini cart popup

### 3. Mini Cart
- ✅ Hiển thị giỏ hàng dạng popup
- ✅ Xem nhanh các món đã thêm
- ✅ Tổng tiền tạm tính
- ✅ Link nhanh đến giỏ hàng và thanh toán
- ✅ Animation mượt mà

### 4. Cart Badge
- ✅ Hiển thị số lượng món trên icon giỏ hàng
- ✅ Cập nhật real-time
- ✅ Thiết kế đẹp mắt

### 5. Quick Add Button
- ✅ Nút "Thêm vào giỏ" trên mỗi món
- ✅ Thêm nhanh không cần confirm
- ✅ Thông báo thành công/lỗi
- ✅ Icon Font Awesome

### 6. Notification System
- ✅ Thông báo thành công (màu xanh)
- ✅ Thông báo lỗi (màu đỏ)
- ✅ Tự động ẩn sau 3 giây
- ✅ Animation slide in/out

## Cách sử dụng

### Khách hàng

1. **Xem mini cart**: Click vào icon giỏ hàng trên header
2. **Thêm món**: Click nút "Thêm vào giỏ" trên món ăn
3. **Cập nhật số lượng**: Thay đổi số trong ô input (tự động cập nhật)
4. **Xóa món**: Click nút "Xóa" trên món trong giỏ
5. **Thanh toán**: Click "Đặt hàng" trong giỏ hàng

### Developer

#### Thêm món vào giỏ từ JavaScript:
```javascript
addToCart(itemId, itemName);
```

#### Hiển thị mini cart:
```javascript
showMiniCart();
```

#### Cập nhật số lượng:
```javascript
updateCartQuantity(cartId, quantity);
```

#### Xóa món:
```javascript
removeFromCart(cartId);
```

## API Endpoints

### POST api/cart.php
**Action: add**
```
menu_item_id: ID món ăn
quantity: Số lượng (mặc định 1)
note: Ghi chú (tùy chọn)
```

**Action: update**
```
cart_id: ID trong giỏ
quantity: Số lượng mới
```

**Action: remove**
```
cart_id: ID trong giỏ
```

### GET api/cart.php
**Action: get_count**
- Trả về tổng số món trong giỏ

**Action: get_items**
- Trả về danh sách món và tổng tiền

## Response Format

```json
{
    "success": true,
    "message": "Đã thêm món vào giỏ hàng",
    "cart_count": 5,
    "subtotal": 250000
}
```

## Bảo mật

- ✅ Kiểm tra đăng nhập trước khi thao tác
- ✅ Validate dữ liệu đầu vào
- ✅ Prepared statements để tránh SQL injection
- ✅ Kiểm tra món còn hàng trước khi thêm

## Tính năng nổi bật

1. **UX tốt**: Không cần reload trang, phản hồi nhanh
2. **Real-time**: Cập nhật số lượng ngay lập tức
3. **Responsive**: Hoạt động tốt trên mobile
4. **Animation**: Hiệu ứng mượt mà, chuyên nghiệp
5. **Error handling**: Xử lý lỗi đầy đủ với thông báo rõ ràng

## Browser Support

- Chrome/Edge: ✅
- Firefox: ✅
- Safari: ✅
- Mobile browsers: ✅

## Dependencies

- Font Awesome 6.4 (cho icons)
- Fetch API (built-in modern browsers)
- ES6 JavaScript

## Cấu trúc file

```
├── api/
│   └── cart.php          # API xử lý giỏ hàng
├── assets/
│   ├── css/
│   │   └── style.css     # CSS cho mini cart, notification
│   └── js/
│       └── cart.js       # JavaScript cart class
├── pages/
│   ├── cart.php          # Trang giỏ hàng
│   ├── checkout.php      # Trang thanh toán
│   └── menu.php          # Trang thực đơn (có nút thêm)
└── includes/
    └── header.php        # Header với cart badge
```

## Troubleshooting

**Không thêm được vào giỏ?**
- Kiểm tra đã đăng nhập chưa
- Kiểm tra món còn hàng không
- Xem console log để debug

**Số lượng không cập nhật?**
- Kiểm tra file cart.js đã load chưa
- Kiểm tra session còn hiệu lực không

**Mini cart không hiện?**
- Kiểm tra CSS đã load đầy đủ
- Kiểm tra không có lỗi JavaScript

## Future Enhancements

- [ ] Lưu giỏ hàng vào localStorage
- [ ] Thêm voucher/mã giảm giá
- [ ] Gợi ý món ăn liên quan
- [ ] Lưu món yêu thích
- [ ] Đặt lại đơn hàng cũ
