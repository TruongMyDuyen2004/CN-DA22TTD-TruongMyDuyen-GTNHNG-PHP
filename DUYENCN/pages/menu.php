<?php
$db = new Database();
$conn = $db->connect();
$current_lang = getCurrentLanguage();

// Lấy danh sách món yêu thích của user (nếu đã đăng nhập)
$user_favorites = [];
if (isset($_SESSION['customer_id'])) {
    try {
        $stmt = $conn->prepare("SELECT menu_item_id FROM favorites WHERE customer_id = ?");
        $stmt->execute([$_SESSION['customer_id']]);
        $user_favorites = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        // Bảng chưa tồn tại, bỏ qua
    }
}

// Lấy khuyến mãi từ URL nếu có
$promo_id = isset($_GET['promo_id']) ? intval($_GET['promo_id']) : 0;
$selected_promo = null;
if ($promo_id > 0) {
    try {
        $stmt = $conn->prepare("SELECT * FROM restaurant_promotions WHERE id = ? AND is_active = 1");
        $stmt->execute([$promo_id]);
        $selected_promo = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

// Lấy danh mục
$stmt = $conn->prepare("SELECT * FROM categories ORDER BY display_order");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy filter từ URL
$category_filter = $_GET['category'] ?? '';
$price_filter = $_GET['price'] ?? '';
$search = trim($_GET['search'] ?? '');
?>

<!-- Menu Hero Section -->
<section class="about-hero">
    <div class="about-hero-content">
        <span class="section-badge"><?php echo $current_lang === 'en' ? 'Menu' : 'Thực đơn'; ?></span>
        <h1 class="about-hero-title"><?php echo $current_lang === 'en' ? 'Menu Ngon Gallery' : 'Thực Đơn Ngon Gallery'; ?></h1>
        <p class="about-hero-subtitle"><?php echo $current_lang === 'en' ? 'Discover our delicious dishes' : 'Khám phá các món ăn ngon của chúng tôi'; ?></p>
    </div>
</section>

<!-- Menu Main Content -->
<section class="menu-page">
    <div class="menu-container">
    
        <?php // Hiển thị khuyến mãi đã chọn từ trang promotions ?>
        <?php if ($selected_promo): ?>
        <div class="menu-promo-banner">
            <div class="menu-promo-banner-icon">
                <i class="fas fa-gift"></i>
            </div>
            <div class="menu-promo-banner-content">
                <strong>🎉 <?php echo $current_lang === 'en' ? 'Promotion Applied!' : 'Ưu đãi đã được áp dụng!'; ?></strong>
                <span><?php echo htmlspecialchars($selected_promo['title']); ?> - <?php echo htmlspecialchars($selected_promo['discount_text']); ?></span>
                <small><?php echo $current_lang === 'en' ? 'Add items to cart to use this promotion' : 'Thêm món vào giỏ hàng để sử dụng ưu đãi này'; ?></small>
            </div>
            <a href="?page=cart&promo_id=<?php echo $selected_promo['id']; ?>" class="menu-promo-btn">
                <i class="fas fa-shopping-cart"></i> <?php echo $current_lang === 'en' ? 'View Cart' : 'Xem giỏ hàng'; ?>
            </a>
        </div>
        <?php endif; ?>
        
        <!-- Menu Header Compact -->
        <div class="menu-header-compact">
            <!-- Search Box Inline -->
            <div class="menu-search-inline">
            <form method="GET" action="" class="search-form">
                <input type="hidden" name="page" value="menu">
                <?php if($category_filter): ?>
                <input type="hidden" name="category" value="<?php echo $category_filter; ?>">
                <?php endif; ?>
                <div class="search-input-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" 
                           name="search" 
                           placeholder="<?php echo $current_lang === 'en' ? 'Search dishes by name...' : 'Tìm kiếm món ăn theo tên...'; ?>" 
                           value="<?php echo htmlspecialchars($search); ?>"
                           class="search-input"
                           autocomplete="off">
                    <?php if($search): ?>
                    <a href="?page=menu<?php echo $category_filter ? '&category='.$category_filter : ''; ?>" class="clear-search">
                        <i class="fas fa-times"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <button type="submit" class="search-btn">
                    <i class="fas fa-search"></i>
                    <?php echo $current_lang === 'en' ? 'Search' : 'Tìm kiếm'; ?>
                </button>
            </form>
            <?php if($search): ?>
            <?php endif; ?>
            </div>
        </div>
        
        <?php if($search): ?>
        <div class="search-result-info">
            <?php echo $current_lang === 'en' ? 'Search results for' : 'Kết quả tìm kiếm cho'; ?>: 
            <strong>"<?php echo htmlspecialchars($search); ?>"</strong>
        </div>
        <?php endif; ?>
        
        <!-- Category Tabs - Lấy từ database -->
        <div class="menu-tabs">
            <a href="index.php?page=menu<?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="menu-tab <?php echo empty($category_filter) ? 'active' : ''; ?>">
                <?php echo $current_lang === 'en' ? 'All' : 'Tất cả'; ?>
            </a>
            <?php foreach ($categories as $cat): 
                $cat_name = $current_lang === 'en' && !empty($cat['name_en']) ? $cat['name_en'] : $cat['name'];
            ?>
            <a href="index.php?page=menu&category=<?php echo $cat['id']; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" 
               class="menu-tab <?php echo $category_filter == $cat['id'] ? 'active' : ''; ?>">
                <?php echo htmlspecialchars($cat_name); ?>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="menu-layout">
            <!-- Menu Content -->
            <div class="menu-content">
                <!-- Filter Toggle Button -->
                <div class="filter-toggle-wrapper">
                    <button class="filter-toggle-btn" onclick="toggleFilter()">
                        <i class="fas fa-sliders-h"></i>
                        <span>Bộ lọc</span>
                    </button>
                </div>
            
            <!-- Sidebar Filter -->
            <aside class="menu-sidebar" id="menuSidebar">
                <div class="sidebar-header">
                    <h3>Bộ lọc</h3>
                    <button class="close-filter-btn" onclick="toggleFilter()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="GET" action="" id="filterForm">
                    <input type="hidden" name="page" value="menu">
                    <?php if($category_filter): ?>
                    <input type="hidden" name="category" value="<?php echo $category_filter; ?>">
                    <?php endif; ?>

                    <!-- Price Filter -->
                    <div class="filter-section">
                        <h4>Mức giá</h4>
                        <label class="filter-checkbox">
                            <input type="checkbox" name="price[]" value="under100" <?php echo (is_array($_GET['price'] ?? null) && in_array('under100', $_GET['price'])) ? 'checked' : ''; ?>>
                            <span>Dưới 100K</span>
                        </label>
                        <label class="filter-checkbox">
                            <input type="checkbox" name="price[]" value="100-200" <?php echo (is_array($_GET['price'] ?? null) && in_array('100-200', $_GET['price'])) ? 'checked' : ''; ?>>
                            <span>100K - 200K</span>
                        </label>
                        <label class="filter-checkbox">
                            <input type="checkbox" name="price[]" value="200-500" <?php echo (is_array($_GET['price'] ?? null) && in_array('200-500', $_GET['price'])) ? 'checked' : ''; ?>>
                            <span>200K - 500K</span>
                        </label>
                        <label class="filter-checkbox">
                            <input type="checkbox" name="price[]" value="500-1000" <?php echo (is_array($_GET['price'] ?? null) && in_array('500-1000', $_GET['price'])) ? 'checked' : ''; ?>>
                            <span>500K - 1 triệu</span>
                        </label>
                        <label class="filter-checkbox">
                            <input type="checkbox" name="price[]" value="over1000" <?php echo (is_array($_GET['price'] ?? null) && in_array('over1000', $_GET['price'])) ? 'checked' : ''; ?>>
                            <span>Trên 1 triệu</span>
                        </label>
                    </div>

                    <!-- Favorites Filter -->
                    <?php if(isset($_SESSION['customer_id'])): ?>
                    <div class="filter-section">
                        <h4>Yêu thích</h4>
                        <label class="filter-checkbox">
                            <input type="checkbox" name="favorites" value="1" <?php echo isset($_GET['favorites']) && $_GET['favorites'] == '1' ? 'checked' : ''; ?>>
                            <span>Món yêu thích</span>
                        </label>
                    </div>
                    <?php endif; ?>

                    <!-- Discount Filter -->
                    <div class="filter-section">
                        <h4>Khuyến mãi</h4>
                        <label class="filter-checkbox">
                            <input type="checkbox" name="discount" value="1" <?php echo isset($_GET['discount']) && $_GET['discount'] == '1' ? 'checked' : ''; ?>>
                            <span>Đang giảm giá</span>
                        </label>
                    </div>

                    <!-- Region Filter - Lọc theo vùng miền -->
                    <div class="filter-section">
                        <h4>Vùng miền</h4>
                        <label class="filter-checkbox">
                            <input type="checkbox" name="region[]" value="mien_bac" <?php echo (is_array($_GET['region'] ?? null) && in_array('mien_bac', $_GET['region'])) ? 'checked' : ''; ?>>
                            <span>Miền Bắc</span>
                        </label>
                        <label class="filter-checkbox">
                            <input type="checkbox" name="region[]" value="mien_trung" <?php echo (is_array($_GET['region'] ?? null) && in_array('mien_trung', $_GET['region'])) ? 'checked' : ''; ?>>
                            <span>Miền Trung</span>
                        </label>
                        <label class="filter-checkbox">
                            <input type="checkbox" name="region[]" value="mien_nam" <?php echo (is_array($_GET['region'] ?? null) && in_array('mien_nam', $_GET['region'])) ? 'checked' : ''; ?>>
                            <span>Miền Nam</span>
                        </label>
                        <label class="filter-checkbox">
                            <input type="checkbox" name="region[]" value="quoc_te" <?php echo (is_array($_GET['region'] ?? null) && in_array('quoc_te', $_GET['region'])) ? 'checked' : ''; ?>>
                            <span>Quốc tế</span>
                        </label>
                    </div>

                    <button type="submit" class="filter-btn">Lọc món ăn</button>
                    
                    <?php 
                    $has_filter = !empty($_GET['price']) || !empty($_GET['discount']) || !empty($_GET['region']) || !empty($_GET['favorites']);
                    if ($has_filter): 
                    ?>
                    <a href="?page=menu<?php echo $category_filter ? '&category='.$category_filter : ''; ?>" class="filter-reset-btn">
                        <i class="fas fa-redo"></i> Xóa bộ lọc
                    </a>
                    <?php endif; ?>
                </form>
            </aside>


            <!-- Menu Grid -->
                <div class="menu-grid">
                    <?php
                    // Build query - Lấy tất cả món (cả còn và hết)
                    $sql = "SELECT m.*, c.name as category_name FROM menu_items m 
                            LEFT JOIN categories c ON m.category_id = c.id 
                            WHERE 1=1";
                    $params = [];
                    
                    // Search by name
                    if ($search) {
                        $sql .= " AND (m.name LIKE ? OR m.name_en LIKE ? OR m.description LIKE ?)";
                        $search_term = "%$search%";
                        $params[] = $search_term;
                        $params[] = $search_term;
                        $params[] = $search_term;
                    }
                    
                    // Filter by category ID
                    if ($category_filter && is_numeric($category_filter)) {
                        $sql .= " AND m.category_id = ?";
                        $params[] = $category_filter;
                    }
                    
                    // Price filter
                    if (!empty($_GET['price']) && is_array($_GET['price'])) {
                        $price_conditions = [];
                        foreach ($_GET['price'] as $p) {
                            switch($p) {
                                case 'under100':
                                    $price_conditions[] = "m.price < 100000";
                                    break;
                                case '100-200':
                                    $price_conditions[] = "(m.price >= 100000 AND m.price < 200000)";
                                    break;
                                case '200-500':
                                    $price_conditions[] = "(m.price >= 200000 AND m.price < 500000)";
                                    break;
                                case '500-1000':
                                    $price_conditions[] = "(m.price >= 500000 AND m.price < 1000000)";
                                    break;
                                case 'over1000':
                                    $price_conditions[] = "m.price >= 1000000";
                                    break;
                            }
                        }
                        if (!empty($price_conditions)) {
                            $sql .= " AND (" . implode(" OR ", $price_conditions) . ")";
                        }
                    }
                    
                    // Favorites filter - Lọc món yêu thích
                    if (isset($_GET['favorites']) && $_GET['favorites'] == '1' && isset($_SESSION['customer_id'])) {
                        $sql .= " AND m.id IN (SELECT menu_item_id FROM favorites WHERE customer_id = ?)";
                        $params[] = $_SESSION['customer_id'];
                    }
                    
                    // Discount filter - Lọc món đang giảm giá
                    if (isset($_GET['discount']) && $_GET['discount'] == '1') {
                        $sql .= " AND m.discount_percent > 0";
                    }
                    
                    // Region filter - Lọc theo vùng miền
                    if (!empty($_GET['region']) && is_array($_GET['region'])) {
                        $region_placeholders = [];
                        foreach ($_GET['region'] as $region) {
                            $region_placeholders[] = "?";
                            $params[] = $region;
                        }
                        $sql .= " AND m.region IN (" . implode(",", $region_placeholders) . ")";
                    }
                    
                    // Sắp xếp: Còn món trước, hết món sau
                    $sql .= " ORDER BY m.is_available DESC, m.name";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute($params);
                    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (count($items) > 0):
                        foreach($items as $item):
                            $item_name = $current_lang === 'en' && !empty($item['name_en']) ? $item['name_en'] : $item['name'];
                            
                            // Lấy giảm giá từ database
                            $discount = $item['discount_percent'] ?? 0;
                            $has_discount = $discount > 0;
                            $original_price = $item['original_price'] ?? $item['price'];
                            
                            // Kiểm tra còn món hay hết
                            $is_available = $item['is_available'] == 1;
                    ?>
                    <div class="menu-card <?php echo !$is_available ? 'out-of-stock' : ''; ?>">
                        <div class="menu-card-image">
                            <?php if(!$is_available): ?>
                            <div class="out-of-stock-overlay">
                                <span class="out-of-stock-badge">Hết món</span>
                            </div>
                            <?php endif; ?>
                            <?php if($has_discount && $is_available): ?>
                            <span class="discount-badge">-<?php echo $discount; ?>%</span>
                            <?php endif; ?>
                            <?php if($is_available): ?>
                            <?php $is_favorited = in_array($item['id'], $user_favorites); ?>
                            <button class="favorite-btn <?php echo $is_favorited ? 'active' : ''; ?>" onclick="toggleFavorite(this, <?php echo $item['id']; ?>)" title="<?php echo $is_favorited ? 'Bỏ yêu thích' : 'Thêm vào yêu thích'; ?>">
                                <i class="<?php echo $is_favorited ? 'fas' : 'far'; ?> fa-heart"></i>
                            </button>
                            <?php endif; ?>
                            <?php if($item['image']): ?>
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item_name); ?>">
                            <?php else: ?>
                                <img src="https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&q=80" alt="<?php echo htmlspecialchars($item_name); ?>">
                            <?php endif; ?>
                        </div>
                        <div class="menu-card-content">
                            <h3 class="menu-card-title"><?php echo htmlspecialchars($item_name); ?></h3>
                            <div class="menu-card-price">
                                <span class="current-price"><?php echo number_format($item['price'], 0, ',', '.'); ?>đ</span>
                                <?php if($has_discount && $is_available): ?>
                                <span class="old-price"><?php echo number_format($original_price, 0, ',', '.'); ?>đ</span>
                                <?php endif; ?>
                            </div>
                            <?php if($is_available): ?>
                            <a href="index.php?page=menu-item-detail&id=<?php echo $item['id']; ?>" class="view-detail-btn">
                                Xem chi tiết
                            </a>
                            <?php else: ?>
                            <span class="view-detail-btn disabled">
                                <i class="fas fa-ban"></i> Tạm hết
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php 
                        endforeach;
                    else:
                    ?>
                    <div class="no-items">
                        <i class="fas fa-search" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem;"></i>
                        <p><?php echo $current_lang === 'en' ? 'No dishes found.' : 'Không tìm thấy món ăn nào.'; ?></p>
                        <?php if($search): ?>
                        <p style="color: #9ca3af; margin-top: 0.5rem;">
                            <?php echo $current_lang === 'en' ? 'Try different keywords' : 'Thử từ khóa khác'; ?>
                        </p>
                        <a href="?page=menu" class="btn-reset-search">
                            <i class="fas fa-redo"></i>
                            <?php echo $current_lang === 'en' ? 'View all dishes' : 'Xem tất cả món'; ?>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* Menu Promo Banner */
.menu-promo-banner {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(34, 197, 94, 0.05));
    border: 2px solid #22c55e;
    border-radius: 14px;
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
}

