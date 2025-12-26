<?php
$db = new Database();
$conn = $db->connect();
$current_lang = getCurrentLanguage();

// Kiểm tra và tạo bảng restaurant_promotions nếu chưa có
try {
    $conn->query("SELECT 1 FROM restaurant_promotions LIMIT 1");
} catch (PDOException $e) {
    $conn->exec("CREATE TABLE IF NOT EXISTS restaurant_promotions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        title_en VARCHAR(255),
        description TEXT,
        description_en TEXT,
        image VARCHAR(255),
        promo_type ENUM('combo', 'discount', 'event', 'seasonal', 'member', 'coupon', 'flash_sale') DEFAULT 'discount',
        discount_text VARCHAR(100),
        discount_percent INT DEFAULT 0,
        link_page VARCHAR(50) DEFAULT 'reservation',
        start_date DATE,
        end_date DATE,
        terms TEXT,
        terms_en TEXT,
        is_featured TINYINT(1) DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        display_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
}

// Thêm cột link_page nếu chưa có
try {
    $conn->query("SELECT link_page FROM restaurant_promotions LIMIT 1");
} catch (PDOException $e) {
    $conn->exec("ALTER TABLE restaurant_promotions ADD COLUMN link_page VARCHAR(50) DEFAULT 'reservation'");
}

// Thêm cột combo_price nếu chưa có
try {
    $conn->query("SELECT combo_price FROM restaurant_promotions LIMIT 1");
} catch (PDOException $e) {
    $conn->exec("ALTER TABLE restaurant_promotions ADD COLUMN combo_price DECIMAL(10,0) DEFAULT NULL");
}

// Lấy danh sách khuyến mãi - Chỉ lấy combo
$now = date('Y-m-d');
$stmt = $conn->prepare("
    SELECT * FROM restaurant_promotions 
    WHERE is_active = 1 
    AND promo_type = 'combo'
    AND (start_date IS NULL OR start_date <= ?)
    AND (end_date IS NULL OR end_date >= ?)
    ORDER BY is_featured DESC, display_order ASC
");
$stmt->execute([$now, $now]);
$promotions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Icon theo loại
$type_icons = [
    'combo' => 'fa-utensils',
    'discount' => 'fa-percent',
    'event' => 'fa-calendar-star',
    'seasonal' => 'fa-snowflake',
    'member' => 'fa-user-tag',
    'coupon' => 'fa-ticket',
    'flash_sale' => 'fa-bolt'
];

// Link mặc định theo loại khuyến mãi
$default_links = [
    'combo' => 'menu',
    'discount' => 'menu',
    'event' => 'reservation',
    'seasonal' => 'menu',
    'member' => 'reservation',
    'coupon' => 'menu',
    'flash_sale' => 'menu'
];

// Text nút theo loại
$button_texts = [
    'combo' => $current_lang === 'en' ? 'View Combo' : 'Xem Combo',
    'discount' => $current_lang === 'en' ? 'View Menu' : 'Xem Thực Đơn',
    'event' => $current_lang === 'en' ? 'Book Now' : 'Đặt Bàn Ngay',
    'seasonal' => $current_lang === 'en' ? 'View Menu' : 'Xem Thực Đơn',
    'member' => $current_lang === 'en' ? 'Book Online' : 'Đặt Bàn Online',
    'coupon' => $current_lang === 'en' ? 'Order Now' : 'Đặt Món Ngay',
    'flash_sale' => $current_lang === 'en' ? 'Order Now' : 'Đặt Món Ngay'
];

// Tạo bảng promotion_items nếu chưa có
try {
    $conn->query("SELECT 1 FROM promotion_items LIMIT 1");
} catch (PDOException $e) {
    $conn->exec("CREATE TABLE IF NOT EXISTS promotion_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        promotion_id INT NOT NULL,
        menu_item_id INT NOT NULL,
        quantity INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (promotion_id) REFERENCES restaurant_promotions(id) ON DELETE CASCADE,
        FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE
    )");
}

// Lấy danh sách món trong combo cho mỗi promotion
$combo_items = [];
foreach ($promotions as $promo) {
    if ($promo['promo_type'] === 'combo') {
        $stmt = $conn->prepare("
            SELECT pi.*, m.name, m.name_en, m.price, m.image, m.description
            FROM promotion_items pi
            JOIN menu_items m ON pi.menu_item_id = m.id
            WHERE pi.promotion_id = ?
            ORDER BY pi.id ASC
        ");
        $stmt->execute([$promo['id']]);
        $combo_items[$promo['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!-- Hero Section -->
<section class="about-hero">
    <div class="about-hero-content">
        <span class="section-badge"><?php echo $current_lang === 'en' ? 'Promotions' : 'Khuyến mãi'; ?></span>
        <h1 class="about-hero-title"><?php echo $current_lang === 'en' ? 'Special Offers' : 'Ưu Đãi Đặc Biệt'; ?></h1>
        <p class="about-hero-subtitle"><?php echo $current_lang === 'en' ? 'Click on any offer to learn more and enjoy' : 'Nhấn vào ưu đãi bạn muốn để xem chi tiết và sử dụng'; ?></p>
    </div>
</section>

<!-- Promotions Content -->
<section class="promo-section">
    <div class="container">
        
        <?php if (empty($promotions)): ?>
        <!-- Empty State -->
        <div class="promo-empty">
            <i class="fas fa-gift"></i>
            <h3><?php echo $current_lang === 'en' ? 'No Promotions Available' : 'Chưa có khuyến mãi'; ?></h3>
            <p><?php echo $current_lang === 'en' ? 'Check back soon for exciting offers!' : 'Hãy quay lại sau để xem các ưu đãi hấp dẫn!'; ?></p>
        </div>
        <?php else: ?>
        
        <!-- Promotions Grid - Giống style trang Giới thiệu -->
        <div class="promo-grid">
            <?php foreach ($promotions as $promo): 
                $title = $current_lang === 'en' && $promo['title_en'] ? $promo['title_en'] : $promo['title'];
                $desc = $current_lang === 'en' && $promo['description_en'] ? $promo['description_en'] : $promo['description'];
                $icon = $type_icons[$promo['promo_type']] ?? 'fa-tag';
                $link = $promo['link_page'] ?? $default_links[$promo['promo_type']] ?? 'reservation';
                $btn_text = $button_texts[$promo['promo_type']] ?? ($current_lang === 'en' ? 'Learn More' : 'Xem thêm');
                $discount = $promo['discount_percent'] ?? 0;
                $is_combo = $promo['promo_type'] === 'combo';
                $has_combo_items = $is_combo && !empty($combo_items[$promo['id']]);
                
                // Tính giá combo
                $combo_total = 0;
                $combo_price = $promo['combo_price'] ?? 0;
                if ($has_combo_items) {
                    foreach ($combo_items[$promo['id']] as $ci) {
                        $combo_total += $ci['price'] * $ci['quantity'];
                    }
                    if (!$combo_price) {
                        $combo_price = $discount > 0 ? round($combo_total * (100 - $discount) / 100) : $combo_total;
                    }
                }
            ?>
            <?php if ($is_combo): ?>
            <!-- Combo Card - Click để mở modal -->
            <div class="promo-card-link" onclick="openComboModal(<?php echo $promo['id']; ?>, '<?php echo addslashes($title); ?>', <?php echo $discount; ?>, '<?php echo addslashes($promo['discount_text'] ?? ''); ?>')">
            <?php else: ?>
            <a href="?page=<?php echo $link; ?>&promo_id=<?php echo $promo['id']; ?>" class="promo-card-link" onclick="savePromotion(<?php echo $promo['id']; ?>, '<?php echo addslashes($title); ?>', <?php echo $discount; ?>, '<?php echo addslashes($promo['discount_text'] ?? ''); ?>')">
            <?php endif; ?>
                <div class="promo-card <?php echo $promo['is_featured'] ? 'featured' : ''; ?>">
                    <?php if ($promo['is_featured']): ?>
                    <div class="promo-featured-badge">
                        <i class="fas fa-star"></i> <?php echo $current_lang === 'en' ? 'Hot' : 'Hot'; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($discount > 0): ?>
                    <div class="promo-discount-badge">-<?php echo $discount; ?>%</div>
                    <?php endif; ?>
                    
                    <div class="promo-card-image">
                        <?php if ($promo['image']): 
                            // Kiểm tra nếu là URL hay file local
                            $img_src = $promo['image'];
                            if (!preg_match('/^https?:\/\//', $img_src)) {
                                $img_src = 'uploads/promotions/' . $img_src;
                            }
                        ?>
                        <img src="<?php echo htmlspecialchars($img_src); ?>" alt="<?php echo htmlspecialchars($title); ?>">
                        <?php else: ?>
                        <div class="promo-card-icon">
                            <i class="fas <?php echo $icon; ?>"></i>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="promo-card-content">
                        <h3><?php echo htmlspecialchars($title); ?></h3>
                        
                        <?php if ($promo['discount_text']): ?>
                        <div class="promo-highlight"><?php echo htmlspecialchars($promo['discount_text']); ?></div>
                        <?php endif; ?>
                        
                        <?php if ($is_combo && $has_combo_items): ?>
                        <!-- Hiển thị giá combo -->
                        <div class="combo-price-display">
                            <?php if ($combo_total > $combo_price): ?>
                            <span class="combo-original"><?php echo number_format($combo_total, 0, ',', '.'); ?>đ</span>
                            <?php endif; ?>
                            <span class="combo-final"><?php echo number_format($combo_price, 0, ',', '.'); ?>đ</span>
                        </div>
                        <!-- Hiển thị số món trong combo -->
                        <div class="combo-items-preview">
                            <i class="fas fa-utensils"></i>
                            <?php echo count($combo_items[$promo['id']]); ?> <?php echo $current_lang === 'en' ? 'items' : 'món'; ?>
                        </div>
                        <?php else: ?>
                        <p><?php echo htmlspecialchars($desc); ?></p>
                        <?php endif; ?>
                        
                        <?php if ($promo['end_date']): 
                            $end = new DateTime($promo['end_date']);
                            $now_dt = new DateTime();
                            $diff = $now_dt->diff($end)->days;
                        ?>
                        <div class="promo-time <?php echo $diff <= 7 ? 'urgent' : ''; ?>">
                            <i class="fas fa-clock"></i>
                            <?php if ($diff <= 7): ?>
                                <?php echo $current_lang === 'en' ? "Only $diff days left!" : "Còn $diff ngày!"; ?>
                            <?php else: ?>
                                <?php echo ($current_lang === 'en' ? 'Until ' : 'Đến ') . date('d/m/Y', strtotime($promo['end_date'])); ?>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="promo-card-btn">
                            <?php echo $btn_text; ?> <i class="fas fa-arrow-right"></i>
                        </div>
                    </div>
                </div>
            <?php if ($is_combo): ?>
            </div>
            <?php else: ?>
            </a>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
        
        <?php endif; ?>
        
        <!-- Quick Links -->
        <div class="promo-quick-links">
            <h3><?php echo $current_lang === 'en' ? 'Quick Access' : 'Truy Cập Nhanh'; ?></h3>
            <div class="quick-links-grid">
                <a href="?page=menu" class="quick-link">
                    <i class="fas fa-utensils"></i>
                    <span><?php echo $current_lang === 'en' ? 'View Menu' : 'Xem Thực Đơn'; ?></span>
                </a>
                <a href="?page=reservation" class="quick-link">
                    <i class="fas fa-calendar-check"></i>
                    <span><?php echo $current_lang === 'en' ? 'Book Table' : 'Đặt Bàn'; ?></span>
                </a>
                <a href="?page=contact" class="quick-link">
                    <i class="fas fa-phone-alt"></i>
                    <span><?php echo $current_lang === 'en' ? 'Contact Us' : 'Liên Hệ'; ?></span>
                </a>
            </div>
        </div>
        
    </div>
</section>

<style>
/* Promotions Section */
.promo-section {
    background: linear-gradient(180deg, #f0fdf4 0%, #ffffff 100%);
    padding: 3rem 0 4rem;
    min-height: 50vh;
}

/* Empty State */
.promo-empty {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 20px;
    box-shadow: 0 2px 20px rgba(0,0,0,0.06);
}

.promo-empty i {
    font-size: 3rem;
    color: #22c55e;
    margin-bottom: 1rem;
}

.promo-empty h3 {
    color: #1f2937;
    margin-bottom: 0.5rem;
}

.promo-empty p {
    color: #6b7280;
}

/* Combo Price Display */
.combo-price-display {
    display: flex;
    align-items: baseline;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.combo-price-display .combo-original {
    color: #9ca3af;
    text-decoration: line-through;
    font-size: 0.85rem;
}

.combo-price-display .combo-final {
    color: #ef4444;
    font-size: 1.35rem;
    font-weight: 700;
}

.combo-items-preview {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    color: #6b7280;
    font-size: 0.85rem;
    margin-bottom: 0.75rem;
}

.combo-items-preview i {
    color: #22c55e;
}

/* ========== MODERN CARD STYLE ========== */

/* Grid */
.promo-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}

/* Card Link */
.promo-card-link {
    text-decoration: none;
    display: block;
    cursor: pointer;
}

/* Card */
.promo-card {
    background: #fff;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 2px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    position: relative;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.promo-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.12);
}

/* Badges */
.promo-featured-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    background: #ff6b35;
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    z-index: 5;
    display: flex;
    align-items: center;
    gap: 4px;
}

.promo-discount-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background: #ef4444;
    color: white;
    padding: 6px 10px;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 700;
    z-index: 5;
}

/* Card Image */
.promo-card-image {
    height: 140px;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.promo-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.promo-card-icon {
    width: 64px;
    height: 64px;
    background: rgba(255,255,255,0.25);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.promo-card-icon i {
    font-size: 1.5rem;
    color: white;
}

/* Card Content */
.promo-card-content {
    padding: 1.25rem;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.promo-card-content h3 {
    color: #1a1a1a;
    font-size: 1.1rem;
    font-weight: 700;
    margin: 0 0 0.5rem;
    line-height: 1.4;
}

.promo-highlight {
    background: #f0fdf4;
    color: #16a34a;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    display: inline-block;
    margin-bottom: 0.75rem;
    border: 1px solid #bbf7d0;
}

.promo-card-content p {
    color: #666;
    font-size: 0.85rem;
    line-height: 1.5;
    margin: 0 0 0.75rem;
    flex: 1;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.promo-time {
    color: #999;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 1rem;
}

.promo-time i {
    font-size: 0.75rem;
}

.promo-time.urgent {
    color: #ef4444;
}

/* Card Button */
.promo-card-btn {
    background: #22c55e;
    color: white;
    padding: 12px 16px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.9rem;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: background 0.2s;
    margin-top: auto;
}

.promo-card:hover .promo-card-btn {
    background: #16a34a;
}

/* Quick Links */
.promo-quick-links {
    background: #fff;
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 2px 20px rgba(0,0,0,0.06);
    text-align: center;
}

.promo-quick-links h3 {
    color: #1a1a1a;
    font-size: 1.2rem;
    margin: 0 0 1.5rem;
    font-weight: 700;
}

.quick-links-grid {
    display: flex;
    justify-content: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.quick-link {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f0fdf4;
    color: #16a34a;
    padding: 12px 20px;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s;
    border: 1px solid #bbf7d0;
}

.quick-link:hover {
    background: #22c55e;
    color: white;
    border-color: #22c55e;
}

.quick-link i {
    font-size: 1rem;
}

/* Responsive */
@media (max-width: 1024px) {
    .promo-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 640px) {
    .promo-grid {
        grid-template-columns: 1fr;
    }
    
    .promo-section {
        padding: 2rem 0 3rem;
    }
    
    .quick-links-grid {
        flex-direction: column;
    }
    
    .quick-link {
        justify-content: center;
    }
}

/* Dark theme support - White background */
body.dark-theme .promo-section {
    background: linear-gradient(180deg, #f0fdf4 0%, #ffffff 100%);
}

body.dark-theme .promo-card,
body.dark-theme .promo-empty,
body.dark-theme .promo-quick-links {
    background: #ffffff;
    border-color: #e5e7eb;
}

body.dark-theme .promo-card:hover {
    border-color: #22c55e;
    box-shadow: 0 15px 35px rgba(34, 197, 94, 0.2);
}

body.dark-theme .promo-card-content h3,
body.dark-theme .promo-empty h3,
body.dark-theme .promo-quick-links h3 {
    color: #1f2937;
}

body.dark-theme .promo-card-content p,
body.dark-theme .promo-empty p {
    color: #6b7280;
}

body.dark-theme .promo-time {
    color: #9ca3af;
}
</style>

<script>
// Lưu khuyến mãi vào session khi click
function savePromotion(promoId, title, discountPercent, discountText) {
    // Gửi request để lưu khuyến mãi vào session
    fetch('api/apply-promotion.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'apply',
            promo_id: promoId,
            order_total: 0,
            type: 'selected'
        })
    });
    
    // Lưu vào localStorage để hiển thị thông báo ở trang đích
    localStorage.setItem('selected_promo', JSON.stringify({
        id: promoId,
        title: title,
        discount_percent: discountPercent,
        discount_text: discountText
    }));
}

// Dữ liệu combo items từ PHP
const comboItemsData = <?php echo json_encode($combo_items); ?>;

// Mở modal combo
function openComboModal(promoId, title, discountPercent, discountText) {
    const modal = document.getElementById('comboModal');
    const modalTitle = document.getElementById('comboModalTitle');
    const modalDiscount = document.getElementById('comboModalDiscount');
    const itemsContainer = document.getElementById('comboItemsContainer');
    const totalPrice = document.getElementById('comboTotalPrice');
    const discountedPrice = document.getElementById('comboDiscountedPrice');
    
    modalTitle.textContent = title;
    modalDiscount.textContent = discountText || (discountPercent > 0 ? `Giảm ${discountPercent}%` : '');
    
    // Lấy danh sách món trong combo
    const items = comboItemsData[promoId] || [];
    
    if (items.length === 0) {
        itemsContainer.innerHTML = `
            <div class="combo-empty">
                <i class="fas fa-utensils"></i>
                <p><?php echo $current_lang === 'en' ? 'No items in this combo yet' : 'Combo này chưa có món nào'; ?></p>
            </div>
        `;
        totalPrice.textContent = '0đ';
        discountedPrice.textContent = '0đ';
    } else {
        let total = 0;
        let html = '';
        
        items.forEach(item => {
            const itemName = '<?php echo $current_lang; ?>' === 'en' && item.name_en ? item.name_en : item.name;
            const itemPrice = parseInt(item.price) * parseInt(item.quantity);
            total += itemPrice;
            
            html += `
                <div class="combo-item">
                    <div class="combo-item-image">
                        ${item.image ? 
                            `<img src="${item.image}" alt="${itemName}">` : 
                            `<div class="combo-item-placeholder"><i class="fas fa-utensils"></i></div>`
                        }
                    </div>
                    <div class="combo-item-info">
                        <h4>${itemName}</h4>
                        <div class="combo-item-qty">x${item.quantity}</div>
                    </div>
                    <div class="combo-item-price">${parseInt(item.price).toLocaleString('vi-VN')}đ</div>
                </div>
            `;
        });
        
        itemsContainer.innerHTML = html;
        totalPrice.textContent = total.toLocaleString('vi-VN') + 'đ';
        
        // Tính giá sau giảm
        const discounted = discountPercent > 0 ? Math.round(total * (100 - discountPercent) / 100) : total;
        discountedPrice.textContent = discounted.toLocaleString('vi-VN') + 'đ';
    }
    
    // Lưu promo info để dùng khi đặt
    modal.dataset.promoId = promoId;
    modal.dataset.promoTitle = title;
    modal.dataset.discountPercent = discountPercent;
    modal.dataset.discountText = discountText;
    
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

// Đóng modal
function closeComboModal() {
    const modal = document.getElementById('comboModal');
    modal.style.display = 'none';
    document.body.style.overflow = '';
}

// Đặt combo - thêm tất cả món vào giỏ hàng
async function orderCombo() {
    const modal = document.getElementById('comboModal');
    const promoId = modal.dataset.promoId;
    const title = modal.dataset.promoTitle;
    const discountPercent = modal.dataset.discountPercent;
    const discountText = modal.dataset.discountText;
    
    // Lấy danh sách món trong combo
    const items = comboItemsData[promoId] || [];
    
    if (items.length === 0) {
        alert('<?php echo $current_lang === 'en' ? 'This combo has no items' : 'Combo này chưa có món nào'; ?>');
        return;
    }
    
    // Hiển thị loading
    const orderBtn = document.querySelector('.combo-order-btn');
    const originalText = orderBtn.innerHTML;
    orderBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php echo $current_lang === 'en' ? 'Adding...' : 'Đang thêm...'; ?>';
    orderBtn.disabled = true;
    
    try {
        // Gọi API add_combo để thêm tất cả món trong combo
        const formData = new FormData();
        formData.append('action', 'add_combo');
        formData.append('promo_id', promoId);
        
        const response = await fetch('api/cart.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Lưu khuyến mãi
            savePromotion(promoId, title, discountPercent, discountText);
            
            // Đóng modal
            closeComboModal();
            
            // Hiển thị thông báo thành công
            showComboNotification(title, items.length);
            
            // Cập nhật số lượng giỏ hàng
            if (result.cart_count !== undefined) {
                updateCartBadge(result.cart_count);
            }
        } else {
            alert(result.message || '<?php echo $current_lang === 'en' ? 'Error adding combo' : 'Lỗi khi thêm combo'; ?>');
        }
        
    } catch (error) {
        console.error('Error adding combo to cart:', error);
        alert('<?php echo $current_lang === 'en' ? 'Error adding combo to cart' : 'Lỗi khi thêm combo vào giỏ hàng'; ?>');
    } finally {
        orderBtn.innerHTML = originalText;
        orderBtn.disabled = false;
    }
}

// Cập nhật badge giỏ hàng
function updateCartBadge(count) {
    const badges = document.querySelectorAll('.cart-count, .cart-badge, [data-cart-count]');
    badges.forEach(badge => {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'flex' : 'none';
    });
}
// Hiển thị thông báo thêm combo thành công
function showComboNotification(comboTitle, itemCount) {
    // Tạo notification element
    const notification = document.createElement('div');
    notification.className = 'combo-notification';
    notification.innerHTML = `
        <div class="combo-notification-content">
            <i class="fas fa-check-circle"></i>
            <div>
                <strong><?php echo $current_lang === 'en' ? 'Added to cart!' : 'Đã thêm vào giỏ!'; ?></strong>
                <p>${comboTitle} (${itemCount} <?php echo $current_lang === 'en' ? 'items' : 'món'; ?>)</p>
            </div>
            <a href="?page=cart" class="combo-notification-btn">
                <?php echo $current_lang === 'en' ? 'View Cart' : 'Xem giỏ hàng'; ?>
            </a>
        </div>
    `;
    document.body.appendChild(notification);
    
    // Animation
    setTimeout(() => notification.classList.add('show'), 100);
    
    // Tự động ẩn sau 5 giây
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

// Cập nhật số lượng giỏ hàng
function updateCartCount() {
    fetch('api/cart.php?action=count')
        .then(res => res.json())
        .then(data => {
            const cartBadge = document.querySelector('.cart-count, .cart-badge');
            if (cartBadge && data.count !== undefined) {
                cartBadge.textContent = data.count;
                cartBadge.style.display = data.count > 0 ? 'flex' : 'none';
            }
        })
        .catch(err => console.log('Cart count update failed'));
}

// Đóng modal khi click bên ngoài
document.addEventListener('click', function(e) {
    const modal = document.getElementById('comboModal');
    if (e.target === modal) {
        closeComboModal();
    }
});

// Đóng modal khi nhấn ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeComboModal();
    }
});
</script>

<!-- Combo Modal -->
<div id="comboModal" class="combo-modal">
    <div class="combo-modal-content">
        <button class="combo-modal-close" onclick="closeComboModal()">
            <i class="fas fa-times"></i>
        </button>
        
        <div class="combo-modal-header">
            <h2 id="comboModalTitle">Combo</h2>
            <div class="combo-modal-discount" id="comboModalDiscount"></div>
        </div>
        
        <div class="combo-items-list" id="comboItemsContainer">
            <!-- Items sẽ được thêm bằng JS -->
        </div>
        
        <div class="combo-modal-footer">
            <div class="combo-price-info">
                <div class="combo-original-price">
                    <span><?php echo $current_lang === 'en' ? 'Original:' : 'Giá gốc:'; ?></span>
                    <span id="comboTotalPrice">0đ</span>
                </div>
                <div class="combo-final-price">
                    <span><?php echo $current_lang === 'en' ? 'Combo price:' : 'Giá combo:'; ?></span>
                    <span id="comboDiscountedPrice">0đ</span>
                </div>
            </div>
            <button class="combo-order-btn" onclick="orderCombo()">
                <i class="fas fa-shopping-cart"></i>
                <?php echo $current_lang === 'en' ? 'Order Combo' : 'Đặt Combo'; ?>
            </button>
        </div>
    </div>
</div>

<style>
/* Combo Modal */
.combo-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.6);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.combo-modal-content {
    background: white;
    border-radius: 16px;
    max-width: 500px;
    width: 100%;
    max-height: 80vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    position: relative;
    animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.combo-modal-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 36px;
    height: 36px;
    border: none;
    background: #f3f4f6;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6b7280;
    transition: all 0.3s;
    z-index: 10;
}

.combo-modal-close:hover {
    background: #ef4444;
    color: white;
}

.combo-modal-header {
    padding: 1.5rem;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: white;
    text-align: center;
}

.combo-modal-header h2 {
    margin: 0 0 0.5rem;
    font-size: 1.5rem;
}

.combo-modal-discount {
    background: rgba(255,255,255,0.2);
    display: inline-block;
    padding: 0.35rem 1rem;
    border-radius: 20px;
    font-weight: 700;
    font-size: 0.9rem;
}

.combo-items-list {
    padding: 1rem;
    overflow-y: auto;
    flex: 1;
    max-height: 300px;
}

.combo-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem;
    background: #f9fafb;
    border-radius: 10px;
    margin-bottom: 0.75rem;
}

.combo-item:last-child {
    margin-bottom: 0;
}

.combo-item-image {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
}

.combo-item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.combo-item-placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.combo-item-info {
    flex: 1;
}

.combo-item-info h4 {
    margin: 0 0 0.25rem;
    font-size: 0.95rem;
    color: #1f2937;
}

.combo-item-qty {
    color: #6b7280;
    font-size: 0.85rem;
}

.combo-item-price {
    font-weight: 700;
    color: #22c55e;
    font-size: 0.95rem;
}

.combo-empty {
    text-align: center;
    padding: 2rem;
    color: #9ca3af;
}

.combo-empty i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.combo-modal-footer {
    padding: 1rem 1.5rem;
    background: #f9fafb;
    border-top: 1px solid #e5e7eb;
}

.combo-price-info {
    display: flex;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.combo-original-price {
    color: #9ca3af;
    font-size: 0.9rem;
}

.combo-original-price span:last-child {
    text-decoration: line-through;
}

.combo-final-price {
    color: #ef4444;
    font-weight: 700;
    font-size: 1.1rem;
}

.combo-order-btn {
    width: 100%;
    padding: 0.875rem;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: all 0.3s;
}

.combo-order-btn:hover {
    background: linear-gradient(135deg, #16a34a, #15803d);
    transform: translateY(-2px);
}

/* Dark theme */
body.dark-theme .combo-modal-content {
    background: white;
}

body.dark-theme .combo-item {
    background: #f9fafb;
}

body.dark-theme .combo-item-info h4 {
    color: #1f2937;
}

body.dark-theme .combo-modal-footer {
    background: #f9fafb;
    border-color: #e5e7eb;
}

@media (max-width: 640px) {
    .combo-modal-content {
        max-height: 90vh;
    }
    
    .combo-items-list {
        max-height: 250px;
    }
}

/* Combo Notification */
.combo-notification {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    z-index: 10000;
    transform: translateX(120%);
    transition: transform 0.3s ease;
    border: 2px solid #22c55e;
    max-width: 400px;
}

.combo-notification.show {
    transform: translateX(0);
}

.combo-notification-content {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.25rem;
}

.combo-notification-content > i {
    font-size: 2rem;
    color: #22c55e;
}

.combo-notification-content strong {
    display: block;
    color: #1f2937;
    font-size: 1rem;
}

.combo-notification-content p {
    margin: 0.25rem 0 0;
    color: #6b7280;
    font-size: 0.85rem;
}

.combo-notification-btn {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.85rem;
    white-space: nowrap;
    transition: all 0.3s;
}

.combo-notification-btn:hover {
    background: linear-gradient(135deg, #16a34a, #15803d);
}

@media (max-width: 640px) {
    .combo-notification {
        bottom: 1rem;
        right: 1rem;
        left: 1rem;
        max-width: none;
    }
}
</style>

<style>
body.dark-theme .promo-highlight {
    background: #f0fdf4;
    color: #15803d;
    border-color: #86efac;
}

body.dark-theme .quick-link {
    background: #f0fdf4;
    color: #15803d;
    border-color: #dcfce7;
}

body.dark-theme .quick-link:hover {
    background: #22c55e;
    color: white;
    border-color: #22c55e;
}
</style>
