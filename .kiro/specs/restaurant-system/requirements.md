# CHƯƠNG 1. HIỆN THỰC HÓA NGHIÊN CỨU

## 1.1. Mô tả hệ thống

Hệ thống được thiết kế để hỗ trợ việc kinh doanh nhà hàng trực tuyến, giúp khách hàng dễ dàng xem thực đơn, đặt món ăn, đặt bàn, theo dõi đơn hàng và tích lũy điểm thưởng. Hệ thống phân vai trò người dùng thành quản trị viên và khách hàng, mỗi vai trò có quyền hạn riêng phù hợp với chức năng của mình trong hệ thống.

## 1.2. Phân tích thiết kế hệ thống

### 1.2.1 Đặc tả yêu cầu hệ thống

#### 1.2.1.1 Yêu cầu chức năng

**Quản lý thực đơn**
- Tạo mới, chỉnh sửa và xóa món ăn (Quản trị viên).
- Hiển thị danh sách món ăn theo danh mục (Khai vị, Món chính, Tráng miệng, Đồ uống...).
- Tìm kiếm và lọc món ăn theo tên, danh mục, vùng miền.
- Xem chi tiết món ăn gồm hình ảnh, mô tả, giá bán, trạng thái còn/hết.
- Đánh giá và nhận xét món ăn (Khách hàng).
- Thích/bỏ thích đánh giá của người khác (Khách hàng).

**Quản lý người dùng**
- Đăng ký / đăng nhập tài khoản (hỗ trợ đăng nhập bằng Google).
- Phân loại người dùng theo vai trò (Quản trị viên, Khách hàng).
- Quản lý thông tin cá nhân (Khách hàng/Quản trị viên).
- Quản lý danh sách người dùng (Quản trị viên).
- Quên mật khẩu / đặt lại mật khẩu.

**Quản lý danh mục món ăn**
- Tạo mới, chỉnh sửa và xóa danh mục món ăn (Quản trị viên).
- Liệt kê tất cả danh mục với số lượng món ăn trong mỗi danh mục.

**Xử lý giỏ hàng và đặt hàng**
- Thêm/xóa món ăn vào giỏ hàng (Khách hàng).
- Cập nhật số lượng món ăn trong giỏ hàng.
- Áp dụng mã giảm giá (voucher) khi thanh toán.
- Sử dụng điểm tích lũy để giảm giá.
- Thanh toán bằng thẻ thành viên.
- Tạo đơn hàng khi khách hàng xác nhận đặt mua.
- Theo dõi trạng thái đơn hàng (Chờ xác nhận, Đang chuẩn bị, Đang giao, Hoàn thành, Đã hủy).
- Cập nhật trạng thái đơn hàng (Quản trị viên).
- Quản lý thanh toán (Quản trị viên).
- In hóa đơn đơn hàng (Khách hàng).

**Quản lý đặt bàn**
- Đặt bàn trực tuyến với ngày, giờ, số khách (Khách hàng).
- Xem lịch sử đặt bàn của mình (Khách hàng).
- Hủy đặt bàn (Khách hàng).
- Xác nhận/từ chối đặt bàn (Quản trị viên).
- Quản lý bàn ăn trong nhà hàng (Quản trị viên).
- Gán bàn cho đặt chỗ (Quản trị viên).

**Quản lý thẻ thành viên**
- Xem thông tin thẻ thành viên (Khách hàng).
- Nạp tiền vào thẻ thành viên (Khách hàng).
- Thanh toán bằng số dư thẻ (Khách hàng).
- Xem lịch sử giao dịch thẻ (Khách hàng).
- Quản lý thẻ thành viên (Quản trị viên).
- Xử lý yêu cầu nạp tiền (Quản trị viên).

**Quản lý voucher và điểm thưởng**
- Tạo mới, chỉnh sửa và xóa voucher (Quản trị viên).
- Xem danh sách voucher khả dụng (Khách hàng).
- Áp dụng voucher khi thanh toán (Khách hàng).
- Tích điểm khi đặt hàng thành công (Khách hàng).
- Xem số điểm và hạng thành viên (Khách hàng).
- Đổi điểm lấy voucher (Khách hàng).
- Cấu hình quy tắc tích điểm (Quản trị viên).

**Quản lý yêu thích**
- Thêm/xóa món ăn yêu thích (Khách hàng).
- Xem danh sách món ăn yêu thích (Khách hàng).

**Quản lý liên hệ**
- Gửi liên hệ/phản hồi (Khách hàng).
- Xem phản hồi từ nhà hàng (Khách hàng).
- Xem và trả lời liên hệ (Quản trị viên).