.menu-promo-banner-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.menu-promo-banner-icon i {
    color: white;
    font-size: 1.25rem;
}

.menu-promo-banner-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
}

.menu-promo-banner-content strong {
    color: #15803d;
    font-size: 0.95rem;
}

.menu-promo-banner-content span {
    color: #374151;
    font-size: 0.9rem;
}

.menu-promo-banner-content small {
    color: #6b7280;
    font-size: 0.8rem;
}

.menu-promo-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: white;
    padding: 0.6rem 1.25rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.85rem;
    transition: all 0.3s;
    white-space: nowrap;
}

.menu-promo-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
    color: white;
}

/* Dark theme */
body.dark-theme .menu-promo-banner {
    background: rgba(34, 197, 94, 0.1);
    border-color: rgba(34, 197, 94, 0.5);
}

body.dark-theme .menu-promo-banner-content strong {
    color: #22c55e;
}

body.dark-theme .menu-promo-banner-content span {
    color: #e2e8f0;
}

body.dark-theme .menu-promo-banner-content small {
    color: #94a3b8;
}

@media (max-width: 640px) {
    .menu-promo-banner {
        flex-direction: column;
        text-align: center;
    }
    
    .menu-promo-btn {
        width: 100%;
        justify-content: center;
    }
}

/* Out of Stock Styles */
.menu-card.out-of-stock {
    opacity: 0.6;
    filter: grayscale(40%);
    position: relative;
}

