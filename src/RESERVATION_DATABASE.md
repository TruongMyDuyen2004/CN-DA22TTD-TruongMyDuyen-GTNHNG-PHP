# Hệ thống Cơ sở dữ liệu Đặt bàn

## Tổng quan

Hệ thống đặt bàn bao gồm 4 bảng chính và các view/procedure hỗ trợ:

### 1. Bảng `tables` - Quản lý bàn ăn
Lưu trữ thông tin về các bàn trong nhà hàng.

**Cấu trúc:**
- `id`: ID bàn
- `table_number`: Số bàn (T01, T02, O01, V01, P01...)
- `capacity`: Sức chứa (số người)
- `location`: Vị trí (indoor, outdoor, vip, private_room)
- `is_available`: Trạng thái có sẵn
- `description`: Mô tả chi tiết

**Phân loại bàn:**
- **Indoor (T01-T07)**: Bàn trong nhà, 2-8 người
- **Outdoor (O01-O03)**: Bàn sân vườn, 4-6 người
- **VIP (V01-V02)**: Bàn VIP riêng tư, 4-6 người
- **Private Room (P01-P03)**: Phòng riêng, 10-20 người

### 2. Bảng `time_slots` - Khung giờ đặt bàn
Quản lý các khung giờ có thể đặt bàn.

**Cấu trúc:**
- `id`: ID khung giờ
- `time_slot`: Giờ (10:00, 10:30, 11:00...)
- `is_available`: Có cho phép đặt không
- `max_reservations`: Số lượng đặt bàn tối đa trong khung giờ

**Khung giờ:**
- **Buổi trưa**: 10:00 - 14:00 (8-12 đặt bàn/khung giờ)
- **Buổi tối**: 17:00 - 21:00 (6-12 đặt bàn/khung giờ)

### 3. Bảng `reservations` - Đơn đặt bàn
Lưu trữ thông tin đặt bàn của khách hàng.

**Cấu trúc:**
- `id`: ID đặt bàn
- `customer_id`: ID khách hàng (nếu đã đăng ký)
- `customer_name`: Tên khách hàng
- `email`: Email
- `phone`: Số điện thoại
- `reservation_date`: Ngày đặt
- `reservation_time`: Giờ đặt
- `number_of_guests`: Số khách
- `special_request`: Yêu cầu đặc biệt
- `status`: Trạng thái (pending, confirmed, cancelled, completed, no_show)
- `deposit_amount`: Số tiền đặt cọc
- `deposit_status`: Trạng thái đặt cọc
- `table_preference`: Ưu tiên vị trí bàn
- `occasion`: Dịp đặc biệt (sinh nhật, kỷ niệm...)
- `admin_note`: Ghi chú của admin
- `confirmed_by`: Admin xác nhận
- `confirmed_at`: Thời gian xác nhận
- `cancelled_reason`: Lý do hủy
- `cancelled_at`: Thời gian hủy

**Trạng thái:**
- `pending`: Chờ xác nhận
- `confirmed`: Đã xác nhận
- `cancelled`: Đã hủy
- `completed`: Đã hoàn thành
- `no_show`: Khách không đến

### 4. Bảng `reservation_tables` - Liên kết đặt bàn và bàn ăn
Quản lý việc gán bàn cho đơn đặt bàn.

**Cấu trúc:**
- `id`: ID
- `reservation_id`: ID đặt bàn
- `table_id`: ID bàn

## Views (Báo cáo)

### 1. `reservation_statistics` - Thống kê đặt bàn
Thống kê số lượng đặt bàn theo ngày.

```sql
SELECT * FROM reservation_statistics;
```

**Kết quả:**
- Ngày
- Tổng số đặt bàn
- Số đã xác nhận
- Số chờ xác nhận
- Số đã hủy
- Tổng số khách

### 2. `table_availability` - Tình trạng bàn
Xem tình trạng bàn hiện tại.

```sql
SELECT * FROM table_availability;
```

**Kết quả:**
- Số bàn
- Sức chứa
- Vị trí
- Có sẵn không
- Số đặt bàn hiện tại

## Stored Procedures

### 1. `check_availability` - Kiểm tra còn chỗ
Kiểm tra xem còn chỗ trong khung giờ cụ thể không.

