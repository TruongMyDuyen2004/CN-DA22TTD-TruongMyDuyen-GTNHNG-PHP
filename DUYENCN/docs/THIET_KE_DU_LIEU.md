# 1.1.1 Thiết kế dữ liệu

## 1.1.1.1 Mô hình ERD

*Hình 8: Mô hình ERD*

## 1.1.1.2 Danh sách các thực thể và mối kết hợp

**Bảng 1: Danh sách các thực thể của mô hình ERD**

| STT | Tên thực thể | Diễn giải |
|-----|--------------|-----------|
| 1 | Customers | Lưu trữ thông tin khách hàng đăng ký tài khoản trên hệ thống. |
| 2 | Admins | Quản lý thông tin của quản trị viên hệ thống. |
| 3 | Categories | Phân loại món ăn theo từng danh mục. |
| 4 | Menu_Items | Lưu trữ thông tin chi tiết các món ăn. |
| 5 | Cart | Lưu trữ giỏ hàng của khách hàng. |
| 6 | Orders | Lưu trữ thông tin đơn hàng của khách hàng. |
| 7 | Order_Items | Lưu chi tiết các món ăn trong từng đơn hàng. |
| 8 | Reviews | Lưu trữ đánh giá của khách hàng về món ăn. |
| 9 | Review_Likes | Lưu thông tin lượt thích cho đánh giá. |
| 10 | Promotions | Lưu trữ thông tin các chương trình khuyến mãi. |
| 11 | Saved_Promotions | Lưu các mã khuyến mãi khách hàng đã lưu. |
| 12 | Dining_Tables | Lưu trữ thông tin các bàn ăn trong nhà hàng. |
| 13 | Reservations | Lưu trữ thông tin đặt bàn của khách hàng. |
| 14 | Reservation_Tables | Bảng liên kết giữa đặt bàn và bàn ăn. |
| 15 | Contacts | Lưu trữ thông tin liên hệ từ khách hàng. |
| 16 | Contact_Replies | Lưu các phản hồi của admin cho liên hệ. |

## 1.1.1.3 Chi tiết các thực thể và mối kết hợp

### Bảng mô tả thực thể Customers

**Tên thực thể:** Customers

**Mô tả:** Lưu trữ thông tin khách hàng đăng ký tài khoản trên hệ thống nhà hàng.

**Chi tiết thực thể:**

**Bảng 2: Bảng mô tả các thuộc tính trong bảng Customers**

| STT | Tên thuộc tính | Diễn giải | Kiểu dữ liệu | Ràng buộc toàn vẹn |
|-----|----------------|-----------|--------------|-------------------|
| 1 | id | Mã định danh duy nhất cho mỗi khách hàng. | int(11) | PK (khóa chính), A.I (tự động tăng), NOT NULL |
| 2 | full_name | Họ và tên đầy đủ của khách hàng. | varchar(100) | NOT NULL |
| 3 | email | Địa chỉ email của khách hàng. | varchar(100) | NOT NULL, UNIQUE |
| 4 | password | Mật khẩu đã được mã hóa của khách hàng. | varchar(255) | NOT NULL |
| 5 | phone | Số điện thoại liên lạc của khách hàng. | varchar(20) | NULL (có thể rỗng) |
| 6 | address | Địa chỉ giao hàng của khách hàng. | text | NULL (có thể rỗng) |
| 7 | avatar | Đường dẫn đến tệp ảnh đại diện của khách hàng. | varchar(255) | NULL (có thể rỗng) |
| 8 | google_id | Mã định danh Google nếu đăng nhập bằng Google. | varchar(255) | NULL (có thể rỗng) |
| 9 | created_at | Thời điểm tài khoản khách hàng được tạo. | datetime | CURRENT_TIMESTAMP |
| 10 | updated_at | Thời điểm thông tin khách hàng được cập nhật lần cuối. | datetime | ON UPDATE CURRENT_TIMESTAMP |

---

### Bảng mô tả thực thể Admins

**Tên thực thể:** Admins

**Mô tả:** Lưu trữ thông tin quản trị viên của hệ thống nhà hàng.

**Chi tiết thực thể:**

**Bảng 3: Bảng mô tả các thuộc tính trong bảng Admins**

| STT | Tên thuộc tính | Diễn giải | Kiểu dữ liệu | Ràng buộc toàn vẹn |
|-----|----------------|-----------|--------------|-------------------|
| 1 | id | Mã định danh duy nhất cho mỗi quản trị viên. | int(11) | PK (khóa chính), A.I (tự động tăng), NOT NULL |
| 2 | username | Tên đăng nhập của quản trị viên. | varchar(50) | NOT NULL, UNIQUE |
| 3 | password | Mật khẩu đã được mã hóa của quản trị viên. | varchar(255) | NOT NULL |
| 4 | email | Địa chỉ email của quản trị viên. | varchar(100) | NOT NULL |
| 5 | created_at | Thời điểm tài khoản quản trị viên được tạo. | datetime | CURRENT_TIMESTAMP |

---

### Bảng mô tả thực thể Categories

**Tên thực thể:** Categories

**Mô tả:** Lưu trữ thông tin các danh mục món ăn trong hệ thống nhà hàng.

**Chi tiết thực thể:**

**Bảng 4: Bảng mô tả các thuộc tính trong bảng Categories**