**Chức năng khác**
- Trang giới thiệu (About).
- Trang trợ giúp (Help).
- Trang tin tức/khuyến mãi.
- Hỗ trợ đa ngôn ngữ (Tiếng Việt, Tiếng Anh).
- Chat hỗ trợ khách hàng (AI Chat).

#### 1.2.1.2 Yêu cầu phi chức năng

**Hiệu năng:** Website được tối ưu để tải trang nhanh, tra cứu thực đơn, xem chi tiết món ăn và xử lý đặt hàng mượt mà. Hệ thống đảm bảo phản hồi nhanh và ổn định ngay cả khi có nhiều yêu cầu từ khách hàng và quản trị viên đồng thời.

**Khả năng mở rộng:** Thiết kế linh hoạt cho phép thêm danh mục món ăn, loại món mới hoặc mở rộng các tính năng như báo cáo doanh thu, chương trình khuyến mãi, và tích hợp thanh toán trực tuyến mà không ảnh hưởng các chức năng hiện tại.

**Tính bảo mật:** Hệ thống phân quyền chặt chẽ: Quản trị viên quản lý toàn bộ hệ thống, thực đơn, danh mục, đơn hàng, đặt bàn và người dùng; khách hàng chỉ xem thực đơn, đặt hàng, đặt bàn và quản lý thông tin cá nhân. Hỗ trợ đăng nhập an toàn qua Google OAuth.

**Giao diện trực quan:** Giao diện thân thiện, trực quan với theme tối hiện đại, hỗ trợ thao tác nhanh trên máy tính, điện thoại và máy tính bảng. Hiển thị danh sách món ăn, chi tiết món, giỏ hàng, lịch sử đơn hàng, đặt bàn và các chức năng tìm kiếm, lọc dữ liệu một cách dễ dàng.

**Khả năng tương thích:** Website hoạt động tốt trên các trình duyệt phổ biến như Chrome, Firefox, Safari, Edge. Hệ thống sử dụng MySQL để quản lý cơ sở dữ liệu, dễ dàng sao lưu, mở rộng và tích hợp với các công cụ quản lý khác.

### 1.2.2 Kiến trúc hệ thống

Để phát triển website nhà hàng Ngon Gallery, kiến trúc hệ thống sẽ có các phần như sau:

#### 1.2.2.1 Giao diện người dùng (Frontend)

Đây là tầng giao diện người dùng, chịu trách nhiệm hiển thị nội dung và tương tác với người dùng bằng cách sử dụng HTML, CSS và JavaScript.

**Chức năng chính:** Cung cấp giao diện trực quan để người dùng duyệt qua thực đơn nhà hàng. Bao gồm trang chủ, danh mục món ăn, bộ lọc tìm kiếm theo vùng miền, giỏ hàng, thanh toán, đặt bàn và các trang chi tiết hiển thị đầy đủ thông tin như hình ảnh, giá bán, mô tả và đánh giá món ăn.

#### 1.2.2.2 Chức năng trang web (Backend)

Tầng này chịu trách nhiệm xử lý logic nghiệp vụ của website bằng cách sử dụng PHP để truy vấn dữ liệu được lưu trong MySQL.

**Chức năng chính:**

*Khách hàng:* Xử lý các yêu cầu xem thực đơn, thêm vào giỏ hàng, áp dụng voucher, sử dụng điểm thưởng, thanh toán bằng thẻ thành viên, đặt hàng, đặt bàn, xem lịch sử đơn hàng và đặt bàn, đánh giá món ăn, quản lý thông tin cá nhân, nạp tiền thẻ thành viên và xem thông báo từ nhà hàng.

*Quản trị viên:* Quản lý toàn bộ hệ thống, quản lý thực đơn (thêm, sửa, xóa), quản lý danh mục, xử lý đơn hàng, quản lý đặt bàn và bàn ăn, quản lý thanh toán, quản lý voucher và điểm thưởng, quản lý thẻ thành viên, xử lý yêu cầu nạp tiền, quản lý người dùng, trả lời liên hệ và xem báo cáo.

#### 1.2.2.3 Cơ sở dữ liệu (Database)

Chịu trách nhiệm lưu trữ và quản lý dữ liệu của ứng dụng bằng MySQL.

**Chức năng chính:** Lưu trữ và quản lý dữ liệu bao gồm thông tin món ăn, danh mục, người dùng (khách hàng, quản trị viên), đơn hàng, chi tiết đơn hàng, đặt bàn, bàn ăn, thẻ thành viên, giao dịch thẻ, voucher, điểm thưởng, đánh giá món ăn, yêu thích, liên hệ và tin tức.
