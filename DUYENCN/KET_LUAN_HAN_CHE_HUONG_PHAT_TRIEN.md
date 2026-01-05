# CHƯƠNG 5: KẾT LUẬN VÀ HƯỚNG PHÁT TRIỂN

## 5.1. Kết luận

Sau thời gian nghiên cứu và thực hiện đề tài "Xây dựng website giới thiệu và đặt món cho nhà hàng Ngon Gallery", đề tài đã đạt được những kết quả như sau:

### 5.1.1. Về mặt lý thuyết

Đề tài đã nghiên cứu và tìm hiểu được các kiến thức nền tảng về phát triển ứng dụng web bao gồm: ngôn ngữ lập trình PHP, hệ quản trị cơ sở dữ liệu MySQL, các công nghệ front-end như HTML5, CSS3, JavaScript. Bên cạnh đó, đề tài cũng đã tìm hiểu về quy trình nghiệp vụ của một nhà hàng từ việc quản lý thực đơn, tiếp nhận đơn hàng, đặt bàn cho đến chăm sóc khách hàng.

### 5.1.2. Về mặt thực tiễn

Đề tài đã xây dựng thành công website với các chức năng chính:

**Đối với khách hàng:**
- Đăng ký, đăng nhập, quản lý thông tin tài khoản cá nhân
- Xem thực đơn, tìm kiếm và lọc món ăn theo danh mục, vùng miền
- Thêm món vào giỏ hàng và thực hiện đặt hàng trực tuyến
- Đặt bàn trước theo ngày giờ mong muốn
- Đánh giá và nhận xét về món ăn đã sử dụng
- Sử dụng voucher giảm giá và tích lũy điểm thưởng
- Quản lý thẻ thành viên, nạp tiền và theo dõi lịch sử giao dịch
- Lưu món ăn yêu thích
- Nhận thông báo tin nhắn phản hồi từ nhà hàng
- Tương tác với chatbot trợ lý ảo "Ngon Gallery AI" để được hỗ trợ nhanh các thông tin như: xem thực đơn, đặt bàn, giờ mở cửa, địa chỉ nhà hàng, chương trình khuyến mãi

**Đối với quản trị viên:**
- Quản lý danh mục và thực đơn món ăn
- Xử lý đơn hàng và cập nhật trạng thái giao hàng
- Quản lý đặt bàn và xác nhận lịch hẹn
- Quản lý thông tin khách hàng và thẻ thành viên
- Tạo và quản lý các chương trình khuyến mãi, voucher, combo
- Quản lý điểm thưởng và xét duyệt yêu cầu nạp tiền
- Duyệt và quản lý đánh giá của khách hàng
- Trả lời tin nhắn liên hệ từ khách hàng
- Xem báo cáo thống kê doanh thu

Website được thiết kế với giao diện thân thiện theo phong cách dark theme hiện đại, dễ sử dụng và tương thích tốt trên các thiết bị di động. Hệ thống cơ sở dữ liệu được thiết kế hợp lý, đảm bảo tính toàn vẹn và nhất quán của dữ liệu. Ngoài ra, website còn hỗ trợ đa ngôn ngữ (Tiếng Việt và Tiếng Anh).

## 5.2. Hạn chế

Bên cạnh những kết quả đạt được, đề tài vẫn còn một số hạn chế:

**Về chức năng:**
- Chưa tích hợp được các cổng thanh toán trực tuyến như VNPay, MoMo, ZaloPay. Hiện tại chỉ hỗ trợ thanh toán khi nhận hàng, chuyển khoản ngân hàng và thanh toán bằng thẻ thành viên.
- Chatbot trợ lý ảo "Ngon Gallery AI" hiện tại hoạt động theo kịch bản có sẵn với các câu trả lời được lập trình trước, chưa tích hợp công nghệ AI thực sự (như ChatGPT, Gemini) để có thể hiểu và trả lời các câu hỏi phức tạp, đa dạng hơn.
- Tính năng đánh giá chưa hỗ trợ khách hàng đính kèm hình ảnh thực tế của món ăn.
- Chưa có hệ thống thông báo đẩy (push notification) trên trình duyệt hoặc gửi email tự động khi có cập nhật đơn hàng.

**Về kỹ thuật:**
- Sử dụng PHP thuần chưa theo mô hình MVC chuẩn nên việc bảo trì và mở rộng còn gặp khó khăn khi dự án phát triển lớn hơn.
- Chưa áp dụng các kỹ thuật tối ưu hiệu năng như caching, lazy loading hình ảnh.
- Chưa có hệ thống kiểm thử tự động (unit test).

## 5.3. Hướng phát triển

Để hoàn thiện và nâng cao chất lượng của website, đề tài đề xuất các hướng phát triển sau:

**Trong ngắn hạn:**
- Tích hợp các cổng thanh toán trực tuyến phổ biến tại Việt Nam như VNPay, MoMo, ZaloPay để tăng sự tiện lợi cho khách hàng.
- Xây dựng hệ thống gửi email tự động thông báo xác nhận đơn hàng, cập nhật trạng thái giao hàng.
- Bổ sung tính năng upload hình ảnh khi đánh giá món ăn.
- Nâng cấp chatbot tích hợp AI thực sự (như ChatGPT API) để trả lời thông minh hơn.

**Trong trung hạn:**
- Phát triển ứng dụng di động (mobile app) trên nền tảng Android và iOS để tiếp cận nhiều khách hàng hơn.
- Tích hợp thông báo đẩy (push notification) cho cả web và mobile.
- Phát triển hệ thống báo cáo thống kê chi tiết hơn với biểu đồ trực quan, xuất file Excel/PDF.
- Thêm tính năng đặt bàn với sơ đồ bàn trực quan.

**Trong dài hạn:**
- Chuyển đổi sang framework Laravel hoặc tách riêng backend API và frontend SPA để dễ dàng mở rộng và bảo trì.
- Ứng dụng trí tuệ nhân tạo để gợi ý món ăn dựa trên sở thích và lịch sử đặt hàng của khách.
- Tích hợp với các nền tảng giao đồ ăn như GrabFood, ShopeeFood để mở rộng kênh bán hàng.
- Phát triển hệ thống quản lý chuỗi nhà hàng nếu có nhu cầu mở rộng quy mô kinh doanh.

## 5.4. Kết luận chung

Đề tài "Xây dựng website giới thiệu và đặt món cho nhà hàng Ngon Gallery" đã hoàn thành các mục tiêu đề ra, xây dựng được một hệ thống website hoàn chỉnh với nhiều tính năng phong phú phục vụ cho hoạt động kinh doanh của nhà hàng. Website không chỉ đáp ứng các chức năng cơ bản như xem thực đơn, đặt hàng, đặt bàn mà còn có các tính năng nâng cao như hệ thống voucher, tích điểm, thẻ thành viên, chatbot hỗ trợ và đa ngôn ngữ.

Mặc dù còn một số hạn chế nhưng với nền tảng hiện tại, hệ thống hoàn toàn có thể được tiếp tục phát triển và hoàn thiện trong tương lai để đáp ứng tốt hơn nhu cầu của người dùng cũng như xu hướng phát triển của công nghệ.

Qua quá trình thực hiện đề tài, sinh viên đã tích lũy được nhiều kiến thức và kỹ năng thực tế về phát triển ứng dụng web, từ việc phân tích yêu cầu, thiết kế cơ sở dữ liệu, xây dựng giao diện cho đến lập trình các chức năng của hệ thống. Đây là những kinh nghiệm quý báu để sinh viên có thể áp dụng vào công việc thực tế sau khi ra trường.