| STT | Tên thuộc tính | Diễn giải | Kiểu dữ liệu | Ràng buộc toàn vẹn |
|-----|----------------|-----------|--------------|-------------------|
| 1 | id | Mã định danh duy nhất cho mỗi danh mục. | int(11) | PK (khóa chính), A.I (tự động tăng), NOT NULL |
| 2 | name | Tên của danh mục món ăn. | varchar(100) | NOT NULL |
| 3 | display_order | Thứ tự hiển thị của danh mục. | int(11) | DEFAULT 0 |
| 4 | created_at | Thời điểm danh mục được tạo. | datetime | CURRENT_TIMESTAMP |

---

### Bảng mô tả thực thể Menu_Items

**Tên thực thể:** Menu_Items

**Mô tả:** Lưu trữ thông tin chi tiết của từng món ăn trong thực đơn nhà hàng.

**Chi tiết thực thể:**

**Bảng 5: Bảng mô tả các thuộc tính trong bảng Menu_Items**

| STT | Tên thuộc tính | Diễn giải | Kiểu dữ liệu | Ràng buộc toàn vẹn |
|-----|----------------|-----------|--------------|-------------------|
| 1 | id | Mã định danh duy nhất cho mỗi món ăn. | int(11) | PK (khóa chính), A.I (tự động tăng), NOT NULL |
| 2 | category_id | Mã tham chiếu đến bảng danh mục (categories). | int(11) | FK (khóa ngoại), NOT NULL |
| 3 | name | Tên của món ăn. | varchar(200) | NOT NULL |
| 4 | description | Mô tả chi tiết về món ăn. | text | NULL (có thể rỗng) |
| 5 | price | Giá bán của món ăn. | decimal(10,2) | NOT NULL |
| 6 | image | Đường dẫn đến hình ảnh món ăn. | varchar(255) | NULL (có thể rỗng) |
| 7 | is_available | Trạng thái còn phục vụ hay không. | tinyint(1) | DEFAULT 1 |
| 8 | discount_percent | Phần trăm giảm giá (nếu có). | decimal(5,2) | NULL (có thể rỗng) |
| 9 | created_at | Thời điểm món ăn được thêm vào hệ thống. | datetime | CURRENT_TIMESTAMP |

---

### Bảng mô tả thực thể Cart

**Tên thực thể:** Cart

**Mô tả:** Lưu trữ thông tin giỏ hàng của khách hàng.

**Chi tiết thực thể:**

**Bảng 6: Bảng mô tả các thuộc tính trong bảng Cart**

| STT | Tên thuộc tính | Diễn giải | Kiểu dữ liệu | Ràng buộc toàn vẹn |
|-----|----------------|-----------|--------------|-------------------|
| 1 | id | Mã định danh duy nhất cho mỗi mục trong giỏ hàng. | int(11) | PK (khóa chính), A.I (tự động tăng), NOT NULL |
| 2 | customer_id | Mã khách hàng sở hữu giỏ hàng. | int(11) | FK (khóa ngoại), NOT NULL |
| 3 | menu_item_id | Mã món ăn được thêm vào giỏ. | int(11) | FK (khóa ngoại), NOT NULL |
| 4 | quantity | Số lượng món ăn trong giỏ. | int(11) | NOT NULL, DEFAULT 1 |
| 5 | note | Ghi chú thêm cho món ăn. | text | NULL (có thể rỗng) |
| 6 | created_at | Thời điểm thêm món vào giỏ hàng. | datetime | CURRENT_TIMESTAMP |

---

### Bảng mô tả thực thể Orders

**Tên thực thể:** Orders

**Mô tả:** Lưu trữ thông tin các đơn hàng của khách hàng.

**Chi tiết thực thể:**

**Bảng 7: Bảng mô tả các thuộc tính trong bảng Orders**

| STT | Tên thuộc tính | Diễn giải | Kiểu dữ liệu | Ràng buộc toàn vẹn |
|-----|----------------|-----------|--------------|-------------------|
| 1 | id | Mã định danh duy nhất cho mỗi đơn hàng. | int(11) | PK (khóa chính), A.I (tự động tăng), NOT NULL |
| 2 | customer_id | Mã khách hàng đặt đơn hàng. | int(11) | FK (khóa ngoại), NOT NULL |
| 3 | order_number | Mã số đơn hàng hiển thị cho khách hàng. | varchar(50) | NOT NULL, UNIQUE |
| 4 | delivery_address | Địa chỉ giao hàng. | text | NOT NULL |
| 5 | delivery_phone | Số điện thoại nhận hàng. | varchar(20) | NOT NULL |
| 6 | total_amount | Tổng giá trị đơn hàng. | decimal(10,2) | NOT NULL |
| 7 | delivery_fee | Phí giao hàng. | decimal(10,2) | DEFAULT 0 |
| 8 | discount_amount | Số tiền được giảm giá. | decimal(10,2) | DEFAULT 0 |
| 9 | note | Ghi chú thêm cho đơn hàng. | text | NULL (có thể rỗng) |
| 10 | status | Trạng thái đơn hàng ('pending', 'confirmed', 'preparing', 'delivering', 'completed', 'cancelled'). | enum | NOT NULL, DEFAULT 'pending' |
| 11 | payment_method | Phương thức thanh toán ('cash', 'card', 'transfer'). | enum | NOT NULL, DEFAULT 'cash' |
| 12 | created_at | Thời điểm đơn hàng được tạo. | datetime | CURRENT_TIMESTAMP |
| 13 | updated_at | Thời điểm đơn hàng được cập nhật lần cuối. | datetime | ON UPDATE CURRENT_TIMESTAMP |

