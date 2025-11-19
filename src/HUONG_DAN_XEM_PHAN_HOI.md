# 📬 Hướng dẫn Xem Phản hồi từ Admin

## Tổng quan
Người dùng có thể tra cứu và xem phản hồi từ admin cho các tin nhắn liên hệ đã gửi.

## Tính năng

### 1. Gửi tin nhắn liên hệ
**Trang:** `index.php?page=contact`

- Điền form liên hệ với thông tin:
  - Họ tên
  - Email (quan trọng để tra cứu sau này)
  - Số điện thoại (tùy chọn)
  - Nội dung tin nhắn
- Click "Gửi tin nhắn"
- Hệ thống lưu vào database với trạng thái "new"

### 2. Tra cứu tin nhắn
**Trang:** `index.php?page=my-contacts`

**Cách truy cập:**
- Click menu "Tin nhắn của tôi" trên header
- Hoặc click link trong thông báo sau khi gửi tin nhắn thành công
- Hoặc click banner thông tin trên trang liên hệ

**Cách tra cứu:**
1. Nhập email đã sử dụng khi gửi tin nhắn
2. Click nút "Tìm kiếm"
3. Hệ thống hiển thị tất cả tin nhắn của email đó

### 3. Xem chi tiết tin nhắn

Mỗi tin nhắn hiển thị:

