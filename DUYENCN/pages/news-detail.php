<?php
// Lấy ID tin tức từ URL
$news_id = isset($_GET['id']) ? (int)$_GET['id'] : 1;

// Dữ liệu tin tức (tạm thời hardcode, sau này có thể lấy từ database)
$news_data = [
    1 => [
        'title' => 'Khai trương Ngon Gallery tại Trà Vinh',
        'category' => 'Sự kiện',
        'date' => '28/11/2024',
        'image' => 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=1200&q=80',
        'content' => '
            <p>Ngon Gallery chính thức khai trương tại TP. Trà Vinh, mang đến không gian ẩm thực sang trọng cho cư dân địa phương.</p>
            
            <h3>Không gian sang trọng, hiện đại</h3>
            <p>Nhà hàng tọa lạc tại vị trí đắc địa trên đường Nguyễn Thiện Thành, Phường 5, TP. Trà Vinh, với diện tích hơn 500m², được thiết kế theo phong cách hiện đại kết hợp nét truyền thống Việt Nam. Không gian được chia thành nhiều khu vực: phòng ăn chính, phòng VIP cho các buổi tiệc riêng tư, và khu vực ngoài trời thoáng mát.</p>
            
            <h3>Thực đơn đa dạng</h3>
            <p>Ngoài các món ăn signature của Ngon Gallery, nhà hàng còn giới thiệu thêm nhiều món đặc sản vùng miền, được chế biến bởi đội ngũ đầu bếp giàu kinh nghiệm.</p>
            
            <h3>Ưu đãi khai trương</h3>
            <ul>
                <li>Giảm 30% tất cả món ăn trong tuần đầu tiên</li>
                <li>Tặng voucher 200.000đ cho khách hàng đặt bàn trước</li>
                <li>Miễn phí món tráng miệng cho bàn từ 4 người trở lên</li>
            </ul>
            
            <p><strong>Địa chỉ:</strong> 126 Nguyễn Thiện Thành, Phường 5, TP. Trà Vinh</p>
            <p><strong>Giờ mở cửa:</strong> 10:00 - 22:00 hàng ngày</p>
        '
    ],
    2 => [
        'title' => 'Ra mắt thực đơn mùa đông 2024',
        'category' => 'Thực đơn',
        'date' => '25/11/2024',
        'image' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=1200&q=80',
        'content' => '
            <p>Khám phá bộ sưu tập món ăn mới với hương vị ấm áp, hoàn hảo cho những ngày se lạnh cuối năm.</p>
            
            <h3>Món mới nổi bật</h3>
            <p>Thực đơn mùa đông năm nay mang đến những món ăn đặc biệt được chế biến từ nguyên liệu theo mùa, kết hợp giữa hương vị truyền thống và phong cách hiện đại.</p>
            
            <h3>Các món đặc sắc</h3>
            <ul>
                <li>Lẩu nấm rừng - Hương vị đậm đà từ các loại nấm quý</li>
                <li>Bò hầm rượu vang - Thịt bò mềm tan trong miệng</li>
                <li>Súp bí đỏ kem tươi - Ngọt tự nhiên, ấm áp</li>
                <li>Cá hồi nướng mật ong - Béo ngậy, thơm lừng</li>
            </ul>
            
            <p>Hãy đến và thưởng thức ngay hôm nay!</p>
        '
    ],
    3 => [
        'title' => 'Ưu đãi đặc biệt mừng Giáng sinh',
        'category' => 'Khuyến mãi',
        'date' => '20/11/2024',
        'image' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=1200&q=80',
        'content' => '
            <p>Giảm 20% cho tất cả đơn hàng từ 500.000đ và tặng kèm món tráng miệng đặc biệt khi đặt bàn trước.</p>
            
            <h3>Chi tiết chương trình</h3>
            <p>Nhân dịp Giáng sinh và năm mới 2025, Ngon Gallery xin gửi tặng quý khách hàng chương trình ưu đãi đặc biệt.</p>
            
            <h3>Ưu đãi bao gồm</h3>
            <ul>
                <li>Giảm 20% tổng hóa đơn từ 500.000đ</li>
                <li>Tặng món tráng miệng đặc biệt khi đặt bàn trước 24h</li>
                <li>Tặng voucher 100.000đ cho lần ghé thăm tiếp theo</li>
                <li>Miễn phí trang trí bàn tiệc theo yêu cầu</li>
            </ul>
            
            <p><strong>Thời gian áp dụng:</strong> 20/12/2024 - 05/01/2025</p>
        '
    ],
    4 => [
        'title' => 'Đầu bếp trưởng nhận giải thưởng ẩm thực',
        'category' => 'Tin tức',
        'date' => '15/11/2024',
        'image' => 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=1200&q=80',
        'content' => '
            <p>Chef Minh Hoàng vinh dự nhận giải "Đầu bếp xuất sắc năm 2024" tại lễ trao giải Vietnam Culinary Awards.</p>
            
            <h3>Về Chef Minh Hoàng</h3>
            <p>Với hơn 15 năm kinh nghiệm trong ngành ẩm thực, Chef Minh Hoàng đã góp phần tạo nên thương hiệu Ngon Gallery với những món ăn độc đáo, kết hợp tinh hoa ẩm thực Việt Nam và quốc tế.</p>
            
            <h3>Giải thưởng</h3>
            <p>Giải "Đầu bếp xuất sắc năm 2024" là sự ghi nhận cho những đóng góp của Chef Minh Hoàng trong việc nâng tầm ẩm thực Việt Nam trên bản đồ ẩm thực thế giới.</p>
            
            <p>Ngon Gallery xin chúc mừng Chef Minh Hoàng và cam kết tiếp tục mang đến những trải nghiệm ẩm thực tuyệt vời nhất cho quý khách!</p>
        '
    ],
    5 => [
        'title' => 'Workshop nấu ăn cuối tuần',
        'category' => 'Sự kiện',
        'date' => '10/11/2024',
        'image' => 'https://images.unsplash.com/photo-1559339352-11d035aa65de?w=1200&q=80',
        'content' => '
            <p>Tham gia lớp học nấu ăn miễn phí mỗi Chủ nhật, học cách chế biến các món Việt truyền thống cùng đầu bếp.</p>
            
            <h3>Thông tin workshop</h3>
            <p>Mỗi Chủ nhật hàng tuần, Ngon Gallery tổ chức lớp học nấu ăn miễn phí dành cho tất cả mọi người yêu thích ẩm thực.</p>
            
            <h3>Nội dung học</h3>
            <ul>
                <li>Tuần 1: Phở Hà Nội truyền thống</li>
                <li>Tuần 2: Bún chả Hà Nội</li>
                <li>Tuần 3: Bánh cuốn nóng</li>
                <li>Tuần 4: Nem rán giòn rụm</li>
            </ul>
            
            <p><strong>Thời gian:</strong> 9:00 - 11:00 mỗi Chủ nhật</p>
            <p><strong>Địa điểm:</strong> 126 Nguyễn Thiện Thành, Phường 5, TP. Trà Vinh</p>
            <p><strong>Đăng ký:</strong> Liên hệ hotline 1900 1234 hoặc đăng ký trực tiếp tại nhà hàng</p>
        '
    ],
    6 => [
        'title' => 'Hợp tác với nông trại hữu cơ',
        'category' => 'Hợp tác',
        'date' => '05/11/2024',
        'image' => 'https://images.unsplash.com/photo-1466978913421-dad2ebd01d17?w=1200&q=80',
        'content' => '
            <p>Ngon Gallery ký kết hợp tác với các nông trại hữu cơ địa phương, đảm bảo nguồn nguyên liệu tươi sạch nhất.</p>
            
            <h3>Cam kết chất lượng</h3>
            <p>Chúng tôi luôn đặt chất lượng nguyên liệu lên hàng đầu. Việc hợp tác với các nông trại hữu cơ giúp đảm bảo mỗi món ăn đều được chế biến từ nguyên liệu tươi ngon, an toàn cho sức khỏe.</p>
            
            <h3>Đối tác nông trại</h3>
            <ul>
                <li>Nông trại Đà Lạt Organic - Rau củ hữu cơ</li>
                <li>Trang trại Bình Dương - Thịt heo sạch</li>
                <li>Vùng nuôi Cần Giờ - Hải sản tươi sống</li>
                <li>Nông trại Củ Chi - Gà ta thả vườn</li>
            </ul>
            
            <p>Ngon Gallery cam kết mang đến những món ăn ngon, sạch và an toàn cho sức khỏe của quý khách!</p>
        '
    ]
];

