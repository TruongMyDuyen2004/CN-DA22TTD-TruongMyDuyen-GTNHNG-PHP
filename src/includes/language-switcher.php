<?php
// Lấy ngôn ngữ hiện tại
$current_lang = $_SESSION['language'] ?? 'vi';
if (!in_array($current_lang, ['vi', 'en'])) {
    $current_lang = 'vi';
}
// Ngôn ngữ sẽ chuyển sang
$next_lang = $current_lang === 'vi' ? 'en' : 'vi';
// Flag image cho ngôn ngữ hiện tại (dùng flagcdn.com)
$current_flag_code = $current_lang === 'vi' ? 'vn' : 'gb';
?>

<button class="lang-switch-btn" onclick="switchLanguage('<?php echo $next_lang; ?>')" title="<?php echo $next_lang === 'en' ? 'Switch to English' : 'Chuyển sang Tiếng Việt'; ?>">
    <img class="lang-flag-img" src="https://flagcdn.com/w40/<?php echo $current_flag_code; ?>.png" alt="<?php echo strtoupper($current_flag_code); ?>">
</button>

<script>
function switchLanguage(lang) {
    // Lưu vị trí scroll hiện tại
    sessionStorage.setItem('scrollPos', window.scrollY);
    
    fetch('api/change-language.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ language: lang })
    })
    .then(r => r.json())
    .then(d => { if (d.success) location.reload(); });
}

// Khôi phục vị trí scroll sau khi reload
document.addEventListener('DOMContentLoaded', function() {
    const scrollPos = sessionStorage.getItem('scrollPos');
    if (scrollPos) {
        window.scrollTo(0, parseInt(scrollPos));
        sessionStorage.removeItem('scrollPos');
    }
});
</script>
