<?php
session_start();

// Xóa tất cả combo đã áp dụng
unset($_SESSION['applied_combos']);
unset($_SESSION['applied_promo']);

echo "<h2>Đã xóa tất cả combo trong session!</h2>";
echo "<p>Bây giờ bạn có thể thử đặt lại combo.</p>";
echo "<a href='index.php?page=promotions'>Quay lại trang khuyến mãi</a> | ";
echo "<a href='index.php?page=cart'>Xem giỏ hàng</a>";

// Hiển thị session hiện tại
echo "<h3>Session hiện tại:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