---

### Bảng mô tả thực thể Order_Items

**Tên thực thể:** Order_Items

**Mô tả:** Lưu trữ chi tiết các món ăn trong từng đơn hàng.

**Chi tiết thực thể:**

**Bảng 8: Bảng mô tả các thuộc tính trong bảng Order_Items**

| STT | Tên thuộc tính | Diễn giải | Kiểu dữ liệu | Ràng buộc toàn vẹn |
|-----|----------------|-----------|--------------|-------------------|
| 1 | id | Mã định danh duy nhất cho mỗi chi tiết đơn hàng. | int(11) | PK (khóa chính), A.I (tự động tăng), NOT NULL |
| 2 | order_id | Mã đơn hàng chứa món ăn này. | int(11) | FK (khóa ngoại), NOT NULL |
| 3 | menu_item_id | Mã món ăn được đặt. | int(11) | FK (khóa ngoại), NOT NULL |
| 4 | quantity | Số lượng món ăn đặt. | int(11) | NOT NULL |
| 5 | price | Đơn giá món ăn tại thời điểm đặt. | decimal(10,2) | NOT NULL |
| 6 | note | Ghi chú riêng cho món ăn. | text | NULL (có thể rỗng) |


---

### Bảng mô tả thực thể Reviews

**Tên thực thể:** Reviews

**Mô tả:** Lưu trữ đánh giá của khách hàng về món ăn.

**Chi tiết thực thể:**

**Bảng 9: Bảng mô tả các thuộc tính trong bảng Reviews**

| STT | Tên thuộc tính | Diễn giải | Kiểu dữ liệu | Ràng buộc toàn vẹn |
|-----|----------------|-----------|--------------|-------------------|
| 1 | id | Mã định danh duy nhất cho mỗi đánh giá. | int(11) | PK (khóa chính), A.I (tự động tăng), NOT NULL |
| 2 | customer_id | Mã khách hàng viết đánh giá. | int(11) | FK (khóa ngoại), NOT NULL |
| 3 | order_id | Mã đơn hàng liên quan (nếu có). | int(11) | FK (khóa ngoại), NULL (có thể rỗng) |
| 4 | menu_item_id | Mã món ăn được đánh giá. | int(11) | FK (khóa ngoại), NULL (có thể rỗng) |
| 5 | rating | Số sao đánh giá (1-5). | int(11) | NOT NULL, CHECK (rating >= 1 AND rating <= 5) |
| 6 | comment | Nội dung bình luận đánh giá. | text | NULL (có thể rỗng) |
| 7 | likes_count | Số lượt thích đánh giá này. | int(11) | DEFAULT 0 |
| 8 | is_approved | Trạng thái duyệt đánh giá. | tinyint(1) | DEFAULT 1 |
| 9 | created_at | Thời điểm đánh giá được tạo. | datetime | CURRENT_TIMESTAMP |
| 10 | updated_at | Thời điểm đánh giá được cập nhật lần cuối. | datetime | ON UPDATE CURRENT_TIMESTAMP |

---

### Bảng mô tả thực thể Review_Likes

**Tên thực thể:** Review_Likes

**Mô tả:** Lưu trữ thông tin lượt thích cho các đánh giá.

**Chi tiết thực thể:**

**Bảng 10: Bảng mô tả các thuộc tính trong bảng Review_Likes**

| STT | Tên thuộc tính | Diễn giải | Kiểu dữ liệu | Ràng buộc toàn vẹn |
|-----|----------------|-----------|--------------|-------------------|
| 1 | id | Mã định danh duy nhất cho mỗi lượt thích. | int(11) | PK (khóa chính), A.I (tự động tăng), NOT NULL |
| 2 | review_id | Mã đánh giá được thích. | int(11) | FK (khóa ngoại), NOT NULL |
| 3 | customer_id | Mã khách hàng thực hiện thích. | int(11) | FK (khóa ngoại), NOT NULL |
| 4 | created_at | Thời điểm thực hiện thích. | datetime | CURRENT_TIMESTAMP |

---

### Bảng mô tả thực thể Promotions

**Tên thực thể:** Promotions

**Mô tả:** Lưu trữ thông tin các chương trình khuyến mãi của nhà hàng.

**Chi tiết thực thể:**

**Bảng 11: Bảng mô tả các thuộc tính trong bảng Promotions**

| STT | Tên thuộc tính | Diễn giải | Kiểu dữ liệu | Ràng buộc toàn vẹn |
|-----|----------------|-----------|--------------|-------------------|
| 1 | id | Mã định danh duy nhất cho mỗi khuyến mãi. | int(11) | PK (khóa chính), A.I (tự động tăng), NOT NULL |
| 2 | code | Mã khuyến mãi (VD: SALE20, FREESHIP). | varchar(50) | NOT NULL, UNIQUE |
| 3 | name | Tên chương trình khuyến mãi. | varchar(255) | NOT NULL |
| 4 | description | Mô tả chi tiết về khuyến mãi. | text | NULL (có thể rỗng) |
| 5 | discount_type | Loại giảm giá ('percent', 'fixed'). | enum | DEFAULT 'percent' |
| 6 | discount_value | Giá trị giảm (20 = 20% hoặc 50000 = 50.000đ). | decimal(10,2) | NOT NULL |
| 7 | min_order_value | Đơn hàng tối thiểu để áp dụng. | decimal(10,2) | DEFAULT 0 |
| 8 | max_discount | Giảm tối đa (chỉ áp dụng cho loại percent). | decimal(10,2) | NULL (có thể rỗng) |
| 9 | usage_limit | Giới hạn số lần sử dụng (NULL = không giới hạn). | int(11) | NULL (có thể rỗng) |
| 10 | used_count | Số lần đã sử dụng. | int(11) | DEFAULT 0 |
| 11 | start_date | Ngày bắt đầu khuyến mãi. | datetime | NOT NULL |
| 12 | end_date | Ngày kết thúc khuyến mãi. | datetime | NOT NULL |
| 13 | is_active | Trạng thái hoạt động (1=Hoạt động, 0=Tắt). | tinyint(1) | DEFAULT 1 |
| 14 | created_at | Thời điểm khuyến mãi được tạo. | datetime | CURRENT_TIMESTAMP |