.menu-card.out-of-stock:hover {
    opacity: 0.75;
    transform: none;
    box-shadow: none;
}

.menu-card.out-of-stock .menu-card-image img {
    filter: grayscale(30%);
}

.out-of-stock-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
    border-radius: inherit;
}

.out-of-stock-badge {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: #ffffff;
    padding: 10px 24px;
    border-radius: 25px;
    font-size: 1rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
}

.view-detail-btn.disabled {
    background: #9ca3af !important;
    color: #ffffff !important;
    cursor: not-allowed !important;
    pointer-events: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    justify-content: center;
}

.view-detail-btn.disabled:hover {
    transform: none !important;
    box-shadow: none !important;
}

/* Modern Compact Header */
.menu-header-compact {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1.5rem;
    padding: 0.75rem 0 1rem;
    flex-wrap: wrap;
}

.menu-title-modern {
    font-size: 1.75rem;
    font-weight: 700;
    color: #ffffff;
    margin: 0;
    letter-spacing: 0.1em;
    text-transform: uppercase;
}

.menu-search-inline {
    flex: 0 1 400px;
    max-width: 450px;
}

.menu-search-inline .search-form {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.menu-search-inline .search-input-wrapper {
    flex: 1;
}

.menu-search-inline .search-input {
    padding: 0.75rem 1.2rem 0.75rem 2.8rem;
    font-size: 1rem;
    border-radius: 25px;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.15);
    color: #fff;
}

