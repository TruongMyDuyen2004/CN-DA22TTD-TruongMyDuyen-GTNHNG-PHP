<?php
$current_lang = getCurrentLanguage();
$items_per_page = 6;
$current_page = isset($_GET['news_page']) ? (int)$_GET['news_page'] : 1;
if ($current_page < 1) $current_page = 1;

// Danh sách tin tức
$all_news = [
    ['id' => 1, 'date' => '28/11/2024', 'category' => 'Sự kiện', 'title' => 'Khai trương Ngon Gallery tại Trà Vinh', 'desc' => 'Ngon Gallery chính thức khai trương tại TP. Trà Vinh, mang đến không gian ẩm thực sang trọng cho cư dân địa phương.', 'img' => 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=600&q=80'],
    ['id' => 2, 'date' => '25/11/2024', 'category' => 'Thực đơn', 'title' => 'Ra mắt thực đơn mùa đông 2024', 'desc' => 'Khám phá bộ sưu tập món ăn mới với hương vị ấm áp, hoàn hảo cho những ngày se lạnh cuối năm.', 'img' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=600&q=80'],
    ['id' => 3, 'date' => '20/11/2024', 'category' => 'Khuyến mãi', 'title' => 'Ưu đãi đặc biệt mừng Giáng sinh', 'desc' => 'Giảm 20% cho tất cả đơn hàng từ 500.000đ và tặng kèm món tráng miệng đặc biệt khi đặt bàn trước.', 'img' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=600&q=80'],
    ['id' => 4, 'date' => '15/11/2024', 'category' => 'Tin tức', 'title' => 'Đầu bếp trưởng nhận giải thưởng ẩm thực', 'desc' => 'Chef Minh Hoàng vinh dự nhận giải "Đầu bếp xuất sắc năm 2024" tại lễ trao giải Vietnam Culinary Awards.', 'img' => 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=600&q=80'],
    ['id' => 5, 'date' => '10/11/2024', 'category' => 'Sự kiện', 'title' => 'Workshop nấu ăn cuối tuần', 'desc' => 'Tham gia lớp học nấu ăn miễn phí mỗi Chủ nhật, học cách chế biến các món Việt truyền thống cùng đầu bếp.', 'img' => 'https://images.unsplash.com/photo-1559339352-11d035aa65de?w=600&q=80'],
    ['id' => 6, 'date' => '05/11/2024', 'category' => 'Hợp tác', 'title' => 'Hợp tác với nông trại hữu cơ', 'desc' => 'Ngon Gallery ký kết hợp tác với các nông trại hữu cơ địa phương, đảm bảo nguồn nguyên liệu tươi sạch nhất.', 'img' => 'https://images.unsplash.com/photo-1466978913421-dad2ebd01d17?w=600&q=80'],
    ['id' => 7, 'date' => '01/11/2024', 'category' => 'Tin tức', 'title' => 'Ngon Gallery đạt chứng nhận vệ sinh an toàn thực phẩm', 'desc' => 'Nhà hàng vinh dự nhận chứng nhận 5 sao về vệ sinh an toàn thực phẩm từ Sở Y tế tỉnh Trà Vinh.', 'img' => 'https://images.unsplash.com/photo-1600891964092-4316c288032e?w=600&q=80'],
    ['id' => 8, 'date' => '28/10/2024', 'category' => 'Sự kiện', 'title' => 'Đêm nhạc acoustic tại Ngon Gallery', 'desc' => 'Thưởng thức bữa tối lãng mạn với tiếng guitar acoustic mỗi tối thứ 7 hàng tuần tại nhà hàng.', 'img' => 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=600&q=80'],
    ['id' => 9, 'date' => '25/10/2024', 'category' => 'Thực đơn', 'title' => 'Món mới: Bò wagyu nướng than hoa', 'desc' => 'Trải nghiệm hương vị thượng hạng với thịt bò wagyu A5 nhập khẩu trực tiếp từ Nhật Bản.', 'img' => 'https://images.unsplash.com/photo-1546833999-b9f581a1996d?w=600&q=80'],
    ['id' => 10, 'date' => '20/10/2024', 'category' => 'Khuyến mãi', 'title' => 'Ưu đãi sinh nhật tháng 10', 'desc' => 'Tặng bánh sinh nhật miễn phí cho khách hàng có sinh nhật trong tháng 10 khi đặt bàn từ 4 người.', 'img' => 'https://images.unsplash.com/photo-1558301211-0d8c8ddee6ec?w=600&q=80'],
    ['id' => 11, 'date' => '15/10/2024', 'category' => 'Tin tức', 'title' => 'Ngon Gallery trên tạp chí ẩm thực', 'desc' => 'Nhà hàng được giới thiệu trong chuyên mục "Top 10 nhà hàng đáng thử" của tạp chí Ẩm Thực Việt Nam.', 'img' => 'https://images.unsplash.com/photo-1551218808-94e220e084d2?w=600&q=80'],
    ['id' => 12, 'date' => '10/10/2024', 'category' => 'Sự kiện', 'title' => 'Lễ hội ẩm thực mùa thu', 'desc' => 'Tham gia lễ hội ẩm thực với hơn 50 món ăn đặc sắc từ các vùng miền Việt Nam.', 'img' => 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=600&q=80'],
    ['id' => 13, 'date' => '05/10/2024', 'category' => 'Hợp tác', 'title' => 'Hợp tác với đầu bếp Michelin', 'desc' => 'Ngon Gallery hân hạnh chào đón Chef Pierre Martin từ Pháp trong chương trình giao lưu ẩm thực quốc tế.', 'img' => 'https://images.unsplash.com/photo-1577219491135-ce391730fb2c?w=600&q=80'],
    ['id' => 14, 'date' => '01/10/2024', 'category' => 'Thực đơn', 'title' => 'Set menu dành cho gia đình', 'desc' => 'Ra mắt set menu gia đình với giá ưu đãi, phù hợp cho bữa ăn sum họp cuối tuần.', 'img' => 'https://images.unsplash.com/photo-1547573854-74d2a71d0826?w=600&q=80'],
    ['id' => 15, 'date' => '28/09/2024', 'category' => 'Khuyến mãi', 'title' => 'Flash sale cuối tháng 9', 'desc' => 'Giảm 30% tất cả món ăn từ 14h-17h trong 3 ngày cuối tháng 9.', 'img' => 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=600&q=80'],
    ['id' => 16, 'date' => '25/09/2024', 'category' => 'Tin tức', 'title' => 'Mở rộng không gian phục vụ', 'desc' => 'Ngon Gallery mở rộng thêm tầng 2 với sức chứa 100 khách, phục vụ tiệc cưới và sự kiện.', 'img' => 'https://images.unsplash.com/photo-1519671482749-fd09be7ccebf?w=600&q=80'],
    ['id' => 17, 'date' => '20/09/2024', 'category' => 'Sự kiện', 'title' => 'Đêm Trung thu tại Ngon Gallery', 'desc' => 'Chương trình đặc biệt đêm Trung thu với múa lân, phá cỗ và quà tặng cho các bé.', 'img' => 'https://images.unsplash.com/photo-1601004890684-d8cbf643f5f2?w=600&q=80'],
    ['id' => 18, 'date' => '15/09/2024', 'category' => 'Thực đơn', 'title' => 'Bánh Trung thu handmade', 'desc' => 'Đặt bánh Trung thu handmade với nhiều hương vị độc đáo, quà tặng ý nghĩa cho người thân.', 'img' => 'https://images.unsplash.com/photo-1609501676725-7186f017a4b7?w=600&q=80'],
];

$total_news = count($all_news);
$total_pages = ceil($total_news / $items_per_page);
if ($current_page > $total_pages) $current_page = $total_pages;

$start = ($current_page - 1) * $items_per_page;
$news_items = array_slice($all_news, $start, $items_per_page);
?>

<!-- News Hero Section -->
<section class="about-hero">
    <div class="about-hero-content">
        <span class="section-badge"><?php echo __('news'); ?></span>
        <h1 class="about-hero-title"><?php echo $current_lang === 'en' ? 'News Ngon Gallery' : 'Tin Tức Ngon Gallery'; ?></h1>
        <p class="about-hero-subtitle"><?php echo $current_lang === 'en' ? 'Latest updates from Ngon Gallery' : 'Cập nhật tin tức mới nhất từ Ngon Gallery'; ?></p>
    </div>
</section>

<!-- News Section -->
<section class="news-page">
    <div class="news-container">
        <!-- News Grid -->
        <div class="news-grid">
            <?php foreach ($news_items as $news): ?>
            <article class="news-card">
                <div class="news-card-image">
                    <img src="<?php echo $news['img']; ?>" alt="<?php echo htmlspecialchars($news['title']); ?>">
                    <span class="news-date"><?php echo $news['date']; ?></span>
                </div>
                <div class="news-card-content">
                    <span class="news-category"><?php echo $news['category']; ?></span>
                    <h3><?php echo htmlspecialchars($news['title']); ?></h3>
                    <p><?php echo htmlspecialchars($news['desc']); ?></p>
                    <a href="index.php?page=news-detail&id=<?php echo $news['id']; ?>" class="news-read-more">
                        <?php echo $current_lang === 'en' ? 'Read more' : 'Đọc thêm'; ?> <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="news-pagination">
            <?php if ($current_page > 1): ?>
            <a href="index.php?page=news&news_page=<?php echo $current_page - 1; ?>" class="pagination-btn pagination-prev">
                <i class="fas fa-chevron-left"></i>
            </a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="index.php?page=news&news_page=<?php echo $i; ?>" 
               class="pagination-btn <?php echo $i === $current_page ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
            <?php endfor; ?>

            <?php if ($current_page < $total_pages): ?>
            <a href="index.php?page=news&news_page=<?php echo $current_page + 1; ?>" class="pagination-btn pagination-next">
                <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<style>
/* News Pagination */
.news-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
    margin-top: 3rem;
    padding-top: 2rem;
}

.news-pagination .pagination-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 44px;
    height: 44px;
    padding: 0 1rem;
    background: #ffffff;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    color: #4b5563;
    font-weight: 600;
    font-size: 0.95rem;
    text-decoration: none;
    transition: all 0.3s ease;
}

.news-pagination .pagination-btn:hover {
    border-color: #22c55e;
    color: #22c55e;
    background: rgba(34, 197, 94, 0.05);
}

.news-pagination .pagination-btn.active {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    border-color: #22c55e;
    color: #ffffff;
    box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
}

.news-pagination .pagination-prev,
.news-pagination .pagination-next {
    min-width: 44px;
    padding: 0;
}

@media (max-width: 576px) {
    .news-pagination {
        gap: 0.35rem;
    }
    
    .news-pagination .pagination-btn {
        min-width: 38px;
        height: 38px;
        font-size: 0.85rem;
    }
}
</style>
