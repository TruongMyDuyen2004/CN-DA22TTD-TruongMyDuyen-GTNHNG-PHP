<?php
session_start();
require_once 'includes/language-helper.php';

// Test thay đổi ngôn ngữ
if (isset($_GET['lang'])) {
    $_SESSION['language'] = $_GET['lang'];
    setcookie('language', $_GET['lang'], time() + (86400 * 365), '/');
    header("Location: test-all-pages.php");
    exit;
}

$current_lang = getCurrentLanguage();
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <title>Test All Translations</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
        .lang-switcher { margin-bottom: 20px; padding: 15px; background: #f5f5f5; border-radius: 8px; }
        .lang-switcher a { padding: 10px 20px; margin-right: 10px; background: #dc2626; color: white; text-decoration: none; border-radius: 5px; }
        .lang-switcher a.active { background: #ea580c; }
        .section { margin: 20px 0; padding: 20px; background: white; border: 1px solid #ddd; border-radius: 8px; }
        .section h2 { color: #dc2626; border-bottom: 2px solid #dc2626; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #f5f5f5; font-weight: bold; }
        .current-lang { background: #e8f5e9; padding: 15px; margin-bottom: 20px; border-radius: 5px; font-size: 18px; }
    </style>
</head>
<body>
    <h1>🌐 Test All Translations - Ngon Gallery</h1>
    
    <div class="current-lang">
        <strong>Current Language:</strong> <?php echo $current_lang == 'vi' ? '🇻🇳 Tiếng Việt' : '🇬🇧 English'; ?>
    </div>
    
    <div class="lang-switcher">
        <strong>Switch Language:</strong>
        <a href="?lang=vi" class="<?php echo $current_lang == 'vi' ? 'active' : ''; ?>">🇻🇳 Tiếng Việt</a>
        <a href="?lang=en" class="<?php echo $current_lang == 'en' ? 'active' : ''; ?>">🇬🇧 English</a>
    </div>
    
    <!-- Navigation -->
    <div class="section">
        <h2>Navigation / Điều hướng</h2>
        <table>
            <tr><td>home</td><td><?php echo __('home'); ?></td></tr>
            <tr><td>about</td><td><?php echo __('about'); ?></td></tr>
            <tr><td>menu</td><td><?php echo __('menu'); ?></td></tr>
            <tr><td>contact</td><td><?php echo __('contact'); ?></td></tr>
            <tr><td>reservation</td><td><?php echo __('reservation'); ?></td></tr>
            <tr><td>cart</td><td><?php echo __('cart'); ?></td></tr>
            <tr><td>orders</td><td><?php echo __('orders'); ?></td></tr>
            <tr><td>profile</td><td><?php echo __('profile'); ?></td></tr>
            <tr><td>login</td><td><?php echo __('login'); ?></td></tr>
            <tr><td>register</td><td><?php echo __('register'); ?></td></tr>
            <tr><td>logout</td><td><?php echo __('logout'); ?></td></tr>
        </table>
    </div>
    
    <!-- Home Page -->
    <div class="section">
        <h2>Home Page / Trang chủ</h2>
        <table>
            <tr><td>hero_badge</td><td><?php echo __('hero_badge'); ?></td></tr>
            <tr><td>hero_title</td><td><?php echo __('hero_title'); ?></td></tr>
            <tr><td>hero_description</td><td><?php echo __('hero_description'); ?></td></tr>
            <tr><td>view_menu</td><td><?php echo __('view_menu'); ?></td></tr>
            <tr><td>book_table</td><td><?php echo __('book_table'); ?></td></tr>
            <tr><td>years_experience</td><td><?php echo __('years_experience'); ?></td></tr>
            <tr><td>special_dishes</td><td><?php echo __('special_dishes'); ?></td></tr>
            <tr><td>happy_customers</td><td><?php echo __('happy_customers'); ?></td></tr>
        </table>
    </div>
    
    <!-- Menu Page -->
    <div class="section">
        <h2>Menu Page / Trang thực đơn</h2>
        <table>
            <tr><td>menu_title</td><td><?php echo __('menu_title'); ?></td></tr>
            <tr><td>menu_subtitle</td><td><?php echo __('menu_subtitle'); ?></td></tr>
            <tr><td>search</td><td><?php echo __('search'); ?></td></tr>
            <tr><td>all_categories</td><td><?php echo __('all_categories'); ?></td></tr>
            <tr><td>available</td><td><?php echo __('available'); ?></td></tr>
            <tr><td>unavailable</td><td><?php echo __('unavailable'); ?></td></tr>
            <tr><td>add_to_cart</td><td><?php echo __('add_to_cart'); ?></td></tr>
        </table>
    </div>
    
    <!-- About Page -->
    <div class="section">
        <h2>About Page / Trang giới thiệu</h2>
        <table>
            <tr><td>about_title</td><td><?php echo __('about_title'); ?></td></tr>
            <tr><td>our_story</td><td><?php echo __('our_story'); ?></td></tr>
            <tr><td>our_mission</td><td><?php echo __('our_mission'); ?></td></tr>
            <tr><td>core_values</td><td><?php echo __('core_values'); ?></td></tr>
        </table>
    </div>
    
    <!-- Contact Page -->
    <div class="section">
        <h2>Contact Page / Trang liên hệ</h2>
        <table>
            <tr><td>contact_title</td><td><?php echo __('contact_title'); ?></td></tr>
            <tr><td>contact_info</td><td><?php echo __('contact_info'); ?></td></tr>
            <tr><td>send_message</td><td><?php echo __('send_message'); ?></td></tr>
            <tr><td>address</td><td><?php echo __('address'); ?></td></tr>
            <tr><td>phone_number</td><td><?php echo __('phone_number'); ?></td></tr>
            <tr><td>opening_hours</td><td><?php echo __('opening_hours'); ?></td></tr>
        </table>
    </div>
    
    <!-- Cart & Checkout -->
    <div class="section">
        <h2>Cart & Checkout / Giỏ hàng & Thanh toán</h2>
        <table>
            <tr><td>cart_title</td><td><?php echo __('cart_title'); ?></td></tr>
            <tr><td>empty_cart</td><td><?php echo __('empty_cart'); ?></td></tr>
            <tr><td>subtotal</td><td><?php echo __('subtotal'); ?></td></tr>
            <tr><td>delivery_fee</td><td><?php echo __('delivery_fee'); ?></td></tr>
            <tr><td>total</td><td><?php echo __('total'); ?></td></tr>
            <tr><td>checkout</td><td><?php echo __('checkout'); ?></td></tr>
        </table>
    </div>
    
    <div style="margin-top: 30px; padding: 20px; background: #e3f2fd; border-radius: 8px;">
        <h3>✅ Test Instructions:</h3>
        <ol>
            <li>Click on 🇻🇳 or 🇬🇧 to switch language</li>
            <li>Check if all translations change correctly</li>
            <li>Then visit actual pages: <a href="index.php">Home</a> | <a href="index.php?page=about">About</a> | <a href="index.php?page=menu">Menu</a> | <a href="index.php?page=contact">Contact</a></li>
        </ol>
    </div>
</body>
</html>