// Lấy thông tin tin tức
$news = isset($news_data[$news_id]) ? $news_data[$news_id] : $news_data[1];
?>

<!-- News Detail Section -->
<section class="news-detail-page">
    <div class="news-detail-container">
        <!-- Breadcrumb -->
        <div class="news-breadcrumb">
            <a href="index.php?page=home"><i class="fas fa-home"></i> Trang chủ</a>
            <span>/</span>
            <a href="index.php?page=news">Tin tức</a>
            <span>/</span>
            <span><?php echo htmlspecialchars($news['title']); ?></span>
        </div>

        <!-- News Header -->
        <div class="news-detail-header">
            <span class="news-detail-category"><?php echo htmlspecialchars($news['category']); ?></span>
            <h1 class="news-detail-title"><?php echo htmlspecialchars($news['title']); ?></h1>
            <div class="news-detail-meta">
                <span><i class="fas fa-calendar-alt"></i> <?php echo $news['date']; ?></span>
                <span><i class="fas fa-user"></i> Admin</span>
                <span><i class="fas fa-eye"></i> 1.2K lượt xem</span>
            </div>
        </div>

        <!-- Featured Image -->
        <div class="news-detail-image">
            <img src="<?php echo $news['image']; ?>" alt="<?php echo htmlspecialchars($news['title']); ?>">
        </div>

        <!-- News Content -->
        <div class="news-detail-content">
            <?php echo $news['content']; ?>
        </div>

        <!-- Share Buttons -->
        <div class="news-share">
            <span><i class="fas fa-share-alt"></i> Chia sẻ bài viết</span>
            <div class="share-buttons">
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                   target="_blank" rel="noopener noreferrer" class="share-btn facebook" title="Chia sẻ lên Facebook">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode($news['title']); ?>" 
                   target="_blank" rel="noopener noreferrer" class="share-btn twitter" title="Chia sẻ lên Twitter">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&title=<?php echo urlencode($news['title']); ?>" 
                   target="_blank" rel="noopener noreferrer" class="share-btn linkedin" title="Chia sẻ lên LinkedIn">
                    <i class="fab fa-linkedin-in"></i>
                </a>
                <a href="javascript:void(0);" onclick="copyToClipboard()" class="share-btn copy" title="Sao chép link">
                    <i class="fas fa-link"></i>
                </a>
            </div>
        </div>

        <!-- Toast notification for copy -->
        <div id="copy-toast" class="copy-toast">
            <i class="fas fa-check-circle"></i> Đã sao chép link!
        </div>

        <script>
        function copyToClipboard() {
            const url = window.location.href;
            navigator.clipboard.writeText(url).then(function() {
                const toast = document.getElementById('copy-toast');
                toast.classList.add('show');
                setTimeout(function() {
                    toast.classList.remove('show');
                }, 2000);
            }).catch(function(err) {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = url;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                
                const toast = document.getElementById('copy-toast');
                toast.classList.add('show');
                setTimeout(function() {
                    toast.classList.remove('show');
                }, 2000);
            });
        }
        </script>

        <style>
        .copy-toast {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 10px 40px rgba(34, 197, 94, 0.4);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 9999;
        }
        
        .copy-toast.show {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }
        
        .copy-toast i {
            font-size: 1.2rem;
        }
        </style>

        <!-- Back Button -->
        <div class="news-back">
            <a href="index.php?page=news" class="btn-back">
                <i class="fas fa-arrow-left"></i> Quay lại danh sách tin tức
            </a>
        </div>
    </div>
</section>