---

### Bảng mô tả thực thể Saved_Promotions

**Tên thực thể:** Saved_Promotions

**Mô tả:** Lưu trữ các mã khuyến mãi mà khách hàng đã lưu để sử dụng sau.

**Chi tiết thực thể:**

**Bảng 12: Bảng mô tả các thuộc tính trong bảng Saved_Promotions**

| STT | Tên thuộc tính | Diễn giải | Kiểu dữ liệu | Ràng buộc toàn vẹn |
|-----|----------------|-----------|--------------|-------------------|
| 1 | id | Mã định danh duy nhất. | int(11) | PK (khóa chính), A.I (tự động tăng), NOT NULL |
| 2 | customer_id | Mã khách hàng lưu mã khuyến mãi. | int(11) | FK (khóa ngoại), NOT NULL |
| 3 | promo_code | Mã khuyến mãi được lưu. | varchar(50) | NOT NULL |
| 4 | saved_at | Thời điểm lưu mã khuyến mãi. | datetime | CURRENT_TIMESTAMP |

---

### Bảng mô tả thực thể Dining_Tables

**Tên thực thể:** Dining_Tables

**Mô tả:** Lưu trữ thông tin các bàn ăn trong nhà hàng.

**Chi tiết thực thể:**

**Bảng 13: Bảng mô tả các thuộc tính trong bảng Dining_Tables**

| STT | Tên thuộc tính | Diễn giải | Kiểu dữ liệu | Ràng buộc toàn vẹn |
|-----|----------------|-----------|--------------|-------------------|
| 1 | id | Mã định danh duy nhất cho mỗi bàn. | int(11) | PK (khóa chính), A.I (tự động tăng), NOT NULL |
| 2 | table_number | Số hiệu bàn (VD: T01, V02). | varchar(10) | NOT NULL, UNIQUE |
| 3 | capacity | Sức chứa tối đa của bàn. | int(11) | NOT NULL |
| 4 | location | Vị trí bàn ('indoor', 'outdoor', 'vip', 'private_room'). | enum | DEFAULT 'indoor' |
| 5 | is_available | Trạng thái bàn có sẵn hay không. | tinyint(1) | DEFAULT 1 |
| 6 | description | Mô tả thêm về bàn. | text | NULL (có thể rỗng) |
| 7 | created_at | Thời điểm bàn được thêm vào hệ thống. | datetime | CURRENT_TIMESTAMP |
| 8 | updated_at | Thời điểm thông tin bàn được cập nhật lần cuối. | datetime | ON UPDATE CURRENT_TIMESTAMP |

---

### Bảng mô tả thực thể Reservations

**Tên thực thể:** Reservations

**Mô tả:** Lưu trữ thông tin đặt bàn của khách hàng.

**Chi tiết thực thể:**

**Bảng 14: Bảng mô tả các thuộc tính trong bảng Reservations**

| STT | Tên thuộc tính | Diễn giải | Kiểu dữ liệu | Ràng buộc toàn vẹn |
|-----|----------------|-----------|--------------|-------------------|
| 1 | id | Mã định danh duy nhất cho mỗi lượt đặt bàn. | int(11) | PK (khóa chính), A.I (tự động tăng), NOT NULL |
| 2 | customer_id | Mã khách hàng đặt bàn (nếu đã đăng nhập). | int(11) | FK (khóa ngoại), NULL (có thể rỗng) |
| 3 | customer_name | Tên khách hàng đặt bàn. | varchar(100) | NOT NULL |
| 4 | email | Email liên hệ. | varchar(100) | NOT NULL |
| 5 | phone | Số điện thoại liên hệ. | varchar(20) | NOT NULL |
| 6 | reservation_date | Ngày đặt bàn. | date | NOT NULL |
| 7 | reservation_time | Giờ đặt bàn. | time | NOT NULL |
| 8 | number_of_guests | Số lượng khách. | int(11) | NOT NULL |
| 9 | special_request | Yêu cầu đặc biệt của khách. | text | NULL (có thể rỗng) |
| 10 | status | Trạng thái đặt bàn ('pending', 'confirmed', 'cancelled', 'completed'). | enum | NOT NULL, DEFAULT 'pending' |
| 11 | table_preference | Vị trí bàn mong muốn ('indoor', 'outdoor', 'vip', 'private_room', 'any'). | enum | DEFAULT 'any' |
| 12 | occasion | Dịp đặc biệt (Sinh nhật, kỷ niệm, họp mặt...). | varchar(100) | NULL (có thể rỗng) |
| 13 | admin_note | Ghi chú của quản trị viên. | text | NULL (có thể rỗng) |
| 14 | confirmed_by | Mã quản trị viên xác nhận đặt bàn. | int(11) | FK (khóa ngoại), NULL (có thể rỗng) |
| 15 | confirmed_at | Thời điểm xác nhận đặt bàn. | datetime | NULL (có thể rỗng) |
| 16 | created_at | Thời điểm yêu cầu đặt bàn được tạo. | datetime | CURRENT_TIMESTAMP |
| 17 | updated_at | Thời điểm bản ghi được cập nhật lần cuối. | datetime | ON UPDATE CURRENT_TIMESTAMP |


