<?php
session_start();
require_once 'includes/language-helper.php';

// Test thay đổi ngôn ngữ
if (isset($_GET['lang'])) {
    $_SESSION['language'] = $_GET['lang'];
    setcookie('language', $_GET['lang'], time() + (86400 * 365), '/');
}

$current_lang = getCurrentLanguage();
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Translation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        .lang-switcher {
            margin-bottom: 30px;
        }
        .lang-switcher a {
            padding: 10px 20px;
            margin-right: 10px;
            background: #dc2626;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .lang-switcher a.active {
            background: #ea580c;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background: #f5f5f5;
        }
        .current-lang {
            background: #e8f5e9;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <h1>Test Translation System</h1>
    
    <div class="current-lang">
        <strong>Current Language:</strong> <?php echo $current_lang; ?>
    </div>
    
    <div class="lang-switcher">
        <a href="?lang=vi" class="<?php echo $current_lang == 'vi' ? 'active' : ''; ?>">🇻🇳 Tiếng Việt</a>
        <a href="?lang=en" class="<?php echo $current_lang == 'en' ? 'active' : ''; ?>">🇬🇧 English</a>
    </div>
    
    <h2>Translation Test</h2>
    <table>
        <thead>
            <tr>
                <th>Key</th>
                <th>Translation</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>home</td>
                <td><?php echo __('home'); ?></td>
            </tr>
            <tr>
                <td>about</td>
                <td><?php echo __('about'); ?></td>
            </tr>
            <tr>
                <td>menu</td>
                <td><?php echo __('menu'); ?></td>
            </tr>
            <tr>
                <td>contact</td>
                <td><?php echo __('contact'); ?></td>
            </tr>
            <tr>
                <td>reservation</td>
                <td><?php echo __('reservation'); ?></td>
            </tr>
            <tr>
                <td>cart</td>
                <td><?php echo __('cart'); ?></td>
            </tr>
            <tr>
                <td>orders</td>
                <td><?php echo __('orders'); ?></td>
            </tr>
            <tr>
                <td>profile</td>
                <td><?php echo __('profile'); ?></td>
            </tr>
            <tr>
                <td>login</td>
                <td><?php echo __('login'); ?></td>
            </tr>
            <tr>
                <td>register</td>
                <td><?php echo __('register'); ?></td>
            </tr>
            <tr>
                <td>logout</td>
                <td><?php echo __('logout'); ?></td>
            </tr>
            <tr>
                <td>search</td>
                <td><?php echo __('search'); ?></td>
            </tr>
            <tr>
                <td>add_to_cart</td>
                <td><?php echo __('add_to_cart'); ?></td>
            </tr>
            <tr>
                <td>checkout</td>
                <td><?php echo __('checkout'); ?></td>
            </tr>
            <tr>
                <td>total</td>
                <td><?php echo __('total'); ?></td>
            </tr>
        </tbody>
    </table>
    
    <h2>Debug Info</h2>
    <pre><?php 
    echo "Session Language: " . ($_SESSION['language'] ?? 'not set') . "\n";
    echo "Cookie Language: " . ($_COOKIE['language'] ?? 'not set') . "\n";
    echo "Current Language: " . $current_lang . "\n";
    echo "\nAll Translations:\n";
    print_r(loadLanguage($current_lang));
    ?></pre>
</body>
</html>
