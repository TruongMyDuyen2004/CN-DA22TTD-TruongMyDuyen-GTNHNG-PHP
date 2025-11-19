# 📧 Hướng dẫn Trả lời Liên hệ từ Khách hàng

## Tổng quan
Hệ thống quản lý liên hệ cho phép admin xem và trả lời tin nhắn từ khách hàng một cách chuyên nghiệp, với khả năng gửi email tự động.

## Tính năng chính

### 1. Xem danh sách liên hệ
- **Thống kê tổng quan**: Hiển thị số lượng tin nhắn theo trạng thái
  - Tổng liên hệ
  - Chưa đọc (màu vàng)
  - Đã đọc
  - Đã trả lời (màu xanh)

- **Bộ lọc**: Lọc tin nhắn theo trạng thái
- **Danh sách**: Hiển thị đầy đủ thông tin liên hệ

### 2. Xem chi tiết tin nhắn
**Cách thực hiện:**
1. Click nút **👁️ Xem** trên tin nhắn muốn xem
2. Modal hiển thị:
   - Thông tin người gửi (tên, email, số điện thoại)
   - Nội dung tin nhắn đầy đủ
   - Thời gian gửi
   - Lịch sử phản hồi (nếu đã trả lời)

**Tự động:**
- Tin nhắn chưa đọc sẽ tự động chuyển sang trạng thái "Đã đọc"
- Màu nền vàng sẽ biến mất

### 3. Trả lời tin nhắn

#### Cách 1: Từ danh sách
1. Click nút **↩️ Trả lời** trên tin nhắn
2. Modal trả lời sẽ hiển thị

#### Cách 2: Từ chi tiết
1. Xem chi tiết tin nhắn
2. Click nút **Trả lời** ở cuối modal

#### Nội dung modal trả lời:
- **Thông tin người nhận**: Hiển thị tên và email khách hàng
- **Tin nhắn gốc**: Xem lại nội dung khách hàng đã gửi
- **Lịch sử phản hồi**: Nếu đã trả lời trước đó
- **Ô nhập phản hồi**: Textarea để nhập nội dung
- **Checkbox gửi email**: Tùy chọn gửi email thông báo

### 4. Gửi phản hồi

**Các bước:**
1. Nhập nội dung phản hồi vào ô textarea
2. Chọn/bỏ chọn "Gửi email thông báo cho khách hàng"
3. Click **📧 Gửi phản hồi**

**Khi gửi thành công:**
- Phản hồi được lưu vào database
- Trạng thái tin nhắn chuyển sang "Đã trả lời"
- Email được gửi tự động (nếu đã chọn)
- Trang tự động reload để cập nhật

### 5. Email tự động

**Nội dung email bao gồm:**
- Header đẹp mắt với logo Ngon Gallery
- Lời chào khách hàng (theo tên)
- Nội dung phản hồi từ admin
- Tin nhắn gốc của khách hàng
- Footer với thông tin liên hệ

**Định dạng:**
- HTML responsive
- Màu sắc thương hiệu (cam #FF6B35)
- Dễ đọc trên mọi thiết bị

## Trạng thái tin nhắn

| Trạng thái | Badge | Ý nghĩa |
|------------|-------|---------|
| **new** | 🟡 Chưa đọc | Tin nhắn mới, chưa được xem |
| **read** | 🔵 Đã đọc | Đã xem nhưng chưa trả lời |
| **replied** | 🟢 Đã trả lời | Đã gửi phản hồi cho khách hàng |

## Cấu trúc Database

### Bảng `contacts`
```sql
- id: ID tin nhắn
- name: Tên người gửi
- email: Email
- phone: Số điện thoại
- message: Nội dung
- status: Trạng thái (new/read/replied)
- admin_reply: Nội dung phản hồi
- replied_at: Thời gian trả lời
- replied_by: ID admin trả lời
- created_at: Thời gian tạo
```

### Bảng `contact_replies`
```sql
- id: ID phản hồi
- contact_id: ID tin nhắn
- admin_id: ID admin
- reply_message: Nội dung phản hồi
- sent_at: Thời gian gửi
```

## Thiết lập Database

**Chạy lệnh:**
```bash
php config/setup_contact_replies.php
```

**Hoặc import SQL:**
```bash
mysql -u username -p database_name < config/setup_contact_replies.sql
```

## API Endpoint

### POST `/api/send-contact-reply.php`

**Request:**
```json
{
  "contact_id": 123,
  "reply_message": "Nội dung phản hồi...",
  "send_email": true
}
```

**Response thành công:**
```json
{
  "success": true,
  "message": "Đã gửi phản hồi thành công",
  "email_sent": true
}
```

**Response lỗi:**
```json
{
  "success": false,
  "message": "Thông báo lỗi"
}
```

## Tính năng nổi bật

### ✅ Giao diện thân thiện
- Modal hiện đại, dễ sử dụng
- Animation mượt mà
- Responsive trên mọi thiết bị

### ✅ Tự động hóa
- Tự động đánh dấu đã đọc
- Tự động gửi email
- Tự động cập nhật trạng thái

### ✅ Lịch sử đầy đủ
- Lưu tất cả phản hồi
- Hiển thị lịch sử trả lời
- Theo dõi admin đã trả lời

### ✅ Email chuyên nghiệp
- Template đẹp mắt
- Bao gồm tin nhắn gốc
- Thông tin liên hệ đầy đủ

## Phím tắt

- **ESC**: Đóng modal
- **Click ngoài modal**: Đóng modal

## Lưu ý quan trọng

1. **Quyền truy cập**: Chỉ admin đã đăng nhập mới có thể trả lời
2. **Email**: Cần cấu hình mail server để gửi email
3. **Validation**: Nội dung phản hồi không được để trống
4. **Lịch sử**: Có thể trả lời nhiều lần cho cùng một tin nhắn

## Xử lý lỗi

### Email không gửi được
- Kiểm tra cấu hình mail server
- Kiểm tra email khách hàng có hợp lệ
- Phản hồi vẫn được lưu vào database

### Không thể gửi phản hồi
- Kiểm tra kết nối database
- Kiểm tra quyền admin
- Xem console log để debug

## Tips sử dụng

1. **Trả lời nhanh**: Sử dụng template có sẵn cho các câu hỏi thường gặp
2. **Cá nhân hóa**: Luôn gọi tên khách hàng trong phản hồi
3. **Chuyên nghiệp**: Kiểm tra chính tả trước khi gửi
4. **Theo dõi**: Đánh dấu đã đọc ngay khi xem tin nhắn
5. **Ưu tiên**: Trả lời tin nhắn mới trước

## Tích hợp

Hệ thống tích hợp với:
- ✅ Admin dashboard
- ✅ Notification system
- ✅ Email system
- ✅ User management

## Cập nhật trong tương lai

- [ ] Template phản hồi có sẵn
- [ ] Gán tin nhắn cho admin cụ thể
- [ ] Thống kê thời gian phản hồi
- [ ] Export báo cáo
- [ ] Tìm kiếm và lọc nâng cao
- [ ] Đính kèm file trong phản hồi

---

**Phát triển bởi**: Ngon Gallery Team  
**Phiên bản**: 1.0  
**Cập nhật**: 2024
