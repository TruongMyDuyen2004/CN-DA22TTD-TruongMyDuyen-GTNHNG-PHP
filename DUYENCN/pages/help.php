<?php
$page_title = "Trợ giúp";
$current_lang = getCurrentLanguage();
?>

<!-- Help Hero Section -->
<section class="about-hero">
    <div class="about-hero-content">
        <span class="section-badge">Hỗ trợ</span>
        <h1 class="about-hero-title">Trung Tâm Trợ Giúp</h1>
        <p class="about-hero-subtitle">Tìm câu trả lời nhanh chóng cho mọi thắc mắc</p>
    </div>
</section>

<section class="help-page">
    <div class="help-container">
        
        <!-- Quick Links -->
        <div class="quick-links">
            <a href="auth/register.php" class="quick-card">
                <div class="quick-icon"><i class="fas fa-user-plus"></i></div>
                <span>Đăng ký</span>
            </a>
            <a href="auth/login.php" class="quick-card">
                <div class="quick-icon"><i class="fas fa-sign-in-alt"></i></div>
                <span>Đăng nhập</span>
            </a>
            <a href="index.php?page=menu" class="quick-card">
                <div class="quick-icon"><i class="fas fa-utensils"></i></div>
                <span>Thực đơn</span>
            </a>
            <a href="index.php?page=reservation" class="quick-card">
                <div class="quick-icon"><i class="fas fa-calendar-alt"></i></div>
                <span>Đặt bàn</span>
            </a>
            <a href="index.php?page=promotions" class="quick-card">
                <div class="quick-icon"><i class="fas fa-tags"></i></div>
                <span>Khuyến mãi</span>
            </a>
            <a href="index.php?page=contact" class="quick-card">
                <div class="quick-icon"><i class="fas fa-headset"></i></div>
                <span>Liên hệ</span>
            </a>
        </div>

        <!-- Search -->
        <div class="search-wrapper">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="helpSearch" placeholder="Tìm kiếm câu hỏi..." onkeyup="searchFAQ()">
            </div>
        </div>

        <!-- FAQ Grid -->
        <div class="faq-grid">

            <!-- Tài khoản -->
            <div class="faq-card">
                <div class="faq-card-header">
                    <div class="faq-card-icon"><i class="fas fa-user"></i></div>
                    <h3>Tài khoản</h3>
                </div>
                <div class="faq-card-body">
                    <div class="faq-item" onclick="toggleFAQ(this)">
                        <div class="faq-q">
                            <span>Làm thế nào để đăng ký tài khoản?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-a">
                            <p>Nhấn vào biểu tượng người dùng → Chọn "Đăng ký" → Điền thông tin (họ tên, email, số điện thoại, mật khẩu) → Nhấn "Đăng ký" để hoàn tất</p>
                            <a href="auth/register.php" class="action-btn"><i class="fas fa-arrow-right"></i> Đăng ký ngay</a>
                        </div>
                    </div>
                    <div class="faq-item" onclick="toggleFAQ(this)">
                        <div class="faq-q">
                            <span>Tôi quên mật khẩu, phải làm sao?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-a">
                            <p>Vào trang Đăng nhập → Nhấn "Quên mật khẩu?" → Nhập email đã đăng ký → Kiểm tra hộp thư và làm theo hướng dẫn</p>
                            <a href="auth/forgot-password.php" class="action-btn"><i class="fas fa-arrow-right"></i> Lấy lại mật khẩu</a>
                        </div>
                    </div>
                    <div class="faq-item" onclick="toggleFAQ(this)">
                        <div class="faq-q">
                            <span>Làm sao để thay đổi thông tin cá nhân?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-a">
                            <p>Đăng nhập → Nhấn vào tên của bạn ở góc trên → Chọn "Hồ sơ" → Chỉnh sửa thông tin cần thiết → Nhấn "Lưu thay đổi"</p>
                            <a href="index.php?page=profile" class="action-btn"><i class="fas fa-arrow-right"></i> Vào hồ sơ</a>
                        </div>
                    </div>
                    <div class="faq-item" onclick="toggleFAQ(this)">
                        <div class="faq-q">
                            <span>Làm sao để đổi mật khẩu?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-a">
                            <p>Đăng nhập → Vào "Hồ sơ" → Chọn tab "Đổi mật khẩu" → Nhập mật khẩu cũ và mật khẩu mới → Xác nhận</p>
                            <a href="index.php?page=profile" class="action-btn"><i class="fas fa-arrow-right"></i> Đổi mật khẩu</a>
                        </div>
                    </div>
                </div>
                <div class="faq-card-footer">
                    <a href="auth/login.php"><i class="fas fa-sign-in-alt"></i> Đăng nhập</a>
                    <a href="auth/register.php"><i class="fas fa-user-plus"></i> Đăng ký</a>
                </div>
            </div>

            <!-- Đặt hàng -->
            <div class="faq-card">
                <div class="faq-card-header">
                    <div class="faq-card-icon"><i class="fas fa-shopping-cart"></i></div>
                    <h3>Đặt hàng</h3>
                </div>
                <div class="faq-card-body">
                    <div class="faq-item" onclick="toggleFAQ(this)">
                        <div class="faq-q">
                            <span>Làm thế nào để đặt món ăn?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-a">
                            <p>Vào Thực đơn → Chọn món yêu thích → Chọn số lượng, ghi chú (nếu có) → Thêm vào giỏ hàng → Vào giỏ hàng → Thanh toán</p>
                            <a href="index.php?page=menu" class="action-btn"><i class="fas fa-arrow-right"></i> Xem thực đơn</a>
                        </div>
                    </div>
                    <div class="faq-item" onclick="toggleFAQ(this)">
                        <div class="faq-q">
                            <span>Tôi có thể hủy đơn hàng không?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-a">
                            <p>Có thể hủy khi đơn hàng chưa được xác nhận. Vào "Đơn hàng của tôi" → Chọn đơn cần hủy → Nhấn "Hủy đơn" → Xác nhận lý do hủy</p>
                            <a href="index.php?page=orders" class="action-btn"><i class="fas fa-arrow-right"></i> Xem đơn hàng</a>
                        </div>
                    </div>
                    <div class="faq-item" onclick="toggleFAQ(this)">
                        <div class="faq-q">
                            <span>Làm sao để theo dõi đơn hàng?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-a">
                            <p>Đăng nhập → Vào "Đơn hàng của tôi" → Xem trạng thái từng đơn (Chờ xác nhận, Đang chuẩn bị, Đang giao, Hoàn thành)</p>
                            <a href="index.php?page=orders" class="action-btn"><i class="fas fa-arrow-right"></i> Theo dõi đơn</a>
                        </div>
                    </div>
                    <div class="faq-item" onclick="toggleFAQ(this)">
                        <div class="faq-q">
                            <span>Làm sao để thêm món vào yêu thích?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-a">
                            <p>Khi xem món ăn, nhấn vào biểu tượng trái tim ❤️ để thêm vào danh sách yêu thích. Xem lại tại mục "Yêu thích"</p>
                            <a href="index.php?page=favorites" class="action-btn"><i class="fas fa-arrow-right"></i> Xem yêu thích</a>
                        </div>
                    </div>
                </div>
                <div class="faq-card-footer">
                    <a href="index.php?page=menu"><i class="fas fa-utensils"></i> Thực đơn</a>
                    <a href="index.php?page=cart"><i class="fas fa-shopping-cart"></i> Giỏ hàng</a>
                </div>
            </div>

            <!-- Thanh toán & Giao hàng -->
            <div class="faq-card">
                <div class="faq-card-header">
                    <div class="faq-card-icon"><i class="fas fa-credit-card"></i></div>
                    <h3>Thanh toán & Giao hàng</h3>
                </div>
                <div class="faq-card-body">
                    <div class="faq-item" onclick="toggleFAQ(this)">
                        <div class="faq-q">
                            <span>Có những hình thức thanh toán nào?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-a">
                            <p><strong>COD:</strong> Trả tiền mặt khi nhận hàng<br>
                            <strong>Thẻ thành viên:</strong> Trừ từ số dư thẻ (cần nạp tiền trước)<br>
                            <strong>Chuyển khoản:</strong> Qua ngân hàng (quét mã QR)</p>
                            <a href="index.php?page=member-card" class="action-btn"><i class="fas fa-arrow-right"></i> Xem thẻ của tôi</a>
                        </div>
                    </div>
                    <div class="faq-item" onclick="toggleFAQ(this)">
                        <div class="faq-q">
                            <span>Phí giao hàng là bao nhiêu?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-a">
                            <div class="fee-list">
                                <div class="fee-item"><span>Dưới 3km</span><strong>Miễn phí</strong></div>
                                <div class="fee-item"><span>3-5km</span><strong>15.000đ</strong></div>
                                <div class="fee-item"><span>5-10km</span><strong>25.000đ</strong></div>
                            </div>
                            <p class="note">* Miễn phí giao hàng cho đơn từ 300.000đ</p>
                        </div>
                    </div>
                    <div class="faq-item" onclick="toggleFAQ(this)">
                        <div class="faq-q">
                            <span>Thời gian giao hàng là bao lâu?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-a">
                            <p>Trung bình <strong>30-45 phút</strong> tùy khoảng cách và tình trạng giao thông. Giờ cao điểm có thể lâu hơn.</p>
                        </div>
                    </div>
                    <div class="faq-item" onclick="toggleFAQ(this)">
                        <div class="faq-q">
                            <span>Làm sao để xem hóa đơn?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-a">
                            <p>Vào "Đơn hàng của tôi" → Chọn đơn hàng đã hoàn thành → Nhấn "Xem hóa đơn" để xem chi tiết hoặc in hóa đơn</p>
                            <a href="index.php?page=orders" class="action-btn"><i class="fas fa-arrow-right"></i> Xem đơn hàng</a>
                        </div>
                    </div>
                </div>
                <div class="faq-card-footer">
                    <a href="index.php?page=member-card"><i class="fas fa-id-card"></i> Thẻ thành viên</a>
                    <a href="index.php?page=orders"><i class="fas fa-box"></i> Đơn hàng</a>
                </div>
            </div>

            <!-- Đặt bàn -->
            <div class="faq-card">
                <div class="faq-card-header">
                    <div class="faq-card-icon"><i class="fas fa-calendar-check"></i></div>
                    <h3>Đặt bàn</h3>
                </div>
                <div class="faq-card-body">
                    <div class="faq-item" onclick="toggleFAQ(this)">
                        <div class="faq-q">
                            <span>Làm thế nào để đặt bàn?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-a">
                            <p>Nhấn "Đặt bàn" → Chọn ngày, giờ, số người → Điền thông tin liên hệ → Ghi chú yêu cầu đặc biệt (nếu có) → Xác nhận đặt bàn</p>
                            <a href="index.php?page=reservation" class="action-btn"><i class="fas fa-arrow-right"></i> Đặt bàn ngay</a>
                        </div>
                    </div>
                    <div class="faq-item" onclick="toggleFAQ(this)">
                        <div class="faq-q">
                            <span>Tôi có thể hủy/thay đổi lịch đặt bàn không?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-a">
                            <p>Có thể hủy hoặc thay đổi trước giờ đặt ít nhất 2 tiếng. Vào "Lịch đặt bàn của tôi" → Chọn lịch cần thay đổi → Hủy hoặc chỉnh sửa</p>
                            <a href="index.php?page=my-reservations" class="action-btn"><i class="fas fa-arrow-right"></i> Xem lịch đặt bàn</a>
                        </div>
                    </div>
                    <div class="faq-item" onclick="toggleFAQ(this)">
                        <div class="faq-q">
                            <span>Đặt bàn có mất phí không?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-a">
                            <p>Đặt bàn hoàn toàn <strong>miễn phí</strong>. Bạn chỉ thanh toán khi dùng bữa tại nhà hàng.</p>
                        </div>
                    </div>
                    <div class="faq-item" onclick="toggleFAQ(this)">
                        <div class="faq-q">
                            <span>Nhà hàng có nhận đặt bàn cho tiệc/sự kiện không?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-a">
                            <p>Có! Chúng tôi nhận đặt bàn cho tiệc sinh nhật, họp mặt, sự kiện công ty. Vui lòng liên hệ trước ít nhất 3 ngày để được tư vấn.</p>
                            <a href="index.php?page=contact" class="action-btn"><i class="fas fa-arrow-right"></i> Liên hệ tư vấn</a>
                        </div>
                    </div>
                </div>
                <div class="faq-card-footer">
                    <a href="index.php?page=reservation"><i class="fas fa-calendar-alt"></i> Đặt bàn</a>
                    <a href="index.php?page=my-reservations"><i class="fas fa-list"></i> Lịch đặt bàn</a>
                </div>
            </div>

            <!-- Thẻ thành viên & Nạp tiền -->
            <div class="faq-card">
                <div class="faq-card-header">
                    <div class="faq-card-icon"><i class="fas fa-id-card"></i></div>
                    <h3>Thẻ thành viên & Nạp tiền</h3>
                </div>
                <div class="faq-card-body">
                    <div class="faq-item" onclick="toggleFAQ(this)">
                        <div class="faq-q">
                            <span>Làm sao để có thẻ thành viên?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-a">
                            <p>Thẻ thành viên được tạo <strong>tự động</strong> khi bạn đăng ký tài khoản. Mỗi thẻ có mã số riêng và mã QR để quét.</p>
                            <a href="index.php?page=member-card" class="action-btn"><i class="fas fa-arrow-right"></i> Xem thẻ của tôi</a>
                        </div>
                    </div>
                    <div class="faq-item" onclick="toggleFAQ(this)">
                        <div class="faq-q">
                            <span>Làm sao để nạp tiền vào thẻ?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-a">
                            <p>Vào "Thẻ thành viên" → Nhấn "Nạp tiền" → Chọn số tiền cần nạp → Quét mã QR để chuyển khoản → Hệ thống tự động cộng tiền sau khi xác nhận</p>
                            <a href="index.php?page=member-card" class="action-btn"><i class="fas fa-arrow-right"></i> Nạp tiền ngay</a>
                        </div>
                    </div>
                    <div class="faq-item" onclick="toggleFAQ(this)">
                        <div class="faq-q">
                            <span>Thẻ thành viên có ưu đãi gì?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-a">
                            <ul class="benefit-list">
                                <li><i class="fas fa-check-circle"></i> Tích điểm 1% mỗi đơn hàng</li>
                                <li><i class="fas fa-check-circle"></i> Giảm giá đặc biệt cho thành viên</li>
                                <li><i class="fas fa-check-circle"></i> Ưu tiên đặt bàn dịp lễ</li>
                                <li><i class="fas fa-check-circle"></i> Quà sinh nhật hàng năm</li>
                                <li><i class="fas fa-check-circle"></i> Thanh toán nhanh không cần tiền mặt</li>
                            </ul>
                        </div>
                    </div>
                    <div class="faq-item" onclick="toggleFAQ(this)">
                        <div class="faq-q">
                            <span>Nạp tiền bao lâu thì được cộng vào thẻ?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-a">
                            <p>Hệ thống tự động xác nhận và cộng tiền trong vòng <strong>1-5 phút</strong> sau khi chuyển khoản thành công. Nếu quá 10 phút chưa nhận được, vui lòng liên hệ hỗ trợ.</p>
                        </div>
                    </div>
                </div>
                <div class="faq-card-footer">
                    <a href="index.php?page=member-card"><i class="fas fa-wallet"></i> Thẻ của tôi</a>
                    <a href="index.php?page=my-points"><i class="fas fa-coins"></i> Điểm thưởng</a>
                </div>
            </div>

            <!-- Điểm thưởng & Voucher -->
            <div class="faq-card">
                <div class="faq-card-header">
                    <div class="faq-card-icon"><i class="fas fa-gift"></i></div>
                    <h3>Điểm thưởng & Voucher</h3>
                </div>
                <div class="faq-card-body">
                    <div class="faq-item" onclick="toggleFAQ(this)">
                        <div class="faq-q">
                            <span>Làm sao để tích điểm?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-a">
                            <p>Điểm được tích <strong>tự động</strong> sau mỗi đơn hàng hoàn thành. Tỷ lệ: <strong>1% giá trị đơn hàng = điểm thưởng</strong> (VD: đơn 100.000đ = 1.000 điểm)</p>
                            <a href="index.php?page=my-points" class="action-btn"><i class="fas fa-arrow-right"></i> Xem điểm của tôi</a>
                        </div>
                    </div>
                    <div class="faq-item" onclick="toggleFAQ(this)">
                        <div class="faq-q">
                            <span>Điểm thưởng dùng để làm gì?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-a">
                            <p>Điểm thưởng có thể đổi lấy <strong>voucher giảm giá</strong> hoặc <strong>quà tặng</strong>. Vào mục "Điểm thưởng" để xem các phần thưởng có thể đổi.</p>
                            <a href="index.php?page=my-points" class="action-btn"><i class="fas fa-arrow-right"></i> Đổi điểm ngay</a>
                        </div>
                    </div>
                    <div class="faq-item" onclick="toggleFAQ(this)">
                        <div class="faq-q">
                            <span>Làm sao để sử dụng voucher?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-a">
                            <p>Khi thanh toán → Nhấn "Áp dụng voucher" → Chọn voucher từ danh sách hoặc nhập mã → Giảm giá sẽ được áp dụng tự động</p>
                            <a href="index.php?page=vouchers" class="action-btn"><i class="fas fa-arrow-right"></i> Xem voucher của tôi</a>
                        </div>
                    </div>
                    <div class="faq-item" onclick="toggleFAQ(this)">
                        <div class="faq-q">
                            <span>Voucher có thời hạn sử dụng không?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-a">
                            <p>Có, mỗi voucher có thời hạn riêng. Kiểm tra ngày hết hạn trong mục "Voucher của tôi". Voucher hết hạn sẽ không thể sử dụng.</p>
                        </div>
                    </div>
                </div>
                <div class="faq-card-footer">
                    <a href="index.php?page=my-points"><i class="fas fa-coins"></i> Điểm thưởng</a>
                    <a href="index.php?page=vouchers"><i class="fas fa-ticket-alt"></i> Voucher</a>
                </div>
            </div>

            <!-- Khuyến mãi -->
            <div class="faq-card">
                <div class="faq-card-header">
                    <div class="faq-card-icon"><i class="fas fa-tags"></i></div>
                    <h3>Khuyến mãi</h3>
                </div>
                <div class="faq-card-body">
                    <div class="faq-item" onclick="toggleFAQ(this)">
                        <div class="faq-q">
                            <span>Làm sao để xem các chương trình khuyến mãi?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-a">
                            <p>Vào mục "Khuyến mãi" trên menu để xem tất cả chương trình đang diễn ra. Các món đang giảm giá sẽ hiển thị tag "Giảm giá" trên thực đơn.</p>
                            <a href="index.php?page=promotions" class="action-btn"><i class="fas fa-arrow-right"></i> Xem khuyến mãi</a>
                        </div>
                    </div>
                    <div class="faq-item" onclick="toggleFAQ(this)">
                        <div class="faq-q">
                            <span>Có thể kết hợp nhiều khuyến mãi không?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-a">
                            <p>Tùy từng chương trình. Một số khuyến mãi có thể kết hợp với voucher, một số thì không. Chi tiết điều kiện sẽ được ghi rõ trong mỗi chương trình.</p>
                        </div>
                    </div>
                    <div class="faq-item" onclick="toggleFAQ(this)">
                        <div class="faq-q">
                            <span>Làm sao để nhận thông báo khuyến mãi mới?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-a">
                            <p>Đăng ký tài khoản và bật nhận thông báo qua email. Bạn cũng có thể theo dõi fanpage để cập nhật khuyến mãi mới nhất.</p>
                        </div>
                    </div>
                </div>
                <div class="faq-card-footer">
                    <a href="index.php?page=promotions"><i class="fas fa-percent"></i> Khuyến mãi</a>
                    <a href="index.php?page=menu"><i class="fas fa-utensils"></i> Thực đơn</a>
                </div>
            </div>

            <!-- Đánh giá -->
            <div class="faq-card">
                <div class="faq-card-header">
                    <div class="faq-card-icon"><i class="fas fa-star"></i></div>
                    <h3>Đánh giá</h3>
                </div>
                <div class="faq-card-body">
                    <div class="faq-item" onclick="toggleFAQ(this)">
                        <div class="faq-q">
                            <span>Làm sao để đánh giá món ăn?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-a">
                            <p>Sau khi đơn hàng hoàn thành → Vào chi tiết món ăn → Nhấn "Viết đánh giá" → Chọn số sao, viết nhận xét → Có thể đính kèm hình ảnh → Gửi đánh giá</p>
                            <a href="index.php?page=orders" class="action-btn"><i class="fas fa-arrow-right"></i> Xem đơn hàng</a>
                        </div>
                    </div>
                    <div class="faq-item" onclick="toggleFAQ(this)">
                        <div class="faq-q">
                            <span>Tôi có thể sửa/xóa đánh giá không?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-a">
                            <p>Có thể chỉnh sửa hoặc xóa đánh giá của mình. Vào chi tiết món → Tìm đánh giá của bạn → Nhấn biểu tượng chỉnh sửa hoặc xóa.</p>
                        </div>
                    </div>
                    <div class="faq-item" onclick="toggleFAQ(this)">
                        <div class="faq-q">
                            <span>Làm sao để xem tất cả đánh giá?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-a">
                            <p>Vào chi tiết món ăn → Kéo xuống phần "Đánh giá" → Nhấn "Xem tất cả" để xem toàn bộ đánh giá từ khách hàng khác.</p>
                            <a href="index.php?page=all-reviews" class="action-btn"><i class="fas fa-arrow-right"></i> Xem tất cả đánh giá</a>
                        </div>
                    </div>
                    <div class="faq-item" onclick="toggleFAQ(this)">
                        <div class="faq-q">
                            <span>Đánh giá có được thưởng điểm không?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-a">
                            <p>Có! Mỗi đánh giá hợp lệ (có nội dung và hình ảnh) sẽ được thưởng điểm. Đánh giá càng chi tiết, điểm thưởng càng cao.</p>
                        </div>
                    </div>
                </div>
                <div class="faq-card-footer">
                    <a href="index.php?page=all-reviews"><i class="fas fa-comments"></i> Tất cả đánh giá</a>
                    <a href="index.php?page=menu"><i class="fas fa-utensils"></i> Thực đơn</a>
                </div>
            </div>

        </div>

        <!-- Contact CTA -->
        <div class="contact-cta">
            <div class="cta-left">
                <div class="cta-icon-wrapper">
                    <i class="fas fa-headset"></i>
                </div>
                <div class="cta-text">
                    <h4>KHÔNG TÌM THẤY CÂU TRẢ LỜI?</h4>
                    <p>Đội ngũ hỗ trợ luôn sẵn sàng giúp đỡ bạn 24/7</p>
                </div>
            </div>
            <div class="cta-buttons">
                <a href="tel:0384848127" class="cta-btn phone">
                    <i class="fas fa-phone-alt"></i> 0384 848 127
                </a>
                <a href="index.php?page=contact" class="cta-btn message">
                    <i class="fas fa-paper-plane"></i> Gửi tin nhắn
                </a>
            </div>
        </div>
    </div>