---

### Bảng mô tả thực thể Reviews

**Tên thực thể:** Reviews

**Mô tả:** Lưu trữ đánh giá của khách hàng về món ăn.

**Chi tiết thực thể:**

**Bảng 9: Bảng mô tả các thuộc tính trong bảng Reviews**

| STT | Tên thuộc tính | Diễn giải | Kiểu dữ liệu | Ràng buộc toàn vẹn |
|-----|----------------|-----------|--------------|-------------------|
| 1 | id | Mã định danh duy nhất cho mỗi đánh giá. | int(11) | PK (khóa chính), A.I (tự động tăng), NOT NULL |
| 2 | customer_id | Mã khách hàng viết đánh giá. | int(11) | FK (khóa ngoại), NOT NULL |
| 3 | order_id | Mã đơn hàng liên quan (nếu có). | int(11) | FK (khóa ngoại), NULL (có thể rỗng) |
| 4 | menu_item_id | Mã món ăn được đánh giá. | int(11) | FK (khóa ngoại), NULL (có thể rỗng) |
| 5 | rating | Số sao đánh giá (1-5). | int(11) | NOT NULL, CHECK (rating >= 1 AND rating <= 5) |
| 6 | comment | Nội dung bình luận đánh giá. | text | NULL (có thể rỗng) |
| 7 | likes_count | Số lượt thích đánh giá này. | int(11) | DEFAULT 0 |
| 8 | is_approved | Trạng thái duyệt đánh giá. | tinyint(1) | DEFAULT 1 |
| 9 | created_at | Thời điểm đánh giá được tạo. | datetime | CURRENT_TIMESTAMP |
| 10 | updated_at | Thời điểm đánh giá được cập nhật lần cuối. | datetime | ON UPDATE CURRENT_TIMESTAMP |

---

### Bảng mô tả thực thể Review_Likes

**Tên thực thể:** Review_Likes

**Mô tả:** Lưu trữ thông tin lượt thích cho các đánh giá.

**Chi tiết thực thể:**

**Bảng 10: Bảng mô tả các thuộc tính trong bảng Review_Likes**

| STT | Tên thuộc tính | Diễn giải | Kiểu dữ liệu | Ràng buộc toàn vẹn |
|-----|----------------|-----------|--------------|-------------------|
| 1 | id | Mã định danh duy nhất cho mỗi lượt thích. | int(11) | PK (khóa chính), A.I (tự động tăng), NOT NULL |
| 2 | review_id | Mã đánh giá được thích. | int(11) | FK (khóa ngoại), NOT NULL |
| 3 | customer_id | Mã khách hàng thực hiện thích. | int(11) | FK (khóa ngoại), NOT NULL |
| 4 | created_at | Thời điểm thực hiện thích. | datetime | CURRENT_TIMESTAMP |

---

### Bảng mô tả thực thể Promotions

**Tên thực thể:** Promotions

**Mô tả:** Lưu trữ thông tin các chương trình khuyến mãi của nhà hàng.

**Chi tiết thực thể:**

**Bảng 11: Bảng mô tả các thuộc tính trong bảng Promotions**

| STT | Tên thuộc tính | Diễn giải | Kiểu dữ liệu | Ràng buộc toàn vẹn |
|-----|----------------|-----------|--------------|-------------------|
| 1 | id | Mã định danh duy nhất cho mỗi khuyến mãi. | int(11) | PK (khóa chính), A.I (tự động tăng), NOT NULL |
| 2 | code | Mã khuyến mãi (VD: SALE20, FREESHIP). | varchar(50) | NOT NULL, UNIQUE |
| 3 | name | Tên chương trình khuyến mãi. | varchar(255) | NOT NULL |
| 4 | description | Mô tả chi tiết về khuyến mãi. | text | NULL (có thể rỗng) |
| 5 | discount_type | Loại giảm giá ('percent', 'fixed'). | enum | DEFAULT 'percent' |
| 6 | discount_value | Giá trị giảm (20 = 20% hoặc 50000 = 50.000đ). | decimal(10,2) | NOT NULL |
| 7 | min_order_value | Đơn hàng tối thiểu để áp dụng. | decimal(10,2) | DEFAULT 0 |
| 8 | max_discount | Giảm tối đa (chỉ áp dụng cho loại percent). | decimal(10,2) | NULL (có thể rỗng) |
| 9 | usage_limit | Giới hạn số lần sử dụng (NULL = không giới hạn). | int(11) | NULL (có thể rỗng) |
| 10 | used_count | Số lần đã sử dụng. | int(11) | DEFAULT 0 |
| 11 | start_date | Ngày bắt đầu khuyến mãi. | datetime | NOT NULL |
| 12 | end_date | Ngày kết thúc khuyến mãi. | datetime | NOT NULL |
| 13 | is_active | Trạng thái hoạt động (1=Hoạt động, 0=Tắt). | tinyint(1) | DEFAULT 1 |
| 14 | created_at | Thời điểm khuyến mãi được tạo. | datetime | CURRENT_TIMESTAMP |

