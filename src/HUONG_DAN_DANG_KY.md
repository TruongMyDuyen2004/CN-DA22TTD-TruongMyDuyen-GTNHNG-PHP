# Hướng Dẫn Sử Dụng Chức Năng Đăng Ký Tài Khoản

## Mô Tả Chức Năng

Chức năng đăng ký cho phép người dùng tạo tài khoản mới trên hệ thống Ngon Gallery để có thể:
- Đặt món ăn và thanh toán
- Theo dõi lịch sử đơn hàng
- Tích điểm và sử dụng voucher
- Đánh giá món ăn
- Lưu món yêu thích
- Đặt bàn trước

## Truy Cập Trang Đăng Ký

**Đường dẫn:** `auth/register.php`

Có thể truy cập bằng cách:
1. Nhấn nút "Đăng ký" trên header của trang web
2. Từ trang đăng nhập, nhấn link "Đăng ký ngay"

## Các Trường Thông Tin

### Thông tin bắt buộc (có dấu *)

| Trường | Mô tả | Yêu cầu |
|--------|-------|---------|
| Họ và tên | Tên đầy đủ của người dùng | Không được để trống |
| Email | Địa chỉ email | Định dạng email hợp lệ, chưa được đăng ký |
| Mật khẩu | Mật khẩu đăng nhập | Tối thiểu 6 ký tự |
| Xác nhận mật khẩu | Nhập lại mật khẩu | Phải khớp với mật khẩu |

### Thông tin tùy chọn

| Trường | Mô tả |
|--------|-------|
| Số điện thoại | Số điện thoại liên hệ (VD: 0912 345 678) |

## Hướng Dẫn Từng Bước

### Bước 1: Truy cập trang đăng ký
- Mở trình duyệt và truy cập website Ngon Gallery
- Nhấn vào nút "Đăng ký" hoặc truy cập trực tiếp `/auth/register.php`

### Bước 2: Điền thông tin cá nhân
1. **Họ và tên:** Nhập họ tên đầy đủ của bạn
2. **Số điện thoại:** Nhập số điện thoại (không bắt buộc)
3. **Email:** Nhập địa chỉ email hợp lệ

### Bước 3: Tạo mật khẩu
1. **Mật khẩu:** Nhập mật khẩu (tối thiểu 6 ký tự)
2. **Xác nhận mật khẩu:** Nhập lại mật khẩu để xác nhận
3. Có thể nhấn biểu tượng 👁️ để hiện/ẩn mật khẩu

### Bước 4: Hoàn tất đăng ký
- Nhấn nút **"Đăng ký"** màu xanh lá
- Nếu thành công, hệ thống sẽ tự động chuyển đến trang đăng nhập

## Đăng Ký Bằng Google

Ngoài cách đăng ký thông thường, bạn có thể:
1. Nhấn nút **"Đăng ký với Google"**
2. Chọn tài khoản Google của bạn
3. Hệ thống sẽ tự động tạo tài khoản với thông tin từ Google

## Các Thông Báo Lỗi

| Thông báo | Nguyên nhân | Cách khắc phục |
|-----------|-------------|----------------|
| "Vui lòng điền đầy đủ thông tin bắt buộc" | Chưa điền đủ các trường có dấu * | Điền đầy đủ họ tên, email, mật khẩu |
| "Mật khẩu xác nhận không khớp" | Mật khẩu và xác nhận mật khẩu khác nhau | Nhập lại mật khẩu giống nhau |
| "Mật khẩu phải có ít nhất 6 ký tự" | Mật khẩu quá ngắn | Đặt mật khẩu dài hơn 6 ký tự |
| "Email đã được sử dụng" | Email đã có tài khoản | Dùng email khác hoặc đăng nhập |
| "Có lỗi xảy ra, vui lòng thử lại" | Lỗi hệ thống | Thử lại sau hoặc liên hệ hỗ trợ |

## Lưu Ý Quan Trọng

1. **Bảo mật mật khẩu:** Mật khẩu được mã hóa an toàn trong cơ sở dữ liệu
2. **Email duy nhất:** Mỗi email chỉ có thể đăng ký một tài khoản
3. **Giữ lại thông tin:** Nếu có lỗi, thông tin đã nhập sẽ được giữ lại (trừ mật khẩu)

## Sau Khi Đăng Ký Thành Công

Sau khi đăng ký thành công, bạn có thể:
1. Đăng nhập với email và mật khẩu vừa tạo
2. Cập nhật thông tin cá nhân trong trang Profile
3. Bắt đầu sử dụng các tính năng của hệ thống

## Liên Kết Liên Quan

- **Đăng nhập:** `auth/login.php`
- **Quên mật khẩu:** `auth/forgot-password.php`
- **Trang chủ:** `index.php`