</section>

<style>
.help-page {
    background: #f8fafc;
    min-height: 100vh;
    padding: 0 0 4rem;
}

.help-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

/* Quick Links */
.quick-links {
    display: flex;
    justify-content: center;
    gap: 1rem;
    flex-wrap: wrap;
    padding: 2rem 0;
}

.quick-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1.25rem 1.5rem;
    background: #fff;
    border-radius: 16px;
    text-decoration: none;
    color: #374151;
    font-weight: 600;
    font-size: 0.9rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transition: all 0.3s;
    min-width: 100px;
}

.quick-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(34, 197, 94, 0.2);
    color: #16a34a;
}

.quick-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.quick-icon i {
    font-size: 1.3rem;
    color: #fff;
}

/* Search */
.search-wrapper {
    max-width: 500px;
    margin: 0 auto 2.5rem;
}

.search-box {
    position: relative;
}

.search-box i {
    position: absolute;
    left: 1.25rem;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
}

.search-box input {
    width: 100%;
    padding: 1rem 1.25rem 1rem 3.5rem;
    border: 2px solid #e5e7eb;
    border-radius: 50px;
    font-size: 1rem;
    background: #fff;
    transition: all 0.3s;
    color: #111;
}

.search-box input:focus {
    outline: none;
    border-color: #22c55e;
    box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.1);
}