---

### Bảng mô tả thực thể Saved_Promotions

**Tên thực thể:** Saved_Promotions

**Mô tả:** Lưu trữ các mã khuyến mãi mà khách hàng đã lưu để sử dụng sau.

**Chi tiết thực thể:**

**Bảng 12: Bảng mô tả các thuộc tính trong bảng Saved_Promotions**

| STT | Tên thuộc tính | Diễn giải | Kiểu dữ liệu | Ràng buộc toàn vẹn |
|-----|----------------|-----------|--------------|-------------------|
| 1 | id | Mã định danh duy nhất. | int(11) | PK (khóa chính), A.I (tự động tăng), NOT NULL |
| 2 | customer_id | Mã khách hàng lưu mã khuyến mãi. | int(11) | FK (khóa ngoại), NOT NULL |
| 3 | promo_code | Mã khuyến mãi được lưu. | varchar(50) | NOT NULL |
| 4 | saved_at | Thời điểm lưu mã khuyến mãi. | datetime | CURRENT_TIMESTAMP |

---

### Bảng mô tả thực thể Dining_Tables

**Tên thực thể:** Dining_Tables

**Mô tả:** Lưu trữ thông tin các bàn ăn trong nhà hàng.

**Chi tiết thực thể:**

**Bảng 13: Bảng mô tả các thuộc tính trong bảng Dining_Tables**

| STT | Tên thuộc tính | Diễn giải | Kiểu dữ liệu | Ràng buộc toàn vẹn |
|-----|----------------|-----------|--------------|-------------------|
| 1 | id | Mã định danh duy nhất cho mỗi bàn. | int(11) | PK (khóa chính), A.I (tự động tăng), NOT NULL |
| 2 | table_number | Số hiệu bàn (VD: T01, V02). | varchar(10) | NOT NULL, UNIQUE |
| 3 | capacity | Sức chứa tối đa của bàn. | int(11) | NOT NULL |
| 4 | location | Vị trí bàn ('indoor', 'outdoor', 'vip', 'private_room'). | enum | DEFAULT 'indoor' |
| 5 | is_available | Trạng thái bàn có sẵn hay không. | tinyint(1) | DEFAULT 1 |
| 6 | description | Mô tả thêm về bàn. | text | NULL (có thể rỗng) |
| 7 | created_at | Thời điểm bàn được thêm vào hệ thống. | datetime | CURRENT_TIMESTAMP |
| 8 | updated_at | Thời điểm thông tin bàn được cập nhật lần cuối. | datetime | ON UPDATE CURRENT_TIMESTAMP |

---

### Bảng mô tả thực thể Reservations

**Tên thực thể:** Reservations

**Mô tả:** Lưu trữ thông tin đặt bàn của khách hàng.

**Chi tiết thực thể:**

**Bảng 14: Bảng mô tả các thuộc tính trong bảng Reservations**

| STT | Tên thuộc tính | Diễn giải | Kiểu dữ liệu | Ràng buộc toàn vẹn |
|-----|----------------|-----------|--------------|-------------------|
| 1 | id | Mã định danh duy nhất cho mỗi lượt đặt bàn. | int(11) | PK (khóa chính), A.I (tự động tăng), NOT NULL |
| 2 | customer_id | Mã khách hàng đặt bàn (nếu đã đăng nhập). | int(11) | FK (khóa ngoại), NULL (có thể rỗng) |
| 3 | customer_name | Tên khách hàng đặt bàn. | varchar(100) | NOT NULL |
| 4 | email | Email liên hệ. | varchar(100) | NOT NULL |
| 5 | phone | Số điện thoại liên hệ. | varchar(20) | NOT NULL |
| 6 | reservation_date | Ngày đặt bàn. | date | NOT NULL |
| 7 | reservation_time | Giờ đặt bàn. | time | NOT NULL |
| 8 | number_of_guests | Số lượng khách. | int(11) | NOT NULL |
| 9 | special_request | Yêu cầu đặc biệt của khách. | text | NULL (có thể rỗng) |
| 10 | status | Trạng thái đặt bàn ('pending', 'confirmed', 'cancelled', 'completed'). | enum | NOT NULL, DEFAULT 'pending' |
| 11 | table_preference | Vị trí bàn mong muốn ('indoor', 'outdoor', 'vip', 'private_room', 'any'). | enum | DEFAULT 'any' |
| 12 | occasion | Dịp đặc biệt (Sinh nhật, kỷ niệm, họp mặt...). | varchar(100) | NULL (có thể rỗng) |
| 13 | admin_note | Ghi chú của quản trị viên. | text | NULL (có thể rỗng) |
| 14 | confirmed_by | Mã quản trị viên xác nhận đặt bàn. | int(11) | FK (khóa ngoại), NULL (có thể rỗng) |
| 15 | confirmed_at | Thời điểm xác nhận đặt bàn. | datetime | NULL (có thể rỗng) |
| 16 | created_at | Thời điểm yêu cầu đặt bàn được tạo. | datetime | CURRENT_TIMESTAMP |
| 17 | updated_at | Thời điểm bản ghi được cập nhật lần cuối. | datetime | ON UPDATE CURRENT_TIMESTAMP |


---

### Bảng mô tả thực thể Reservation_Tables

**Tên thực thể:** Reservation_Tables