.menu-search-inline .search-input::placeholder {
    color: rgba(255, 255, 255, 0.5);
}

.menu-search-inline .search-input:focus {
    border-color: #d4a574;
    background: rgba(255, 255, 255, 0.12);
    box-shadow: 0 0 0 2px rgba(212, 165, 116, 0.2);
}

.menu-search-inline .search-btn {
    padding: 0.75rem 1.5rem;
    font-size: 1rem;
    border-radius: 25px;
    background: linear-gradient(135deg, #d4a574 0%, #c89456 100%);
    border: none;
    color: #1a1a1a;
    font-weight: 600;
}

.search-result-info {
    text-align: left;
    margin-bottom: 1rem;
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.9rem;
}

/* Search Box Styles */
.search-form {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

.search-input-wrapper {
    flex: 1;
    position: relative;
}

.search-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    font-size: 1rem;
}

.search-input {
    width: 100%;
    padding: 1rem 3rem 1rem 3rem;
    border: 2px solid #e5e7eb;
    border-radius: 50px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: white;
}

body.dark-theme .search-input {
    background: #fefce8;
    border: 2px solid #22c55e;
    color: #1e293b;
}

body.dark-theme .search-input::placeholder {
    color: #64748b;
}

.search-input:focus {
    outline: none;
    border-color: #f97316;
    box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
}

.clear-search {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    text-decoration: none;
    padding: 0.25rem;
    border-radius: 50%;
    transition: all 0.2s;
}

.clear-search:hover {
    color: #ef4444;
    background: rgba(239, 68, 68, 0.1);
}

.search-btn {
    padding: 1rem 1.5rem;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: white;
    border: none;
    border-radius: 50px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    white-space: nowrap;
}

.search-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(34, 197, 94, 0.4);
}

