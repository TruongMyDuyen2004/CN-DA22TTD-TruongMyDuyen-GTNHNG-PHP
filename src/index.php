<?php
session_start();
require_once 'config/database.php';
require_once 'includes/language-helper.php';

// Xử lý trang invoice riêng (không dùng layout chung)
if (isset($_GET['page']) && $_GET['page'] === 'invoice') {
    include 'pages/invoice.php';
    exit;
}

// Xử lý thay đổi ngôn ngữ từ URL
if (isset($_GET['lang'])) {
    $_SESSION['language'] = $_GET['lang'];
    setcookie('language', $_GET['lang'], time() + (86400 * 365), '/');
    // Redirect để xóa parameter lang khỏi URL
    $redirect_url = 'index.php';
    if (isset($_GET['page'])) {
        $redirect_url .= '?page=' . $_GET['page'];
    }
    header("Location: $redirect_url");
    exit;
}

$current_lang = getCurrentLanguage();
$page = isset($_GET['page']) ? $_GET['page'] : 'home';
$title = 'Ngon Gallery - ' . __('menu_subtitle');
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link rel="icon" type="image/jpeg" href="assets/images/logo.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="assets/images/logo.jpg">
    <link rel="apple-touch-icon" href="assets/images/logo.jpg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;500;600;700&family=Great+Vibes&family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/improvements.css">
    <link rel="stylesheet" href="assets/css/modern-effects.css">
    <link rel="stylesheet" href="assets/css/language-switcher.css">
    <link rel="stylesheet" href="assets/css/cart.css">
    <link rel="stylesheet" href="assets/css/ai-chat.css">
    <link rel="stylesheet" href="assets/css/dark-modern-theme.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/profile-modern.css?v=<?php echo time(); ?>">
</head>
<body class="dark-theme">
    <?php include 'includes/header.php'; ?>
    
    <main>
        <?php
        switch($page) {
            case 'about':
                include 'pages/about.php';
                break;
            case 'menu':
                include 'pages/menu.php';
                break;
            case 'contact':
                include 'pages/contact.php';
                break;
            case 'my-contacts':
                include 'pages/my-contacts-v2.php';
                break;
            case 'reservation':
                include 'pages/reservation.php';
                break;
            case 'my-reservations':
                include 'pages/my-reservations.php';
                break;
            case 'profile':
                include 'pages/profile.php';
                break;
            case 'favorites':
                include 'pages/favorites.php';
                break;
            case 'member-card':
                include 'pages/member-card.php';
                break;
            case 'cart':
                include 'pages/cart.php';
                break;
            case 'checkout':
                include 'pages/checkout.php';
                break;
            case 'orders':
                include 'pages/orders.php';
                break;
            case 'review':
                include 'pages/review.php';
                break;
            case 'all-reviews':
                include 'pages/all-reviews.php';
                break;
            case 'promotions':
                include 'pages/promotions.php';
                break;
            case 'help':
                include 'pages/help.php';
                break;
            case 'menu-item-detail':
                include 'pages/menu-item-detail.php';
                break;
            case 'news':
                include 'pages/news.php';
                break;
            case 'news-detail':
                include 'pages/news-detail.php';
                break;
            case 'vouchers':
                include 'pages/vouchers.php';
                break;
            case 'my-points':
                include 'pages/my-points.php';
                break;
            default:
                include 'pages/home.php';
        }
        ?>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Pass translations to JavaScript
        window.translations = {
            cart_title: '<?php echo __('cart_title'); ?>',
            empty_cart: '<?php echo __('empty_cart'); ?>',
            total: '<?php echo __('total'); ?>',
            view_cart: '<?php echo __('view_cart'); ?>',
            checkout: '<?php echo __('checkout'); ?>',
            error_occurred: '<?php echo __('error_occurred'); ?>',
            confirm_remove: '<?php echo __('confirm_remove'); ?>'
        };
    </script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/ai-chat.js"></script>
    <?php if (isset($_SESSION['customer_id'])): ?>
    <script src="assets/js/cart.js"></script>
    <?php endif; ?>
</body>
</html>