**Mô tả:** Bảng liên kết giữa đặt bàn và bàn ăn (quan hệ nhiều-nhiều).

**Chi tiết thực thể:**

**Bảng 15: Bảng mô tả các thuộc tính trong bảng Reservation_Tables**

| STT | Tên thuộc tính | Diễn giải | Kiểu dữ liệu | Ràng buộc toàn vẹn |
|-----|----------------|-----------|--------------|-------------------|
| 1 | id | Mã định danh duy nhất. | int(11) | PK (khóa chính), A.I (tự động tăng), NOT NULL |
| 2 | reservation_id | Mã lượt đặt bàn. | int(11) | FK (khóa ngoại), NOT NULL |
| 3 | table_id | Mã bàn ăn được đặt. | int(11) | FK (khóa ngoại), NOT NULL |
| 4 | created_at | Thời điểm liên kết được tạo. | datetime | CURRENT_TIMESTAMP |

---

### Bảng mô tả thực thể Contacts

**Tên thực thể:** Contacts

**Mô tả:** Lưu trữ thông tin liên hệ từ khách hàng gửi đến nhà hàng.

**Chi tiết thực thể:**

**Bảng 16: Bảng mô tả các thuộc tính trong bảng Contacts**

| STT | Tên thuộc tính | Diễn giải | Kiểu dữ liệu | Ràng buộc toàn vẹn |
|-----|----------------|-----------|--------------|-------------------|
| 1 | id | Mã định danh duy nhất cho mỗi liên hệ. | int(11) | PK (khóa chính), A.I (tự động tăng), NOT NULL |
| 2 | name | Tên người gửi liên hệ. | varchar(100) | NOT NULL |
| 3 | email | Email người gửi. | varchar(100) | NOT NULL |
| 4 | phone | Số điện thoại người gửi. | varchar(20) | NULL (có thể rỗng) |
| 5 | message | Nội dung tin nhắn liên hệ. | text | NOT NULL |
| 6 | status | Trạng thái xử lý ('new', 'read', 'replied'). | enum | NOT NULL, DEFAULT 'new' |
| 7 | admin_reply | Nội dung phản hồi của admin. | text | NULL (có thể rỗng) |
| 8 | replied_by | Mã admin đã phản hồi. | int(11) | FK (khóa ngoại), NULL (có thể rỗng) |
| 9 | replied_at | Thời điểm phản hồi. | datetime | NULL (có thể rỗng) |
| 10 | created_at | Thời điểm liên hệ được gửi. | datetime | CURRENT_TIMESTAMP |

---

### Bảng mô tả thực thể Contact_Replies

**Tên thực thể:** Contact_Replies

**Mô tả:** Lưu trữ lịch sử các phản hồi của admin cho liên hệ.

**Chi tiết thực thể:**

**Bảng 17: Bảng mô tả các thuộc tính trong bảng Contact_Replies**

| STT | Tên thuộc tính | Diễn giải | Kiểu dữ liệu | Ràng buộc toàn vẹn |
|-----|----------------|-----------|--------------|-------------------|
| 1 | id | Mã định danh duy nhất cho mỗi phản hồi. | int(11) | PK (khóa chính), A.I (tự động tăng), NOT NULL |
| 2 | contact_id | Mã liên hệ được phản hồi. | int(11) | FK (khóa ngoại), NOT NULL |
| 3 | admin_id | Mã admin thực hiện phản hồi. | int(11) | FK (khóa ngoại), NOT NULL |
| 4 | reply_message | Nội dung tin nhắn phản hồi. | text | NOT NULL |
| 5 | sent_at | Thời điểm gửi phản hồi. | datetime | CURRENT_TIMESTAMP |

---

## Tổng kết các mối quan hệ

| Mối quan hệ | Diễn giải |
|-------------|-----------|
| customers → cart | Một khách hàng có nhiều mục trong giỏ hàng (1-n) |
| customers → orders | Một khách hàng có nhiều đơn hàng (1-n) |
| customers → reviews | Một khách hàng viết nhiều đánh giá (1-n) |
| customers → review_likes | Một khách hàng thích nhiều đánh giá (1-n) |
| customers → saved_promotions | Một khách hàng lưu nhiều mã khuyến mãi (1-n) |
| customers → reservations | Một khách hàng có nhiều lượt đặt bàn (1-n) |
| admins → reservations | Một admin xác nhận nhiều đặt bàn (1-n) |
| admins → contacts | Một admin phản hồi nhiều liên hệ (1-n) |
| admins → contact_replies | Một admin gửi nhiều phản hồi (1-n) |
| categories → menu_items | Một danh mục chứa nhiều món ăn (1-n) |
| menu_items → cart | Một món ăn có trong nhiều giỏ hàng (1-n) |
| menu_items → order_items | Một món ăn có trong nhiều chi tiết đơn hàng (1-n) |
| menu_items → reviews | Một món ăn nhận nhiều đánh giá (1-n) |
| orders → order_items | Một đơn hàng chứa nhiều chi tiết (1-n) |
| reviews → review_likes | Một đánh giá có nhiều lượt thích (1-n) |
| dining_tables → reservation_tables | Một bàn được đặt nhiều lần (1-n) |
| reservations → reservation_tables | Một lượt đặt bàn có thể đặt nhiều bàn (1-n) |
| contacts → contact_replies | Một liên hệ có nhiều phản hồi (1-n) |


---

### Bảng mô tả thực thể Reservation_Tables