.search-result-info {
    text-align: center;
    margin-top: 1rem;
    color: #6b7280;
    font-size: 0.95rem;
}

body.dark-theme .search-result-info {
    color: rgba(255, 255, 255, 0.7);
}

.search-result-info strong {
    color: #f97316;
}

.btn-reset-search {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 1rem;
    padding: 0.75rem 1.5rem;
    background: #f97316;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-reset-search:hover {
    background: #ea580c;
    transform: translateY(-2px);
}

.no-items {
    text-align: center;
    padding: 3rem;
    grid-column: 1 / -1;
}

@media (max-width: 640px) {
    .search-form {
        flex-direction: column;
    }
    
    .search-input-wrapper {
        width: 100%;
    }
    
    .search-btn {
        width: 100%;
        justify-content: center;
    }
}

/* Filter Toggle Button & Collapsible Sidebar */
.filter-toggle-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.75rem 1.25rem;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    border: none;
    color: #ffffff;
    border-radius: 50px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    margin-bottom: 1rem;
    font-size: 0.9rem;
    box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
}

.filter-toggle-btn:hover {
    background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4);
}

.filter-toggle-btn i {
    font-size: 1rem;
}

.filter-toggle-wrapper {
    margin-bottom: 1.5rem;
}

/* Compact Menu Tabs */
body.dark-theme .menu-tabs {
    margin-bottom: 1rem !important;
    padding: 0.5rem 0 !important;
    gap: 0.4rem !important;
}

