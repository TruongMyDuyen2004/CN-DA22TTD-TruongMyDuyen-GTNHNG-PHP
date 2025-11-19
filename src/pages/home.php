<section class="hero">
    <div class="hero-particles"></div>
    <div class="hero-content">
        <div class="hero-badge"><?php echo __('hero_badge'); ?></div>
        <h2 class="hero-title"><?php echo __('hero_title'); ?><br><span class="gradient-text">Ngon Gallery</span></h2>
        <p class="hero-description"><?php echo __('hero_description'); ?></p>
        <div class="hero-buttons">
            <a href="index.php?page=menu" class="btn btn-primary btn-hero">
                <i class="fas fa-book-open"></i> <?php echo __('view_menu'); ?>
            </a>
            <a href="index.php?page=reservation" class="btn btn-outline btn-hero">
                <i class="fas fa-calendar-check"></i> <?php echo __('book_table'); ?>
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
    <div class="hero-scroll">
        <i class="fas fa-chevron-down"></i>
    </div>
</section>

<section class="features">
    <div class="container">
        <div class="section-header">
            <span class="section-badge"><?php echo __('features_badge'); ?></span>
            <h2><?php echo __('why_choose_us'); ?></h2>
            <p class="section-subtitle"><?php echo __('why_choose_subtitle'); ?></p>
        </div>
        <div class="feature-grid">
            <div class="feature-item" data-aos="fade-up" data-aos-delay="0">
                <div class="feature-icon-wrapper">
                    <div class="icon">🍜</div>
                </div>
                <h3><?php echo __('traditional_food'); ?></h3>
                <p><?php echo __('traditional_food_desc'); ?></p>
                <a href="index.php?page=menu" class="feature-link"><?php echo __('discover'); ?> <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="feature-item" data-aos="fade-up" data-aos-delay="100">
                <div class="feature-icon-wrapper">
                    <div class="icon">🌿</div>
                </div>
                <h3><?php echo __('fresh_ingredients'); ?></h3>
                <p><?php echo __('fresh_ingredients_desc'); ?></p>
                <a href="index.php?page=about" class="feature-link"><?php echo __('learn_more'); ?> <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="feature-item" data-aos="fade-up" data-aos-delay="200">
                <div class="feature-icon-wrapper">
                    <div class="icon">👨‍🍳</div>
                </div>
                <h3><?php echo __('professional_chefs'); ?></h3>
                <p><?php echo __('professional_chefs_desc'); ?></p>
                <a href="index.php?page=about" class="feature-link"><?php echo __('about'); ?> <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="feature-item" data-aos="fade-up" data-aos-delay="300">
                <div class="feature-icon-wrapper">
                    <div class="icon">🏠</div>
                </div>
                <h3><?php echo __('cozy_space'); ?></h3>
                <p><?php echo __('cozy_space_desc'); ?></p>
                <a href="index.php?page=reservation" class="feature-link"><?php echo __('reservation'); ?> <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
    </div>
</section>

<section class="menu-preview">
    <div class="container">
        <div class="section-header">
            <span class="section-badge"><?php echo __('menu'); ?></span>
            <h2><?php echo __('featured_dishes'); ?></h2>
            <p class="section-subtitle"><?php echo __('featured_dishes_desc'); ?></p>
        </div>
        <div class="menu-preview-grid">
            <?php
            // Load featured dishes from database
            $db = new Database();
            $conn = $db->connect();
            $current_lang = getCurrentLanguage();
            
            // Get 3 featured dishes
            $stmt = $conn->prepare("SELECT * FROM menu_items WHERE is_available = 1 ORDER BY id LIMIT 3");
            $stmt->execute();
            $featured_dishes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($featured_dishes as $dish):
                $name = $current_lang === 'en' && !empty($dish['name_en']) ? $dish['name_en'] : $dish['name'];
                $description = $current_lang === 'en' && !empty($dish['description_en']) ? $dish['description_en'] : $dish['description'];
            ?>
            <div class="menu-preview-item">
                <div class="menu-preview-image"><?php echo $dish['image'] ?? '🍜'; ?></div>
                <div class="menu-preview-content">
                    <h4><?php echo htmlspecialchars($name); ?></h4>
                    <p><?php echo htmlspecialchars($description); ?></p>
                    <div class="menu-preview-footer">
                        <span class="price-tag"><?php echo number_format($dish['price'], 0, ',', '.'); ?>đ</span>
                        <span class="rating">⭐ 4.9</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="text-align: center; margin-top: 3rem;">
            <a href="index.php?page=menu" class="btn btn-primary btn-large">
                <?php echo __('view_full_menu'); ?> <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>



<section class="cta-section">
    <div class="container">
        <div class="cta-content">
            <h2><?php echo __('ready_to_experience'); ?></h2>
            <p><?php echo __('book_today_desc'); ?></p>
            <a href="index.php?page=reservation" class="btn btn-primary btn-large">
                <i class="fas fa-calendar-check"></i> <?php echo __('book_now'); ?>
            </a>
        </div>
    </div>
</section>