**Tên thực thể:** Reservation_Tables

**Mô tả:** Bảng liên kết giữa đặt bàn và bàn ăn (quan hệ nhiều-nhiều).

**Chi tiết thực thể:**

**Bảng 15: Bảng mô tả các thuộc tính trong bảng Reservation_Tables**

| STT | Tên thuộc tính | Diễn giải | Kiểu dữ liệu | Ràng buộc toàn vẹn |
|-----|----------------|-----------|--------------|-------------------|
| 1 | id | Mã định danh duy nhất. | int(11) | PK (khóa chính), A.I (tự động tăng), NOT NULL |
| 2 | reservation_id | Mã lượt đặt bàn. | int(11) | FK (khóa ngoại), NOT NULL |
| 3 | table_id | Mã bàn ăn được đặt. | int(11) | FK (khóa ngoại), NOT NULL |
| 4 | created_at | Thời điểm liên kết được tạo. | datetime | CURRENT_TIMESTAMP |

---

### Bảng mô tả thực thể Contacts

**Tên thực thể:** Contacts

**Mô tả:** Lưu trữ thông tin liên hệ từ khách hàng gửi đến nhà hàng.

**Chi tiết thực thể:**

**Bảng 16: Bảng mô tả các thuộc tính trong bảng Contacts**

| STT | Tên thuộc tính | Diễn giải | Kiểu dữ liệu | Ràng buộc toàn vẹn |
|-----|----------------|-----------|--------------|-------------------|
| 1 | id | Mã định danh duy nhất cho mỗi liên hệ. | int(11) | PK (khóa chính), A.I (tự động tăng), NOT NULL |
| 2 | name | Tên người gửi liên hệ. | varchar(100) | NOT NULL |
| 3 | email | Email người gửi. | varchar(100) | NOT NULL |
| 4 | phone | Số điện thoại người gửi. | varchar(20) | NULL (có thể rỗng) |
| 5 | message | Nội dung tin nhắn liên hệ. | text | NOT NULL |
| 6 | status | Trạng thái xử lý ('new', 'read', 'replied'). | enum | NOT NULL, DEFAULT 'new' |
| 7 | admin_reply | Nội dung phản hồi của admin. | text | NULL (có thể rỗng) |
| 8 | replied_by | Mã admin đã phản hồi. | int(11) | FK (khóa ngoại), NULL (có thể rỗng) |
| 9 | replied_at | Thời điểm phản hồi. | datetime | NULL (có thể rỗng) |
| 10 | created_at | Thời điểm liên hệ được gửi. | datetime | CURRENT_TIMESTAMP |

---

### Bảng mô tả thực thể Contact_Replies

**Tên thực thể:** Contact_Replies

**Mô tả:** Lưu trữ lịch sử các phản hồi của admin cho liên hệ.

**Chi tiết thực thể:**

**Bảng 17: Bảng mô tả các thuộc tính trong bảng Contact_Replies**

| STT | Tên thuộc tính | Diễn giải | Kiểu dữ liệu | Ràng buộc toàn vẹn |
|-----|----------------|-----------|--------------|-------------------|
| 1 | id | Mã định danh duy nhất cho mỗi phản hồi. | int(11) | PK (khóa chính), A.I (tự động tăng), NOT NULL |
| 2 | contact_id | Mã liên hệ được phản hồi. | int(11) | FK (khóa ngoại), NOT NULL |
| 3 | admin_id | Mã admin thực hiện phản hồi. | int(11) | FK (khóa ngoại), NOT NULL |
| 4 | reply_message | Nội dung tin nhắn phản hồi. | text | NOT NULL |
| 5 | sent_at | Thời điểm gửi phản hồi. | datetime | CURRENT_TIMESTAMP |

---

## Tổng kết các mối quan hệ

| Mối quan hệ | Diễn giải |
|-------------|-----------|
| customers → cart | Một khách hàng có nhiều mục trong giỏ hàng (1-n) |
| customers → orders | Một khách hàng có nhiều đơn hàng (1-n) |
| customers → reviews | Một khách hàng viết nhiều đánh giá (1-n) |
| customers → review_likes | Một khách hàng thích nhiều đánh giá (1-n) |
| customers → saved_promotions | Một khách hàng lưu nhiều mã khuyến mãi (1-n) |
| customers → reservations | Một khách hàng có nhiều lượt đặt bàn (1-n) |
| admins → reservations | Một admin xác nhận nhiều đặt bàn (1-n) |
| admins → contacts | Một admin phản hồi nhiều liên hệ (1-n) |
| admins → contact_replies | Một admin gửi nhiều phản hồi (1-n) |
| categories → menu_items | Một danh mục chứa nhiều món ăn (1-n) |
| menu_items → cart | Một món ăn có trong nhiều giỏ hàng (1-n) |
| menu_items → order_items | Một món ăn có trong nhiều chi tiết đơn hàng (1-n) |
| menu_items → reviews | Một món ăn nhận nhiều đánh giá (1-n) |
| orders → order_items | Một đơn hàng chứa nhiều chi tiết (1-n) |
| reviews → review_likes | Một đánh giá có nhiều lượt thích (1-n) |
| dining_tables → reservation_tables | Một bàn được đặt nhiều lần (1-n) |
| reservations → reservation_tables | Một lượt đặt bàn có thể đặt nhiều bàn (1-n) |
| contacts → contact_replies | Một liên hệ có nhiều phản hồi (1-n) |