body.dark-theme .menu-tab {
    padding: 0.6rem 1.2rem !important;
    font-size: 1rem !important;
    border-radius: 6px !important;
}

body.dark-theme .menu-layout {
    display: block !important;
    max-width: 100%;
}

body.dark-theme .menu-sidebar {
    position: fixed;
    left: -290px;
    top: 0;
    height: 100vh;
    width: 270px;
    z-index: 1000;
    transition: left 0.3s ease;
    overflow-y: auto;
    background: #0f172a;
    border-radius: 0;
    border-right: 1px solid rgba(34, 197, 94, 0.2);
    padding-top: 0;
    box-shadow: 4px 0 20px rgba(0, 0, 0, 0.3);
}

body.dark-theme .menu-sidebar.active {
    left: 0;
}

.sidebar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.85rem 1rem;
    border-bottom: 1px solid #e5e7eb;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    position: sticky;
    top: 0;
    z-index: 10;
}

.sidebar-header h3 {
    color: #ffffff;
    margin: 0;
    font-size: 0.95rem;
    font-weight: 600;
}

.close-filter-btn {
    background: transparent;
    border: none;
    color: #ffffff;
    font-size: 1.1rem;
    cursor: pointer;
    padding: 0.35rem;
    border-radius: 50%;
    transition: all 0.2s ease;
    line-height: 1;
}

.close-filter-btn:hover {
    background: rgba(255, 255, 255, 0.2);
}

.filter-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    z-index: 999;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.filter-overlay.active {
    opacity: 1;
    visibility: visible;
}

/* Filter Section Styles - Dark Theme */
body.dark-theme .filter-section {
    margin: 0.5rem 0.75rem;
    padding: 0.75rem 1rem;
    background: #1e293b;
    border-radius: 10px;
    border: 1px solid rgba(34, 197, 94, 0.2);
}