```sql
CALL check_availability('2024-12-25', '18:00:00', 4);
```

**Tham số:**
- `p_date`: Ngày đặt
- `p_time`: Giờ đặt
- `p_guests`: Số khách

**Kết quả:**
- Khung giờ
- Số đặt bàn tối đa
- Số đặt bàn hiện tại
- Số chỗ còn trống
- Trạng thái (Available/Full)

### 2. `cancel_expired_reservations` - Hủy đặt bàn quá hạn
Tự động hủy các đặt bàn quá 2 giờ mà chưa xác nhận.

```sql
CALL cancel_expired_reservations();
```

## Triggers

### `before_reservation_update`
Tự động cập nhật `updated_at` khi có thay đổi.

## Events

### `auto_cancel_expired_reservations`
Chạy mỗi giờ để tự động hủy đặt bàn quá hạn.

## Cài đặt

### Bước 1: Chạy file SQL
```bash
mysql -u root -p ngon_gallery < config/setup_reservations.sql
```

### Bước 2: Kiểm tra
```sql
-- Xem danh sách bàn
SELECT * FROM tables;

-- Xem khung giờ
SELECT * FROM time_slots;

-- Xem đặt bàn mẫu
SELECT * FROM reservations;

-- Xem thống kê
SELECT * FROM reservation_statistics;
```

## Các truy vấn thường dùng

### 1. Tìm bàn trống cho ngày và giờ cụ thể
```sql
SELECT t.*
FROM tables t
WHERE t.capacity >= 4  -- Số khách
AND t.is_available = TRUE
AND t.id NOT IN (
    SELECT rt.table_id
    FROM reservation_tables rt
    JOIN reservations r ON rt.reservation_id = r.id
    WHERE r.reservation_date = '2024-12-25'
    AND r.reservation_time = '18:00:00'
    AND r.status IN ('confirmed', 'pending')
);
```

### 2. Lấy danh sách đặt bàn hôm nay
```sql
SELECT 
    r.*,
    GROUP_CONCAT(t.table_number) as tables
FROM reservations r
LEFT JOIN reservation_tables rt ON r.id = rt.reservation_id
LEFT JOIN tables t ON rt.table_id = t.id
WHERE r.reservation_date = CURDATE()
AND r.status IN ('confirmed', 'pending')
GROUP BY r.id
ORDER BY r.reservation_time;
```

### 3. Thống kê đặt bàn theo tháng
```sql
SELECT 
    YEAR(reservation_date) as year,
    MONTH(reservation_date) as month,
    COUNT(*) as total_reservations,
    SUM(number_of_guests) as total_guests,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
FROM reservations
GROUP BY YEAR(reservation_date), MONTH(reservation_date)
ORDER BY year DESC, month DESC;
```

### 4. Tìm khách hàng đặt bàn nhiều nhất
```sql
SELECT 
    customer_name,
    email,
    phone,
    COUNT(*) as total_reservations,
    SUM(number_of_guests) as total_guests
FROM reservations
WHERE status = 'completed'
GROUP BY customer_name, email, phone
ORDER BY total_reservations DESC
LIMIT 10;
```

### 5. Kiểm tra khung giờ nào đông khách nhất
```sql
SELECT 
    reservation_time,
    COUNT(*) as total_reservations,
    SUM(number_of_guests) as total_guests,
    AVG(number_of_guests) as avg_guests
FROM reservations
WHERE status IN ('confirmed', 'completed')
GROUP BY reservation_time
ORDER BY total_reservations DESC;
```

## Lưu ý

1. **Đặt cọc**: Với nhóm trên 10 người hoặc phòng riêng, nên yêu cầu đặt cọc
2. **Xác nhận**: Admin nên xác nhận đặt bàn trong vòng 2 giờ
3. **Hủy tự động**: Hệ thống tự động hủy đặt bàn quá 2 giờ chưa xác nhận
4. **Capacity**: Chọn bàn có sức chứa phù hợp với số khách
5. **Time slots**: Có thể điều chỉnh `max_reservations` theo nhu cầu

## Tích hợp với code PHP

Xem file `api/reservation.php` và `pages/reservation.php` để biết cách sử dụng database này trong ứng dụng.
