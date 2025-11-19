<header id="header">
    <div class="header-container">
        <div class="logo">
            <a href="index.php">
                <h1>Ngon Gallery</h1>
                <p class="tagline"><?php echo __('tagline'); ?></p>
            </a>
        </div>
        <nav class="main-nav">
            <ul class="nav-menu">
                <li><a href="index.php?page=home" class="nav-link <?php echo ($page == 'home') ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i> <?php echo __('home'); ?>
                </a></li>
                <li><a href="index.php?page=about" class="nav-link <?php echo ($page == 'about') ? 'active' : ''; ?>">
                    <i class="fas fa-info-circle"></i> <?php echo __('about'); ?>
                </a></li>
                <li><a href="index.php?page=menu" class="nav-link <?php echo ($page == 'menu') ? 'active' : ''; ?>">
                    <i class="fas fa-utensils"></i> <?php echo __('menu'); ?>
                </a></li>
                <li><a href="index.php?page=all-reviews" class="nav-link <?php echo ($page == 'all-reviews') ? 'active' : ''; ?>">
                    <i class="fas fa-star"></i> <?php echo __('reviews'); ?>
                </a></li>
                <li><a href="index.php?page=contact" class="nav-link <?php echo ($page == 'contact') ? 'active' : ''; ?>">
                    <i class="fas fa-envelope"></i> <?php echo __('contact'); ?>
                </a></li>
                <li><a href="index.php?page=my-contacts" class="nav-link <?php echo ($page == 'my-contacts') ? 'active' : ''; ?>">
                    <i class="fas fa-envelope-open-text"></i> <?php echo __('my_messages'); ?>
                </a></li>
                
            </ul>
        </nav>
        
        <div class="header-actions">
            <?php include 'includes/language-switcher.php'; ?>
            
            <?php if (isset($_SESSION['customer_id'])): 
                // Lấy số lượng món trong giỏ và avatar
                try {
                    $db_cart = new Database();
                    $conn_cart = $db_cart->connect();
                    $stmt_cart = $conn_cart->prepare("SELECT SUM(quantity) as total FROM cart WHERE customer_id = ?");
                    $stmt_cart->execute([$_SESSION['customer_id']]);
                    $cart_count = $stmt_cart->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
                    
                    // Lấy avatar của user
                    $stmt_avatar = $conn_cart->prepare("SELECT avatar, full_name FROM customers WHERE id = ?");
                    $stmt_avatar->execute([$_SESSION['customer_id']]);
                    $user_data = $stmt_avatar->fetch(PDO::FETCH_ASSOC);
                    $user_avatar = !empty($user_data['avatar']) ? $user_data['avatar'] : '';
                    $user_name = !empty($user_data['full_name']) ? $user_data['full_name'] : ($_SESSION['customer_name'] ?? 'User');
                } catch (Exception $e) {
                    $cart_count = 0;
                    $user_avatar = '';
                    $user_name = $_SESSION['customer_name'] ?? 'User';
                }
            ?>
                <a href="index.php?page=reservation" class="btn-reservation">
                    <i class="fas fa-calendar-alt"></i> <?php echo __('reservation'); ?>
                </a>
                
                <a href="index.php?page=cart" class="cart-icon" onclick="event.preventDefault(); showMiniCart();" title="<?php echo __('cart'); ?>">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if ($cart_count > 0): ?>
                        <span class="cart-badge"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>
                
                <a href="index.php?page=orders" class="icon-btn" title="<?php echo __('orders'); ?>">
                    <i class="fas fa-box"></i>
                </a>
                
                <div class="user-dropdown">
                    <button class="user-btn">
                        <?php 
                        $show_avatar = false;
                        if (!empty($user_avatar)) {
                            // Kiểm tra nếu là URL hoặc file tồn tại
                            if (filter_var($user_avatar, FILTER_VALIDATE_URL) || @file_exists($user_avatar)) {
                                $show_avatar = true;
                            }
                        }
                        ?>
                        <?php if ($show_avatar): ?>
                            <img src="<?php echo htmlspecialchars($user_avatar); ?>?v=<?php echo time(); ?>" 
                                 alt="Avatar" 
                                 class="user-avatar-img" 
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="user-avatar-placeholder" style="display:none;">
                                <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                            </div>
                        <?php else: ?>
                            <div class="user-avatar-placeholder">
                                <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        <span><?php echo htmlspecialchars(explode(' ', $user_name)[0]); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="user-menu">
                        <a href="index.php?page=profile"><i class="fas fa-user"></i> <?php echo __('profile'); ?></a>
                        <a href="index.php?page=orders"><i class="fas fa-box"></i> <?php echo __('my_orders'); ?></a>
                        <a href="auth/logout.php"><i class="fas fa-sign-out-alt"></i> <?php echo __('logout'); ?></a>
                    </div>
                </div>
            <?php else: ?>
                <a href="index.php?page=reservation" class="btn-reservation">
                    <i class="fas fa-calendar-alt"></i> <?php echo __('reservation'); ?>
                </a>
                <a href="auth/login.php" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> <?php echo __('login'); ?>
                </a>
                <a href="auth/register.php" class="btn-register">
                    <i class="fas fa-user-plus"></i> <?php echo __('register'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>