body.dark-theme .filter-section h4 {
    color: #22c55e;
    font-size: 0.75rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    padding-bottom: 0.4rem;
    border-bottom: 1px solid rgba(34, 197, 94, 0.2);
}

body.dark-theme .filter-checkbox {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.25rem;
    cursor: pointer;
    color: #e2e8f0;
    font-size: 0.85rem;
    padding: 0.4rem 0.5rem;
    border-radius: 6px;
    transition: all 0.2s ease;
    background: transparent;
}

body.dark-theme .filter-checkbox:hover {
    background: rgba(34, 197, 94, 0.15);
}

body.dark-theme .filter-checkbox:has(input:checked) {
    background: rgba(34, 197, 94, 0.25);
    color: #22c55e;
    font-weight: 500;
}

body.dark-theme .filter-checkbox input[type="checkbox"] {
    width: 16px;
    height: 16px;
    accent-color: #22c55e;
    cursor: pointer;
    flex-shrink: 0;
}

body.dark-theme .filter-checkbox span {
    flex: 1;
    line-height: 1.2;
}

body.dark-theme .filter-btn {
    margin: 0.75rem 0.75rem 0.5rem;
    width: calc(100% - 1.5rem);
    padding: 0.65rem 1rem;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    border: none;
    border-radius: 8px;
    color: #ffffff;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

body.dark-theme .filter-btn:hover {
    background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
    transform: translateY(-1px);
}

/* Reset Filter Button */
.filter-reset-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin: 0.25rem 0.75rem 0.5rem;
    width: calc(100% - 1.5rem);
    padding: 0.5rem 1rem;
    background: transparent;
    border: 1px solid rgba(239, 68, 68, 0.3);
    border-radius: 6px;
    color: #f87171;
    font-weight: 500;
    font-size: 0.85rem;
    text-decoration: none;
    transition: all 0.2s ease;
}

.filter-reset-btn:hover {
    border-color: #ef4444;
    color: #ef4444;
    background: rgba(239, 68, 68, 0.1);
}

body.dark-theme .menu-content {
    width: 100% !important;
    max-width: 100%;
}

body.dark-theme .menu-grid {
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)) !important;
}

body.dark-theme .menu-container {
    max-width: 1600px !important;
    padding: 0 1rem !important;
}

/* Custom Scrollbar for Sidebar */
body.dark-theme .menu-sidebar::-webkit-scrollbar {
    width: 4px;
}

body.dark-theme .menu-sidebar::-webkit-scrollbar-track {
    background: #1e293b;
}

body.dark-theme .menu-sidebar::-webkit-scrollbar-thumb {
    background: #334155;
    border-radius: 2px;
}

body.dark-theme .menu-sidebar::-webkit-scrollbar-thumb:hover {
    background: #22c55e;
}
</style>

<div class="filter-overlay" id="filterOverlay" onclick="toggleFilter()"></div>

<script>
function toggleFilter() {
    const sidebar = document.getElementById('menuSidebar');
    const overlay = document.getElementById('filterOverlay');
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
    document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
}

// Toggle Favorite
function toggleFavorite(btn, itemId) {
    fetch('api/favorites.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=toggle&menu_item_id=' + itemId
    })
    .then(response => response.json())
    .then(data => {
        if (data.require_login) {
            // Chưa đăng nhập
            if (confirm('Vui lòng đăng nhập để sử dụng tính năng yêu thích. Đăng nhập ngay?')) {
                window.location.href = 'auth/login.php';
            }
            return;
        }
        
        if (data.success) {
            const icon = btn.querySelector('i');
            if (data.action === 'added') {
                btn.classList.add('active');
                icon.classList.remove('far');
                icon.classList.add('fas');
                btn.title = 'Bỏ yêu thích';
                // Animation
                btn.style.transform = 'scale(1.3)';
                setTimeout(() => btn.style.transform = '', 200);
            } else {
                btn.classList.remove('active');
                icon.classList.remove('fas');
                icon.classList.add('far');
                btn.title = 'Thêm vào yêu thích';
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}
</script>
