<section class="hero">
    <div class="hero-particles"></div>
    <div class="hero-content">
        <h2 class="hero-title"><span class="gradient-text">Ngon Gallery</span></h2>
        <p class="hero-subtitle">Vietnamese Gallery</p>
        <div class="hero-buttons">
            <a href="index.php?page=reservation" class="btn btn-primary btn-hero">
                ĐẶT BÀN NGAY
            </a>
        </div>
        <div class="hero-stats">
            <div class="stat-item">
                <div class="stat-number">10+</div>
                <div class="stat-label"><?php echo __('years_experience'); ?></div>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
                <div class="stat-number">50+</div>
                <div class="stat-label"><?php echo __('special_dishes'); ?></div>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
                <div class="stat-number">10K+</div>
                <div class="stat-label"><?php echo __('happy_customers'); ?></div>
            </div>
        </div>
    </div>
    
    <!-- Featured Dish Popup - Món mới nhất từ Admin -->
    <?php
    // Lấy món ăn mới nhất được thêm trong 7 ngày gần đây
    $new_dish = null;
    try {
        if (class_exists('Database')) {
            $db_featured = new Database();
            $conn_featured = $db_featured->connect();
            
            if ($conn_featured) {
                $current_lang_featured = function_exists('getCurrentLanguage') ? getCurrentLanguage() : 'vi';
                
                $stmt_new = $conn_featured->prepare("
                    SELECT mi.*, c.name as category_name 
                    FROM menu_items mi 
                    LEFT JOIN categories c ON mi.category_id = c.id
                    WHERE mi.is_available = 1 
                    AND mi.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    ORDER BY mi.created_at DESC 
                    LIMIT 1
                ");
                $stmt_new->execute();
                $new_dish = $stmt_new->fetch(PDO::FETCH_ASSOC);
                
                // Nếu không có món mới trong 7 ngày, lấy món mới nhất
                if (!$new_dish) {
                    $stmt_latest = $conn_featured->prepare("
                        SELECT mi.*, c.name as category_name 
                        FROM menu_items mi 
                        LEFT JOIN categories c ON mi.category_id = c.id
                        WHERE mi.is_available = 1 
                        ORDER BY mi.created_at DESC 
                        LIMIT 1
                    ");
                    $stmt_latest->execute();
                    $new_dish = $stmt_latest->fetch(PDO::FETCH_ASSOC);
                }
            }
        }
    } catch (Exception $e) {
        $new_dish = null;
    }
    
    if ($new_dish):
        $current_lang_featured = isset($current_lang_featured) ? $current_lang_featured : 'vi';
        $dish_name = $current_lang_featured === 'en' && !empty($new_dish['name_en']) ? $new_dish['name_en'] : $new_dish['name'];
        $dish_desc = $current_lang_featured === 'en' && !empty($new_dish['description_en']) ? $new_dish['description_en'] : ($new_dish['description'] ?? '');
        $dish_category = $new_dish['category_name'] ?? 'Món ăn';
        
        // Xử lý hình ảnh
        $dish_image = $new_dish['image'] ?? '';
        $is_valid_img = !empty($dish_image) && (
            strpos($dish_image, 'http') === 0 || 
            strpos($dish_image, 'uploads/') === 0 ||
            strpos($dish_image, '/uploads/') === 0
        );
        $display_img = $is_valid_img ? $dish_image : 'https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?w=400&q=80';
        
        // Kiểm tra xem món có phải mới trong 7 ngày không
        $is_new = isset($new_dish['created_at']) && strtotime($new_dish['created_at']) >= strtotime('-7 days');
        $badge_text = $is_new ? 'Món mới' : $dish_category;
    ?>
    <div class="hero-featured-dish" id="newDishPopup">
        <div class="featured-dish-card">
            <button class="close-featured" onclick="closeNewDishPopup()">×</button>
            <?php if($is_new): ?>
            <div class="featured-dish-badge new-badge">
                <i class="fas fa-sparkles"></i> <?php echo $badge_text; ?>
            </div>
            <?php else: ?>
            <div class="featured-dish-badge"><?php echo htmlspecialchars($badge_text); ?></div>
            <?php endif; ?>
            <a href="index.php?page=menu-item-detail&id=<?php echo $new_dish['id']; ?>" class="featured-dish-link">
                <div class="featured-dish-image">
                    <img src="<?php echo htmlspecialchars($display_img); ?>" 
                         alt="<?php echo htmlspecialchars($dish_name); ?>"
                         onerror="this.src='https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?w=400&q=80'">
                </div>
                <div class="featured-dish-info">
                    <h4><?php echo htmlspecialchars($dish_name); ?></h4>
                    <p class="featured-dish-meta"><?php echo htmlspecialchars(mb_substr($dish_desc, 0, 50)) . (mb_strlen($dish_desc) > 50 ? '...' : ''); ?></p>
                    <div class="featured-dish-price">
                        <span class="price"><?php echo number_format($new_dish['price'], 0, ',', '.'); ?>đ</span>
                        <?php if(!empty($new_dish['discount_percent']) && $new_dish['discount_percent'] > 0): ?>
                        <span class="discount">-<?php echo $new_dish['discount_percent']; ?>%</span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        </div>
    </div>
    
    <script>
    // Lưu trạng thái đã đóng popup vào sessionStorage
    function closeNewDishPopup() {
        document.getElementById('newDishPopup').style.display = 'none';
        sessionStorage.setItem('newDishClosed_<?php echo $new_dish['id']; ?>', 'true');
    }
    
    // Kiểm tra xem đã đóng popup chưa
    document.addEventListener('DOMContentLoaded', function() {
        if (sessionStorage.getItem('newDishClosed_<?php echo $new_dish['id']; ?>')) {
            document.getElementById('newDishPopup').style.display = 'none';
        }
    });
    </script>
    <?php endif; ?>
    
    <div class="section-scroll" onclick="document.querySelector('.about-intro').scrollIntoView({behavior: 'smooth'})">
        <i class="fas fa-chevron-down"></i>
    </div>
</section>

<section class="about-intro" id="about-intro">
    <div class="container">
        <div class="section-header-center">
            <span class="section-badge"><?php echo __('features_badge'); ?></span>
            <h2><?php echo __('why_choose_us'); ?></h2>
            <p class="section-subtitle"><?php echo __('why_choose_subtitle'); ?></p>
        </div>
        <div class="about-intro-grid">
            <div class="about-intro-image">
                <img src="https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=600&q=80" alt="Ngon Gallery Restaurant" onerror="this.src='https://images.unsplash.com/photo-1552566626-52f8b828add9?w=600&q=80'">
                <div class="experience-badge">
                    <span class="exp-number">10+</span>
                    <span class="exp-text"><?php echo __('years_experience'); ?></span>
                </div>
            </div>
            <div class="about-intro-content">
                <div class="features-list">
                    <div class="feature-row">
                        <div class="feature-icon"><i class="fas fa-utensils"></i></div>
                        <div class="feature-text">
                            <h4><?php echo __('traditional_food'); ?></h4>
                            <p><?php echo __('traditional_food_desc'); ?></p>
                        </div>
                    </div>
                    <div class="feature-row">
                        <div class="feature-icon"><i class="fas fa-leaf"></i></div>
                        <div class="feature-text">
                            <h4><?php echo __('fresh_ingredients'); ?></h4>
                            <p><?php echo __('fresh_ingredients_desc'); ?></p>
                        </div>
                    </div>
                    <div class="feature-row">
                        <div class="feature-icon"><i class="fas fa-user-tie"></i></div>
                        <div class="feature-text">
                            <h4><?php echo __('professional_chefs'); ?></h4>
                            <p><?php echo __('professional_chefs_desc'); ?></p>
                        </div>
                    </div>
                    <div class="feature-row">
                        <div class="feature-icon"><i class="fas fa-home"></i></div>
                        <div class="feature-text">
                            <h4><?php echo __('cozy_space'); ?></h4>
                            <p><?php echo __('cozy_space_desc'); ?></p>
                        </div>
                    </div>
                </div>
                <div class="intro-buttons">
                    <a href="index.php?page=about" class="btn btn-primary"><?php echo __('learn_more'); ?></a>
                    <a href="index.php?page=reservation" class="btn btn-outline"><?php echo __('book_table'); ?></a>
                </div>
            </div>
        </div>
        <div class="intro-stats">
            <div class="intro-stat-item">
                <span class="stat-icon"><i class="fas fa-bowl-food"></i></span>
                <span class="stat-value">50+</span>
                <span class="stat-label"><?php echo __('special_dishes'); ?></span>
            </div>
            <div class="intro-stat-item">
                <span class="stat-icon"><i class="fas fa-users"></i></span>
                <span class="stat-value">10K+</span>
                <span class="stat-label"><?php echo __('happy_customers'); ?></span>
            </div>
            <div class="intro-stat-item">
                <span class="stat-icon"><i class="fas fa-star"></i></span>
                <span class="stat-value">4.9</span>
                <span class="stat-label">Đánh giá</span>
            </div>
            <div class="intro-stat-item">
                <span class="stat-icon"><i class="fas fa-award"></i></span>
                <span class="stat-value">5★</span>
                <span class="stat-label">Chất lượng</span>
            </div>
        </div>
    </div>
    <div class="section-scroll" onclick="document.querySelector('#menu-preview').scrollIntoView({behavior: 'smooth'})">
        <i class="fas fa-chevron-down"></i>
    </div>
</section>

<!-- Section Divider -->
<div class="section-divider">
    <div class="divider-line"></div>
    <div class="divider-icon"><i class="fas fa-utensils"></i></div>
    <div class="divider-line"></div>
</div>

<section class="menu-preview" id="menu-preview">
    <div class="container">
        <div class="section-header">
            <span class="section-badge"><?php echo __('menu'); ?></span>
            <h2><?php echo __('featured_dishes'); ?></h2>
            <p class="section-subtitle"><?php echo __('featured_dishes_desc'); ?></p>
        </div>
        <div class="menu-preview-grid">
            <?php
            // Load featured dishes from database
            $featured_dishes = [];
            $current_lang = function_exists('getCurrentLanguage') ? getCurrentLanguage() : 'vi';
            
            // Default images for dishes without images
            $default_images = [
                'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&q=80',
                'https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?w=400&q=80',
                'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=400&q=80'
            ];
            
            try {
                if (class_exists('Database')) {
                    $db = new Database();
                    $conn = $db->connect();
                    
                    if ($conn) {
                        // Get 3 featured dishes
                        $stmt = $conn->prepare("SELECT * FROM menu_items WHERE is_available = 1 ORDER BY id LIMIT 3");
                        $stmt->execute();
                        $featured_dishes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    }
                }
            } catch (Exception $e) {
                $featured_dishes = [];
            }
            
            $index = 0;
            foreach ($featured_dishes as $dish):
                $name = $current_lang === 'en' && !empty($dish['name_en']) ? $dish['name_en'] : $dish['name'];
                $description = $current_lang === 'en' && !empty($dish['description_en']) ? $dish['description_en'] : $dish['description'];
                
                // Check if image is a valid URL or path
                $image = $dish['image'] ?? '';
                $is_valid_image = !empty($image) && (
                    strpos($image, 'http') === 0 || 
                    strpos($image, 'uploads/') === 0 ||
                    strpos($image, '/uploads/') === 0
                );
                $display_image = $is_valid_image ? $image : $default_images[$index % 3];
                
                // Get discount info
                $discount = $dish['discount_percent'] ?? 0;
                $has_discount = $discount > 0;
                $original_price = $dish['original_price'] ?? $dish['price'];
            ?>
            <a href="index.php?page=menu-item-detail&id=<?php echo $dish['id']; ?>" class="menu-preview-item">
                <div class="menu-preview-image">
                    <?php if($has_discount): ?>
                    <span class="discount-badge">-<?php echo $discount; ?>%</span>
                    <?php endif; ?>
                    <img src="<?php echo htmlspecialchars($display_image); ?>" 
                         alt="<?php echo htmlspecialchars($name); ?>"
                         onerror="this.src='<?php echo $default_images[$index % 3]; ?>'">
                </div>
                <div class="menu-preview-content">
                    <h4><?php echo htmlspecialchars($name); ?></h4>
                    <p><?php echo htmlspecialchars(mb_substr($description, 0, 60)) . (mb_strlen($description) > 60 ? '...' : ''); ?></p>
                    <div class="menu-preview-footer">
                        <div class="price-wrapper">
                            <span class="price-tag"><?php echo number_format($dish['price'], 0, ',', '.'); ?>đ</span>
                            <?php if($has_discount): ?>
                            <span class="old-price"><?php echo number_format($original_price, 0, ',', '.'); ?>đ</span>
                            <?php endif; ?>
                        </div>
                        <span class="rating">⭐ 4.9</span>
                    </div>
                </div>
            </a>
            <?php 
                $index++;
            endforeach; 
            ?>
        </div>
        <div class="menu-preview-cta">
            <a href="index.php?page=menu" class="btn-view-menu">
                <span><?php echo __('view_full_menu'); ?></span>
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
    <div class="section-scroll" onclick="document.querySelector('#cta-section').scrollIntoView({behavior: 'smooth'})">
        <i class="fas fa-chevron-down"></i>
    </div>
</section>

<!-- Section Divider 2 -->
<div class="section-divider section-divider-2">
    <div class="divider-line"></div>
    <div class="divider-icon"><i class="fas fa-star"></i></div>
    <div class="divider-line"></div>
</div>

<section class="cta-section-modern" id="cta-section">
    <div class="cta-background">
        <div class="cta-overlay"></div>
        <div class="cta-particles">
            <span></span><span></span><span></span><span></span><span></span>
        </div>
    </div>
    <div class="container">
        <div class="cta-content-modern">
            <div class="cta-icon-wrapper">
                <div class="cta-icon">
                    <i class="fas fa-utensils"></i>
                </div>
                <div class="cta-icon-ring"></div>
            </div>
            <h2 class="cta-title"><?php echo __('ready_to_experience'); ?></h2>
            <p class="cta-subtitle"><?php echo __('book_today_desc'); ?></p>

            <a href="index.php?page=reservation" class="cta-btn-modern">
                <span class="btn-bg"></span>
                <i class="fas fa-calendar-check"></i> 
                <span class="btn-text"><?php echo __('book_now'); ?></span>
                <i class="fas fa-arrow-right btn-arrow"></i>
            </a>
        </div>
    </div>
</section>



