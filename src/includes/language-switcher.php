<?php
// Danh sách ngôn ngữ
$languages = [
    'vi' => ['name' => 'Tiếng Việt', 'flag' => '🇻🇳'],
    'en' => ['name' => 'English', 'flag' => '🇬🇧']
];

// Lấy ngôn ngữ hiện tại từ session hoặc mặc định là 'vi'
$current_lang = $_SESSION['language'] ?? 'vi';

// Kiểm tra ngôn ngữ có hợp lệ không, nếu không thì reset về 'vi'
if (!isset($languages[$current_lang])) {
    $current_lang = 'vi';
    $_SESSION['language'] = 'vi';
}
?>

<div class="language-switcher">
    <button class="language-btn" onclick="toggleLanguageMenu()">
        <span class="current-lang-flag"><?php echo $languages[$current_lang]['flag']; ?></span>
        <span class="current-lang-name"><?php echo $languages[$current_lang]['name']; ?></span>
        <i class="fas fa-chevron-down"></i>
    </button>
    
    <div class="language-menu" id="languageMenu">
        <?php foreach ($languages as $code => $lang): ?>
            <a href="?lang=<?php echo $code; ?>" 
               class="language-option <?php echo $code === $current_lang ? 'active' : ''; ?>"
               onclick="changeLanguage('<?php echo $code; ?>')">
                <span class="lang-flag"><?php echo $lang['flag']; ?></span>
                <span class="lang-name"><?php echo $lang['name']; ?></span>
                <?php if ($code === $current_lang): ?>
                    <i class="fas fa-check"></i>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<script>
function toggleLanguageMenu() {
    const menu = document.getElementById('languageMenu');
    menu.classList.toggle('show');
}

function changeLanguage(lang) {
    // Gửi request để thay đổi ngôn ngữ
    fetch('api/change-language.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ language: lang })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

// Đóng menu khi click bên ngoài
document.addEventListener('click', function(event) {
    const switcher = document.querySelector('.language-switcher');
    if (switcher && !switcher.contains(event.target)) {
        document.getElementById('languageMenu').classList.remove('show');
    }
});
</script>
