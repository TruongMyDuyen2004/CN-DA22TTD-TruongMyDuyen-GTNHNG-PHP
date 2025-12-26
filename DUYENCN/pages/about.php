<?php
$current_lang = getCurrentLanguage();
?>

<!-- About Hero Section -->
<section class="about-hero">
    <div class="about-hero-content">
        <span class="section-badge"><?php echo __('about'); ?></span>
        <h1 class="about-hero-title"><?php echo $current_lang === 'en' ? 'About Ngon Gallery' : 'Về Ngon Gallery'; ?></h1>
        <p class="about-hero-subtitle"><?php echo __('our_story_short'); ?></p>
    </div>
</section>

<!-- About Main Content -->
<section class="about-section">
    <div class="container">
        <!-- Restaurant Image Section -->
        <div class="about-image-section">
            <div class="restaurant-image-wrapper">
                <img src="assets/images/restaurant.jpg" alt="Ngon Gallery Restaurant" class="restaurant-image">
            </div>
            <div class="about-image-text">
                <h3>Không Gian Nhà Hàng</h3>
                <p><span class="highlight-name">Ngon Gallery</span> sở hữu không gian ấm cúng, hiện đại nhưng vẫn giữ được nét truyền thống Việt Nam. Thiết kế nội thất tinh tế, ánh sáng dịu nhẹ tạo nên bầu không khí thư giãn, lý tưởng cho các bữa ăn gia đình, gặp gỡ bạn bè hay tiệc công ty.</p>
                <p>Với sức chứa lên đến 100 khách, chúng tôi có thể phục vụ từ những bữa ăn nhỏ đến các sự kiện lớn. Đội ngũ nhân viên chuyên nghiệp luôn sẵn sàng mang đến trải nghiệm tuyệt vời nhất cho quý khách.</p>
            </div>
        </div>

        <!-- Story Section -->
        <div class="about-image-section story-section">
            <div class="about-image-text">
                <h3><?php echo __('our_story'); ?></h3>
                <p><?php echo __('our_story_text'); ?></p>
                <p>Từ một quán ăn nhỏ với niềm đam mê ẩm thực Việt Nam, <span class="highlight-name">Ngon Gallery</span> đã không ngừng phát triển và trở thành điểm đến tin cậy của hàng ngàn thực khách. Chúng tôi tự hào mang đến những món ăn truyền thống được chế biến từ công thức gia truyền, kết hợp với nguyên liệu tươi ngon nhất.</p>
                <p>Mỗi món ăn tại <span class="highlight-name">Ngon Gallery</span> đều được chăm chút tỉ mỉ, từ khâu lựa chọn nguyên liệu, sơ chế đến chế biến và trình bày. Chúng tôi tin rằng ẩm thực không chỉ là món ăn, mà còn là câu chuyện văn hóa, là tình yêu và sự tận tâm của người làm ra nó.</p>
            </div>
            <div class="restaurant-image-wrapper">
                <img src="https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=800&q=80" alt="Restaurant Story" class="restaurant-image">
            </div>
        </div>

        <!-- Mission & Vision Grid -->
        <div class="about-grid-2">
            <div class="about-card">
                <div class="card-image-wrapper">
                    <img src="https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=600&q=80" alt="Our Mission" class="card-image">
                </div>
                <h3><?php echo __('our_mission'); ?></h3>
                <p><?php echo __('our_mission_text'); ?></p>
                <p>Chúng tôi cam kết mang đến trải nghiệm ẩm thực đích thực, nơi mỗi món ăn đều kể một câu chuyện về văn hóa Việt Nam. Sứ mệnh của chúng tôi là bảo tồn và phát huy những giá trị ẩm thực truyền thống, đồng thời không ngừng đổi mới để phục vụ khách hàng tốt hơn mỗi ngày.</p>
            </div>
            
            <div class="about-card">
                <div class="card-image-wrapper">
                    <img src="https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=600&q=80" alt="Our Vision" class="card-image">
                </div>
                <h3>Tầm Nhìn</h3>
                <p>Trở thành chuỗi nhà hàng ẩm thực Việt Nam hàng đầu, được yêu thích bởi chất lượng món ăn, dịch vụ chuyên nghiệp và không gian ấm cúng. Chúng tôi hướng đến việc mở rộng quy mô, mang hương vị Việt Nam đến với nhiều thực khách hơn nữa.</p>
                <p><span class="highlight-name">Ngon Gallery</span> không chỉ là nơi thưởng thức món ăn, mà còn là không gian văn hóa, nơi mọi người có thể gặp gỡ, chia sẻ và tạo nên những kỷ niệm đẹp bên bữa ăn ngon.</p>
            </div>
        </div>

        <!-- Values & Features Grid -->
        <div class="about-grid-3">
            <div class="about-card">
                <div class="card-image-wrapper">
                    <img src="https://images.unsplash.com/photo-1556910103-1c02745aae4d?w=500&q=80" alt="Core Values" class="card-image">
                </div>
                <h3><?php echo __('core_values'); ?></h3>
                <ul>
                    <li><?php echo __('quality_first'); ?></li>
                    <li><?php echo __('dedicated_service'); ?></li>
                    <li><?php echo __('clean_safe'); ?></li>
                    <li><?php echo __('fair_pricing'); ?></li>
                </ul>
            </div>
            
            <div class="about-card">
                <div class="card-image-wrapper">
                    <img src="https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=500&q=80" alt="Culinary Excellence" class="card-image">
                </div>
                <h3>Đặc Sắc Ẩm Thực</h3>
                <ul>
                    <li>Công thức gia truyền độc đáo</li>
                    <li>Nguyên liệu tươi mỗi ngày</li>
                    <li>Đầu bếp giàu kinh nghiệm</li>
                    <li>Thực đơn đa dạng phong phú</li>
                </ul>
            </div>
            
            <div class="about-card">
                <div class="card-image-wrapper">
                    <img src="https://images.unsplash.com/photo-1559339352-11d035aa65de?w=500&q=80" alt="Quality Commitment" class="card-image">
                </div>
                <h3>Cam Kết Chất Lượng</h3>
                <ul>
                    <li>An toàn vệ sinh thực phẩm</li>
                    <li>Phục vụ nhanh chóng, chu đáo</li>
                    <li>Không gian sạch sẽ, thoáng mát</li>
                    <li>Giá cả hợp lý, minh bạch</li>
                </ul>
            </div>
        </div>

    </div>
</section>