/* FAQ Grid */
.faq-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.faq-card {
    background: #fff;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}

.faq-card-header {
    padding: 1.25rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: #fff;
}

.faq-card-icon {
    width: 42px;
    height: 42px;
    background: rgba(255,255,255,0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.faq-card-icon i {
    font-size: 1.2rem;
    color: #fff;
}

.faq-card-header h3 {
    font-size: 1.15rem;
    margin: 0;
    font-weight: 700;
}

.faq-card-body {
    padding: 0.5rem;
}

/* FAQ Items */
.faq-item {
    margin: 0.5rem;
    border-radius: 12px;
    overflow: hidden;
    cursor: pointer;
    border: 1px solid #f1f5f9;
    transition: all 0.3s;
}

.faq-item:hover {
    border-color: #22c55e;
    background: #f9fafb;
}

.faq-q {
    padding: 1rem 1.25rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.faq-q span {
    font-weight: 600;
    color: #374151;
    font-size: 0.95rem;
}

.faq-q i {
    color: #22c55e;
    font-size: 0.85rem;
    transition: transform 0.3s;
}

.faq-item.active {
    border-color: #22c55e;
    background: #f0fdf4;
}

.faq-item.active .faq-q span {
    color: #166534;
}

.faq-item.active .faq-q i {
    transform: rotate(180deg);
}

.faq-a {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}

.faq-item.active .faq-a {
    max-height: 500px;
}

.faq-a p, .faq-a ul {
    padding: 0.75rem 1.25rem;
    color: #4b5563;
    line-height: 1.7;
    margin: 0;
    font-size: 0.95rem;
}

.faq-a strong {
    color: #166534;
}

/* Action Button */
.action-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0.5rem 1.25rem 1rem;
    padding: 0.6rem 1.2rem;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: #fff;
    text-decoration: none;
    border-radius: 25px;
    font-size: 0.85rem;
    font-weight: 600;
    transition: all 0.3s;
}

.action-btn:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
}

/* Fee List */
.fee-list {
    margin: 0.5rem 1.25rem;
}

.fee-item {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 1rem;
    background: #fff;
    border-radius: 8px;
    margin-bottom: 0.4rem;
    border: 1px solid #e5e7eb;
}

.fee-item span { color: #6b7280; }
.fee-item strong { color: #16a34a; }

.note {
    font-size: 0.85rem !important;
    color: #9ca3af !important;
    font-style: italic;
}

/* Benefit List */
.benefit-list {
    list-style: none;
    padding: 0.75rem 1.25rem !important;
    margin: 0;
}

.benefit-list li {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.4rem 0;
    color: #374151;
    font-size: 0.9rem;
}

.benefit-list li i {
    color: #22c55e;
    font-size: 0.85rem;
}

/* Card Footer */
.faq-card-footer {
    display: flex;
    gap: 0.5rem;
    padding: 1rem 1.25rem;
    background: #f8fafc;
    border-top: 1px solid #e5e7eb;
}

.faq-card-footer a {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.6rem 1rem;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    color: #374151;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 600;
    transition: all 0.3s;
}

.faq-card-footer a:hover {
    border-color: #22c55e;
    color: #16a34a;
    background: #f0fdf4;
}

/* Contact CTA - Modern Design */
.contact-cta {
    background: linear-gradient(135deg, #15803d 0%, #22c55e 50%, #4ade80 100%);
    border-radius: 24px;
    padding: 2rem 3rem;
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    gap: 2rem;
    position: relative;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(22, 101, 52, 0.3);
}

.contact-cta::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
}

.contact-cta::after {
    content: '';
    position: absolute;
    bottom: -30%;
    left: 10%;
    width: 200px;
    height: 200px;
    background: rgba(255,255,255,0.05);
    border-radius: 50%;
}

.cta-left {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    color: #fff;
    position: relative;
    z-index: 1;
}

.cta-icon-wrapper {
    width: 60px;
    height: 60px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.cta-icon-wrapper i {
    font-size: 1.75rem;
    color: #fff;
}

.cta-text h4 {
    font-size: 1.25rem;
    margin: 0 0 0.25rem;
    font-weight: 700;
    letter-spacing: 0.5px;
    color: #fff;
    text-align: left;
}

.cta-text p {
    margin: 0;
    opacity: 0.9;
    font-size: 0.95rem;
    font-style: italic;
    color: #fff;
    text-align: left;
}

.cta-buttons {
    display: flex;
    gap: 1rem;
    position: relative;
    z-index: 1;
    flex-wrap: nowrap;
    justify-content: center;
    flex-shrink: 0;
}

.cta-btn {
    padding: 1rem 2.5rem;
    border-radius: 50px;
    text-decoration: none;
    font-weight: 600;
    font-size: 1rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
    transition: all 0.3s;
    white-space: nowrap;
    width: 200px;
    height: 56px;
    box-sizing: border-box;
}

.cta-btn.phone {
    background: #fff;
    color: #166534;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border: 2px solid #fff;
}

.cta-btn.phone:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.2);
}

.cta-btn.message {
    background: rgba(255,255,255,0.2);
    color: #fff;
    border: 2px solid rgba(255,255,255,0.5);
}

.cta-btn.message:hover {
    background: #fff;
    color: #166534;
    border-color: #fff;
    transform: translateY(-3px);
}

/* Responsive */
@media (max-width: 1024px) {
    .faq-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 900px) {
    .contact-cta {
        flex-direction: column;
        padding: 2rem 1.5rem;
        gap: 1.25rem;
    }
    
    .cta-left {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .cta-icon-wrapper {
        width: 55px;
        height: 55px;
    }
    
    .cta-text h4 {
        font-size: 1.15rem;
        text-align: center;
    }
    
    .cta-text p {
        text-align: center;
    }
    
    .cta-buttons {
        width: 100%;
        flex-direction: row;
        justify-content: center;
        flex-wrap: nowrap;
    }
    
    .cta-btn {
        flex: 1;
        max-width: 200px;
        min-width: 150px;
        justify-content: center;
        padding: 0.85rem 1rem;
    }
}

@media (max-width: 768px) {
    .faq-grid {
        grid-template-columns: 1fr;
    }
    
    .quick-links {
        gap: 0.75rem;
    }
    
    .quick-card {
        padding: 1rem;
        min-width: 80px;
    }
    
    .quick-card span {
        font-size: 0.8rem;
    }
}

@media (max-width: 500px) {
    .cta-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .cta-btn {
        width: 100%;
        max-width: 250px;
    }
}
</style>

<script>
function toggleFAQ(el) {
    const item = el;
    const wasActive = item.classList.contains('active');
    
    // Close all items in the same card
    item.closest('.faq-card-body').querySelectorAll('.faq-item').forEach(faq => {
        faq.classList.remove('active');
    });
    
    // Toggle current item
    if (!wasActive) {
        item.classList.add('active');
    }
}

function searchFAQ() {
    const q = document.getElementById('helpSearch').value.toLowerCase().trim();
    
    // Show/hide FAQ items based on search
    document.querySelectorAll('.faq-item').forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(q) ? 'block' : 'none';
    });
    
    // Show/hide FAQ cards based on visible items
    document.querySelectorAll('.faq-card').forEach(card => {
        const items = card.querySelectorAll('.faq-item');
        let hasVisible = false;
        items.forEach(item => {
            if (item.style.display !== 'none') hasVisible = true;
        });
        card.style.display = hasVisible || !q ? 'block' : 'none';
    });
    
    // If search is empty, reset all
    if (!q) {
        document.querySelectorAll('.faq-item').forEach(item => {
            item.style.display = 'block';
        });
        document.querySelectorAll('.faq-card').forEach(card => {
            card.style.display = 'block';
        });
    }
}

// Auto-expand first item in each card on page load (optional)
document.addEventListener('DOMContentLoaded', function() {
    // Optionally expand first FAQ item in each card
    // document.querySelectorAll('.faq-card-body').forEach(body => {
    //     const firstItem = body.querySelector('.faq-item');
    //     if (firstItem) firstItem.classList.add('active');
    // });
});
</script>