**Thông tin cơ bản:**
- ID tin nhắn (#3, #5, ...)
- Ngày giờ gửi
- Trạng thái:
  - 🟡 **Mới**: Chưa được admin xem
  - 🔵 **Đã xem**: Admin đã đọc nhưng chưa trả lời
  - 🟢 **Đã trả lời**: Admin đã phản hồi

**Nội dung:**
- Tin nhắn gốc của bạn
- Phản hồi từ admin (nếu có)
- Thời gian admin trả lời
- Tên admin đã trả lời

### 4. Trạng thái tin nhắn

#### Chưa có phản hồi
```
⏳ Chúng tôi đang xem xét và sẽ phản hồi sớm nhất có thể
```

#### Đã có phản hồi
```
✅ Phản hồi từ chúng tôi
[Nội dung phản hồi]
🕐 04/11/2025 18:57
👤 Admin Username
```

## Giao diện

### Trang tra cứu
- **Header**: Tiêu đề và mô tả
- **Form tìm kiếm**: Ô nhập email + nút tìm kiếm
- **Gợi ý**: Hướng dẫn nhập email đã dùng
- **Kết quả**: Danh sách tin nhắn (nếu tìm thấy)

### Card tin nhắn
```
┌─────────────────────────────────────┐
│ #3  📅 04/11/2025 18:57  🟢 Đã trả lời │
├─────────────────────────────────────┤
│ 💬 Tin nhắn của bạn                  │
│ [Nội dung tin nhắn gốc]              │
│                                      │
│ ✅ Phản hồi từ chúng tôi             │
│ [Nội dung phản hồi từ admin]         │
│ 🕐 05/11/2025 10:30                  │
│ 👤 admin                             │
└─────────────────────────────────────┘
```

### Không tìm thấy
```
📭 Không tìm thấy tin nhắn
Không có tin nhắn nào với email này.
Hãy gửi tin nhắn mới cho chúng tôi!

[📧 Gửi tin nhắn mới]
```

## Luồng hoạt động

### Từ phía người dùng:
1. Gửi tin nhắn liên hệ → Trạng thái: **Mới**
2. Admin xem tin nhắn → Trạng thái: **Đã xem**
3. Admin trả lời → Trạng thái: **Đã trả lời**
4. Người dùng tra cứu → Xem phản hồi

### Từ phía admin:
1. Nhận tin nhắn mới
2. Xem chi tiết
3. Trả lời qua modal
4. Gửi phản hồi (có thể kèm email)

## Tính năng nổi bật

### ✅ Tra cứu dễ dàng
- Chỉ cần email
- Không cần đăng nhập
- Xem tất cả tin nhắn cùng email

### ✅ Hiển thị đầy đủ
- Tin nhắn gốc
- Phản hồi từ admin
- Thời gian chi tiết
- Trạng thái rõ ràng

### ✅ Giao diện đẹp
- Responsive
- Animation mượt
- Màu sắc phân biệt trạng thái
- Icon trực quan

### ✅ Thông báo email
- Admin có thể gửi email khi trả lời
- Email chứa nội dung phản hồi
- Link đến trang tra cứu

## Bảo mật

### Quyền truy cập
- Chỉ xem được tin nhắn của email mình
- Không cần đăng nhập
- Không thể xem tin nhắn của người khác

### Dữ liệu
- Email được validate
- Nội dung được escape HTML
- Không lưu password

## Database

### Bảng contacts
```sql
- id: ID tin nhắn
- name: Tên người gửi
- email: Email (dùng để tra cứu)
- phone: Số điện thoại
- message: Nội dung
- status: new/read/replied
- admin_reply: Nội dung phản hồi
- replied_at: Thời gian trả lời
- replied_by: ID admin trả lời
- created_at: Thời gian tạo
```

### Bảng contact_replies
```sql
- id: ID phản hồi
- contact_id: ID tin nhắn
- admin_id: ID admin
- reply_message: Nội dung
- sent_at: Thời gian gửi
```

## API

### Tra cứu tin nhắn
**Method:** POST  
**Endpoint:** `index.php?page=my-contacts`  
**Data:** `search_email`

**Query:**
```sql
SELECT c.*, a.username 
FROM contacts c
LEFT JOIN admins a ON c.replied_by = a.id
WHERE c.email = ?
ORDER BY c.created_at DESC
```

## Responsive Design

### Desktop (> 768px)
- Layout 2 cột cho form
- Card tin nhắn rộng
- Hiển thị đầy đủ thông tin

### Mobile (< 768px)
- Layout 1 cột
- Form stack vertical
- Card tin nhắn thu gọn
- Touch-friendly buttons

## Ngôn ngữ

Hỗ trợ đa ngôn ngữ:
- 🇻🇳 Tiếng Việt
- 🇬🇧 English

Translation keys:
- `my_contacts_title`
- `my_contacts_subtitle`
- `enter_your_email`
- `search`
- `your_message`
- `admin_reply`
- `waiting_for_reply`
- `no_contacts_found`
- `status_new/read/replied`

## Tips cho người dùng

1. **Lưu email**: Nhớ email đã dùng để gửi tin nhắn
2. **Kiểm tra thường xuyên**: Vào trang tra cứu để xem phản hồi
3. **Kiểm tra spam**: Email phản hồi có thể vào spam
4. **Gửi lại**: Nếu không nhận được phản hồi sau 24h, gửi lại

## Tips cho admin

1. **Trả lời nhanh**: Người dùng đang chờ
2. **Gửi email**: Bật tùy chọn gửi email khi trả lời
3. **Nội dung rõ ràng**: Viết phản hồi dễ hiểu
4. **Theo dõi**: Kiểm tra tin nhắn mới thường xuyên

## Tích hợp

### Menu navigation
```php
<li><a href="index.php?page=my-contacts">
    <i class="fas fa-envelope-open-text"></i> 
    Tin nhắn của tôi
</a></li>
```

### Banner trên trang contact
```php
<div class="info-banner">
    Đã gửi tin nhắn? Kiểm tra phản hồi tại:
    <a href="index.php?page=my-contacts">
        Xem tin nhắn của tôi
    </a>
</div>
```

### Link sau khi gửi thành công
```php
<?php if ($messageType == 'success'): ?>
    <a href="index.php?page=my-contacts">
        Xem tin nhắn của tôi
    </a>
<?php endif; ?>
```

## Cải tiến trong tương lai

- [ ] Đăng nhập để xem tin nhắn (không cần nhập email)
- [ ] Thông báo realtime khi có phản hồi mới
- [ ] Trả lời lại tin nhắn từ trang tra cứu
- [ ] Đánh dấu đã đọc phản hồi
- [ ] Export lịch sử tin nhắn
- [ ] Đính kèm file trong tin nhắn
- [ ] Chat realtime với admin

---

**Phát triển bởi**: Ngon Gallery Team  
**Phiên bản**: 1.0  
**Cập nhật**: 2024
