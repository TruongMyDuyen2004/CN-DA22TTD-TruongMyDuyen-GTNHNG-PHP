# Hướng dẫn đa ngôn ngữ - Ngon Gallery

## Tính năng

✅ Chuyển đổi ngôn ngữ dễ dàng
✅ Lưu lựa chọn trong session và cookie
✅ Giao diện đẹp với dropdown menu
✅ Hỗ trợ 5 ngôn ngữ: Việt, Anh, Hàn, Nhật, Trung
✅ Responsive trên mọi thiết bị

## Cài đặt

### 1. Files đã tạo

```
includes/
├── language-switcher.php    # Component chuyển ngôn ngữ
└── language-helper.php       # Helper functions

lang/
├── vi.php                    # Tiếng Việt
├── en.php                    # English
└── ko.php                    # Korean

api/
└── change-language.php       # API thay đổi ngôn ngữ

assets/css/
└── language-switcher.css     # CSS cho language switcher
```

### 2. Thêm vào header

File `includes/header.php` đã được cập nhật với language switcher.

### 3. Load helper functions

Thêm vào đầu `index.php`:

```php
require_once 'includes/language-helper.php';
```

## Sử dụng

### Hiển thị text đã dịch

```php
// Cách 1: Hàm __()
echo __('home');  // Output: "Trang chủ" (nếu ngôn ngữ là vi)

// Cách 2: Với default value
echo __('unknown_key', 'Default text');

// Cách 3: Với tham số
echo __f('welcome_user', ['name' => 'John']);
```

### Trong HTML

```php
<h1><?php echo __('menu_title'); ?></h1>
<p><?php echo __('menu_subtitle'); ?></p>
```

### Trong navigation

```php
<a href="?page=home"><?php echo __('home'); ?></a>
<a href="?page=menu"><?php echo __('menu'); ?></a>
```

## Thêm ngôn ngữ mới

### 1. Tạo file ngôn ngữ

Tạo file `lang/ja.php` (Japanese):

```php
<?php
return [
    'home' => 'ホーム',
    'about' => '私たちについて',
    'menu' => 'メニュー',
    // ... thêm các key khác
];
```

### 2. Cập nhật danh sách ngôn ngữ

Trong `includes/language-switcher.php`:

```php
$languages = [
    'vi' => ['name' => 'Tiếng Việt', 'flag' => '🇻🇳'],
    'en' => ['name' => 'English', 'flag' => '🇬🇧'],
    'ko' => ['name' => 'Korean', 'flag' => '🇰🇷'],
    'ja' => ['name' => 'Japanese', 'flag' => '🇯🇵'],  // Thêm dòng này
    'zh' => ['name' => 'Chinese', 'flag' => '🇨🇳']
];
```

### 3. Cập nhật API

Trong `api/change-language.php`:

```php
$supported_languages = ['vi', 'en', 'ko', 'ja', 'zh'];
```

## Cấu trúc file ngôn ngữ

```php
<?php
return [
    // Header
    'home' => 'Trang chủ',
    'about' => 'Giới thiệu',
    
    // Common
    'welcome' => 'Chào mừng',
    'search' => 'Tìm kiếm',
    
    // Menu
    'menu_title' => 'Thực đơn',
    'available' => 'Còn món',
    
    // Cart
    'cart_title' => 'Giỏ hàng',
    'empty_cart' => 'Giỏ hàng trống',
];
```

## API

### POST /api/change-language.php

**Request:**
```json
{
    "language": "en"
}
```

**Response:**
```json
{
    "success": true,
    "language": "en",
    "message": "Language changed successfully"
}
```

## Helper Functions

### getCurrentLanguage()
Lấy ngôn ngữ hiện tại.

```php
$lang = getCurrentLanguage(); // 'vi', 'en', 'ko', etc.
```

### loadLanguage($lang)
Load file ngôn ngữ.

```php
$translations = loadLanguage('en');
```

### __($key, $default)
Dịch một key.

```php
echo __('home');  // "Trang chủ"
echo __('unknown', 'Default');  // "Default"
```

### __f($key, $params)
Dịch với tham số.

```php
// Trong file ngôn ngữ:
'welcome_user' => 'Xin chào {name}!'

// Sử dụng:
echo __f('welcome_user', ['name' => 'John']);
// Output: "Xin chào John!"
```

### getAvailableLanguages()
Lấy danh sách ngôn ngữ.

```php
$languages = getAvailableLanguages();
```

## CSS Classes

### .language-switcher
Container chính.

### .language-btn
Button hiển thị ngôn ngữ hiện tại.

### .language-menu
Dropdown menu chứa các ngôn ngữ.

### .language-option
Mỗi option trong menu.

### .language-option.active
Option đang được chọn.

## Customization

### Thay đổi vị trí

```css
.language-switcher {
    position: fixed;
    top: 20px;
    right: 20px;
}
```

### Thay đổi màu sắc

```css
.language-btn {
    background: #your-color;
    border-color: #your-color;
}
```

### Thay đổi animation

```css
.language-menu.show {
    animation: yourAnimation 0.3s ease;
}
```

## Best Practices

### 1. Naming Convention
- Sử dụng snake_case cho keys
- Nhóm theo chức năng (header_, menu_, cart_)
- Tên rõ ràng, dễ hiểu

### 2. Fallback
- Luôn có default value
- Fallback về tiếng Việt nếu file không tồn tại
- Hiển thị key nếu không tìm thấy translation

### 3. Performance
- Cache translations trong static variable
- Load một lần duy nhất
- Sử dụng session để lưu lựa chọn

### 4. Maintenance
- Giữ các file ngôn ngữ đồng bộ
- Comment cho các key phức tạp
- Version control cho translations

## Troubleshooting

### Ngôn ngữ không thay đổi
- Kiểm tra session đã start chưa
- Xóa cache browser
- Kiểm tra cookie settings

### File ngôn ngữ không load
- Kiểm tra đường dẫn file
- Kiểm tra quyền đọc file
- Kiểm tra syntax PHP

### Text không dịch
- Kiểm tra key có trong file ngôn ngữ không
- Kiểm tra đã gọi loadLanguage() chưa
- Kiểm tra typo trong key

## Examples

### Example 1: Menu Page

```php
<?php require_once 'includes/language-helper.php'; ?>

<h1><?php echo __('menu_title'); ?></h1>
<p><?php echo __('menu_subtitle'); ?></p>

<button><?php echo __('add_to_cart'); ?></button>
```

### Example 2: Cart Page

```php
<h2><?php echo __('cart_title'); ?></h2>

<?php if (empty($cart_items)): ?>
    <p><?php echo __('empty_cart'); ?></p>
<?php endif; ?>

<div class="total">
    <?php echo __('total'); ?>: <?php echo $total; ?>đ
</div>
```

### Example 3: Form

```php
<form>
    <label><?php echo __('search'); ?></label>
    <input type="text" placeholder="<?php echo __('search'); ?>">
    
    <button><?php echo __('submit'); ?></button>
</form>
```

## Future Enhancements

- [ ] Auto-detect browser language
- [ ] RTL support (Arabic, Hebrew)
- [ ] Translation management panel
- [ ] Export/Import translations
- [ ] Pluralization support
- [ ] Date/Time localization
- [ ] Number formatting
- [ ] Currency conversion

## Version History

### v1.0 (Current)
- 5 languages support
- Session & cookie storage
- Beautiful UI
- Helper functions
- API endpoint
